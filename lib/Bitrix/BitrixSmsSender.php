<?php

namespace Proklung\Notifier\Bitrix;

use Bitrix\Main\ArgumentException;
use Bitrix\Main\Mail\Internal\EventTable;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\SystemException;
use Bitrix\Main\Type\DateTime;
use Exception;
use RuntimeException;
use Symfony\Component\Notifier\Exception\TransportExceptionInterface;
use Symfony\Component\Notifier\Message\SmsMessage;
use Symfony\Component\Notifier\TexterInterface;

/**
 * Class BitrixSmsSender
 * @package Proklung\Notifier\Bitrix
 *
 * @since 28.07.2021
 */
class BitrixSmsSender
{
    /**
     * @var EventBridgeSms $eventBridge Обработка битриксовых данных события SMS.
     */
    private $eventBridge;

    /**
     * @var TexterInterface $notifier Notifier.
     */
    private $notifier;

    /**
     * BitrixSmsSender constructor.
     *
     * @param EventBridgeSms  $eventBridge Обработка битриксовых данных события.
     * @param TexterInterface $notifier    Notifier.
     */
    public function __construct(EventBridgeSms $eventBridge, TexterInterface $notifier)
    {
        $this->eventBridge = $eventBridge;
        $this->notifier = $notifier;
    }

    /**
     * Статический фасад.
     *
     * @param TexterInterface $notifier Notifier.
     *
     * @return static
     */
    public static function getInstance(TexterInterface $notifier): self
    {
        return new static(new EventBridgeSms(), $notifier);
    }

    /**
     * Отправить сообщение.
     *
     * @param string $codeEvent Код события.
     * @param array  $arFields  Параметры события.
     *
     * @return void
     * @throws ArgumentException | ObjectPropertyException | SystemException Битриксовые ошибки.
     * @throws TransportExceptionInterface                                   Ошибки транспорта SMS.
     */
    public function send(string $codeEvent, array $arFields): void
    {
        $template = $this->eventBridge->fetchTemplates($codeEvent, 's1');
        if ($template === null) {
            throw new RuntimeException('Template SMS object cannot be NULL');
        }

        $message = $this->eventBridge->compileMessage($template, $arFields);

        $sms = new SmsMessage(
            $message->getReceiver(),
            $message->getText()
        );

        $success = 'Y';
        $errorMessage = '';
        try {
            $sentMessage = $this->notifier->send($sms);
        } catch (Exception $e) {
            $success = 'N';
            $errorMessage = $e->getMessage();
        }

        // Эмуляция поведения Битрикса при обработке событий.
        try {
            EventTable::add(
                [
                    'EVENT_NAME' => $codeEvent,
                    'SUCCESS_EXEC' => $success,
                    'MESSAGE_ID' => 99999, // Признак, что отправлено через Notifier
                    'DUPLICATE' => 'N',
                    'LID' => SITE_ID,
                    'LANGUAGE_ID' => LANGUAGE_ID,
                    'DATE_INSERT' => new DateTime,
                    'DATE_EXEC' => new DateTime,
                    'C_FIELDS' => $success === 'N' ? ['error' => $errorMessage] : ['data' => $sentMessage->getMessageId()],
                ]
            );
        } catch (Exception $e) {
            // Silence. Не самый важный момент.
        }
    }
}