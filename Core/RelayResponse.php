<?php

namespace Bartleby\Core;

/**
 * Class RelayResponse
 * Used to relay a formatted respons from a delegate (e.g an activation plugin)
 * @package Bartleby\Core
 */
class RelayResponse {

    /* @var boolean success*/
    public $success = false;

    /* @var array */
    public $informations=array();

}