<?php

namespace App\Controller;

use App\Repositories;
use App\Services;
use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;

class APIController
{
    const CREATE_REQUEST__BANK_ID_NOT_INT = 'Идентификатор банка должен быть целым числом';
    const CREATE_REQUEST__BANK_NOT_FOUND = 'Данного пользователя не существует';
    const CREATE_REQUEST__USER_ROLE_IS_NOT_A_BANK = 'Пользователь не является банком';
    const CREATE_REQUEST__RATER_ID_NOT_INT = 'Идентификатор оценщика должен быть целым числом';
    const CREATE_REQUEST__RATER_NOT_FOUND = 'Данного оценщика не существует';
    const CREATE_REQUEST__RATER_ROLE_IS_NOT_A_BANK = 'Оценщик не является банком';
    const CREATE_REQUEST__RATER_IS_NOT_ACTIVE = 'Выбран удаленный оценщик';
    const CREATE_REQUEST__RATER_NOT_ACCREDITED = 'Выбран не аккредитованый оценщик';
    const CREATE_REQUEST__RATER_IS_NOT_ACCESS = 'Выбран не подтвержденный оценщик';
    const CREATE_REQUEST__CONTACT_ID_NOT_INT = 'Идентификатор контакта должен быть целым числом';
    const CREATE_REQUEST__CONTACT_NOT_FOUND = 'Данного контакта не существует';
    const CREATE_REQUEST__CONTACT_ROLE_IS_NOT_A_RATER = 'Контакт не является банком';
    const CREATE_REQUEST__CONTACT_IS_NOT_ACTIVE = 'Выбран удаленный контакт';
    const CREATE_REQUEST__OBJECT_TYPE_ID_NOT_INT = 'Тип объекта должен быть числом';
    const CREATE_REQUEST__OBJECT_TYPE_NOT_FOUND = 'Тип объекта не должены быть пустым';
    const CREATE_REQUEST__DISTANCE_SUB_ZERO = 'Дистанция не может быть меньше нуля';
    const CREATE_REQUEST__ADDRESS_IS_NOT_CORRECT = 'Не корректный аддресс';
    const CREATE_REQUEST__COORDINATES_IS_NOT_CORRECT = 'Не корректные координаты';
    const CREATE_REQUEST__CUSTOMER_NAME_IS_NOT_CORRECT = 'Имя пользователя не корректное';
    const CREATE_REQUEST__CUSTOMER_EMAIL_IS_NOT_VALID = 'Email пользователя не валидный';
    //const CREATE_REQUEST__CUSTOMER_PHONE_IS_NOT_INT = 'Телефон пользователя не корректный';
    const CREATE_REQUEST__FAILED = 'Ошибка при создании заявки';

    private $dataJSON;
    private $dayMin;
    private $dayMax;
    private $userId;
    private $weekDaysNames;

    public function getSearchFormData(Application $app) {
        /** @var $frp Services\FrequentRequestProtect */
        $frp = $app['frequent_request_protector'];

        $frp->checkAccess(__FUNCTION__);

        /** @var $bankRepository Repositories\BankRepository */
        /** @var $objectTypeRepository Repositories\ObjectTypeRepository */
        $bankRepository = $app['bank.repository'];
        $objectTypeRepository = $app['object_type.repository'];

        return $app->json([
                'banks' => $bankRepository->getAllActive(),
                'objectTypes' => $objectTypeRepository->getAll()
            ]
        );
    }

    public function getRaters(Application $app, Request $request) {
        /** @var $frp Services\FrequentRequestProtect */
        $frp = $app['frequent_request_protector'];

        $frp->checkAccess(__FUNCTION__);

        /** @var $raterRepository Repositories\RaterRepository */
        /** @var $requestDateCalculator Services\RequestDateCalculator */
        $raterRepository = $app['rater.repository'];
        $requestDateCalculator = $app['request_date_calculator'];

        $deletedRaters = [];
        // make date created up to 1 hour from now (while user choose rater on site)
        $created = time() + $requestDateCalculator::HOUR_LENGTH;

        $raters = $raterRepository->getAllByBankId((int)$request->get('bankId'));

        foreach ($raters['raters'] as $raterIdx => $raterItem) {
            // delete raters with no work days or prices or contacts
            if (empty($raterItem['contacts']) || empty($raterItem['pricesReport']) || empty($raterItem['workTime'])) {
                $deletedRaters[] = $raterItem['id'];

                unset($raters['raters'][$raterIdx]);
            } else {
                foreach ($raterItem['pricesReport'] as $reportIdx => $reportItem) {
                    $date = $requestDateCalculator->calculateReadyTimestamp(
                        $created,
                        $reportItem['time'] * $requestDateCalculator::HOUR_LENGTH,
                        $raterItem['workTime']
                    );
                    $raters['raters'][$raterIdx]['pricesReport'][$reportIdx]['date'] = $date;
                    $raters['raters'][$raterIdx]['pricesReport'][$reportIdx]['dateFormat'] = date('d.m.Y H:i', $date);
                }

                // hide some fields for customer
                unset(
                    $raters['raters'][$raterIdx]['email'],
                    $raters['raters'][$raterIdx]['comment'],
                    $raters['raters'][$raterIdx]['workTime']
                );
            }
        }

        // delete contacts for deleted raters
        foreach ($raters['contacts'] as $contactIdx => $contactItem) {
            if (in_array($contactItem['raterId'], $deletedRaters, true)) {
                unset($raters['contacts'][$contactIdx]);
            } else {
                // hide some fields for customer
                unset($raters['contacts'][$contactIdx]['phone'],
                    $raters['contacts'][$contactIdx]['fax'],
                    $raters['contacts'][$contactIdx]['address']
                );
            }
        }

        return $app->json($raters);
    }

    public function createRequest(Application $app, Request $request) {
        /** @var $userRepository Repositories\UserRepository */
        /** @var $raterRepository Repositories\RaterRepository */
        /** @var $contactRepository Repositories\ContactRepository */
        /** @var $bankRepository Repositories\BankRepository */
        /** @var $workTimeRepository Repositories\WorkTimeRepository */
        /** @var $requestDateCalculator Services\RequestDateCalculator */
        /** @var $notifier Services\Notifier */
        /** @var $request Request */
        $userRepository = $app['user.repository'];
        $raterRepository = $app['rater.repository'];
        $contactRepository = $app['contact.repository'];
        $bankRepository = $app['bank.repository'];
        $workTimeRepository = $app['work_time.repository'];
        $requestDateCalculator = $app['request_date_calculator'];
        $notifier = $app['notifier'];

        // request rate
        $bankId = (int)$request->get('bankId');
        $raterId = (int)$request->get('raterId');
        $raterContactId = (int)$request->get('contactId');
        $objectTypeId = (int)$request->get('objectTypeId');
        $distance = (float)$request->get('distance');

        // customer contact
        $address = trim((string)$request->get('address'));
        $coordinates = trim((string)$request->get('coordinates'));

        // person associated with customer contact
        $customerName = trim((string)$request->get('name'));
        $customerEmail = preg_replace('/\s+/', '', (string)$request->get('email'));
        $customerPhone = trim((string)$request->get('phone'));

        //    I. Проверка пришедших данных (RequestDataVerify)

        if(!is_int($bankId)){
            $error[] = $this::CREATE_REQUEST__BANK_ID_NOT_INT;
        }

        $raterInfo = $userRepository->getUserById($bankId);

        if(empty($raterInfo)) {
            $error[] = $this::CREATE_REQUEST__BANK_NOT_FOUND;
        }

        if('ROLE_BANK' !== $raterInfo['roles']) {
            $error[] = $this::CREATE_REQUEST__USER_ROLE_IS_NOT_A_BANK;
        }

        if(!is_int($raterId)){
            $error[] = $this::CREATE_REQUEST__RATER_ID_NOT_INT;
        }

        $raterInfo = $userRepository->getUserById($raterId);

        if(empty($raterInfo)) {
            $error[] = $this::CREATE_REQUEST__RATER_NOT_FOUND;
        }

        if('ROLE_RATER' !== $raterInfo['roles']) {
            $error[] = $this::CREATE_REQUEST__RATER_ROLE_IS_NOT_A_BANK;
        }

        if(1 !== (int)$raterInfo['access']) {
            $error[] = $this::CREATE_REQUEST__RATER_IS_NOT_ACTIVE;
        }

        if(!$raterRepository->getRaterAccreditationByBankId($raterId, $bankId)) {
            $error[] = $this::CREATE_REQUEST__RATER_NOT_ACCREDITED;
        }

        if(1 !== $raterInfo['access']) {
            $error[] = $this::CREATE_REQUEST__RATER_IS_NOT_ACCESS;
        }

        if(!is_int($raterContactId)) {
            $error[] = $this::CREATE_REQUEST__CONTACT_ID_NOT_INT;
        }

        $contactInfo = $contactRepository->getContactRaterById($raterContactId);

        if(empty($contactInfo)) {
            $error[] = $this::CREATE_REQUEST__CONTACT_NOT_FOUND;
        }

        if('ROLE_RATER' !== $contactInfo['roles']) {
            $error[] = $this::CREATE_REQUEST__CONTACT_ROLE_IS_NOT_A_RATER;
        }

        if(1 === $contactInfo['active']) {
            $error[] = $this::CREATE_REQUEST__CONTACT_IS_NOT_ACTIVE;
        }

        if(!is_int($objectTypeId)) {
            $error[] = $this::CREATE_REQUEST__OBJECT_TYPE_ID_NOT_INT;
        }

        $objectType = $contactRepository->getObjectTypeById($objectTypeId);

        if(empty($objectType)) {
            $error[] = $this::CREATE_REQUEST__OBJECT_TYPE_NOT_FOUND;
        }

        if((float)$distance < 0) {
            $error[] = $this::CREATE_REQUEST__DISTANCE_SUB_ZERO;
        }

        if(empty($address) || !is_string($address)) {
            $error[] = $this::CREATE_REQUEST__ADDRESS_IS_NOT_CORRECT;
        }

        if(empty($coordinates) || !is_string($coordinates)) {
            $error[] = $this::CREATE_REQUEST__COORDINATES_IS_NOT_CORRECT;
        }

        if(empty($customerName) || !is_string($customerName)) {
            $error[] = $this::CREATE_REQUEST__CUSTOMER_NAME_IS_NOT_CORRECT;
        }

        //\w+(\.\w+)*@\D((?:[\w]+\.)+)([a-zA-Z]{2,4})
        if(!filter_var($customerEmail, FILTER_VALIDATE_EMAIL)) {
            $error[] = $this::CREATE_REQUEST__CUSTOMER_EMAIL_IS_NOT_VALID;
        }

        if(!empty($error)) {
            return $app->json([$status = 400, $error]);
        }

        //II. Создание заявки об оценке

        //    1. Выбираем пользователя по текущему email
        $userInfo = $userRepository->getUserByEmail($customerEmail, true);

        //    2. если пользовательский email подавший заявку не существует, то (2.1)
        //        2.1 добавляем нового пользователя в базу данных
        //        2.2 Выбираем пользователя по текущему email
        $idNewCreateUser = 0;

        if(empty($userInfo)){
            $options = [
                'email' => $customerEmail,
                'password' => md5(__LINE__ . __FILE__ . md5('Th4538yTprPeFb34')),
                'name' => $customerName,
                'comment' => null,
                'requisites' => null,
                'roles' => 'ROLE_CUSTOMER',
                'created' => time(),
                'access' => 1, // После подтверждения email - 1 ??
                'active' => 1
            ];
            $idNewCreateUser = $userRepository->createUser($options);
            $userInfo = $userRepository->getUserByEmail($customerEmail);
            unset($options);
        }

        // 3. если заказчик существует то (3.1)
        $idNewCreateRequest = $idNewCreateContact = $idNewCreatePerson = 0;
        if(!empty($userInfo)) {
            // 3.1 Создаем контакт для заказчика
            $options = [
                'user_id' => $idNewCreateUser,
                'phone' => $customerPhone,
                'fax' => null,
                'address' => $address,
                'coordinates' => $coordinates,
                'active' => 1
            ];
            $idNewCreateContact = $contactRepository->createContact($options);
            unset($options);
        }

        //    4. Выбираем созданный контакт
        $contactInfo = $contactRepository->getContactById($idNewCreateContact);

        //    5. Если контакт существует то (5.1)
        if(!empty($contactInfo)) {
            //        5.1 Создаем персону
            $options = [
                'contact_id' => $idNewCreateContact,
                'name' => $customerName,
                'phone' => $customerPhone,
                'email' => $customerEmail,
                'skype' => '',
                'active' => 1
            ];
            $idNewCreatePerson = $contactRepository->createPerson($options);
            unset($options);

            $pricesReport = $raterRepository->getPricesReportByRaterIdAndObjectTypeId($raterId, $objectTypeId);
            $pricesDistance = $raterRepository->getPricesDistanceByRaterId($raterId);
            $finishDate = $requestDateCalculator->calculateReadyTimestamp(
                $created = time(),
                $pricesReport['price'],
                $workTimeRepository->getByUserId($raterId)
            );

            //        5.2 Создаем заявку
            $options = [
                'customer_id' => $idNewCreateUser,
                'customer_contact_id' => $idNewCreateContact,
                'rater_id' => $raterId,
                'rater_contact_id' => $raterContactId,
                'object_type_id' => $objectTypeId,
                'title' => '',
                'cost_distance' => $pricesDistance['price'],
                'cost_report' => $pricesReport['price'],
                'status' => 'PREPROCESSED',
                'created' => $created,
                'updated' => time(),
                'finish_date' => $finishDate
            ];
            $idNewCreateRequest = $raterRepository->createRequest($options);
            unset($options);
        }

        //    6. если заявка не создана, то (6.1)
        if(!is_int($idNewCreateRequest) && $idNewCreateRequest == 0) {
            //        6.1 сгенерировать ошибку
            $error[] = $this::CREATE_REQUEST__FAILED;

            //        6.3 переход к (IV)
            return $app->json($error);
        }

        //    7. если выше есть ошибки то переходим IV
        //III. Отправка сообщений о новой заявке на email оценщику и пользователю
/*        if(empty($error)) {
            $priceReport = $raterRepository->getPricesReportByRaterIdAndObjectTypeId($raterId, $objectTypeId);
            $raterWorkTime = $workTimeRepository->getByUserId($raterId);
            $response['reportDate'] = $requestDateCalculator->calculateReadyTimestamp(
                time(),
                $priceReport['time'] * $requestDateCalculator::HOUR_LENGTH,
                $raterWorkTime
            );
            $bankInfo = $bankRepository->getBankById($bankId);
            $subject = 'Новая заявка на сайте poipoteke.com';
            $from = 'services@poipoteke.com';

            $notifier->notifyCustomerNewRateRequest(
                $subject,
                $from,
                $customerEmail,
                [
                    'rater' => $raterInfo['name'],
                    'bank' => $bankInfo['name'],
                    'reportDay' => $response['reportDate'],
                    'priceReport' =>$priceReport['price'],
                    'address' => $address
                ]
            );

            $notifier->notifyRaterNewRateRequest(
                $subject,
                $from,
                $raterInfo['email'],
                [
                    'address' => $address,
                    'bank' => $bankInfo['name'],
                    'report' => [
                        'time' => $priceReport['time'],
                        'price' =>$priceReport['price']
                    ],
                    'customer' => [
                        'name' => $customerName,
                        'email' => $customerEmail,
                        'phone' => $customerPhone
                    ]
                ]
            );

            $status = 200;
        }*/

        //    1. Отсылаем на email оценщику сообщение о новой заявке

        //    2. если сообщение не отправлено, то (2.1)
        //        2.1 записать в логи

        //    3. Отсылаем на email пользователю сообщение, что заявка успешно создана

        //    4. если сообщение не отправлено, то (4.1)
        //        4.1 записать в логи
        //IV. Вернуть ответ пользователю
        $status = 200;
        return $app->json($status || $error);
    }

    public function getRaterInfo(Application $app) {
        $info = [];
        $banks = [];

        /** @var $userRepository Repositories\UserRepository */
        /** @var $raterRepository Repositories\RaterRepository */
        /** @var $tokenStorage TokenStorage */
        $userRepository = $app['user.repository'];
        $raterRepository = $app['rater.repository'];
        $tokenStorage = $app['security.token_storage'];

        $token = $tokenStorage->getToken();

        if (null !== $token) {
            $username = $token->getUser()->getUsername();
            $info = $userRepository->getUserByEmail($username);
            $banks = $raterRepository->getBanksWithAccreditation($username);
        }

        return $app->json(['info' => $info, 'banks' => $banks]);
    }

    public function getRaterContacts(Application $app) {
        $contacts = [];

        /** @var $userRepository Repositories\UserRepository */
        /** @var $contactRepository Repositories\ContactRepository */
        /** @var $tokenStorage TokenStorage */
        $userRepository = $app['user.repository'];
        $contactRepository = $app['contact.repository'];
        $tokenStorage = $app['security.token_storage'];

        $token = $tokenStorage->getToken();

        if (null !== $token) {
            $username = $token->getUser()->getUsername();
            $userId = $userRepository->getIdByEmail($username);
            $contacts = $contactRepository->getAllByUserId($userId);
        }

        return $app->json(['contacts' => $contacts]);
    }

    public function getRaterPrices(Application $app) {
        $pricePerMeter = [];
        $pricesReport = [];

        /** @var $userRepository Repositories\UserRepository */
        /** @var $raterRepository Repositories\RaterRepository */
        /** @var $tokenStorage TokenStorage */
        $userRepository = $app['user.repository'];
        $raterRepository = $app['rater.repository'];
        $tokenStorage = $app['security.token_storage'];

        $token = $tokenStorage->getToken();

        if (null !== $token) {
            $username = $token->getUser()->getUsername();
            $userId = $userRepository->getIdByEmail($username);
            $pricePerMeter = $raterRepository->getPricePerMeter($userId);
            $pricesReport = $raterRepository->getPricesReport($userId);
        }

        return $app->json(['pricePerMeter' => $pricePerMeter, 'report' => $pricesReport]);
    }

    public function getRaterWorkTime(Application $app) {
        $workTime = [];

        /** @var $userRepository Repositories\UserRepository */
        /** @var $workTimeRepository Repositories\WorkTimeRepository */
        /** @var $tokenStorage TokenStorage */
        $userRepository = $app['user.repository'];
        $workTimeRepository = $app['work_time.repository'];
        $tokenStorage = $app['security.token_storage'];

        $token = $tokenStorage->getToken();

        if (null !== $token) {
            $username = $token->getUser()->getUsername();
            $userId = $userRepository->getIdByEmail($username);
            $workTime = $workTimeRepository->getByUserId($userId);
        }

        return $app->json(['workTime' => $workTime]);
    }

    public function getRaterRequests(Application $app, Request $request) {
        $raterRequests = [];
        $page = (int)$request->get('page');

        /** @var $userRepository Repositories\UserRepository */
        /** @var $raterRepository Repositories\RaterRepository */
        /** @var $tokenStorage TokenStorage */
        $userRepository = $app['user.repository'];
        $raterRepository = $app['rater.repository'];
        $tokenStorage = $app['security.token_storage'];

        $token = $tokenStorage->getToken();

        if (null !== $token) {
            $username = $token->getUser()->getUsername();
            $userId = $userRepository->getIdByEmail($username);
            $raterRequests = $raterRepository->getRequests($userId, $page);
        }

        return $app->json(['requests' => $raterRequests]);
    }

    public function updateRaterInfo(Application $app) {
        /** @var $raterInfo Repositories\RaterRepository */
        $raterInfo = $app['rater.repository'];
        $data = $this->dataJSON['updateRaterInfo'];
        $this->constr($app);

        if (!empty($data['update'])) {
            $raterInfo->updateInfo($data, $this->userId);
        }

        if (!empty($data['insert'])) {
            foreach ($data['insert'] as $value) {
                $raterInfo->insertInfo($value, $this->userId);
            }
        }

        return $app->json([$raterInfo]);
    }

    public function updateRaterContacts(Application $app) {
        /** @var $contactRepositories Repositories\WorkTimeRepository */
        $contactRepositories = $app['contact.repository'];
        $data = $this->dataJSON['updateRaterWorkTime'];
        $this->constr($app);
        $result = [];

        if (!empty($data['delete'])) {
            $result['delete'] = $contactRepositories->delete($data, $this->userId);
        }

        if (!empty($data['update'])) {
            $result['update'] = $contactRepositories->update($data, $this->userId);
        }

        if (!empty($data['insert'])) {
            $result['insert'] = $contactRepositories->insert($data, $this->userId);
        }

        return $app->json($result);
    }

    public function updateRaterPrices(Application $app, Request $request) {
        $this->constr($app);

        if (array_key_exists('distancePrice', $this->dataJSON)) {
            /** @var $pricesDistance Repositories\RaterRepository */
            $pricesDistance = $app['rater.repository'];
            $data = $this->dataJSON['distancePrice'];

            if ($pricesDistance->selectPricesDistance($this->userId)) {
                if (!empty($data)) {
                    $result['pricesDistance'] = $pricesDistance->updatePricesDistance($data, $this->userId);
                }
            } else {
                if (!empty($data['insert'])) {
                    return $app->json($pricesDistance->insertPricesDistance($data['insert'], $this->userId));
                }
            }
        }

        if (array_key_exists('reportPrice', $this->dataJSON)) {
            /** @var $raterRepositories Repositories\RaterRepository */
            $raterRepositories = $app['rater.repository'];
            $data = $this->dataJSON['reportPrice'];
            $resultDB = $raterRepositories->selectPricesReport();

            if (!empty($data['delete'])) {
                $raterRepositories->deletePricesReport($data['delete'], $this->userId);
            }

            if (!empty($data['update'])) {
                foreach ($data['update'] as $updateKey => $updateValue) {
                    $objectTypeDB = $resultDB->fetchColumn();
                    $objectTypeId = (int)$updateValue['objectTypeId'];
                    $price = (float)$data['update'][(int)$updateKey]['price'];
                    $time = (int)$data['update'][(int)$updateKey]['time'];

                    if (!$objectTypeId or $objectTypeId !== $objectTypeDB) {
                        continue;
                    }

                    if ($this->userId && $price && $time && 0 !== ((int)$objectTypeId && $price && $time)) {
                        $raterRepositories->updatePricesReport($objectTypeId, $price, $time, $this->userId);
                    }
                }
            }

            if (!empty($data['insert'])) {
                foreach ($data['insert'] as $insertKey => $insertValue) {
                    $objectTypeId = (int)$insertValue['objectTypeId'];
                    $price = (float)$data['insert'][(int)$insertKey]['price'];
                    $time = (int)$data['insert'][(int)$insertKey]['time'];

                    while ($result = $resultDB->fetchColumn()) {
                        $objectTypeDB[] = $result;
                    }

                    if ($raterRepositories->selectObjectTypeId($this->userId, $request->get('objectTypeId'))) {
                        continue;
                    }

                    if (!in_array($objectTypeId, $objectTypeDB)) {
                        continue;
                    }

                    if ($this->userId && $price && $time) {
                        $raterRepositories->insertPricesReport($objectTypeId, $price, $time, $this->userId);
                    }
                }
            }
        }

        return $app->json($result);
    }

    public function updateRaterWorkTime(Application $app, Request $request) {
        /** @var $workTimeRepositories Repositories\WorkTimeRepository */
        $workTimeRepositories = $app['work_time.repository'];
        $data = $this->dataJSON['updateRaterWorkTime'];
        $this->constr($app);
        $result = [];

        if (!empty($data['delete'])) {
            $result['delete'] = $workTimeRepositories->delete($data);
        }

        if (!empty($data['update'])) {
            foreach ($data['update'] as $key => $value) {
                $paramKey = ':d' . $key;
                $parameters[$paramKey] = array_key_exists('dayOfWeek', $value) ? $value['dayOfWeek'] : 'none';
                $daysExpressionCondition[] = $paramKey;

                $result['update'][] = $workTimeRepositories->update($value, $parameters, $paramKey);
            }
        }

        if (!empty($data['insert'])) {
            foreach ($data['insert'] as $value) {
                $value['dayOfWeek'] = array_key_exists('dayOfWeek', $value)
                    ? mb_strtoupper(trim((string)$value['dayOfWeek']), 'utf-8')
                    : '';
                $value['start'] = array_key_exists('start', $value) ? (int)$value['start'] : $this->dayMin;
                $value['end'] = array_key_exists('end', $value) ? (int)$value['end'] : $this->dayMin;

                if (in_array($value['dayOfWeek'], $this->weekDaysNames)
                    && $value['end'] > $this->dayMin
                    && $value['end'] < $this->dayMax
                    && $value['end'] > $value['start']
                    && $value['start'] >= $this->dayMin
                ) {
                    $result['insert'][] = $workTimeRepositories->insert($value);
                }
            }
        }

        return $app->json([$result]);
    }

    public function updateRaterRequestStatus(Application $app, Request $request) {
        /** @var $userRepository Repositories\UserRepository */
        /** @var $tokenStorage TokenStorage */
        /** @var $raterRepository Repositories\RaterRepository */
        $userRepository = $app['user.repository'];
        $tokenStorage = $app['security.token_storage'];
        $raterRepository = $app['rater.repository'];
        $this->constr($app);

        $userId = 0;
        $token = $tokenStorage->getToken();

        if (null !== $token) {
            $username = $token->getUser()->getUsername();
            $userId = $userRepository->getIdByEmail($username);
        }

        $updatedRows = 0;
        $requestId = $request->get('id');
        $updateStatus = mb_strtoupper(trim($request->get('status')), 'utf-8');
        $requestStatus = $raterRepository->getRequestStatusById($requestId, $userId);
        $allStatuses = $raterRepository->getRequestAllStatuses();
        $availableStatuses = $raterRepository->getRequestAvailableUpdateStatuses();

        if (in_array($requestStatus, $availableStatuses) && in_array($updateStatus, $allStatuses)) {
            $updatedRows = $raterRepository->updateRequestStatus([
                'id' => $requestId,
                'rater_id' => $userId,
                'status' => $updateStatus
            ]);
        }

        return $app->json([$updatedRows]);
    }
}