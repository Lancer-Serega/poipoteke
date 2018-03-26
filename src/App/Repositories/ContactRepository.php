<?php

namespace App\Repositories;

use App\Repositories;
use Doctrine\DBAL\Connection;
use Silex\Application;

class ContactRepository
{
    private $conn;

    public function __construct(Connection $conn) {
        $this->conn = $conn;
    }

    public function createContact($options) {
        $result = 0;

        $options = [
            'user_id' => isset($options['user_id']) ? (int)$options['user_id'] : 0,
            'phone' => isset($options['phone']) ? (string)$options['phone'] : '',
            'fax' => isset($options['fax']) ? (string)$options['fax'] : '',
            'address' => isset($options['address']) ? (string)$options['address'] : '',
            'coordinates' => isset($options['coordinates']) ? (string)$options['coordinates'] : '',
            'active' => 1
        ];

        if ($options['user_id'] && $options['address']) {
            $queryBuilder = $this->conn->createQueryBuilder();
            $queryBuilder
                ->insert('contact')
                ->values([
                    'user_id' => ':user_id',
                    'phone' => ':phone',
                    'fax' => ':fax',
                    'address' => ':address',
                    'coordinates' => ':coordinates',
                    'active' => ':active'
                ])
                ->setParameters($options)
                ->execute();

            $result = $this->conn->lastInsertId();
        }

        return $result;
    }

    public function createPerson($options) {
        $result = 0;

        $options = [
            'contact_id' => isset($options['contact_id']) ? (int)$options['contact_id'] : 0,
            'name' => isset($options['name']) ? (string)$options['name'] : '',
            'phone' => isset($options['phone']) ? (string)$options['phone'] : '',
            'email' => isset($options['email']) ? (string)$options['email'] : '',
            'skype' => isset($options['skype']) ? (string)$options['skype'] : '',
            'active' => 1
        ];

        if ($options['contact_id'] && $options['name']) {
            $queryBuilder = $this->conn->createQueryBuilder();
            $queryBuilder
                ->insert('person')
                ->values([
                    'contact_id' => ':contact_id',
                    'name' => ':name',
                    'phone' => ':phone',
                    'email' => ':email',
                    'skype' => ':skype',
                    'active' => ':active'
                ])
                ->setParameters($options)
                ->execute();

            $result = $this->conn->lastInsertId();
        }

        return $result;
    }

    public function getById($contactId) {
        $contact = [];

        $queryBuilder = $this->conn->createQueryBuilder();
        $expression = $queryBuilder->expr();

        $result = $queryBuilder
            ->select('user_id', 'phone', 'fax', 'address', 'coordinates')
            ->from('contact')
            ->where($expression->eq('id', ':contact_id'))
            ->setParameter('contact_id', $contactId)
            ->execute()
            ->fetchAll();

        if (!empty($result)) {
            $contact = [
                'user_id' => (int)$result[0]['user_id'],
                'phone' => (string)$result[0]['phone'],
                'fax' => (string)$result[0]['fax'],
                'address' => (string)$result[0]['address'],
                'coordinates' => (string)$result[0]['coordinates']
            ];
        }

        return $contact;
    }

    public function getAllByUserId($userId) {
        $contacts = [];

        $queryBuilder = $this->conn->createQueryBuilder();
        $expression = $queryBuilder->expr();

        $result = $queryBuilder
            ->select(
                'contact.id AS contact__id',
                'contact.phone AS contact__phone',
                'contact.fax AS contact__fax',
                'contact.address AS contact__address',
                'contact.coordinates AS contact__coordinates',
                'person.id AS person__id',
                'person.name AS person__name',
                'person.phone AS person__phone',
                'person.email AS person__email',
                'person.skype AS person__skype'
            )
            ->from('contact')
            ->leftJoin('contact', 'person', 'person', $expression->eq('contact.id', 'person.contact_id'))
            ->where($expression->eq('contact.user_id', ':user_id'))
            ->setParameter('user_id', $userId)
            ->execute();

        $existedContacts = [];
        $existedPersons = [];

        while ($row = $result->fetch()) {
            $contactId = (int)$row['contact__id'];
            $personId = (int)$row['person__id'];

            if ($contactId && !in_array($contactId, $existedContacts)) {
                $existedContacts[] = $contactId;
                $contacts[] = [
                    'id' => $contactId,
                    'phone' => (string)$row['contact__phone'],
                    'fax' => (string)$row['contact__fax'],
                    'address' => (string)$row['contact__address'],
                    'coordinates' => (string)$row['contact__coordinates'],
                    'persons' => []
                ];
            }

            $existedContactKey = array_search($contactId, $existedContacts);

            if ($personId && !in_array($personId, $existedPersons)) {
                $existedPersons[] = $personId;
                $contacts[$existedContactKey]['persons'][] = [
                    'id' => $personId,
                    'name' => (string)$row['person__name'],
                    'phone' => (string)$row['person__phone'],
                    'email' => (string)$row['person__email'],
                    'skype' => (string)$row['person__skype']
                ];
            }
        }

        return $contacts;
    }

    public function getContactById($contactId) {
        $info = [];

        $queryBuilder = $this->conn->createQueryBuilder();
        $expression = $queryBuilder->expr();

        $result = $queryBuilder
            ->select('phone', 'fax', 'address', 'coordinates', 'active')
            ->from('contact')
            ->where($expression->eq('id', ':contact_id'))
            ->setParameter('contact_id', $contactId)
            ->execute()
            ->fetch();

        if (!empty($result)) {
            $info = [
                'phone' => (string)$result['phone'],
                'fax' => (string)$result['fax'],
                'address' => (string)$result['address'],
                'coordinates' => (string)$result['coordinates'],
                'active' => (int)$result['active']
            ];
        }

        return $info;
    }

    public function getContactRaterById($raterContactId) {
        $info = [];
        $queryBuilder = $this->conn->createQueryBuilder();
        $expr = $queryBuilder->expr();
        $info = $queryBuilder
            ->select('c.user_id, c.phone, c.fax, c.address, c.coordinates, c.active', 'u.roles')
            ->from('contact', 'c')
            ->leftJoin('c', 'user', 'u', $expr->eq('c.user_id', 'u.id'))
            ->where(
                $expr->andX(
                    $expr->eq('c.user_id', ':userId'),
                    $expr->eq('c.active', ':c_active'),
                    $expr->eq('u.roles', ':u_roles')
                )
            )
            ->setParameters([
                'userId' => $raterContactId,
                'c_active' => 1,
                'u_roles' => 'ROLE_RATER'
            ])
            ->execute()
            ->fetch();

        return $info;
    }

    public function delete($data, $userId) {
        $queryBuilder = $this->conn->createQueryBuilder();

        if (!empty($data['delete'])) {
            $queryBuilder
                ->update('contact')
                ->set('contact.active', (int)0)
                ->where('id = ' . $userId)
                ->execute();
        }
    }

    public function update($data, $userId) {
        $queryBuilder = $this->conn->createQueryBuilder();
        $expr = $queryBuilder->expr();

        foreach ($data['update'] as $key => $value) {
            $paramKey = ':d' . $key;
            $parameters[$paramKey] = array_key_exists('contact', $value) ? $value['contact'] : 'none';
            $daysExpressionCondition[] = $paramKey;

            $queryBuilder
                ->update('contact')
                ->set('phone', $value['phone'])
                ->set('fax', $value['fax'])
                ->set('address', $value['address'])
                ->setParameters($parameters)
                ->where(
                    $expr->andX(
                        $expr->eq('id', $userId),
                        $expr->eq('contact.active', 1),
                        $expr->in('contact', $paramKey)
                    )
                )
                ->execute();
        }
    }

    public function insert($data, $userId) {
        $queryBuilder = $this->conn->createQueryBuilder();

        if (!empty($data['insert'])) {

            foreach ($data['insert'] as $key => $value) {
                $value['phone'] = array_key_exists('phone', $value) ? (string)$value['phone'] : '';
                $value['fax'] = array_key_exists('fax', $value) ? (string)$value['fax'] : '';
                $value['address'] = array_key_exists('address', $value) ? (string)$value['address'] : '';
                $value['coordinates'] = array_key_exists('coordinates', $value) ? (float)$value['coordinates'] : .0;

                $result[] = $queryBuilder
                    ->insert('contact')
                    ->values(
                        [
                            'id' => $userId,
                            'phone' => ':phone',
                            'fax' => ':fax',
                            'address' => ':address',
                            'coordinates' => ':coordinates'
                        ]
                    )
                    ->setParameters($value)
                    ->execute();
            } return $result;

        }


    }

    public function getObjectTypeById($objectTypeId) {
        $queryBuilder = $this->conn->createQueryBuilder();
        $expr = $queryBuilder->expr();

        return $queryBuilder
            ->select('id, name')
            ->from('object_type')
            ->where(
                $expr->eq('id', ':objectTypeId')
            )
            ->setParameters([
                'objectTypeId' => $objectTypeId,
            ])
            ->execute();
    }
}
