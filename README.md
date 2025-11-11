# Simple Logger for Loggly

This is a small plugin that integrates Loggly logging with WordPress.

## Configuration

The configuration is currently all done via PHP constants that you can define in your `wp-config.php` file.

### REQUIRED: Token

You MUST set a token to use the API. All other configuration is optional.

See `https://{username}.loggly.com/tokens/` to get your token.

```php
define( 'SIMPLE_PHP_API_FOR_LOGGLY_TOKEN', 'abcdef' );
```

### PHP error handling

You can enable automatic integration with PHP error logs.

_The default is off._

```php
define( 'SIMPLE_PHP_API_FOR_LOGGLY_ERROR_HANDLER', true );
```

#### Customize error log level

You can customize the error log level to log to Loggly. It uses the same values as [error_reporting()](https://www.php.net/manual/en/function.error-reporting.php).

_The default level is to log all errors (`E_ALL`)._

```php
// Log only deprecation messages.
define( 'SIMPLE_PHP_API_FOR_LOGGLY_ERROR_LOG_LEVEL', E_DEPRECATED );
```

### Customize log destination

You can customize the destination if needed or if you want to customize how it gets tagged.

_The default is `https://logs-01.loggly.com/inputs/%s/tag/http/`_

```php
define( 'SIMPLE_PHP_API_FOR_LOGGLY_DESTINATION', 'https://logs-01.loggly.com/inputs/%s/tag/mytag/ ' );
```
