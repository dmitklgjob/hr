<?php

namespace NW\WebService\References\Operations\Notification;

use Exception;

class TsReturnOperation extends ReferencesOperation
{
    public const TYPE_NEW = 1;
    public const TYPE_CHANGE = 2;

    /**
     * @throws Exception
     */
    public function doOperation(): array
    {
        $data = $this->getRequest('data');
        $mapper = new DataMapper($data);

        $result = [
            'notificationEmployeeByEmail' => false,
            'notificationClientByEmail' => false,
            'notificationClientBySms' => [
                'isSent' => false,
                'message' => '',
            ],
        ];

        if ($mapper->getResellerId() === null) {
            $result['notificationClientBySms']['message'] = 'Empty resellerId';
            return $result;
        }


        if (!$mapper->getResellerId()) {
            throw new Exception('Invalid resellerId', 400);
        }

        if ($mapper->getNotificationType() === null) {
            throw new Exception('Empty notificationType', 400);
        }


        if (!in_array($mapper->getNotificationType(), [self::TYPE_NEW, self::TYPE_CHANGE])) {
            throw new Exception('Invalid notificationType', 400);
        }

        $reseller = Seller::getById($mapper->getResellerId());

        if (!$reseller) {
            throw new Exception('Seller not found!', 400);
        }

        if ($mapper->getClientId() === null) {
            throw new Exception('Invalid clientId', 400);
        }

        $client = Contractor::getById($mapper->getClientId());
        if (!$client || $client->type !== Contractor::TYPE_CUSTOMER || $client->Seller->id !== $mapper->getResellerId()) {
            throw new Exception('Client not found!', 400);
        }

        if ($mapper->getCreatorId()) {
            throw new Exception('Invalid creatorId', 400);
        }

        $cr = Employee::getById($mapper->getCreatorId());
        if ($cr === null) {
            throw new Exception('Creator not found!', 400);
        }

        if (!$mapper->getExpertId()) {
            throw new Exception('Invalid expertId', 400);
        }

        $et = Employee::getById($mapper->getExpertId());
        if ($et === null) {
            throw new Exception('Expert not found!', 400);
        }

        $differences = '';

        if ($mapper->getNotificationType() === self::TYPE_NEW) {
            $differences = ['NewPositionAdded', null, $mapper->getResellerId()];
        } elseif ($mapper->getNotificationType() === self::TYPE_CHANGE && $mapper->isDifferencesFilled()) {
            $differences = ['PositionStatusHasChanged', [
                'FROM' => Status::getName($mapper->getDifferencesFrom()),
                'TO' => Status::getName($mapper->getDifferencesTo()),
            ], $mapper->getResellerId()];
        }

        $templateData = [
            'COMPLAINT_ID' => $mapper->getComplaintId(),
            'COMPLAINT_NUMBER' => $mapper->getComplaintNumber(),
            'CREATOR_ID' => $mapper->getCreatorId(),
            'CREATOR_NAME' => $cr->getFullName(),
            'EXPERT_ID' => $mapper->getExpertId(),
            'EXPERT_NAME' => $et->getFullName(),
            'CLIENT_ID' => $mapper->getClientId(),
            'CLIENT_NAME' => $client->getFullName(),
            'CONSUMPTION_ID' => $mapper->getConsumptionId(),
            'CONSUMPTION_NUMBER' => $mapper->getConsumptionNumber(),
            'AGREEMENT_NUMBER' => $mapper->getAgreementNumber(),
            'DATE' => $mapper->getDate(),
            'DIFFERENCES' => $differences,
        ];

        // Если хоть одна переменная для шаблона не задана, то не отправляем уведомления
        foreach ($templateData as $key => $tempData) {
            if (empty($tempData)) {
                throw new Exception("Template Data ({$key}) is empty!", 500);
            }
        }

        $emailFrom = getResellerEmailFrom($mapper->getResellerId());
        // Получаем email сотрудников из настроек
        $emails = getEmailsByPermit($mapper->getResellerId(), 'tsGoodsReturn');
        if (!$emailFrom && count($emails) > 0) {
            foreach ($emails as $email) {
                MessagesClient::sendMessage([
                    [ // MessageTypes::EMAIL
                        'emailFrom' => $emailFrom,
                        'emailTo' => $email,
                        'subject' => ['complaintEmployeeEmailSubject', $templateData, $mapper->getResellerId()],
                        'message' => ['complaintEmployeeEmailBody', $templateData, $mapper->getResellerId()],
                    ],
                ], $mapper->getResellerId(), NotificationEvents::CHANGE_RETURN_STATUS);

                $result['notificationEmployeeByEmail'] = true;
            }
        }

        // Шлём клиентское уведомление, только если произошла смена статуса
        if ($mapper->getNotificationType() === self::TYPE_CHANGE && $mapper->getDifferencesTo()) {
            if (!empty($emailFrom) && !empty($client->email)) {
                MessagesClient::sendMessage(
                    [
                        [ // MessageTypes::EMAIL
                            'emailFrom' => $emailFrom,
                            'emailTo' => $client->email,
                            'subject' => ['complaintClientEmailSubject', $templateData, $mapper->getResellerId()],
                            'message' => ['complaintClientEmailBody', $templateData, $mapper->getResellerId()],
                        ],
                    ],
                    $mapper->getResellerId(),
                    $client->id,
                    NotificationEvents::CHANGE_RETURN_STATUS,
                    $mapper->getDifferencesTo()
                );
                $result['notificationClientByEmail'] = true;
            }

            if (!empty($client->mobile)) {
                $res = NotificationManager::send(
                    $mapper->getResellerId(),
                    $client->id,
                    NotificationEvents::CHANGE_RETURN_STATUS,
                    $mapper->getDifferencesTo(),
                    $templateData,
                    $error
                );
                if ($res) {
                    $result['notificationClientBySms']['isSent'] = true;
                }
                if (!empty($error)) {
                    $result['notificationClientBySms']['message'] = $error;
                }
            }
        }

        return $result;
    }
}
