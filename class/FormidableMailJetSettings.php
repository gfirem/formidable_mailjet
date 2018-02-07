<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
require_once 'FormidableMailJetException.php';

class FormidableMailJetSettings {

	public static function route() {

		$action = isset( $_REQUEST['frm_action'] ) ? 'frm_action' : 'action';
		$action = FrmAppHelper::get_param( $action );
		if ( $action == 'process-form' ) {
			return self::process_form();
		} else {
			return self::display_form();
		}
	}

	/**
	 * @internal var gManager GManager_1_0
	 */
	public static function display_form() {
		$locale_source  = array(
			"en_US" => "English",
			"es_ES" => "Español"
		);
		$gManager       = GManagerFactory::buildManager( 'FormidableMailJetManager', 'formidable_mailjet', FormidableMailJetManager::getShort() );
		$key            = get_option( FormidableMailJetManager::getShort() . 'licence_key' );
		$public_key     = get_option( FormidableMailJetManager::getShort() . 'public_key' );
		$private_key    = get_option( FormidableMailJetManager::getShort() . 'private_key' );
		$refresh_factor = get_option( FormidableMailJetManager::getShort() . 'refresh_factor' );


		?>
		<h3 class="frm_first_h3"><?php echo FormidableMailJetManager::t( "Licence Data for MailJet integration" ) ?></h3>
		<table class="form-table">
			<tr>
				<td width="150px"><?php echo FormidableMailJetManager::t( "Version: " ) ?></td>
				<td>
					<span><?php echo FormidableMailJetManager::getVersion() ?></span>
				</td>
			</tr>
			<tr class="form-field" valign="top">
				<td width="150px">
					<label for="key"><?php echo FormidableMailJetManager::t( "Order key: " ) ?></label>
					<span class="frm_help frm_icon_font frm_tooltip_icon" title="" data-original-title="<?php echo FormidableMailJetManager::t( "Order key send to you with order confirmation, to get updates." ) ?>"></span>
				</td>
				<td><input type="text" name="<?php echo FormidableMailJetManager::getShort() ?>_key" id="<?php echo FormidableMailJetManager::getShort() ?>_key" value="<?php echo $key ?>"/></td>
			</tr>
			<tr class="form-field" valign="top">
				<td width="150px"><?php echo FormidableMailJetManager::t( "Key status: " ) ?></label></td>
				<td><?php echo $gManager->getStatus() ?></td>
			</tr>
		</table>
		<h3><?php echo FormidableMailJetManager::t( "MailJet options" ) ?></h3>
		<table class="form-table">
			<tr>
				<td width="150px"><label for="<?php echo FormidableMailJetManager::getShort() ?>_public_key"><?php echo FormidableMailJetManager::t( "Api key: " ) ?></label></td>
				<td><input type="text" size="40" name="<?php echo FormidableMailJetManager::getShort() ?>_public_key" id="<?php echo FormidableMailJetManager::getShort() ?>_public_key" value="<?php echo $public_key ?>"/></td>
			</tr>
			<tr class="form-field" valign="top">
				<td width="150px">
					<label for="<?php echo FormidableMailJetManager::getShort() ?>_private_key"><?php echo FormidableMailJetManager::t( "Secret key: " ) ?></label>
				</td>
				<td><input type="password" size="40" name="<?php echo FormidableMailJetManager::getShort() ?>_private_key" id="<?php echo FormidableMailJetManager::getShort() ?>_private_key" value="<?php echo $private_key ?>"/></td>
			</tr>
			<tr class="form-field" valign="top">
				<td width="150px">
					<label for="<?php echo FormidableMailJetManager::getShort() ?>_refresh_factor"><?php echo FormidableMailJetManager::t( "Status refresh time: " ) ?></label>
				</td>
				<td>
					<input type="number" size="5" name="<?php echo FormidableMailJetManager::getShort() ?>_refresh_factor" id="<?php echo FormidableMailJetManager::getShort() ?>_refresh_factor" value="<?php echo $refresh_factor ?>"/>
					<p><?php echo FormidableMailJetManager::t( "Time in minutes. The system use to monitor the time between calls to MailJet server to maintain an internal cache system " ) ?></p>
				</td>
			</tr>
		</table>
		<?php
		if ( ! empty( $public_key ) ) {
			?>
			<h3><?php echo FormidableMailJetManager::t( "Api Payload" ) ?></h3>
			<?php
			try {
				$mj                         = new MailJetSend();
				$api_details                = $mj->get_api_details( $public_key );
				$payload                    = $mj->get_api_pay_load( $api_details[0]["ID"] );
				$payload[0]["LastActivity"] = date( FormidableMailJetManager::getDateFormat(), $payload[0]["LastActivity"] );
				echo "<pre>" . json_encode( $payload[0], JSON_PRETTY_PRINT ) . "</pre>";
			} catch ( FormidableMailJetException $ex ) {
				$body = $ex->getBody();
				if ( ! empty( $body ) && is_array( $body ) ) {
					$error_str = "";
					foreach ( $body as $key => $value ) {
						if ( ! empty( $value ) ) {
							$error_str .= $key . " : " . $value . "<br/>";
						}
					}
				}
				else{
					$error_str = $ex->getMessage();
				}
				FormidableMailJetLogs::log( array(
					'action'         => "Send",
					'object_type'    => FormidableMailJetManager::getShort(),
					'object_subtype' => "detail_error",
					'object_name'    => $error_str,
				) );
			}
		}
		?>
	<?php
	}

	public static function process_form() {
		if ( isset( $_POST[ FormidableMailJetManager::getShort() . '_key' ] ) && ! empty( $_POST[ FormidableMailJetManager::getShort() . '_key' ] ) ) {
			$gManager = GManagerFactory::buildManager( 'FormidableMailJetManager', 'formidable_mailjet', FormidableMailJetManager::getShort() );
			$gManager->activate( $_POST[ FormidableMailJetManager::getShort() . '_key' ] );
			update_option( FormidableMailJetManager::getShort() . 'licence_key', $_POST[ FormidableMailJetManager::getShort() . '_key' ] );
		} else {
			delete_option( FormidableMailJetManager::getShort() . 'licence_key' );
		}

		if ( isset( $_POST[ FormidableMailJetManager::getShort() . '_public_key' ] ) && ! empty( $_POST[ FormidableMailJetManager::getShort() . '_public_key' ] ) ) {
			update_option( FormidableMailJetManager::getShort() . 'public_key', $_POST[ FormidableMailJetManager::getShort() . '_public_key' ] );
		} else {
			delete_option( FormidableMailJetManager::getShort() . 'public_key' );
		}

		if ( isset( $_POST[ FormidableMailJetManager::getShort() . '_private_key' ] ) && ! empty( $_POST[ FormidableMailJetManager::getShort() . '_private_key' ] ) ) {
			update_option( FormidableMailJetManager::getShort() . 'private_key', $_POST[ FormidableMailJetManager::getShort() . '_private_key' ] );
		} else {
			delete_option( FormidableMailJetManager::getShort() . 'private_key' );
		}

		if ( isset( $_POST[ FormidableMailJetManager::getShort() . '_refresh_factor' ] ) && ! empty( $_POST[ FormidableMailJetManager::getShort() . '_refresh_factor' ] ) ) {
			update_option( FormidableMailJetManager::getShort() . 'refresh_factor', $_POST[ FormidableMailJetManager::getShort() . '_refresh_factor' ] );
		} else {
			delete_option( FormidableMailJetManager::getShort() . 'refresh_factor' );
		}

		self::display_form();
	}
}