<?php
namespace Bartleby\EndPoints;

require_once BARTLEBY_ROOT_FOLDER . 'Mongo/MongoEndPoint.php';
require_once BARTLEBY_ROOT_FOLDER . 'Mongo/MongoCallDataRawWrapper.php';
require_once BARTLEBY_ROOT_FOLDER . 'Core/KeyPath.php';

use Bartleby\Mongo\MongoEndPoint;
use Bartleby\Mongo\MongoCallDataRawWrapper;
use Bartleby\Core\JsonResponse;
use \MongoCollection;
use \MongoRegex;
use Bartleby\Core\KeyPath;


class UploadBlockCallData extends MongoCallDataRawWrapper {
    // The block UID (is extracted from the path  'POST:/block/{id}' => array('UploadBlock','call'))
    const id = 'id';
}

class UploadBlock extends MongoEndPoint  {

    function call(){

        /* @var $parameters UploadBlockCallData */
        $parameters = $this->getModel();

        $blockUID = $parameters->getValueForKey(UploadBlockCallData::id);
        if (!isset($blockUID)) {
            return new JsonResponse("Block UID is undefined", 404);
        }
        $q = array (MONGO_ID_KEY =>$blockUID);
        if (!(isset($q)&& count($q)>0)){
            return new JsonResponse('Query is void',412);
        }
        try {

            $db=$this->getDB();
            /* @var \MongoCollection */
            $collection = $db->blocks;
            $block = $collection->findOne($q);
            // Deletion is idempotent so we prefer not to react on semantic issues this level.
            if (isset($block)) {
                if (is_array($block) && array_key_exists('digest',$block)) {
                    $blockSHA1 = $block['digest'];
                    // The block relative path "/c[0]/c[1]/c[3]/<sha1>"
                    $relativePath = '/' . substr($blockSHA1, 0, 1) . '/' . substr($blockSHA1, 1, 1) . '/' . substr($blockSHA1, 2, 1) . '/' . $blockSHA1;
                    $destinationPath = REPOSITORY_WRITING_PATH . '/' . $relativePath;
                    if (file_exists($destinationPath)) {
                        // The block already exits
                        // We don't need to upload it.
                        $s = $this->responseStringWithTriggerInformations($this->createTrigger($parameters), NULL);
                        return new JsonResponse($s, 200);
                    }
                    $folderPath = dirname($destinationPath);
                    // Create a folder if necessary.
                    @mkdir($folderPath, 0755, true);

                    ////////////////////////
                    // USE a stream input.
                    ////////////////////////

                    // We prefer not to load the file in memory.
                    // direct stream handling without that requires less memory than

                    $flow = fopen("php://input", "r");
                    /* Open a file for writing */
                    $fp = fopen($destinationPath, "w");
                    /* Read the data 1 KB at a time and write to the file */
                    while ($data = fread($flow, 1024)) {
                        fwrite($fp, $data);
                    }
                    fclose($fp);
                    fclose($flow);

                    if (file_exists($destinationPath)) {
                        $s = $this->responseStringWithTriggerInformations($this->createTrigger($parameters), NULL);
                        return new JsonResponse($s, 201);
                    }else{
                        return new JsonResponse('An error has occured the uploaded block has not been created' . $destinationPath, 410);
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
    }

    /**
     * Creates and relay the action using a trigger
     *
     * @param UploadBlockCallData $parameters
     * @return  array composed of two components
     *  [0] is an int -1 if an error has occured and the trigger index on success.
     *  [1] correspond to the requestDuration
     * @throws \Exception
     */
    function createTrigger(UploadBlockCallData $parameters) {
        $ref = $parameters->getValueForKey(UploadBlockCallData::id);
        $homologousAction = "DownloadBlock";
        $senderUID = $this->getCurrentUserID($this->getSpaceUID(true));
        return $this->relayTrigger($senderUID, "blocks", "UploadBlock", $homologousAction, $ref, false);
    }

}
?>