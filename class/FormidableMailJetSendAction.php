<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class FormidableMailJetSendAction extends FrmFormAction {
	
	protected $form_default = array( 'wrk_name' => '' );

	
	public function __construct() {
		if ( class_exists( "FrmProAppController" ) ) {

			require_once 'MailJetSend.php';

			add_action( 'frm_trigger_formidable_mailjet_send_create_action', array( $this, 'mj_action_create' ), 10, 3 );
			add_action( 'frm_trigger_formidable_mailjet_send_update_action', array( $this, 'mj_action_update' ), 10, 3 );

			add_action( 'admin_head', array( $this, 'add_admin_styles' ) );
			add_filter( 'wp_kses_allowed_html', array( $this, 'wp_kses_allowed_html' ), 10, 2 );
			add_shortcode( "form-mj-security", array( $this, 'form_mj_security_content' ) );

			$action_ops = array(
				'classes'  => 'mailjet_integration',
				'limit'    => 99,
				'active'   => true,
				'priority' => 50,
				'event'    => array( 'create', 'update' ),
			);

			$this->FrmFormAction( 'formidable_mailjet_send', FormidablePasswordFieldManager::t( 'MailJet Send Action' ), $action_ops );
		}
	}

	/**
	 * Triggered by create action
	 *
	 * @param $action
	 * @param $entry
	 * @param $form
	 */
	public function mj_action_create( $action, $entry, $form ) {
		$this->send_action( $action, $entry, $form );
	}

	/**
	 * Triggered by update action
	 *
	 * @param $action
	 * @param $entry
	 * @param $form
	 */
	public function mj_action_update( $action, $entry, $form ) {
		$this->send_action( $action, $entry, $form );
	}

	private function send_action( $action, $entry, $form ) {
		$result = false;
		try {
			$args          = array();
			$campaign_name = "";
			$sender        = "";
			$sender_name   = "";
			$sender_email  = "";
			$subject       = "";
			$text_content  = "";
			$html_content  = "";
			$action_fields = array( "campaign_name", "subject", "sender", "sender_name", "sender_email", "segmentation_id", "text_content", "html_content" );

			$segmentation_list_content = FrmEntryMeta::get_entry_meta_by_field( $entry->id, $action->post_content["segmentation_id"] );
			$segmentation_list_id      = FormidableMailJetSegmentField::process_content( $segmentation_list_content, true );

			$contact_list_content = FrmEntryMeta::get_entry_meta_by_field( $entry->id, $action->post_content["contact_list_id"] );
			$contact_list_id      = FormidableMailJetContactField::process_content( $contact_list_content, true );

			foreach ( $action_fields as $act_field ) {
				$act_content = $action->post_content[ $act_field ];
				$shortCodes  = FrmFieldsHelper::get_shortcodes( $act_content, $entry->form_id );
				$content     = apply_filters( 'frm_replace_content_shortcodes', $act_content, FrmEntry::getOne( $entry->id ), $shortCodes );
				FrmProFieldsHelper::replace_non_standard_formidable_shortcodes( array(), $content );
				$args[ $act_field ] = do_shortcode( $content );
			}

			extract( $args );
			$mj_sender = new MailJetSend();
			$result    = $mj_sender->send_campaign( $campaign_name, $sender, $sender_name, $sender_email, $subject, $contact_list_id, $segmentation_list_id, $text_content, $html_content );

			if($result !== false) {
				$status_fields     = FrmField::get_all_types_in_form( $form->id, "nailjet_status" );
				if ( ! empty( $status_fields ) ) {
					$campaign_overview = $mj_sender->overview_newsletter( $result["ID"] );
					foreach ( $status_fields as $field ) {
						$value = FrmEntryMeta::get_entry_meta_by_field( $entry->id, $field->id );
						if ( empty( $value ) ) {
							$insert_result = FrmEntryMeta::add_entry_meta( $entry->id, $field->id, null, json_encode($campaign_overview[0]));
						} else {
							$insert_result = FrmEntryMeta::update_entry_meta( $entry->id, $field->id, null, json_encode($campaign_overview[0]) );
						}
					}
				}
			}

		} catch ( Exception $ex ) {
			$this->show_error( $ex->getMessage() );
		}

		return $result;
	}

	public function show_error( $string ) {
		echo '<div class="error fade"><p>' . $string . '</p></div>';
	}

	/**
	 * Allow new tags to process shortCodes
	 *
	 * @param $allowedPostTags
	 * @param $context
	 *
	 * @return mixed
	 */
	public function wp_kses_allowed_html( $allowedPostTags, $context ) {
		if ( $context == 'post' ) {
			$allowedPostTags['input']['form-mj-security'] = 1;
			$allowedPostTags['input']['value']            = 1;
		}

		return $allowedPostTags;
	}

	/**
	 * Return nonce for given action in shortCode
	 *
	 * @param $attr
	 * @param null $content
	 *
	 * @return string
	 */
	public function form_mj_security_content( $attr, $content = null ) {
		$internal_attr = shortcode_atts( array(
			'act' => 'get_form_field',
		), $attr );

		$nonce = base64_encode( $internal_attr['act'] );

		return $nonce;
	}

	public function add_admin_styles() {
		$current_screen = get_current_screen();
		if ( $current_screen->id === 'toplevel_page_formidable' ) {
			$icon_url   = FORMIDABLE_MAILJET_IMAGE . "mailjet-logo.png";
			?>
			<style>
				.frm_formidable_mailjet_send_action.frm_bstooltip.frm_active_action.mailjet_integration {
					display: inline-table;
					font-size: 13px;
					height: 24px;
					width: 24px;
					background-image: url("<?php echo "$icon_url"; ?>");
					background-repeat: no-repeat;
				}

				.frm_form_action_icon.mailjet_integration {
					background-image: url("<?php echo "$icon_url"; ?>");
					background-repeat: no-repeat;
					display: block;
					float: left;
					font-size: 13px;
					height: 24px;
					margin-right: 8px;
					width: 24px;
				}

				.frm_actions_list > li > a::before, .frm_email_settings h3 .frm_form_action_icon::before {
					vertical-align: baseline !important;
				}
			</style>
		<?php
		}
	}
	
	/**
	 * Get the HTML for your action settings
	 *
	 * @param array $form_action
	 * @param array $args
	 *
	 * @return string|void
	 */
	public function form( $form_action, $args = array() ) {
		extract( $args );
		$form           = $args['form'];
		$fields         = $args['values']['fields'];
		$action_control = $this;

		?>
		<style>
			<?= "#pda-loading-".$this->number ?>
			{
				display: none
			;
			}
		</style>
		<input type="hidden" name="form-nonce-<?= $this->number ?>" id="form-nonce-<?= $this->number ?>" form-copy-security="<?= base64_encode( 'get_form_fields' ); ?>">
		<input type="hidden" value="<?= esc_attr( $form_action->post_content['form_id'] ); ?>" name="<?php echo $action_control->get_field_name( 'form_id' ) ?>">
		<input type="hidden" value="<?= esc_attr( $form_action->post_content['form_destination_data'] ); ?>" name="<?php echo $action_control->get_field_name( 'form_destination_data' ) ?>">
		<h3 id="copy_section"><?= FormidablePasswordFieldManager::t( 'Fill the data to create a campaign' ) ?></h3>
		<hr/>
		<table class="form-table frm-no-margin">
			<tbody id="copy-table-body">
			<tr>
				<th><label for="<?php echo $action_control->get_field_name( 'campaign_name' ) ?>"> <b><?= FormidablePasswordFieldManager::t( ' Campaign Title: ' ); ?></b></label></th>
				<td>
					<input class="large-text  frm_help  mailjet_send_action <?php echo $action_control->get_field_name( 'html_content' ) ?>" type="text" name="<?php echo $action_control->get_field_name( 'campaign_name' ) ?>" id="<?php echo $action_control->get_field_name( 'campaign_name' ) ?>" value="<?= esc_attr( $form_action->post_content['campaign_name'] ); ?>"/>
				</td>
			</tr>
			<tr>
				<th><label for="<?php echo $action_control->get_field_name( 'subject' ) ?>"> <b><?= FormidablePasswordFieldManager::t( ' Subject: ' ); ?></b></label></th>
				<td>
					<input class="large-text  frm_help  mailjet_send_action <?php echo $action_control->get_field_name( 'html_content' ) ?>" type="text" name="<?php echo $action_control->get_field_name( 'subject' ) ?>" id="<?php echo $action_control->get_field_name( 'subject' ) ?>" value="<?= esc_attr( $form_action->post_content['subject'] ); ?>"/>
				</td>
			</tr>
			<tr>
				<th><label for="<?php echo $action_control->get_field_name( 'sender' ) ?>"> <b><?= FormidablePasswordFieldManager::t( ' Sender: ' ); ?></b></label></th>
				<td>
					<input class="large-text  frm_help  mailjet_send_action <?php echo $action_control->get_field_name( 'html_content' ) ?>" type="text" name="<?php echo $action_control->get_field_name( 'sender' ) ?>" id="<?php echo $action_control->get_field_name( 'sender' ) ?>" value="<?= esc_attr( $form_action->post_content['sender'] ); ?>"/>
				</td>
			</tr>
			<tr>
				<th><label for="<?php echo $action_control->get_field_name( 'sender_name' ) ?>"> <b><?= FormidablePasswordFieldManager::t( ' Sender Name: ' ); ?></b></label></th>
				<td>
					<input class="large-text  frm_help  mailjet_send_action <?php echo $action_control->get_field_name( 'html_content' ) ?>" type="text" name="<?php echo $action_control->get_field_name( 'sender_name' ) ?>" id="<?php echo $action_control->get_field_name( 'sender_name' ) ?>" value="<?= esc_attr( $form_action->post_content['sender_name'] ); ?>"/>
				</td>
			</tr>
			<tr>
				<th><label for="<?php echo $action_control->get_field_name( 'sender_email' ) ?>"> <b><?= FormidablePasswordFieldManager::t( ' Sender Email: ' ); ?></b></label></th>
				<td>
					<input class="large-text  frm_help  mailjet_send_action <?php echo $action_control->get_field_name( 'html_content' ) ?>" type="text" name="<?php echo $action_control->get_field_name( 'sender_email' ) ?>" id="<?php echo $action_control->get_field_name( 'sender_email' ) ?>" value="<?= esc_attr( $form_action->post_content['sender_email'] ); ?>"/>
				</td>
			</tr>
			<tr>
				<th><label for="<?php echo $action_control->get_field_name( 'segmentation_id' ) ?>"> <b><?= FormidablePasswordFieldManager::t( ' Segmentation Id: ' ); ?></b></label></th>
				<td>
					Aqui hay que poner la lista de campos que tienen el segmento
					<input class="large-text  frm_help  mailjet_send_action <?php echo $action_control->get_field_name( 'html_content' ) ?>" type="text" name="<?php echo $action_control->get_field_name( 'segmentation_id' ) ?>" id="<?php echo $action_control->get_field_name( 'segmentation_id' ) ?>" value="<?= esc_attr( $form_action->post_content['segmentation_id'] ); ?>"/>
				</td>
			</tr>
			<tr>
				<th><label for="<?php echo $action_control->get_field_name( 'contact_list_id' ) ?>"> <b><?= FormidablePasswordFieldManager::t( ' Contact List Id: ' ); ?></b></label></th>
				<td>
					Aqui hay que poner la lista de campos que tienen los contactos
					<input class="large-text  frm_help  mailjet_send_action <?php echo $action_control->get_field_name( 'html_content' ) ?>" type="text" name="<?php echo $action_control->get_field_name( 'contact_list_id' ) ?>" id="<?php echo $action_control->get_field_name( 'contact_list_id' ) ?>" value="<?= esc_attr( $form_action->post_content['contact_list_id'] ); ?>"/>
				</td>
			</tr>
			<tr>
				<td colspan="2">
					<hr/>
				</td>
			</tr>
			<tr>
				<th><label for="<?php echo $action_control->get_field_name( 'text_content' ) ?>"> <b><?= FormidablePasswordFieldManager::t( ' Text Content: ' ); ?></b></label></th>
				<td>
					<textarea class="large-text  frm_help  mailjet_send_action <?php echo $action_control->get_field_name( 'html_content' ) ?>" name="<?php echo $action_control->get_field_name( 'text_content' ) ?>" id="<?php echo $action_control->get_field_name( 'text_content' ) ?>"><?= esc_attr( $form_action->post_content['text_content'] ); ?></textarea>
				</td>
			</tr>
			<tr>
				<th><label for="<?php echo $action_control->get_field_name( 'html_content' ) ?>"> <b><?= FormidablePasswordFieldManager::t( ' Html Content: ' ); ?></b></label></th>
				<td>
					<textarea class="large-text  frm_help mailjet_send_action <?php echo $action_control->get_field_name( 'html_content' ) ?>" name="<?php echo $action_control->get_field_name( 'html_content' ) ?>" id="<?php echo $action_control->get_field_name( 'html_content' ) ?>"><?= esc_attr( $form_action->post_content['html_content'] ); ?></textarea>
				</td>
			</tr>
			</tbody>
		</table>

		<script type="application/javascript">
			jQuery(document).ready(function ($) {
				function myToggleAllowedShortCodes(id) {
					if (typeof(id) == 'undefined') {
						id = '';
					}
					var c = id;

					if (id !== '') {
						var $ele = jQuery(document.getElementById(id));
						if ($ele.attr('class') && id !== 'wpbody-content' && id !== 'content' && id !== 'dyncontent' && id != 'success_msg') {
							var d = $ele.attr('class').split(' ')[0];
							if (d == 'frm_long_input' || typeof d == 'undefined') {
								d = '';
							} else {
								id = jQuery.trim(d);
							}
							c = c + ' ' + d;
						}
					}
					jQuery('#frm-insert-fields-box,#frm-conditionals,#frm-adv-info-tab,#frm-html-tags,#frm-layout-classes,#frm-dynamic-values').removeClass().addClass('tabs-panel ' + c);
				}

				jQuery(document).on('focusin click', 'form input, form textarea, #wpcontent', function (e) {
					e.stopPropagation();
					if (jQuery(this).is(':not(:submit, input[type=button])') && jQuery(this).hasClass("mailjet_send_action")) {
						var id = jQuery(this).attr('id');
						console.log(id);
						myToggleAllowedShortCodes(id);
						jQuery('.frm_code_list a').removeClass('frm_noallow').addClass('frm_allow');
						jQuery('.frm_code_list a.hide_' + id).addClass('frm_noallow').removeClass('frm_allow');
					}
					else {
						jQuery('.frm_code_list a').addClass('frm_noallow').removeClass('frm_allow');
					}
				});
			});
		</script>
	<?php
	}
	
	/**
	 * Add the default values for your options here
	 */
	function get_defaults() {
		$result = array(
			'form_id'         => $this->get_field_name( 'form_id' ),
			'campaign_name'   => '',
			'subject'         => '',
			'sender'          => '',
			'sender_name'     => '',
			'sender_email'    => '',
			'segmentation_id' => '',
			'contact_list_id' => '',
			'text_content'    => '',
			'html_content'    => '',
		);
		
		if ( $this->form_id != null ) {
			$result['form_id'] = $this->form_id;
		}
		
		return $result;
	}
}