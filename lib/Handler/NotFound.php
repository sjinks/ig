<?php

namespace WildWolf\Handler;

class NotFound extends BaseHandler
{
    protected function run()
    {
        $this->app->render('404.phtml', ['title' => 'Не знайдено'], 404);
    }
}
