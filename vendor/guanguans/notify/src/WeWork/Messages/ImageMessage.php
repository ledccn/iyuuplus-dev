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

namespace Guanguans\Notify\WeWork\Messages;

use GuzzleHttp\RequestOptions;

/**
 * @method self image($image)
 */
class ImageMessage extends Message
{
    protected array $defined = [
        'image',
    ];

    public function toHttpOptions(): array
    {
        return [
            RequestOptions::JSON => [
                'msgtype' => $this->type(),
                $this->type() => [
                    'base64' => base64_encode_file($image = $this->getValidatedOption('image')),
                    'md5' => md5_file($image),
                ],
            ],
        ];
    }

    protected function type(): string
    {
        return 'image';
    }
}
