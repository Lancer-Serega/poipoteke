<?php

namespace App\Provider;

use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\User as SymUser;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Doctrine\DBAL\Connection;
use Silex\Application;
use App\Repositories;

class User implements UserProviderInterface
{
    /** @var $app Application */
    /** @var $conn Connection */
    private $app;
    private $conn;

    public function __construct(Application $app) {
        $this->app = $app;
        $this->conn = $app['db'];
    }

    public function loadUserByUsername($email) {
        /** @var Repositories\UserRepository $userRepository */
        $userRepository = $this->app['user.repository'];
        $userInfo = $userRepository->getUserByEmail($email, true);

        if (empty($userInfo)) {
            throw new UsernameNotFoundException(sprintf('Пользователь с email "%s" не найден.', $email));
        }

        $userRepository->updateLastAccess($email);

        return new SymUser($userInfo['email'], $userInfo['password'], explode(',', $userInfo['roles']), true, true, true, true);
    }

    public function refreshUser(UserInterface $user) {
        if (!$user instanceof SymUser) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', get_class($user)));
        }

        return $this->loadUserByUsername($user->getUsername());
    }

    public function supportsClass($class) {
        return $class === 'Symfony\Component\Security\Core\User\User';
    }
}