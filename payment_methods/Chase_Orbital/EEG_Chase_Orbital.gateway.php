<?php

if (!defined('EVENT_ESPRESSO_VERSION')) {
	exit('No direct script access allowed');
}

/**
 *
 * EEG_Chase_Orbital
 *
 * Just approves payments where billing_info[ 'credit_card' ] == 1.
 * If $billing_info[ 'credit_card' ] == '2' then it's pending.
 * All others get refused
 *
 * @package			Event Espresso
 * @subpackage
 * @author			Mike Nelson, Nick Routsong
 *
 */
class EEG_Chase_Orbital extends EE_Onsite_Gateway{

	protected $_connection_username;
	protected $_connection_password;
	protected $_merchant_id;
	protected $_terminal_id;
	protected $_bin;
	protected $_accepted_cards;
	protected $_validate_ssl_locally   = false;
	protected $_disable_ssl_validation = true;
	protected $_show_default_error = false;
	protected $_default_error;

	/**
	 * All the currencies supported by this gateway. Add any others you like,
	 * as contained in the esp_currency table
	 * @var array
	 */
	protected $_currencies_supported = array('USD', 'CAD');

	/**
	 *
	 * @param EEI_Payment $payment
	 * @param array $billing_info
	 * @return \EE_Payment|\EEI_Payment
	 */
	public function do_direct_payment($payment, $billing_info = null) {

		require_once(dirname(__FILE__) . '/lib/ChasePaymentech.php');

		$this->_default_error = __('An error occurred while processing your transaction. Please try again or contact us to complete your order.', 'event-espresso');

		$card_num = preg_replace('/[^0-9]+/', '', $billing_info['credit_card']);
		$cvv = preg_replace('/[^0-9]+/', '', $billing_info['cvv']);
        $payment_id = uniqid();
        $payment->set_txn_id_chq_nmbr( $payment_id );
		$fields = array(
			'AccountNum' => $card_num,
			'Exp' => $billing_info['exp_month'].$billing_info['exp_year'],
			'CurrencyCode' => $payment->currency_code() === 'USD' ? '840' : '124',//840: USD, 124: CAD
			'CurrencyExponent' => 2,
			'CardSecValInd' => null,
			'CardSecVal' => $cvv,
			'Amount' => number_format($payment->amount(), 2, '', ''), //Chase expects 1.00 to be 100
			'AVSzip' => substr($billing_info['zip'],0,10),
			'AVSaddress1' => substr($billing_info['address'],0,30),
			'AVSaddress2' => ($billing_info['address2']) ? substr($billing_info['address2'],0,30) : null,
			'AVScity' => substr($billing_info['city'],0,20),
			'AVSstate' => substr($billing_info['state'],0,2), // 2 Characters Max
			'AVScountryCode' => (in_array($billing_info['country'], array('US', 'CA', 'GB', 'UK'))) ? $billing_info['country'] : ' ', //Chase only expects a country code for the 4 countries in the array
			'AVSphoneNum' => substr(preg_replace('/\D/', '', $billing_info['phone']),0,14),
			'AVSname' => substr($billing_info['first_name'] . ' ' .$billing_info['last_name'],0,30),
			'OrderID' => $payment_id // Set your own custom Order ID, up to 22 characters in length
		);

		/*
         * Visa & Discover require that the CardSecValInd be set
         *
         */
		$card_type = ChasePaymentech_Request::getCreditCardBrand($card_num);

		if(in_array($card_type, array('Visa','Discover'))){
			$fields['CardSecValInd'] = (is_numeric($cvv)) ? 1 : 9;
		}
		$this->_log_clean_request_fields( $fields, $payment );

		$sale = new ChasePaymentech_NewOrder(
			$this->_connection_username,
			$this->_connection_password,
			$this->_merchant_id,
			array(
				'bin' => $this->_bin,
				'terminal_id' => $this->_terminal_id
			)
		);

		if($this->_validate_ssl_locally && file_exists(dirname(__FILE__) . '/lib/cacert.pem')){
			$sale->setCurlOption(CURLOPT_CAINFO, dirname(__FILE__) . '/lib/cacert.pem');
		}

		if($this->_disable_ssl_validation){
			$sale->setCurlOption(CURLOPT_SSL_VERIFYHOST, 0);
			$sale->setCurlOption(CURLOPT_SSL_VERIFYPEER, 0);
		}

		$sale->setTestMode($this->_debug_mode);
		// $sale->setLogFile($this->log_file);
		// $sale->setLogRequests($this->log_requests, $force = true);
		$sale->setFields($fields);

		$response = $sale->authorizeAndCapture();
		$this->log(
			array(
				'approved' => (string)$response->approved,
				'declined' => (string)$response->declined,
				'error' => (string)$response->error,
				'ProcStatus' => (string)$response->ProcStatus instanceof SimpleXMLElement ? $response->ProcStatus->asXML() : '',
				'RespCode' => (string)$response->RespCode,
				'ApprovalStatus' => (string)$response->ApprovalStatus,
				'response' => (string)htmlentities( $response->response )
			),
			$payment
		);
        $payment->set_details( $response );

		if (!isset($response->RespCode) || trim($response->RespCode) != '00') {

			$payment->set_status( $this->_pay_model->declined_status() );

			if($this->_show_default_error !== true) {
				$error_message = ($response->StatusMsg && !empty($response->StatusMsg)) ? (string) $response->StatusMsg : $this->_default_error;
				$payment->set_gateway_response( (string)$error_message );
			} else {
				$payment->set_gateway_response( (string)$this->_default_error );
			}
		} else {
			$payment->set_status( $this->_pay_model->approved_status() );
			$payment->set_gateway_response( __( 'Payment Accepted', 'event_espresso' ) );
		}

		return $payment;
	}
	
	/**
	 * Logs the request fields (removing credit card and expiration date)
	 * @param array $request_fields
	 * @param EE_Payment $payment
	 */
	protected function _log_clean_request_fields( $request_fields, $payment ) {
		unset( $request_fields['AccountNum'], $request_fields['Exp'] );
		$this->log( 
			array(
				'cleaned request fields' => $request_fields
			), 
			$payment
		);
	}

}

// End of file EEG_Chase_Orbital.php