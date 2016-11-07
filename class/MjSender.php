<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class MjSender {

	public $name;
	public $email;

	public function get_contact_as_json() {
		return '{
					"Email": "' . $this->email . '",
                    "Name": "' . $this->name . '"
                }';
	}
}