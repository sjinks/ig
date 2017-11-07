<?php

namespace WildWolf\Handler;

use WildWolf\FBR\Response\CompareCompleted;

class CompareResult extends BaseHandler
{
    protected function run()
    {
        $guid     = func_get_arg(0);
        $response = $this->app->fbr->getComparisonResults($guid);

        if ($response instanceof CompareCompleted) {
            if ($response->cacheable()) {
                $this->processResult($guid, $response);
            }
            elseif ($response->resultCode() == 2) {
                $this->wait();
            }

            return;
        }

        $this->failure(self::ERROR_GENERAL_FAILURE);
    }

    private function wait()
    {
        $this->app->render(
            'wait.phtml',
            [
                'title'     => 'Зачекайте, будь ласка',
                'timeout'   => 5000,
                'footer_js' => ['/js/wait.js?v=3'],
            ]
        );
    }

    private function processResult(string $guid, CompareCompleted $response)
    {
        /**
         * @var \WildWolf\User $user
         */
        $user = $_SESSION['user'] ?? null;
        if ($user && !$user->isPrivileged()) {
            $user->logout($this->app->acckit);
            unset($_SESSION['user']);
        }

        $base  = $this->app->uploader->getTargetName($guid . '-*.jpg', false);
        $url   = dirname($this->app->uploader->getTargetName($guid));
        $files = glob($base);

        $names = [];
        $sims  = [];
        foreach ($files as $f) {
            $names[] = '/uploads/' . $url . '/' . basename($f);
            $sims[]  = 0;
        }

        if ($response->resultCode() == 3) {
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

        $this->app->render('cresults.phtml', $data);
    }
}
