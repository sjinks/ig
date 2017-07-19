<?php

namespace WildWolf\Handler;

class Stats extends JsonHandler
{
    protected function run($guid)
    {
        $response = $this->app->fbr->getUploadStats($guid);

        if (!is_object($response) || \FBR\FBR::ANS_GET_USTATS != $response->ans_type) {
            $this->error();
        }

        $data = [];
        foreach ($response->data->fotos as $x) {
            $data[] = [$x->par2, $x->par3, $x->foto];
        }

        $this->sendJson($data);
    }
}
