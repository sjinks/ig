<?php

namespace WildWolf\Handler;

use Slim\Slim;

abstract class BaseHandler
{
    const ERROR_UPLOAD_FAILURE     = 1;
    const ERROR_FILE_EMPTY         = 2;
    const ERROR_NOT_IMAGE          = 3;
    const ERROR_FILE_NOT_SUPPORTED = 4;
    const ERROR_FILE_TOO_BIG       = 5;
    const ERROR_GENERAL_FAILURE    = 6;
    const ERROR_AUTH_FAILED        = 7;
    const ERROR_RECAPTCHA          = 8;
    const ERROR_BANNED             = 9;
    const ERROR_NO_CREDITS         = 10;

    /**
     * @var \Slim\Slim
     */
    protected $app;

    /**
     * @var int
     */
    protected $code;

    public function __construct(Slim $app)
    {
        $this->app = $app;
    }

    public function __invoke()
    {
        $this->code = filter_input(INPUT_GET, 'error', FILTER_SANITIZE_NUMBER_INT);

        $args = func_get_args();
        $this->run(...$args);
    }

    abstract protected function run();

    protected static function getErrorByCode(int $code) : string
    {
        static $errors = [
            self::ERROR_UPLOAD_FAILURE     => 'Не вдалося завантажити файл.',
            self::ERROR_FILE_EMPTY         => 'Завантажений файл порожній.',
            self::ERROR_NOT_IMAGE          => 'Завантажений файл не є зображенням.',
            self::ERROR_FILE_NOT_SUPPORTED => 'Тип файлу не підтримується.',
            self::ERROR_FILE_TOO_BIG       => 'Завантажений файл завеликий.',
            self::ERROR_GENERAL_FAILURE    => 'Помилка обробки запиту. Будь ласка, спробуйте пізніше.',
            self::ERROR_AUTH_FAILED        => 'Верифікація не вдалася, спробуйте ще раз.',
            self::ERROR_RECAPTCHA          => 'Ви не змогли пройти ReCaptcha. Якщо ви не бот, спробуйте ще раз.',
            self::ERROR_BANNED             => 'Будь ласка, спробуйте пізніше.',
            self::ERROR_NO_CREDITS         => 'Кількість безкоштовних спроб досягнуто. Будь ласка, спробуйте ще раз завтра.'
        ];

        return isset($errors[$code]) ? $errors[$code] : 'Невідома помилка';
    }

    protected static function maybeAppendErrorCode(string $url) : string
    {
        if ($this->code) {
            $char = (false === strpos($url, '?')) ? '?' : '&';
            return $url . $char . 'error=' . $code;
        }

        return $url;
    }

    protected function failure(int $code, bool $idx = false)
    {
        $base = ($idx) ? '/?error=' : '/start?error=';
        $this->app->redirect($base . $code);
    }
}
