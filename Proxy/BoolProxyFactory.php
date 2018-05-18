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

namespace lyquidity\XPath2\Proxy;

use \lyquidity\XPath2\lyquidity\Types;
use \lyquidity\XPath2\lyquidity\Type;
use lyquidity\XPath2\CoreFuncs;
use \lyquidity\XPath2\lyquidity\Convert;

/**
 * BoolFactory (internal)
 */
class BoolProxyFactory extends ValueProxyFactory
{
	/**
	 * @var int $Code = 21
	 */
	const Code = 21;

	/**
	 * Create
	 * @param object $value
	 * @return ValueProxy
	 */
	public function Create( $value )
    {
		if ( is_bool( $value ) )
		{
			$value = $value ? CoreFuncs::$True : CoreFuncs::$False;
		}
		else if ( ! $value instanceof CoreFuncs::$True &&  ! $value instanceof CoreFuncs::$False )
		{
			$value = Convert::ToBoolean( $value, null );
		}
        return new BoolProxy( $value );
    }

	/**
	 * GetValueCode
	 * @return int
	 */
	public function GetValueCode()
    {
        return BoolProxyFactory::Code;
    }

	/**
	 * GetValueType
	 * @return Type
	 */
	public function GetValueType()
    {
        return Types::$BooleanType;
    }

	/**
	 * @return bool getIsNumeric
	 */
	public function getIsNumeric()
	{
		return false;
	}

	/**
	 * Compare
	 * @param ValueProxyFactory $other
	 * @return int
	 */
	public function Compare( $other )
    {
		if ( $other->GetValueCode() == BoolProxyFactory::Code )
			return 0;
		return -2;
    }

    public static function tests()
    {}

}



?>
