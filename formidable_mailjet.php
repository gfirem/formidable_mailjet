<?php
/*
 * Plugin Name:       Formidable MailJet integration
 * Plugin URI:        http://wwww.gfirem.com
 * Description:       Integrate formidable with MailJet. Include two fields to select the contact list and segment and action to send a Campaign
 * Version:           1.0.0
 * Author:            Guillermo Figueroa Mesa
 * Author URI:        http://wwww.gfirem.com
 * Text Domain:       formidable_mailjet-locale
 * License:           Apache License 2.0
 * License URI:       http://www.apache.org/licenses/
 * Domain Path:       /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'FormidableMailJet' ) ) :

	require_once 'plugin-update-checker/plugin-update-checker.php';

	$myUpdateChecker = PucFactory::buildUpdateChecker( 'http://gfirem.com/update-services/?action=get_metadata&slug=formidable_mailjet', __FILE__ );
	$myUpdateChecker->addQueryArgFilter( 'appendFormidableMailJetQueryArgsCredentials' );

	/**
	 * Append the order key to the update server URL
	 *
	 * @param $queryArgs
	 *
	 * @return
	 */
	function appendFormidableMailJetQueryArgsCredentials( $queryArgs ) {
		$queryArgs['order_key'] = get_option( FormidableMailJetManager::getShort() . 'licence_key', '' );

		return $queryArgs;
	}

	class FormidableMailJet {

		/**
		 * Instance of this class.
		 *
		 * @var object
		 */
		protected static $instance = null;

		/**
		 * Initialize the plugin.
		 */
		private function __construct() {

			define( 'FORMIDABLE_MAILJET_IMAGE', plugin_dir_url( __FILE__ ) . "img/" );
			define( 'FORMIDABLE_MAILJET_VIEW', plugin_dir_path( __FILE__ ) . "view/" );
			define( 'FORMIDABLE_MAILJET_TEMPLATE', plugin_dir_path( __FILE__ ) . "template/" );
			define( 'FORMIDABLE_MAILJET_JS', plugin_dir_url( __FILE__ ) . "assets/js/" );
			define( 'FORMIDABLE_MAILJET_CSS', plugin_dir_url( __FILE__ ) . "assets/css/" );

			// Load plugin text domain
			add_action( 'init', array( $this, 'load_plugin_textdomain' ) );

			require_once 'class/FormidableMailJetManager.php';
			$manager = new FormidableMailJetManager();

		}

		/**
		 * Return an instance of this class.
		 *
		 * @return object A single instance of this class.
		 */
		public static function get_instance() {
			// If the single instance hasn't been set, set it now.
			if ( null == self::$instance ) {
				self::$instance = new self;
			}

			return self::$instance;
		}

		/**
		 * Load the plugin text domain for translation.
		 */
		public function load_plugin_textdomain() {
			load_plugin_textdomain( 'formidable_mailjet-locale', false, basename( dirname( __FILE__ ) ) . '/languages' );
		}
	}

	add_action( 'plugins_loaded', array( 'FormidableMailJet', 'get_instance' ) );

endif;