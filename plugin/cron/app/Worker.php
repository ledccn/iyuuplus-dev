<?php

namespace plugin\cron\app;

use Exception;

/**
 * Worker.
 */
class Worker extends \Workerman\Worker
{
    /**
     * Run worker instance.
     * @return void
     * @throws Exception
     */
    public function run(): void
    {
        $this->listen();

        // Try to emit onWorkerStart callback.
        if ($this->onWorkerStart) {
            try {
                \call_user_func($this->onWorkerStart, $this);
            } catch (\Exception|\Error|\Throwable $e) {
                // Avoid rapid infinite loop exit.
                sleep(1);
                static::stopAll(250, $e);
            }
        }
    }
}
