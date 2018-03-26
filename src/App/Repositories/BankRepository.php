<?php

namespace App\Repositories;

use Doctrine\DBAL\Connection;

class BankRepository
{
    private $conn;

    public function __construct(Connection $conn) {
        $this->conn = $conn;
    }

    public function getAllActive() {
        $banks = [];

        $queryBuilder = $this->conn->createQueryBuilder();
        $expression = $queryBuilder->expr();

        $result = $queryBuilder
            ->select('DISTINCT user.id AS user__id', 'user.name AS user__name')
            ->from('user')
            ->innerJoin('user', 'rater_bank', 'rater_bank', 'user.id = rater_bank.bank_id')
            ->where($expression->eq('user.roles', ':role'))
            ->setParameter('role', 'ROLE_BANK')
            ->execute();

        while ($row = $result->fetch()) {
            $banks[] = [
                'id' => (int)$row['user__id'],
                'name' => (string)$row['user__name']
            ];
        }

        return $banks;
    }

    public function getByRaterIdAndBankId($raterId, $bankId) {
        $raterBank = [];

        $queryBuilder = $this->conn->createQueryBuilder();
        $expression = $queryBuilder->expr();

        $result = $queryBuilder
            ->select('rater_id', 'bank_id')
            ->from('rater_bank')
            ->where($expression->andX(
                $expression->eq('rater_id', ':rater_id'),
                $expression->eq('bank_id', ':bank_id')
            ))
            ->setParameters(['rater_id' => $raterId, 'bank_id' => $bankId])
            ->execute()
            ->fetchAll();

        if (!empty($result)) {
            $raterBank = [
                'rater_id' => (int)$result[0]['rater_id'],
                'bank_id' => (int)$result[0]['bank_id']
            ];
        }

        return $raterBank;
    }

    public function getBankById($bankId) {
        $queryBuilder = $this->conn->createQueryBuilder();
        $expr = $queryBuilder->expr();

        $result = $queryBuilder
            ->select('name')
            ->from('user')
            ->where(
                $expr->eq('id', ':bankId'),
                $expr->eq('roles', ':userRoles')
            )
            ->setParameters([
                'bankId' => $bankId,
                'userRoles' => 'ROLE_BANK'
            ])
            ->execute()->fetch();

        return $result;
    }
}