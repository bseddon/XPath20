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
 * @version 0.1.1
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

namespace lyquidity\XPath2\parser;

class yyDebugSimple implements yyDebug
{
	/**
	 * @param string $message
	 */
	private function println ( $s )
	{
		echo( $s );
	}

	/**
	 * Report push token
	 * @param int $state
	 * @param object $value
	 */
	public function push( $state, $value)
	{
		$this->println( "push\tstate $state\tvalue $value" );
	}

	/**
	 * @param int $state
	 * @param int $token
	 * @param string $name
	 * @param object $value
	 */
	public function lex ( $state, $token, $name, $value )
	{
		$this->println( "lex\tstate $state\treading $name\tvalue $value" );
	}

	/**
	 * Token shift
	 * @param int $from
	 * @param int $to
	 * @param int $errorFlag
	 */
	public function shift( $from, $to, $errorFlag = null )
	{
		if ( is_null( $errorFlag ) )
		{
			$this->println( "goto\tfrom state $from to $to" );
			return;
		}

		switch (errorFlag)
		{
			default:				// normally
				$this->println( "shift\tfrom state $from to $to" );
				break;
			case 0: case 1: case 2:	// in error recovery
				$this->println( "shift\tfrom state $from to $to\t$errorFlag left to recover" );
				break;
			case 3:					// normally
				$this->println( "shift\tfrom state $from to $to\ton error" );
				break;
		}
	}

	/**
	 * Report token pop
	 * @param int $state
	 */
	public function pop ( $state )
	{
		$this->println( "pop\tstate $state\ton error" );
	}

	/**
	 * Report token discard
	 * @param int $state
	 * @param int $token
	 * @param string $name
	 * @param object $value
	 */
	public function discard ( $state, $token, $name, $value )
	{
		$this->println( "discard\tstate $state\t$token $name\tvalue $value" );
	}

	/**
	 * Report token reduce
	 * @param int $from
	 * @param int $to
	 * @param int $rule
	 * @param string $text
	 * @param int $len
	 */
	public function reduce( $from, $to, $rule, $text, $len )
	{
		$this->println( "reduce\tstate $from\tuncover $to \trule ($rule) $text" );
	}

	/**
	 * Report accepting a token
	 * @param object $value
	 */
	public function accept( $value )
	{
		$this->println("accept\tvalue $value");
	}

	/**
	 * Report an error
	 * @param string $message
	 */
	public function error( $message )
	{
		$this->println( "error\t$message" );
	}

	/**
	 * Reject the token
	 */
	public function reject()
	{
		$this->println( "reject" );
	}

}
