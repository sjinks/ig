<?php

namespace WildWolf\Handler;

class Stats extends JsonHandler
{
    protected function run()
    {
        $guid     = func_get_arg(0);
        $response = $this->app->fbr->getUploadStats($guid);

        if (!is_object($response) || \FBR\FBR::ANS_GET_USTATS != $response->ans_type) {
            $this->error();
        }

        $data = [];
        foreach ($response->data->fotos as $x) {
            $data[$x->par1-1] = [$x->par2, $x->par3, $x->foto];
        }

        ksort($data);
        $this->response($data);
    }
}
