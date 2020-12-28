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

namespace lyquidity\XPath2\Proxy;

use lyquidity\XPath2\lyquidity\Type;
use \lyquidity\XPath2\lyquidity\Types;

/**
 * IntProxyFactory (internal)
 */
class IntProxyFactory extends ValueProxyFactory
{
	/**
	 * @var int $Code = 2
	 */
	const Code = 2;

	/**
	 * Create
	 * @param object $value
	 * @return ValueProxy
	 */
	public function Create( $value )
	{
	    return new IntProxy( $value );
	}

	/**
	 * GetValueCode
	 * @return int
	 */
	public function GetValueCode()
	{
	    return IntProxyFactory::Code;
	}

	/**
	 * GetValueType
	 * @return Type
	 */
	public function GetValueType()
	{
	    return Types::$IntType;
	}

	/**
	 * Returns true if the value is numeric
	 * @return bool
	 */
	public function getIsNumeric()
	{
		return true;
	}

	/**
	 * Compare
	 * @param ValueProxyFactory $other
	 * @return int
	 */
	public function Compare($other)
	{
	    switch ( $other->GetValueCode() )
	    {
	        case SByteProxyFactory::Code:
	        case ByteProxyFactory::Code:
	        case UShortProxyFactory::Code:
	        case ShortProxyFactory::Code:
	            return 1;

	        case IntProxyFactory::Code:
	            return 0;

	        case UIntProxyFactory::Code:
	        case ULongProxyFactory::Code:
	        case LongProxyFactory::Code:
	        case IntegerProxyFactory::Code:
	        case DecimalProxyFactory::Code:
	        case FloatProxyFactory::Code:
	        case DoubleProxyFactory::Code:
	            return -1;

	        default:
	            return -2;
	    }
	}

	/**
	 * Unit tests
	 */
	public static function tests()
	{}

}



?>
