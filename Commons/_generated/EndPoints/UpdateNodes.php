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

class  UpdateNodesCallData extends MongoCallDataRawWrapper {
	const nodes='nodes';
}

 class  UpdateNodes extends MongoEndPoint {

    function call() {
        /* @var UpdateNodesCallData */
        $parameters=$this->getModel();
        $db=$this->getDB();
        /* @var \MongoCollection */
        $collection = $db->nodes;
        // Write Acknowlegment policy
        $options = $this->getConfiguration()->getDefaultMongoWriteConcern();
        $arrayOfObject=$parameters->getValueForKey(UpdateNodesCallData::nodes);
        if(!isset($arrayOfObject) || (is_array($arrayOfObject) && count($arrayOfObject)<1) ){
            return new JsonResponse('Invalid void array',406);
        }
        try {
            
            //Multiple Update via a locked Closure
            $spaceUID=$this->getSpaceUID(false);
            /* @var $mongoAction \Closure*/
            $mongoAction=function () use($spaceUID,$collection,$arrayOfObject,$options) {
                foreach ($arrayOfObject as $obj){     
                    $q = array (MONGO_ID_KEY => $obj[MONGO_ID_KEY]);
                    if (is_array($obj)){
                        // Inject the rootUID and the spaceUID in any entity
                        $obj[OBSERVATION_UID_KEY]=$this->getObservationUID(false);
                        $obj[SPACE_UID_KEY]=$spaceUID;
                    }
                    $r = $collection->update( $q, $obj,$options);
                    if ($r['ok']==1) {
                        if (array_key_exists('updatedExisting', $r)) {
                            $existed = $r['updatedExisting'];
                             if ($existed == false) {
                                 return new JsonResponse($q,404);
                             }
                        }
                    }else{
                        return new JsonResponse($q,412);
                    }
                }
                return null;
            };
            
            $lockName='nodes';
            $r=RunUnlocked::run($mongoAction,$lockName);
            
            // We return JsonResponse directly in case of failure in the closure.
            if(isset($r)){
               return $r;
            }else{
                $s=$this->responseStringWithTriggerInformations($this->createTrigger($parameters),NULL);
                return new JsonResponse($s,200);
            };

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
     * @param UpdateNodesCallData $parameters
     * @return array composed of two components
     *  [0] is an int
     *      -2 if the trigger relay has been discarded
     *      -1 if an error has occured
     *      > 0 the trigger index on success
     *  [1] correspond to the requestDuration
     * @throws \Exception
     */
    function createTrigger(UpdateNodesCallData $parameters){
        $ref=$parameters->getValueForKey(UpdateNodesCallData::nodes);
        $homologousAction="ReadNodesById";
        $senderUID=$this->getCurrentUserID($this->getSpaceUID(true));
        return $this->relayTrigger($senderUID,"nodes","UpdateNodes",$homologousAction,$ref,true);
    }
    
}
?>