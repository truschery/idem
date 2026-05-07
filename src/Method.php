<?php

namespace Truschery\Idem;

use Truschery\Idem\Attributes\IdempotencyKey;
use Truschery\Idem\Contracts\IdempotencyStrategyInterface;
use Truschery\Idem\Exceptions\LockWaitExceededException;

class Method
{
    public function __construct(
        private IdempotencyStrategyInterface $strategy,
    ){}

    /**
     * @throws LockWaitExceededException
     */
    public function deed(string $key, \Closure $callback, ?int $timeout = 10)
    {
        // TODO: Нужно еще реализовать проверку хеша параметров
        $record = $this->strategy->get($key);

        if($record->isReplayed){
            return $record->response;
        }

        $this->waitForLock($key, $timeout);

        $record = $this->strategy->get($key);
        if($record->isReplayed){
            $this->strategy->releaseLock($key);
            return $record->response;
        }

        try {
            $response = $callback();
            $this->strategy->save($key, $response);

            return $response;
        }finally{
            $this->strategy->releaseLock($key);
        }
    }

    /**
     * @throws LockWaitExceededException
     */
    private function waitForLock(string $key, int $timeout): void
    {
        $startTime = time();

        while(true){

            if($this->strategy->acquireLock($key)){
                return;
            }

            if(time() - $startTime > $timeout){
                throw new LockWaitExceededException;
            }

            usleep(100000);
        }
    }

}