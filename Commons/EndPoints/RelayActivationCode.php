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


final class RelayActivationCodeCallData extends MongoCallDataRawWrapper {

    const toEmail = 'toEmail';

    const toPhoneNumber = 'toPhoneNumber';

    const code='code';

    const title='title';

    const body='body';

}

final class RelayActivationCode extends MongoEndPoint {

    function POST() {
        /* @var RelayActivationCodeCallData */
        $parameters=$this->getModel();
        $toEmail=$parameters->getValueForKey(RelayActivationCodeCallData::toEmail);
        $toPhoneNumber=$parameters->getValueForKey(RelayActivationCodeCallData::toPhoneNumber);
        $code=$parameters->getValueForKey(RelayActivationCodeCallData::code);
        if (isset($toEmail) && isset($toPhoneNumber) && isset($code)){
            $body=$parameters->getValueForKey(RelayActivationCodeCallData::body);
            if (!isset($body)){
                $body='$code';
            }
            $body=str_replace('$code',$code,$body);
            $title=$parameters->getValueForKey(RelayActivationCodeCallData::title);
            if (!isset($title)){
                $title='Activation from '.$this->getConfiguration()->BASE_URL();
            }
            /* @var RelayResponse */
            $r=$this->getConfiguration()->relay($toEmail,$toPhoneNumber,$title,$body);
            if ($r instanceof RelayResponse){
                if ($r->success){
                    return new JsonResponse($r->informations,201);
                }else{
                    return new JsonResponse(VOID_RESPONSE,412);
                }
            }else{
                // The plugin should return a RelayResponse
                return new JsonResponse("RelayActivationCode should return a RelayResponse instance",412);
            };
        }else{
            return new JsonResponse('Submission is not valid',412);
        }
    }

}