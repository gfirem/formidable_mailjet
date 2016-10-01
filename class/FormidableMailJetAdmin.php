<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


class FormidableMailJetAdmin {

	function __construct() {
		require_once 'GManagerFactory.php';

		add_filter( 'frm_add_settings_section', array( $this, 'add_formidable_key_field_setting_page' ) );
		add_filter( 'plugin_action_links', array( $this, 'add_formidable_key_field_setting_link' ), 9, 2 );

	}

	/**
	 * Add setting page to global formidable settings
	 *
	 * @param $sections
	 *
	 * @return mixed
	 */
	public function add_formidable_key_field_setting_page( $sections ) {
		$sections['mailjet_integration'] = array(
			'name'     => FormidableMailJetManager::t( "MailJet" ),
			'class'    => 'FormidableMailJetSettings',
			'function' => 'route',
		);

		return $sections;
	}

	/**
	 * Add a "Settings" link to the plugin row in the "Plugins" page.
	 *
	 * @param $links
	 * @param string $pluginFile
	 *
	 * @return array
	 * @internal param array $pluginMeta Array of meta links.
	 */
	public function add_formidable_key_field_setting_link( $links, $pluginFile ) {
		if ( $pluginFile == 'formidable_mailjet/formidable_mailjet.php' ) {
			array_unshift( $links, FormidableMailJetManager::get_setting_link() );
		}

		return $links;
	}
}