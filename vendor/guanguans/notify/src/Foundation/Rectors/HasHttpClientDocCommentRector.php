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

namespace Guanguans\Notify\Foundation\Rectors;

use Guanguans\Notify\Foundation\Client;
use Guanguans\Notify\Foundation\Concerns\HasHttpClient;
use Guanguans\Notify\Foundation\Support\Str;
use Guanguans\Notify\Foundation\Support\Utils;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\RequestOptions;
use PhpParser\Node;
use PhpParser\Node\Stmt\Trait_;
use PHPStan\PhpDocParser\Ast\PhpDoc\GenericTagValueNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\PhpDocTagNode;
use Rector\BetterPhpDocParser\PhpDocInfo\PhpDocInfo;
use Rector\BetterPhpDocParser\PhpDocInfo\PhpDocInfoFactory;
use Rector\Comments\NodeDocBlock\DocBlockUpdater;
use Rector\Contract\Rector\ConfigurableRectorInterface;
use Rector\Rector\AbstractRector;
use Symplify\RuleDocGenerator\Exception\PoorDocumentationException;
use Symplify\RuleDocGenerator\Exception\ShouldNotHappenException;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\ConfiguredCodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;
use Webmozart\Assert\Assert;

/**
 * @internal
 */
final class HasHttpClientDocCommentRector extends AbstractRector implements ConfigurableRectorInterface
{
    private DocBlockUpdater $docBlockUpdater;
    private PhpDocInfoFactory $phpDocInfoFactory;
    private array $except = [
        '__*',
        'create',
        'hasHandler',
        'resolve',
        'getConfig',
    ];

    public function __construct(DocBlockUpdater $docBlockUpdater, PhpDocInfoFactory $phpDocInfoFactory)
    {
        $this->docBlockUpdater = $docBlockUpdater;
        $this->phpDocInfoFactory = $phpDocInfoFactory;
    }

    /**
     * @throws PoorDocumentationException
     * @throws ShouldNotHappenException
     */
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition(
            'Has http client doc comment',
            [
                new ConfiguredCodeSample(
                    <<<'CODE_SAMPLE'
                        trait HasHttpClient {}
                        CODE_SAMPLE,
                    <<<'CODE_SAMPLE'
                        /**
                         * @method self setHandler(callable $handler)
                         * @method self unshift(callable $middleware, string $name = null)
                         * ...
                         * @method self remove($remove)
                         * @method self allowRedirects($allowRedirects)
                         * ...
                         * @method self version($version)
                         *
                         * @see \GuzzleHttp\HandlerStack
                         * @see \GuzzleHttp\RequestOptions
                         *
                         * @mixin \Guanguans\Notify\Foundation\Client
                         */
                        CODE_SAMPLE,
                    ['__*' => '__*'],
                ),
            ],
        );
    }

    public function configure(array $configuration): void
    {
        Assert::allStringNotEmpty($configuration);
        $this->except = array_merge($this->except, $configuration);
    }

    public function getNodeTypes(): array
    {
        return [Trait_::class];
    }

    /**
     * @psalm-suppress MoreSpecificImplementedParamType
     *
     * @param Trait_ $node
     *
     * @throws \ReflectionException
     */
    public function refactor(Node $node)
    {
        /** @var class-string $trait */
        $trait = $node->getAttribute('scope')->getClassReflection()->getName();

        if (HasHttpClient::class !== $trait) {
            return;
        }

        $node->setAttribute('comments', []);
        $phpDocInfo = $this->phpDocInfoFactory->createFromNodeOrEmpty($node);

        $this->addMixinDoc($phpDocInfo);
        $this->addRequestOptionsDoc($phpDocInfo);

        $phpDocInfo->addPhpDocTagNode($this->createEmptyDocTagNode());
        $phpDocInfo->addPhpDocTagNode(new PhpDocTagNode('@see', new GenericTagValueNode('\\'.HandlerStack::class)));
        $phpDocInfo->addPhpDocTagNode(new PhpDocTagNode('@see', new GenericTagValueNode('\\'.RequestOptions::class)));
        $phpDocInfo->addPhpDocTagNode($this->createEmptyDocTagNode());
        $phpDocInfo->addPhpDocTagNode(new PhpDocTagNode('@mixin', new GenericTagValueNode('\\'.Client::class)));

        $this->docBlockUpdater->updateRefactoredNodeWithPhpDocInfo($node);

        return $node;
    }

    /**
     * @throws \ReflectionException
     */
    private function addMixinDoc(PhpDocInfo $phpDocInfo): void
    {
        $reflectionMethods = array_filter(
            (new \ReflectionClass(HandlerStack::class))->getMethods(\ReflectionMethod::IS_PUBLIC),
            fn (\ReflectionMethod $reflectionMethod): bool => !Str::is($this->except, $reflectionMethod->getName()),
        );

        foreach ($reflectionMethods as $reflectionMethod) {
            $phpDocInfo->addPhpDocTagNode($this->createMethodPhpDocTagNode($reflectionMethod));
        }
    }

    private function addRequestOptionsDoc(PhpDocInfo $phpDocInfo): void
    {
        foreach (Utils::httpOptionConstants() as $constant) {
            $name = Str::camel($constant);
            $phpDocInfo->addPhpDocTagNode(new PhpDocTagNode('@method', new GenericTagValueNode("self $name(\$$name)")));
        }
    }

    private function createEmptyDocTagNode(): PhpDocTagNode
    {
        return new PhpDocTagNode('', new GenericTagValueNode(''));
    }

    /**
     * @throws \ReflectionException
     */
    private function createMethodPhpDocTagNode(\ReflectionMethod $reflectionMethod): PhpDocTagNode
    {
        $static = $reflectionMethod->isStatic() ? 'static ' : '';

        $returnType = 'self ';

        $name = $reflectionMethod->getName();

        $parameters = rtrim(
            array_reduce(
                $reflectionMethod->getParameters(),
                static function (string $carry, \ReflectionParameter $reflectionParameter): string {
                    if ($reflectionParameter->hasType()) {
                        /** @noinspection PhpVoidFunctionResultUsedInspection */
                        $type = $reflectionParameter->getType();
                        \assert($type instanceof \ReflectionNamedType);
                        $type->isBuiltin() or $carry .= '\\';
                        $carry .= $type->getName().' ';
                    }

                    $carry .= '$'.$reflectionParameter->getName();

                    if ($reflectionParameter->isDefaultValueAvailable()) {
                        $defaultValue = $reflectionParameter->getDefaultValue();

                        /** @noinspection DebugFunctionUsageInspection */
                        $export = var_export($defaultValue, true);
                        null === $defaultValue and $export = 'null';
                        [] === $defaultValue and $export = '[]';

                        $carry .= ' = '.$export;
                    }

                    return $carry.', ';
                },
                '',
            ),
            ', ',
        );

        return new PhpDocTagNode('@method', new GenericTagValueNode("$static$returnType$name($parameters)"));
    }
}
