<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class FormidableMailJetDTField {
	
	function __construct() {
		if ( class_exists( "FrmProAppController" ) ) {
			add_action( 'frm_pro_available_fields', array( $this, 'add_formidable_key_field' ) );
			add_action( 'frm_before_field_created', array( $this, 'set_formidable_key_field_options' ) );
			add_action( 'frm_display_added_fields', array( $this, 'show_formidable_key_field_admin_field' ) );
			add_action( 'frm_form_fields', array( $this, 'show_formidable_key_field_front_field' ), 10, 2 );
			add_action( 'frm_display_value', array( $this, 'display_formidable_key_field_admin_field' ), 10, 3 );
			add_filter( 'frm_display_field_options', array( $this, 'add_formidable_key_field_display_options' ) );
			add_filter( 'frmpro_fields_replace_shortcodes', array( $this, 'add_formidable_custom_short_code' ), 10, 4 );
//			add_action( 'wp_footer', array( $this, 'add_footer_script' ) );
//			add_action( 'admin_print_footer_scripts', array( $this, 'add_footer_script' ) );
//			add_shortcode( 'fmj_refresh_button', array( $this, "fmj_refresh_button_fnc" ) );
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
		$fields['mailjet_date_time'] = '<b class="gfirem_field">' . FormidableMailJetManager::t( "MJ Schedule" ) . '</b>';
		
		return $fields;
	}


	/**
	 * Set the default options for the field
	 *
	 * @param $fieldData
	 *
	 * @return mixed
	 */
	public function set_formidable_key_field_options( $fieldData ) {
		if ( $fieldData['type'] == 'mailjet_date_time' ) {
			$fieldData['name'] = FormidableMailJetManager::t( "MJ Schedule" );

			$defaults = array(
				'mailjet_date_time_format' => ''
			);

			foreach ( $defaults as $k => $v ) {
				$fieldData['field_options'][ $k ] = $v;
			}
		}

		return $fieldData;
	}

	/**
	 * Show the field placeholder in the admin area
	 *
	 * @param $field
	 */
	public function show_formidable_key_field_admin_field( $field ) {
		if ( $field['type'] != 'mailjet_date_time' ) {
			return;
		}
		?>
		<div class="frm_html_field_placeholder">
			<div class="frm_html_field"><?= FormidableMailJetManager::t( "Show the Date and Time." ) ?> </div>
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
		if ( $field['type'] != 'mailjet_date_time' ) {
			return;
		}


		$print_value = $field['default_value'];
		if ( ! empty( $field['value'] ) ) {
			$print_value = $field['value'];
		}

		$field['value'] = stripslashes_deep( $field['value'] );

		$html_id   = $field['field_key'];
		$file_name = str_replace( 'item_meta[' . $field['id'] . ']', 'file' . $field['id'], $field_name );

		$this->load_script( $print_value, $html_id );

		include FORMIDABLE_MAILJET_VIEW . 'field_datetime.php';
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
		if ( $field->type != 'mailjet_date_time' ) {
			return $value;
		}

		if ( empty( $value ) ) {
			return $value;
		}

		return $value;
	}

	private function load_script( $print_value = "", $field_id = "" ) {
		wp_enqueue_style( 'jquery.switchButton', FORMIDABLE_MAILJET_CSS . 'jquery.switchButton.css', array(), FormidableMailJetManager::getVersion() );
		wp_enqueue_style( 'jquery.datetimepicker', FORMIDABLE_MAILJET_CSS . 'jquery.datetimepicker.min.css', array(), FormidableMailJetManager::getVersion() );
		wp_enqueue_script( 'jquery.datetimepicker', FORMIDABLE_MAILJET_JS . 'jquery.datetimepicker.full.min.js', array( "jquery" ), FormidableMailJetManager::getVersion(), true );
		wp_enqueue_script( 'jquery.switchButton', FORMIDABLE_MAILJET_JS . 'jquery.switchButton.js', array( "jquery", "jquery-effects-core" ), FormidableMailJetManager::getVersion(), true );
		wp_enqueue_script( 'formidable_mailjet_date_time', FORMIDABLE_MAILJET_JS . 'formidable_mailjet_date_time.js', array( "jquery" ), FormidableMailJetManager::getVersion(), true );
		$params = array(
			"now_date" => date( 'Y/m/d' ),
			"now_time" => date( 'H:i' )
		);
		if ( ! empty( $print_value ) ) {
			$params["print_value"] = $print_value;
		}
		if ( ! empty( $field_id ) ) {
			$params["field_id"] = $field_id;
		}
		wp_localize_script( 'formidable_mailjet_date_time', 'formidable_mailjet_date_time', $params );
	}

	/**
	 * Set display option for the field
	 *
	 * @param $display
	 *
	 * @return mixed
	 */
	public function add_formidable_key_field_display_options( $display ) {
		if ( $display['type'] == 'mailjet_date_time' ) {
			$display['unique']         = false;
			$display['required']       = true;
			$display['read_only']      = true;
			$display['description']    = true;
			$display['options']        = true;
			$display['label_position'] = true;
			$display['css']            = true;
			$display['conf_field']     = false;
			$display['invalid']        = true;
			$display['default_value']  = true;
			$display['visibility']     = true;
			$display['size']           = true;
		}
		
		return $display;
	}

	/**
	 * Add custom shortcode
	 *
	 * @param $replace_with
	 * @param $tag
	 * @param $atts
	 * @param $field
	 *
	 * @return string
	 */
	public function add_formidable_custom_short_code( $replace_with, $tag, $atts, $field ) {
		if ( $field->type != "mailjet_date_time" ) {
			return $replace_with;
		}

		return $replace_with;
	}

}