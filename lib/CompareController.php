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
        $files = $request->getUploadedFiles();
        $entry = $files['photo1'] ?? null;
        $refs  = $files['photo2'] ?? [null];

        try {
            $entries   = array_merge([$entry], $refs);
            $resources = $this->validateUploadedFiles($entries);
            $guid      = $this->uploadToFBR($resources);
            $this->saveFiles($entries, $guid);
        }
        catch (\Throwable $e) {
            $this->deleteTemporaryFiles($entries);
            return $this->failure($response, $e->getCode());
        }

        return $response
            ->withStatus(302)
            ->withHeader('Location', '/cresult/' . $guid)
        ;
    }

    /**
     * @param \Psr\Http\Message\UploadedFileInterface[] $entries
     * @return array
     */
    private function validateUploadedFiles(array $entries) : array
    {
        $resources = [];
        foreach ($entries as $x) {
            $this->uploader->setFile($x);
            $this->uploader->validateFile();

            /// TODO: check if the photo is 0.08..5 Mpix
            $resources[] = fopen($x->getStream()->getMetadata('uri'), 'rb');
        }

        return $resources;
    }

    private function saveFiles(array $entries, string $base)
    {
        $cnt = count($entries);
        for ($i=0; $i<$cnt; ++$i) {
            $file = $base . '-' . $i . '.jpg';
            $this->uploader->setFile($entries[$i]);
            $this->uploader->save($file);
        }
    }

    private function uploadToFBR(array $resources) : string
    {
        $r = $this->fbr->startCompare($resources[0], count($resources) - 1);
        if (!($r instanceof StartCompareAck)) {
            throw new \Exception('', self::ERROR_GENERAL_FAILURE);
        }

        $guid = $r->serverRequestId();
        $cnt  = count($resources);
        for ($i=1; $i<$cnt; ++$i) {
            $r = $this->fbr->uploadRefPhoto($guid, $resources[$i], $i, $cnt - 1, (string)$i);
            if (!($r instanceof UploadCompareAck)) {
                throw new \Exception('', self::ERROR_GENERAL_FAILURE);
            }
        }

        return $guid;
    }

    /**
     * @param \Psr\Http\Message\UploadedFileInterface[] $entries
     */
    private function deleteTemporaryFiles(array $entries)
    {
        foreach ($entries as $x) {
            $stream = $x->getStream();
            $fname  = $stream->getMetadata('uri');
            $stream->close();
            unlink($fname);
        }
    }

    public function result(/** @scrutinizer ignore-unused */ ServerRequestInterface $request, ResponseInterface $response, array $params) : ResponseInterface
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

        return $this->failure($response, self::ERROR_GENERAL_FAILURE);
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

        if ($r->succeeded()) {
            /**
             * @var $x \WildWolf\FBR\Response\Parts\CompareResult
             */
            foreach ($r as $x) {
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
