<?php

/**
 * ChasePaymentech_NewOrder
 *
 * @author Routy Development LLC <support@getrouy.com>
 * @copyright (c) 2013, Routy Development
 * @package ChasePaymentech
 * @license see license.txt
 * @version 1.1
 * @link http://download.chasepaymentech.com/docs/orbital/orbital_gateway_xml_specification.pdf
 *
 */
class ChasePaymentech_NewOrder extends ChasePaymentech_Request {

    protected $_available_fields = array(
        'AccountNum','Exp','CurrencyCode','CurrencyExponent','CardSecValInd','CardSecVal',
        'AVSzip','AVSaddress1','AVSaddress2','AVScity','AVSstate','AVSphoneNum',
        'AVSname','AVScountryCode','AVSDestzip','AVSDestaddress1','AVSDestaddress2',
        'AVSDestcity','AVSDeststate','AVSDestphoneNum','AVSDestname','AVSDestcountryCode',
        'CustomerProfileFromOrderInd','CustomerRefNum','CustomerProfileOrderOverrideInd',
        'Status','PriorAuthID','OrderID','Amount','Comments','ShippingRef','TaxInd','Tax',
        'RecurringInd','MBType','MBOrderIdGenerationMethod','MBRecurringStartDate',
        'MBRecurringEndDate','MBRecurringNoEndDateFlag','MBRecurringMaxBillings',
        'MBRecurringFrequency','MBDeferredBillDate','TxRefNum','AVSPhoneType',
        'AVSDestPhoneType','CustomerEmail','EmailAddressSubtype','CustomerIpAddress',
        'ShippingMethod'
    );

    public function authorize() {

        $xml = $this->_setRequest();

        $xml_order = $xml->NewOrder;
        $xml_order->MessageType = 'A';

        // Required to send an amount for Test Cases
        if(!($this->_test_mode === true && !empty($xml_order->Amount))){
            $xml_order->Amount = '000';
        }

        $this->_request_xml = $xml;

        return $this->_sendRequest();
    }

    public function authorizeAndCapture() {

        $xml = $this->_setRequest();

        $xml_order = $xml->NewOrder;
        $xml_order->MessageType = 'AC';

        $this->_request_xml = $xml;

        return $this->_sendRequest();

    }

    public function refund() {

        $xml = $this->_setRequest();

        $xml_order = $xml->NewOrder;
        $xml_order->MessageType = 'R';

        $this->_request_xml = $xml;

        return $this->_sendRequest();

    }

    private function _setRequest(){

        $xml = new SimpleXMLElement('<Request><NewOrder></NewOrder></Request>');
        $xml_order = $xml->NewOrder;

        $xml_order->OrbitalConnectionUsername = $this->_username;
        $xml_order->OrbitalConnectionPassword = $this->_password;
        $xml_order->IndustryType = $this->_industry_type;;
        $xml_order->MessageType = null;
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

        return $this->_response = new ChasePaymentech_NewOrder_Response($response);

    }

}

/**
 * ChasePaymentech_NewOrder_Response
 *
 * @author Routy Development LLC.
 * @copyright (c) 2012-2013 Routy Development LLC.
 * @package Chase Paymentech
 * @link http://http://download.chasepaymentech.com/docs/orbital/orbital_gateway_xml_specification.pdf XML API Docs
 */
class ChasePaymentech_NewOrder_Response extends ChasePaymentech_Response {

    public function __construct($response){

        $this->response = $response;

        if($response){

            try {

                $xml = new SimpleXMLElement($response);

            } catch(Exception $e){

                $this->approved = false;
                $this->declined = false;
                $this->error = true;
                $this->StatusMsg = 'Invalid response received. Unable to parse XML response.';
                return;

            }

            if($xml->NewOrderResp){

                if($xml->NewOrderResp->ProcStatus == '0'){

                    $xml_resp = $xml->NewOrderResp;

                    $this->approved = ($xml_resp->ApprovalStatus == self::APPROVED);
                    $this->declined = ($xml_resp->ApprovalStatus == self::DECLINED);
                    $this->error    = ($xml_resp->ApprovalStatus == self::ERROR);

                    $this->ApprovalStatus = $xml_resp->ApprovalStatus;
                    $this->ProcStatus = $xml_resp->ProcStatus;
                    $this->RespCode = $xml_resp->RespCode;
                    $this->AVSRespCode = $xml_resp->AVSRespCode;
                    $this->CVV2RespCode = $xml_resp->CVV2RespCode;
                    $this->TxRefIdx = $xml_resp->TxRefIdx;
                    $this->TxRefNum = $xml_resp->TxRefNum;
                    $this->AuthCode = $xml_resp->AuthCode;
                    $this->StatusMsg = $xml_resp->StatusMsg;
                    $this->RespMsg = $xml_resp->RespMsg;
                    $this->HostRespCode = $xml_resp->HostRespCode;
                    $this->RespTime = $xml_resp->RespTime;
                    $this->HostAVSRespCode = $xml_resp->HostAVSRespCode;
                    $this->HostCVV2RespCode = $xml_resp->HostCVV2RespCode;

                    /*
                     * Profile Specific
                     *
                     */
                    if($xml_resp->CustomerRefNum){
                        $this->CustomerRefNum = $xml_resp->CustomerRefNum;
                    }


                } else {

                    $this->approved = false;
                    $this->declined = false;
                    $this->error = true;
                    $this->StatusMsg = 'Gateway rejected request. Gateway responded with message: '.$xml_resp->StatusMsg;
                    return;

                }

            }elseif( $xml->QuickResponse
                     && $xml->QuickResponse->StatusMsg ) {
                $this->approved = false;
                $this->declined = false;
                $this->error = true;
                $this->StatusMsg = $xml->QuickResponse->StatusMsg;
            } else {

                $this->approved = false;
                $this->declined = false;
                $this->error = true;
                $this->StatusMsg = 'Invalid XML received.';
                return;

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
