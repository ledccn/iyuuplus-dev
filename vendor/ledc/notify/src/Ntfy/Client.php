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

namespace Guanguans\Notify\Ntfy;

use Guanguans\Notify\Foundation\Contracts\Authenticator;

/**
 * @see https://ntfy.sh
 * @see https://docs.ntfy.sh/publish/
 */
class Client extends \Guanguans\Notify\Foundation\Client
{
    public function __construct(?Authenticator $authenticator = null)
    {
        parent::__construct($authenticator);
        $this->baseUri('https://ntfy.sh/');
    }
}
