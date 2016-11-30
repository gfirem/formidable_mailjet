<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Mailjet\Client;
use Mailjet\Resources;

class FormidableMailJetContactField {
	
	function __construct() {
		
		if ( class_exists( "FrmProAppController" ) ) {
			add_action( 'frm_pro_available_fields', array( $this, 'add_formidable_key_field' ) );
			add_action( 'frm_before_field_created', array( $this, 'set_formidable_key_field_options' ) );
			add_action( 'frm_display_added_fields', array( $this, 'show_formidable_key_field_admin_field' ) );
			add_action( 'frm_field_options_form', array( $this, 'field_formidable_key_field_option_form' ), 10, 3 );
			add_action( 'frm_update_field_options', array( $this, 'update_formidable_key_field_options' ), 10, 3 );
			add_action( 'frm_form_fields', array( $this, 'show_formidable_key_field_front_field' ), 10, 2 );
			add_action( 'frm_display_value', array( $this, 'display_formidable_key_field_admin_field' ), 10, 3 );
			add_filter( 'frm_display_field_options', array( $this, 'add_formidable_key_field_display_options' ) );
			add_filter( 'frmpro_fields_replace_shortcodes', array( $this, 'add_formidable_custom_short_code' ), 10, 4 );
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
		$fields['mailjet_contact'] = '<b class="gfirem_field">'.FormidableMailJetManager::t( "MJ Contact" ). '</b>';
		
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
		if ( $fieldData['type'] == 'mailjet_contact' ) {
			$fieldData['name'] = FormidableMailJetManager::t( "MJ Contact" );
			
			$defaults = array(
				'mailjet_contact_show_deleted' => ''
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
		if ( $field['type'] != 'mailjet_contact' ) {
			return;
		}
		$credential = FormidableMailJetManager::get_credential();
		?>
		<div class="frm_html_field_placeholder">
			<div class="frm_html_field"><?= ( $credential != false ) ? FormidableMailJetManager::t( "Pick a Contact list from MailJet." ) : "<div style='color: #ff0000;'>" . FormidableMailJetManager::t( "Configure your credentials." ) . "&nbsp;" . FormidableMailJetManager::get_setting_link() . "</div>"; ?> </div>
		</div>
	<?php
	}
	
	
	/**
	 * Display the additional options for the new field
	 *
	 * @param $field
	 * @param $display
	 * @param $values
	 */
	public function field_formidable_key_field_option_form( $field, $display, $values ) {
		if ( $field['type'] != 'mailjet_contact' ) {
			return;
		}
		
		$defaults = array(
			'mailjet_contact_show_deleted' => '',
		);
		
		foreach ( $defaults as $k => $v ) {
			if ( ! isset( $field[ $k ] ) ) {
				$field[ $k ] = $v;
			}
		}
		
		$show_deletes = "";
		if ( $field['mailjet_contact_show_deleted'] == "1" ) {
			$show_deletes = "checked='checked'";
		}
		?>
		<tr>
			<td>
				<label for="field_options[mailjet_contact_show_deleted_<?php echo $field['id'] ?>]"><?= FormidableMailJetManager::t( "Show delete items" ) ?></label>
				<span class="frm_help frm_icon_font frm_tooltip_icon" title="" data-original-title="<?= FormidableMailJetManager::t( "Check if you want to show deleted contacts list" ) ?>"></span>
			</td>
			<td>
				<input type="checkbox" <?= $show_deletes ?> name="field_options[mailjet_contact_show_deleted_<?php echo $field['id'] ?>]" id="field_options[mailjet_contact_show_deleted_<?php echo $field['id'] ?>]" value="1"/>
			</td>
		</tr>
	<?php
	}
	
	/**
	 * Update the field options from the admin area
	 *
	 * @param $field_options
	 * @param $field
	 * @param $values
	 *
	 * @return mixed
	 */
	public function update_formidable_key_field_options( $field_options, $field, $values ) {
		if ( $field->type != 'mailjet_contact' ) {
			return $field_options;
		}
		
		$defaults = array(
			'mailjet_contact_show_deleted' => '',
		);
		
		foreach ( $defaults as $opt => $default ) {
			$field_options[ $opt ] = isset( $values['field_options'][ $opt . '_' . $field->id ] ) ? $values['field_options'][ $opt . '_' . $field->id ] : $default;
		}
		
		return $field_options;
	}
	
	/**
	 * Add the HTML for the field on the front end
	 *
	 * @param $field
	 * @param $field_name
	 */
	public function show_formidable_key_field_front_field( $field, $field_name ) {
		if ( $field['type'] != 'mailjet_contact' ) {
			return;
		}
		$field['value'] = stripslashes_deep( $field['value'] );

		$credential = FormidableMailJetManager::get_credential();

		if ( $credential != false ) {
			//Show the list
			$mj_client = new Client( $credential['public'], $credential['private'] );
			$filters   = [
				'IsDeleted' => ( ! empty( $field['mailjet_contact_show_deleted'] ) && $field['mailjet_contact_show_deleted'] == "1" )
			];
			$response  = $mj_client->get( Resources::$Contactslist, [ 'filters' => $filters ] );
			if ( $response->success() ) {
				?><select name="item_meta[<?= $field['id'] ?>]" id="field_<?= $field['field_key'] ?>"><?php
				foreach ( $response->getData() as $key => $item ) {
					$selected        = "";
					$value           = json_encode( array( $item["ID"] => $item["Name"] ) );
					$saved_value     = json_decode( $field['value'], true );
					$saved_value_key = key( $saved_value );
					if ( $saved_value_key == $item["ID"] ) {
						$selected = "selected='selected'";
					}
					echo "<option " . $selected . " value='" . $value . "'>" . $item["Name"] . "</option>";
				}
				?>
				</select>
			<?php
			} else {
				var_dump( $response->getStatus() );
			}
		} else {
			?>
			<input type="hidden" id='field_<?= $field['field_key'] ?>' name='item_meta[<?= $field['id'] ?>]' value="0"/>
		<?php
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
		if ( $field->type != 'mailjet_contact' || empty( $value ) ) {
			return $value;
		}
		$result = "error!";
		$decode = json_decode( $value, true );
		if ( is_array( $decode ) ) {
			$key    = key( $decode );
			$result = $decode[ $key ];
		}
		
		return $result;
	}
	
	/**
	 * Set display option for the field
	 *
	 * @param $display
	 *
	 * @return mixed
	 */
	public function add_formidable_key_field_display_options( $display ) {
		if ( $display['type'] == 'mailjet_contact' ) {
			$display['unique']         = true;
			$display['required']       = true;
			$display['description']    = true;
			$display['options']        = true;
			$display['label_position'] = true;
			$display['css']            = true;
			$display['conf_field']     = false;
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
		if ( $field->type != "mailjet_contact" ) {
			return $replace_with;
		}

		return self::process_content( $replace_with, $atts );
	}

	/**
	 * Process the field content
	 *
	 * @param $content
	 * @param bool $get_id
	 *
	 * @return mixed|string
	 */
	public static function process_content( $content, $atts, $get_id = false ) {
		$result = "error!";
		$decode = json_decode( $content, true );
		if ( is_array( $decode ) ) {
			$key = key( $decode );
			if ( $get_id ) {
				return $key;
			}
			$result = $decode[ $key ];
			if ( isset( $atts['mj_id'] ) && ! empty( $atts['mj_id'] ) && $atts['mj_id'] = "1" ) {
				$result = $key;
			}
		}

		return $result;
	}
}