<?php

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Symfony\Component\Notifier\Bridge\AllMySms\AllMySmsTransportFactory;
use Symfony\Component\Notifier\Bridge\Clickatell\ClickatellTransportFactory;
use Symfony\Component\Notifier\Bridge\Discord\DiscordTransportFactory;
use Symfony\Component\Notifier\Bridge\Esendex\EsendexTransportFactory;
use Symfony\Component\Notifier\Bridge\FakeChat\FakeChatTransportFactory;
use Symfony\Component\Notifier\Bridge\FakeSms\FakeSmsTransportFactory;
use Symfony\Component\Notifier\Bridge\Firebase\FirebaseTransportFactory;
use Symfony\Component\Notifier\Bridge\FreeMobile\FreeMobileTransportFactory;
use Symfony\Component\Notifier\Bridge\GatewayApi\GatewayApiTransportFactory;
use Symfony\Component\Notifier\Bridge\Gitter\GitterTransportFactory;
use Symfony\Component\Notifier\Bridge\GoogleChat\GoogleChatTransportFactory;
use Symfony\Component\Notifier\Bridge\Infobip\InfobipTransportFactory;
use Symfony\Component\Notifier\Bridge\Iqsms\IqsmsTransportFactory;
use Symfony\Component\Notifier\Bridge\LightSms\LightSmsTransportFactory;
use Symfony\Component\Notifier\Bridge\LinkedIn\LinkedInTransportFactory;
use Symfony\Component\Notifier\Bridge\Mattermost\MattermostTransportFactory;
use Symfony\Component\Notifier\Bridge\Mercure\MercureTransportFactory;
use Symfony\Component\Notifier\Bridge\MessageBird\MessageBirdTransportFactory;
use Symfony\Component\Notifier\Bridge\MicrosoftTeams\MicrosoftTeamsTransportFactory;
use Symfony\Component\Notifier\Bridge\Mobyt\MobytTransportFactory;
use Symfony\Component\Notifier\Bridge\Nexmo\NexmoTransportFactory;
use Symfony\Component\Notifier\Bridge\Octopush\OctopushTransportFactory;
use Symfony\Component\Notifier\Bridge\OvhCloud\OvhCloudTransportFactory;
use Symfony\Component\Notifier\Bridge\RocketChat\RocketChatTransportFactory;
use Symfony\Component\Notifier\Bridge\Sendinblue\SendinblueTransportFactory;
use Symfony\Component\Notifier\Bridge\Sinch\SinchTransportFactory;
use Symfony\Component\Notifier\Bridge\Slack\SlackTransportFactory;
use Symfony\Component\Notifier\Bridge\Smsapi\SmsapiTransportFactory;
use Symfony\Component\Notifier\Bridge\SmsBiuras\SmsBiurasTransportFactory;
use Symfony\Component\Notifier\Bridge\SpotHit\SpotHitTransportFactory;
use Symfony\Component\Notifier\Bridge\Telegram\TelegramTransportFactory;
use Symfony\Component\Notifier\Bridge\Twilio\TwilioTransportFactory;
use Symfony\Component\Notifier\Bridge\Zulip\ZulipTransportFactory;
use Symfony\Component\Notifier\Transport\AbstractTransportFactory;
use Symfony\Component\Notifier\Transport\NullTransportFactory;
use Symfony\Contracts\HttpClient\HttpClientInterface;

return static function (ContainerConfigurator $container) {

    $classToServices = [
        AllMySmsTransportFactory::class => ['texter.transport_factory', 'notifier.transport_factory.allmysms'],
        ClickatellTransportFactory::class => ['texter.transport_factory', 'notifier.transport_factory.clickatell'],
        DiscordTransportFactory::class => ['chatter.transport_factory', 'notifier.transport_factory.discord'],
        EsendexTransportFactory::class => ['texter.transport_factory', 'notifier.transport_factory.esendex'],
        FakeChatTransportFactory::class => ['chatter.transport_factory', 'notifier.transport_factory.fake-chat'],
        FakeSmsTransportFactory::class => ['texter.transport_factory', 'notifier.transport_factory.fake-sms'],
        FirebaseTransportFactory::class => ['chatter.transport_factory', 'notifier.transport_factory.firebase'],
        FreeMobileTransportFactory::class => ['texter.transport_factory','notifier.transport_factory.free-mobile'],
        GatewayApiTransportFactory::class => ['texter.transport_factory', 'notifier.transport_factory.gateway-api'],
        GitterTransportFactory::class => ['chatter.transport_factory', 'notifier.transport_factory.gitter'],
        GoogleChatTransportFactory::class => ['chatter.transport_factory', 'notifier.transport_factory.google-chat'],
        InfobipTransportFactory::class => ['texter.transport_factory', 'notifier.transport_factory.info-bip'],
        IqsmsTransportFactory::class => ['texter.transport_factory', 'notifier.transport_factory.iqsms'],
        LightSmsTransportFactory::class => ['texter.transport_factory', 'notifier.transport_factory.light-sms'],
        LinkedInTransportFactory::class => ['chatter.transport_factory', 'notifier.transport_factory.linked-in'],
        MattermostTransportFactory::class => ['chatter.transport_factory', 'notifier.transport_factory.mattermost'],
        MercureTransportFactory::class => ['chatter.transport_factory', 'notifier.transport_factory.mercure'],
        MessageBirdTransport::class => ['texter.transport_factory', 'notifier.transport_factory.messagebird'],
        MicrosoftTeamsTransportFactory::class => ['chatter.transport_factory', 'notifier.transport_factory.microsoft-teams'],
        MobytTransportFactory::class => ['texter.transport_factory', 'notifier.transport_factory.mobyt'],
        NexmoTransportFactory::class => ['texter.transport_factory', 'notifier.transport_factory.nexmo'],
        OctopushTransportFactory::class => ['texter.transport_factory', 'notifier.transport_factory.octopush'],
        OvhCloudTransportFactory::class => ['texter.transport_factory', 'notifier.transport_factory.ovh-cloud'],
        RocketChatTransportFactory::class => ['chatter.transport_factory', 'notifier.transport_factory.rocket-chat'],
        SendinblueNotifierTransportFactory::class => ['texter.transport_factory', 'notifier.transport_factory.sendinblue'],
        SinchTransportFactory::class => ['texter.transport_factory', 'notifier.transport_factory.sinch'],
        SlackTransportFactory::class => ['chatter.transport_factory', 'notifier.transport_factory.slack'],
        SmsapiTransportFactory::class => 'notifier.transport_factory.smsapi',
        SmsBiurasTransportFactory::class => ['texter.transport_factory', 'notifier.transport_factory.sms-biuras'],
        SpotHitTransportFactory::class => ['texter.transport_factory', 'notifier.transport_factory.spot-hit'],
        TelegramTransportFactory::class => ['chatter.transport_factory', 'notifier.transport_factory.telegram'],
        TwilioTransportFactory::class => ['texter.transport_factory','notifier.transport_factory.twilio'],
        ZulipTransportFactory::class => ['chatter.transport_factory', 'notifier.transport_factory.zulip'],
    ];

    foreach ($classToServices as $class => $service) {
        if (class_exists($class)) {
                $container->services()
                ->set($service[1], $class)
                ->parent('notifier.transport_factory.abstract')
                ->tag($service[0]);
        }
    }

    $container->services()
        ->set('http_client', HttpClientInterface::class)
        ->factory(['Symfony\Component\HttpClient\HttpClient', 'create'])

        ->set('notifier.transport_factory.null', NullTransportFactory::class)
        ->parent('notifier.transport_factory.abstract')
        ->tag('chatter.transport_factory')
        ->tag('texter.transport_factory')

        ->set('notifier.transport_factory.abstract', AbstractTransportFactory::class)
            ->abstract()
            ->args([service('event_dispatcher'), service('http_client')->ignoreOnInvalid()])
    ;
};
