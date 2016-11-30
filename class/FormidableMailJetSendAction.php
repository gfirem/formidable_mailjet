<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class FormidableMailJetSendAction extends FrmFormAction {


	protected $form_default = array( 'wrk_name' => '' );
	private $error = array();
	
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

			$this->FrmFormAction( 'formidable_mailjet_send', FormidableMailJetManager::t( 'MailJet Send Action' ), $action_ops );

			add_filter( 'frm_validate_entry', array( $this, 'validate_form' ), 20, 2 );
		}
	}

	public function get_single_action( $id ) {
		return parent::get_single_action( $id );
	}

	public function validate_form( $errors, $values ) {

		$actions = FrmProPostAction::get_action_for_form( $values['form_id'], "formidable_mailjet_send" );

		if ( ! empty( $actions ) ) {
			foreach ( $actions as $key => $action ) {
				$required_fields = array(
					$action->post_content["campaign_name"],
					$action->post_content["subject"],
					$action->post_content["text_content"],
					$action->post_content["html_content"],
				);

				if ( ! empty( $action->post_content["contact_list_id_manually"] ) ) {
					$required_fields[] = $action->post_content["contact_list_id_manually"];
				}

				$to_validate = str_replace( array( "[", "]" ), "", $required_fields );

				foreach ( $to_validate as $item ) {
					if ( empty( $values["item_meta"][ $item ] ) ) {
						return array_merge( $errors, array( "field" . $item => "Invalid data to send the campaign " ) );
					}
				}
			}
		}

		return $errors;
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
			$subject       = "";
			$text_content  = "";
			$html_content  = "";
			$action_fields = array( "campaign_name", "subject", "sender", "segmentation_id", "text_content", "html_content" );

			if ( ! empty( $action->post_content["segmentation_enabled"] ) ) {
				if ( empty( $action->post_content["segmentation_id_manually"] ) ) {
					$segmentation_list_content = FrmEntryMeta::get_entry_meta_by_field( $entry->id, $action->post_content["segmentation_id"] );
					$segmentation_list_id      = strval( FormidableMailJetSegmentField::process_content( $segmentation_list_content, array(), true ) );
				} else {
					$segmentation_list_id = strval( $action->post_content["segmentation_id_manually"] );
				}
			} else {
				$segmentation_list_id = "-1";
			}

			if ( empty( $action->post_content["contact_list_id_manually"] ) ) {
				$contact_list_content = FrmEntryMeta::get_entry_meta_by_field( $entry->id, $action->post_content["contact_list_id"] );
				$contact_list_id      = strval( FormidableMailJetContactField::process_content( $contact_list_content, array(), true ) );
			} else {
				$contact_list_id = strval( $action->post_content["contact_list_id_manually"] );
			}

			$sender_random = false;
			if ( ! empty( $action->post_content["sender_random"] ) ) {
				$sender_random = true;
			}

			$schedule = "";
			if ( ! empty( $action->post_content["send_schedule"] ) ) {
				$schedule_content = FrmEntryMeta::get_entry_meta_by_field( $entry->id, $action->post_content["send_schedule"] );
				$format_time      = DateTime::createFromFormat( "Y/m/d H:i", $schedule_content );
				if ( $format_time !== false ) {
					$schedule = $format_time->getTimestamp();
				}
			}

			foreach ( $action_fields as $act_field ) {
				$act_content = $action->post_content[ $act_field ];
				$shortCodes  = FrmFieldsHelper::get_shortcodes( $act_content, $entry->form_id );
				$content     = apply_filters( 'frm_replace_content_shortcodes', $act_content, FrmEntry::getOne( $entry->id ), $shortCodes );
				FrmProFieldsHelper::replace_non_standard_formidable_shortcodes( array(), $content );
				$args[ $act_field ] = do_shortcode( $content );
			}

			extract( $args );
			$mj_sender = new MailJetSend();
			$result    = $mj_sender->send_campaign( $campaign_name, $sender, $subject, $contact_list_id, $segmentation_list_id, $text_content, $html_content, $sender_random, $schedule );

			if ( $result !== false ) {
				$status_fields = FrmField::get_all_types_in_form( $form->id, "mailjet_status" );
				if ( ! empty( $status_fields ) ) {
					if ( empty( $schedule ) ) {
						$campaign_overview = $mj_sender->overview_newsletter( $result["ID"] );
						$status_data       = $campaign_overview[0];
					} else {
						$status_data["NewsLetterID"] = $result["ID"];
						$status_data["LastUpdate"]   = time();
					}
					foreach ( $status_fields as $field ) {
						$value = FrmEntryMeta::get_entry_meta_by_field( $entry->id, $field->id );
						if ( empty( $value ) ) {
							$insert_result = FrmEntryMeta::add_entry_meta( $entry->id, $field->id, null, json_encode( $status_data ) );
						} else {
							$insert_result = FrmEntryMeta::update_entry_meta( $entry->id, $field->id, null, json_encode( $status_data ) );
						}
					}
				}
				FormidableMailJetAdmin::setMessage( array(
					"message" => FormidableMailJetManager::t( "All fine!" ),
					"type"    => "success",

				) );
			}

		} catch ( FormidableMailJetException $ex ) {
			$this->handle_exception( $ex->getMessage(), $ex->getBody() );
		} catch ( InvalidArgumentException $ex ) {
			$this->handle_exception( $ex->getMessage() );
		}

		return $result;
	}

	private function handle_exception( $message, $body = null ) {
		if ( ! empty( $body ) && is_array( $body ) ) {
			$error_str = "";
			foreach ( $body as $key => $value ) {
				if ( ! empty( $value ) ) {
					$error_str .= $key . " : " . $value . "<br/>";
				}
			}

			FormidableMailJetLogs::log( array(
				'action'         => "Send",
				'object_type'    => FormidableMailJetManager::getShort(),
				'object_subtype' => "detail_error",
				'object_name'    => $error_str,
			) );

			FormidableMailJetAdmin::setMessage( array(
				"message" => $error_str,
				"type"    => "danger"
			) );

			return;
		}
		$this->show_error( $message );
	}

	public function show_error( $string ) {
		FormidableMailJetAdmin::setMessage( array(
			"message" => $string,
			"type"    => "danger"
		) );
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
			$icon_url = FORMIDABLE_MAILJET_IMAGE . "mailjet-logo.png";
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
		$form            = $args['form'];
		$fields          = $args['values']['fields'];
		$action_control  = $this;
		$mj_sender       = new MailJetSend();
		$list_of_senders = $mj_sender->get_active_senders();

		if ( empty( $form_action->post_content['contact_list_id_manually'] ) ) {
			$contact_list_manually_show = 'style="display: none"';
			$contact_list_show          = "";
		} else {
			$contact_list_show          = 'style="display: none"';
			$contact_list_manually_show = "";
		}

		if ( empty( $form_action->post_content['segmentation_id_manually'] ) ) {
			$segmentation_manually_show = 'style="display: none"';
			$segmentation_show          = "";
		} else {
			$segmentation_show          = 'style="display: none"';
			$segmentation_manually_show = "";
		}

		?>
		<style>
			<?php echo "#pda-loading-".$this->number ?>
			{
				display: none
			;
			}
		</style>
		<h3 id="copy_section"><?php echo FormidableMailJetManager::t( 'Fill the data to create a campaign' ) ?></h3>
		<hr/>
		<table class="form-table frm-no-margin">
			<tbody id="copy-table-body">
			<tr>
				<th><label for="<?php echo $action_control->get_field_name( 'campaign_name' ) ?>"> <b><?php echo FormidableMailJetManager::t( ' Campaign Title: ' ); ?></b></label></th>
				<td>
					<input class="large-text  frm_help  mailjet_send_action <?php echo $action_control->get_field_name( 'html_content' ) ?>" type="text" name="<?php echo $action_control->get_field_name( 'campaign_name' ) ?>" id="<?php echo $action_control->get_field_name( 'campaign_name' ) ?>" value="<?php echo esc_attr( $form_action->post_content['campaign_name'] ); ?>"/>
				</td>
			</tr>
			<tr>
				<th><label for="<?php echo $action_control->get_field_name( 'subject' ) ?>"> <b><?php echo FormidableMailJetManager::t( ' Subject: ' ); ?></b></label></th>
				<td>
					<input class="large-text frm_help mailjet_send_action <?php echo $action_control->get_field_name( 'html_content' ) ?>" type="text" name="<?php echo $action_control->get_field_name( 'subject' ) ?>" id="<?php echo $action_control->get_field_name( 'subject' ) ?>" value="<?php echo esc_attr( $form_action->post_content['subject'] ); ?>"/>
				</td>
			</tr>
			<tr>
				<th><label for="<?php echo $action_control->get_field_name( 'sender' ) ?>"> <b><?php echo FormidableMailJetManager::t( ' Sender: ' ); ?></b></label></th>
				<td>
					<select class="large-text frm_help mailjet_send_action <?php echo $action_control->get_field_name( 'html_content' ) ?>" name="<?php echo $action_control->get_field_name( 'sender' ) ?>" id="<?php echo $action_control->get_field_name( 'sender' ) ?>">
						<?php
						foreach ( $list_of_senders as $id => $item ) {
							$selected = "";
							if ( esc_attr( $form_action->post_content['sender'] ) == $item["ID"] ) {
								$selected = "selected='selected'";
							}
							echo "<option " . $selected . " value='" . $item["ID"] . "'>" . $item["Name"] . " ( " . $item["Email"] . " ) " . "</option>";
						}
						?>
					</select>

					<div>
						<?php
						$sender_random_checked = "";
						if ( ! empty( $form_action->post_content['sender_random'] ) && esc_attr( $form_action->post_content['sender_random'] ) == "1" ) {
							$sender_random_checked = "checked='checked'";
						}
						?>
						<label for="<?php echo $action_control->get_field_name( 'sender_random' ) ?>"><?php echo FormidableMailJetManager::t( ' Check to use a random sender from the list ' ); ?></label>
						<input style="margin-left: 5px;" type="checkbox" <?php echo "$sender_random_checked"; ?> name="<?php echo $action_control->get_field_name( 'sender_random' ) ?>" id="<?php echo $action_control->get_field_name( 'sender_random' ) ?>" value="1"/>
					</div>
				</td>
			</tr>
			<tr>
				<th><label for="<?php echo $action_control->get_field_name( 'contact_list_id' ) ?>"> <b><?php echo FormidableMailJetManager::t( ' Contact List: ' ); ?></b></label></th>
				<td>
					<select <?php echo "$contact_list_show"; ?> class="large-text segmentation_id_select frm_help mailjet_send_action <?php echo $action_control->get_field_name( 'html_content' ) ?>" name="<?php echo $action_control->get_field_name( 'contact_list_id' ) ?>" id="<?php echo $action_control->get_field_name( 'contact_list_id' ) ?>">
						<?php
						foreach ( $fields as $id => $item ) {
							if ( $item["type"] == 'mailjet_contact' ) {
								$selected = "";
								if ( esc_attr( $form_action->post_content['contact_list_id'] ) == $item["id"] ) {
									$selected = "selected='selected'";
								}
								echo "<option " . $selected . " value='" . $item["id"] . "'>" . $item["name"] . "</option>";
							}
						}
						?>
					</select>
					<a href="#" class="mailjet_toggle_manually" target1="<?php echo $action_control->get_field_name( 'contact_list_id' ) ?>" target2="<?php echo $action_control->get_field_name( 'contact_list_id_manually' ) ?>"><?php echo FormidableMailJetManager::t( 'Toggle manually' ); ?></a>
					<input <?php echo "$contact_list_manually_show"; ?> class="frm_help mailjet_send_action <?php echo $action_control->get_field_name( 'html_content' ) ?>" type="text" name="<?php echo $action_control->get_field_name( 'contact_list_id_manually' ) ?>" id="<?php echo $action_control->get_field_name( 'contact_list_id_manually' ) ?>" value="<?php echo esc_attr( $form_action->post_content['contact_list_id_manually'] ); ?>"/>
				</td>
			</tr>
			<?php
			$segmentation_enable         = "";
			$segmentation_container_show = 'style="display: none"';
			if ( ! empty( $form_action->post_content['segmentation_enabled'] ) && esc_attr( $form_action->post_content['segmentation_enabled'] ) == "1" ) {
				$segmentation_enable         = "checked='checked'";
				$segmentation_container_show = "";
			}
			?>
			<tr>
				<th><label for="<?php echo $action_control->get_field_name( 'segmentation_enabled' ) ?>"> <b><?php echo FormidableMailJetManager::t( ' Enable Segment: ' ); ?></b></label></th>
				<td>
					<input target="<?php echo $action_control->get_field_name( 'segmentation_id' ) ?>" <?php echo "$segmentation_enable"; ?> class="mailjet_conditional_segmentation" type="checkbox" name="<?php echo $action_control->get_field_name( 'segmentation_enabled' ) ?>" id="<?php echo $action_control->get_field_name( 'segmentation_enabled' ) ?>" value="1"/>
				</td>
			</tr>
			<tr <?php echo "$segmentation_container_show"; ?> >
				<th><label for="<?php echo $action_control->get_field_name( 'segmentation_id' ) ?>"> <b><?php echo FormidableMailJetManager::t( ' Segment: ' ); ?></b></label></th>
				<td>
					<select <?php echo "$segmentation_show"; ?> class="large-text segmentation_id_select frm_help  mailjet_send_action <?php echo $action_control->get_field_name( 'html_content' ) ?>" name="<?php echo $action_control->get_field_name( 'segmentation_id' ) ?>" id="<?php echo $action_control->get_field_name( 'segmentation_id' ) ?>">
						<?php
						foreach ( $fields as $id => $item ) {
							if ( $item["type"] == 'mailjet_segment' ) {
								$selected = "";
								if ( esc_attr( $form_action->post_content['segmentation_id'] ) == $item["id"] ) {
									$selected = "selected='selected'";
								}
								echo "<option " . $selected . " value='" . $item["id"] . "'>" . $item["name"] . "</option>";
							}
						}
						?>
					</select>
					<a href="#" class="mailjet_toggle_manually" target1="<?php echo $action_control->get_field_name( 'segmentation_id' ) ?>" target2="<?php echo $action_control->get_field_name( 'segmentation_id_manually' ) ?>"><?php echo FormidableMailJetManager::t( 'Toggle manually' ); ?></a>
					<input  <?php echo "$segmentation_manually_show"; ?> class="frm_help  mailjet_send_action <?php echo $action_control->get_field_name( 'html_content' ) ?>" type="text" name="<?php echo $action_control->get_field_name( 'segmentation_id_manually' ) ?>" id="<?php echo $action_control->get_field_name( 'segmentation_id_manually' ) ?>" value="<?php echo esc_attr( $form_action->post_content['segmentation_id_manually'] ); ?>"/>
				</td>
			</tr>
			<tr>
				<td colspan="2">
					<hr/>
				</td>
			</tr>
			<tr>
				<th><label for="<?php echo $action_control->get_field_name( 'text_content' ) ?>"> <b><?php echo FormidableMailJetManager::t( ' Text Content: ' ); ?></b></label></th>
				<td>
					<textarea class="large-text frm_help  mailjet_send_action <?php echo $action_control->get_field_name( 'html_content' ) ?>" name="<?php echo $action_control->get_field_name( 'text_content' ) ?>" id="<?php echo $action_control->get_field_name( 'text_content' ) ?>"><?php echo esc_attr( $form_action->post_content['text_content'] ); ?></textarea>
				</td>
			</tr>
			<tr>
				<th><label for="<?php echo $action_control->get_field_name( 'html_content' ) ?>"> <b><?php echo FormidableMailJetManager::t( ' Html Content: ' ); ?></b></label></th>
				<td>
					<textarea class="large-text frm_help mailjet_send_action <?php echo $action_control->get_field_name( 'html_content' ) ?>" name="<?php echo $action_control->get_field_name( 'html_content' ) ?>" id="<?php echo $action_control->get_field_name( 'html_content' ) ?>"><?php echo esc_attr( $form_action->post_content['html_content'] ); ?></textarea>
				</td>
			</tr>
			<tr>
				<td colspan="2">
					<hr/>
				</td>
			</tr>
			<tr>
				<th><label for="<?php echo $action_control->get_field_name( 'send_schedule' ) ?>"> <b><?php echo FormidableMailJetManager::t( ' Schedule: ' ); ?></b></label></th>
				<td>
					<select class="large-text segmentation_id_select frm_help mailjet_send_action <?php echo $action_control->get_field_name( 'html_content' ) ?>" name="<?php echo $action_control->get_field_name( 'send_schedule' ) ?>" id="<?php echo $action_control->get_field_name( 'send_schedule' ) ?>">
						<?php
						foreach ( $fields as $id => $item ) {
							if ( $item["type"] == 'mailjet_date_time' ) {
								$selected = "";
								if ( esc_attr( $form_action->post_content['send_schedule'] ) == $item["id"] ) {
									$selected = "selected='selected'";
								}
								echo "<option " . $selected . " value='" . $item["id"] . "'>" . $item["name"] . "</option>";
							}
						}
						?>
					</select>
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

				function invert_show($select, $manual) {
					if ($("[id='" + $manual + "']").is(":visible")) {
						$("[id='" + $manual + "']").hide();
						$("[id='" + $manual + "']").val("");
						$("[id='" + $select + "']").show();
					}
					else {
						$("[id='" + $manual + "']").show();
						$("[id='" + $select + "']").hide();
					}
				}

				$(".mailjet_toggle_manually").click(function (e) {
					e.preventDefault();
					var $select = $(this).attr("target1"),
						$manual = $(this).attr("target2");

					invert_show($select, $manual);
				});

				$(".mailjet_conditional_segmentation").click(function (e) {
					var $container = $(this).attr("target");
					$container = $("[id='" + $container + "']").parent().parent();
					($container.is(":visible")) ? $container.hide() : $container.show();
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
			'form_id'                  => $this->get_field_name( 'form_id' ),
			'campaign_name'            => '',
			'subject'                  => '',
			'sender'                   => '',
			'sender_random'            => '',
			'segmentation_enabled'     => '',
			'segmentation_id'          => '',
			'segmentation_id_manually' => '',
			'contact_list_id'          => '',
			'contact_list_id_manually' => '',
			'text_content'             => '',
			'html_content'             => '',
			'send_schedule'            => '',
		);
		
		if ( $this->form_id != null ) {
			$result['form_id'] = $this->form_id;
		}
		
		return $result;
	}
}