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

namespace lyquidity\XPath2;

class XPath2ResultType
{
	const Number = 0;
	const String = 1;
	const Navigator = 2;
	const Boolean = 3;
	const NodeSet = 4;
	const Any = 5;
	const Error = 6;
	const DateTime = 7;
	const Duration = 8;
	const AnyUri = 9;
	const QName = 10;
	const Other = 11;

	/**
	 * Return the constant name corresponding to the $tokenValue
	 * @param int $typeCode
	 * @return string
	 */
	public static function getResultTypeName( $resultType )
	{
		// Maybe this should be: XmlTypeCode::$codeToTypeMap[ $typeCode ];
		$oClass = new \ReflectionClass( __CLASS__ );
		foreach ( $oClass->getConstants() as $key => $value )
		{
			if ( $value == $resultType ) return $key;
		}

		return "???";
	}

}

?>
