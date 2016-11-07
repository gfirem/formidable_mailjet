<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class FormidableMailJetManager {
	protected $plugin_slug;
	private static $plugin_short = 'FormidableMailJet';

	protected static $version;

	public function __construct() {

		$this->plugin_slug = 'FormidableMailJet';
		self::$version     = '0.1';

		//Load dependencies
		require_once 'vendor/autoload.php';

		require_once 'FormidableMailJetAdmin.php';
		$admin = new FormidableMailJetAdmin();

		require_once 'FormidableMailJetSettings.php';
		$settings = new FormidableMailJetSettings();

		require_once 'FormidableMailJetContactField.php';
		$contact = new FormidableMailJetContactField();

		require_once 'FormidableMailJetSegmentField.php';
		$segment = new FormidableMailJetSegmentField();

		require_once 'FormidableMailJetStatusField.php';
		$status = new FormidableMailJetStatusField();

		add_action( 'frm_registered_form_actions', array( $this, 'register_action' ) );

	}

	/**
	 * Register action
	 *
	 * @param $actions
	 *
	 * @return mixed
	 */
	public function register_action( $actions ) {
		$actions['formidable_mailjet_send'] = 'FormidableMailJetSendAction';
		require_once 'FormidableMailJetSendAction.php';

		return $actions;
	}

	static function getShort() {
		return self::$plugin_short;
	}

	static function getVersion() {
		return self::$version;
	}

	/**
	 * Translate string to main Domain
	 *
	 * @param $str
	 *
	 * @return string|void
	 */
	public static function t( $str ) {
		return __( $str, 'formidable_mailjet-locale' );
	}

	/**
	 * Get WP option for date format
	 *
	 * @return mixed|void
	 */
	public static function getDateFormat() {
		return get_option( 'date_format' );
	}

	public static function get_credential() {
		$public_key  = get_option( FormidableMailJetManager::getShort() . 'public_key' );
		$private_key = get_option( FormidableMailJetManager::getShort() . 'private_key' );
		$sender      = get_option( FormidableMailJetManager::getShort() . 'sender' );

		if ( ! empty( $public_key ) && ! empty( $private_key ) && ! empty( $sender ) ) {
			return array(
				"public"  => $public_key,
				"private" => $private_key,
				"sender"  => $sender,
			);
		} else {
			return false;
		}
	}

	public static function get_setting_link() {
		return sprintf( '<a href="%s">%s</a>', esc_attr( admin_url( 'admin.php?page=formidable-settings&t=mailjet_integration_settings' ) ), FormidableMailJetManager::t( "Settings" ) );
	}

}