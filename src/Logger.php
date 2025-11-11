<?php

namespace Simple_Logger_For_Loggly;

use WP_Error;

class Logger {

	/**
	 * The singleton instance.
	 *
	 * @var Logger|null
	 */
	private static ?self $instance = null;

	/**
	 * An array of error codes and their equivalent string value
	 *
	 * @var array
	 */
	private array $error_codes = [
		E_ERROR             => 'E_ERROR',
		E_WARNING           => 'E_WARNING',
		E_PARSE             => 'E_PARSE',
		E_NOTICE            => 'E_NOTICE',
		E_CORE_ERROR        => 'E_CORE_ERROR',
		E_CORE_WARNING      => 'E_CORE_WARNING',
		E_COMPILE_ERROR     => 'E_COMPILE_ERROR',
		E_COMPILE_WARNING   => 'E_COMPILE_WARNING',
		E_USER_ERROR        => 'E_USER_ERROR',
		E_USER_WARNING      => 'E_USER_WARNING',
		E_USER_NOTICE       => 'E_USER_NOTICE',
		E_STRICT            => 'E_STRICT',
		E_RECOVERABLE_ERROR => 'E_RECOVERABLE_ERROR',
		E_DEPRECATED        => 'E_DEPRECATED',
		E_USER_DEPRECATED   => 'E_USER_DEPRECATED',
	];

	private function __construct(
		private ?string $token = null,
		private string $destination,
		private bool $error_handler,
		private int $log_level,
	) {
	}

	/**
	 * Setup or get the singleton instance.
	 *
	 * @return Logger The class intance.
	 */
	public static function instance(): Logger {
		$tag           = ( defined( 'SIMPLE_LOGGER_FOR_LOGGLY_TAG' ) && is_string( SIMPLE_LOGGER_FOR_LOGGLY_TAG ) && SIMPLE_LOGGER_FOR_LOGGLY_TAG )
			? SIMPLE_LOGGER_FOR_LOGGLY_TAG : 'http';
		$token         = ( defined( 'SIMPLE_LOGGER_FOR_LOGGLY_TOKEN' ) && is_string( SIMPLE_LOGGER_FOR_LOGGLY_TOKEN ) && SIMPLE_LOGGER_FOR_LOGGLY_TOKEN )
			? SIMPLE_LOGGER_FOR_LOGGLY_TOKEN : null;
		$destination   = ( defined( 'SIMPLE_LOGGER_FOR_LOGGLY_DESTINATION' ) && is_string( SIMPLE_LOGGER_FOR_LOGGLY_DESTINATION ) && SIMPLE_LOGGER_FOR_LOGGLY_DESTINATION )
			? SIMPLE_LOGGER_FOR_LOGGLY_DESTINATION : 'https://logs-01.loggly.com/inputs/%s/tag/' . $tag . '/';
		$error_handler = defined( 'SIMPLE_LOGGER_FOR_LOGGLY_ERROR_HANDLER' )
			? (bool) SIMPLE_LOGGER_FOR_LOGGLY_ERROR_HANDLER : false;
		$log_level     = defined( 'SIMPLE_LOGGER_FOR_LOGGLY_ERROR_LOG_LEVEL' )
			? (int) SIMPLE_LOGGER_FOR_LOGGLY_ERROR_LOG_LEVEL : E_ALL;

		self::$instance ??= new self(
			token: $token,
			destination: $destination,
			error_handler: $error_handler,
			log_level: $log_level,
		);

		return self::$instance;
	}

	/**
	 * Log data easily without having to get the instance.
	 *
	 * @param mixed       $data              Data to log.
	 * @param null|string $from_component    Component name to identify where log is coming from.
	 * @param bool        $include_page_info Whether to include page info in the log (default off).
	 *
	 * @return bool|WP_Error True if successful or an WP_Error object with the problem.
	 */
	public static function log(
		mixed $data,
		?string $from_component = null,
		bool $include_page_info = false,
	) {
		return self::instance()->log_data( $data, $from_component, $include_page_info );
	}

	/**
	 * Log data.
	 *
	 * @param mixed       $data              Data to log.
	 * @param null|string $from_component    Component name to identify where log is coming from, for error handling integration this is the error level.
	 * @param bool        $include_page_info Whether to include page info in the log (default off).
	 *
	 * @return bool|WP_Error True if successful or an WP_Error object with the problem.
	 */
	public function log_data(
		mixed $data,
		?string $from_component = null,
		bool $include_page_info = false,
	) {
		$from_url = parse_url( is_multisite() ? network_site_url() : site_url(), PHP_URL_HOST );

		$log_data = [
			'timestamp' => date_i18n( 'M d H:i:s' ),
			'url'       => $from_url,
			'component' => $from_component,
			'data'      => $data,
		];

		if ( $include_page_info ) {
			$log_data['page_info'] = $this->get_page_info();
		}

		$json = json_encode( $log_data );

		if ( str_contains( $this->destination, '%s' ) ) {
			return new WP_Error(
				'simple-logger-for-loggly-invalid-destination',
				esc_html(
					sprintf(
						__( 'Invalid Loggly destination, must contain "%%s" (%s).', 'simple-logger-for-loggly' ),
						$this->destination
					),
				),
			);
		}

		if ( $this->log_level && $from_component && false !== ( $code = $this->codify_error_string( $from_component ) ) && ! ( $this->log_level & $code ) ) {
			return new WP_Error(
				'simple-logger-for-loggly-log-level-off',
				esc_html(
					sprintf(
						__( 'The Loggly log level %s has been turned off in this configuration. Current log level: %d', 'simple-logger-for-loggly' ),
						$this->stringify_error_code( $code ),
						$this->log_level,
					),
				),
			);
		}

		$result = wp_remote_post(
			$this->destination,
			[
				'body'    => $json,
				'headers' => [
					'Content-Type' => 'application/json',
				],
			],
		);

		return ! is_wp_error( $result ) && '{"response":"ok"}' === wp_remote_retrieve_body( $result );
	}

	/**
	 * Handle error logging to Papertrail
	 *
	 * @param int    $id      Error number
	 * @param string $message Error message
	 * @param string $file    Error file
	 * @param int    $line    Error line
	 * @param array  $context Error context
	 */
	public static function error_handler( $id, $message, $file, $line, $context ) {
		$instance = self::instance();

		$type = $instance->stringify_error_code( $id );

		$page_info = [
			'error' => sprintf( '%s | %s | %s:%s', $type, $message, $file, $line ),
		];

		$page_info = $instance->get_page_info( $page_info );

		if ( 'E_ERROR' !== $type ) {
			unset( $page_info['$_POST'] );
			unset( $page_info['$_GET'] );
		}

		$instance->log_data( $page_info, 'Simple_Logger_For_Loggly/Error/' . $type );
	}

	/**
	 * Get the page information.
	 *
	 * @param array $page_info Additional page information to start with.
	 *
	 * @return array The page information.
	 */
	public function get_page_info( array $page_info = [] ) {
		// Setup URL
		$page_info['url'] = 'http://';

		if ( is_ssl() ) {
			$page_info['url'] = 'https://';
		}

		$page_info['url'] .= $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];

		$page_info['url'] = explode( '?', $page_info['url'] );
		$page_info['url'] = $page_info['url'][0];
		$page_info['url'] = explode( '#', $page_info['url'] );
		$page_info['url'] = $page_info['url'][0];

		$page_info['$_GET']  = $_GET;
		$page_info['$_POST'] = $_POST;

		$page_info['DOING_AJAX'] = ( defined( 'DOING_AJAX' ) && DOING_AJAX );
		$page_info['DOING_CRON'] = ( defined( 'DOING_CRON' ) && DOING_CRON );
		$page_info['DOING_REST'] = ( function_exists( 'wp_is_serving_rest_request' ) && wp_is_serving_rest_request() );

		$keys_to_remove = [
			'password',
			'pwd',
			'user_pass',
		];

		/**
		 * Allow filtering the list of keys to remove information from $_GET/$_POST for.
		 *
		 * @since 0.1
		 *
		 * @param array $keys_to_remove The list of keys to remove information for.
		 */
		$keys_to_remove = (array) apply_filters( 'simple_logger_for_loggly', $keys_to_remove );

		// Remove potentially sensitive information from page info.
		foreach ( $keys_to_remove as $key_to_remove ) {
			if ( isset( $page_info['$_GET'][ $key_to_remove ] ) ) {
				unset( $page_info['$_GET'][ $key_to_remove ] );
			}

			if ( isset( $page_info['$_POST'][ $key_to_remove ] ) ) {
				unset( $page_info['$_POST'][ $key_to_remove ] );
			}
		}

		return $page_info;
	}

	/**
	 * Turn a string representation of an error type into an error code
	 *
	 * If the error code doesn't exist in our array, this will return false. $type will get run through basename, so
	 * component strings from error logs will get handled without any changes necessary to the type value.
	 *
	 * @param string $type
	 *
	 * @return false|int
	 */
	protected function codify_error_string( string $type ) {
		return array_search( basename( $type ), $this->error_codes, true );
	}

	/**
	 * Get the corresponding error code string from the PHP integer.
	 *
	 * @param int $code The PHP error code.
	 *
	 * @return string The error code string like "E_ALL", falls back to "unknown".
	 */
	protected function stringify_error_code( int $code ): string {
		return $this->error_codes[ $code ] ?? 'unknown';
	}

}
