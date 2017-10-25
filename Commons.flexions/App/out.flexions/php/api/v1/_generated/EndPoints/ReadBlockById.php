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

class  ReadBlockByIdCallData extends MongoCallDataRawWrapper {
	/* The unique identifier the the of Block */
	const blockId='blockId';
}

 class  ReadBlockById extends MongoEndPoint {

     function call() {
        /* @var ReadBlockByIdCallData */
        $parameters=$this->getModel();
        $db=$this->getDB();
        /* @var \MongoCollection */
        $collection = $db->blocks;
         $q = array (MONGO_ID_KEY =>$parameters->getValueForKey(ReadBlockByIdCallData::blockId));
        if (isset($q)&& count($q)>0){
        }else{
            return new JsonResponse('Query is void',412);
        }
        try {
            $r = $collection->findOne($q);
            if (isset($r)) {
                return new JsonResponse($r,200);
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