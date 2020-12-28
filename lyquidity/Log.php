<?php

/**
 * Implements the a logging class.
 *
 * @author Bill Seddon
 * @version 0.9
 * @Copyright (C) 2018 Lyquidity Solutions Limited
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace lyquidity\XPath2\lyquidity;

use lyquidity\XPath2\XPath2Exception;

/**
 * Load the Log class if not already loaded
 */
if ( ! class_exists( "\\Log", true ) )
{
	$logPath = isset( $_ENV['LOG_LIBRARY_PATH'] )
		? $_ENV['LOG_LIBRARY_PATH']
		: ( defined( 'LOG_LIBRARY_PATH' ) ? LOG_LIBRARY_PATH : __DIR__ . "/../../log/" );

	require_once $logPath . "Log.php";
	/**
	 * Load the event_log handler implementation
	 */
	require_once "$logPath/log/error-log.php";
}

/**
 * This a singleton class used to provide a common logging facility for all XPath class instances.
 */
class Log
{
	/**
	 * A reference to this singleton instance
	 * @var Log Singleton
	 */
	private static $instance;

	/**
	 * The log instance to use
	 * @var \Log
	 */
	private $log;

	/**
	 * Get an instance of the global singleton
	 * @return Log
	 */
	public static function getInstance()
	{
		if ( is_null( self::$instance ) )
		{
			self::$instance = new self();
			self::$instance->createLog( 'error_log', PEAR_LOG_TYPE_SYSTEM, 'xpath2_log',
				array(
					'lineFormat' => '[%{priority}] %{message}',
				)
			);

		}

		return self::$instance;
	}

	/**
	 * This creates a specific type of log instance
	 * @param string $handler	The type of Log handler to construct
	 * @param string $name 		The name of the log resource to which the events
	 *							will be logged.  Defaults to an empty string.
	 * @param string $ident 	An identification string that will be included in
	 *							all log events logged by this handler.  This value
	 *							defaults to an empty string and can be changed at
	 *							runtime using the ``setIdent()`` method.
	 * @param array $conf		Associative array of key-value pairs that are
	 *							used to specify any handler-specific settings.
	 * @param int $level		Log messages up to and including this level.
	 *							This value defaults to ``PEAR_LOG_DEBUG``.
	 *							See `Log Levels`_ and `Log Level Masks`_.
	 * @return void
	 */
	public function createLog( $handler, $name, $ident, $conf = null, $level = null )
	{
		$this->log = \Log::singleton( $handler, $name, $ident, $conf, $level );
	}

	/**
	 * If you know what you are doing and want to create a custom log,
	 * perhaps with custom handler, or perhaps you want a composite log
	 * that directs logs to multiple locations you set it here.
	 * @param Log $log The logging instance to use
	 * @return void
	 */
	public function setLog( $log )
	{
		$this->log = $log;
	}

	/**
	 * A convenience function for logging a emergency event.  It will log a
	 * message at the PEAR_LOG_EMERG log level.
	 *
	 * PEAR_LOG_EMERG
	 *
	 * @param   mixed   $message	String or object containing the message to log.
	 * @return  boolean True if the message was successfully logged.
	 */
	public function emerg( $message )
	{
		if ( ! $this->log ) return;
		return $this->log->emerg( $message );
	}

	/**
	 * A convenience function for logging an alert event.  It will log a
	 * message at the PEAR_LOG_ALERT log level.
	 *
	 * PEAR_LOG_ALERT
	 *
	 * @param   mixed   $message	String or object containing the message to log.
	 * @return  boolean True if the message was successfully logged.
	 */
	public function alert( $message )
	{
		if ( ! $this->log ) return;
		return $this->log->alert( $message );
	}

	/**
	 * A convenience function for logging a critical event.  It will log a
	 * message at the PEAR_LOG_CRIT log level.
	 *
	 * PEAR_LOG_CRIT
	 *
	 * @param  mixed $message	String or object containing the message to log.
	 * @return  boolean True if the message was successfully logged.
	 */
	public function crit( $message )
	{
		if ( ! $this->log ) return;
		return $this->log->crit( $message );
	}

	/**
	 * A convenience function for logging a error event.  It will log a
	 * message at the PEAR_LOG_ERR log level.
	 *
	 * PEAR_LOG_ERR
	 *
	 * @param mixed $message	String or object containing the message to log.
	 * @return  boolean True if the message was successfully logged.
	 */
	public function err( $message )
	{
		$ex = new \Exception();
		echo $ex->getTraceAsString();

		if ( ! $this->log ) return;
		return $this->log->err( $message );
	}

	/**
	 * A convenience function for logging a warning event.  It will log a
	 * message at the PEAR_LOG_WARNING log level.
	 *
	 * PEAR_LOG_WARNING
	 *
	 * @param   mixed   $message	String or object containing the message to log.
	 * @return  boolean True if the message was successfully logged.
	 */
	public function warning( $message )
	{
		if ( ! $this->log ) return;
		return $this->log->warning( $message );
	}

	/**
	 * A convenience function for logging a notice event.  It will log a
	 * message at the PEAR_LOG_NOTICE log level.
	 *
	 * PEAR_LOG_NOTICE
	 *
	 * @param   mixed   $message	String or object containing the message to log.
	 * @return  boolean True if the message was successfully logged.
	 */
	public function notice( $message )
	{
		if ( ! $this->log ) return;
		return $this->log->notice( $message );
	}

	/**
	 * A convenience function for logging a information event.  It will log a
	 * message at the PEAR_LOG_INFO log level.
	 *
	 * PEAR_LOG_INFO
	 *
	 * @param   mixed   $message	String or object containing the message to log.
	 * @return  boolean True if the message was successfully logged.
	 */
	public function info( $message = "" )
	{
		if ( ! $this->log ) return;
		return $this->log->info( $message );
	}

	/**
	 * A convenience function for logging a debug event.  It will log a
	 * message at the PEAR_LOG_DEBUG log level.
	 *
	 * PEAR_LOG_DEBUG
	 *
	 * @param mixed $message	String or object containing the message to log.
	 * @return  boolean True if the message was successfully logged.
	 */
	public function debug( $message )
	{
		if ( ! $this->log ) return;
		return $this->log->debug( $message );
	}

	/**
	 * A convenience function for logging a event about an XPath conformance issue.
	 * It will log a message at the PEAR_LOG_DEBUG log level.
	 *
	 * PEAR_LOG_WARNING
	 *
	 * @param XPath2Exception $ex	An exception reference
	 * @return  boolean True if the message was successfully logged.
	 */
	public function fromXPath2Exception( $ex )
	{
		if ( ! $this->log ) return;
		return $this->log->warning( $ex->ErrorCode . " " . $ex->getMessage() );
	}

	/**
	 * Set log output for console and error_log
	 * @return void
	 */
	public function setConsoleLog()
	{
		$logConsole  = \Log::singleton( 'console', '', 'console',
			array(
				'lineFormat' => '%{timestamp} [%{priority}] %{message}',
				'timeFormat' => '%Y-%m-%d %H:%M:%S',
			)
		);

		$this->setLog( $logConsole );
	}

	/**
	 * Set log output for console and error_log
	 * @return void
	 */
	public function setDebugLog()
	{
		$logConsole  = \Log::singleton( 'console', '', 'console',
			array(
				'lineFormat' => '%{timestamp} [%{priority}] %{message}',
				'timeFormat' => '%Y-%m-%d %H:%M:%S',
			)
		);

		$logError = \Log::singleton( 'error_log', PEAR_LOG_TYPE_SYSTEM, 'error_log',
			array(
				'lineFormat' => '[%{priority}] %{message}',
			)
		);

		$logComposite = \Log::singleton( 'composite' );
		$logComposite->addChild( $logConsole );
		$logComposite->addChild( $logError );

		$this->setLog( $logComposite );
	}
}
