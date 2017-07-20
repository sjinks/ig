<?php

class CloudflareIPRewriteMiddleware extends \Slim\Middleware
{
    public function call()
    {
        $app = $this->getApplication();
        $env = $app->environment();

        $cf = new \CloudFlare\IpRewrite();
        if ($cf->isCloudFlare()) {
            $env['cf.original_ip'] = $cf->getOriginalIP();
            $env['REMOTE_ADDR']    = $cf->getRewrittenIP();
            $env['country_code']   = filter_input(INPUT_SERVER, 'HTTP_CF_IPCOUNTRY');
        }

        $this->next->call();
    }
}
