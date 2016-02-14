<?php
/**
 * ChasePaymentech_Reversal
 * 
 * @author Routy Development LLC.
 * @copyright (c) 2012-2013 Routy Development LLC.
 * @package Chase Paymentech
 * @link http://http://download.chasepaymentech.com/docs/orbital/orbital_gateway_xml_specification.pdf XML API Docs
 */
class ChasePaymentech_Reversal extends ChasePaymentech_Request {

    protected $_available_fields = array(
        'TxRefNum', 'TxRefIdx', 'AdjustedAmt', 'OrderID', 'ReversalRetryNumber',
        'OnlineReversalInd'
    );
    
    protected $_required_fields = array(
        'OrderID'
    );
    
    public function void() {
        
        $xml = $this->_setRequest();
        
        $this->_request_xml = $xml;
        
        return $this->_sendRequest();
    }

    private function _setRequest(){
        
        $xml = new SimpleXMLElement('<Request><Reversal></Reversal></Request>');
        $xml_order = $xml->Reversal;
        
        $xml_order->OrbitalConnectionUsername = $this->_username;
        $xml_order->OrbitalConnectionPassword = $this->_password;
        $xml_order->TxRefNum = null;
        $xml_order->TxRefIdx = null;
        $xml_order->AdjustedAmt = null;
        $xml_order->OrderID = null;
        $xml_order->BIN = $this->_bin;
        $xml_order->MerchantID = $this->_merchant_id;
        $xml_order->TerminalID = $this->_terminal_id;
        
        foreach($this->_available_fields as $field){
            if(isset($this->_fields[$field]) && !empty($this->_fields[$field])){
                $xml_order->$field = $this->_fields[$field];
            }
        }

        $this->_request_xml = $xml;
        
        return $xml;
        
    }
    
    protected function _handleResponse($response) {

        return $this->_response = new ChasePaymentech_Reversal_Response($response);
        
    }

}

/**
 * ChasePaymentech_Reversal_Response
 * 
 * @author Routy Development LLC.
 * @copyright (c) 2012-2013 Routy Development LLC.
 * @package Chase Paymentech
 * @link http://http://download.chasepaymentech.com/docs/orbital/orbital_gateway_xml_specification.pdf XML API Docs
 */
class ChasePaymentech_Reversal_Response extends ChasePaymentech_Response {
    
    public function __construct($response){
    
        $this->response = $response;
        
        if($response){
        
            if(is_object($response)){

                $this->approved = false;
                $this->declined = false;
                $this->error = true;
                $this->StatusMsg = $response->StatusMsg;
                
            } else {
            
                try {

                    $xml = new SimpleXMLElement($response);

                } catch(Exception $e){

                    $this->approved = false;
                    $this->declined = false;
                    $this->error = true;
                    $this->StatusMsg = 'Invalid response received. Unable to parse XML response.';
                    return;

                }

                if($xml->ReversalResp){

                    if($xml->ReversalResp->ProcStatus == '0'){

                        $xml_resp = $xml->ReversalResp;

                        $this->approved = ($xml_resp->ApprovalStatus == self::APPROVED);
                        $this->declined = ($xml_resp->ApprovalStatus == self::DECLINED);
                        $this->error    = ($xml_resp->ApprovalStatus == self::ERROR);

                        $this->OrderID = $xml_resp->OrderID;
                        $this->TxRefNum = $xml_resp->TxRefNum;
                        $this->TxRefIdx = $xml_resp->TxRefIdx;
                        $this->OutstandingAmt = $xml_resp->OutstandingAmt;
                        $this->ProcStatus = $xml_resp->ProcStatus;
                        $this->StatusMsg = $xml_resp->StatusMsg[0];
                        $this->RespTime = $xml_resp->RespTime;

                    } else {

                        $this->approved = false;
                        $this->declined = false;
                        $this->error = true;
                        $this->StatusMsg = 'Gateway rejected request. Gateway responded with message: '.$xml_resp->StatusMsg[0];
                        return;

                    }

                } else if ($xml->QuickResp){

                    $this->approved = false;
                    $this->declined = false;
                    $this->error = true;
                    $this->ProcStatus = $xml_resp->QuickResp->ProcStatus;
                    $this->StatusMsg = $xml_resp->QuickResp->StatusMsg[0];
                    return;

                } else {

                    $this->approved = false;
                    $this->declined = false;
                    $this->error = true;
                    $this->StatusMsg = 'Invalid XML received.';
                    return;

                }
            }
        
        } else {
            
            $this->approved = false;
            $this->declined = false;
            $this->error = true;
            $this->StatusMsg = 'Unable to connect to Chase Paymentech.';
            
        }
    }
}

?>
