<?php

namespace App\Repositories;

use Doctrine\DBAL\Connection;

class RequisiteRepository
{
    private $conn;

    public function __construct(Connection $conn) {
        $this->conn = $conn;
    }

    public function getUserRequisites($userId) {
        $queryBuilder = $this->conn->createQueryBuilder();
        $expr = $queryBuilder->expr();
        $queryBuilder
            ->select('inn')
            ->from('requisites')
            ->where(
                $expr->eq('user_id', ':userId')
            )
            ->setParameters([
                'userId' => $userId,
            ])
            ->execute()
            ->fetch();


    }

    public function addUserRequisites($userId, $requisites) {
        $queryBuilder = $this->conn->createQueryBuilder();
        $queryBuilder->insert('requisites')
            ->values([
                'user_id' => ':userId',
                'inn' => ':inn'
            ])
            ->setParameters([
                'userId' => $userId,
                'inn' => $requisites['inn']
            ])
            ->execute();
    }

    public function updateUserRequisites($userId, $requisites) {
        $queryBuilder = $this->conn->createQueryBuilder();
        $expr = $queryBuilder->expr();
        $queryBuilder
            ->update('requisites')
            ->set('inn', ':inn')
            ->where('user_id = :userId')
            ->andWhere(' = ')
            ->setParameters([
                'inn' => $requisites['inn'],
                'user_id' => $userId
            ])
            ->execute();

    }
}