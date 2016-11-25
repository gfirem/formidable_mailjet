<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class FormidableMailJetLogs {
	function __construct() {
		add_filter( 'aal_init_roles', array( $this, 'aal_init_roles' ) );
	}

	public function aal_init_roles( $roles ) {
		$roles_existing          = $roles['manage_options'];
		$roles['manage_options'] = array_merge( $roles_existing, array( FormidableMailJetManager::getShort() ) );

		return $roles;
	}

	public static function log( $args ) {
		if ( function_exists( "aal_insert_log" ) ) {
			aal_insert_log( $args );
		}
	}

}