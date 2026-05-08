<?php

namespace Truschery\Idem;

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
    public function deed(IdempotencyKey $key, \Closure $callback, ?int $timeout = 10)
    {
        // TODO: Нужно еще реализовать проверку хеша параметров
        $record = $this->strategy->get($key);

        if($record->isReplayed){
            if($record->hash && $record->hash !== $key->hash){
                throw new \Exception('Mismatch hashes');
            }

            return $record->response;
        }

        $this->waitForLock($key, $timeout);

        $record = $this->strategy->get($key);

        if($record->isReplayed){
            if($record->hash && $record->hash !== $key->hash){
                throw new \Exception('Mismatch hashes');
            }

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
    private function waitForLock(IdempotencyKey $key, int $timeout): void
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