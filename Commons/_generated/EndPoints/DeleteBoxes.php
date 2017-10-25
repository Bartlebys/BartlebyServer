<?php
/**
* Generated by BARTLEBY'S Flexions for [Benoit Pereira da Silva] (https://pereira-da-silva.com/contact) on ?
* https://github.com/Bartlebys
*
* DO NOT MODIFY THIS FILE YOUR MODIFICATIONS WOULD BE ERASED ON NEXT GENERATION!
*
* Copyright (c) 2016  [Bartleby's org] (https://bartlebys.org)   All rights reserved.
*/

namespace Bartleby\EndPoints;

require_once BARTLEBY_ROOT_FOLDER . 'Core/KeyPath.php';
//require_once BARTLEBY_ROOT_FOLDER.'Core/RunAndLock.php';
require_once BARTLEBY_ROOT_FOLDER.'Core/RunUnlocked.php';
require_once BARTLEBY_ROOT_FOLDER . 'Mongo/MongoEndPoint.php';
require_once BARTLEBY_ROOT_FOLDER . 'Mongo/MongoCallDataRawWrapper.php';
require_once BARTLEBY_PUBLIC_FOLDER . 'Configuration.php';

use Bartleby\Mongo\MongoEndPoint;
use Bartleby\Mongo\MongoCallDataRawWrapper;
use Bartleby\Core\JsonResponse;
use \MongoCollection;
use Bartleby\Configuration;
use Bartleby\Core\KeyPath;
use Closure;
//use Bartleby\Core\RunAndLock;  // To keep the generated code we have replaced RunAndLock by RunUnlocked
use Bartleby\Core\RunUnlocked;

class  DeleteBoxesCallData extends MongoCallDataRawWrapper {
	const ids='ids';
}

 class  DeleteBoxes extends MongoEndPoint {

    function call() {
        /* @var DeleteBoxesCallData */
        $parameters=$this->getModel();
        $db=$this->getDB();
        /* @var \MongoCollection */
        $collection = $db->boxes;
        // Write Acknowlegment policy
        $options = $this->getConfiguration()->getDefaultMongoWriteConcern();
        $ids=$parameters->getValueForKey(DeleteBoxesCallData::ids);
        if(isset ($ids) && count($ids)>0){
            $q = array( MONGO_ID_KEY =>array( '$in' => $ids ));
        }else{
            $s=$this->responseStringWithTriggerInformations($this->createTrigger($parameters),NULL);
            return new JsonResponse($s,204);
        }
        try {
             
            //Delete via a locked Closure
            /* @var $mongoAction \Closure*/
            $mongoAction=function () use($collection,$q,$options) {
                return $collection->remove ( $q,$options );
            };
            $lockName='boxes';
            $r=RunUnlocked::run($mongoAction,$lockName);
            
             if ($r['ok']==1) {
                 $hasBeenRemoved=($r['n'] >= 1);
                 if( $hasBeenRemoved ){
                     $s=$this->responseStringWithTriggerInformations($this->createTrigger($parameters),$hasBeenRemoved?NULL:'Already deleted');
                     return new JsonResponse($s,200);
                 }else if  ($this->getConfiguration()->IGNORE_MULTIPLE_DELETION_ATTEMPT() === true) {
                      return new JsonResponse('Already deleted',200);
                 }else{
                     return new JsonResponse(VOID_RESPONSE,404);
                 }
            } else {
                return new JsonResponse($r,412);
            }
        } catch ( \Exception $e ) {
            return new JsonResponse( [  'code'=>$e->getCode(),
                                        'message'=>$e->getMessage(),
                                        'file'=>$e->getFile(),
                                        'line'=>$e->getLine(),
                                        'trace'=>$e->getTraceAsString()
                                     ],
                                     417
                                    );
        }
     }
    
    /**
     * Creates and relay the action using a trigger
     * 
     * @param DeleteBoxesCallData $parameters
     * @return array composed of two components
     *  [0] is an int
     *      -2 if the trigger relay has been discarded
     *      -1 if an error has occured
     *      > 0 the trigger index on success
     *  [1] correspond to the requestDuration
     * @throws \Exception
     */
    function createTrigger(DeleteBoxesCallData $parameters){
        $ref=$parameters->getValueForKey(DeleteBoxesCallData::ids);
        $homologousAction="DeleteBoxes";
        $senderUID=$this->getCurrentUserID($this->getSpaceUID(true));
        return $this->relayTrigger($senderUID,"boxes","DeleteBoxes",$homologousAction,$ref,true);
    }
    
}
?>