<?php

namespace App\Services;

use Silex\Application;
use Symfony\Component\HttpFoundation\Session\Session;
use Exception;

class FrequentRequestProtect
{
    const LIFETIME = 60;
    const REQUEST_NUMBER_LIMIT = 10;
    const FIRST_REQUEST_INDEX = 1;
    const EXCEPTION_CODE_TOO_MANY_REQUESTS = 429;
    const EXCEPTION_MESSAGE_TOO_MANY_REQUESTS = 'Too many requests';
    const VAR_NAME_REQUEST_NUMBER = '_requestNumber';
    const VAR_NAME_LIFETIME_START = '_lifeTimeStart';

    private $session;
    private $lifetime;
    private $limit;
    private $requestNumberVarName;
    private $lifetimeStartVarName;

    public function __construct(Session $session) {
        $this->session = $session;
    }

    public function checkAccess($caller = '', $options = []) {
        $this->setOptions($options);
        $this->setNames($caller);

        if ($this->isLifetimeExceeded()) {
            $this->resetSession();
        } else if ($this->isRequestNumberLessThanLimit()) {
            $this->updateSession();
        } else {
            $this->setBan();
        }
    }

    private function setOptions($options) {
        $this->limit = isset($options['requestNumberLimit'])
            ? (int)$options['requestNumberLimit']
            : self::REQUEST_NUMBER_LIMIT;
        $this->lifetime = isset($options['lifetime'])
            ? (int)$options['lifetime']
            : self::LIFETIME;
    }

    private function setNames($prefix) {
        $this->requestNumberVarName = $prefix . self::VAR_NAME_REQUEST_NUMBER;
        $this->lifetimeStartVarName = $prefix . self::VAR_NAME_LIFETIME_START;
    }

    private function isLifetimeExceeded() {
        return time() - $this->getLifetimeStart() > $this->lifetime;
    }

    private function resetSession() {
        $this->session->set($this->requestNumberVarName, self::FIRST_REQUEST_INDEX);
        $this->session->set($this->lifetimeStartVarName, time());
    }

    private function isRequestNumberLessThanLimit() {
        return $this->getRequestNumber() < $this->limit;
    }

    private function updateSession() {
        $this->session->set($this->requestNumberVarName, self::FIRST_REQUEST_INDEX + $this->getRequestNumber());
    }

    private function setBan() {
        throw new Exception(self::EXCEPTION_MESSAGE_TOO_MANY_REQUESTS, self::EXCEPTION_CODE_TOO_MANY_REQUESTS);
    }

    private function getLifetimeStart() {
        if (!$this->session->has($this->lifetimeStartVarName)) {
            $this->session->set($this->lifetimeStartVarName, time());
        }

        return $this->session->get($this->lifetimeStartVarName);
    }

    private function getRequestNumber() {
        return $this->session->has($this->requestNumberVarName) ? $this->session->get($this->requestNumberVarName) : 0;
    }
}
