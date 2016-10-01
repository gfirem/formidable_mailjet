<?php

if ( ! class_exists( 'GManager_1_0', false ) ):

	/**
	 * Class GManagerFactory
	 * @copyright 2016
	 * @version 1.0
	 */
	class GManager_1_0 {
		protected $internalError;
		protected $productID;
		protected $short;
		protected $controller;

		function __construct( $controller, $productId, $short ) {
			$this->productID  = $productId;
			$this->short      = $short;
			$this->controller = $controller;

			$this->internalError = new WP_Error();
		}

		public function t( $str ) {
			return call_user_func( array( $this->controller, "t" ), $str );
		}

		/**
		 * Internal get call
		 *
		 * @param $action
		 * @param $licence_key
		 *
		 * @return array|WP_Error
		 */
		private function genericGet( $action, $licence_key ) {
			$protocol    = ( ! empty( $_SERVER['HTTPS'] ) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443 ) ? "https://" : "http://";
			$args        = array(
				'woo_sl_action'     => $action,
				'licence_key'       => $licence_key,
				'product_unique_id' => $this->productID,
				'domain'            => str_replace( $protocol, "", get_bloginfo( 'wpurl' ) )
			);
			$request_uri = 'http://www.gfirem.com/index.php' . '?' . http_build_query( $args );
			$data        = wp_remote_get( $request_uri );

			if ( is_wp_error( $data ) || $data['response']['code'] != 200 ) {
				$this->internalError->add( $this->getShort(), $this->t( "Error Handling server connections" ) );

				return $this->internalError;
			}

			return $data;
		}

		/**
		 * Deactivate product
		 *
		 * @return GShopData|null|WP_Error
		 */
		public function deActivate() {
			$activated = get_option( $this->getShort() . 'activated', false );
			$result    = null;
			if ( $activated ) {
				$licence_key = get_option( $this->getShort() . 'licence_key', '' );
				if ( ! empty( $licence_key ) ) {
					$data = $this->genericGet( 'deactivate', $licence_key );

					if ( is_wp_error( $data ) ) {
						return  $data->get_error_message();
					}

					$data_body = json_decode( $data['body'] );
					if ( isset( $data_body[0]->status ) ) {
						if ( $data_body[0]->status == 'success' ) {
							if ( $data_body[0]->status_code == 's201' ) {
								update_option( $this->getShort() . 'licence_key', '' );
								update_option( $this->getShort() . 'activated', false );
								$result = new GShopData( true, $this->t( "Licence susses deactivated" ) );
							}
						} else {
							$result = new GShopData( false, $this->t( "Licence deactivated request error" ) );
						}
					} else {
						$this->internalError->add( $this->getShort(), $this->t( "Error Handling server connections" ) );
						$result = $this->internalError;
					}
				} else {
					$result = new GShopData( false, $this->t( "Not licence key detected. Please set." ) );
				}
			} else {
				$result = new GShopData( false, $this->t( "Licence already deactivated" ) );
			}

			return $result;
		}

		/**
		 * Activate the product with licence key
		 *
		 * @param $licence_key
		 *
		 * @return GShopData|null|WP_Error
		 */
		public function activate( $licence_key ) {
			$activated = get_option( $this->getShort() . 'activated', false );
			$result    = null;
			if ( ! $activated ) {

				$data = $this->genericGet( 'activate', $licence_key );

				if ( is_wp_error( $data ) ) {
					return $data->get_error_message();;
				}

				$data_body = json_decode( $data['body'] );
				if ( isset( $data_body[0]->status ) ) {
					if ( $data_body[0]->status == 'success' ) {
						if ( $data_body[0]->status_code == 's200' || $data_body[0]->status_code == 's100' ) {
							update_option( $this->getShort() . 'licence_key', $licence_key );
							update_option( $this->getShort() . 'activated', true );
							$result = new GShopData( true, $this->t( "Licence susses activated" ) );
						} else {
							update_option( $this->getShort() . 'licence_key', '' );
							update_option( $this->getShort() . 'activated', false );
							$result = new GShopData( false, $this->t( "Licence activating error" ) );
						}
					} else {
						$result = new GShopData( false, $this->t( "Licence activation request error" ) );
					}
				} else {
					$this->internalError->add( $this->getShort(), $this->t( "Error Handling server connections" ) );
					$result = $this->internalError;
				}
			} else {
				$result = new GShopData( true, $this->t( "Licence already activated" ) );
			}

			return $result;
		}


		/**
		 * Get status of product
		 *
		 * @return GShopData|null|WP_Error
		 */
		public function getStatus() {
			$result      = null;
			$licence_key = get_option( $this->getShort() . 'licence_key', '' );
			if ( ! empty( $licence_key ) ) {

				$data = $this->genericGet( 'status-check', $licence_key );

				if ( is_wp_error( $data ) ) {
					return $data->get_error_message();
				}

				$data_body = json_decode( $data['body'] );
				if ( isset( $data_body[0]->status ) ) {
					if ( $data_body[0]->status == 'success' ) {
						switch ( $data_body[0]->status_code ) {
							case "s205":
								update_option( $this->getShort() . 'activated', true );
								$result = new GShopData( true, $this->t( "Licence is active" ) );
								break;
							case "s203":
								update_option( $this->getShort() . 'activated', false );
								$result = new GShopData( false, $this->t( "Licence is inactive" ) );
								break;
						}
					} else if ( $data_body[0]->status == 'error' ) {
						switch ( $data_body[0]->status_code ) {
							case "e002":
								update_option( $this->getShort() . 'activated', false );
								$result = new GShopData( true, $this->t( "Invalid license key" ) );
								break;
							case "e001":
								update_option( $this->getShort() . 'activated', false );
								$result = new GShopData( true, $this->t( "Invalid provided data" ) );
								break;
						}
					} else {
						$result = new GShopData( false, $this->t( "Licence status request unsuccessfully" ) );
					}
				} else {
					$this->internalError->add( $this->getShort(), $this->t( "Error Handling server connections" ) );
					$result = $this->internalError;
				}
			} else {
				$result = new GShopData( false, $this->t( "Not licence key detected. Please set." ) );
			}

			return $result;
		}

		static function isActive() {
			return get_option( self::getShort() . 'activated', false );
		}

		private function getShort() {
			return $this->short;
		}
	}

endif;

if ( ! class_exists( 'GShopData', false ) ):

	class GShopData {
		public $active;
		public $message;

		function __construct( $active, $message ) {
			$this->active  = $active;
			$this->message = $message;
		}

		function __toString() {
			return $this->message;
		}


	}

endif;

if ( ! class_exists( 'GManagerFactory', false ) ):


	class GManagerFactory {
		protected static $classVersions = array();
		protected static $sorted = false;

		/**
		 * Create a new instance of GManager.
		 *
		 * @see GManager_1_0::__construct()
		 *
		 * @param $productId
		 *
		 * @param $short
		 *
		 * @return GManager_1_0
		 */
		public static function buildManager( $controller, $productId, $short ) {
			$class = self::getLatestClassVersion( 'GManager' );

			return new $class( $controller, $productId, $short );
		}

		/**
		 * Get the specific class name for the latest available version of a class.
		 *
		 * @param string $class
		 *
		 * @return string|null
		 */
		public static function getLatestClassVersion( $class ) {
			if ( ! self::$sorted ) {
				self::sortVersions();
			}

			if ( isset( self::$classVersions[ $class ] ) ) {
				return reset( self::$classVersions[ $class ] );
			} else {
				return null;
			}
		}

		/**
		 * Sort available class versions in descending order (i.e. newest first).
		 */
		protected static function sortVersions() {
			foreach ( self::$classVersions as $class => $versions ) {
				uksort( $versions, array( __CLASS__, 'compareVersions' ) );
				self::$classVersions[ $class ] = $versions;
			}
			self::$sorted = true;
		}

		protected static function compareVersions( $a, $b ) {
			return - version_compare( $a, $b );
		}

		/**
		 * Register a version of a class.
		 *
		 * @access private This method is only for internal use by the library.
		 *
		 * @param string $generalClass Class name without version numbers, e.g. 'PluginUpdateChecker'.
		 * @param string $versionedClass Actual class name, e.g. 'PluginUpdateChecker_1_2'.
		 * @param string $version Version number, e.g. '1.2'.
		 */
		public static function addVersion( $generalClass, $versionedClass, $version ) {
			if ( ! isset( self::$classVersions[ $generalClass ] ) ) {
				self::$classVersions[ $generalClass ] = array();
			}
			self::$classVersions[ $generalClass ][ $version ] = $versionedClass;
			self::$sorted                                     = false;
		}
	}

endif;

GManagerFactory::addVersion( 'GManager', 'GManager_1_0', '1.0' );