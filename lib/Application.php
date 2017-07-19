<?php

/**
 * @property \FBR\FBR $fbr
 * @property \WildWolf\SepAPI $sepapi
 * @property \WildWolf\AccountKit $acckit
 * @property \WildWolf\ImageUploader $uploader
 */
final class Application extends \Slim\Slim
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

    public function init()
    {
        self::startSession();
        $this->setUpRouting();
        $this->setUpDI();

        $this->error([$this, 'errorHandler']);
        $this->notFound([$this, 'handler404']);
    }

    public function errorHandler(\Exception $e)
    {
        if ($e instanceof \ErrorException) {
            error_log($e->__toString());
        }

        $this->failure(self::ERROR_GENERAL_FAILURE);
    }

    public function handler404()
    {
        $this->render('404.phtml');
    }

    private function setUpRouting()
    {
        \Slim\Route::setDefaultConditions([
            'guid' => '[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{12}',
            'n'    => '[1-9][0-9]*'
        ]);

        $this->get('/',              [$this, 'action_index']);
        $this->post('/checkphone',   [$this, 'action_checkphone']);
        $this->get('/verify',        [$this, 'action_verify']);
        $this->get('/logout',        [$this, 'action_logout']);
        $this->get('/start',         [$this, 'mw_verify_access_token'], [$this, 'action_start']);
        $this->post('/upload',       [$this, 'mw_verify_access_token'], [$this, 'mw_verify_recaptcha'], [$this, 'action_upload']);
        $this->get('/queue/:guid',   [$this, 'action_queue']);
        $this->get('/result/:guid',  [$this, 'action_result']);
        $this->get('/stats/:guid',   [$this, 'action_stats']);
        $this->get('/face/:guid/:n', [$this, 'action_face']);
    }

    private function setUpDI()
    {
        $app = $this;

        $this->container->singleton('sepapi', function() use ($app) {
            return new \WildWolf\SepAPI($app->config('api.endpoint'), $app->config('api.token'));
        });

        $this->container->singleton('acckit', function() use ($app) {
            return new \WildWolf\AccountKit($app->config('fb.app_id'), $app->config('fb.ak.app_secret'));
        });

        $this->container->singleton('fbr', function() use ($app) {
            return new \FBR\FBR($app->config('fbr.url'), $app->config('fbr.client_id'));
        });

        $this->container->singleton('uploader', function() {
            $uploader = new \WildWolf\ImageUploader();
            $uploader->setMaxUploadSize(5242880);
            $uploader->setDirectoryDepth(3);
            $uploader->setCheckUniqueness(false);
            $uploader->setAcceptedTypes(['image/jpeg', 'image/png']);
            $uploader->setUploadDir(realpath(__DIR__ . '/../public/uploads'));
            return $uploader;
        });
    }

    /**
     * @param int $code
     * @return string
     */
    private static function getErrorByCode(int $code) : string
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

    private static function startSession()
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
    }

    private function failure(int $code, bool $idx = false)
    {
        $base = ($idx) ? '/?error=' : '/start?error=';
        $this->redirect($base . $code);
    }

    private static function maybeAppendErrorCode(string $url) : string
    {
        $code = filter_input(INPUT_GET, 'error', FILTER_SANITIZE_NUMBER_INT);
        if ($code) {
            $char = (false === strpos($url, '?')) ? '?' : '&';
            return $url . $char . 'error=' . $code;
        }

        return $url;
    }

    public function mw_verify_access_token()
    {
        if (empty($_SESSION['user'])) {
            $this->redirect(self::maybeAppendErrorCode('/'));
        }
    }

    public function mw_verify_recaptcha()
    {
        if (!empty($_SESSION['user']->whitelisted)) {
            return;
        }

        $recaptcha = new \ReCaptcha\ReCaptcha($this->config('recaptcha.secret'));
        $response  = filter_input(INPUT_POST, 'g-recaptcha-response', FILTER_DEFAULT);
        $ip        = filter_input(INPUT_SERVER, 'REMOTE_ADDR', FILTER_DEFAULT);
        $result    = $recaptcha->verify($response, $ip);
        if (!$result->isSuccess()) {
            $this->failure(self::ERROR_RECAPTCHA);
        }
    }

    private function doLogOut($token)
    {
        try {
            $this->acckit->logout($token);
        }
        catch (\Exception $e) {
            // Ignore exception
        }

        unset($_SESSION['user']);
    }

    public function action_index()
    {
        $code = filter_input(INPUT_GET, 'error', FILTER_SANITIZE_NUMBER_INT);

        if (!empty($_SESSION['user'])) {
            $user  = $_SESSION['user'];
            if (!$user->whitelisted && !$user->paid) {
                $this->doLogOut($user->token);
            }
            else {
                $this->redirect(self::maybeAppendErrorCode('/start'));
            }
        }

        $error = $code ? self::getErrorByCode($code) : null;
        $this->render('index.phtml', ['error' => $error, 'app_id' => $this->config('fb.app_id')]);
    }

    public function action_checkphone()
    {
        $phone = filter_input(INPUT_POST, 'p', FILTER_DEFAULT);
        $body  = 0;

        try {
            $data = $this->sepapi->validatePhone($phone);

            if (is_object($data)) { // Only for whitelisted users
                if ($data->credits > 0) {
                    $this->acckit->validateAccessToken($data->token);
                    $body = 200;
                    $_SESSION['user'] = $data;
                }
            }
            elseif (is_scalar($data)) {
                $body = $data;
            }
        }
        catch (\Exception $e) {
            // Do nothing
        }

        $this->sendJson($body);
    }

    public function action_verify()
    {
        $code = filter_input(INPUT_GET, 'code',   FILTER_DEFAULT);

        if ($code) {
            try {
                $d1 = $this->acckit->getAccessToken($code);
                $d2 = $this->acckit->validateAccessToken($d1->access_token);

                $response = $this->sepapi->smsLogin($d2->id, $d1->access_token, $d2->phone->number);
                if (is_numeric($response)) {
                    $this->acckit->logout($d1->access_token);
                    switch ($response) {
                        case 403: $this->failure(self::ERROR_BANNED, true);
                        case 509: $this->failure(self::ERROR_NO_CREDITS, true);
                        default:  $this->failure(self::ERROR_GENERAL_FAILURE, true);
                    }
                }

                $_SESSION['user'] = $response;
                $this->redirect('/start');
            }
            catch (\Slim\Exception\Stop $e) {
                throw $e;
            }
            catch (\Exception $e) {
                // Fall through
            }
        }

        $this->failure(self::ERROR_AUTH_FAILED, true);
    }

    public function action_start()
    {
        $code  = filter_input(INPUT_GET, 'error', FILTER_SANITIZE_NUMBER_INT);
        $error = $code ? self::getErrorByCode($code) : null;

        $skip_recaptcha = !empty($_SESSION['user']->whitelisted);

        $this->render('upload.phtml', ['error' => $error, 'skip_recaptcha' => $skip_recaptcha]);
    }

    public function action_logout()
    {
        if (!empty($_SESSION['user'])) {
            $user = $_SESSION['user'];
            $this->doLogOut($user->token);
        }

        $this->redirect('/');
    }

    private function trackUpload()
    {
        $id = $_SESSION['user']->id;

        $response = $this->sepapi->trackUpload($id);
        $code     = self::ERROR_GENERAL_FAILURE;
        if (is_scalar($response)) {
            switch ($response) {
                case -1:
                    $code = self::ERROR_NO_CREDITS;
                    break;

                case -2:
                    $code = self::ERROR_BANNED;
                    break;

                default:
                    $_SESSION['user']->credits = $response;
                    return;
            }
        }

        $this->failure($code);
    }

    public function action_upload()
    {
        try {
            list($resource, $type) = $this->uploader->validateFile('photo');
            if (null === $resource) {
                $this->failure(self::ERROR_FILE_NOT_SUPPORTED);
            }

            $this->trackUpload();
            $response = $this->fbr->uploadFile($resource);
            if (!is_object($response) || $response->ans_type != \FBR\FBR::ANS_OK) {
                $this->failure(self::ERROR_GENERAL_FAILURE);
            }

            $guid = $response->data->reqID_serv;
            $file = $guid . '.jpg';

            $this->uploader->saveAsJpeg($resource, $file);
            unlink($_FILES['photo']['tmp_name']);
        }
        catch (\WildWolf\ImageUploaderException $e) {
            error_log($e);
            $this->failure($e->getCode());
        }

        $this->redirect('/queue/' . $guid);
    }

    public function action_queue($guid)
    {
        $response = $this->fbr->checkUploadStatus($guid);

        if (!is_object($response)) {
            $this->failure(self::ERROR_GENERAL_FAILURE);
        }
        elseif (\FBR\FBR::ANS_PROCESSING == $response->ans_type) {
            $this->render('wait.phtml');
        }
        elseif (\FBR\FBR::ANS_COMPLETED == $response->ans_type) {
            $this->redirect('/result/' . $guid);
        }
        else {
            $this->failure(self::ERROR_GENERAL_FAILURE);
        }
    }

    public function action_result($guid)
    {
        if (isset($_SESSION['user']) && !$_SESSION['user']->whitelisted && !$_SESSION['user']->paid) {
            unset($_SESSION['user']);
        }

        $response = $this->fbr->checkUploadStatus($guid);
        if (!is_object($response)) {
            $this->failure(self::ERROR_GENERAL_FAILURE);
        }
        elseif (\FBR\FBR::ANS_PROCESSING == $response->ans_type) {
            $this->redirect('/queue/' . $guid);
        }
        elseif (\FBR\FBR::ANS_COMPLETED == $response->ans_type) {
            $data = [
                'count' => $response->data->results_amount,
                'guid'  => $guid,
                'url'   => '/uploads/' . $this->uploader->getTargetName($guid . '.jpg'),
            ];

            $this->render('results.phtml', $data);
        }
        else {
            $this->failure(self::ERROR_GENERAL_FAILURE);
        }
    }

    private function sendJsonError()
    {
        if ($this->request->isXhr()) {
            $data = "[]";
            $this->response->headers->set('Content-Type', 'application/json');
            $this->response->headers->set('Content-Length', strlen($data));
            $this->halt(500, $data);
        }

        $this->failure(self::ERROR_GENERAL_FAILURE);
    }

    private function sendJson($data)
    {
        $data = json_encode($data);
        $this->response->headers->set('Content-Type', 'application/json');
        $this->response->headers->set('Content-Length', strlen($data));
        $this->halt(200, $data);
    }

    public function action_stats($guid)
    {
        $response = $this->fbr->getUploadStats($guid);

        if (!is_object($response) || \FBR\FBR::ANS_GET_USTATS != $response->ans_type) {
            $this->sendJsonError();
        }

        $data = [];
        foreach ($response->data->fotos as $x) {
            $data[] = [$x->par2, $x->par3, $x->foto];
        }

        $this->sendJson($data);
    }

    public function action_face($guid, $n)
    {
        $response = $this->fbr->getFaces($guid, $n);

        if (!is_object($response) || \FBR\FBR::ANS_GET_FACES != $response->ans_type) {
            $this->sendJsonError();
        }

        $data = [];
        foreach ($response->data->fotos as $x) {
            $path    = $x->path;
            $link    = '#';
            $country = '';
            $descr   = '';
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

            $entry  = [$x->par3, $path, $x->foto, $link, $country, $mphoto, $pphoto];
            $data[] = $entry;
        }

        $this->sendJson($data);
    }
}
