<?php

namespace App\Repositories;

use Doctrine\DBAL\Connection;

class UserRepository
{
    private $conn;
    private $roles;

    public function __construct(Connection $conn) {
        $this->conn = $conn;
        $this->roles = [
            'customer' => 'ROLE_CUSTOMER',
            'rater' => 'ROLE_RATER',
            'bank' => 'ROLE_BANK',
            'admin' => 'ROLE_ADMIN'
        ];
    }

    public function createUser($options) {
        $result = 0;
        $now = time('now');

        $options = [
            'email' => isset($options['email']) ? (string)$options['email'] : '',
            'password' => isset($options['password']) ? (string)$options['password'] : '',
            'name' => isset($options['name']) ? (string)$options['name'] : '',
            'comment' => isset($options['comment']) ? (string)$options['comment'] : '',
            'requisites' => isset($options['requisites']) ? (string)$options['requisites'] : '',
            'roles' => isset($options['roles']) && in_array($options['roles'], $this->roles)
                ? (string)$options['roles'] : $this->roles['customer'],
            'created' => $now,
            'access' => $now,
            'active' => 1
        ];

        if ($options['email'] && $options['password']) {
            $queryBuilder = $this->conn->createQueryBuilder();
            $queryBuilder
                ->insert('user')
                ->values([
                    'email' => ':email',
                    'password' => ':password',
                    'name' => ':name',
                    'comment' => ':comment',
                    'requisites' => ':requisites',
                    'roles' => ':roles',
                    'created' => ':created',
                    'access' => ':access',
                    'active' => ':active'
                ])
                ->setParameters($options)
                ->execute();

            $result = $this->conn->lastInsertId();
        }

        return $result;
    }

    public function getUserByEmail($email, $full = false) {
        $info = [];

        $queryBuilder = $this->conn->createQueryBuilder();
        $expression = $queryBuilder->expr();

        $result = $queryBuilder
            ->select('id', 'password', 'name', 'comment', 'requisites', 'roles', 'created', 'access')
            ->from('user')
            ->where($expression->eq('email', ':email'))
            ->setParameter('email', $email)
            ->execute()
            ->fetchAll();

        if (!empty($result)) {
            $info = [
                'email' => $email,
                'name' => (string)$result[0]['name'],
                'comment' => (string)$result[0]['comment'],
                'requisites' => (string)$result[0]['requisites']
            ];

            if ($full) {
                $info = array_merge($info, [
                    'id' => (int)$result[0]['id'],
                    'created' => (int)$result[0]['created'],
                    'access' => (int)$result[0]['access'],
                    'password' => (string)$result[0]['password'],
                    'roles' => (string)$result[0]['roles']
                ]);
            }
        }

        return $info;
    }

    public function getUserById($userId) {
        $info = [];

        $queryBuilder = $this->conn->createQueryBuilder();
        $expr = $queryBuilder->expr();

        $result = $queryBuilder
            ->select('email', 'password', 'name', 'comment', 'requisites', 'roles', 'created', 'access')
            ->from('user')
            ->where($expr->eq('id', ':user_id'))
            ->setParameter('user_id', $userId)
            ->execute()
            ->fetchAll();

        if (!empty($result)) {
            $info = [
                'email' => (string)$result[0]['email'],
                'password' => (string)$result[0]['password'],
                'name' => (string)$result[0]['name'],
                'comment' => (string)$result[0]['comment'],
                'requisites' => (string)$result[0]['requisites'],
                'created' => (int)$result[0]['created'],
                'access' => (int)$result[0]['access'],
                'roles' => (string)$result[0]['roles']
            ];
        }

        return $info;
    }

    public function getIdByEmail($email) {
        $queryBuilder = $this->conn->createQueryBuilder();
        $expression = $queryBuilder->expr();

        $result = $queryBuilder
            ->select('id')
            ->from('user')
            ->where($expression->eq('email', ':email'))
            ->setParameter('email', $email)
            ->execute()
            ->fetch();

        return (int)$result['id'];
    }

    public function getLastAccess($email) {
        $queryBuilder = $this->conn->createQueryBuilder();

        $expression = $queryBuilder->expr();
        $queryBuilder
            ->select('access')
            ->from('user')
            ->where(
                $expression->eq('email', ':email')
            )
            ->setParameter('email', $email)
            ->execute();
    }

    public function updateLastAccess($email) {
        $queryBuilder = $this->conn->createQueryBuilder();

        $queryBuilder
            ->update('user')
            ->set('access', ':access')
            ->where('email = :email')
            ->setParameters(
                [
                    'access' => time(),
                    'email' => $email
                ]
            )
            ->execute();
    }

    public function updateUserEmail($userId, $newEmail) {
        $queryBuilder = $this->conn->createQueryBuilder();

        return $queryBuilder
            ->update('user')
            ->set('email', ':email')
            ->where('id = :userId')
            ->setParameters([
                    'email' => $newEmail,
                    'userId' => $userId
                ]
            )
            ->execute();
    }

    public function updatePersonEmail($contactId, $newEmail) { // In this file or create PersonRepository.php ??
        $queryBuilder = $this->conn->createQueryBuilder();

        return $queryBuilder
            ->update('person')
            ->set('email', ':email')
            ->where('contact_id = :contactId')
            ->setParameters([
                    'email' => $newEmail,
                    'contactId' => $contactId
                ]
            )
            ->execute();
    }

    //public function getLastAccess() {
    //    $queryBuilder = $this->conn->createQueryBuilder();
    //
    //    $expression = $queryBuilder->expr();
    //    $queryBuilder
    //        ->select('access')
    //        ->from('user')
    //        ->where(
    //            $expression->eq('user.id', ':user_id')
    //        )
    //        ->setParameter('user_id', )
    //        ->execute();
    //}
    //
    //public function updateLastAccess() {
    //    $queryBuilder = $this->conn->createQueryBuilder();
    //
    //    $queryBuilder
    //        ->update('user')
    //        ->set('access', ':access')
    //        ->where('user.id = :user_id')
    //        ->setParameters(
    //            [
    //                'access' => time(),
    //                'user_id' =>
    //            ]
    //        )
    //        ->execute();
    //}
}

