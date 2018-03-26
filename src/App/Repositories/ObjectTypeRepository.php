<?php

namespace App\Repositories;

use Doctrine\DBAL\Connection;

class ObjectTypeRepository
{
    private $conn;

    public function __construct(Connection $conn) {
        $this->conn = $conn;
    }

    public function getAll() {
        $objectTypes = [];

        $queryBuilder = $this->conn->createQueryBuilder();

        $result = $queryBuilder
            ->select('id', 'name')
            ->from('object_type')
            ->execute();

        while ($row = $result->fetch()) {
            $objectTypes[] = [
                'id' => (int)$row['id'],
                'name' => (string)$row['name']
            ];
        }

        return $objectTypes;
    }

    public function getById($objectTypeId) {
        $objectType = [];

        $queryBuilder = $this->conn->createQueryBuilder();

        $result = $queryBuilder
            ->select('id', 'name')
            ->from('object_type')
            ->where($expression->eq('id', ':object_type_id'))
            ->setParameter('object_type_id', $objectTypeIds)
            ->execute()
            ->fetchAll();

        if (!empty($result)) {
            $objectType = [
                'id' => (int)$result[0]['id'],
                'name' => (string)$result[0]['name']
            ];
        }

        return $objectType;
    }
}