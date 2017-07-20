<?php

namespace WildWolf\Test;

class ValidateReCaptchaHandlerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \WildWolf\Handler\ValidateReCaptcha
     */
    private $handler;

    /**
     * @var \Slim\Slim
     */
    private $app;

    protected function setUp()
    {
        $this->app     = new \Slim\Slim();
        $this->handler = new \WildWolf\Handler\ValidateReCaptcha($this->app);
    }

    public function testRealReCaptcha()
    {
        $this->app->container->singleton('recaptcha', function() { return new \ReCaptcha\ReCaptcha('some-secret'); });

        try {
            ($this->handler)();
            $this->assertTrue(false);
        }
        catch (\Slim\Exception\Stop $e) {
            $response = $this->app->response;
            $this->assertEquals(302, $response->status());
            $this->assertTrue($response->headers()->has('Location'));

            $location = $response->headers()->get('Location');
            $this->assertContains('error=' . \WildWolf\Handler\BaseHandler::ERROR_RECAPTCHA, $location);
        }
    }

    public function testFakeReCaptcha()
    {
        $recaptcha_response_stub = $this->getMockBuilder(\ReCaptcha\Response::class)->disableOriginalConstructor()->getMock();
        $recaptcha_response_stub->method('isSuccess')->willReturn(true);

        $recaptcha_stub = $this->getMockBuilder(\ReCaptcha\ReCaptcha::class)->disableOriginalConstructor()->getMock();
        $recaptcha_stub->method('verify')->willReturn($recaptcha_response_stub);

        $this->app->container->singleton('recaptcha', function() use ($recaptcha_stub) { return $recaptcha_stub; });

        ($this->handler)();

        $response = $this->app->response;
        $this->assertFalse($response->headers()->has('Location'));
    }
}
