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

namespace Guanguans\Notify\Foundation\Authenticators;

use Guanguans\Notify\Foundation\Support\Utils;

class OptionsAuthenticator extends NullAuthenticator
{
    private array $options;

    public function __construct(array $options)
    {
        $this->options = $options;
    }

    public function applyToOptions(array $options): array
    {
        return Utils::mergeHttpOptions($options, $this->options);
    }
}
