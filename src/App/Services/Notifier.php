<?php

namespace App\Services;


use Swift_SmtpTransport as Transport;
use Swift_Mailer as Mailer;
use Swift_Message as Message;

class Notifier
{
    private $transport;
    private $mailer;
    private $templateEngine;

    public function __construct($connectionOptions, $templateEngine) {
        $this->transport = Transport::newInstance();
        $this->transport
            ->setUsername($connectionOptions['username'])
            ->setPassword($connectionOptions['password'])
            ->setAuthMode($connectionOptions['auth_mode'])
            ->setHost($connectionOptions['host'])
            ->setPort($connectionOptions['port'])
            ->setEncryption($connectionOptions['encryption']);

        $this->mailer = Mailer::newInstance($this->transport);
        $this->templateEngine = $templateEngine;
    }

    public function notifyCustomerNewRateRequest($subject, $from, $to, $options) {
        $message = Message::newInstance()
            ->setSubject($subject)
            ->setFrom($from)
            ->setTo($to)
            ->setBody($this->templateEngine->render('notify-customer-new-rate-request.html.twig', $options));
        $this->mailer->send($message);
    }

    public function notifyRaterNewRateRequest($subject, $from, $to, $options) {
        $message = Message::newInstance()
            ->setSubject($subject)
            ->setFrom($from)
            ->setTo($to)
            ->setBody($this->templateEngine->render('notify-rater-new-rate-request.html.twig', $options));
        $this->mailer->send($message);
    }
}