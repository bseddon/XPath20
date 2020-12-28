<?php
/**
 * XPath 2.0 for PHP
 *  _				       _	 _ _ _
 * | |   _   _  __ _ _   _(_) __| (_) |_ _   _
 * | |  | | | |/ _` | | | | |/ _` | | __| | | |
 * | |__| |_| | (_| | |_| | | (_| | | |_| |_| |
 * |_____\__, |\__, |\__,_|_|\__,_|_|\__|\__, |
 *	     |___/    |_|				     |___/
 *
 * @author Bill Seddon
 * @version 0.9
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

use lyquidity\XPath2\Value\DecimalValue;
use lyquidity\XPath2\Value\Integer;
use lyquidity\XPath2\Value\Long;
use lyquidity\xml\MS\XmlTypeCardinality;
use lyquidity\XPath2\DOM\XmlSchema;

/**
 * ExtFuncs ( public static )
 */
class MathsFuncs
{
	private static function toDouble( $value )
	{
		if ( $value instanceof XPath2Item )
		{
			$value = $value->getTypedValue();
		}
		else if ( $value instanceof DecimalValue )
		{
			$value = $value->ToDouble( null );
		}
		else if ( $value instanceof Long )
		{
			return $value->ToDouble( null );
		}
		else if ( is_int( $value ) )
		{
			// Do nothing
		}
		else if ( $value instanceof Integer )
	    {
	    	$value = $value->ToDouble( null );
	    }
		else if ( Integer::IsDerivedSubtype( $value ) )
		{
			$value = self::toDouble( Integer::ToInteger( $value, null ) );
		}
		else
		    throw XPath2Exception::withErrorCodeAndParams( "XPTY0004", \lyquidity\XPath2\Properties\Resources::XPTY0004,
				array(
					SequenceType::WithTypeCodeAndCardinality( SequenceType::GetXmlTypeCodeFromObject( $value ), XmlTypeCardinality::OneOrMore ),
					"xs:float | xs:double | xs:decimal | xs:integer in math:pow()"
				)
			);

		return $value;
	}

	/**
	 * pow
	 * @param object $value
	 * @param object $power
	 * @return object
	 */
	public static function pow( $value, $power )
	{
		if ( $value instanceof Undefined || $power instanceof Undefined )
			return $value;

		$value = self::toDouble( $value );
		$power = self::toDouble( $power );

		if ( $value === 0 || $value === 0.0 )
		{
			if ( $power < 0 && is_int( $power ) && abs( $power ) % 2 == 1 )
			{
				return XPath2Item::fromValueAndType( INF, XmlSchema::$Double );
			}
		}
		return XPath2Item::fromValueAndType( pow( $value, $power ), XmlSchema::$Double );

	}

	/**
	 * pi
	 * @return object
	 */
	public static function pi()
	{
		return XPath2Item::fromValueAndType( pi(), XmlSchema::$Double );
	}

	/**
	 * sin
	 * @param object $value
	 * @return object
	 */
	public static function sin( $value )
	{
		if ( $value instanceof Undefined )
			return $value;

		$value = self::toDouble( $value );
		return XPath2Item::fromValueAndType( sin( $value ), XmlSchema::$Double );

	}

	/**
	 * cos
	 * @param object $value
	 * @return object
	 */
	public static function cos( $value )
	{
		if ( $value instanceof Undefined )
			return $value;

		$value = self::toDouble( $value );
		return XPath2Item::fromValueAndType( cos( $value ), XmlSchema::$Double );

	}

	/**
	 * tan
	 * @param object $value
	 * @return object
	 */
	public static function tan( $value )
	{
		if ( $value instanceof Undefined )
			return $value;

		$value = self::toDouble( $value );
		return XPath2Item::fromValueAndType( tan( $value ), XmlSchema::$Double );

	}

	/**
	 * asin
	 * @param object $value
	 * @return object
	 */
	public static function asin( $value )
	{
		if ( $value instanceof Undefined )
			return $value;

		$value = self::toDouble( $value );
		return XPath2Item::fromValueAndType( asin( $value ), XmlSchema::$Double );

	}

	/**
	 * acos
	 * @param object $value
	 * @return object
	 */
	public static function acos( $value )
	{
		if ( $value instanceof Undefined )
			return $value;

		$value = self::toDouble( $value );
		return XPath2Item::fromValueAndType( acos( $value ), XmlSchema::$Double );

	}

	/**
	 * atan
	 * @param object $value
	 * @return object
	 */
	public static function atan( $value )
	{
		if ( $value instanceof Undefined )
			return $value;

		$value = self::toDouble( $value );
		return XPath2Item::fromValueAndType( atan( $value ), XmlSchema::$Double );

	}

	/**
	 * atan2
	 * @param object $value
	 * @param object $value2
	 * @return object
	 */
	public static function atan2( $value, $value2 )
	{
		if ( $value instanceof Undefined || $value2 instanceof Undefined )
			return $value;

		$value = self::toDouble( $value );
		$value2 = self::toDouble( $value2 );
		return XPath2Item::fromValueAndType( atan2( $value, $value2 ), XmlSchema::$Double );

	}

	/**
	 * deg2rad
	 * This is a 'guest' function and not part of the XPath2 functions list but is useful
	 * @param object $value
	 * @return object
	 */
	public static function deg2rad( $value )
	{
		if ( $value instanceof Undefined )
			return $value;

		$value = self::toDouble( $value );
		return XPath2Item::fromValueAndType( deg2rad( $value ), XmlSchema::$Double );

	}

	/**
	 * rad2deg
	 * This is a 'guest' function and not part of the XPath2 functions list but is useful
	 * @param object $value
	 * @return object
	 */
	public static function rad2deg( $value )
	{
		if ( $value instanceof Undefined )
			return $value;

		$value = self::toDouble( $value );
		return XPath2Item::fromValueAndType( rad2deg( $value ), XmlSchema::$Double );

	}

	/**
	 * exp
	 * @param object $value
	 * @return object
	 */
	public static function exp( $value )
	{
		if ( $value instanceof Undefined )
			return $value;

		$value = self::toDouble( $value );
		return XPath2Item::fromValueAndType( exp( $value ), XmlSchema::$Double );

	}


	/**
	 * exp10
	 * @param object $value
	 * @return object
	 */
	public static function exp10( $value )
	{
		if ( $value instanceof Undefined )
			return $value;

		$value = self::toDouble( $value );
		return XPath2Item::fromValueAndType( exp( $value ), XmlSchema::$Double );

	}

	/**
	 * log
	 * @param object $value
	 * @return object
	 */
	public static function log( $value )
	{
		if ( $value instanceof Undefined )
			return $value;

		$value = self::toDouble( $value );
		return XPath2Item::fromValueAndType( log( $value ), XmlSchema::$Double );

	}

	/**
	 * log10
	 * @param object $value
	 * @return object
	 */
	public static function log10( $value )
	{
		if ( $value instanceof Undefined )
			return $value;

		$value = self::toDouble( $value );
		return XPath2Item::fromValueAndType( log( $value ), XmlSchema::$Double );

	}
}