<?php
/**
 * Plugin Name: Simple Logger for Loggly
 * Plugin URI:  https://github.com/sc0ttkclark/simple-logger-for-loggly
 * Description: WordPress integration of a Simple PHP Logging API for Loggly
 * Version:     0.1
 * Author:      Scott Kingsley Clark
 * Author URI:  https://www.scottkclark.com/
 */

/*
 * See README.md for configuration information.
 */

/*
 * REQUIRED: You MUST set a token to use the API. All other configuration is optional.
 *
 * See https://{username}.loggly.com/tokens/ to get your token.
 */
// define( 'SIMPLE_PHP_API_FOR_LOGGLY_TOKEN', 'abcdef' );

require_once __DIR__ . '/src/Logger.php';

// Maybe setup the error handler.
if ( defined( 'SIMPLE_PHP_API_FOR_LOGGLY_ERROR_HANDLER' ) && SIMPLE_PHP_API_FOR_LOGGLY_ERROR_HANDLER ) {
	set_error_handler( [ \Simple_Logger_For_Loggly\Logger::class, 'error_handler' ] );
}
