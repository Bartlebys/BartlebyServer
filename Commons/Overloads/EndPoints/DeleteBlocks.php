<?php

namespace Bartleby\EndPoints\Overloads;
require_once BARTLEBY_ROOT_FOLDER.'Commons/_generated/EndPoints/DeleteBlocks.php';

use Bartleby\EndPoints\DeleteBlocksCallData;
use Bartleby\Mongo\MongoCallDataRawWrapper;
use Bartleby\Core\JsonResponse;
use \MongoCollection;
use Bartleby\Configuration;
use Bartleby\Core\KeyPath;

// OVERLOAD goal: Proceed to file cleaning when a logical block is deleted
class  DeleteBlocks extends \Bartleby\EndPoints\DeleteBlocks  {

     function call() {
         /* @var DeleteBlocksCallData */
         $parameters=$this->getModel();
         $db=$this->getDB();
         /* @var \MongoCollection */
         $collection = $db->blocks;
         $ids=$parameters->getValueForKey(DeleteBlocksCallData::ids);
         if(isset ($ids) && count($ids)>0){
             $q = array( MONGO_ID_KEY =>array( '$in' => $ids ));
         }else{
             return new JsonResponse('Query is void',412);
         }
         try {
             $cursor = $collection->find($q);
             // Deletion is idempotent so we prefer not to react on semantic issues this level.
             foreach ( $cursor as $block ) {
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
         // Delete the  Entities.
         return parent::call();
     }
 }

?>