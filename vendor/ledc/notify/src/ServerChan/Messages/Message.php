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

namespace Guanguans\Notify\ServerChan\Messages;

/**
 * @method self channel($channel)
 * @method self desp($desp)
 * @method self noip($noip)
 * @method self openid($openid)
 * @method self short($short)
 * @method self title($title)
 */
class Message extends \Guanguans\Notify\Foundation\Message
{
    protected array $defined = [
        'title',
        'desp',
        'short',
        'noip',
        'channel',
        'openid',

        // 'encoded',
        // 'key',
        // 'uid',
    ];

    public function toHttpUri(): string
    {
        return '{token}.send';
    }

    /**
     * @codeCoverageIgnore
     *
     * @noinspection EncryptionInitializationVectorRandomnessInspection
     */
    private function encrypt(string $content, string $key, string $iv): string
    {
        $key = substr(md5($key), 0, 16);
        $iv = substr(md5($iv), 0, 16);

        return openssl_encrypt(base64_encode($content), 'AES-128-CBC', $key, 0, $iv);
    }
}
