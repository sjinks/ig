<?php

namespace WildWolf;

use function Monolog\Handler\error_log;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use WildWolf\FBR\Response\CapturedFaces;
use WildWolf\FBR\Response\SearchCompleted;
use WildWolf\FBR\Response\SearchInProgress;
use WildWolf\FBR\Response\SearchUploadAck;

class SearchController extends BaseController
{
    /**
     * @var ImageUploader
     */
    private $uploader;

    /**
     * @var FBR\Client
     */
    private $fbr;

    /**
     * @var \Slim\Views\PhpRenderer
     */
    private $renderer;

    /**
     * @var AccountKit
     */
    private $acckit;

    /**
     * @var SepAPI
     */
    private $sepapi;

    /**
     * @var array
     */
    private $settings;

    public function __construct(ContainerInterface $container)
    {
        parent::__construct($container);

        $this->uploader = $container->get('uploader');
        $this->fbr      = $container->get('fbr');
        $this->renderer = $container->get('view');
        $this->acckit   = $container->get('acckit');
        $this->sepapi   = $container->get('sepapi');
        $this->settings = $container->get('settings');
    }

    public function start(ServerRequestInterface $request, ResponseInterface $response) : ResponseInterface
    {
        $get      = $request->getQueryParams();
        $code     = $get['error'] ?? 0;

        /**
         * @var User $user
         */
        $user           = $_SESSION['user'];
        $error          = $code ? self::getErrorByCode($code) : null;
        $skip_recaptcha = $user->isWhitelisted();

        return $this->renderer->render(
            $response,
            'upload.phtml',
            [
                'error'          => $error,
                'skip_recaptcha' => $skip_recaptcha,
                'title'          => 'Завантажити світлину',
                'recaptcha'      => $this->settings['recaptcha.public'],
                'footer_js'      => [
                    '/js/upload.min.js?v=9',
                    'https://www.google.com/recaptcha/api.js?onload=reCaptchaCallback&render=explicit',
                ],
            ]
        );
    }

    public function upload(ServerRequestInterface $request, ResponseInterface $response) : ResponseInterface
    {
        $files = $request->getUploadedFiles();
        $entry = $files['photo'] ?? null;
        try {
            $this->uploader->setFile($entry);
            $this->uploader->validateFile();

            /** @var \Psr\Http\Message\UploadedFileInterface $entry */
            /** @var string $fname */
            /// Scrutinizer does not understand that $entry is not null here :-(
            $fname = $entry->/** @scrutinizer ignore-call */getStream()->getMetadata('uri');
            if (!Utils::maybePreprocessImage($fname, 4)) {
                return $this->failure($response, self::ERROR_UPLOAD_FAILURE);
            }

            /** @var resource $f */
            $f = fopen($fname, 'rb'); // Will throw an exception on failure because of `set_error_handler()`
            $r = $this->fbr->uploadPhotoForSearch($f);
            fclose($f);
            if (!($r instanceof SearchUploadAck)) {
                return $this->failure($response, self::ERROR_GENERAL_FAILURE);
            }

            $guid = $r->serverRequestId();
            $file = $guid . '.jpg';
            $code = $this->trackUpload($guid, $request->getAttribute('REMOTE_ADDR', ''));

            if (!$code) {
                $this->uploader->save($file);
            }
        }
        catch (ImageUploaderException $e) {
            if ($entry) {
                $stream = $entry->getStream();
                $name   = $stream->getMetadata('uri');
                $stream->close();
                unlink($name);
            }

            return $this->failure($response, $e->getCode());
        }

        return $response
            ->withStatus(302)
            ->withHeader('Location', '/result/' . $guid)
        ;
    }

    private function trackUpload(string $guid, string $ip) : int
    {
        /**
         * @var \WildWolf\User $user
         */
        $user = $_SESSION['user'];
        $id   = $user->id();

        $response = $this->sepapi->trackUpload($id, $guid, $ip, time());

        if (is_scalar($response)) {
            switch ($response) {
                case -1:
                    return self::ERROR_NO_CREDITS;

                case -2:
                    return self::ERROR_BANNED;

                default:
                    // $_SESSION will be updated as well
                    $user->setCredits($response);
                    return 0;
            }
        }

        return self::ERROR_GENERAL_FAILURE;
    }

    public function result(/** @scrutinizer ignore-unused */ ServerRequestInterface $request, ResponseInterface $response, array $args) : ResponseInterface
    {
        $guid     = $args['guid'];
        $r        = $this->fbr->checkSearchStatus($guid);

        if ($r instanceof SearchInProgress) {
            return $this->renderer->render(
                $response,
                'wait.phtml',
                [
                    'title'     => 'Зачекайте, будь ласка',
                    'timeout'   => 10000,
                    'footer_js' => ['/js/wait.min.js?v=3'],
                ]
            );
        }

        if ($r instanceof SearchCompleted) {
            return $this->processResult($response, $guid, $r);
        }

        return $this->failure($response, self::ERROR_GENERAL_FAILURE);
    }

    private function processResult(ResponseInterface $response, string $guid, SearchCompleted $r)
    {
        /**
         * @var \WildWolf\User $user
         */
        $user = $_SESSION['user'] ?? null;
        if ($user && !$user->isPrivileged()) {
            $user->logout($this->acckit);
            unset($_SESSION['user']);
        }

        $stats = $this->fbr->getCapturedFaces($guid);
        if ($stats instanceof CapturedFaces) {
            $iframe = filter_input(INPUT_GET, 'iframe', FILTER_SANITIZE_NUMBER_INT);
            $data   = [
                'count'      => $r->resultsAmount(),
                'stats'      => $stats,
                'guid'       => $guid,
                'url'        => '/uploads/' . $this->uploader->getTargetName($guid . '.jpg'),
                'iframe'     => $iframe,
                'title'      => 'Результати розпізнавання',
                'header_css' => ['https://cdnjs.cloudflare.com/ajax/libs/fancybox/3.3.5/jquery.fancybox.min.css'],
                'footer_js'  => [
                    'https://cdnjs.cloudflare.com/ajax/libs/jsrender/0.9.90/jsrender.min.js',
                    'https://cdnjs.cloudflare.com/ajax/libs/fancybox/3.3.5/jquery.fancybox.min.js',
                    '/js/results.min.js',
                ],
            ];

            return $this->renderer->render($response, 'results.phtml', $data);
        }

        return $this->failure($response, self::ERROR_GENERAL_FAILURE);
    }

    public function face(/** @scrutinizer ignore-unused */ ServerRequestInterface $request, ResponseInterface $response, array $args) : ResponseInterface
    {
        $guid = $args['guid'];
        $n    = $args['n'];

        try {
            $r = $this->fbr->getMatchedFaces($guid, $n);

            if (!($r instanceof \WildWolf\FBR\Response\MatchedFaces)) {
                return $this->jsonError($response);
            }

            $data = [];
            foreach ($r as $x) {
                $info   = $this->getAdditionalInformation($x->path(), $x->namel());
                $entry  = [$x->similarity(), $info[0], $x->face(), $info[1], $info[2], $info[3], $info[4]];
                $data[] = $entry;
            }

            return $this->jsonResponse($response, $data);
        }
        catch (\WildWolf\FBR\Exception $e) {
            return $this->jsonError($response);
        }
    }

    private function getAdditionalInformation(string $path, string $namel) : array
    {
        if (!empty($path) && preg_match('!(?:^|[\\\\/])criminals!i', $path)) {
            return $this->processCriminalOldFormat($path);
        }

        $m = [];
        if (!empty($namel) && preg_match('/^{!1-0-([0-9]++)-([0-9]++)}#/', $namel, $m)) {
            return $this->processCriminalFormat1((int)$m[1], (int)$m[2]);
        }

        return ['-', '#', '', '', ''];
    }

    private function processCriminalFormat1(int $cid, int $pid) : array
    {
        $json    = $this->psbInfo($cid);
        $path    = $json[2] ?? '-';
        $link    = $json     ? ('https://myrotvorets.center/criminal/' . $json[1] . '/') : '#';
        $country = $json[4] ?? '';
        list($pphoto, $mphoto) = $this->findPhotos1($json[9] ?? [], $pid);

        return [$path, $link, $country, $mphoto, $pphoto];
    }

    private function findPhotos1(array $photos, int $pid) : array
    {
        $mphoto  = '';
        $pphoto  = '';

        foreach ($photos as $y) {
            if (empty($pphoto) && 'image/' === substr($y[1], 0, strlen('image/'))) {
                $pphoto = 'https://psb4ukr.natocdn.work/' . $y[0];
            }

            if ($pid == $y[2]) {
                $mphoto = 'https://psb4ukr.natocdn.work/' . $y[0];
                break;
            }
        }

        return [$pphoto, $mphoto];
    }

    private function processCriminalOldFormat(string $path) : array
    {
        $orig    = $path;
        $link    = '#';
        $country = '';
        $m       = [];
        $pphoto  = '';
        $mphoto  = '';

        if (preg_match('!([/\\\\][0-9a-fA-F]{2}[/\\\\][0-9a-fA-F]{2}[/\\\\][0-9a-fA-F]{2,}[/\\\\])!', $path, $m)) {
            $id      = str_replace(['/', '\\'], '', $m[1]);
            $json    = $this->psbInfo((int)hexdec($id));
            $path    = $json[2] ?? '-';
            $link    = $json     ? ('https://myrotvorets.center/criminal/' . $json[1] . '/') : '#';
            $country = $json[4] ?? '';

            $prefix = 'criminals' . strtolower(str_replace('\\', '/', $m[1]));
            if ($json && preg_match('/{([^}]++)}/', $orig, $m)) {
                $prefix .= $m[1] . '.';
                list($pphoto, $mphoto) = $this->findPhotosOld($json[9] ?? [], $prefix);
            }
        }

        return [$path, $link, $country, $mphoto, $pphoto];
    }

    private function findPhotosOld(array $photos, string $prefix) : array
    {
        $mphoto  = '';
        $pphoto  = '';

        foreach ($photos as $y) {
            if (!$pphoto && 'image/' === substr($y[1], 0, strlen('image/'))) {
                $pphoto = 'https://psb4ukr.natocdn.work/' . $y[0];
            }

            if ($prefix === substr($y[0], 0, strlen($prefix))) {
                $mphoto = 'https://psb4ukr.natocdn.work/' . $y[0];
                break;
            }
        }

        return [$pphoto, $mphoto];
    }

    private function psbInfo(int $id)
    {
        $cache = $this->container->has('cache') ? $this->container->get('cache') : null;
        $key   = 'criminal-' . $id;
        $res   = $cache ? $cache->get($key, null) : null;

        if (!is_array($res)) {
            try {
                $res = json_decode(file_get_contents('https://srv1.psbapi.work/c/D/' . $id));

                if ($cache) {
                    $cache->set($key, $res, 3600);
                }
            }
            catch (\Throwable $e) {
                error_log($e);
            }
        }

        return $res;
    }
}
