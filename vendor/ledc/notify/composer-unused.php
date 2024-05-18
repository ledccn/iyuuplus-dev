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

use ComposerUnused\ComposerUnused\Configuration\Configuration;
use ComposerUnused\ComposerUnused\Configuration\NamedFilter;
use ComposerUnused\ComposerUnused\Configuration\PatternFilter;
use Webmozart\Glob\Glob;

return static fn (Configuration $configuration): Configuration => $configuration
    // ->addNamedFilter(NamedFilter::fromString('symfony/config'))
    // ->addPatternFilter(PatternFilter::fromString('/symfony\/.*/'))
    ->setAdditionalFilesFor(
        'icanhazstring/composer-unused',
        array_merge(
            Glob::glob(__DIR__.'/*.php'),
            Glob::glob(__DIR__.'/.*.php'),
        ),
    );
