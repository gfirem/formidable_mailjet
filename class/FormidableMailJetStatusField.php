<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class FormidableMailJetStatusField {
	
	function __construct() {
		if ( class_exists( "FrmProAppController" ) ) {
			add_action( 'frm_pro_available_fields', array( $this, 'add_formidable_key_field' ) );
			add_action( 'frm_display_added_fields', array( $this, 'show_formidable_key_field_admin_field' ) );
			add_action( 'frm_form_fields', array( $this, 'show_formidable_key_field_front_field' ), 10, 2 );
			add_action( 'frm_display_value', array( $this, 'display_formidable_key_field_admin_field' ), 10, 3 );
			add_filter( 'frm_display_field_options', array( $this, 'add_formidable_key_field_display_options' ) );
			add_filter( 'frmpro_fields_replace_shortcodes', array( $this, 'add_formidable_custom_short_code' ), 10, 4 );
			add_action( 'wp_footer', array( $this, 'add_footer_script' ) );
			add_action( 'admin_print_footer_scripts', array( $this, 'add_footer_script' ) );
			add_shortcode( 'fmj_refresh_button', array( $this, "fmj_refresh_button_fnc" ) );
		}
	}

	
	/**
	 * Add new field to formidable list of fields
	 *
	 * @param $fields
	 *
	 * @return mixed
	 */
	public function add_formidable_key_field( $fields ) {
		$fields['mailjet_status'] = '<b class="gfirem_field">'.FormidableMailJetManager::t( "Campaign Status" ). '</b>';
		
		return $fields;
	}

	/**
	 * Show the field placeholder in the admin area
	 *
	 * @param $field
	 */
	public function show_formidable_key_field_admin_field( $field ) {
		if ( $field['type'] != 'mailjet_status' ) {
			return;
		}
		?>
		<div class="frm_html_field_placeholder">
			<div class="frm_html_field"><?= FormidableMailJetManager::t( "Show the status of a Campaign." ) ?> </div>
		</div>
	<?php
	}

	/**
	 * Add the HTML for the field on the front end
	 *
	 * @param $field
	 * @param $field_name
	 *
	 * @return mixed
	 */
	public function show_formidable_key_field_front_field( $field, $field_name ) {
		if ( $field['type'] != 'mailjet_status' ) {
			return;
		}

		if ( ! empty( $field['value'] ) ) {
			$field['value'] = stripslashes_deep( $field['value'] );

			$data_entry_ids = FrmEntryMeta::getEntryIds( array( 'fi.form_id' => $field['form_id'], 'meta_value like' => $field['value'] ) );

			echo $this->get_status_string( $field['value'], $data_entry_ids[0], $field['id'] );


		} else {
			echo FormidableMailJetManager::t( "No data " ) . " <u>refresh</u>";
		}

	}

	/**
	 * Add the HTML to display the field in the admin area
	 *
	 * @param $value
	 * @param $field
	 * @param $atts
	 *
	 * @return string
	 */
	public function display_formidable_key_field_admin_field( $value, $field, $atts ) {
		if ( $field->type != 'mailjet_status' ) {
			return $value;
		}

		if ( empty( $value ) ) {
			return $value;
		}

		return $this->get_status_string( $value, $atts["entry_id"], $field->id );
	}

	/**
	 * @param $data_str
	 * @param null $entry_id
	 * @param null $field_id
	 *
	 * @return string
	 */
	private function get_status_string( $data_str, $entry_id = null, $field_id = null ) {
		wp_enqueue_script( 'formidable_mailjet_popup', FORMIDABLE_MAILJET_JS . 'formidable_mailjet_popup.js', array( "jquery" ), "1.0", true );
		$js_data = array(
			'ajax_url' => admin_url( 'admin-ajax.php' ),
			'nonce'    => wp_create_nonce( 'fmj_update_status' )
		);
		wp_localize_script( 'formidable_mailjet_popup', 'formidable_mj', $js_data );
		$data = (array) json_decode( $data_str );
		$str  = '<a class="mailjet_status_open_popup" field="' . $field_id . '" entry="' . $entry_id . '" newsletter="' . $data["NewsLetterID"] . '" target="' . $data["ID"] . '" href="#">' . FormidableMailJetManager::t( "Status" ) . '</a>';
		$str .= "<input type='hidden' id='" . $data["ID"] . "' value='" . $data_str . "'>";

		return $str;
	}

	/**
	 * Add footer script to open a popup
	 */
	public function add_footer_script() {
		?>
		<style>
			.ui-dialog-titlebar-close {
				visibility: hidden;
			}

			.ui-dialog .ui-state-error {
				padding: .3em;
			}

		</style>
		<?php
		$str = '<div class="mailjet_status_popup_container" title="' . FormidableMailJetManager::t( "Title" ) . '">';
		$str .= '<div class="status-loading-container"><img id="status-loading" src="' . home_url() . '/wp-content/plugins/formidable/images/ajax_loader.gif"/></div>';
		$str .= '<pre id="mailjet_status_popup_content"></pre>';
		$str .= '</div>';

		echo $str;
	}

	/**
	 * Set display option for the field
	 *
	 * @param $display
	 *
	 * @return mixed
	 */
	public function add_formidable_key_field_display_options( $display ) {
		if ( $display['type'] == 'mailjet_status' ) {
			$display['unique']         = false;
			$display['required']       = false;
			$display['description']    = true;
			$display['options']        = true;
			$display['label_position'] = true;
			$display['css']            = true;
			$display['conf_field']     = true;
		}
		
		return $display;
	}

	/**
	 * Add custom shortcode attr "mj_id"
	 *
	 * @param $replace_with
	 * @param $tag
	 * @param $atts
	 * @param $field
	 *
	 * @return string
	 */
	public function add_formidable_custom_short_code( $replace_with, $tag, $atts, $field ) {
		if ( $field->type != "mailjet_status" ) {
			return $replace_with;
		}

		return self::process_content( $replace_with, $atts, $field );
	}

	/**
	 * Process the field content
	 *
	 * @param $content
	 * @param $atts
	 *
	 * @return mixed|string
	 *
	 */
	public static function process_content( $content, $atts, $field ) {
		$result = "";
		$decode = json_decode( strtolower( $content ), true );
		if ( is_array( $decode ) ) {
			$result = $decode["ID"];
			if ( ! empty( $atts ) && is_array( $atts ) ) {
				if ( ! empty( $atts["popup"] ) && $atts["popup"] == "1" ) {
					$result = self::get_status_string( $content, $atts["entry_id"], $field->id );
				} else {
					foreach ( $atts as $key => $value ) {
						if ( $value == "1" ) {
							$result = "<span id='fmj_status_" . $key . "'>" . $decode[ $key ] . "</span>";
						}
					}
				}
			}
		} else {
			foreach ( $atts as $key => $value ) {
				if ( $value == "1" ) {
					$result = "<span id='fmj_status_" . $key . "'> - </span>";
				}
			}
		}

		return $result;
	}

	/**
	 * Print shortcode to put the refresh button
	 *
	 * @param $attr
	 * @param null $content
	 *
	 * @return string
	 */
	public function fmj_refresh_button_fnc( $attr, $content = null ) {
		$internal_attr = shortcode_atts( array(
			'entry_id' => '',
			'field_id' => '',
			'form_id'  => '',
			'refresh'  => '',
		), $attr );

		if ( empty( $content ) ) {
			return $content;
		} else {
			wp_enqueue_script( 'formidable_mailjet_refresh', FORMIDABLE_MAILJET_JS . 'formidable_mailjet_refresh.js', array(), '1.00', true );
			wp_localize_script( 'formidable_mailjet_refresh', 'formidable_mailjet_refresh', array(
				'ajax_url' => admin_url( 'admin-ajax.php' ),
				'nonce'    => wp_create_nonce( 'fmj_refresh_status' )
			) );
			$refresh = explode( ",", $internal_attr['refresh'] );
			if ( is_array( $refresh ) ) {
				foreach ( $refresh as $item ) {
					$item    = strtolower( $item );
					$content = str_replace( "fmj_status_" . $item, "fmj_status_" . $item . "_" . $internal_attr['entry_id'], $content );
				}
				$refresh = strtolower( json_encode( $refresh ) );

				$str = "<span id='fmj_refresh_btn_content_" . $internal_attr['entry_id'] . "'>" . do_shortcode( $content );
				$str .= "<img class='fmj_refresh_loading' id='status_loading_" . $internal_attr['entry_id'] . "' src='" . home_url() . "/wp-content/plugins/formidable/images/ajax_loader.gif'/>";
				$str .= "<a class='fmj_refresh_btn' field='" . $internal_attr['field_id'] . "' form='" . $internal_attr['form_id'] . "' entry='" . $internal_attr['entry_id'] . "' href='#'>" . FormidableMailJetManager::t( "Refresh" ) . "</a>";
				$str .= "<input type='hidden'value='" . $refresh . "' id='fmj_refresh_targets_" . $internal_attr['entry_id'] . "'>";
				$str .= "</span>";

				return $str;
			} else {
				return $content;
			}
		}
	}
}