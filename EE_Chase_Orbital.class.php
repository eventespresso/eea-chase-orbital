<?php if ( ! defined( 'EVENT_ESPRESSO_VERSION' )) { exit(); }
/**
 * ------------------------------------------------------------------------
 *
 * Class  EE_Chase_Orbital
 *
 * @package			Event Espresso
 * @subpackage		espresso-chase-orbital
 * @author			    Brent Christensen
 * @ version		 	$VID:$
 *
 * ------------------------------------------------------------------------
 */
// define the plugin directory path and URL
define( 'EE_CHASE_ORBITAL_BASENAME', plugin_basename( EE_CHASE_ORBITAL_PLUGIN_FILE ));
define( 'EE_CHASE_ORBITAL_PATH', plugin_dir_path( __FILE__ ));
define( 'EE_CHASE_ORBITAL_URL', plugin_dir_url( __FILE__ ));

Class  EE_Chase_Orbital extends EE_Addon {

	/**
	 * class constructor
	 */
	public function __construct() {
	}

	public static function register_addon() {
		// register addon via Plugin API
		EE_Register_Addon::register(
			'Chase_Orbital',
			array(
				'version' => EEA_CHASE_ORBITAL_VERSION,
				'min_core_version' => EEA_CHASE_ORBITAL_MIN_CORE_VERSION,
				'main_file_path' => EEA_CHASE_ORBITAL_PLUGIN_FILE,
                'admin_callback' => 'additional_admin_hooks',
				// if plugin updat/e engine is being used for auto-updates. not needed if PUE is not being used.
				'pue_options' => array(
					'pue_plugin_slug' => 'espresso_chase_orbital',
					'plugin_basename' => EEA_CHASE_ORBITAL_BASENAME,
					'checkPeriod' => '24',
					'use_wp_update' => FALSE,
				),
				'payment_method_paths' => array(
					EEA_CHASE_ORBITAL_PATH . 'payment_methods' . DS . 'Chase_Orbital',
				),
		));
	}



	/**
	 * 	additional_admin_hooks
	 *
	 *  @access 	public
	 *  @return 	void
	 */
	public function additional_admin_hooks() {
		// is admin and not in M-Mode ?
		if ( is_admin() && ! EE_Maintenance_Mode::instance()->level() ) {
			add_filter( 'plugin_action_links', array( $this, 'plugin_actions' ), 10, 2 );
		}
	}



	/**
	 * plugin_actions
	 *
	 * Add a settings link to the Plugins page, so people can go straight from the plugin page to the settings page.
	 * @param $links
	 * @param $file
	 * @return array
	 */
	public function plugin_actions( $links, $file ) {
		if ( $file == EEA_CHASE_ORBITAL_BASENAME ) {
			// before other links
			array_unshift( $links, '<a href="admin.php?page=espresso_payment_settings">' . __('Settings') . '</a>' );
		}
		return $links;
	}






}
// End of file EE_Chase_Orbital.class.php
// Location: wp-content/plugins/espresso-chase-orbital/EE_Chase_Orbital.class.php
