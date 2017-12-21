<?php

namespace WildWolf;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class UserController extends BaseController
{
    public function index(ServerRequestInterface $request, ResponseInterface $response) : ResponseInterface
    {
        /**
         * @var AccountKit $acckit
         */
        $acckit  = $this->container->get('acckit');

        /**
         * @var \Slim\Views\PhpRenderer $renderer
         */
        $renderer = $this->container->get('view');

        $get      = $request->getQueryParams();
        $code     = $get['error'] ?? 0;

        if (!empty($_SESSION['user'])) {
            /**
             * @var \WildWolf\User $user
             */
            $user = $_SESSION['user'];
            if ($user->isPrivileged()) {
                $url = $code ? '/start?error=' . $code : '/start';
                return $response
                    ->withStatus(302)
                    ->withHeader('Location', $url)
                ;
            }

            $user->logout($acckit);
            unset($_SESSION['user']);
        }

        $error = $code ? self::getErrorByCode($code) : null;
        return $renderer->render(
            $response,
            'index.phtml',
            [
                'error'     => $error,
                'app_id'    => $this->container->get('settings')['fb.app_id'],
                'header_js' => ['https://sdk.accountkit.com/uk_UA/sdk.js'],
                'footer_js' => ['/js/index.js']
            ]
        );
    }

    public function checkPhone(ServerRequestInterface $request, ResponseInterface $response) : ResponseInterface
    {
        /**
         * @var SepAPI $sepapi
         */
        $sepapi = $this->container->get('sepapi');
        /**
         * @var AccountKit $acckit
         */
        $acckit = $this->container->get('acckit');

        $post   = (array)$request->getParsedBody();
        $phone  = $post['p'] ?? null;
        $body   = 0;

        try {
            $data = $sepapi->validatePhone($phone);

            if (is_object($data)) { // Only for whitelisted users
                if ($data->credits > 0) {
                    $acckit->validateAccessToken($data->token);
                    $body = 200;
                    $_SESSION['user'] = new \WildWolf\User($data);
                }
            }
            elseif (is_scalar($data)) {
                $body = $data;
            }
        }
        catch (\Exception $e) {
            error_log($e);
        }

        return $this->jsonResponse($response, $body);
    }

    public function verify(ServerRequestInterface $request, ResponseInterface $response) : ResponseInterface
    {
        $get  = $request->getQueryParams();
        $code = $get['code'] ?? null;
        $err  = self::ERROR_AUTH_FAILED;

        if ($code) {
            try {
                $err = $this->doSmsLogin($code);
            }
            catch (\Exception $e) {
                // Fall through
            }
        }

        if ($err) {
            return $this->failure($response, $err, true);
        }

        return $response
            ->withStatus(302)
            ->withHeader('Location', '/start')
        ;
    }

    private function doSmsLogin(string $code) : int
    {
        /**
         * @var AccountKit $acckit
         */
        $acckit = $this->container->get('acckit');

        /**
         * @var SepAPI $sepapi
         */
        $sepapi = $this->container->get('sepapi');

        $d1     = $acckit->getAccessToken($code);
        $d2     = $acckit->validateAccessToken($d1->access_token);

        $response = $sepapi->smsLogin($d2->id, $d1->access_token, $d2->phone->number);
        if (is_numeric($response)) {
            $acckit->logout($d1->access_token);
            switch ($response) {
                case 403:
                    return self::ERROR_BANNED;

                case 509:
                    return self::ERROR_NO_CREDITS;

                default:
                    return self::ERROR_AUTH_FAILED;
            }
        }

        $_SESSION['user'] = new \WildWolf\User($response);
        return 0;
    }

    public function logOut(ServerRequestInterface $request, ResponseInterface $response) : ResponseInterface
    {
        if (!empty($_SESSION['user'])) {
            /**
             * @var AccountKit $acckit
             */
            $acckit = $this->container->get('acckit');

            /**
             * @var \WildWolf\User $user
             */
            $user = $_SESSION['user'];
            $user->logout($acckit);
            unset($_SESSION['user']);
        }

        return $response
            ->withStatus(302)
            ->withHeader('Location', '/')
        ;
    }
}
