<?php

namespace App\Repositories;

use Doctrine\DBAL\Connection;

class WorkTimeRepository
{
    private $conn;
    private $weekDaysNames;
    private $dayMin;
    private $dayMax;

    public function __construct(Connection $conn) {
        $this->conn = $conn;
        $this->weekDaysNames = [
            'monday' => 'MONDAY',
            'tuesday' => 'TUESDAY',
            'wednesday' => 'WEDNESDAY',
            'thursday' => 'THURSDAY',
            'friday' => 'FRIDAY',
            'saturday' => 'SATURDAY',
            'sunday' => 'SUNDAY'
        ];
        $this->dayMin = 0;
        $this->dayMax = 86400;
    }

    public function getByUserId($userId) {
        $workTime = [];

        $queryBuilder = $this->conn->createQueryBuilder();
        $expression = $queryBuilder->expr();

        $result = $queryBuilder
            ->select('day_of_week', 'start', 'end')
            ->from('work_time')
            ->where($expression->eq('user_id', ':user_id'))
            ->setParameter('user_id', $userId)
            ->execute();

        while ($row = $result->fetch()) {
            $workTime[] = [
                'dayOfWeek' => (string)$row['day_of_week'],
                'start' => (int)$row['start'],
                'end' => (int)$row['end']
            ];
        }

        return $workTime;
    }

    public function getWeekDaysNames() {
        return $this->weekDaysNames;
    }

    public function delete($data, $userId) {

        $queryBuilder = $this->conn->createQueryBuilder();
        $connApp = $this->conn;
        $expr = $queryBuilder->expr();

        return $queryBuilder
            ->delete('work_time')
            ->where(
                $expr->andX(
                    $expr->eq('id', ':id'),
                    $expr->in('day_of_week', ':day_of_week')
                )
            )
            ->setParameters(
                [
                    'id' => $userId,
                    'day_of_week' => $data['delete']
                ],
                [
                    'day_of_week' => $connApp::PARAM_STR_ARRAY
                ]
            )
            ->execute();
        /*         $dayOfWeek = ['adsfasdf', 'monday', 'WEDNESDAY', 'asdf'];
                 $parameters = ['user_id' => 1];
                 $daysExpressionCondition = '';

                 foreach ($days as $key => $value) {
                     $parameterKey = ':d' . $key;
                     $parameters[$parameterKey] = $value;
                     $daysExpressionCondition[] = $parameterKey;
                 }

                 $queryBuilder
                     ->delete('work_time')
                     ->where($expression->andX(
                         $expression->eq('user_id', ':user_id'),
                         $expression->in('day_of_week', $daysExpressionCondition)
                     ))
                     ->setParameters($parameters)
                     ->execute();*/
    }

    public function update($data, $userId) {
        $queryBuilder = $this->conn->createQueryBuilder();
        $expr = $queryBuilder->expr();

        return $queryBuilder
            ->update('work_time')
            ->set('start', ':start')
            ->set('end', ':end')
            ->where(
                $expr->andX(
                    $expr->eq('id', ':id'),
                    $expr->in('day_of_week', ':dayOfWeek')
                )
            )
            ->setParameters(
                [
                    'id' => $userId,
                    'start' => $data['start'],
                    'end' => $data['end'],
                    'dayOfWeek' => $data['dayOfWeek']
                ]
            )
            ->execute();
    }

    public function insert($data, $userId) {
        $queryBuilder = $this->conn->createQueryBuilder();

        return $queryBuilder
            ->insert('work_time')
            ->values([
                'day_of_week' => ':dayOfWeek',
                'start' => ':start',
                'end' => ':end',
                'dayOfWeek' => ':dayOfWeek'
            ]
            )
            ->setParameters(
                [
                    'id' => $userId,
                    'start' => $data['start'],
                    'end' => $data['end'],
                    'dayOfWeek' => $data['dayOfWeek']
                ]
            )
            ->execute();
    }
}
