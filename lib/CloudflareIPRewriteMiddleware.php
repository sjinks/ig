<?php

class CloudflareIPRewriteMiddleware extends \Slim\Middleware
{
    public function call()
    {
        new \CloudFlare\IpRewrite();
        $this->next->call();
    }
}
