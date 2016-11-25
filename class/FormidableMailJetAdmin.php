<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


class FormidableMailJetAdmin {

	function __construct() {
		require_once 'GManagerFactory.php';

		add_filter( 'frm_add_settings_section', array( $this, 'add_formidable_key_field_setting_page' ) );
		add_filter( 'plugin_action_links', array( $this, 'add_formidable_key_field_setting_link' ), 9, 2 );

		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_js' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_js' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_style' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_style' ) );

		add_action( 'wp_ajax_fmj_update_overview', array( $this, 'fmj_update_overview' ) );
		add_action( 'wwp_ajax_nopriv_fmj_update_overview', array( $this, 'fmj_update_overview' ) );

		add_action( 'wp_ajax_fmj_refresh_overview', array( $this, 'fmj_refresh_overview' ) );
		add_action( 'wwp_ajax_nopriv_fmj_refresh_overview', array( $this, 'fmj_refresh_overview' ) );

	}

	/**
	 * Ajax response to update_overview
	 */
	public function fmj_refresh_overview() {
		if ( ! ( is_array( $_POST ) && defined( 'DOING_AJAX' ) && DOING_AJAX ) ) {
			return;
		}

		if ( ! check_ajax_referer( 'fmj_refresh_status' ) ) {
			die();
		}

		if ( ! empty( $_POST["request_part"] ) && $_POST["request_part"] != "full" ) {
			if ( ! empty( $_POST["request_target"] ) && ! empty( $_POST["entry_id"] ) && ! empty( $_POST["field_id"] )
			) {
				echo self::get_refresh_overview( $_POST["request_part"], $_POST["request_target"], $_POST["entry_id"], $_POST["field_id"] );
			}
		}

		die();
	}

	public static function get_refresh_overview( $request_part, $request_target, $entry_id, $field_id ) {
		$overview = FrmEntryMeta::get_entry_meta_by_field( $entry_id, $field_id );

		if ( ! empty( $overview ) ) {
			$overview = (array) json_decode( $overview );
			$result   = $result = self::get_overview( $request_part, $overview["NewsLetterID"], $entry_id, $field_id, stripcslashes( $request_target ) );

		} else {
			//Todo in this case the entry need to be submit almost one time to get the initial data from MJ
			$result = array();
		}

		return json_encode( $result );
	}

	/**
	 * Ajax response to update_overview
	 */
	public function fmj_update_overview() {
		if ( ! ( is_array( $_POST ) && defined( 'DOING_AJAX' ) && DOING_AJAX ) ) {
			return;
		}

		if ( ! check_ajax_referer( 'fmj_update_status' ) ) {
			die();
		}

		if ( ! empty( $_POST["request_part"] ) && $_POST["request_part"] == "full" ) {
			if ( ! empty( $_POST["newsletter_id"] ) && ! empty( $_POST["target_id"] )
			     && ! empty( $_POST["entry_id"] ) && ! empty( $_POST["field_id"] )
			) {
				echo self::get_update_overview( $_POST["request_part"], $_POST["newsletter_id"], $_POST["entry_id"], $_POST["field_id"], $_POST["target_id"] );
			}
		}

		die();
	}

	public static function get_update_overview( $request_part, $newsletter_id, $target_id, $entry_id, $field_id ) {
		$result = self::get_overview( $request_part, $newsletter_id, $entry_id, $field_id, $target_id );

		return json_encode( $result );
	}

	private static function get_overview( $request_part, $newsletter_id, $entry_id, $field_id, $target_id = null ) {
		$error_str      = "";
		$status         = "";
		$last_update    = "";
		$refresh_factor = get_option( FormidableMailJetManager::getShort() . 'refresh_factor' );
		if ( empty( $refresh_factor ) ) {
			$refresh_factor = 10;
		}
		try {
			$mj_sender      = new MailJetSend();
			$status_from_db = FrmEntryMeta::get_entry_meta_by_field( $entry_id, $field_id );
			if ( ! empty( $status_from_db ) ) {
				$status_from_db = json_decode( $status_from_db );
				$last_update    = $status_from_db->LastUpdate;
				if ( empty( $last_update ) ) {
					$status = $mj_sender->overview_newsletter( $newsletter_id );
					$r      = FrmEntryMeta::add_entry_meta( $entry_id, $field_id, null, json_encode( $status[0] ) );
				} else {
					$elapsed_time = time() - $last_update;
					if ( $elapsed_time > ( $refresh_factor * 60 ) ) {
						$status = $mj_sender->overview_newsletter( $newsletter_id );
						$r      = FrmEntryMeta::update_entry_meta( $entry_id, $field_id, null, json_encode( $status[0] ) );
					} else {
						$status[0] = $status_from_db;
					}
				}

			} else {
				$status = $mj_sender->overview_newsletter( $newsletter_id );
				$r      = FrmEntryMeta::add_entry_meta( $entry_id, $field_id, null, json_encode( $status[0] ) );
			}
		} catch ( FormidableMailJetException $ex ) {
			$body = $ex->getBody();
			if ( ! empty( $body ) && is_array( $body ) ) {

				foreach ( $body as $key => $value ) {
					if ( ! empty( $value ) ) {
						$error_str .= $key . " : " . $value . "<br/>";
					}
				}
			} else {
				$error_str = $ex->getMessage();
			}
			FormidableMailJetLogs::log( array(
				'action'         => "Send",
				'object_type'    => FormidableMailJetManager::getShort(),
				'object_subtype' => "detail_error",
				'object_name'    => $error_str,
			) );
		}
		$result = array(
			"request_part"     => $request_part,
			"status"           => $status[0],
			"entry_id"         => $entry_id,
			"field_id"         => $field_id,
			"newsletter_id"    => $newsletter_id,
			"error"            => $error_str,
			"refresh_factor"   => $refresh_factor * 60,
			"current_time"     => date( "d/m/Y h:i:s", time() ),
			"elapsed_time"     => time() - $last_update,
			"last_update_time" => date( "d/m/Y h:i:s", $last_update ),
		);

		if ( ! empty( $target_id ) ) {
			$result["target_id"] = $target_id;
		}

		return $result;
	}

	/**
	 * Include styles
	 */
	public function enqueue_style() {
		wp_enqueue_style( 'jquery' );
		wp_enqueue_style( "jquery-ui-dialog" );
		wp_enqueue_style( "formidable_mailjet", FORMIDABLE_MAILJET_CSS . 'formidable_mailjet.css' );
	}

	/**
	 * Include script
	 */
	public function enqueue_js() {
		wp_deregister_script( 'thickbox' );
		wp_enqueue_script( 'jquery' );
		wp_enqueue_script( "jquery-effects-core" );
		wp_enqueue_script( 'jquery-ui-core' );
		wp_enqueue_script( 'jquery-ui-dialog' );
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