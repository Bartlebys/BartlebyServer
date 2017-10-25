<?php

namespace Bartleby\Core;

require_once BARTLEBY_ROOT_FOLDER . 'Core/Context.php';
require_once BARTLEBY_ROOT_FOLDER . 'Core/KeyPath.php';
require_once BARTLEBY_ROOT_FOLDER. 'Core/RunAndLock.php';
require_once  BARTLEBY_ROOT_FOLDER. 'Commons/Filters/FilterCollectionOfEntityPasswordsRemover.php';

use Bartleby\Core\Context;
use \MongoCollection;
use Bartleby\Core\Mode;
use MongoClient;
use Bartleby\Filters\FilterCollectionOfEntityPasswordsRemover;
use Bartleby\Filters\FilterEntityPasswordRemover;

/**
 * Class Controller
 * @package Bartleby\Core
 */
abstract class Controller implements IAuthentified, IAuthenticationControl {

    /**
     * @var null|bool
     */
    protected $_isAuthenticated = null;

    /**
     * @var null|bool
     */
    protected $_authenticationIsValid = null;

    /**
     * @var string
     */
    protected $_userID;

    /**
     * A model wrapper
     * @var CallDataRawWrapper
     */
    private $_model;

    /**
     * @var \Bartleby\Core\Context
     */
    protected $_context;

    /**
     * Controller constructor.
     * @param $model
     * @param \Bartleby\Core\Context $context
     */
    public function __construct($model, Context $context) {
        $this->_model = $model;
        $this->_context = $context;
    }

    /**
     * @return Configuration
     */
    protected function getConfiguration() {
        return $this->_context->getConfiguration();
    }

    /**
     * Return the current user UID for a given spaceUID
     * @param $spaceUID
     * @return string
     */
    public function getCurrentUserID($spaceUID) {
        if (isset($this->_userID)) {
            return $this->_userID;
        }
        $this->_userID = $this->_context->getUserID($spaceUID);
        return $this->_userID;
    }


    /**
     * Grabs the SpaceUID or throws and Exception
     * @param bool $canBeNull
     * @return mixed|string the dataSpace UID.
     * @throws \Exception if $canBeNull==false and the value has not been found
     */
    public function getSpaceUID($canBeNull = false) {
        $spaceUID = $this->_context->getSpaceUID();
        if (!isset($spaceUID) && !$canBeNull) {
            // It is not normal in api mode
            throw new \Exception("Undefined space UID");
        }
        return $spaceUID;
    }


    /**
     * Grabs the runUID or throws and Exception
     * @param bool $canBeNull
     * @return mixed|string the run UID.
     * @throws \Exception if $canBeNull==false and the value has not been found
     */
    public function getRunUID($canBeNull = false) {
        $runUID = $this->_context->getRunUID();
        if (!isset($runUID) && !$canBeNull) {
            throw new \Exception("Undefined run UID");
        }
        return $runUID;
    }


    /**
     * Grabs the observationUID or throws and Exception
     * @param bool $canBeNull
     * @return mixed|string the Observation UID.
     * @throws \Exception if $canBeNull==false and the value has not been found
     */
    public function getObservationUID($canBeNull = false) {
        $observationUID = $this->_context->getObservationUID();
        if (!isset($observationUID) && !$canBeNull) {
            throw new \Exception("Undefined Observation UID");
        }
        return $observationUID;
    }


    /**
     * Grabs the observationUIDS or throws and Exception
     * @param bool $canBeNull
     * @return array of String the Observation UID.
     * @throws \Exception if $canBeNull==false and the value has not been found
     */
    public function getObservationUIDS($canBeNull = false) {
        $observationUIDS = $this->_context->getObservationUIDS();
        if (!isset($observationUIDS) && !$canBeNull) {
            throw new \Exception("Undefined Observation UIDS");
        }
        return $observationUIDS;
    }

    /**

    /**
     * Inserts a trigger into the triggers collection to be relayed via SSE.
     *
     * @param string $senderUID
     * @param string $collectionName the collection name
     * @param string $origin the action that has created the trigger
     * @param string $homologousAction e.g: `CreateUser would trigger homologous action `ReadUser`
     * @param mixed $reference can be a collection or a single instance.
     * @param bool $isACollection
     * @return array composed of two components
     *  [0] is an int
     *      -2 if the trigger has not been relayed
     *      -1 if an error has occured
     *      > 0 the trigger index on success
     *  [1] correspond to the requestDuration
     * @throws \Exception
     */
    public function relayTrigger($senderUID, $collectionName, $origin, $homologousAction, $reference,$isACollection) {

        // Do not relay.
        if ($collectionName=='lockers'){
            return [-2,0];
        }

        // Relay
        $ts=microtime(true);

        // Determine if the trigger should be ephemeral
        $ephemeral = NULL;
        $spaceUID = NO_UID;
        $runUID = NO_UID;
        $requestCounter = "0";
        $observationUID = NO_UID;

        $spaceUID = $this->getSpaceUID(false);
        $observationUID = $this->getObservationUID(false);
        $runUID = $this->getRunUID(false);

        $allHEADER = getallheaders();
        if ($allHEADER != false) {
            if (array_key_exists(EPHEMERAL_KEY, $allHEADER)) {
                $ephemeral = '1';
            }
            if (array_key_exists(REQUEST_COUNTER_KEY, $allHEADER)) {
                $requestCounter = $allHEADER[REQUEST_COUNTER_KEY];
            }
        }

        if (isset($spaceUID) && isset($homologousAction) && isset($reference)) {
            $UIDS = $this->_extractUIDS($reference);
            if (!isset($senderUID) || $senderUID == "") {
                if (strpos($homologousAction, "ReadUser") !== false) {
                    // It is a user creation so we should determine the user creator
                } else {
                    throw new \Exception("Trigger sender is undefined", 0);
                }
            }


            if (count($UIDS) > 0) {

                // Insert the trigger.
                $db = $this->getDB();
                /* @var \MongoCollection */
                $collection = $db->triggers;
                // Default Read policy
                // https://docs.mongodb.com/manual/reference/read-concern/
                $readOptions = array(
                    "r" => "local"
                );

                $trigger = new \stdClass();
                $trigger->UID = $runUID . '.' . $requestCounter;
                $trigger->observationUID = $observationUID;
                $trigger->spaceUID = $spaceUID;
                $trigger->senderUID = $senderUID;
                $trigger->runUID = $runUID;
                $trigger->index = -10000; // Distinctive Initial value
                $trigger->origin = $origin;
                $trigger->targetCollectionName = $collectionName;
                $trigger->action = $homologousAction;
                $trigger->UIDS = join(',', $UIDS);
                if (strpos($homologousAction, 'Read') !== false) {
                    // It is a CREATE and UPDATE or an UPSERT
                    if ($isACollection==true){
                        $trigger->payloads = $reference;
                    }else{
                        $trigger->payloads = array($reference);
                    }
                } else {
                    // It is a DELETE
                    $trigger->payloads = "[[]]";
                }

                // Derived password eviction
                if ($collectionName=='users' && (strpos($homologousAction, 'Read') !== false)){
                    $filter=new FilterCollectionOfEntityPasswordsRemover();
                    $trigger->payloads=$filter->filterData($trigger->payloads);
                }

                /////////////////////////////////////////
                /// Triggers index
                /// uses semaphores to guarantee
                /// incremental update of the trigger index
                /// The lock is generated by ObservationUID
                /////////////////////////////////////////

                $useSemaphore = true;
                $semaphoreIdentifier = crc32('triggers');
                $semResource = NULL;

                //Create the trigger in a locked Closure
                /* @var $mongoAction \Closure*/
                $mongoAction=function () use($trigger,$collection,$observationUID,$readOptions,$requestCounter,$ts) {

                    $trigger->index = $collection->count(['observationUID' => $observationUID], $readOptions);
                    // In case of irrelevant HEADER
                    // We want a unique UID so we use the Trigger counter
                    if ($requestCounter == "0") {
                        $requestCounter = "E." . ($trigger->index + 0);
                    }

                    // Write Concerns
                    $options = $this->getConfiguration()->getDefaultMongoWriteConcern();

                    $date = new \DateTime();
                    $iso8601 = $date->format(DATE_ISO8601);
                    $o = array(
                        MONGO_ID_KEY => $trigger->UID,
                        OBSERVATION_UID_KEY => $trigger->observationUID,
                        SPACE_UID_KEY => $trigger->spaceUID,
                        RUN_UID_KEY => $trigger->runUID,
                        "senderUID" => $trigger->senderUID,
                        "index" => $trigger->index,
                        "origin" => $trigger->origin,
                        "action" => $trigger->action,
                        "targetCollectionName" => $trigger->targetCollectionName,
                        "UIDS" => $trigger->UIDS,
                        "creationDate" => $iso8601,
                        "payloads" => $trigger->payloads
                    );

                    if (isset($ephemeral)) {
                        // To consistent with current JOBject encodings in MongoDB.
                        // We use '1' and not true
                        $o[EPHEMERAL_KEY] = '1';
                    }
                    $r = $collection->insert($o, $options);
                    $ts=microtime(true)-$ts;
                    if ($r['ok'] == 1) {
                        return [$trigger->index,$ts];
                    } else {
                        return [-1,$ts];
                    }
                };
                $lockName='triggers';
                return RunAndLock::run($mongoAction,$lockName);

            } else {
                throw new \Exception("Void UIDS for trigger $observationUID $spaceUID $senderUID $homologousAction $reference", 0);
            }
        } else {
            throw new \Exception("Inconsitent trigger $observationUID $spaceUID $senderUID $homologousAction $reference", 0);
        }
    }


    /**
     * Returns a json encoded string
     * @param array composed of two components
     *  [0] is an int -1 if an error has occured and the trigger index on success.
     *  [1] correspond to the requestDuration
     * @param $optionalMessage
     * @return array
     */
    public function responseStringWithTriggerInformations($informations, $optionalMessage) {
        if (is_array($informations) && count($informations)>=1){
            $index=$informations[0];
            $requestDuration=$informations[1];
            if (isset($optionalMessage)) {
                return array("triggerIndex" => $index, "triggerRelayDuration"=>$requestDuration,"message" => $optionalMessage);
            } else {
                return array("triggerIndex" => $index,"triggerRelayDuration"=>$requestDuration);
            }
        }else{
            throw  new \Exception('Invalid triggers informations');
        }
    }

    /**
     * Extracts the UIDS from a given reference.
     * @param $reference
     * @param array $UIDS
     * @return array
     */
    private function _extractUIDS($reference, $UIDS = array()) {
        if (isset($reference)) {
            if (is_array($reference)) {
                if (array_key_exists("_id", $reference)) {
                    $UIDS[] = $reference["_id"];
                } else {
                    foreach ($reference as $element) {
                        if (is_array($element)) {
                            return $this->_extractUIDS($element);
                        } elseif (is_string($element)) {
                            $UIDS[] = $element;
                        } else {
                            //
                        }
                    }
                }
            } elseif (is_string($reference)) {
                $UIDS[] = $reference;
            }
        }
        return $UIDS;
    }

    /***
     * Always returns a CallDataRawWrapper
     * @return CallDataRawWrapper
     */
    function getModel() {
        // It can be normal for pages not to have Data wrapper
        // So we create one
        if (!isset($this->_model)) {
            $this->_model = new CallDataRawWrapper([]);
        }
        return $this->_model;
    }

    ///////////////////////////
    /// MONGO IMPLEMENTATION
    //////////////////////////


    /**
     * The MongoDB
     *
     * @var MongoDB
     */
    private $_db;

    /**
     * The Mongo client
     *
     * @var MongoClient
     */
    private $_mongoClient;


    /**
     * @return MongoDB
     */
    protected function getDB() {
        try {
            if (!isset($this->_db)) {
                $client = $this->getMongoClient();
                if (isset($client)) {
                    $this->_db = $client->selectDB($this->getConfiguration()->MONGO_DB_NAME());
                }
            }
            return $this->_db;
        } catch (\MongoConnectionException $e) {
            throw new \Exception("MongoDB Connection Issue. " . $e->getMessage());
        }
    }


    /**
     * @return MongoClient
     */
    protected function getMongoClient() {
        if (!isset($this->_mongoClient)) {
            $this->_mongoClient = new MongoClient ();
        }
        return $this->_mongoClient;
    }


    //////////////////////////////
    //
    // IAuthentified
    //
    //////////////////////////////


    /* @var array user as an array */
    private $_currentUser = null;

    /**
     * @return array
     */
    public function getCurrentUser() {
        if (!isset($this->_currentUser)) {
            // There is no garantee that the context is populated.
            // It can be populated by the GateKeeper if the permission == PERMISSION_BY_IDENTIFICATION
            $this->_currentUser = $this->_context->getCurrentUser();
        }
        if (!isset($this->_currentUser)) {
            $this->_currentUser = $this->_context->getCurrentUser();
        }

        return $this->_currentUser;
    }

    /**
     * @param array $currentUser
     */
    public function setCurrentUser($currentUser) {
        $this->_currentUser = $currentUser;
    }


    //////////////////////////////
    //
    // IAuthenticationControl
    //
    //////////////////////////////


    /**
     * Should return true if there are traces of a previous valid identification (KVIds or cookie)
     * This method does not garantee the validity of the authentication.
     * Call authenticationIsValid($spaceUID) to perform a complete verification at a given time.
     * @param $spaceUID
     * @return mixed
     */
    public function isAuthenticated($spaceUID) {
        if (!isset($this->_isAuthenticated)) {
            $userUID = $this->getCurrentUserID($spaceUID);
            $this->_isAuthenticated = (isset($userUID) ? true : false);
        }
        return $this->_isAuthenticated;
    }

    /**
     * Verifies if the authentication is still valid at a given momentum.
     * @param $spaceUID
     * @return mixed
     */
    public function authenticationIsValid($spaceUID) {
        if (!isset($spaceUID)) {
            $this->_context->consignIssue('spaceUID is not defined', __FILE__, __LINE__);
            return false;
        }
        if (!$this->isAuthenticated($spaceUID)) {
            $this->_context->consignIssue('user is not authenticated', __FILE__, __LINE__);
            return false;
        }
        // We are sure that we have a valid userUID
        $userUID = $this->getCurrentUserID($spaceUID);

        // Let's check if there is a user with that UID
        $currentUser = $this->_getCurrentUser($userUID);
        if (!isset($currentUser)) {
            $this->_context->consignIssue('user ' . $userUID . ' was not found', __FILE__, __LINE__);
            return false;
        }

        // Does that user have set a spaceUID?
        if (!array_key_exists(SPACE_UID_KEY, $currentUser)) {
            $this->_context->consignIssue('Unexisting spaceUID', __FILE__, __LINE__);
            return false;
        }

        // Is the spaceUID consistent?
        if ($currentUser[SPACE_UID_KEY] != $spaceUID) {
            $this->_context->consignIssue('DataSpace is inconsistent', __FILE__, __LINE__);
            return false;
        }

        // Is the user "suspended" ?
        if (array_key_exists('status', $currentUser)) {
            if ($currentUser['status'] == 'suspended') {
                $this->_context->consignIssue('This user is suspended', __FILE__, __LINE__);
                return false;
            }
        }
        return true;
    }

    /**
     * @return array|null the user or null
     */
    private function _getCurrentUser($userID) {
        if (strlen($userID) < 24) {
            return NULL;
        }
        $user = $this->_context->getCurrentUser();
        if (isset($user)) {
            return $this->_user;
        }
        try {
            $db = $this->getDB();
            /* @var \MongoCollection */
            $collection = $db->users;
            $q = array(MONGO_ID_KEY => $userID);
            $u = $collection->findOne($q);
            if (isset($u)) {
                $this->_context->setCurrentUser($u);
                return $u;
            }
        } catch (\Exception $e) {
            // Silent
            $this->_context->consignIssue('Mongo Exception ' . $e->getMessage(), __FILE__, __LINE__);
        }
        return NULL;
    }

}