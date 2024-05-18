<?php

declare(strict_types=1);

/**
 * Copyright (c) 2021-2024 guanguans<ityaozm@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 *
 * @see https://github.com/guanguans/notify
 */

namespace Guanguans\Notify\YiFengChuanHua\Messages;

/**
 * @method self body($body)
 * @method self delayMilliseconds($delayMilliseconds)
 * @method self head($head)
 * @method self url($url)
 */
class Message extends \Guanguans\Notify\Foundation\Message
{
    protected array $required = [
        // 'head',
    ];
    protected array $defined = [
        'head',
        'body',
        'delayMilliseconds',
        'url',
    ];

    public function toHttpUri(): string
    {
        return 'services/push/send/{token}';
    }
}
