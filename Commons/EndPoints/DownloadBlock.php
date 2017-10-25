<?php
namespace Bartleby\EndPoints;

require_once BARTLEBY_ROOT_FOLDER . 'Mongo/MongoEndPoint.php';
require_once BARTLEBY_ROOT_FOLDER . 'Mongo/MongoCallDataRawWrapper.php';


use Bartleby\Mongo\MongoEndPoint;
use Bartleby\Mongo\MongoCallDataRawWrapper;
use Bartleby\Core\JsonResponse;
use \MongoCollection;
use \MongoRegex;

class DownloadBlockCallData extends MongoCallDataRawWrapper {
    // The block UID (is extracted from the path  'GET:/block/{id}' => array('DownloadBlock','call'))
    const id = 'id';
}

class DownloadBlock extends MongoEndPoint  {

    function call() {

        $redirect=true;

        /* @var DownloadBlockCallData */
        $parameters=$this->getModel();
        $db=$this->getDB();
        /* @var \MongoCollection */
        $collection = $db->blocks;
        $q = array (MONGO_ID_KEY =>$parameters->getValueForKey(DownloadBlockCallData::id));
        if (isset($q)&& count($q)>0){
        }else{
            return new JsonResponse('Query is void',412);
        }
        try {
            $block = $collection->findOne($q);
            if (isset($block)) {
                if (is_array($block) && array_key_exists('digest',$block)){
                    $blockSHA1=$block['digest'];
                    // The block relative path "/c[0]/c[1]/c[3]/<sha1>"
                    $relativePath='/'.substr($blockSHA1,0,1).'/'.substr($blockSHA1,1,1).'/'.substr($blockSHA1,2,1).'/'.$blockSHA1;
                    $absolutePath = REPOSITORY_WRITING_PATH . '/' . $relativePath;
                    if (file_exists($absolutePath)){
                        $uri=REPOSITORY_BASE_URL.'/files'.$relativePath;
                        if ($redirect) {
                            // This is the best approach
                            // Redirect with a 307 code
                            header('Location:  ' . $uri . '?antiCache=' . uniqid(), true, 307);
                            exit ();
                        } else {
                            // But if it fails we can use
                            // A two step approach.
                            $infos = array();
                            $infos ["uri"] = $uri;
                            return new JsonResponse($infos, 200);
                        }
                    }else{
                        return new JsonResponse('File not found. '.$relativePath,404);
                    }
                }else{
                    return new JsonResponse('Digest not found',404);
                }
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