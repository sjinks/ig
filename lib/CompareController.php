<?php

namespace WildWolf;

use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use WildWolf\FBR\Response\CompareCompleted;
use WildWolf\FBR\Response\StartCompareAck;
use WildWolf\FBR\Response\UploadCompareAck;

class CompareController extends BaseController
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

    public function __construct(ContainerInterface $container)
    {
        parent::__construct($container);

        $this->uploader = $container->get('uploader');
        $this->fbr      = $container->get('fbr');
        $this->renderer = $container->get('view');
        $this->acckit   = $container->get('acckit');
    }

    public function upload(ServerRequestInterface $request, ResponseInterface $response) : ResponseInterface
    {
        $code = 0;

        $entry = $_FILES['photo1'] ?? [];
        $refs  = empty($_FILES['photo2']) ? null : Utils::normalizeFileEntry($_FILES['photo2']);

        if (!$entry || !$refs) {
            return $this->failure($response, self::ERROR_GENERAL_FAILURE);
        }

        try {
            $entries   = array_merge([$entry], $refs);
            $resources = [];

            foreach ($entries as $x) {
                $this->uploader->setFile($x);
                $this->uploader->validateFile($x);

//                 $mp = $resource->megapixels();
//                 if ($mp > 5 || $mp < 0.08) {
//                     throw new \Exception('', self::ERROR_BAD_RESOLUTION);
//                 }

                $resources[] = fopen($x['tmp_name'], 'rb');
            }

            $r = $this->fbr->startCompare($resources[0], count($resources) - 1);
            if (!($r instanceof StartCompareAck)) {
                throw new \Exception('', self::ERROR_GENERAL_FAILURE);
            }

            $guid = $r->serverRequestId();
            for ($i=1; $i<count($resources); ++$i) {
                $r = $this->fbr->uploadRefPhoto($guid, $resources[$i], $i, count($resources) - 1, $i);
                if (!($r instanceof UploadCompareAck)) {
                    throw new \Exception('', self::ERROR_GENERAL_FAILURE);
                }
            }

            for ($i=0; $i<count($resources); ++$i) {
                $file = $guid . '-' . $i . '.jpg';
                $this->uploader->setFile($entries[$i]);
                $this->uploader->save($file);
            }
        }
        catch (\Throwable $e) {
            $code = $e->getCode();
        }
        finally {
            unlink($entry['tmp_name']);
            foreach ($refs as $x) {
                unlink($x['tmp_name']);
            }
        }

        if ($code) {
            return $this->failure($response, $code);
        }

        return $response
            ->withStatus(302)
            ->withHeader('Location', '/cresult/' . $guid)
        ;
    }

    public function result(ServerRequestInterface $request, ResponseInterface $response, array $params) : ResponseInterface
    {
        $guid = $params['guid'];
        $r    = $this->fbr->getComparisonResults($guid);

        if ($r instanceof CompareCompleted) {
            if ($r->cacheable()) {
                return $this->processResult($response, $guid, $r);
            }

            if ($r->pending()) {
                return $this->wait($response);
            }
        }

        return $this->failure(self::ERROR_GENERAL_FAILURE);
    }

    private function wait(ResponseInterface $response) : ResponseInterface
    {
        return $this->renderer->render(
            $response,
            'wait.phtml',
            [
                'title'     => 'Зачекайте, будь ласка',
                'timeout'   => 5000,
                'footer_js' => ['/js/wait.js?v=3'],
            ]
        );
    }

    private function processResult(ResponseInterface $response, string $guid, CompareCompleted $r) : ResponseInterface
    {
        /**
         * @var \WildWolf\User $user
         */
        $user = $_SESSION['user'] ?? null;
        if ($user && !$user->isPrivileged()) {
            $user->logout($this->acckit);
            unset($_SESSION['user']);
        }

        $base  = $this->uploader->getTargetName($guid . '-*.jpg', false);
        $url   = dirname($this->uploader->getTargetName($guid));
        $files = glob($base);

        $names = [];
        $sims  = [];
        foreach ($files as $f) {
            $names[] = '/uploads/' . $url . '/' . basename($f);
            $sims[]  = 0;
        }

        if ($r->resultCode() == 3) {
            /**
             * @var $x \WildWolf\FBR\Response\Parts\CompareResult
             */
            foreach ($response as $x) {
                $idx = (int)$x->name();
                $sim = $x->similarity();

                $sims[$idx] = $sim;
            }
        }

        $data = [
            'files'  => $names,
            'sims'   => $sims,
            'title'  => 'Результати порівняння',
        ];

        return $this->renderer->render($response, 'cresults.phtml', $data);
    }
}
