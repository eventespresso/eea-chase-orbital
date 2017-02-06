<?php
/*
  Plugin Name: Event Espresso - Chase Paymentech Orbital
  Plugin URI: https://www.eventespresso.com
  Description: Chase Paymentech Orbital is an on-site payment method for Event Espresso for accepting credit and debit cards and is available to event organizers in many countries. An account with Chase Paymentech is required to accept payments.
  Version: 0.0.1.dev.003
  Author: Event Espresso
  Author URI: https://www.eventespresso.com
  Copyright 2014 Event Espresso (email : support@eventespresso.com)

  This program is free software; you can redistribute it and/or modify
  it under the terms of the GNU General Public License, version 2, as
  published by the Free Software Foundation.

  This program is distributed in the hope that it will be useful,
  but WITHOUT ANY WARRANTY; without even the implied warranty of
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.See the
  GNU General Public License for more details.

  You should have received a copy of the GNU General Public License
  along with this program; if not, write to the Free Software
  Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA02110-1301USA
 *
 * ------------------------------------------------------------------------
 *
 * Event Espresso
 *
 * Event Registration and Management Plugin for WordPress
 *
 * @ package		Event Espresso
 * @ author			Event Espresso
 * @ copyright	(c) 2008-2014 Event Espresso  All Rights Reserved.
 * @ license		https://eventespresso.com/support/terms-conditions/   * see Plugin Licensing *
 * @ link				https://www.eventespresso.com
 * @ version	 	EE4
 *
 * ------------------------------------------------------------------------
 */
define( 'EE_CHASE_ORBITAL_VERSION', '0.0.1.dev.003' );
define( 'EE_CHASE_ORBITAL_PLUGIN_FILE',  __FILE__ );

function load_espresso_chase_orbital() {
if ( class_exists( 'EE_Addon' )) {
	// chase_orbital version  
	require_once ( plugin_dir_path( __FILE__ ) . 'EE_Chase_Orbital.class.php' );
	EE_Chase_Orbital::register_addon();
}
}
add_action( 'AHEE__EE_System__load_espresso_addons', 'load_espresso_chase_orbital' );

// End of file espresso_chase_orbital.php
// Location: wp-content/plugins/espresso-chase-orbital/espresso_chase_orbital.php