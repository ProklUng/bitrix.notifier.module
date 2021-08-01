<?php

namespace Proklung\Notifier\DI;

use Bitrix\Main\Config\Configuration;
use LogicException;
use ProklUng\ContainerBoilerplate\DI\AbstractServiceContainer;
use Exception;
use RuntimeException;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Mailer\Bridge\Google\Transport\GmailTransportFactory;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Notifier\Recipient\Recipient;
use Symfony\Component\Notifier\Transport\TransportFactoryInterface;
use Twig_Environment;

/**
 * Class Services
 * @package Proklung\Notifier\DI
 *
 * @since 27.07.2021
 */
class Services extends AbstractServiceContainer
{
    /**
     * @var ContainerBuilder|null $container Контейнер.
     */
    protected static $container;

    /**
     * @var array $config Битриксовая конфигурация.
     */
    protected $config = [];

    /**
     * @var array $parameters Параметры битриксового сервис-локатора.
     */
    protected $parameters = [];

    /**
     * @var array $services Сервисы битриксового сервис-локатора.
     */
    protected $services = [];

    /**
     * @var array $mailerConfig Конфигурация мэйлера.
     */
    private $mailerConfig;

    /**
     * @var string $moduleId ID модуля (переопределяется наследником).
     */
    protected $moduleId = 'proklung.notifier';

    /**
     * @var array $twigConfig Конфигурация Твига.
     */
    private $twigConfig;

    /**
     * Services constructor.
     */
    public function __construct()
    {
        parent::__construct();

        $this->config = Configuration::getInstance()->get($this->moduleId) ?? ['proklung.notifier' => []];

        $this->services = $this->config['services'] ?? [];
        $this->mailerConfig = $this->config['mailer'] ?? [];
        $this->twigConfig = $this->config['twig'] ?? [];

        // Инициализация параметров контейнера.
        $this->parameters['cache_path'] = $this->config['parameters']['cache_path'] ?? '/bitrix/cache/proklung.notifier';
        $this->parameters['container.dumper.inline_factories'] = $this->config['parameters']['container.dumper.inline_factories'] ?? false;
        $this->parameters['compile_container_envs'] = (array)$this->config['parameters']['compile_container_envs'];

        unset(
            $this->config['parameters'],
            $this->config['services'],
            $this->config['mailer'],
            $this->config['twig']
        );
    }

    /**
     * Инициализация контейнера.
     *
     * @return void
     * @throws Exception
     */
    public function initContainer() : void
    {
        static::$container->setParameter('kernel.debug', $_ENV['DEBUG'] ?? true);
        static::$container->setParameter('kernel.project_dir', $_SERVER['DOCUMENT_ROOT']);
        static::$container->setParameter('kernel.cache_dir', $_SERVER['DOCUMENT_ROOT'] . '/bitrix/cache');
        static::$container->setParameter('mailer_enabled', false);

        $loader = new PhpFileLoader(static::$container, new FileLocator(__DIR__ . '/../../configs'));

        $loader->load('notifier.php');
        $loader->load('notifier_transports.php');
        $loader->load('bitrix.php');

        $configManager = new \Proklung\Notifier\DI\Configuration(
            static::$container->getParameter('kernel.debug')
        );

        $config = $this->processConfiguration($configManager, $this->config);

        static::$container->setParameter('notifier_config', $config);
        static::$container->setParameter('notifier_params', $this->parameters);

        $this->processServices($this->services, static::$container);

        $loaderYaml = new YamlFileLoader(static::$container, new FileLocator(__DIR__ . '/../../configs'));
        if (class_exists(Twig_Environment::class)
            ||
            class_exists(\Twig\Environment::class)
        ) {
            $loaderYaml->load('twig.yaml');

            static::$container->setParameter('twig_paths', $this->twigConfig['paths']);
            static::$container->setParameter('twig_cache_dir', $this->twigConfig['cache_dir']);
            static::$container->setParameter('twig_config', $this->twigConfig['config']);
        }

        if (!empty($this->mailerConfig) && (bool)$this->mailerConfig['enabled'] === true) {
            if (!class_exists(Mailer::class)) {
                throw new LogicException(
                    'Mailer support cannot be enabled as the component is not installed. Try running "composer require symfony/mailer".'
                );
            }

            $loaderYaml->load('mailer.yaml');
            if (class_exists(Twig_Environment::class)) {
                $loaderYaml->load('mailer_custom.yaml');
            }

            $loaderYaml->load('mailer_transports.yaml');

            $configMailerManager = new ConfigurationMailer(
                static::$container->getParameter('kernel.debug')
            );

            $configMailer = $this->processConfiguration($configMailerManager, [$this->mailerConfig]);

            $this->registerMailerConfiguration($configMailer, static::$container);

            $defaultSender = $configMailer['envelope']['sender'] ?? '';
            if (!$defaultSender) {
                $defaultSender = \COption::GetOptionString('main', 'email_from');
            }

            static::$container->setParameter(
                'default_sender_email',
                $defaultSender
            );
        } else { // Enabled = false или отсутствует
            if (static::$container->hasDefinition('notifier.channel.email')) {
                static::$container->removeDefinition('notifier.channel.email');
            }
        }

        $this->registerNotifierConfiguration($config, static::$container);

        $this->setupAutowiring(static::$container);

        $this->build(static::$container);

        static::$container->compile(true);
    }

    /**
     * Flashes.
     *
     * @return FlashBagInterface
     * @throws Exception
     */
    public static function getFlashBag() : FlashBagInterface
    {
        $container = static::getInstance();
        /** @var Session $session */
        $session = $container->get('session_instance');

        return $session->getFlashBag();
    }

    /**
     * Обработка классов как сервисов из битриксового конфига. Раздел services.
     *
     * @param array            $services  Конфиг.
     * @param ContainerBuilder $container Контейнер.
     *
     * @return void
     */
    private function processServices(array $services, ContainerBuilder $container) : void
    {
        if (count($services) === 0) {
            return;
        }

        foreach ($services as $id => $serviceData) {
            if (!$serviceData['class'] || !class_exists($serviceData['class'])) {
                throw new RuntimeException(
                    'Класс сервиса не указан или не существует: ' . $serviceData['class']
                );
            }

            $definition = new Definition($serviceData['class']);
            $definition->setPublic(true);

            if (!empty($serviceData['tags'])) {
                $tagName = $serviceData['tags']['name'] ?? '';
                if ($tagName) {
                    $definition->addTag($tagName);
                }
            }

            $container->setDefinition($id, $definition);
            if ($id !== $serviceData['class']) {
                $container->setAlias($serviceData['class'], $id);
            }
        }
    }

    /**
     * Autowiring.
     *
     * @param ContainerBuilder $container Контейнер.
     *
     * @return void
     */
    private function setupAutowiring(ContainerBuilder $container) : void
    {
        $container->registerForAutoconfiguration(TransportFactoryInterface::class)
            ->setPublic(true)
            ->addTag('texter.transport_factory')
        ;
    }

    /**
     * @param ConfigurationInterface $configuration
     * @param array                  $configs
     *
     * @return array
     */
    private function processConfiguration(ConfigurationInterface $configuration, array $configs): array
    {
        $processor = new Processor();

        return $processor->processConfiguration($configuration, $configs);
    }

    /**
     * @param ContainerBuilder $container Контейнер.
     *
     * @return void
     */
    private function build(ContainerBuilder $container): void
    {
    }

    /**
     * Конфигурирование Notifier.
     *
     * @param array            $config    Конфиг.
     * @param ContainerBuilder $container Контейнер.
     *
     * @return void
     */
    private function registerNotifierConfiguration(array $config, ContainerBuilder $container)
    {
        if ($config['chatter_transports']) {
            $container->getDefinition('chatter.transports')->setArgument(0, $config['chatter_transports']);
        } else {
            $container->removeDefinition('chatter');
        }
        if ($config['texter_transports']) {
            $container->getDefinition('texter.transports')->setArgument(0, $config['texter_transports']);
        } else {
            $container->removeDefinition('texter');
        }

        $container->getDefinition('notifier.channel_policy')->setArgument(0, $config['channel_policy']);

        if (class_exists(FakeChatTransportFactory::class)
            && $container->hasDefinition('mailer')
        ) {
            $container->getDefinition('notifier.transport_factory.fakechat')
                ->replaceArgument('$mailer', new Reference('mailer'));
        }

        if (class_exists(FakeSmsTransportFactory::class)
            && $container->hasDefinition('mailer')
        ) {
            $container->getDefinition('notifier.transport_factory.fakesms')
                ->replaceArgument('$mailer', new Reference('mailer'));
        }

        if (isset($config['admin_recipients'])) {
            $notifier = $container->getDefinition('notifier');
            foreach ($config['admin_recipients'] as $i => $recipient) {
                $id = 'notifier.admin_recipient.'.$i;
                $container->setDefinition($id, new Definition(Recipient::class, [$recipient['email'], $recipient['phone']]));
                $notifier->addMethodCall('addAdminRecipient', [new Reference($id)]);
            }
        }
    }

    /**
     * Конфигурирование мэйлера.
     *
     * @param array            $config    Конфиг.
     * @param ContainerBuilder $container Контейнер.
     *
     * @return void
     */
    private function registerMailerConfiguration(array $config, ContainerBuilder $container)
    {
        if (!\count($config['transports']) && null === $config['dsn']) {
            $config['dsn'] = 'smtp://null';
        }
        $transports = $config['dsn'] ? ['main' => $config['dsn']] : $config['transports'];
        $container->getDefinition('mailer.transports')->setArgument(0, $transports);
        $container->getDefinition('mailer.default_transport')->setArgument(0, current($transports));

        $classToServices = [
            SesTransportFactory::class => 'mailer.transport_factory.amazon',
            GmailTransportFactory::class => 'mailer.transport_factory.gmail',
            MandrillTransportFactory::class => 'mailer.transport_factory.mailchimp',
            MailgunTransportFactory::class => 'mailer.transport_factory.mailgun',
            PostmarkTransportFactory::class => 'mailer.transport_factory.postmark',
            SendgridTransportFactory::class => 'mailer.transport_factory.sendgrid',
        ];

        foreach ($classToServices as $class => $service) {
            if (!class_exists($class)) {
                $container->removeDefinition($service);
            }
        }

        $recipients = $config['envelope']['recipients'] ?? null;
        $sender = $config['envelope']['sender'] ?? null;

        $envelopeListener = $container->getDefinition('mailer.envelope_listener');
        $envelopeListener->setArgument(0, $sender);
        $envelopeListener->setArgument(1, $recipients);

        $container->setParameter('mailer_dsn_file', (string)$config['dsn_file']);
        $container->setParameter('mailer_dsn', (string)$config['dsn']);
        $container->setParameter('mailer_default_email_from', (string)$config['default_email_from']);
        $container->setParameter('mailer_default_title', (string)$config['default_email_title']);
        $container->setParameter('envelope', (array)$config['envelope']);

        $container->setParameter('mailer_enabled', true);
    }
}
