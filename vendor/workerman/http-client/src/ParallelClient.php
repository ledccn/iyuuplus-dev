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

    public function await(bool $errorThrow = true): array
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
            $options['success'] = function ($response) use ($suspension) {
                $suspension->resume($response);
            };
            $options['error'] = function ($response) use ($suspension, $errorThrow) {
                if ($errorThrow) {
                    $suspension->throw($response);
                } else {
                    $suspension->resume($response);
                }
            };

            $this->request($each[0], $options);
        }

        foreach ($suspensionArr as $index => $suspension) {
            $response = $suspension->suspend();
            switch (get_class($response)) {
                case Response::class:
                    $result[$index] = [true, $response];
                    break;
                case Throwable::class:
                    $result[$index] = [false, $response];
                    break;
                default:
                    $result[$index] = [false, null];
                    break;
            }
        }

        return $result;
    }

}
