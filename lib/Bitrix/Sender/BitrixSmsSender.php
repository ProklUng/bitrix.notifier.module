<?php

namespace Proklung\Notifier\Bitrix\Sender;

use Bitrix\Main\ArgumentException;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\SystemException;
use Exception;
use Proklung\Notifier\Bitrix\EventBridgeSms;
use Proklung\Notifier\Bitrix\Utils\EventTableUpdater;
use RuntimeException;
use Symfony\Component\Notifier\Exception\TransportExceptionInterface;
use Symfony\Component\Notifier\Message\SmsMessage;
use Symfony\Component\Notifier\TexterInterface;

/**
 * Class BitrixSmsSender
 * @package Proklung\Notifier\Bitrix\Sender
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
        $fields = $success === 'N' ? ['error' => $errorMessage] : ['data' => $sentMessage->getMessageId()];

        EventTableUpdater::create($codeEvent, $fields, 99999, $success);
    }
}
