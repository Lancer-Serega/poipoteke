<?php

namespace App\Repositories;

use App\MyExceptions\RaterRepositoryEx;
use Doctrine\DBAL\Connection;

class RaterRepository
{
    const REQUEST_STATUS_PREPROCESSED = 'PREPROCESSED';
    const REQUEST_STATUS_ACCEPTED = 'ACCEPTED';
    const REQUEST_STATUS_CANCELLED = 'CANCELLED';
    const REQUEST_STATUS_DONE = 'DONE';
    const REQUEST_DEFAULT_TITLE = 'Заявка на оценку';
    const DEFAULT_PRICE_DISTANCE = 10000;

    private $conn;

    public function __construct(Connection $conn) {
        $this->conn = $conn;
    }

    public function createRequest($options) {
        $result = 0;
        $now = time();

        $options = [
            'customer_id' => isset($options['customer_id']) ? (int)$options['customer_id'] : 0,
            'customer_contact_id' => isset($options['customer_contact_id']) ? (int)$options['customer_contact_id'] : 0,
            'rater_id' => isset($options['rater_id']) ? (int)$options['rater_id'] : 0,
            'rater_contact_id' => isset($options['rater_contact_id']) ? (int)$options['rater_contact_id'] : 0,
            'object_type_id' => isset($options['object_type_id']) ? (int)$options['object_type_id'] : '',
            'title' => isset($options['title'])
                ? (string)$options['title']
                : self::REQUEST_DEFAULT_TITLE . ' (' . date('d.m.Y', $now) . ')',
            'cost_distance' => isset($options['cost_distance']) ? (float)$options['cost_distance'] : .0,
            'cost_report' => isset($options['cost_report']) ? (float)$options['cost_report'] : .0,
            'status' => self::REQUEST_STATUS_PREPROCESSED,
            'created' => $now,
            'updated' => $now,
            'finish_date' => isset($options['finish_date']) ? (int)$options['finish_date'] : 0,
        ];

        if ($options['customer_id'] && $options['customer_contact_id']
            && $options['rater_id'] && $options['rater_contact_id']
            && $options['object_type_id']
        ) {
            $queryBuilder = $this->conn->createQueryBuilder();
            $queryBuilder
                ->insert('request_rate')
                ->values([
                    'customer_id' => ':customer_id',
                    'customer_contact_id' => ':customer_contact_id',
                    'rater_id' => ':rater_id',
                    'rater_contact_id' => ':rater_contact_id',
                    'object_type_id' => ':object_type_id',
                    'title' => ':title',
                    'cost_distance' => ':cost_distance',
                    'cost_report' => ':cost_report',
                    'status' => ':status',
                    'created' => ':created',
                    'updated' => ':updated',
                    'finish_date' => ':finish_date'
                ])
                ->setParameters($options)
                ->execute();

            $result = $this->conn->lastInsertId();
        }

        return $result;
    }

    public function getAllByBankId($bankId) {
        $raters = [];
        $contacts = [];
        $existedRaters = [];
        $existedContacts = [];
        $existedPriceReports = [];
        $existedWorkTime = [];

        $queryBuilder = $this->conn->createQueryBuilder();
        $expression = $queryBuilder->expr();

        $result = $queryBuilder
            ->select(
                'DISTINCT user.id AS user__id',
                'user.email AS user__email',
                'user.name AS user__name',
                'user.comment AS user__comment',
                'contact.id AS contact__id',
                'contact.phone AS contact__phone',
                'contact.fax AS contact__fax',
                'contact.address AS contact__address',
                'contact.coordinates AS contact__coordinates',
                'prices_distance.price_per_meter AS prices_distance__price_per_meter',
                'prices_report.price AS prices_report__price',
                'prices_report.time AS prices_report__time',
                'prices_report.object_type_id AS prices_report__object_type_id',
                'work_time.day_of_week AS work_time__day_of_week',
                'work_time.start AS work_time__start',
                'work_time.end AS work_time__end'
            )
            ->from('user', 'user')
            ->innerJoin('user', 'rater_bank', 'rater_bank', $expression->andX(
                $expression->eq('user.id', 'rater_bank.rater_id'),
                $expression->eq('rater_bank.bank_id', ':bank_id')
            ))
            ->innerJoin('rater_bank', 'user', 'bank', $expression->andX(
                $expression->eq('rater_bank.bank_id', 'bank.id'),
                $expression->eq('bank.roles', ':bank_role')
            ))
            ->leftJoin('user', 'contact', 'contact', $expression->eq('user.id', 'contact.user_id'))
            ->leftJoin('user', 'prices_distance', 'prices_distance', $expression->eq('user.id', 'prices_distance.user_id'))
            ->leftJoin('user', 'prices_report', 'prices_report', $expression->eq('user.id', 'prices_report.user_id'))
            ->leftJoin('user', 'work_time', 'work_time', $expression->eq('user.id', 'work_time.user_id'))
            ->where($expression->eq('user.roles', ':rater_role'))
            ->setParameters(['bank_id' => $bankId, 'bank_role' => 'ROLE_BANK', 'rater_role' => 'ROLE_RATER'])
            ->execute();

        while ($row = $result->fetch()) {
            $raterId = (int)$row['user__id'];
            $contactId = (int)$row['contact__id'];
            $pricesReportId = $row['prices_report__object_type_id'] . '_' . $raterId;
            $workTimeId = $row['work_time__day_of_week'] . '_' . $raterId;

            if ($raterId && !in_array($raterId, $existedRaters)) {
                $existedRaters[] = $raterId;
                $raters[] = [
                    'id' => $raterId,
                    'email' => (string)$row['user__email'],
                    'name' => (string)$row['user__name'],
                    'comment' => (string)$row['user__comment'],
                    'contacts' => [],
                    'pricePerMeter' => (float)$row['prices_distance__price_per_meter'],
                    'pricesReport' => [],
                    'workTime' => []
                ];
            }

            $existedRaterKey = array_search($raterId, $existedRaters);

            if ($contactId && !in_array($contactId, $existedContacts)) {
                $existedContacts[] = $contactId;
                $raters[$existedRaterKey]['contacts'][] = count($contacts);
                $contacts[] = [
                    'id' => $contactId,
                    'raterId' => $raterId,
                    'phone' => (string)$row['contact__phone'],
                    'fax' => (string)$row['contact__fax'],
                    'address' => (string)$row['contact__address'],
                    'coordinates' => (string)$row['contact__coordinates']
                ];
            }

            if (!in_array($pricesReportId, $existedPriceReports)
                && (float)$row['prices_report__price']
                && (int)$row['prices_report__object_type_id']
            ) {
                $existedPriceReports[] = $pricesReportId;
                $raters[$existedRaterKey]['pricesReport'][] = [
                    'price' => (float)$row['prices_report__price'],
                    'time' => (int)$row['prices_report__time'],
                    'objectTypeId' => (int)$row['prices_report__object_type_id']
                ];
            }

            if (!in_array($workTimeId, $existedWorkTime)
                && (string)$row['work_time__day_of_week']
                && (int)$row['work_time__end']
            ) {
                $existedWorkTime[] = $workTimeId;
                $raters[$existedRaterKey]['workTime'][] = [
                    'dayOfWeek' => (string)$row['work_time__day_of_week'],
                    'start' => (int)$row['work_time__start'],
                    'end' => (int)$row['work_time__end']
                ];
            }
        }

        return ['raters' => $raters, 'contacts' => $contacts];
    }

    public function getBanksWithAccreditation($email) {
        $banks = [];

        $queryBuilder = $this->conn->createQueryBuilder();
        $expression = $queryBuilder->expr();

        $result = $queryBuilder
            ->select('bank.name AS bank__name')
            ->from('user', 'rater')
            ->innerJoin('rater', 'rater_bank', 'rater_bank', $expression->eq('rater.id', 'rater_bank.rater_id'))
            ->innerJoin('rater_bank', 'user', 'bank', $expression->eq('rater_bank.bank_id', 'bank.id'))
            ->where($expression->andX(
                $expression->eq('rater.email', ':rater_email'),
                $expression->eq('rater.roles', ':rater_role'),
                $expression->eq('bank.roles', ':bank_role')
            ))
            ->setParameters(['rater_email' => $email, 'rater_role' => 'ROLE_RATER', 'bank_role' => 'ROLE_BANK'])
            ->execute();

        while ($row = $result->fetch()) {
            $banks[] = ['name' => (string)$row['bank__name']];
        }

        return $banks;
    }

    public function getPricePerMeter($raterId) {
        $result = [];

        $queryBuilder = $this->conn->createQueryBuilder();
        $expression = $queryBuilder->expr();

        try {
            $result = $queryBuilder
                ->select('price_per_meter', 'distance')
                ->from('prices_distance')
                ->where($expression->eq('user_id', ':rater_id'))
                ->setParameter('rater_id', $raterId)
                ->execute()
                ->fetchAll();
        } catch (\Exception $e) {
            $result['error'] = RaterRepositoryEx::getPricePerMeter();
        }

        if (!array_key_exists('error', $result)) {
            $result = (float)$result[0]['price_per_meter'];
        }

        return $result;
    }

    public function getPricesReport($raterId) {
        $pricesReport = [];

        $queryBuilder = $this->conn->createQueryBuilder();
        $expression = $queryBuilder->expr();

        $result = $queryBuilder
            ->select(
                'prices_report.price AS prices_report__price',
                'prices_report.time AS prices_report__time',
                'object_type.id AS object_type__id',
                'object_type.name AS object_type__name'
            )
            ->from('prices_report')
            ->leftJoin('prices_report', 'object_type', 'object_type',
                $expression->eq('prices_report.object_type_id', 'object_type.id')
            )
            ->where($expression->eq('prices_report.user_id', ':rater_id'))
            ->setParameter('rater_id', $raterId)
            ->execute();

        while ($row = $result->fetch()) {
            $pricesReport[] = [
                'price' => (float)$row['prices_report__price'],
                'time' => (int)$row['prices_report__time'],
                'objectTypeId' => (int)$row['object_type__id'],
                'objectTypeName' => (string)$row['object_type__name']
            ];
        }

        return $pricesReport;
    }

    public function getPricesReportByRaterIdAndObjectTypeId($raterId, $objectTypeId) {
        $result = [];

        $queryBuilder = $this->conn->createQueryBuilder();
        $expression = $queryBuilder->expr();

        try {
            $result = $queryBuilder
                ->select('price', 'time')
                ->from('prices_report')
                ->where($expression->andX(
                    $expression->eq('user_id', ':rater_id'),
                    $expression->eq('object_type_id', ':object_type_id')
                )
                )
                ->setParameters(['rater_id' => $raterId, 'object_type_id' => $objectTypeId])
                ->execute()
                ->fetch();
        } catch (\Exception $e) {
            $result['error'] = RaterRepositoryEx::getPricesReportByRaterIdAndObjectTypeId();
        }

        if (!array_key_exists('error', $result)) {
            $result = [
                'price' => (float)$result[0]['price'],
                'time' => (int)$result[0]['time']
            ];
        }

        return $result;
    }

    public function getRequests($userId, $page) {
        $requests = [];

        $queryBuilder = $this->conn->createQueryBuilder();
        $expression = $queryBuilder->expr();

        // $result = $queryBuilder
            // ->select(
                // 'request_rate.id AS request_rate__id',
                // 'request_rate.title AS request_rate__title',
                // 'request_rate.object_address AS request_rate__object_address',
                // 'request_rate.cost_distance AS request_rate__cost_distance',
                // 'request_rate.cost_report AS request_rate__cost_report',
                // 'request_rate.object_coordinates AS request_rate__object_coordinates',
                // 'request_rate.status AS request_rate__status',
                // 'request_rate.created AS request_rate__created',
                // 'request_rate.updated AS request_rate__updated',
                // 'contact.phone AS rater__phone',
                // 'contact.fax AS rater__fax',
                // 'contact.address AS rater__address',
                // 'customer.email AS request_rate__email',
                // 'customer_person.name AS person__name',
                // 'customer_person.phone AS person__phone',
                // 'object_type.title AS object_type__title'
            // )
            // --------------------------
            // join user -> contact -> person :: customer.email, customer_person.name, customer_person.phone
            // join contact -> person :: contact.phone, contact.address, contact.fax
            // join object type
            // при создании пользователя и заявки записать в контакт пользователя адрес его объекта
            // ->from('request_rate')
            // ->leftJoin('request_rate', 'object_type', 'object_type',
                // $expression->eq('prices_report.object_type_id', 'object_type.id')
            // )
            // --------------------------
            // ->where($expression->eq('request_rate.rater_id', ':rater_id'))
            // ->setParameter('rater_id', $userId)
            // ->execute();

        // while ($row = $result->fetch()) {
            // $requests[] = [];
        // }

        return $requests;
    }

    public function getRequestStatusById($id, $raterId) {
        $queryBuilder = $this->conn->createQueryBuilder();
        $expression = $queryBuilder->expr();

        try {
        $result = $queryBuilder
            ->select('status')
            ->from('request_rate')
            ->where($expression->andX(
                $expression->eq('id', ':id'),
                $expression->eq('rater_id', ':rater_id')
            )
            )
            ->setParameters(
                [
                    'id' => $id,
                    'rater_id' => $raterId
                ]
            )
            ->execute()
            ->fetchAll();
        } catch (\Exception $e) {
            $result['error'] = RaterRepositoryEx::getRequestStatusById();
        }

        if (!empty($result)) {
            $status = (float)$result[0]['status'];
        }

        return $status;
    }

    public function updateRequestStatus($options) {
        $queryBuilder = $this->conn->createQueryBuilder();
        $expression = $queryBuilder->expr();

        $options = [
            'id' => isset($options['id']) ? (int)$options['id'] : 0,
            'rater_id' => isset($options['rater_id']) ? (int)$options['rater_id'] : 0,
            'status' => isset($options['status'])
                ? (string)$options['status']
                : self::REQUEST_STATUS_PREPROCESSED
        ];

        try {
            $result = $queryBuilder
            ->update('request_rate')
            ->set('status', ':status')
            ->where($expression->andX(
                $expression->eq('id', ':id'),
                $expression->eq('rater_id', ':rater_id')
            ))
            ->setParameters([
                'id' => $options['id'],
                'rater_id' => $options['rater_id'],
                'status' => $options['status']
            ])
            ->execute();
        } catch (\Exception $e) {
            $result['error'] = RaterRepositoryEx::updateRequestStatus();
        }

        return $result;
    }

    public function getRequestAllStatuses() {
        return [
            self::REQUEST_STATUS_PREPROCESSED,
            self::REQUEST_STATUS_ACCEPTED,
            self::REQUEST_STATUS_CANCELLED,
            self::REQUEST_STATUS_DONE
        ];
    }

    public function getRequestAvailableUpdateStatuses() {
        return [
            self::REQUEST_STATUS_PREPROCESSED,
            self::REQUEST_STATUS_ACCEPTED
        ];
    }

    public function updateInfo($data, $userId) {
        $queryBuilder = $this->conn->createQueryBuilder();
        $expr = $queryBuilder->expr();

        try {
            $result['response'] = $queryBuilder
                ->update('user')
                ->set('title', $data['title'])
                ->set('comment', $data['comment'])
                ->set('requisites', $data['requisites'])
                ->where($expr->eq('id', ':id'))
                ->setParameter('id', $userId)
                ->execute();
        } catch (\Exception $e) {
            $result['error'] = RaterRepositoryEx::updatePricesDistance();
        }

        return $result;
    }

    public function insertInfo($value, $userId) {
        $queryBuilder = $this->conn->createQueryBuilder();

        try {
            $result['response'][] = $queryBuilder
                ->insert('user')
                ->values(
                    [
                        'id' => $userId,
                        'title' => ':title',
                        'comment' => ':comment',
                        'requisites' => ':requisites',
                    ]
                )
                ->setParameters($value)
                ->execute()
                ->fetch();
        } catch (\Exception $e) {
            $result['error'] = RaterRepositoryEx::updatePricesDistance();
        }

        return $result;
    }

    public function selectPricesDistance($userId) {
        $queryBuilder = $this->conn->createQueryBuilder();
        $expr = $queryBuilder->expr();

        try {
            $result['response'] = $queryBuilder
                ->select('user_id')
                ->from('prices_distance')
                ->where($expr->eq('user_id', (int)$userId))
                ->execute();
        } catch (\Exception $e) {
            $result['error'] = RaterRepositoryEx::updatePricesDistance();
        }

        return $result;
    }

    public function updatePricesDistance($data, $userId) {
        $queryBuilder = $this->conn->createQueryBuilder();
        $expr = $queryBuilder->expr();

        try {
            $result['response'] = $queryBuilder
                ->update('prices_distance')
                ->set('price', ':price')
                ->set('distance', ':distance')
                ->where($expr->eq('user_id', ':id'))
                ->setParameters(
                    [
                        'price' => (float)$data,
                        'distance' => (int)$this::DEFAULT_PRICE_DISTANCE,
                        'id' => (int)$userId
                    ]
                )
                ->execute();
        }catch(\Exception $e) {
            $result['error'] = RaterRepositoryEx::updatePricesDistance();
        }

        return $result;
    }

    public function insertPricesDistance($data, $userId) {
        $queryBuilder = $this->conn->createQueryBuilder();

        try {
            $result['response'] = $queryBuilder
                ->insert('prices_distance')
                ->values(
                    [
                        'price' => ':price',
                        'distance' => ':distance',
                        'id' => ':id'
                    ]
                )
                ->setParameters(
                    [
                        'price' => (float)$data,
                        'distance' => (int)$this::DEFAULT_PRICE_DISTANCE,
                        'id' => (int)$userId
                    ]
                )
                ->execute();
        } catch (\Exception $e) {
            $result['error'] = RaterRepositoryEx::insertPricesDistance();
        }

        return $result;
    }

    public function selectPricesReport() {
        $queryBuilder = $this->conn->createQueryBuilder();

        try {
            return $queryBuilder
                ->select('id')
            ->from('object_type')
            ->execute();
        } catch (\Exception $e) {
            $result['error'] = RaterRepositoryEx::selectPricesReport();
        }

        return $result;
    }

    public function deletePricesReport($data, $userId) {
        $queryBuilder = $this->conn->createQueryBuilder();
        $expr = $queryBuilder->expr();
        $connApp = $this->conn;

        try {
            $result['response'] = $queryBuilder
                ->delete('prices_report')
                ->where(
                    $expr->andX(
                        $expr->eq('id', ':id'),
                        $expr->in('object_type_id', ':object_type_arr')
                    )
                )
                ->setParameters(
                    [
                        'id' => (int)$userId,
                        'object_type_arr' => $data
                    ],
                    [
                        'object_type_arr' => $connApp::PARAM_INT_ARRAY
                    ]
                )->execute();
        } catch (\Exception $e) {
            $result['error'] = RaterRepositoryEx::deletePricesReport();
        }

        return $result;
    }

    public function updatePricesReport($objectTypeId, $price, $time, $userId) {
        $queryBuilder = $this->conn->createQueryBuilder();
        $expr = $queryBuilder->expr();

        try {
            $result['response'] = $queryBuilder
                ->update('prices_report')
                ->set('price', ':price')
                ->set('time', ':time')
                ->where(
                    $expr->andX(
                        $expr->eq('id', ':id'),
                        $expr->eq('object_type_id', ':object_type_id')
                    )
                )
                ->setParameters(
                    [
                        'id' => (int)$userId,
                        'object_type_id' => (int)$objectTypeId,
                        'price' => (float)$price,
                        'time' => (int)$time
                    ]
                )
                ->execute();
        } catch (\Exception $e) {
            $result['error'] = RaterRepositoryEx::updatePricesReport();
        }

        return $result;
    }

    public function insertPricesReport($objectTypeId, $price, $time, $userId) {
        $queryBuilder = $this->conn->createQueryBuilder();

        try {
            $result['response'] = $queryBuilder
                ->insert('prices_report')
                ->values(
                    [
                        'price' => ':price',
                        'time' => ':time',
                        'id' => ':id',
                        'object_type_id' => ':object_type_id'
                    ]
                )
                ->setParameters(
                    [
                        'id' => $userId,
                        'object_type_id' => $objectTypeId,
                        'price' => $price,
                        'time' => $time
                    ]
                )
                ->execute();
        } catch (\Exception $e) {
            $result['error'] = RaterRepositoryEx::insertPricesReport();
        }

        return $result;
    }

    public function selectObjectTypeId($userId, $objectTypeId) {
        $queryBuilder = $this->conn->createQueryBuilder();
        $expr = $queryBuilder->expr();
        $queryBuilder->resetQueryParts();

        try {
            $result['response'] = $queryBuilder
                ->select('id', 'object_type_id')
                ->from('prices_report')
                ->where(
                    $expr->andX(
                        $expr->eq('id', ':id'),
                        $expr->eq('object_type_id', ':object_type_id')
                    )
                )
                ->setParameters(
                    [
                        'id' => $userId,
                        'object_type_id' => $objectTypeId
                    ]
                )
                ->execute();
        } catch (\Exception $e) {
            $result['error'] = RaterRepositoryEx::selectObjectTypeId();
        }

        return $result;
    }

    public function getRaterAccreditationByBankId($raterId, $bankId) {
        $queryBuilder = $this->conn->createQueryBuilder();
        $expression = $queryBuilder->expr();

        return $queryBuilder
            ->select('COUNT(bank_id)')
            ->from('rater_bank')
            ->where(
                $expression->andX(
                    $expression->eq('rater_id', ':rater_id'),
                    $expression->eq('bank_id', ':bank_id')
                )
            )
            ->setParameters([
                    'rater_id' => $raterId,
                    'bank_id' => $bankId,
                ]
            )
            ->execute()
            ->fetchColumn(0);
    }

    public function getPricesDistanceByRaterId($raterId) {
        $result = [];

        $queryBuilder = $this->conn->createQueryBuilder();
        $expression = $queryBuilder->expr();

        try {
            $result = $queryBuilder
                ->select('price', 'distance')
                ->from('prices_distance')
                ->where($expression->eq('user_id', ':rater_id'))
                ->setParameter('rater_id',  $raterId)
                ->execute()
                ->fetch();
        } catch (\Exception $e) {
            $result['error'] = RaterRepositoryEx::getPricesDistanceByRaterId();
        }

        if (!array_key_exists('error', $result)) {
            $result = [
                'price' => (float)$result[0]['price'],
                'time' => (int)$result[0]['time']
            ];
        }

        return $result;

    }
}