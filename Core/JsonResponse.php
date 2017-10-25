<?php


namespace Bartleby\Core;

require_once __DIR__ . '/IResponse.php';
require_once __DIR__ . '/Response.php';

class JsonResponse extends Response implements  IHTTPResponse{

    /* @var $data the data (we keep the property public for DataFilters) */
    public $data;


    public $_prettyPrint=false;
    /**
     * @var Integer|int
     */
    private $_statusCode=-1;

    /**
     * JsonResponse constructor.
     * @param $data
     * @param $statusCode Integer
     */
    public function __construct($data, $statusCode) {
        $this->data = $data;
        $this->_statusCode = $statusCode;
    }


    function usePrettyPrint($enabled){
        $this->_prettyPrint=$enabled;
    }


    /**
     * @return mixed
     */
    public function getJsonEncodedData() {
        if(!is_array($this->data) && $this->data!=VOID_RESPONSE) {
            if ($this->_prettyPrint){
                // We encapsulate in an array
                return json_encode(array($this->data),JSON_PRETTY_PRINT);
            }else{

            }
        }
        if ($this->_prettyPrint) {
            return json_encode($this->data,JSON_PRETTY_PRINT);
        }else{
            return json_encode($this->data);
        }
    }

    /**
     * @return int
     */
    public function getStatusCode() {
        return $this->_statusCode;
    }


    /**
     * Sends the response
     */
     function send() {
        // we use this for JSON response only
        // We can accounter also redirections so we prefer to set
        // the header contextually.
         $code = $this->getStatusCode();
         header("Access-Control-Allow-Origin: *");
         header("Access-Control-Allow-Methods: *");
         header("Content-Type: application/json");
         $header = 'HTTP/1.1 ' . $code . ' ' . Response::getRequestStatus($code);
         header($header);
         if (isset ($this->data)) {
             $json=$this->getJsonEncodedData();
             echo  $json;
         }
    }
}
