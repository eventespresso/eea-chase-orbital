<?php

if (!defined('EVENT_ESPRESSO_VERSION')) {
	exit('No direct script access allowed');
}

/**
 *
 * EE_PMT_Chase_Orbital
 *
 *
 * @package			Event Espresso
 * @subpackage
 * @author				Mike Nelson
 *
 */
class EE_PMT_Chase_Orbital extends EE_PMT_Base{

	/**
	 *
	 * @param EE_Payment_Method $pm_instance
	 * @return EE_PMT_Chase_Orbital
	 */
	public function __construct($pm_instance = NULL) {
		require_once($this->file_folder().'EEG_Chase_Orbital.gateway.php');
		$this->_gateway = new EEG_Chase_Orbital();
		$this->_pretty_name = __("Chase Orbital", 'event_espresso');
		parent::__construct($pm_instance);
	}

	/**
	 * Adds the help tab
	 * @see EE_PMT_Base::help_tabs_config()
	 * @return array
	 */
	public function help_tabs_config(){
		return array(
			$this->get_help_tab_name() => array(
				'title' => __('Chase Orbital Settings', 'event_espresso'),
				'filename' => 'chase_orbital_onsite'
				),
		);
	}

	/**
	 * @param \EE_Transaction $transaction
	 * @return \EE_Billing_Attendee_Info_Form
	 */
	public function generate_new_billing_form( EE_Transaction $transaction = null ) {
		$form = new EE_Billing_Attendee_Info_Form( $this->_pm_instance, array(
			'name'        => 'Chase_Orbital_Form',
			'subsections' => array(
				//this will become the payments status when processing payments on this mock object
				'status'      => new EE_Select_Input(
					array(
						'Approved' => 'Approved',
						'Pending'  => 'Pending',
						'Declined' => 'Declined',
						'Failed'   => 'Failed'
					),
					array( 'html_help_text' => __( 'What the payment status will be set to', 'event_espresso' ) )
				),
				'credit_card' => new EE_Credit_Card_Input( array(
					'required'        => true,
					'html_label_text' => __( 'Credit Card', 'event_espresso' ),
				) ),
				'exp_month'   => new EE_Credit_Card_Month_Input( true, array(
					'required'        => true,
					'html_label_text' => __( 'Expiry Month', 'event_espresso' )
				) ),
				'exp_year'    => new EE_Credit_Card_Year_Input( array(
					'required'        => true,
					'html_label_text' => __( 'Expiry Year', 'event_espresso' ),
				) ),
				'cvv'         => new EE_CVV_Input( array(
					'required'        => true,
					'html_label_text' => __( 'CVV', 'event_espresso' )
				) ),
			)
		) );
		return $form;
	}

	/**
	 * Gets the form for all the settings related to this payment method type
	 * @return EE_Payment_Method_Form
	 */
	public function generate_new_settings_form() {
		EE_Registry::instance()->load_helper('Template');
		$credit_card_types = $this->credit_card_types();
		$form = new EE_Payment_Method_Form(array(
			'extra_meta_inputs'=>array(
				'merchant_id'=>new EE_Text_Input(array(
					'html_label_text'=>  sprintf(__("Connection Merchant ID %s", "event_espresso"), $this->get_help_tab_link() )
				)),
				'connection_username'=>new EE_Text_Input(array(
					'html_label_text'=>  sprintf(__("Connection Username %s", "event_espresso"), $this->get_help_tab_link() )
				)),
				'connection_password'=>new EE_Password_Input(array(
					'html_label_text'=>  sprintf(__("Connection Password %s", "event_espresso"), $this->get_help_tab_link() )
				)),
				'terminal_id'=>new EE_Text_Input(array(
					'html_label_text'=>  sprintf(__("Terminal ID %s", "event_espresso"), $this->get_help_tab_link() ),
					'default' => '001'
				)),
				'bin'=>new EE_Text_Input(array(
					'html_label_text'=>  sprintf(__("BIN %s", "event_espresso"), $this->get_help_tab_link() ),
					'default' => '000002'
				)),
				'currency'=>new EE_Checkbox_Input(
					array(
						'840' => __( 'USD', 'event_espresso' ),
						'124' => __( 'CAD', 'event_espresso' )
					),
					array(
						'html_label_text'=>  sprintf( __("Currency %s", 'event_espresso'),  $this->get_help_tab_link() ),
						'html_help_text'=>  __("Currency that transactions are to be captured in.", 'event_espresso'),
						'default' => '840',
						'required' => true
					)
				),
				'credit_card_types' => new EE_Checkbox_Multi_Input(
					$credit_card_types,
					array(
						'html_label_text' => sprintf( __("Required Payment Form Fields %s", 'event_espresso'),  $this->get_help_tab_link() ),
						'default' => array_keys( $credit_card_types )
					)),
				'test_transactions'=>new EE_Yes_No_Input(
					array(
						'html_label_text'=>  sprintf( __("Enable test mode? %s", 'event_espresso'),  $this->get_help_tab_link() ),
						'html_help_text'=>  __("Direct transaction to test end point.", 'event_espresso'),
						'default' => false,
						'required' => true
					)
				)
			)
		));

		return $form;
	}

	/**
	 * Returns an array of accepted credit card types
	 * @return array
	 */
	public function credit_card_types() {
		return array(
			'Visa' => __( 'Visa', 'event_espresso' ),
			'MasterCard' => __( 'MasterCard', 'event_espresso' ),
			'Discover' => __( 'Discover', 'event_espresso' ),
			'American Express' => __( 'American Express', 'event_espresso' )
		);
	}

	/**
	 * Returns an array where keys are the slugs for billing inputs, and values
	 * are their i18n names
	 * @return array
	 */
	public function billing_input_names() {
		return array(
			'first_name' => __( 'First Name', 'event_espresso' ),
			'last_name' => __('Last Name', 'event_espresso'),
			'email' => __( 'Email', 'event_espresso' ),
			'company' => __( 'Company', 'event_espresso' ),
			'address' => __('Address', 'event_espresso'),
			'address2' => __('Address2', 'event_espresso'),
			'city' => __('City', 'event_espresso'),
			'state' => __('State', 'event_espresso'),
			'country' => __('Country', 'event_espresso'),
			'zip' =>  __('Zip', 'event_espresso'),
			'phone' => __('Phone', 'event_espresso'),
			'fax' => __( 'Fax', 'event_espresso' ),
			'cvv' => __('CVV', 'event_espresso')
		);
	}

	/**
	 * Overrides parent so we always have all billing inputs in the returned array,
	 * not just the ones included at the time. This helps simplify the gateway code
	 * @param type $billing_form
	 * @return array
	 */
	protected function _get_billing_values_from_form( $billing_form ){
		$all_billing_values_empty = array();
		foreach( array_keys( $this->billing_input_names() ) as $input_name ) {
			$all_billing_values_empty[ $input_name ] = '';
		}
		return array_merge(
			$all_billing_values_empty,
			parent::_get_billing_values_from_form($billing_form) );

	}
}
// End of file EE_PMT_Chase_Orbital.php