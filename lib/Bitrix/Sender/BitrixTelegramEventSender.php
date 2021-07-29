<?php

namespace Proklung\Notifier\Bitrix\Sender;

use League\HTMLToMarkdown\HtmlConverter;
use Proklung\Notifier\Bitrix\Contract\BitrixNotifierSenderInterface;
use Proklung\Notifier\Bitrix\EventBridgeMail;
use Proklung\Notifier\Bitrix\Utils\EventTableUpdater;
use RuntimeException;
use Symfony\Component\Notifier\Bridge\Telegram\TelegramOptions;
use Symfony\Component\Notifier\Bridge\Telegram\TelegramTransport;
use Symfony\Component\Notifier\ChatterInterface;
use Symfony\Component\Notifier\Message\ChatMessage;

/**
 * Class BitrixTelegramEventSender
 * @package Proklung\Notifier\Bitrix
 *
 * @since 28.07.2021
 */
class BitrixTelegramEventSender implements BitrixNotifierSenderInterface
{
    /**
     * @var EventBridgeMail $eventBridge Обработка битриксовых данных события.
     */
    private $eventBridge;

    /**
     * @var ChatterInterface $notifier Notifier.
     */
    private $notifier;

    /**
     * BitrixChatEventSender constructor.
     *
     * @param EventBridgeMail  $eventBridge Обработка битриксовых данных события.
     * @param ChatterInterface $notifier    Notifier.
     */
    public function __construct(EventBridgeMail $eventBridge, ChatterInterface $notifier)
    {
        $this->eventBridge = $eventBridge;
        $this->notifier = $notifier;
    }

    /**
     * Статический фасад.
     *
     * @param ChatterInterface $notifier Notifier.
     *
     * @return static
     * @throws RuntimeException Когда пакет symfony/telegram-notifier не установлен.
     */
    public static function getInstance(ChatterInterface $notifier) : self
    {
        if (!class_exists(TelegramTransport::class)) {
            throw new RuntimeException(
                sprintf(
                    'Unable to send notification via "%s" as the bridge is not installed; try running "composer require %s".',
                    'telegram',
                    'symfony/telegram-notifier'
                )
            );
        }

        return new static(new EventBridgeMail(), $notifier);
    }

    /**
     * @inheritdoc
     */
    public function send(string $codeEvent, array $arFields) : void
    {
        $eventsInfo = $this->eventBridge->getMessageTemplate($codeEvent);
        foreach ($eventsInfo as $eventInfo) {
            $compileData = $this->eventBridge->compileMessage($eventInfo, $arFields, ['s1']);

            $content = $compileData['subject'] . ' ' . $compileData['body'];

            $converter = new HtmlConverter(['remove_nodes' => 'span div']);
            $markdown = $converter->convert($content);

            $notification = (new ChatMessage($markdown))
                ->transport('telegram');

            $telegramOptions = (new TelegramOptions())
                ->parseMode('Markdown')
                ->disableWebPagePreview(true)
                ->disableNotification(false);

            $notification->options($telegramOptions);

            $this->notifier->send($notification);

            // Эмуляция поведения Битрикса при обработке событий.
            EventTableUpdater::create($eventInfo->getEventCode(), $eventInfo->getMessageData(), 99999);
        }
    }
}