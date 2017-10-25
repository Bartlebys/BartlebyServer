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

class  CreateBoxesCallData extends MongoCallDataRawWrapper {
	const boxes='boxes';
}

 class  CreateBoxes extends MongoEndPoint {

    function call() {
        /* @var CreateBoxesCallData */
        $parameters=$this->getModel();
        $db=$this->getDB();
        /* @var \MongoCollection */
        $collection = $db->boxes;
        // Write Acknowlegment policy
        $options = $this->getConfiguration()->getDefaultMongoWriteConcern();
        $arrayOfObject=$parameters->getValueForKey(CreateBoxesCallData::boxes);
        if(!isset($arrayOfObject) || (is_array($arrayOfObject) && count($arrayOfObject)<1) ){
            return new JsonResponse('Invalid void array',406);
        }
        try {
                    
            // Inject the ObservationUID and the spaceUID in any entity
            $observationUID=$this->getObservationUID(false);
            $spaceUID=$this->getSpaceUID(false);
            foreach ($arrayOfObject as &$element) {
                if (is_array($element)){
                    $element[OBSERVATION_UID_KEY]=$observationUID;
                    $element[SPACE_UID_KEY]=$spaceUID;
                }
            }
            
            //Batch Insert via a locked Closure
            /* @var $mongoAction \Closure*/
            $mongoAction=function () use($collection,$arrayOfObject,$options) {
                return  $collection->batchInsert( $arrayOfObject,$options );
            };
            $lockName='boxes';
            $r=RunUnlocked::run($mongoAction,$lockName);
             if ($r['ok']==1) {
                $s=$this->responseStringWithTriggerInformations($this->createTrigger($parameters),NULL);
                return new JsonResponse($s,201);
            } else {
                return new JsonResponse($r,412);
            }
        } catch ( \Exception $e ) {
             
            // MONGO E11000 duplicate key error
            if ( $e->getCode() == 11000 && $this->getConfiguration()->IGNORE_MULTIPLE_CREATION_IN_CRUD_MODE() == true){
                // We return A 200 not a 201
                $s=$this->responseStringWithTriggerInformations($this->createTrigger($parameters),'This is not the first attempt.');
                return new JsonResponse($s,200);
            }
            
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
     * @param CreateBoxesCallData $parameters
     * @return array composed of two components
     *  [0] is an int
     *      -2 if the trigger relay has been discarded
     *      -1 if an error has occured
     *      > 0 the trigger index on success
     *  [1] correspond to the requestDuration
     * @throws \Exception
     */
    function createTrigger(CreateBoxesCallData $parameters){
        $ref=$parameters->getValueForKey(CreateBoxesCallData::boxes);
        $homologousAction="ReadBoxesById";
        $senderUID=$this->getCurrentUserID($this->getSpaceUID(true));
        return $this->relayTrigger($senderUID,"boxes","CreateBoxes",$homologousAction,$ref,true);
    }
    
}
?>