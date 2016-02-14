<?php

/**
 * ChasePaymentech_Response
 * 
 * @author Routy Development LLC <support@getrouy.com>
 * @copyright (c) 2013, Routy Development
 * @package ChasePaymentech
 * @license see license.txt
 * @version 1.1
 * @link http://download.chasepaymentech.com/docs/orbital/orbital_gateway_xml_specification.pdf
 * 
 */
abstract class ChasePaymentech_Response {
    
    const APPROVED = 1;
    const DECLINED = 0;
    const ERROR    = 2;
    
    public $approved;
    public $declined;
    public $error;
    public $ProcStatus;
    public $RespCode;
    public $ApprovalStatus;
    public $response;
    
}

?>
