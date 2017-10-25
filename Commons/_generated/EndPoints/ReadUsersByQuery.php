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

class  ReadUsersByQueryCallData extends MongoCallDataRawWrapper {
	const result_fields='result_fields';
	/* the sort (MONGO DB) */
	const sort='sort';
	/* the query (MONGO DB) */
	const query='query';
}

 class  ReadUsersByQuery extends MongoEndPoint {

     function call() {
        /* @var ReadUsersByQueryCallData */
        $parameters=$this->getModel();
        $db=$this->getDB();
        /* @var \MongoCollection */
        $collection = $db->users;
      $q = $parameters->getValueForKey(ReadUsersByQueryCallData::query);
       if(!isset($q)){
           return new JsonResponse(VOID_RESPONSE,417);
       }
       $f=$parameters->getValueForKey(ReadUsersByQueryCallData::result_fields);
        try {
            $r=array();
            if(isset($f)){
                $cursor = $collection->find( $q , $f );
            }else{
                $cursor = $collection->find($q);
            }
           
            // Sort ?
            $s=$parameters->getCastedDictionaryForKey(ReadUsersByQueryCallData::sort);
            if (isset($s) && count($s)>0){
              $cursor=$cursor->sort($s);
            }
            
            if ($cursor->count ( TRUE ) > 0) {
               foreach ( $cursor as $obj ) {
                $r[] = $obj;
               }
            }
           
            if (count($r)>0 ) {
                 return new JsonResponse(array('found'=>$r),200);
            } else {
                return new JsonResponse(VOID_RESPONSE,404);
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
}
?>