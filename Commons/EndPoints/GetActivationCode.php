<?php


namespace Bartleby\EndPoints;
require_once BARTLEBY_ROOT_FOLDER . 'Core/Configuration.php';
require_once BARTLEBY_ROOT_FOLDER . 'Core/RelayResponse.php';
require_once BARTLEBY_ROOT_FOLDER . 'Mongo/MongoEndPoint.php';
require_once BARTLEBY_ROOT_FOLDER . 'Mongo/MongoCallDataRawWrapper.php';


use Bartleby\Core\Mode;
use Bartleby\Core\RelayResponse;
use Bartleby\mongo\MongoCallDataRawWrapper;
use Bartleby\Mongo\MongoConfiguration;
use Bartleby\Mongo\MongoEndPoint;
use Bartleby\Core\JsonResponse;
use \MongoCursorException;
use \MongoClient;
use Bartleby\Configuration;

/**
 * Class GetActivationCodeCallData
 * @package Bartleby\EndPoints
 */
final class GetActivationCodeCallData extends MongoCallDataRawWrapper {


    /* @var string */
    const lockerUID = 'lockerUID';

    /* @var string */
    const title = 'title';

    /* @var string */
    const body = 'body';
}


/**
 * Class GetActivationCode
 *
 * @package Bartleby\EndPoints
 */
final class GetActivationCode extends MongoEndPoint {

    function GET() {

        /// GetActivationCode(for :lockerUID)
        /// -> Will verify the user ID and use the found user PhoneNumber to send the activation code.

        /* @var GetActivationCodeCallData */
        $parameters = $this->getModel();
        $lockerUID = $parameters->getValueForKey(GetActivationCodeCallData::lockerUID);
        if (isset($lockerUID)) {

            // Load the Locker
            // Verify the user.UID==locker.userUID
            // Relay the message.

            $db = $this->getDB();
            /* @var \MongoCollection */
            $collection = $db->lockers;
            $q = array(MONGO_ID_KEY => $lockerUID);
            try {
                $locker = $collection->findOne($q);

                if (isset($locker)) {
                    if (array_key_exists('userUID', $locker)) {
                        $spaceUID = $this->getSpaceUID(false);
                        $currentUser = $this->getCurrentUser();

                        ////////////////////////
                        /// Verify the userUID
                        ////////////////////////

                        if ($currentUser[MONGO_ID_KEY] == $locker['userUID']) {

                            $toEmail = $currentUser['email'];
                            $countryCode = $currentUser['phoneCountryCode'];
                            preg_match('/\((.*?)\)/', $countryCode, $match);
                            if (is_array($match) && count($match) > 0) {
                                $countryCode = $match[1];
                            }

                            $toPhoneNumber = $countryCode . $currentUser['phoneNumber'];
                            $code = $locker['code'];

                            ////////////////////////
                            /// Relay via the second
                            // authentication Factor
                            ////////////////////////

                            $body = $parameters->getValueForKey(GetActivationCodeCallData::body);
                            if (!isset($body)) {
                                $body = '$code';
                            }

                            $body = str_replace('$code', $code, $body);
                            $title = $parameters->getValueForKey(GetActivationCodeCallData::title);
                            if (!isset($title)) {
                                $title = 'Activation from ' . $this->getConfiguration()->BASE_URL();
                            }

                            if (array_key_exists('security', $locker)) {
                                $security = $locker['security'];
                                // We return the locker
                                if ($security == "skipSecondaryAuthFactor"){
                                    return new JsonResponse($locker, 200);
                                }
                                /* @var RelayResponse */
                                $relayedResponse = $this->getConfiguration()->relay($toEmail, $toPhoneNumber, $title, $body);
                                if ($relayedResponse instanceof RelayResponse) {
                                    if ($relayedResponse->success) {
                                        return new JsonResponse($relayedResponse->informations, 201);
                                    } else {
                                        return new JsonResponse(VOID_RESPONSE, 412);
                                    }
                                } else {
                                    // The plugin should return a RelayResponse
                                    return new JsonResponse("GetActivationCode should return a RelayResponse instance", 412);
                                };
                            } else {
                                // The plugin should return a RelayResponse
                                return new JsonResponse("Invalid locker security key is not defined", 412);
                            }

                        } else {
                            return new JsonResponse('User UIDs are discordants', 401);
                        }
                    } else {
                        return new JsonResponse("userUID not found", 404);
                    }
                } else {
                    return new JsonResponse(VOID_RESPONSE, 404);
                }
            } catch (\Exception $e) {
                return new JsonResponse(['code' => $e->getCode(),
                    'message' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => $e->getTraceAsString()
                ],
                    417
                );
            }
        } else {
            return new JsonResponse('Locker UID is not defined', 412);
        }
    }
}