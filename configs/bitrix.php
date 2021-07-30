<?php

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Proklung\Notifier\Bitrix\EventBridgeMail;
use Proklung\Notifier\Bitrix\EventBridgeSms;
use Proklung\Notifier\Bitrix\Sender\BitrixMailEventSender;
use Proklung\Notifier\Bitrix\Sender\BitrixPolicySender;
use Proklung\Notifier\Bitrix\Sender\BitrixSmsSender;
use Proklung\Notifier\Bitrix\Sender\BitrixTelegramEventSender;

return static function (ContainerConfigurator $container) {
    $container->services()
              ->set(EventBridgeMail::class, EventBridgeMail::class)
              ->public()

              ->set(EventBridgeSms::class, EventBridgeSms::class)
              ->public()

              ->set(BitrixPolicySender::class, BitrixPolicySender::class)
              ->args([service(EventBridgeMail::class), service('notifier')])
              ->public()

              ->alias('bitrix.notifier.policy', BitrixPolicySender::class)
              ->public()

              ->set(BitrixMailEventSender::class, BitrixMailEventSender::class)
              ->args([service(EventBridgeMail::class), service('notifier')])
              ->public()

              ->alias('bitrix.notifier.mail', BitrixMailEventSender::class)
              ->public()

              ->set(BitrixSmsSender::class, BitrixSmsSender::class)
              ->args([service(EventBridgeSms::class), service('texter')])
              ->public()

              ->alias('bitrix.notifier.sms', BitrixSmsSender::class)
              ->public()

              ->set(BitrixTelegramEventSender::class, BitrixTelegramEventSender::class)
              ->args([service(EventBridgeMail::class), service('chatter')])
              ->public()

              ->alias('bitrix.notifier.telegram', BitrixTelegramEventSender::class)
              ->public()
    ;
};
