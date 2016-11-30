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
			throw new FormidableMailJetException( "Invalid Credentials" );
		}
	}

	/**
	 * Create and Send a campaign
	 *
	 * @param $title
	 * @param $sender
	 * @param $subject
	 * @param $contact_list_id
	 * @param $segment_list_id
	 * @param $text
	 * @param $html
	 * @param bool $sender_random
	 * @param string $schedule
	 *
	 * @return bool
	 * @throws Exception
	 */
	public function send_campaign( $title, $sender, $subject, $contact_list_id, $segment_list_id, $text = "", $html = "", $sender_random = false, $schedule = "" ) {
		$exclude = array( "sender_random", "schedule", "text", "html" );
		foreach ( get_defined_vars() as $key => $var ) {
			if ( ! in_array( $key, $exclude ) && empty( $var ) ) {
				throw new InvalidArgumentException( "The parameter (" . $key . ") is empty." );
			}
		}

		if ( ! $sender_random ) {
			$sender_details = $this->get_sender( $sender );
			$sender_details = $sender_details[0];
		} else {
			$senders         = $this->get_active_senders();
			$sender_position = array_rand( $senders );
			$sender_details  = $senders[ $sender_position ];
		}

		if ( ! empty( $sender_details ) ) {
			$sender_name  = $sender_details["Name"];
			$sender_email = $sender_details["Email"];
			$sender       = $sender_details["Name"];
		} else {
			throw new InvalidArgumentException( "Invalid sender details." );
		}

		if ( ! empty( $sender_name ) && ! empty( $sender_email ) ) {
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
		} else {
			throw new InvalidArgumentException( "Invalid sender name or email." );
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
	public function create_newsletter( $title, $sender, $sender_name, $sender_email, $subject, $contact_list_id, $segment_list_id = "-1", $test_address = "" ) {
		$body = array(
			"Locale"         => "en_US",
			"Sender"         => $sender,
			"SenderName"     => $sender_name,
			"SenderEmail"    => $sender_email,
			"Subject"        => $subject,
			"ContactsListID" => $contact_list_id,
			"EditMode"       => "tool",
			"ReplyEmail"     => "progfm@hotmail.com",
			"Title"          => $title

		);

		if ( $segment_list_id != "-1" && ! empty( $segment_list_id ) ) {
			$body["SegmentationID"] = $segment_list_id;
		}

		if ( ! empty( $test_address ) ) {
			$body["TestAddress"] = $test_address;
		}

		$result = $this->mj_client->post( Resources::$Newsletter, array( "body" => $body ) );

		if ( $result->success() ) {
			return $result->getData();
		} else {
			throw new FormidableMailJetException( "Error creating the newsletter. Response Status:" . $result->getStatus(), $result->getBody() );
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
				throw new FormidableMailJetException( "Error adding the content to the newsletter. Response Status:" . $result->getStatus(), $result->getBody() );
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
					throw new FormidableMailJetException( "Error sending the test of the newsletter. Response Status:" . $result->getStatus(), $result->getBody() );
				}
			} else {
				throw new FormidableMailJetException( "Don't detect any email into the body" );
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
				throw new FormidableMailJetException( "Error sending the newsletter. Response Status:" . $result->getStatus(), $result->getBody() );
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

			$result = $this->mj_client->post( Resources::$NewsletterSchedule, array( 'id' => $id, "body" => $body ) );

			if ( $result->success() ) {
				return $result->getData();
			} else {
				throw new FormidableMailJetException( "Error sending the newsletter. Response Status:" . $result->getStatus(), $result->getBody() );
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
				throw new FormidableMailJetException( "Error getting the status of the newsletter. Response Status:" . $result->getStatus(), $result->getBody() );
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

			$result = $this->mj_client->get( Resources::$Campaign, array( "ID" => "mj.nl=" . $id ) );

			if ( $result->success() ) {
				$data                  = $result->getData();
				$data[0]["LastUpdate"] = time();

				return $data;
			} else {
				throw new FormidableMailJetException( "Error getting overview of the newsletter or not send yet. Response Status: " . $result->getStatus(), $result->getBody() );
			}
		} else {
			throw new InvalidArgumentException( "Id parameter is empty" );
		}
	}

	/**
	 * Get a list of active senders
	 *
	 */
	public function get_active_senders() {
		$result = $this->mj_client->get( Resources::$Sender, array( "filters" => array( "status" => "Active" ) ) );

		if ( $result->success() ) {
			return $result->getData();
		} else {
			throw new FormidableMailJetException( "Error getting active sender list. Response Status:" . $result->getStatus(), $result->getBody() );
		}
	}

	/**
	 * Get sender details
	 *
	 * @param $id
	 *
	 * @return array
	 * @throws Exception
	 */
	public function get_sender( $id ) {
		if ( ! empty( $id ) ) {

			$result = $this->mj_client->get( Resources::$Sender, array( "ID" => $id ) );

			if ( $result->success() ) {
				return $result->getData();
			} else {
				throw new FormidableMailJetException( "Error getting the sender details. Response Status:" . $result->getStatus(), $result->getBody() );
			}
		} else {
			throw new InvalidArgumentException( "Id parameter is empty" );
		}
	}

	/**
	 * Get Api Key Payload
	 *
	 * @param $id
	 *
	 * @return array
	 * @throws Exception
	 */
	public function get_api_pay_load( $id ) {
		if ( ! empty( $id ) ) {

			$result = $this->mj_client->get( Resources::$Apikeytotals, array( "ID" => $id ) );

			if ( $result->success() ) {
				return $result->getData();
			} else {
				throw new FormidableMailJetException( "Error getting the api key payload. Response Status:" . $result->getStatus(), $result->getBody() );
			}
		} else {
			throw new InvalidArgumentException( "Id parameter is empty" );
		}
	}

	/**
	 * Get Api Details
	 *
	 * @param $id
	 *
	 * @return array
	 * @throws Exception
	 */
	public function get_api_details( $id ) {
		if ( ! empty( $id ) ) {

			$result = $this->mj_client->get( Resources::$Apikey, array( "ID" => $id ) );

			if ( $result->success() ) {
				return $result->getData();
			} else {
				throw new FormidableMailJetException( "Error getting the api details. Response Status:" . $result->getStatus(), $result->getBody() );
			}
		} else {
			throw new InvalidArgumentException( "Id parameter is empty" );
		}
	}
}