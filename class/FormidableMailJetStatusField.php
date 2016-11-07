<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class FormidableMailJetStatusField {
	
	function __construct() {
		if ( class_exists( "FrmProAppController" ) ) {
			add_action( 'frm_pro_available_fields', array( $this, 'add_formidable_key_field' ) );
		} else {
			add_action( 'frm_available_fields', array( $this, 'add_formidable_key_field' ) );
		}
		add_action( 'frm_display_added_fields', array( $this, 'show_formidable_key_field_admin_field' ) );
		add_action( 'frm_form_fields', array( $this, 'show_formidable_key_field_front_field' ), 10, 2 );
		add_action( 'frm_display_value', array( $this, 'display_formidable_key_field_admin_field' ), 10, 3 );
		add_filter( 'frm_display_field_options', array( $this, 'add_formidable_key_field_display_options' ) );
	}

	
	/**
	 * Add new field to formidable list of fields
	 *
	 * @param $fields
	 *
	 * @return mixed
	 */
	public function add_formidable_key_field( $fields ) {
		$fields['nailjet_status'] = FormidableMailJetManager::t( "Campaign Status" );
		
		return $fields;
	}

	/**
	 * Show the field placeholder in the admin area
	 *
	 * @param $field
	 */
	public function show_formidable_key_field_admin_field( $field ) {
		if ( $field['type'] != 'nailjet_status' ) {
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
		if ( $field['type'] != 'nailjet_status' ) {
			return;
		}

		$field['value'] = stripslashes_deep( $field['value'] );

		echo FormidableMailJetManager::t( "ID: " ) . $field['value'];
//		if ( ! empty( $field['value'] ) ) {
//			?><!--<span class="dashicons dashicons-yes" style="color: #008000;">Show a popup with the info</span>--><?php
//		} else {
//			?><!--<span class="dashicons dashicons-no-alt" style="color: #ff0000;">No info yet</span>--><?php
//		}
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
		if ( $field->type != 'nailjet_status' ) {
			return $value;
		}


//		if ( ! empty( $value ) ) {
//			$value = '<span class="dashicons dashicons-yes" style="color: #008000;">Show a popup with the info</span>';
//		} else {
//			$value = '<span class="dashicons dashicons-no-alt" style="color: #ff0000;">No info yet</span>';
//		}
		
		return FormidableMailJetManager::t( "ID: " ) . $value;
	}
	
	/**
	 * Set display option for the field
	 *
	 * @param $display
	 *
	 * @return mixed
	 */
	public function add_formidable_key_field_display_options( $display ) {
		if ( $display['type'] == 'nailjet_status' ) {
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
}