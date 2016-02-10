<?php

if (!defined('EVENT_ESPRESSO_VERSION')) {
	exit('No direct script access allowed');
}

/**
 *
 * EEG_Chase_Orbital
 *
 * Just approves payments where billing_info[ 'credit_card' ] == 1.
 * If $billing_info[ 'credit_card' ] == '2' then its pending.
 * All others get refused
 *
 * @package			Event Espresso
 * @subpackage
 * @author				Mike Nelson
 *
 */
class EEG_Chase_Orbital extends EE_Onsite_Gateway{

	/**
	 * All the currencies supported by this gateway. Add any others you like,
	 * as contained in the esp_currency table
	 * @var array
	 */
	protected $_currencies_supported = array(
					'USD',
					'GBP',
					'CAD',
					'AUD');



	/**
	 *
	 * @param EEI_Payment $payment
	 * @param array $billing_info
	 * @return \EE_Payment|\EEI_Payment
	 */
	public function do_direct_payment($payment, $billing_info = null) {
		$this->log( $billing_info, $payment );
		switch( $billing_info[ 'status' ] ){
			case 'Approved':
				$payment->set_status( $this->_pay_model->approved_status() );
				$payment->set_gateway_response( "Payment accepted");
				break;
			case 'Pending':
				$payment->set_status( $this->_pay_model->pending_status() );
				$payment->set_gateway_response( "WE are on vacation, we will process your payment when we get back.");
				break;
			case 'Declined':
				$payment->set_status( $this->_pay_model->declined_status() );
				$payment->set_gateway_response( "You don't have enough spunk, payment was declined");
				break;
			case 'Failed':
				$payment->set_status( $this->_pay_model->failed_status() );
				$payment->set_gateway_response( "Mice got into our servers. The exterminator will be over after he finishes his day job. Sorry");
		}
		return $payment;
	}
}

// End of file EEG_Chase_Orbital.php