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

use Guanguans\Notify\Foundation\Concerns\AsMultipart;

/**
 * @method self media($media)
 * @method self type($type)
 */
class UploadMediaMessage extends \Guanguans\Notify\Foundation\Message
{
    use AsMultipart;
    protected array $defined = [
        'media',
        'type',
    ];
    protected array $allowedValues = [
        // 'type' => ['voice', 'file'],
    ];

    public function toHttpUri(): string
    {
        return "cgi-bin/webhook/upload_media?key={token}&type={$this->getOption('type')}";
    }
}
