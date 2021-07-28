<?php

namespace Proklung\Notifier\Bitrix;

/**
 * Class EventInfo
 * DTO с информацией о шаблоне битриксового события.
 * @package Proklung\Notifier\Bitrix
 *
 * @since 28.07.2021
 */
class EventInfo
{
    /**
     * @var integer
     */
    private $id;

    /**
     * @var string
     */
    private $eventCode;

    /**
     * @var string
     */
    private $active;

    /**
     * @var string
     */
    private $emailFrom;

    /**
     * @var string
     */
    private $emailTo;

    /**
     * @var string
     */
    private $subject;

    /**
     * @var string
     */
    private $message;

    /**
     * @var array $files
     */
    private $files;

    /**
     * @var array $arMessage Данные на события в битриксовом формате.
     */
    private $arMessage;

    /**
     * @var mixed
     */
    private $siteId;

    /**
     * EventInfo constructor.
     *
     * @param array $arMessage Данные на событие.
     */
    public function __construct(
        array $arMessage
    ) {
        $this->id = $arMessage['ID'];
        $this->eventCode = $arMessage['EVENT_NAME'] ?? '';
        $this->active = $arMessage['ACTIVE'] ?? '';
        $this->siteId = $arMessage['LID'] ?? '';
        $this->emailFrom = $arMessage['EMAIL_FROM'] ?? '';
        $this->emailTo = $arMessage['EMAIL_TO'] ?? '';
        $this->subject = $arMessage['SUBJECT'] ?? '';
        $this->message = $arMessage['MESSAGE'] ?? '';
        $this->files = $arMessage['FILES'] ?? [];
        $this->arMessage = $arMessage;
    }

    /**
     * Статический конструктор.
     *
     * @param array $bitrixArray Битриксовый массив на событие.
     *
     * @return static
     */
    public static function fromArray(array $bitrixArray) : self
    {
        return new static($bitrixArray);
    }

    /**
     * @return string
     */
    public function getEventCode(): string
    {
        return $this->eventCode;
    }

    /**
     * @return array
     */
    public function getMessageData(): array
    {
        return $this->arMessage;
    }

    /**
     * @return integer
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getActive(): string
    {
        return $this->active;
    }

    /**
     * @return string
     */
    public function getEmailFrom(): string
    {
        return $this->emailFrom;
    }

    /**
     * @return string
     */
    public function getEmailTo(): string
    {
        return $this->emailTo;
    }

    /**
     * @return string
     */
    public function getSubject(): string
    {
        return $this->subject;
    }

    /**
     * @return string
     */
    public function getMessage(): string
    {
        return $this->message;
    }

    /**
     * @return array
     */
    public function getFiles(): array
    {
        return $this->files;
    }
}