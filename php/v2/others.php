<?php

namespace NW\WebService\References\Operations\Notification;

/**
 * @property Seller $Seller
 */
class Contractor
{
    const TYPE_CUSTOMER = 0;
    public $id;
    public $type;
    public $name;

    public static function getById(int $resellerId): ?self
    {
        return new self($resellerId); // fakes the getById method
    }

    public function getFullName(): string
    {
        return $this->name . ' ' . $this->id;
    }
}

class Seller extends Contractor
{
}

class Employee extends Contractor
{
}

class Status
{
    public $id, $name;

    private  static $list = [
        1 => 'Completed',
        2 => 'Pending',
        3 => 'Rejected',
    ];

    public static function getName(?int $id): string
    {
        if (!$id || !in_array($id, array_keys(self::$list))) {
            throw new \DomainException('Invalid status');
        }

        return self::$list[$id];
    }
}

abstract class ReferencesOperation
{
    abstract public function doOperation(): array;

    public function getRequest($pName): array
    {
        // Лучше использовать GET или POST (не знаю какой у вас эндпоинт) REQUEST не желательно
        return $_REQUEST[$pName] ?? [];
    }
}

function getResellerEmailFrom(): ?string
{
    return 'contractor@example.com';
}

function getEmailsByPermit($resellerId, $event): array
{
    // fakes the method
    return ['someemeil@example.com', 'someemeil2@example.com'];
}

class NotificationEvents
{
    const CHANGE_RETURN_STATUS = 'changeReturnStatus';
    const NEW_RETURN_STATUS    = 'newReturnStatus';
}

class NotificationManager
{
    public static function send(
        $resellerId,
        $clientId,
        $event,
        $notificationSubEvent,
        $templateData,
        &$errorText,
        $locale = null
    ): bool {
        // fakes the method
        return true;
    }
}

class MessagesClient
{
    static function sendMessage(
        $sendMessages,
        $resellerId = 0,
        $customerId = 0,
        $notificationEvent = 0,
        $notificationSubEvent = ''
    ): string {
        return '';
    }
}

class DataMapper
{
    /**
     * @var array
     */
    private $data;

    /**
     * @var null|int
     */
    private $resellerId;
    /**
     * @var int|null
     */
    private $notificationType;
    /**
     * @var int|null
     */
    private $clientId;
    /**
     * @var int|null
     */
    private $creatorId;
    /**
     * @var int|null
     */
    private $expertId;
    /**
     * @var int|null
     */
    private $differencesFrom;
    /**
     * @var int|null
     */
    private $differencesTo;
    /**
     * @var int|null
     */
    private $complaintId;
    /**
     * @var string
     */
    private $complaintNumber;
    /**
     * @var int|null
     */
    private $consumptionId;
    /**
     * @var string
     */
    private $consumptionNumber;
    /**
     * @var string
     */
    private $agreementNumber;
    /**
     * @var string
     */
    private $date;

    public function __construct(array $data)
    {
        $this->data = $data;
        $this->resellerId = $this->getInt('resellerId');
        $this->notificationType = $this->getInt('notificationType');
        $this->clientId = $this->getInt('clientId');
        $this->creatorId = $this->getInt('creatorId');
        $this->expertId = $this->getInt('expertId');
        $differences = $this->data['differences'] ?? [];
        $this->differencesFrom = $this->getInt('from', $differences);
        $this->differencesTo = $this->getInt('to', $differences);
        $this->complaintId = $this->getInt('complaintId');
        $this->complaintNumber = $this->getString('complaintNumber');
        $this->consumptionId = $this->getInt('consumptionId');
        $this->consumptionNumber = $this->getString('consumptionNumber');
        $this->agreementNumber = $this->getString('agreementNumber');
        $this->date = $this->getString('date');
    }

    public function getResellerId(): ?int
    {
        return $this->resellerId;
    }

    public function getNotificationType(): ?int
    {
        return $this->notificationType;
    }

    public function getClientId(): ?int
    {
        return $this->clientId;
    }

    public function getCreatorId(): ?int
    {
        return $this->creatorId;
    }

    public function getExpertId(): ?int
    {
        return $this->expertId;
    }

    public function getDifferencesFrom(): ?int
    {
        return $this->differencesFrom;
    }

    public function getDifferencesTo(): ?int
    {
        return $this->differencesTo;
    }

    public function isDifferencesFilled(): bool
    {
        return $this->differencesFrom && $this->differencesTo;
    }

    public function getComplaintId(): ?int
    {
        return $this->complaintId;
    }

    public function getComplaintNumber(): string
    {
        return $this->complaintNumber;
    }

    public function getConsumptionId(): ?int
    {
        return $this->consumptionId;
    }

    public function getConsumptionNumber(): string
    {
        return $this->consumptionNumber;
    }

    public function getAgreementNumber(): string
    {
        return $this->agreementNumber;
    }

    public function getDate(): string
    {
        return $this->date;
    }

    private function getInt(string $key, ?array $data = null): ?int
    {
        $data = $data === null ? $this->data : $data;
        $value = $data[$key] ?? null;

        return filter_var($value, FILTER_VALIDATE_INT) === false ? null : (int) $value;
    }

    private function getString(string $key): string
    {
        return $this->data[$key] ?? '';
    }
}
