<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( "GDebug" ) ):
	class GDebug {
		public static function get_var_name($var){
			foreach($GLOBALS as $var_name => $value) {
				if ( $value === $var ) {
					return $var_name;
				}
			}
			return false;
		}
	}
endif;