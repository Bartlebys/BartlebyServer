<?php

namespace Bartleby\EndPoints\Overloads;

require_once BARTLEBY_ROOT_FOLDER.'Commons/_generated/EndPoints/DeleteBlock.php';

use Bartleby\EndPoints\DeleteBlockCallData;
use Bartleby\Mongo\MongoCallDataRawWrapper;
use Bartleby\Core\JsonResponse;
use \MongoCollection;
use Bartleby\Configuration;
use Bartleby\Core\KeyPath;

// OVERLOAD goal: Proceed to file cleaning when a logical block is deleted
class  DeleteBlock extends \Bartleby\EndPoints\DeleteBlock  {

     function call() {

         /* @var DeleteBlockCallData */
         $parameters=$this->getModel();
         $db=$this->getDB();
         /* @var \MongoCollection */
         $collection = $db->blocks;
         $q = array (MONGO_ID_KEY =>$parameters->getValueForKey(DeleteBlockCallData::blockId));
         if (!(isset($q)&& count($q)>0)){
             return new JsonResponse('Query is void',412);
         }
         try {
             $block = $collection->findOne($q);
             // Deletion is idempotent so we prefer not to react on semantic issues this level.
             if (isset($block)) {
                 if (is_array($block) && array_key_exists('digest',$block)) {
                     $blockSHA1 = $block['digest'];
                     // The block relative path "/c[0]/c[1]/c[3]/<sha1>"
                     $relativePath = '/' . substr($blockSHA1, 0, 1) . '/' . substr($blockSHA1, 1, 1) . '/' . substr($blockSHA1, 2, 1) . '/' . $blockSHA1;
                     $absolutePath = REPOSITORY_WRITING_PATH . '/' . $relativePath;
                     if(!file_exists($absolutePath) && !is_dir($absolutePath)){
                         @unlink($absolutePath);
                     }
                 }
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

         // Delete the  Entity.
         return parent::call();
     }


 }