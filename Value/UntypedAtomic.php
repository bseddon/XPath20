<?php
/**
 * XPath 2.0 for PHP
 *  _					  _	 _ _ _
 * | |   _   _  __ _ _   _(_) __| (_) |_ _   _
 * | |  | | | |/ _` | | | | |/ _` | | __| | | |
 * | |__| |_| | (_| | |_| | | (_| | | |_| |_| |
 * |_____\__, |\__, |\__,_|_|\__,_|_|\__|\__, |
 *	   |___/	|_|					|___/
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

namespace lyquidity\XPath2\Value;

use \lyquidity\xml\interfaces\ICloneable;
use \lyquidity\xml\interfaces\IComparable;
use \lyquidity\xml\interfaces\IConvertable;
use \lyquidity\xml\interfaces\IEquatable;
use \lyquidity\XPath2\lyquidity\Types;
use lyquidity\xml\TypeCode;
use \lyquidity\XPath2\lyquidity\Convert;
use lyquidity\XPath2\Properties\Resources;
use lyquidity\XPath2\DOM\XmlSchema;
use lyquidity\xml\interfaces\IXmlSchemaType;
use lyquidity\xml\exceptions\NotImplementedException;
use lyquidity\xml\exceptions\FormatException;
use lyquidity\XPath2\XPath2Exception;

/**
 * UntypedAtomic (public)
 */
class UntypedAtomic implements ICloneable, IComparable, IConvertable, IEquatable, IXmlSchemaType
{
	public static $CLASSNAME = "lyquidity\XPath2\Value\UntypedAtomic";

	/**
	 * Constructor
	 * @param string $value
	 */
	public  function __construct( $value )
	{
		// $this->Value = trim( $value );
		$this->Value = $value; // instanceof XPath2Item ? $value->getValue() : $value;
	}

	/**
	 * @var String $Value
	 */
	public  $Value;
	/**
	 * @var object $_doubleValue
	 */
	private  $_doubleValue;

	/**
	 * Returns a schema type for the proxy value
	 * @return XmlSchemaType
	 */
	public function getSchemaType()
	{
		return XmlSchema::$UntypedAtomic;
	}

	/**
	 * Returns the contained value
	 * @return UntypedAtomic
	 */
	public function getValue()
	{
		return $this->Value;
	}

	/**
	 * Equals
	 * @param object $obj
	 * @return bool
	 */
	public function Equals( $obj )
	{
		if ( ! $obj instanceof UntypedAtomic )
			return false;

		/**
		 * @var UntypedAtomic $other
		 */
		$other = $obj;
		return $this->Value === $other->Value;
	}

	/**
	 * CanBeNumber
	 * @return bool
	 */
	private function CanBeNumber()
	{
		return is_numeric( $this->Value ) || $this->Value == "INF" || $this->Value == "-INF";
	}

	/**
	 * TryParseDouble
	 * @param double $num
	 * @return bool
	 */
	public function TryParseDouble( &$num )
	{
		if ( ! is_null( $this->_doubleValue ) )
		{
			$num = (double)$this->_doubleValue;
			return true;
		}
		if ( $this->CanBeNumber() )
		{
			if ( is_nan( $this->Value ) || $this->Value == "NAN" )
			{
				$num = NAN;
				$this->_doubleValue = $num;
				return true;
			}
			else if ( $this->Value == INF || $this->Value == "INF" )
			{
				$num = INF;
				$this->_doubleValue = $num;
				return true;
			}
			else if ( $this->Value == -INF || $this->Value == "-INF" )
			{
				$num = -INF;
				$this->_doubleValue = $num;
				return true;
			}
			else
			{
				$locale = \Collator::create( null )->getLocale( \Locale::VALID_LOCALE );
				$fmt = new \NumberFormatter( $locale, \NumberFormatter::DECIMAL );
				$num = $fmt->parse( $this->Value );
				if ( $num )
				{
					if ( $num == 0 ) $num = 0.0;
					$this->_doubleValue = $num;
					return true;
				}
			}
		}

		$num = 0.0;
		$this->_doubleValue = $num;
		return false;
	}

	/**
	 * Clone
	 * @return object
	 */
	public function CloneInstance()
	{
		return new UntypedAtomic( $this->Value );
	}

	/**
	 * CompareTo
	 * @param object $obj
	 * @return int
	 */
	public function CompareTo( $obj )
	{
		if ( ! $obj instanceof UntypedAtomic )
		{
			throw new \lyquidity\xml\exceptions\ArgumentNullException( "$obj" );
		}

		/**
		 * @var UntypedAtomic $src
		 */
		$src = $obj;
		return strcmp( $this->Value, $src->Value );
	}

	/**
	 * GetTypeCode
	 * @return TypeCode
	 */
	public function GetTypeCode()
	{
		return TypeCode::Object;
	}

	/**
	 *
	 * @return boolean
	 */
	public function getIsNode()
	{
		return false;
	}

	/**
	 * ToBoolean
	 * @param IFormatProvider $provider
	 * @return bool
	 */
	public function ToBoolean( $provider )
	{
		try
		{
			return $this->Value != "";
			// return Convert::ToBoolean( $this->Value, $provider );
		}
		catch ( \lyquidity\xml\exceptionsFormatException $ex )
		{
			throw XPath2Exception::withErrorCodeAndParams("FORG0001", Resources::FORG0001, array( $this->Value, "xs:boolean") );
		}
	}

	/**
	 * ToByte
	 * @param IFormatProvider $provider
	 * @return byte
	 */
	public function ToByte( $provider )
	{
		try
		{
			return Convert::ToByte( $this->Value, $provider );
		}
		catch ( FormatException $ex )
		{
			throw XPath2Exception::withErrorCodeAndParams("FORG0001", Resources::FORG0001, array( $this->Value, "xs:unsignedByte") );
		}
	}

	/**
	 * ToChar
	 * @param IFormatProvider $provider
	 * @return char
	 */
	public function ToChar( $provider )
	{
		throw new NotImplementedException();
	}

	/**
	 * ToDateTime
	 * @param IFormatProvider $provider
	 * @return DateTime
	 */
	public function ToDateTime( $provider )
	{
		throw new NotImplementedException();
	}

	/**
	 * ToDecimal
	 * @param IFormatProvider $provider
	 * @return DecimalValue
	 */
	public function ToDecimal( $provider )
	{
		try
		{
			return Convert::ToDecimal( $this->Value, $provider );
		}
		catch ( FormatException $ex )
		{
			throw XPath2Exception::withErrorCodeAndParams("FORG0001", Resources::FORG0001, array( $this->Value, "xs:decimal") );
		}
	}

	/**
	 * ToSingle
	 * @param IFormatProvider $provider
	 * @return float
	 */
	public function ToSingle( $provider )
	{
		try
		{
			/**
			 * @var float $num
			 */
			return Convert::ToSingle( $this->Value, $provider );
		}
		catch ( FormatException $ex )
		{
			throw XPath2Exception::withErrorCodeAndParams("FORG0001", Resources::FORG0001, array( $this->Value, "xs:float") );
		}
	}

	/**
	 * ToDouble
	 * @param IFormatProvider $provider
	 * @return double
	 */
	public function ToDouble( $provider )
	{
		try
		{
			if ( is_null( $this->_doubleValue ) )
			{
				$this->_doubleValue = Convert::ToDouble( $this->Value, $provider );
			}
			return is_null( $this->_doubleValue ) ? null : (double)$this->_doubleValue;
		}
		catch ( FormatException $ex )
		{
			throw XPath2Exception::withErrorCodeAndParams("FORG0001", Resources::FORG0001, array( $this->Value, "xs:double") );
		}
	}

	/**
	 *
	 * {@inheritDoc}
	 * @see \\lyquidity\xml\interfaces\IConvertable::Toint()
	 */
	public function Toint( $provider )
	{
		return $this->ToInt32( $provider );
	}

	/**
	 * ToInt16
	 * @param IFormatProvider $provider
	 * @return short
	 */
	public function ToInt16( $provider )
	{
		try
		{
			return Convert::ToInt16( $this->Value, $provider );
		}
		catch ( FormatException $ex )
		{
			throw XPath2Exception::withErrorCodeAndParams("FORG0001", Resources::FORG0001, array( $this->Value, "xs:short") );
		}
	}

	/**
	 * ToInt32
	 * @param IFormatProvider $provider
	 * @return int
	 */
	public function ToInt32( $provider )
	{
		try
		{
			return Convert::ToInt32( $this->Value, $provider );
		}
		catch ( FormatException $ex )
		{
			throw XPath2Exception::withErrorCodeAndParams("FORG0001", Resources::FORG0001, array( $this->Value, "xs:int") );
		}
	}

	/**
	 * ToInt64
	 * @param IFormatProvider $provider
	 * @return long
	 */
	public function ToInt64( $provider )
	{
		try
		{
			return Convert::ToInt64( $this->Value, $provider );
		}
		catch ( FormatException $ex )
		{
			throw XPath2Exception::withErrorCodeAndParams("FORG0001", Resources::FORG0001, array( $this->Value, "xs:long") );
		}
	}

	/**
	 * ToSByte
	 * @param IFormatProvider $provider
	 * @return sbyte
	 */
	public function ToSByte( $provider )
	{
		try
		{
			return Convert::ToSByte( $this->Value, $provider );
		}
		catch ( FormatException $ex )
		{
			throw XPath2Exception::withErrorCodeAndParams("FORG0001", Resources::FORG0001, array( $this->Value, "xs:byte") );
		}
	}

	/**
	 * ToString
	 * @param IFormatProvider $provider
	 * @return string
	 */
	public function ToString( $provider = null )
	{
		return  $this->Value . "";
	}

	/**
	 * Return a stringified version of the object
	 * @return string
	 */
	public function __toString()
	{
		return $this->ToString();
	}

	/**
	 * ToType
	 * @param Type $conversionType
	 * @param IFormatProvider $provider
	 * @return object
	 */
	public function ToType( $conversionType, $provider )
	{
		return Convert::ChangeType( $this->Value, $conversionType, $provider );
	}

	/**
	 * ToUInt16
	 * @param IFormatProvider $provider
	 * @return ushort
	 */
	public function ToUInt16( $provider )
	{
		try
		{
			return Convert::ToUInt16( $this->Value, $provider );
		}
		catch ( FormatException $ex )
		{
			throw XPath2Exception::withErrorCodeAndParams("FORG0001", Resources::FORG0001, array( $this->Value, "xs:unsignedShort") );
		}
	}

	/**
	 * ToUInt32
	 * @param IFormatProvider $provider
	 * @return uint
	 */
	public function ToUInt32( $provider )
	{
		try
		{
			return Convert::ToUInt32( $this->Value, $provider );
		}
		catch ( FormatException $ex )
		{
			throw XPath2Exception::withErrorCodeAndParams("FORG0001", Resources::FORG0001, array( $this->Value, "xs:unsignedInt") );
		}
	}

	/**
	 * ToUInt64
	 * @param IFormatProvider $provider
	 * @return ulong
	 */
	public function ToUInt64( $provider )
	{
		try
		{
			return Convert::ToUInt64( $this->Value, $provider );
		}
		catch ( FormatException $ex )
		{
			throw XPath2Exception::withErrorCodeAndParams("FORG0001", Resources::FORG0001, array( $this->Value, "xs:unsignedLong") );
		}
	}

	public static function tests()
	{
		$www = new UntypedAtomic( "100" );
		$xxx = new UntypedAtomic( "xxx" );
		$yyy = new UntypedAtomic( "yyy" );
		$zzz = new UntypedAtomic( 1 );

		$result = $xxx->Equals( $xxx ); // True
		$result = $xxx->Equals( $yyy ); // False
		$result = $zzz->Equals( $zzz ); // True

		$num;
		$result = $xxx->TryParseDouble( $num ); // False
		$result = $www->TryParseDouble( $num ); // True

		$result = $xxx->CloneInstance();

		$result = $xxx->CompareTo( $xxx ); // 0
		$result = $xxx->CompareTo( $yyy ); // -1
		$result = $zzz->CompareTo( $zzz ); // 0
		$result = $xxx->CompareTo( $zzz ); // 1

		$result = $xxx->GetTypeCode();

		$execute = function( $callback )
		{
			try
			{
				return $callback();
			}
			catch( \Exception $ex )
			{
				$class = get_class();
				echo "Error: $class {$ex->getMessage()}\n";
			}

			return null;
		};
		$provider = null;

		foreach ( array( /* $www, $xxx, $yyy, */ $zzz ) as $item )
		{
			$result = $execute( function() use( $item, $provider ) { return $item->ToBoolean( $provider ); } );
			$result = $execute( function() use( $item, $provider ) { return $item->ToByte( $provider ); } );
			$result = $execute( function() use( $item, $provider ) { return $item->ToChar( $provider ); } );
			$result = $execute( function() use( $item, $provider ) { return $item->ToDateTime( $provider ); } );
			$result = $execute( function() use( $item, $provider ) { return $item->ToDecimal( $provider ); } );
			$result = $execute( function() use( $item, $provider ) { return $item->ToSingle( $provider ); } );
			$result = $execute( function() use( $item, $provider ) { return $item->ToDouble( $provider ); } );
			$result = $execute( function() use( $item, $provider ) { return $item->Toint( $provider ); } );
			$result = $execute( function() use( $item, $provider ) { return $item->ToInt16( $provider ); } );
			$result = $execute( function() use( $item, $provider ) { return $item->ToInt32( $provider ); } );
			$result = $execute( function() use( $item, $provider ) { return $item->ToInt64( $provider ); } );
			$result = $execute( function() use( $item, $provider ) { return $item->ToSByte( $provider ); } );
			$result = $execute( function() use( $item, $provider ) { return $item->ToString( $provider ); } );
			$type = Types::$BooleanType;
			$result = $execute( function() use( $item, $provider, $type ) { return $item->ToType( $type, $provider ); } );
			$result = $execute( function() use( $item, $provider ) { return $item->ToUInt16( $provider ); } );
			$result = $execute( function() use( $item, $provider ) { return $item->ToUInt32( $provider ); } );
			$result = $execute( function() use( $item, $provider ) { return $item->ToUInt64( $provider ); } );
		}
	}
}

?>
