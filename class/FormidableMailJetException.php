<?php

/**
 * Handle internal exceptions
 *
 * Class FormidableMailJetException
 */
class FormidableMailJetException extends Exception {

	protected $body = array();

	public function __construct( $message = "", $body = null ) {
		if ( ! empty( $body ) ) {
			$this->body = $body;
		}
		parent::__construct( $message, 0, null );
	}

	/**
	 * @return array|null
	 */
	public function getBody() {
		return $this->body;
	}
}