# Интеграция компонента Symfony Notifier в Битрикс

[Документация](https://symfony.com/doc/current/notifier.html) на оригинальный компонент. 
В целом модуль следует канве оригинального компонента. Основное отличие - способ конфигурирования сервисов
 (не Yaml, а битриксовые массивные конфиги).

## Установка

composer.json основного проекта:

```json
  "extra": {
    "installer-paths": {
      "./bitrix/modules/{$name}/": ["type:bitrix-d7-module", "type:bitrix-module"],
      "./bitrix/components/{$name}/": ["type:bitrix-d7-component", "type:bitrix-component"],
      "./bitrix/templates/{$name}/": ["type:bitrix-d7-template", "type:bitrix-theme"]
    }
  }
```

И:

```json
    "repositories": [
        {
            "type": "git",
            "url": "https://github.com/proklung/bitrix.notifier.module"
        },
        {
          "type": "git",
          "url": "https://github.com/proklung/bitrix.containerable.boilerplate"
        }
    ]
```

```bash
$ composer require proklung/bitrix-notifier-module
```

Установите модуль `proklung.notifier` в административном интерфейсе сайта `bitrix/admin/partner_modules.php`

Добавьте следующий код в ваш `init.php`:

```php
use Bitrix\Main\Loader;

Loader::includeModule('proklung.notifier');

```
### Использование (простейшие примеры)

Отправить сообщение в чат Телеграма:

```php
use Symfony\Component\Notifier\Message\ChatMessage;

$container = \Proklung\Notifier\DI\Services::boot();
$chatter = $container->get('chatter');

$notification = (new ChatMessage('Hi from module'));
$chatter->send($notification);
```

Отправить SMS (при условии, что соответствующий транспорт сконфигурирован):

```php
use Symfony\Component\Notifier\Message\SmsMessage;

$container = \Proklung\Notifier\DI\Services::boot();
$texter = $container->get('texter');
$sms = new SmsMessage(
            '+79263622508',
            'Тестирование!'
        );

$sentMessage = $texter->send($sms);
```

Отправить email (NB: сейчас все настроено под отправку через Gmail; SMTP не тестировал):

```php
use Symfony\Component\Notifier\Notification\Notification;
use Symfony\Component\Notifier\Recipient\Recipient;

$notifier = \Proklung\Notifier\DI\Services::getInstance()->get('notifier');
$notification = (new Notification('New Invoice', ['email']))
                ->content('You got a new invoice for 15 EUR.');

$recipient = new Recipient(
            'email_customer@gmail.com',
            '+71111111'
        );

$notifier->send($notification, $recipient);
```

### Зависимости

Модуль не отягощен лишними зависимостями. В комплекте идет только `Symfony Mailer`. Нужные транспорты подтягиваются
отдельно.

Например, если нужна отправка сообщений в Телеграм, то ставится пакет `symfony/telegram-notifier`. Т.к. он "стандартный",
то подхватится автоматически.

Если нужно зацепить кастомный транспорт, то это делается через `/bitrix/.settings.php` (смотри ниже). 

## Конфигурационный файл

Конфигурация идентична родительскому пакету. Настройка производится посредством правки файлов `bitrix/.settings.php`

Секция `proklung.notifier`:

```php

return [
// ....
  'proklung.notifier' => [
          'value' => [
              'notifier' => [
                  'chatter_transports' => [
                      'telegram' => 'telegram://315114113:AAFjvQlEiaWisZsav_YQfwmhEIIP9PFw3Ss@default?channel=-1001595706876',
                  ],
                  'texter_transports' => [
                      'smstraffic' => 'smstraffic://mosfond1:pnLag4yN@default?from=MFC',
                  ],
              ],
              'parameters' => [
                  'cache_path' => '/bitrix/cache/s1/proklung.notifier', // Путь к закешированному контейнеру
                  'container.dumper.inline_factories' => false, // Дампить контейнер как одиночные файлы
                  'compile_container_envs' => ['prod'], // Окружения при которых компилировать контейнер
              ],
              'services' => [
                  // Дополнительный кастомный транспорт.
                  // Сервисы инстанцируются без auto-wiring
                  'smstraffic.factory' =>
                      [
                          'class' => 'Prokl\Component\Notifier\Bridge\SmsTraffic\SmsTrafficTransportFactory',
                          'tags' => ['name' => 'texter.transport_factory'],
                      ],
              ],
              'mailer' => [
                      'enabled' => true,
                      'dsn' => 'gmail+smtp://robotivanov2020:izjqfjyegwmznrtb@default',
                      'dsn_file' => 'file:///upload/emails/{Y}/{m}/{d}/@hash.eml',
                      'envelope' => [
                          'recipients' => ['testing@mail.ru'],
                           // Поле from отправляемого письма 
                          'sender' => 'sender@gmail.com',  // Важный параметр, если иметь дело с почтой.
                  ],
              ],
              // Конфигурация Твига (для генерации писем)  
              'twig' => [
                  'cache_dir' => $_SERVER['DOCUMENT_ROOT'] . '/bitrix/cache/twig',
                  'config' => [],
                  'paths' => [$_SERVER['DOCUMENT_ROOT'] . '/'],
              ],
          ],
      ]
];
```

## Кэширование контейнера

Параметр `cache_path` - путь, куда ляжет скомпилированный контейнер. Если не задано, то по умолчанию `/bitrix/cache/s1/proklung.redis`.

Предполагается, что в системе так или иначе установлена переменная среды `DEBUG` в массиве `$_ENV`. Если нет, то по умолчанию
 полагается, что среда "отладочная".
 
Параметр (массив) `compile_container_envs` указывает окружения, при которых необходимо кэшировать контейнер.

Пока простая логика: `$_ENV["DEBUG"] === true` => окружение `dev`, иначе `prod`. 
