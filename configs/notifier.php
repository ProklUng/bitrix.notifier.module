<?php

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Proklung\Notifier\Email\CustomEmailChannel;
use Proklung\Notifier\Session\SessionInitializator;
use Symfony\Bridge\Monolog\Handler\NotifierHandler;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Notifier\Channel\BrowserChannel;
use Symfony\Component\Notifier\Channel\ChannelPolicy;
use Symfony\Component\Notifier\Channel\ChatChannel;
use Symfony\Component\Notifier\Channel\EmailChannel;
use Symfony\Component\Notifier\Channel\SmsChannel;
use Symfony\Component\Notifier\Chatter;
use Symfony\Component\Notifier\ChatterInterface;
use Symfony\Component\Notifier\EventListener\NotificationLoggerListener;
use Symfony\Component\Notifier\EventListener\SendFailedMessageToNotifierListener;
use Symfony\Component\Notifier\Message\ChatMessage;
use Symfony\Component\Notifier\Message\SmsMessage;
use Symfony\Component\Notifier\Messenger\MessageHandler;
use Symfony\Component\Notifier\Notifier;
use Symfony\Component\Notifier\NotifierInterface;
use Symfony\Component\Notifier\Texter;
use Symfony\Component\Notifier\TexterInterface;
use Symfony\Component\Notifier\Transport;
use Symfony\Component\Notifier\Transport\Transports;
use Twig\Extra\CssInliner\CssInlinerExtension;
use Twig\Extra\Inky\InkyExtension;

return static function (ContainerConfigurator $container) {
    // Mailer должен присутствовать.
    if (class_exists(Mailer::class)) {
        $missingPackages = [];
        if (!class_exists(CssInlinerExtension::class)) {
            $missingPackages['twig/cssinliner-extra'] = ' CSS Inliner';
        }

        if (!class_exists(InkyExtension::class)) {
            $missingPackages['twig/inky-extra'] = 'Inky';
        }

        $classEmailChannel = count($missingPackages) === 0 ? EmailChannel::class : CustomEmailChannel::class;

        $container->services()
            ->set('notifier.channel.email', $classEmailChannel)
            ->args([service('mailer.transports'),
                    service('messenger.default_bus')->ignoreOnInvalid(),
                    $classEmailChannel === CustomEmailChannel::class ? param('default_sender_email') : null
                ])
            ->tag('notifier.channel', ['channel' => 'email']);
    }

    $container->services()
        ->set('session_migrator', SessionInitializator::class)

        ->set('session_instance', SessionInterface::class)
        ->public()
        ->factory([service('session_migrator'), 'session'])

        ->set('request_stack', RequestStack::class)
        ->call('push', [service('module_request')])
        ->public()

        ->set('event_dispatcher', EventDispatcher::class)

        ->set('module_request', Request::class)
        ->factory([Request::class, 'createFromGlobals'])
        ->call('setSession', [service('session_instance')])

        ->set('notifier', Notifier::class)
            ->args([tagged_locator('notifier.channel', 'channel'), service('notifier.channel_policy')->ignoreOnInvalid()])
        ->public()
        ->alias(NotifierInterface::class, 'notifier')

        ->set('notifier.channel_policy', ChannelPolicy::class)
            ->args([[]])

        ->set('notifier.channel.browser', BrowserChannel::class)
            ->args([service('request_stack')])
            ->tag('notifier.channel', ['channel' => 'browser'])

        ->set('notifier.channel.chat', ChatChannel::class)
            ->args([service('chatter.transports'), service('messenger.default_bus')->ignoreOnInvalid()])
            ->tag('notifier.channel', ['channel' => 'chat'])

        ->set('notifier.channel.sms', SmsChannel::class)
            ->args([service('texter.transports'), service('messenger.default_bus')->ignoreOnInvalid()])
            ->tag('notifier.channel', ['channel' => 'sms'])

        ->set('notifier.monolog_handler', NotifierHandler::class)
            ->args([service('notifier')])

        ->set('notifier.failed_message_listener', SendFailedMessageToNotifierListener::class)
            ->args([service('notifier')])

        ->set('chatter', Chatter::class)
            ->args([
                service('chatter.transports'),
                service('messenger.default_bus')->ignoreOnInvalid(),
                service('event_dispatcher')->ignoreOnInvalid(),
            ])
        ->public()
        ->alias(ChatterInterface::class, 'chatter')

        ->set('chatter.transports', Transports::class)
            ->factory([service('chatter.transport_factory'), 'fromStrings'])
            ->args([[]])

        ->set('chatter.transport_factory', Transport::class)
            ->args([tagged_iterator('chatter.transport_factory')])

        ->set('chatter.messenger.chat_handler', MessageHandler::class)
            ->args([service('chatter.transports')])
            ->tag('messenger.message_handler', ['handles' => ChatMessage::class])

        ->set('texter', Texter::class)
            ->args([
                service('texter.transports'),
                service('messenger.default_bus')->ignoreOnInvalid(),
                service('event_dispatcher')->ignoreOnInvalid(),
            ])
            ->public()

        ->alias(TexterInterface::class, 'texter')

        ->set('texter.transports', Transports::class)
            ->factory([service('texter.transport_factory'), 'fromStrings'])
            ->args([[]])

        ->set('texter.transport_factory', Transport::class)
            ->args([tagged_iterator('texter.transport_factory')])

        ->set('texter.messenger.sms_handler', MessageHandler::class)
            ->args([service('texter.transports')])
            ->tag('messenger.message_handler', ['handles' => SmsMessage::class])

        ->set('notifier.logger_notification_listener', NotificationLoggerListener::class)
            ->tag('kernel.event_subscriber')
    ;
};
