<?php

namespace Bartleby\Core;
require_once __DIR__ . '/ErrorCodes.php';
require_once __DIR__ . '/ILockableRun.php';

use Closure;
use Bartleby\Core\ErrorCodes;


/**
 * Class RunAndLock
 *
 * You can call : `sysctl -a |grep kernel\.sem` to know the current semaphore limit for the server.
 *
 * `sysctl -a |grep kernel\.sem`
 * `ipcs -s`
 *
 * "    root@chaosmos:~# sysctl -a |grep kernel\.sem "
 * "    kernel.sem = 250	32000	32	128 "
 *
 * 250 is a usual value
 *
 *
 *  Usage sample :
 *
 *      $mongoAction=function () use($collection,$obj,$options){
 *           return $collection->insert ( $obj,$options );
 *      };
 *      $lockName='users';
 *      $result=RunAndLock::run($mongoAction,$lockName);
 *
 * @package Bartleby\Core
 */
class RunAndLock implements ILockableRun {


    /**
     * @var Closure
     */
    private $_closure;

    /**
     * @var string the lock name best practice "collectionName.spaceUID"
     */
    private $_lockName;

    /**
     * Runs a locked closure
     * @param Closure $closure
     * @param $lockName
     */
    static public function run(Closure $closure, $lockName) {
        $runner = new RunAndLock($closure, $lockName);
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
        $result = NULL;
        if (function_exists('sem_get')) { // Test semaphore support
            // get the resource for the semaphore
            $semResource = @sem_get($this->_lockName, 1, 0666, 1);
            // If the resource is not valid.
            if ($semResource === false) {
                // TODO send an admin alert ( via traces )
                throw new \Exception('Unable get semaphore\'s resource try to determine if it is a semaphore limit for the server : \'sysctl -a |grep kernel\.sem\' and \'ipcs -s\' to determine'. $this->_lockName, ErrorCodes::SEMAPHORES_ACQUISTION_FAILED);
                return;
            }
            if (@sem_acquire($semResource)) { // try to acquire the semaphore in case of success it will block until the sem will be available

                try {
                    $result = $this->_closure->__invoke();
                } catch (\Exception $e) {
                    $deferedException = $e;
                }
                // Release the semaphore in any case.
                @sem_release($semResource);

                // Rethrow the exception if necessary
                if (isset($deferedException)) {
                    throw $deferedException;
                }
                return $result;

            }else{
                throw new \Exception('Unable to acquire semaphore '.$this->_lockName,ErrorCodes::SEMAPHORES_ACQUISTION_FAILED);
                return ;
            }
        } else {
            // Semaphore support is required
            // Theres is no semaphore support
            throw new \Exception("Semaphores support is required",ErrorCodes::SEMAPHORES_ARE_NOT_AVAILABLE);
        }
    }

    private function _is_closure($t) {
        return is_object($t) && ($t instanceof Closure);
    }
}