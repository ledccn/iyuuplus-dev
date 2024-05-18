# notify

> [!NOTE]
> Push notification SDK(AnPush、Bark、Chanify、DingTalk、Discord、Gitter、GoogleChat、IGot、Iyuu、Lark、Mattermost、MicrosoftTeams、NowPush、Ntfy、Push、Pushback、PushBullet、PushDeer、Pushover、PushPlus、QQ、RocketChat、ServerChan、ShowdocPush、SimplePush、Slack、Telegram、WeWork、WPush、XiZhi、YiFengChuanHua、Zulip).

[![tests](https://github.com/guanguans/notify/actions/workflows/tests.yml/badge.svg)](https://github.com/guanguans/notify/actions/workflows/tests.yml)
[![check & fix styling](https://github.com/guanguans/notify/actions/workflows/php-cs-fixer.yml/badge.svg)](https://github.com/guanguans/notify/actions/workflows/php-cs-fixer.yml)
[![codecov](https://codecov.io/gh/guanguans/notify/branch/main/graph/badge.svg?token=URGFAWS6S4)](https://codecov.io/gh/guanguans/notify)
[![Latest Stable Version](https://poser.pugx.org/guanguans/notify/v)](https://packagist.org/packages/guanguans/notify)
[![GitHub release (with filter)](https://img.shields.io/github/v/release/guanguans/notify)](https://github.com/guanguans/notify/releases)
[![Total Downloads](https://poser.pugx.org/guanguans/notify/downloads)](https://packagist.org/packages/guanguans/notify)
[![License](https://poser.pugx.org/guanguans/notify/license)](https://packagist.org/packages/guanguans/notify)

## Platform support

[//]: # (https://github.com/dschep/ntfy)
* [AnPush](./src/AnPush/README.md)
* [Bark](./src/Bark/README.md)
* [Chanify](./src/Chanify/README.md)
* [DingTalk](./src/DingTalk/README.md)
* [Discord](./src/Discord/README.md)
* [~~Gitter~~](./src/Gitter/README.md)
* [GoogleChat](./src/GoogleChat/README.md)
* [IGot](./src/IGot/README.md)
* [Iyuu](./src/Iyuu/README.md)
* [Lark](./src/Lark/README.md)
* [Mattermost](./src/Mattermost/README.md)
* [MicrosoftTeams](./src/MicrosoftTeams/README.md)
* [~~NowPush~~](./src/NowPush/README.md)
* [Ntfy](./src/Ntfy/README.md)
* [Push](./src/Push/README.md)
* [Pushback](./src/Pushback/README.md)
* [PushBullet](./src/PushBullet/README.md)
* [PushDeer](./src/PushDeer/README.md)
* [Pushover](./src/Pushover/README.md)
* [PushPlus](./src/PushPlus/README.md)
* [QQ](./src/QQ/README.md)
* [RocketChat](./src/RocketChat/README.md)
* [ServerChan](./src/ServerChan/README.md)
* [ShowdocPush](./src/ShowdocPush/README.md)
* [SimplePush](./src/SimplePush/README.md)
* [Slack](./src/Slack/README.md)
* [Telegram](./src/Telegram/README.md)
* [WeWork](./src/WeWork/README.md)
* [WPush](./src/WPush/README.md)
* [XiZhi](./src/XiZhi/README.md)
* [YiFengChuanHua](./src/YiFengChuanHua/README.md)
* [Zulip](./src/Zulip/README.md)

## Related repository

* [https://github.com/guanguans/laravel-exception-notify](https://github.com/guanguans/laravel-exception-notify)
* [https://github.com/guanguans/yii-log-target](https://github.com/guanguans/yii-log-target)

## Requirement

* PHP >= 8.2

## Installation

```bash
composer require ledc/notify -v
```

## Usage example

```php
// 1. Create authenticator
$authenticator = new Guanguans\Notify\DingTalk\Authenticator(
    'c44fec1ddaa8a833156efb77b7865d62ae13775418030d94d05da08bfca73',
    // 'SECc32bb7345c0f73da2b9786f0f7dd5083bd768a29b82e6d460149d730eee51'
);

// 2. Create client
$client = new Guanguans\Notify\DingTalk\Client($authenticator);

// 3. Create message
$message = Guanguans\Notify\DingTalk\Messages\BtnsActionCardMessage::make([
    'title' => 'This is title(keyword).',
    'text' => 'This is text.',
    // 'btnOrientation' => 1,
    // 'btns' => [
    //     [
    //         'title' => 'This is title 1.',
    //         'actionURL' => 'https://github.com/guanguans/notify',
    //     ],
    // ],
])
    ->btnOrientation(1)
    ->addBtn([
        'title' => 'This is title 2.',
        'actionURL' => 'https://github.com/guanguans/notify',
    ]);

// 4. Send message
$response = $client
    // ->baseUri('base-uri')
    // ->connectTimeout(10)
    // ->debug(true)
    // ->proxy('http://127.0.0.1:1087')
    // ->timeout(30)
    // ->verify(false)
    // ->push(
    //     GuzzleHttp\Middleware::log(
    //         new Psr\Log\NullLogger,
    //         new GuzzleHttp\MessageFormatter(GuzzleHttp\MessageFormatter::DEBUG)
    //     ),
    //     'log'
    // )
    // ->before(
    //     'log',
    //     GuzzleHttp\Middleware::mapRequest(
    //         static fn (Psr\Http\Message\RequestInterface $request) => $request
    //     ),
    // )
    ->send($message)
    ->dump()
    // ->throw()
    ->json();
```

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](.github/CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

* [guanguans](https://github.com/guanguans)
* [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE) for more information.
