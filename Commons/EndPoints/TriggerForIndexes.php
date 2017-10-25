<?php

namespace Bartleby\EndPoints;

require_once BARTLEBY_ROOT_FOLDER . 'Mongo/MongoEndPoint.php';
require_once BARTLEBY_PUBLIC_FOLDER . 'Configuration.php';

use Bartleby\Mongo\MongoEndPoint;
use Bartleby\Mongo\MongoCallDataRawWrapper;
use Bartleby\Core\JsonResponse;
use \MongoCollection;
use Bartleby\Configuration;

class  TriggerForIndexesCallData extends MongoCallDataRawWrapper {
    const indexes='indexes';
}

class  TriggerForIndexes extends MongoEndPoint {

    function call() {
        /* @var TriggerForIndexesCallData */
        $parameters=$this->getModel();
        $db=$this->getDB();
        /* @var \MongoCollection */
        $collection = $db->triggers;
        $indexes=$parameters->getValueForKey(TriggerForIndexesCallData::indexes);

        if(isset ($indexes) && is_array($indexes) && count($indexes)){

            $q = array( 'indexes'=>array( '$in' => $indexes ));

            ////////////////////////////////////////////
            // space and Observation UID confinements
            ////////////////////////////////////////////

            try {
                // Restrict to this spaceUID
                $q[SPACE_UID_KEY] = $this->getSpaceUID(false);
            } catch (\Exception $e) {
                return new JsonResponse("spaceUID is undefined", 412);
            }

            $observationUID = $this->getObservationUID(true);
            $observationUIDS = $this->getObservationUIDS(true);
            if (is_null($observationUID) && (is_null($observationUIDS) || !is_array($observationUIDS))){
                return new JsonResponse("observationUID and observationUIDS are undefined you must set one", 412);
            }
            // Observation UID (s)
            // We can use one observationUID or multiple ones.
            if (isset($observationUIDS) && is_array($observationUIDS)) {
                $q[OBSERVATION_UID_KEY] = array( MONGO_ID_KEY=>array( '$in' => $observationUIDS));
            }else if (isset($observationUID) && $observationUID!="" ) {
                $q[OBSERVATION_UID_KEY] = $observationUID;
            }

        }else{
            return new JsonResponse(VOID_RESPONSE,204);
        }
        try {
            
            $r=array();
            $cursor = $collection->find($q);
            if ($cursor->count ( TRUE ) > 0) {
                foreach ( $cursor as $obj ) {
                    $r[] = $obj;
                }
            }
            if (count($r)>0 ) {
                return new JsonResponse($r,200);
            } else {
                return new JsonResponse(VOID_RESPONSE,404);
            }
        } catch ( \Exception $e ) {
            return new JsonResponse( array ('code'=>$e->getCode(),
                'message'=>$e->getMessage(),
                'file'=>$e->getFile(),
                'line'=>$e->getLine(),
                'trace'=>$e->getTraceAsString()
            ),
                417
            );
        }
    }
}

?>