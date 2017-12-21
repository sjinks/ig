<?php

namespace WildWolf\Logging;

use Monolog\Logger;
use Monolog\Handler\MailHandler;
use PHPMailer\PHPMailer\PHPMailer;

class PHPMailerHandler extends MailHandler
{
    /**
     * @var PHPMailer
     */
    protected $mailer;

    public function __construct(PHPMailer $mailer, $level = Logger::ERROR, $bubble = true)
    {
        parent::__construct($level, $bubble);

        $this->mailer = $mailer;
    }

    protected function send($content, array $records)
    {
        $this->mailer->Body = $content;
        error_log($content);
        $this->mailer->send();
    }
}
