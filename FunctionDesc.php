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
 * @Copyright ( C ) 2017 Lyquidity Solutions Limited
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * ( at your option ) any later version.
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
 * FunctionDesc ( private final )
 */
class FunctionDesc
{
	const CLASSNAME = "lyquidity\XPath2\FunctionDesc";

	/**
	 * @var String $name
	 */
	public $name;

	/**
	 * @var String $ns
	 */
	public $ns;

	/**
	 * @var int $arity
	 */
	public $arity;

	/**
	 * Constructor
	 * @param String $name
	 * @param String $ns
	 * @param int $arity
	 */
	public function __construct( $name, $ns, $arity )
	{
		$this->name = $name;
		$this->ns = $ns;
		$this->arity = $arity;
	}

	/**
	 * Equals
	 * @param object $obj
	 * @return bool
	 */
	public function Equals( $obj )
	{
		if ( ! $obj instanceof FunctionDesc ) return false;
		/**
		 * @var FunctionDesc $other
		 */
		$other = $obj;
		return $this->name == $other->name && $this->ns == $other->ns && ( $this->arity == $other->arity || $this->arity == -1 || $other->arity == -1 );
	}

	/**
	 * GetHashCode
	 * @return int
	 */
	public function GetHashCode( )
	{
		return $name.GetHashCode( );
	}

}
