<?php

namespace Workerman\Http;

use Revolt\EventLoop;
use Throwable;
use Workerman\Http\Response;
use Workerman\Http\Client;

/**
 * parallel client request
 */
#[\AllowDynamicProperties]
class ParallelClient extends Client
{
    protected $_buffer_queues = [];

    public function push(string $url, array $options = [])
    {
        $this->_buffer_queues[] = [$url, $options];
    }

    public function batch(array $set)
    {
        $this->_buffer_queues = array_merge($this->_buffer_queues, $set);
    }

    public function await(bool $errorThrow = false): array
    {
        if(!class_exists(EventLoop::class, false)) {
            throw new \RuntimeException('Please install revolt/event-loop to use parallel client.');
        }

        $queues = $this->_buffer_queues;

        $result = [];

        $suspensionArr = array_fill(0, count($queues), EventLoop::getSuspension());

        foreach ($queues as $index => $each) {
            $suspension = $suspensionArr[$index];
            $options = $each[1];

            $options['success'] = function ($response) use (&$result, &$suspension, $options, $index) {
                $result[$index] = [true, $response];
                $suspension->resume();
                // custom callback
                if (!empty($options['success'])) {
                    call_user_func($options['success'], $response);
                }
            };

            $options['error'] = function ($exception) use (&$result, &$suspension, $errorThrow, $options, $index) {
                $result[$index] = [false, $exception];
                try {
                    if ($errorThrow) {
                        $suspension->throw($exception);
                    } else {
                        $suspension->resume();
                    }
                } catch (Throwable $e) {
                    unset($suspension);
                }
                // custom callback
                if (!empty($options['error'])) {
                    call_user_func($options['error'], $exception);
                }
            };

            $this->request($each[0], $options);
        }

        foreach ($suspensionArr as $index => $suspension) {
            $suspension->suspend();
        }

        ksort($result);
        return $result;
    }

    #[\Override]
    protected function deferError($options, $exception)
    {
        if (!empty($options['error'])) {
            call_user_func($options['error'], $exception);
        }
    }

}
