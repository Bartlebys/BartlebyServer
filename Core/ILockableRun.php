<?php

namespace Bartleby\Core;
use Closure;

interface  ILockableRun{
    /**
     * Runs a locked closure
     * @param Closure $closure
     * @param $lockName
     */
    static public function run(Closure $closure, $lockName);

    /**
     * Runs a locked closure closure protected by a locking mechanism or not
     * @return null
     * @throws \Exception
     */
    public function runClosure();
}
