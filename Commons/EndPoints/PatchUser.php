<?php

namespace Bartleby\EndPoints;
require_once BARTLEBY_ROOT_FOLDER . 'Core/Configuration.php';
require_once BARTLEBY_ROOT_FOLDER . 'Mongo/MongoEndPoint.php';
require_once BARTLEBY_ROOT_FOLDER . 'Mongo/MongoCallDataRawWrapper.php';


use Bartleby\Core\Mode;
use Bartleby\Core\RunUnlocked;
use Bartleby\mongo\MongoCallDataRawWrapper;
use Bartleby\Mongo\MongoConfiguration;
use Bartleby\Mongo\MongoEndPoint;
use Bartleby\Core\JsonResponse;
use \MongoCursorException;
use \MongoClient;
use Bartleby\Configuration;


final class PatchUserCallData extends MongoCallDataRawWrapper {

    const userId = 'userId';

    const password = 'password';

}

final class PatchUser extends MongoEndPoint {

    function POST() {

        /* @var PatchUserCallData */
        $parameters=$this->getModel();
        $db=$this->getDB();
        /* @var \MongoCollection */
        $collection = $db->users;
        $q = array (MONGO_ID_KEY =>$parameters->getValueForKey(PatchUserCallData::userId));
        if (isset($q)&& count($q)>0){
        }else{
            return new JsonResponse('Query is void',412);
        }
        try {
            $user = $collection->findOne($q);
            if (isset($user)) {

                if (array_key_exists('supportsPasswordUpdate', $user)) {
                    if ($user['supportsPasswordUpdate'] == false) {
                        return new JsonResponse('supportsPasswordUpdate is set to false', 423);
                    }
                }

                if (array_key_exists('supportsPasswordSyndication', $user)) {
                    if ($user['supportsPasswordSyndication'] == false) {
                        return new JsonResponse('supportsPasswordSyndication is set to false', 423);
                    }
                }

                // Don't stress we never transmit the user's password
                // This is crypted image generated client side
                $password=$parameters->getValueForKey(PatchUserCallData::password);
                if (isset($password)){
                    // We store a salted version of the crypted password
                    $user['password']=$this->getConfiguration()->salt($password);
                }

                /// Let's update the user
                $q = array (MONGO_ID_KEY =>$user[MONGO_ID_KEY]);
                try {

                    // Write Acknowlegment policy
                    $options = $this->getConfiguration()->getDefaultMongoWriteConcern();

                    /// Inject the rootUID and the spaceUID in any entity
                    $user[OBSERVATION_UID_KEY]=$this->getObservationUID(false);
                    $user[SPACE_UID_KEY]=$this->getSpaceUID(false);

                    //Update via a locked Closure
                    /* @var $mongoAction \Closure*/
                    $mongoAction=function () use($collection,$q, $user,$options) {
                        return  $collection->update ($q, $user,$options );
                    };
                    $lockName='users';
                    $r=RunUnlocked::run($mongoAction,$lockName);
                    if ($r['ok']==1) {
                        if(array_key_exists('updatedExisting',$r)){
                            $existed=$r['updatedExisting'];
                            if($existed==true){
                                $s=$this->responseStringWithTriggerInformations($this->createTrigger($user),NULL);
                                return new JsonResponse($s,200);
                            }else{
                                return new JsonResponse(VOID_RESPONSE,404);
                            }
                        }
                        $s=$this->responseStringWithTriggerInformations($this->createTrigger($user),NULL);
                        return new JsonResponse($s,200);
                    } else {
                        return new JsonResponse($r,412);
                    };

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

    /**
     * Creates and relay the action using a trigger
     *
     * @param the user
     * @return  array composed of two components
     *  [0] is an int -1 if an error has occured and the trigger index on success.
     *  [1] correspond to the requestDuration
     * @throws \Exception
     */
    function createTrigger($user){
        $ref=$user;
        $homologousAction="ReadUserById";
        $senderUID=$this->getCurrentUserID($this->getSpaceUID(true));
        return $this->relayTrigger($senderUID,"users","PatchUser",$homologousAction,$ref,false);
    }

}