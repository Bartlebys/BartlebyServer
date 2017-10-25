<?php
/**
 * Created by PhpStorm.
 * User: bpds
 * Date: 08/07/2016
 * Time: 09:31
 */

namespace Bartleby\EndPoints;

require_once BARTLEBY_ROOT_FOLDER . 'Mongo/MongoEndPoint.php';
require_once BARTLEBY_ROOT_FOLDER . 'Mongo/MongoCallDataRawWrapper.php';

use Bartleby\Core\JsonResponse;
use Bartleby\mongo\MongoCallDataRawWrapper;
use Bartleby\Mongo\MongoEndPoint;


final class StatsCallData extends MongoCallDataRawWrapper {

    const filterByObservationUID='filterByObservationUID';
}

final class Stats extends MongoEndPoint{

    /**
     * Returns the whole data space.
     */
    function GET(){
        $collectionsNames=$this->getConfiguration()->getCollectionsNameList();
        $dataSet=[];
        $spaceUID=$this->getSpaceUID(false);

        /* @var ExportCallData */
        $parameters=$this->getModel();
        $db=$this->getDB();

        $q = [SPACE_UID_KEY =>$spaceUID];
        $observationUID = $parameters->getValueForKey(StatsCallData::filterByObservationUID);
        $excludeTriggers=false;
        if (isset($observationUID)){
            $q[OBSERVATION_UID_KEY]=$observationUID;
        }

        $dataSet['db']=array();

        foreach ($collectionsNames as $collectionName) {
            if ($collectionName=="triggers" && $excludeTriggers){
                continue;
            }
            try {
                /* @var \MongoCollection */
                $collection = $db->{$collectionName};
                $dataSet['db'][$collectionName]=$collection->count($q);
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

        $dataSet['semaphores']=array();
        $r=shell_exec('ipcs -s');
        $lines=explode("\n",$r);
        $n=count($lines)-2;
        $start=false;
        for ($i=0;$i<$n;$i++){
            $a=explode(' ',$lines[$i]);
            if (count($a)>2){
                $firstComponent=$a[0];
                $semid=$a[0];
                if ($firstComponent=='key'){
                    $start=true;
                }
                if($start && strlen($firstComponent)>3){
                    $dataSet['semaphores'][]=$lines[$i];
                }
            }
        }
        $dataSet['semaphores.count']=count($dataSet['semaphores']);




        return new JsonResponse($dataSet,200);
    }

}