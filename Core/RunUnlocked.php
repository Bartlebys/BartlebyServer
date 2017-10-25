<?php

namespace Bartleby\Core;
require_once __DIR__ . '/ILockableRun.php';
use Closure;

// Used to preserve some generative code
// This class does lock while running the closure
class RunUnlocked implements  ILockableRun {

    /**
     * @var Closure
     */
    private $_closure;

    /**
     * NOT used in this implementation
     * @var string the lock name best practice "collectionName.spaceUID"
     */
    private $_lockName;

    /**
     * Runs a locked closure
     * @param Closure $closure
     * @param $lockName
     */
    static public  function run(Closure $closure, $lockName) {
        $runner = new RunUnlocked($closure, $lockName);
        return $runner->runClosure();
    }

    /**
     * RunAndLock constructor.
     * @param Closure $closure
     * @param string $lockName
     */
    public function __construct(Closure $closure, $lockName) {
        if (!isset($lockName) || !isset($lockName)) {
            $lockName = 'DEFAULT';
        }
        $this->_closure = $closure;
        $this->_lockName = crc32($lockName);
    }

    /**
     * Runs a locked closure closure protected by a semaphore
     * @return null
     * @throws \Exception
     */
    public function runClosure() {
        return $this->_closure->__invoke();
    }
}