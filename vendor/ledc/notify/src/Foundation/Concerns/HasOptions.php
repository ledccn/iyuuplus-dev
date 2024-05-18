<?php

/** @noinspection MissingReturnTypeInspection */
/** @noinspection MissingParameterTypeDeclarationInspection */

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

use Guanguans\Notify\Foundation\Exceptions\BadMethodCallException;
use Guanguans\Notify\Foundation\Exceptions\InvalidArgumentException;
use Guanguans\Notify\Foundation\Support\Str;
use Guanguans\Notify\Foundation\Support\Utils;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @property-read array<string, mixed> $defaults // Support nested options.
 * @property-read list<string> $required
 * @property-read list<string> $defined
 * @property-read bool $ignoreUndefined // Required symfony/options-resolver >= 6.3
 * @property-read array<array-key, array|string> $deprecated
 * @property-read array<string, \Closure> $normalizers
 * @property-read array<string, mixed> $allowedValues
 * @property-read array<string, list<string>|string> $allowedTypes;
 * @property-read array<string, string> $infos
 *
 * @method array<string, mixed> defaults() // Support nested options.
 * @method array<int|string, array|string> deprecated()
 * @method array<string, \Closure> normalizers()
 */
trait HasOptions
{
    protected array $options = [];

    /**
     * @throws \ReflectionException
     */
    public function __call(mixed $name, mixed $arguments)
    {
        $defined = Utils::definedFor($this);

        foreach ([null, 'snake', 'pascal', 'kebab'] as $case) {
            $casedName = $case ? Str::{$case}($name) : $name;

            if (\in_array($casedName, $defined, true)) {
                if (empty($arguments)) {
                    throw new InvalidArgumentException(
                        sprintf('The method [%s::%s] require an argument.', static::class, $name),
                    );
                }

                return $this->setOption($casedName, $arguments[0]);
            }
        }

        throw new BadMethodCallException(sprintf('The method [%s::%s] does not exist.', static::class, $name));
    }

    public function setOption(string $option, $value): self
    {
        $this->options[$option] = $value;

        return $this;
    }

    public function setOptions(array $options): self
    {
        $this->options = array_merge($this->options, $options);

        return $this;
    }

    public function getOption(string $option, $default = null)
    {
        return $this->options[$option] ?? $default;
    }

    public function getOptions(): array
    {
        return $this->options;
    }

    public function getValidatedOption(string $option, $default = null)
    {
        return $this->getValidatedOptions()[$option] ?? $default;
    }

    public function getValidatedOptions(): array
    {
        return $this->configureAndResolveOptions($this->options, function (OptionsResolver $optionsResolver): void {
            $this->preConfigureOptionsResolver($optionsResolver);
            $this->configureOptionsResolver($optionsResolver);
        });
    }

    public function offsetExists($offset): bool
    {
        return isset($this->options[$offset]);
    }

    public function offsetGet($offset): mixed
    {
        return $this->getOption($offset);
    }

    public function offsetSet($offset, $value): void
    {
        $this->setOption($offset, $value);
    }

    public function offsetUnset($offset): void
    {
        unset($this->options[$offset]);
    }

    protected function configureAndResolveOptions(array $options, callable $callback): array
    {
        $optionsResolver = new OptionsResolver;

        $callback($optionsResolver);

        return $optionsResolver->resolve($options);
    }

    protected function configureOptionsResolver(OptionsResolver $optionsResolver): void
    {
        // Configure options resolver...
    }

    private function preConfigureOptionsResolver(OptionsResolver $optionsResolver): void
    {
        $optionsResolver->setDefaults(array_merge(
            property_exists($this, 'defaults') ? $this->defaults : [],
            method_exists($this, 'defaults') ? $this->defaults() : [],
        ));

        property_exists($this, 'required') and $optionsResolver->setRequired($this->required);
        property_exists($this, 'defined') and $optionsResolver->setDefined($this->defined);

        // // A prototype option can only be defined inside a nested option and during its resolution it will expect an array of arrays.
        // property_exists($this, 'prototype') and $optionsResolver->setPrototype($this->prototype);

        // Required symfony/options-resolver >= 6.3
        if (property_exists($this, 'ignoreUndefined') && method_exists($optionsResolver, 'setIgnoreUndefined')) {
            $optionsResolver->setIgnoreUndefined($this->ignoreUndefined); // @codeCoverageIgnore
        }

        $deprecated = array_merge(
            property_exists($this, 'deprecated') ? $this->deprecated : [],
            method_exists($this, 'deprecated') ? $this->deprecated() : [],
        );

        foreach ($deprecated as $option => $arguments) {
            // Required symfony/options-resolver < 6.0
            \is_string($arguments) and $arguments = [$arguments];

            \is_string($option) and array_unshift($arguments, $option);

            $optionsResolver->setDeprecated(...array_pad($arguments, 3, ''));
        }

        $normalizers = array_merge(
            property_exists($this, 'normalizers') ? $this->normalizers : [],
            method_exists($this, 'normalizers') ? $this->normalizers() : [],
        );

        foreach ($normalizers as $option => $normalizer) {
            $optionsResolver->setNormalizer($option, $normalizer);
        }

        if (property_exists($this, 'allowedValues')) {
            foreach ($this->allowedValues as $option => $allowedValue) {
                $optionsResolver->setAllowedValues($option, $allowedValue);
            }
        }

        if (property_exists($this, 'allowedTypes')) {
            foreach ($this->allowedTypes as $option => $allowedType) {
                $optionsResolver->setAllowedTypes($option, $allowedType);
            }
        }

        if (property_exists($this, 'infos')) {
            foreach ($this->infos as $option => $info) {
                $optionsResolver->setInfo($option, $info);
            }
        }
    }
}
