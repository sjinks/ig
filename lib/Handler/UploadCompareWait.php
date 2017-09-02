<?php

namespace WildWolf\Handler;

class UploadCompareWait extends BaseHandler
{
    protected function run()
    {
        $guid = func_get_arg(0);
        $this->app->render('cwait.phtml', ['guid' => $guid, 'title' => 'Зачекайте, будь ласка']);
    }
}
