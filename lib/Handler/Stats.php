<?php

namespace WildWolf\Handler;

class Stats extends JsonHandler
{
    protected function run()
    {
        $guid = func_get_arg(0);

        try {
            $response = $this->app->fbr->getUploadStats($guid);
            if (!($response instanceof \WildWolf\FBR\Response\Stats)) {
                $this->error();
            }

            $data = [];
            foreach ($response as $x) {
                $data[] = [$x->minConfidence(), $x->maxConfidence(), $x->face()];
            }

            $this->response($data);
        }
        catch (\WildWolf\FBR\Exception $e) {
            $this->error();
        }
    }
}
