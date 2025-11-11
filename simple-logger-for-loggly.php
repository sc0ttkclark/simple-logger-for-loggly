<?php
/**
 * Plugin Name: Simple Logger for Loggly
 * Plugin URI:  https://github.com/sc0ttkclark/simple-logger-for-loggly
 * Description: WordPress integration of a Simple PHP Logging API for Loggly
 * Version:     0.1
 * Author:      Scott Kingsley Clark
 * Author URI:  https://www.scottkclark.com/
 */

// See https://{username}.loggly.com/tokens/ to get your token
// define( 'SIMPLE_PHP_API_FOR_LOGGLY_TOKEN', 'abcdef' );

// Customize the destination if needed (if it changes or if you want to customize how it gets tagged.
// define( 'SIMPLE_PHP_API_FOR_LOGGLY_DESTINATION', 'https://logs-01.loggly.com/inputs/%s/tag/http/ ' );

// Whether to enable PHP error logs to be sent to Loggly (default off)
// define( 'SIMPLE_PHP_API_FOR_LOGGLY_ERROR_HANDLER', true );

// Customize the error log level, uses same as error_reporting()
// See https://www.php.net/manual/en/function.error-reporting.php
// define( 'SIMPLE_PHP_API_FOR_LOGGLY_ERROR_LOG_LEVEL', E_DEPRECATED );
// define( 'SIMPLE_PHP_API_FOR_LOGGLY_ERROR_LOG_LEVEL', E_ALL );

require_once __DIR__ . '/src/Logger.php';

// Maybe setup the error handler.
if ( defined( 'SIMPLE_PHP_API_FOR_LOGGLY_ERROR_HANDLER' ) && SIMPLE_PHP_API_FOR_LOGGLY_ERROR_HANDLER ) {
	set_error_handler( [ \Simple_Logger_For_Loggly\Logger::class, 'error_handler' ] );
}
