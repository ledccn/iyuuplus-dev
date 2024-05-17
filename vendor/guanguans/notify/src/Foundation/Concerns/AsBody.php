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

namespace Guanguans\Notify\Foundation\Concerns;

use GuzzleHttp\RequestOptions;

/**
 * @mixin \Guanguans\Notify\Foundation\Message
 */
trait AsBody
{
    public function toHttpOptions(): array
    {
        return [
            RequestOptions::BODY => (string) $this,
        ];
    }
}
