<?php

namespace WildWolf\Test;

class LogOutHandlerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \WildWolf\Handler\LogOut
     */
    private $handler;

    /**
     * @var \Slim\Slim
     */
    private $app;

    protected function setUp()
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $this->app = new \Slim\Slim();

        $acckit = $this->createMock(\WildWolf\AccountKit::class);
        $acckit->method('logout')->willReturn(true);
        $this->app->container->singleton('acckit', function() use ($acckit) { return $acckit; });

        $this->handler = new \WildWolf\Handler\LogOut($this->app);
    }

    protected function tearDown()
    {
        $_SESSION = [];
    }

    public function testNotLoggedInUser()
    {
        try {
            ($this->handler)();
            $this->assertTrue(false);
        }
        catch (\Slim\Exception\Stop $e) {
            $this->assertFalse(isset($_SESSION['user']));

            $response = $this->app->response;
            $this->assertTrue($response->headers()->has('Location'));

            $location = $response->headers()->get('Location');
            $this->assertEquals('/', $location);
        }
    }

    public function testLoggedInUser()
    {
        $_SESSION = [
            'user' => (object)[
                'token' => 'sometoken',
            ]
        ];

        try {
            ($this->handler)();
            $this->assertTrue(false);
        }
        catch (\Slim\Exception\Stop $e) {
            $this->assertFalse(isset($_SESSION['user']));

            $response = $this->app->response;
            $this->assertTrue($response->headers()->has('Location'));

            $location = $response->headers()->get('Location');
            $this->assertEquals('/', $location);
        }
    }
}
