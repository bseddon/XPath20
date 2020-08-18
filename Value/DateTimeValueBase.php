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

namespace lyquidity\XPath2\Value;

use \lyquidity\xml\interfaces\IComparable;
use \lyquidity\xml\interfaces\IConvertable;
use \lyquidity\XPath2\lyquidity\Convert;
use lyquidity\xml\TypeCode;
use lyquidity\XPath2\Undefined;
use lyquidity\xml\exceptions\ArgumentException;

/**
 * DateTimeValueBase (public abstract)
 */
class DateTimeValueBase implements IComparable, IConvertable
{
	/**
	 * CLASSNAME
	 * @var string
	 */
	public static $CLASSNAME = "lyquidity\XPath2\Value\DateTimeValueBase";

	/**
	 * Default constructor
	 */
	public  function __construct()
	{}

	/**
	 * Constructor
	 * @param bool $sign
	 * @param \DateTime $value
	 * @param bool $notLocal
	 */
	public function fromDateBase( $sign, $value, $notLocal = false )
	{
		if ( ! $value instanceof \DateTime )
		{
			throw new \InvalidArgumentException( "$value" );
		}

		if ( ! property_exists( $value, 'microseconds' ) )
		{
			$value->microseconds = 0;
		}

		$this->S = $sign;
		$this->Value = $value;
		$this->IsLocal = ! $notLocal && $value->getTimezone()->getName() == date_default_timezone_get();
	}

	/**
	 * Constructor
	 * @param bool $sign
	 * @param \DateTime $date
	 * @param \DateTime $time
	 * @param bool $notLocal
	 */
	public function fromDateTimeBase( $sign, $date, $time, $notLocal = false )
	{
		if ( ! $date instanceof \DateTime )
		{
			throw new \InvalidArgumentException( "$date" );
		}
		if ( ! $time instanceof \DateTime )
		{
			throw new \InvalidArgumentException( "$time" );
		}

		$this->S = $sign;
		$this->IsLocal = ! $notLocal && (
				$date->getTimezone()->getName() == date_default_timezone_get() ||
				$time->getTimezone()->getName() == date_default_timezone_get()
			);
		$offset = $this->IsLocal ? "" : $date->getTimezone()->getName();
		$microseconds = isset( $time->microseconds ) ? $time->microseconds : 0;
		$date = "{$date->format("Y")}-{$date->format("m")}-{$date->format("d")}";
		$time = "{$time->format("H")}:{$time->format("i")}:{$time->format("s")}";
		$this->Value = new \DateTime( "{$date}T{$time}{$offset}" );
		$this->Value->microseconds = $microseconds;
	}

	/**
	 * S
	 * @var bool $S
	 */
	public $S;

	/**
	 * Value
	 * @var \DateTime $Value
	 */
	public $Value;

	/**
	 * IsLocal
	 * @var bool $IsLocal
	 */
	public $IsLocal;

	/**
	 * IsPeriodEnd
	 * @var bool
	 */
	public $isPeriodEnd = false;

	// /**
	//  * A note of the microseconds
	//  * @var integer
	//  */
	// public $microseconds = 0;

	/**
	 * Equals
	 * @param object $obj
	 * @return bool
	 */
	public function Equals( $obj )
	{
		if ( ! $obj instanceof DateTimeValueBase )
		{
			return false;
		}

		/**
		 * @var DateTimeValueBase $other
		 */
	    $other = $obj;
		return $this->Value == $other->Value &&
			( isset( $this->Value->microseconds ) ? $this->Value->microseconds : 0 ) ==
			( isset( $other->Value->microseconds ) ? $other->Value->microseconds : 0 );
	}

	/**
	 * CompareTo
	 * @param object $obj
	 * @return int
	 */
	public function CompareTo( $obj )
	{
		if ( ! $obj instanceof DateTimeValueBase )
	        throw new ArgumentException("$obj");

		/**
		 * @var DateTimeValueBase $other
		 */
		$other = $obj;

		return $this->Value == $other->Value
			? 0
			: ( $this->Value > $other->Value ? 1 : -1 );

		// Replace any occurence of the timezone Zulu
		$thisDate = preg_replace( "/Z$/", "+00:00", $this->ToString( null ) );
		$otherDate = preg_replace( "/Z$/", "+00:00", $other->ToString( null ) );

		return strcmp( $thisDate, $otherDate );
	}

	/**
	 * Return the timezone signature
	 */
	public function getTimezone()
	{
		return $this->Value->format("P");
	}

	/**
	 * Create a DayTimeDurationValue representation of the timezone offset
	 * @return DayTimeDurationValue
	 */
	public function TimezoneToInterval()
	{
		// Get the timezone
		$timezone = $this->getTimezone();

		// No deviation returns an empty sequence
		if ( $timezone == "Z" || $timezone == "+00:00" || $this->Value->getTimezone()->getName() == date_default_timezone_get() )
		{
			return DayTimeDurationValue::Parse( "PT0S" );
			// /MinimalConformance/Functions/DurationDateTimeFunc/ComponentExtractionDDT
			// K-TimezoneFromDateFunc-7
			return Undefined::getValue();
		}

		// Create an interval string
		if ( ! preg_match( "/(?<sign>[+-])(?<hours>\d{2,2}):(?<minutes>\d{2,2})/", $timezone, $matches ) )
		{
			return Undefined::getValue();
		}

		$invert = $matches['sign'] == '-' ? '-' : '';

		return DayTimeDurationValue::Parse( "{$invert}PT{$matches['hours']}H{$matches['minutes']}M" );

	}

	/**
	 * ToGYearMonth
	 * @return GYearMonthValue
	 */
	public function ToGYearMonth()
	{
        return new GYearMonthValue( $this->S, new \DateTime( "{$this->Value->format("Y")}-{$this->Value->format("m")}-01T00:00:00{$this->Value->getTimezone()->getName()}" ) );
	}

	/**
	 * ToGYear
	 * @return GYearValue
	 */
	public function ToGYear()
	{
        return new GYearValue( $this->S, new \DateTime( "{$this->Value->format("Y")}-01-01T00:00:00{$this->Value->getTimezone()->getName()}" ) );
	}

	/**
	 * ToGDay
	 * @return GDayValue
	 */
	public function ToGDay()
	{
        return new GDayValue( new \DateTime( "2008-01-{$this->Value->format("d")}T00:00:00{$this->Value->getTimezone()->getName()}" ) );
	}

	/**
	 * ToGMonth
	 * @return GMonthValue
	 */
	public function ToGMonth()
	{
        return new GMonthValue( new \DateTime( "2008-{$this->Value->format("m")}-01T00:00:00{$this->Value->getTimezone()->getName()}" ) );
	}

	/**
	 * ToGMonthDay
	 * @return GMonthDayValue
	 */
	public function ToGMonthDay()
	{
        return new GMonthDayValue( new \DateTime( "2008-{$this->Value->format("m")}-{$this->Value->format("d")}T00:00:00{$this->Value->getTimezone()->getName()}" ) );
	}

	/**
	 * GetTypeCode
	 * @return TypeCode
	 */
	public function GetTypeCode()
	{
	    return TypeCode::DateTime;
	}

	/**
	 * ToBoolean
	 * @param IFormatProvider $provider
	 * @return bool
	 */
	public function ToBoolean( $provider )
	{
	    return Convert::ToBoolean( $this->Value, $provider );
	}

	/**
	 * ToByte
	 * @param IFormatProvider $provider
	 * @return byte
	 */
	public function ToByte( $provider )
	{
	    return Convert::ToByte( $this->Value, $provider );
	}

	/**
	 * ToChar
	 * @param IFormatProvider $provider
	 * @return char
	 */
	public function ToChar( $provider )
	{
	    return Convert::ToChar( $this->Value, $provider );
	}

	/**
	 * ToDateTime
	 * @param IFormatProvider $provider
	 * @return DateTime
	 */
	public function ToDateTime( $provider )
	{
	    return Convert::ToDateTime( $this->Value, $provider );
	}

	/**
	 * ToDecimal
	 * @param IFormatProvider $provider
	 * @return DecimalValue
	 */
	public function ToDecimal( $provider )
	{
	    return Convert::ToDecimal( $this->Value, $provider );
	}

	/**
	 * ToDouble
	 * @param IFormatProvider $provider
	 * @return double
	 */
	public function ToDouble( $provider )
	{
	    return Convert::ToDouble( $this->Value, $provider );
	}

	/**
	 * ToInt16
	 * @param IFormatProvider $provider
	 * @return short
	 */
	public function ToInt16( $provider )
	{
	    return Convert::ToInt16( $this->Value, $provider );
	}

	/**
	 * ToInt32
	 * @param IFormatProvider $provider
	 * @return int
	 */
	public function ToInt( $provider )
	{
		return Convert::ToInt( $this->Value, $provider );
	}

	/**
	 * ToInt32
	 * @param IFormatProvider $provider
	 * @return int
	 */
	public function ToInt32( $provider )
	{
	    return Convert::ToInt32( $this->Value, $provider );
	}

	/**
	 * ToInt64
	 * @param IFormatProvider $provider
	 * @return long
	 */
	public function ToInt64( $provider )
	{
	    return Convert::ToInt64( $this->Value, $provider );
	}

	/**
	 * ToSByte
	 * @param IFormatProvider $provider
	 * @return sbyte
	 */
	public function ToSByte( $provider )
	{
	    return Convert::ToSByte( $this->Value, $provider );
	}

	/**
	 * ToSingle
	 * @param IFormatProvider $provider
	 * @return float
	 */
	public function ToSingle( $provider )
	{
	    return Convert::ToSingle( $this->Value, $provider );
	}

	/**
	 * ToString
	 * @param IFormatProvider $provider
	 * @return string
	 */
	public function ToString( $provider = null )
	{
	    return Convert::ToString( $this->Value, $provider );
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
	    return Convert::ToUInt16( $this->Value, $provider );
	}

	/**
	 * ToUInt32
	 * @param IFormatProvider $provider
	 * @return uint
	 */
	public function ToUInt32( $provider )
	{
	    return Convert::ToUInt32( $this->Value, $provider );
	}

	/**
	 * ToUInt64
	 * @param IFormatProvider $provider
	 * @return ulong
	 */
	public function ToUInt64( $provider )
	{
	    return Convert::ToUInt64( $this->Value, $provider );
	}

}

?>
