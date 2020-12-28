<?php

/**
 * XPath 2.0 for PHP
 *  _                      _     _ _ _
 * | |   _   _  __ _ _   _(_) __| (_) |_ _   _
 * | |  | | | |/ _` | | | | |/ _` | | __| | | |
 * | |__| |_| | (_| | |_| | | (_| | | |_| |_| |
 * |_____\__, |\__, |\__,_|_|\__,_|_|\__|\__, |
 *       |___/    |_|                    |___/
 *
 * @author Bill Seddon
 * @version 0.9
 * @Copyright (C) 2017 Lyquidity Solutions Limited
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
 *
 */

namespace lyquidity\XPath2;

/**
 * XPath2Exception (public)
 */
class XPath2Exception extends \Exception
{
	/**
	 * ErrorCode
	 * @var string $ErrorCode
	 */
	public $ErrorCode;

	/**
	 * Constructor
	 * @param string $message
	 * @param \Exception $innerException
	 */
	public  function __construct($message, $innerException)
	{
		parent::__construct( $message, null, $innerException );
	}

	/**
	 * asDefault
	 * @param string $message
	 * @param \Exception $innerException
	 * @return XPath2Exception
	 */
	public static function asDefault( $message, $innerException )
	{
		return new XPath2Exception( $message, $innerException );
	}

	/**
	 * withErrorCode
	 * @param string $errorCode
	 * @param string $message
	 * @param \Exception $innerException (Default: null)
	 * @return XPath2Exception
	 */
	public static function withErrorCode($errorCode, $message, $innerException= null)
	{
		$ex = new XPath2Exception( $message, $innerException );
		$ex->ErrorCode = $errorCode;
		return $ex;
	}

	/**
	 * withErrorCodeAndParam
	 * @param string $errorCode
	 * @param string $message
	 * @param object $parameter
	 * @param \Exception $innerException (Default: null)
	 * @return XPath2Exception
	 */
	public static function withErrorCodeAndParam($errorCode, $message, $parameter, $innerException = null)
	{
		$ex = XPath2Exception::withErrorCodeAndParams( $errorCode, $message, array( $parameter ), $innerException );
		// $ex->ErrorCode = $errorCode;
		return $ex;
	}

	/**
	 * withErrorCodeAndParams
	 * @param string $errorCode
	 * @param string $message
	 * @param List<object> $parameters
	 * @param \Exception $innerException (Default: null)
	 * @return XPath2Exception
	 */
	public static function withErrorCodeAndParams($errorCode, $message, $parameters, $innerException = null)
	{
		// The messages use the .NET string substitution convention using curly braces
		// so these few lines replace references with the appropriate parameter
		if ( preg_match_all( "/\{(\d)\}/", $message, $matches ) )
		{
			foreach ( $matches[1] as $index => $match )
			{
				if ( ! isset( $parameters[$match] ) ) continue;
				$message = str_replace( "{{$match}}", $parameters[$match], $message );
			}
		}

		$ex = new XPath2Exception( $message, $innerException );
		$ex->ErrorCode = $errorCode;
		return $ex;
	}

}
