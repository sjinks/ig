<?php

namespace WildWolf\Handler;

class Face extends JsonHandler
{
    protected function run()
    {
        $guid     = func_get_arg(0);
        $n        = func_get_arg(1);

        $response = $this->app->fbr->getFaces($guid, $n);

        if (!is_object($response) || \FBR\FBR::ANS_GET_FACES != $response->ans_type) {
            $this->error();
        }

        $data = [];
        foreach ($response->data->fotos as $x) {
            $info   = $this->getAdditionalInformation($x->path);
            $entry  = [$x->par3, $info[0], $x->foto, $info[1], $info[2], $info[3], $info[4]];
            $data[$x->par2] = $entry;
        }

        ksort($data);
        $this->response($data);
    }

    private function getAdditionalInformation($path) : array
    {
        $link    = '#';
        $country = '';
        $mphoto  = '';
        $pphoto  = '';

        if (preg_match('![\\\\/]criminals!i', $path)) {
            $m = array();
            if (preg_match('!([/\\\\][0-9a-fA-F]{2}[/\\\\][0-9a-fA-F]{2}[/\\\\][0-9a-fA-F]{2,}[/\\\\])!', $path, $m)) {
                $id      = str_replace(['/', '\\'], '', $m[1]);
                $json    = json_decode(file_get_contents('https://srv1.psbapi.work/c/D/' . hexdec($id)));
                $path    = $json ? $json[2] : $path;
                $link    = $json ? ('https://myrotvorets.center/criminal/' . $json[1] . '/') : ('https://myrotvorets.center/?p=0x' .  $id);
                $country = $json ? $json[4] : '';

                $prefix = 'criminals' . str_replace('\\', '/', $m[1]);
                if ($json[9] && preg_match('/{([^}]++)}/', $x->path, $m)) {
                    $prefix .= $m[1] . '.';
                    foreach ($json[9] as $y) {
                        if (!$pphoto && 'image/' === substr($y[1], 0, strlen('image/'))) {
                            $pphoto = 'https://psb4ukr.natocdn.work/' . $y[0];
                        }

                        if ($prefix === substr($y[0], 0, strlen($prefix))) {
                            $mphoto = 'https://psb4ukr.natocdn.work/' . $y[0];
                            break;
                        }
                    }
                }
            }
        }

        return [$path, $link, $country, $mphoto, $pphoto];
    }
}
