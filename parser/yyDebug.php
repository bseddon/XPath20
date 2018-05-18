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

interface yyDebug {
	/**
	 * @param int $state
	 * @param object $value
	 */
	function push( $state, $value );
	/**
	 * @param int $state
	 * @param int $token
	 * @param string $name
	 * @param object $value
	 */
	function lex( $state, $token, $name, $value);
	/**
	 * @param int $from
	 * @param int $to
	 * @param int $errorFlag (optional)
	 */
	function shift( $from, $to, $errorFlag = null );
	/**
	 * @param int $state
	 */
	function pop( $state );
	/**
	 * @param int $state
	 * @param int $token
	 * @param string $name
	 * @param object $value
	 */
	function discard( $state, $token, $name, $value );
	/**
	 * @param int $from
	 * @param int $to
	 * @param int $rule
	 * @param string $text
	 * @param int $len
	 */
	function reduce( $from, $to, $rule, $text, $len );
	/**
	 * @param object $value
	 */
	function accept( $value );
	/**
	 * @param string $message
	 */
	function error( $message);
	/**
	 */
	function reject ();
}
