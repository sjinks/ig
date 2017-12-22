<?php

namespace WildWolf;

use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;

abstract class BaseController
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
    const ERROR_BAD_RESOLUTION     = 11;

    /**
     * @var ContainerInterface
     */
    protected $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

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
            self::ERROR_NO_CREDITS         => 'Кількість безкоштовних спроб досягнуто. Будь ласка, спробуйте ще раз завтра.',
            self::ERROR_BAD_RESOLUTION     => 'Невірна роздільна здатність світлини.',
        ];

        return $errors[$code] ?? 'Невідома помилка';
    }

    protected function failure(ResponseInterface $response, int $code, bool $idx = false) : ResponseInterface
    {
        $base = ($idx) ? '/?error=' : '/start?error=';
        return $response
            ->withStatus(302)
            ->withHeader('Location', $base . $code)
        ;
    }

    protected function jsonError(ResponseInterface $response) : ResponseInterface
    {
        return $this->sendJsonResponse($response, '[]', 500);
    }

    protected function jsonResponse(ResponseInterface $response, $data) : ResponseInterface
    {
        return $this->sendJsonResponse($response, json_encode($data), 200);
    }

    private function sendJsonResponse(ResponseInterface $response, string $body, int $code)
    {
        $b = $response->getBody();
        $b->write($body);

        return $response
            ->withStatus($code)
            ->withHeader('Content-Type', 'application/json; charset=utf-8')
            ->withBody($b)
        ;
    }
}
