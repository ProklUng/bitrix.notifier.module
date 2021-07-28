<?php

namespace Proklung\Notifier\Bitrix;

use Bitrix\Main\ArgumentException;
use Bitrix\Main\Mail\Internal\EventTable;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\SystemException;
use Exception;
use Symfony\Component\Notifier\Notification\Notification;
use Symfony\Component\Notifier\NotifierInterface;
use Symfony\Component\Notifier\Recipient\Recipient;

/**
 * Class BitrixMailEventSender
 * @package Proklung\Notifier\Bitrix
 *
 * @since 28.07.2021
 */
class BitrixMailEventSender
{
    /**
     * @var EventBridge $eventBridge Обработка битриксовых данных события.
     */
    private $eventBridge;

    /**
     * @var NotifierInterface $notifier Notifier.
     */
    private $notifier;

    /**
     * BitrixMailEventSender constructor.
     *
     * @param EventBridge       $eventBridge Обработка битриксовых данных события.
     * @param NotifierInterface $notifier    Notifier.
     */
    public function __construct(EventBridge $eventBridge, NotifierInterface $notifier)
    {
        $this->eventBridge = $eventBridge;
        $this->notifier = $notifier;
    }

    /**
     * Статический фасад.
     *
     * @param NotifierInterface $notifier Notifier.
     *
     * @return static
     */
    public static function getInstance(NotifierInterface $notifier) : self
    {
        return new static(new EventBridge(), $notifier);
    }

    /**
     * Отправить сообщение.
     *
     * @param string      $codeEvent  Код события.
     * @param array       $arFields   Параметры события.
     * @param string|null $importance Важность сообщения (в понимании Notifier).
     *
     * @return void
     * @throws ArgumentException | ObjectPropertyException | SystemException Битриксовые ошибки.
     */
    public function send(string $codeEvent, array $arFields, ?string $importance = null) : void
    {
        $eventsInfo = $this->eventBridge->getMessageTemplate($codeEvent);
        foreach ($eventsInfo as $eventInfo) {
            $compileData = $this->eventBridge->compileMessage($eventInfo, $arFields, ['s1']);

            if ($importance !== null) {
                $notification = (new Notification($compileData['subject']))
                    ->content($compileData['body'])
                    ->importance($importance);
            } else {
                $notification = (new Notification($compileData['subject'], ['email']))
                    ->content($compileData['body']);
            }

            $recipient = new Recipient($compileData['mail_to']);

            $this->notifier->send($notification, $recipient);

            // Эмуляция поведения Битрикса при обработке событий.
            try {
                EventTable::add(
                    [
                        'EVENT_NAME' => $eventInfo->getEventCode(),
                        'SUCCESS_EXEC' => 'Y',
                        'MESSAGE_ID' => 99999, // Признак, что отправлено через Notifier
                        'DUPLICATE' => 'N',
                        'LID' => SITE_ID,
                        'LANGUAGE_ID' => LANGUAGE_ID,
                        'DATE_INSERT' => new \Bitrix\Main\Type\DateTime,
                        'DATE_EXEC' => new \Bitrix\Main\Type\DateTime,
                        'C_FIELDS' => $eventInfo->getMessageData(),
                    ]
                );
            } catch (Exception $e) {
                // Silence. Не самый важный момент.
            }
        }
    }
}
