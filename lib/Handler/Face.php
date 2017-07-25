<?php

namespace WildWolf\Handler;

class Face extends JsonHandler
{
    protected function run()
    {
        $guid     = func_get_arg(0);
        $n        = func_get_arg(1);

        try {
            $response = $this->app->fbr->getFaces($guid, $n);

            if (!($response instanceof \WildWolf\FBR\Response\Match)) {
                $this->error();
            }

            $data = [];
            foreach ($response as $x) {
                $info   = $this->getAdditionalInformation($x->path());
                $entry  = [$x->similarity(), $info[0], $x->face(), $info[1], $info[2], $info[3], $info[4]];
                $data[] = $entry;
            }

            $this->response($data);
        }
        catch (\WildWolf\FBR\Exception $e) {
            $this->error();
        }
    }

    private function getAdditionalInformation($path) : array
    {
        $orig    = $path;
        $link    = '#';
        $country = '';
        $mphoto  = '';
        $pphoto  = '';

        if (preg_match('![\\\\/]criminals!i', $path)) {
            $m = array();
            if (preg_match('!([/\\\\][0-9a-fA-F]{2}[/\\\\][0-9a-fA-F]{2}[/\\\\][0-9a-fA-F]{2,}[/\\\\])!', $path, $m)) {
                $id      = str_replace(['/', '\\'], '', $m[1]);
                $json    = $this->psbInfo(hexdec($id));
                $path    = $json ? $json[2] : $path;
                $link    = $json ? ('https://myrotvorets.center/criminal/' . $json[1] . '/') : ('https://myrotvorets.center/?p=0x' .  $id);
                $country = $json ? $json[4] : '';

                $prefix = 'criminals' . str_replace('\\', '/', $m[1]);
                if ($json[9] && preg_match('/{([^}]++)}/', $orig, $m)) {
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

    private function psbInfo(int $id)
    {
        $key = 'criminal-' . $id;
        $res = $this->app->cache->get($key, null);

        if (!is_array($res)) {
            $res = json_decode(file_get_contents('https://srv1.psbapi.work/c/D/' . $id));
            $this->app->cache->set($key, $res, 3600);
        }

        return $res;
    }
}
