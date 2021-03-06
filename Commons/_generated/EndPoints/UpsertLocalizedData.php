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

class  UpsertLocalizedDataCallData extends MongoCallDataRawWrapper {
	const localizedData='localizedData';
}

 class  UpsertLocalizedData extends MongoEndPoint {

    function call() {
        /* @var UpsertLocalizedDataCallData */
        $parameters=$this->getModel();
        $db=$this->getDB();
        /* @var \MongoCollection */
        $collection = $db->localizedData;
         // Write Acknowlegment policy
        $options = $this->getConfiguration()->getDefaultMongoWriteConcern();
        $options['upsert']=true;
        $arrayOfObject=$parameters->getValueForKey(UpsertLocalizedDataCallData::localizedData);
        if(!isset($arrayOfObject) || (is_array($arrayOfObject) && count($arrayOfObject)<1) ){
            return new JsonResponse('Invalid void array',406);
        }
        try {
              // MULTIPLE UPDATE With one Lock would me more efficient (!) https://docs.mongodb.com/manual/reference/method/db.collection.update/
              $observationUID=$this->getObservationUID(false);
              $spaceUID=$this->getSpaceUID(false);
              foreach ($arrayOfObject as $obj){
                // Inject the rootUID and the spaceUID in any entity
                $obj[OBSERVATION_UID_KEY]=$observationUID;
                $obj[SPACE_UID_KEY]=$spaceUID;
                $q = array (MONGO_ID_KEY => $obj[MONGO_ID_KEY]);
                // Simple Upsert via a locked Closure
                /* @var $mongoAction \Closure*/
                $mongoAction=function () use($collection,$q,$obj,$options) {
                    return $collection->update( $q, $obj,$options);
                };
                $lockName='localizedData';
                $r=RunUnlocked::run($mongoAction,$lockName);

                if ($r['ok']!=1) {
                    return new JsonResponse($q,412);
                }
             }
             $s=$this->responseStringWithTriggerInformations($this->createTrigger($parameters),NULL);
            return new JsonResponse($s,200);

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
     * @param UpsertLocalizedDataCallData $parameters
     * @return array composed of two components
     *  [0] is an int
     *      -2 if the trigger relay has been discarded
     *      -1 if an error has occured
     *      > 0 the trigger index on success
     *  [1] correspond to the requestDuration
     * @throws \Exception
     */
    function createTrigger(UpsertLocalizedDataCallData $parameters){
        $ref=$parameters->getValueForKey(UpsertLocalizedDataCallData::localizedData);
        $homologousAction="ReadLocalizedDataById";
        $senderUID=$this->getCurrentUserID($this->getSpaceUID(true));
        return $this->relayTrigger($senderUID,"localizedData","UpsertLocalizedData",$homologousAction,$ref,true);
    }
    
}
?>