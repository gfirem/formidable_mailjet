<?php
use Mailjet\Client;
use Mailjet\Resources;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


class MailJetSend {

	/** @var Client */
	private $mj_client;

	function __construct() {
		require_once 'MjSender.php';

		$credential = FormidableMailJetManager::get_credential();
		if ( $credential != false ) {
			$this->mj_client = new Client( $credential['public'], $credential['private'] );
		} else {
			throw new Exception( "Invalid Credentials" );
		}
	}

	public function send_campaign( $title, $sender, $sender_name, $sender_email, $subject, $contact_list_id, $segment_list_id, $text, $html, $schedule = "" ) {
		$args_size = func_num_args();
		$arg_list  = func_get_args();
		for ( $i = 0; $i < $args_size; $i ++ ) {
			if ( empty( $arg_list[ $i ] ) ) {
				throw new InvalidArgumentException( "The parameter " . GDebug::get_var_name( $arg_list[ $i ] ) );
			}
		}

		$response    = $this->create_newsletter( $title, $sender, $sender_name, $sender_email, $subject, $contact_list_id, $segment_list_id );
		$response_id = $response[0]["ID"];
		if ( ! empty( $response_id ) ) {
			$this->add_content_to_newsletter( $response_id, $text, $html );
			if ( empty( $schedule ) ) {
				$this->send_newsletter( $response_id );
			} else {
				$this->schedule_newsletter( $response_id, $schedule );
			}

			return $response[0];
		} else {
			return false;
		}
	}

	/**
	 * Create a newsletter
	 *
	 * @param $title
	 * @param $sender
	 * @param $sender_name
	 * @param $sender_email
	 * @param $subject
	 * @param $contact_list_id
	 * @param $segment_list_id
	 * @param string $test_address
	 *
	 * @return array
	 * @throws Exception
	 */
	public function create_newsletter( $title, $sender, $sender_name, $sender_email, $subject, $contact_list_id, $segment_list_id, $test_address = "" ) {
		$body = array(
			"Locale"         => "en_US",
			"Sender"         => $sender,
			"SenderName"     => $sender_name,
			"SenderEmail"    => $sender_email,
			"Subject"        => $subject,
			"ContactsListID" => $contact_list_id,
			"SegmentationID" => $segment_list_id,
			"EditMode"       => "tool",
			"ReplyEmail"     => "progfm@hotmail.com",
			"Title"          => $title

		);

		if ( ! empty( $test_address ) ) {
			$body["TestAddress"] = $test_address;
		}

		$result = $this->mj_client->post( Resources::$Newsletter, array( "body" => $body ) );

		if ( $result->success() ) {
			return $result->getData();
		} else {
			throw new Exception( "Error creating the newsletter. Response Status:" . $result->getStatus() );
		}
	}

	/**
	 * Add content to the existing newsletter
	 *
	 * @param $id
	 * @param $text
	 * @param $html
	 *
	 * @return array
	 * @throws Exception
	 */
	public function add_content_to_newsletter( $id, $text, $html ) {
		if ( ! empty( $id ) ) {
			$body = array(
				"Text-part" => $text,
				"Html-part" => $html,
			);

			$result = $this->mj_client->post( Resources::$NewsletterDetailcontent, array( 'id' => $id, "body" => $body ) );

			if ( $result->success() ) {
				return $result->getData();
			} else {
				throw new Exception( "Error adding the content to the newsletter. Response Status:" . $result->getStatus() );
			}
		} else {
			throw new InvalidArgumentException( "Id parameter is empty" );
		}
	}

	/**
	 * Send test email. Attach one MjSender email object or array of it
	 *
	 * @param $id
	 * @param MjSender $emails
	 *
	 * @return array
	 * @throws Exception
	 */
	public function test_newsletter( $id, $emails ) {
		if ( ! empty( $id ) ) {
			$body = array();
			if ( ! is_array( $emails ) ) {
				$body["Recipients"] = $emails->get_contact_as_json();
			} else {
				/** @var MjSender $item */
				foreach ( $emails as $item ) {
					$body["Recipients"] .= $item->get_contact_as_json();
				}
			}
			if ( count( $body["Recipients"] ) >= 1 ) {
				$result = $this->mj_client->post( Resources::$NewsletterTest, array( 'id' => $id, "body" => $body ) );


				if ( $result->success() ) {
					return $result->getData();
				} else {
					throw new Exception( "Error sending the test of the newsletter. Response Status:" . $result->getStatus() );
				}
			} else {
				throw new Exception( "Don't detect any email into the body" );
			}
		} else {
			throw new InvalidArgumentException( "Id parameter is empty" );
		}
	}

	/**
	 * Send the newsletter
	 *
	 * @param $id
	 *
	 * @return array
	 * @throws Exception
	 */
	public function send_newsletter( $id ) {
		if ( ! empty( $id ) ) {

			$result = $this->mj_client->post( Resources::$NewsletterSend, array( 'id' => $id ) );

			if ( $result->success() ) {
				return $result->getData();
			} else {
				throw new Exception( "Error sending the newsletter. Response Status:" . $result->getStatus() );
			}
		} else {
			throw new InvalidArgumentException( "Id parameter is empty" );
		}
	}

	/**
	 * Send newsletter in a schedule
	 *
	 * @param $id
	 * @param $schedule
	 *
	 * @return array
	 * @throws Exception
	 */
	public function schedule_newsletter( $id, $schedule ) {
		if ( ! empty( $id ) ) {

			$body = array(
				"date" => $schedule
			);

			$result = $this->mj_client->post( Resources::$NewsletterSend, array( 'id' => $id, "body" => $body ) );

			if ( $result->success() ) {
				return $result->getData();
			} else {
				throw new Exception( "Error sending the newsletter. Response Status:" . $result->getStatus() );
			}
		} else {
			throw new InvalidArgumentException( "Id parameter is empty" );
		}
	}

	/**
	 * Get the status of the newsletter
	 *
	 * @param $id
	 *
	 * @return array
	 * @throws Exception
	 */
	public function status_newsletter( $id ) {
		if ( ! empty( $id ) ) {

			$result = $this->mj_client->post( Resources::$NewsletterStatus, array( 'id' => $id ) );

			if ( $result->success() ) {
				return $result->getData();
			} else {
				throw new Exception( "Error getting the status of the newsletter. Response Status:" . $result->getStatus() );
			}
		} else {
			throw new InvalidArgumentException( "Id parameter is empty" );
		}
	}

	/**
	 * Get the overview of the newsletter
	 *
	 * @param $id
	 *
	 * @return array
	 * @throws Exception
	 */
	public function overview_newsletter( $id ) {
		if ( ! empty( $id ) ) {

			$result = $this->mj_client->get( Resources::$Campaign, array( "ID" => "mj.nl=".$id ) );

			if ( $result->success() ) {
				return $result->getData();
			} else {
				throw new Exception( "Error getting overview of the newsletter. Response Status:" . $result->getStatus() );
			}
		} else {
			throw new InvalidArgumentException( "Id parameter is empty" );
		}
	}

}