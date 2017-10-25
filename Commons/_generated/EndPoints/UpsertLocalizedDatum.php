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

class  UpsertLocalizedDatumCallData extends MongoCallDataRawWrapper {
	const localizedDatum='localizedDatum';
}

 class  UpsertLocalizedDatum extends MongoEndPoint {

    function call() {
        /* @var UpsertLocalizedDatumCallData */
        $parameters=$this->getModel();
        $db=$this->getDB();
        /* @var \MongoCollection */
        $collection = $db->localizedData;
         // Write Acknowlegment policy
        $options = $this->getConfiguration()->getDefaultMongoWriteConcern();
        $options['upsert']=true;
        $obj=$parameters->getValueForKey(UpsertLocalizedDatumCallData::localizedDatum);
         if(!isset($obj) || count($parameters->getDictionary())==0){
          return new JsonResponse('Invalid void object',406);
        }
        // Inject the rootUID and the spaceUID in any entity
        $obj[OBSERVATION_UID_KEY]=$this->getObservationUID(false);
        $obj[SPACE_UID_KEY]=$this->getSpaceUID(false);
        $q = array (MONGO_ID_KEY =>$obj[MONGO_ID_KEY]);
        try {
            // Upsert via a locked Closure
            /* @var $mongoAction \Closure*/
            $mongoAction=function () use($collection,$q,$obj,$options) {
                return $collection->update( $q, $obj,$options);
            };
            $lockName='localizedData';
            $r=RunUnlocked::run($mongoAction,$lockName);
            
            if ($r['ok']==1) {
                $s=$this->responseStringWithTriggerInformations($this->createTrigger($parameters),NULL);
                return new JsonResponse($s,200);
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
     * @param UpsertLocalizedDatumCallData $parameters
     * @return array composed of two components
     *  [0] is an int
     *      -2 if the trigger relay has been discarded
     *      -1 if an error has occured
     *      > 0 the trigger index on success
     *  [1] correspond to the requestDuration
     * @throws \Exception
     */
    function createTrigger(UpsertLocalizedDatumCallData $parameters){
        $ref=$parameters->getValueForKey(UpsertLocalizedDatumCallData::localizedDatum);
        $homologousAction="ReadLocalizedDatumById";
        $senderUID=$this->getCurrentUserID($this->getSpaceUID(true));
        return $this->relayTrigger($senderUID,"localizedData","UpsertLocalizedDatum",$homologousAction,$ref,false);
    }
    
}
?>