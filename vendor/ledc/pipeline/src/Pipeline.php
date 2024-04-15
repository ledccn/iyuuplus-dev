<?php

namespace Ledc\Pipeline;

use Closure;
use Psr\Container\ContainerInterface;
use RuntimeException;
use Throwable;

/**
 * 管道（流水线）模式
 * - 源码来自：illuminate/pipeline
 */
class Pipeline
{
    /**
     * 容器实例
     * @var ContainerInterface|null
     */
    protected ?ContainerInterface $container = null;

    /**
     * 初始数据
     * - The object being passed through the pipeline.
     * @var mixed
     */
    protected mixed $passable;

    /**
     * 管道数组
     * - The array of class pipes.
     * @var array
     */
    protected array $pipes = [];

    /**
     * 要在每个管道上调用的方法
     * - The method to call on each pipe.
     * @var string
     */
    protected string $method = 'handle';

    /**
     * 异常处理器
     * @var callable|null
     */
    protected $exceptionHandler = null;

    /**
     * 构造函数
     * @param ContainerInterface|null $container
     */
    public function __construct(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    /**
     * 初始数据
     * - Set the object being sent through the pipeline.
     * @param mixed $passable
     * @return $this
     */
    public function send(mixed $passable): static
    {
        $this->passable = $passable;
        return $this;
    }

    /**
     * 调用栈
     * - Set the array of pipes.
     * @param array|mixed $pipes
     * @return $this
     */
    public function through(mixed $pipes): static
    {
        $this->pipes = is_array($pipes) ? $pipes : func_get_args();
        return $this;
    }

    /**
     * 增加管道
     * - Push additional pipes onto the pipeline.
     * @param array|mixed $pipes
     * @return $this
     */
    public function pipe(mixed $pipes): static
    {
        array_push($this->pipes, ...(is_array($pipes) ? $pipes : func_get_args()));
        return $this;
    }

    /**
     * 设置要在管道上调用的方法
     * - Set the method to call on the pipes.
     * @param string $method
     * @return $this
     */
    public function via(string $method): static
    {
        $this->method = $method;
        return $this;
    }

    /**
     * 执行
     * - Run the pipeline with a final destination callback.
     * @param Closure $destination 核心业务逻辑
     * @return mixed
     */
    public function then(Closure $destination): mixed
    {
        $pipeline = array_reduce(
            array_reverse($this->pipes()), $this->carry(), $this->prepareDestination($destination)
        );

        return $pipeline($this->passable);
    }

    /**
     * 执行后返回结果
     * - Run the pipeline and return the result.
     * @return mixed
     */
    public function thenReturn(): mixed
    {
        return $this->then(function ($passable) {
            return $passable;
        });
    }

    /**
     * 获取Closure洋葱模型的最后部分（最终的核心业务逻辑）
     * - Get the final piece of the Closure onion.
     * @param Closure $destination
     * @return Closure
     */
    protected function prepareDestination(Closure $destination): Closure
    {
        return function ($passable) use ($destination) {
            try {
                return $destination($passable);
            } catch (Throwable $e) {
                return $this->handleException($passable, $e);
            }
        };
    }

    /**
     * 获取符合洋葱模型的闭包
     * - Get a Closure that represents a slice of the application onion.
     * @return Closure
     */
    protected function carry(): Closure
    {
        return function ($stack, $pipe) {
            return function ($passable) use ($stack, $pipe) {
                try {
                    if (is_callable($pipe)) {
                        // If the pipe is a callable, then we will call it directly, but otherwise we
                        // will resolve the pipes out of the dependency container and call it with
                        // the appropriate method and arguments, returning the results back out.
                        return $pipe($passable, $stack);
                    } elseif (!is_object($pipe)) {
                        [$name, $parameters] = $this->parsePipeString($pipe);

                        // If the pipe is a string we will parse the string and resolve the class out
                        // of the dependency injection container. We can then build a callable and
                        // execute the pipe function giving in the parameters that are required.
                        $container = $this->getContainer();
                        if (method_exists($container, 'make')) {
                            $pipe = $container->make($name);
                        } else {
                            throw new RuntimeException('容器缺少make方法.');
                        }

                        $parameters = array_merge([$passable, $stack], $parameters);
                    } else {
                        // If the pipe is already an object we'll just make a callable and pass it to
                        // the pipe as-is. There is no need to do any extra parsing and formatting
                        // since the object we're given was already a fully instantiated object.
                        $parameters = [$passable, $stack];
                    }

                    $carry = method_exists($pipe, $this->method)
                        ? $pipe->{$this->method}(...$parameters)
                        : $pipe(...$parameters);

                    return $this->handleCarry($carry);
                } catch (Throwable $e) {
                    return $this->handleException($passable, $e);
                }
            };
        };
    }

    /**
     * 解析完整的管道字符串
     * - Parse full pipe string to get name and parameters.
     * @param string $pipe
     * @return array
     */
    protected function parsePipeString(string $pipe): array
    {
        [$name, $parameters] = array_pad(explode(':', $pipe, 2), 2, []);

        if (is_string($parameters)) {
            $parameters = explode(',', $parameters);
        }

        return [$name, $parameters];
    }

    /**
     * 获取管道数组
     * - Get the array of configured pipes.
     * @return array
     */
    protected function pipes(): array
    {
        return $this->pipes;
    }

    /**
     * 获取容器实例
     * - Get the container instance.
     * @return ContainerInterface
     * @throws RuntimeException
     */
    protected function getContainer(): ContainerInterface
    {
        if (!$this->container) {
            throw new RuntimeException('容器实例没有传递给Pipeline.');
        }
        return $this->container;
    }

    /**
     * 设置容器实例
     * - Set the container instance.
     *
     * @param ContainerInterface $container
     * @return $this
     */
    public function setContainer(ContainerInterface $container): static
    {
        $this->container = $container;
        return $this;
    }

    /**
     * 在将每个管道返回的值传递给下一个管道之前处理它
     * - Handle the value returned from each pipe before passing it to the next.
     * @param mixed $carry
     * @return mixed
     */
    protected function handleCarry(mixed $carry): mixed
    {
        return $carry;
    }

    /**
     * 设置异常处理器
     * @param callable $handler
     * @return $this
     */
    public function whenException(callable $handler): static
    {
        $this->exceptionHandler = $handler;
        return $this;
    }

    /**
     * 异常处理
     * - Handle the given exception.
     * @param mixed $passable
     * @param Throwable $e
     * @return mixed
     * @throws Throwable
     */
    protected function handleException(mixed $passable, Throwable $e): mixed
    {
        if ($this->exceptionHandler) {
            return call_user_func($this->exceptionHandler, $passable, $e);
        }
        throw $e;
    }
}
