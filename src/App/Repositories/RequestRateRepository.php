<?php
/**
 * Created by PhpStorm.
 * User: Lancer
 * Date: 23.05.2016
 * Time: 15:14
 */

namespace App\Repositories;

use Doctrine\DBAL\Connection;

class RequestRateRepository
{
    private $conn;

    public function __construct(Connection $conn) {
        $this->conn = $conn;
    }

    public function getRaterRequest($raterId) {
        $queryBuilder = $this->conn->createQueryBuilder();
        $expr = $queryBuilder->expr();
        $queryBuilder
            ->select('
            id, customer_id, rater_id, object_type_id, customer_contact_id, rater_contact_id,
            title, cost_distance, cost_report, status, created, updated, finish_date')
            ->from('request_rate')
            ->where(
                $expr->eq('rater_id', ':raterId')
            )
            ->setParameters([
                'raterId' => $raterId,
            ])
            ->execute();
    }
}