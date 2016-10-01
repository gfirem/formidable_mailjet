<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

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
		$gManager    = GManagerFactory::buildManager( 'FormidableMailJetManager', 'formidable_key_field', FormidableMailJetManager::getShort() );
		$key         = get_option( FormidableMailJetManager::getShort() . 'licence_key' );
		$public_key  = get_option( FormidableMailJetManager::getShort() . 'public_key' );
		$private_key = get_option( FormidableMailJetManager::getShort() . 'private_key' );
		$sender      = get_option( FormidableMailJetManager::getShort() . 'sender' );
		?>
		<h3 class="frm_first_h3"><?= FormidableMailJetManager::t( "Licence Data for MailJet integration" ) ?></h3>
		<table class="form-table">
			<tr>
				<td width="150px"><?= FormidableMailJetManager::t( "Version: " ) ?></td>
				<td>
					<span><?= FormidableMailJetManager::getVersion() ?></span>
				</td>
			</tr>
			<tr class="form-field" valign="top">
				<td width="150px">
					<label for="key"><?= FormidableMailJetManager::t( "Order key: " ) ?></label>
					<span class="frm_help frm_icon_font frm_tooltip_icon" title="" data-original-title="<?= FormidableMailJetManager::t( "Order key send to you with order confirmation, to get updates." ) ?>"></span>
				</td>
				<td><input type="text" name="<?= FormidableMailJetManager::getShort() ?>_key" id="<?= FormidableMailJetManager::getShort() ?>_key" value="<?= $key ?>"/></td>
			</tr>
			<tr class="form-field" valign="top">
				<td width="150px"><?= FormidableMailJetManager::t( "Key status: " ) ?></label></td>
				<td><?= $gManager->getStatus() ?></td>
			</tr>
		</table>
		<h3><?= FormidableMailJetManager::t( "MailJet credentials" ) ?></h3>
		<table class="form-table">
			<tr>
				<td width="150px"><label for="<?= FormidableMailJetManager::getShort() ?>_public_key"><?= FormidableMailJetManager::t( "Api key: " ) ?></label></td>
				<td><input type="text" size="40" name="<?= FormidableMailJetManager::getShort() ?>_public_key" id="<?= FormidableMailJetManager::getShort() ?>_public_key" value="<?= $public_key ?>"/></td>
			</tr>
			<tr class="form-field" valign="top">
				<td width="150px">
					<label for="<?= FormidableMailJetManager::getShort() ?>_private_key"><?= FormidableMailJetManager::t( "Secret key: " ) ?></label>
				</td>
				<td><input type="password" size="40" name="<?= FormidableMailJetManager::getShort() ?>_private_key" id="<?= FormidableMailJetManager::getShort() ?>_private_key" value="<?= $private_key ?>"/></td>
			</tr>
			<tr class="form-field" valign="top">
				<td width="150px">
					<label for="<?= FormidableMailJetManager::getShort() ?>_sender"><?= FormidableMailJetManager::t( "Sender: " ) ?></label>
				</td>
				<td><input type="text" size="40" name="<?= FormidableMailJetManager::getShort() ?>_sender" id="<?= FormidableMailJetManager::getShort() ?>_sender" value="<?= $sender ?>"/></td>
			</tr>
		</table>
	<?php
	}

	public static function process_form() {
		if ( isset( $_POST[ FormidableMailJetManager::getShort() . '_key' ] ) && ! empty( $_POST[ FormidableMailJetManager::getShort() . '_key' ] ) ) {
			$gManager = GManagerFactory::buildManager( 'FormidableMailJetManager', 'formidable_key_field', FormidableMailJetManager::getShort() );
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

		if ( isset( $_POST[ FormidableMailJetManager::getShort() . '_sender' ] ) && ! empty( $_POST[ FormidableMailJetManager::getShort() . '_sender' ] ) ) {
			update_option( FormidableMailJetManager::getShort() . 'sender', $_POST[ FormidableMailJetManager::getShort() . '_sender' ] );
		} else {
			delete_option( FormidableMailJetManager::getShort() . 'sender' );
		}

		self::display_form();
	}
}