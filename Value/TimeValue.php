<?php
/**
 * XPath 2.0 for PHP
 *  _					   _	 _ _ _
 * | |   _   _  __ _ _   _(_) __| (_) |_ _   _
 * | |  | | | |/ _` | | | | |/ _` | | __| | | |
 * | |__| |_| | (_| | |_| | | (_| | | |_| |_| |
 * |_____\__, |\__, |\__,_|_|\__,_|_|\__|\__, |
 *	     |___/	  |_|					 |___/
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

use lyquidity\xml\MS\XmlNamespaceManager;
use lyquidity\xml\MS\XmlSchemaType;
use lyquidity\xml\MS\XmlTypeCode;
use lyquidity\xml\interfaces\IComparable;
use lyquidity\XPath2\Properties\Resources;
use lyquidity\XPath2\DOM\XmlSchema;
use lyquidity\xml\interfaces\IXmlSchemaType;
use lyquidity\XPath2\CoreFuncs;
use lyquidity\XPath2\SequenceType;
use lyquidity\XPath2\Undefined;
use lyquidity\xml\exceptions\InvalidCastException;
use lyquidity\xml\exceptions\ArgumentException;
use lyquidity\xml\exceptions\ArgumentOutOfRangeException;
use lyquidity\XPath2\XPath2Exception;

/**
 * TimeValue (public)
 */
class TimeValue implements IComparable, IXmlConvertable, IXmlSchemaType
{
	/**
	 * CLASSNAME
	 * @var string
	 */
	public static $CLASSNAME = "lyquidity\XPath2\Value\TimeValue";

	/**
	 * @var int $ProxyValueCode = 14
	 */
	const ProxyValueCode = 14;

	/**
	 * Constructor
	 * @param \DateTime $value
	 * @param bool $notLocal
	 */
	public  function __construct( $value, $notLocal = false )
	{
		$this->Value = $value;
		$this->Value->isDate = false;
		$this->Value->isTime = true;
		$this->IsLocal = ! $notLocal && $value->getTimezone()->getName() == date_default_timezone_get();
	}

	/**
	 * Value
	 * @var \DateTime $Value
	 */
	public  $Value;

	/**
	 * IsLocal
	 * @var bool $IsLocal
	 */
	public $IsLocal;

	/**
	 * Returns a schema type for the proxy value
	 * @return XmlSchemaType
	 */
	public function getSchemaType()
	{
		return XmlSchema::$Time;
	}

	/**
	 * Returns the contained value
	 * @return TimeValue
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
		if ( ! $obj instanceof TimeValue )
		{
			return false;
		}

		/**
		 * @var DateTimeValueBase $other
		 */
		$other = $obj;
		return $this->ToString( null ) == $other->ToString( null );
	}

	/**
	 * ToString
	 * @return string
	 */
	public function ToString()
	{
		$result = "";
		$format = "H:i:s";

		// BMS 2020-12-16 This property may not exist and, amyway, the previous test clearly wrong.
		//
		// if ( ! @empty( $this->Value->microseconds != 0 ) )
		if ( property_exists( $this->Value, 'microseconds' ) && ! @empty( $this->Value->microseconds ) )
		{
			$format .= "." . rtrim( $this->Value->microseconds, "0" );
			$format = rtrim( $format, "." );
		}

		if ( ! $this->IsLocal )
		{
			$format .= $this->Value->getOffset() == 0
	   		 	? "\Z"
	   			: "P";
		}

		return $this->Value->format( $format );
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
	 * Create a DayTimeDurationValue representation of the timezone offset
	 * @return DayTimeDurationValue
	 */
	public function TimezoneToInterval()
	{
		// Get the timezone
		$timezone = $this->Value->getTimezone()->getName();

		// No deviation returns an empty sequence
		if ( $timezone == "Z" || $timezone == "+00:00" || $timezone == date_default_timezone_get() )
		{
			return DayTimeDurationValue::Parse( "PT0S" );
			// /MinimalConformance/Functions/DurationDateTimeFunc/ComponentExtractionDDT
			// K-TimezoneFromDateFunc-7
			// return Undefined::getValue();
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
	 * Parse
	 * @param string $text
	 * @return TimeValue
	 */
	public static function Parse( $text )
	{
		$text = CoreFuncs::NormalizeSpace( $text );

		// $x = "-2016-01-02T09:08:02.1234Z";
		// $x = "-2016-01-02";
		// $x = "-2016-01-02T09:08:02";
		// $x = "-2016-01-02T09:08";
		// $x = "-2016-01-02T09";
		$pattern = "^((?<time>(?<hour>[0-9]{1,2})(:(?<minute>[0-9]{1,2}))?(:(?<second>[0-9]{2,2}))?)(\.(?<micro>[0-9]+))?)?(?<offset>(?=[+\-a-zA-Z]).*)?$";

		$result = preg_match( "/$pattern/", $text, $matches );
		$error = empty( $text ) || ! $result ||
				( ! empty( $matches['offset'] ) && $matches['offset'] == $text ) ||
				( empty( $matches['minute'] ) && empty( $matches['second'] ) ) ||
				( ! empty( $matches['hour'] )   && ( $matches['hour'] > 24 ) ) ||
				( ! empty( $matches['minute'] ) && ( $matches['minute'] > 59 ) ) ||
				( ! empty( $matches['second'] ) && ( $matches['second'] > 59 ) ) ||
				( ! empty( $matches['offset'] ) && ! preg_match( "/^[+-]?\d+$|^[+-]?\d{2}:\d{2}$|^[a-z]+$/i", $matches['offset'] ) );

		if ( ! $error )
		{
			$timeSeconds = ( empty( $matches['hour'] ) ? 0 : $matches['hour'] * 3600 ) +
			( empty( $matches['minute'] ) ? 0 : $matches['minute'] * 60 ) +
			( empty( $matches['second'] ) ? 0 : $matches['second'] * 1 ) +
			( empty( $matches['micro'] ) ? 0 : $matches['micro'] / 1000 );
			$error = $timeSeconds > 86400;

			if ( ! $error && ! empty( $matches['offset'] ) && preg_match( "/^[+-](?<hours>\d{2})(:(?<minutes>\d{2}))?$/", $matches['offset'], $offsetMatches ) )
			{
				$error = ( ! empty( $offsetMatches['hours'] ) && $offsetMatches['hours'] > 14 ) ||
				empty( $offsetMatches['minutes'] ) ||
				$offsetMatches['minutes'] > 59;
			}
		}

		if ( $error )
		{
			throw XPath2Exception::withErrorCodeAndParams( "FORG0001", Resources::FORG0001, array( $text, "Date" ) );
		}

		$sign = empty( $matches['sign'] ) ? false : $matches['sign'] == "-" ;
		$date = empty( $matches['date'] ) ? "1970-01-01" : $matches['date'];
		$time = empty( $matches['time'] ) ? "" : $matches['time'];
		$offset = empty( $matches['offset'] ) ? "" : $matches['offset'];

		$microseconds = empty( $matches['micro'] ) ? 0 : $matches['micro'];
		$notlocal = ! empty( $matches['offset'] );

		$T = $date && $time ? "T" : "";

		/**
		 * @var \DateTime $dateTime
		 */
		$dateTime = new \DateTime( "{$date}$T{$time}$offset" );
		$dateTime->microseconds = $microseconds;

		return new TimeValue( $dateTime, $notlocal );
	}

	/**
	 * CompareTo
	 * @param object $obj
	 * @return int
	 */
	function CompareTo( $obj )
	{
		if ( ! $obj instanceof TimeValue )
			throw new ArgumentException("$obj");

		/**
		 * @var TimeValue $other
		 */
		$other = $obj;
		return $this->Value == $other->Value
			? 0
			: ( $this->Value > $other->Value ? 1 : -1 );
	}

	/**
	 * Add
	 * @param TimeValue $tm
	 * @param DayTimeDurationValue $duration
	 * @return TimeValue
	 */
	public static function AddDayTimeDuration( $tm, $duration )
	{
		try
		{
			if ( ! $tm instanceof TimeValue )
				throw new \InvalidArgumentException("$tm");

	   		if ( ! $duration instanceof DurationValue )
				throw new \InvalidArgumentException("$duration");

			// Shortcut for when there is no duration
			if ( ! $duration->Value->hasValue ) return $tm;

			$dt = $tm->Value;

			// This is a convoluted way to add dates.  But its necessary because the
			// PHP DateTime call cannot handle negative years and, so, not all the
			// weirdness that happens around year zero.  So instead of relying on the
			// ability of the DataTime class to handle negative year these lines convert
			// the date to the corresponding julian days and perform maths on this number
			// of days then converting the result back to a gregorian date.  Julian dates
			// rooted in 4713BC so maths on dates at the turn of the gregorian calendar
			// are 5000 years in the future.
			$durationSeconds = $duration->getTotalSeconds();
			$julianDay = gregoriantojd( $dt->format("m"), $dt->format("d"), $dt->format("Y") );
			$gregorianDate = jdtogregorian( $julianDay );
			$julianSeconds = new DecimalValue( $julianDay );

			$julianSeconds = $julianSeconds->Mul( 86400 )->Add( $dt->format("H") * 3600 )->Add( $dt->format("i") * 60 )->Add( $dt->format("s") )->Add( "0.{$dt->microseconds}" );
			$newSeconds = $julianSeconds->Add( $durationSeconds );
			$newDay = $newSeconds->Div( 86400 )->getIntegerPart();
			$newTime = $newSeconds->Mod( 86400 );
			$newHour = $newTime->Div( 3600 )->getIntegerPart();
			$newTime = $newTime->Sub( $newHour * 3600 );
			$newMinute = $newTime->Div( 60 )->getIntegerPart();
			$newTime = $newTime->Sub( $newMinute * 60 );
			$newSecond = $newTime->getIntegerPart();
			$newMicro = $newTime->getDecimalPart();

			$today = new \DateTime();
			$year = $today->format("Y");
			$month = $today->format("m");
			$day = $today->format("d");

			// $gregorianDate = jdtogregorian( $newDay );
			// if ( ! preg_match( "!^(?<month>\d+?)/(?<day>\d+?)/(?<sign>-?)(?<year>\d+?)$!", $gregorianDate, $matches ) )
			// 	throw new \UnexpectedValueException();

			$sign = ! empty( $matches['sign'] );

			$gregorianDate = sprintf( "%04s-%02s-%02sT%02d:%02d:%02d.%d", $year, $month, $day, $newHour, $newMinute, $newSecond, $newMicro );
			$newDate = new \DateTime( $gregorianDate, $tm->IsLocal ? null : $dt->getTimezone() );
			$newDate->microseconds = $newMicro;
			return new TimeValue( $newDate, ! $tm->IsLocal );

			// return new TimeValue( $tm->Value->add( $duration->Value ) );
		}
		catch( ArgumentOutOfRangeException $ex )
		{
			throw XPath2Exception::withErrorCode( "FODT0001", Resources::FODT0001 );
		}
	}

	/**
	 * Sub
	 * @param TimeValue $tm1
	 * @param TimeValue $tm2
	 * @return DayTimeDurationValue
	 */
	public static function Sub( $tm1, $tm2 )
	{
		try
		{
			if ( ! $tm1 instanceof TimeValue )
				throw new \InvalidArgumentException("$tm1");

			if ( ! $tm2 instanceof TimeValue )
				throw new \InvalidArgumentException("$tm2");

			$seconds1 = new DecimalValue( $tm1->Value->getTimestamp() );
			$seconds1->Add( $tm1->Value->microseconds );

			$seconds2 = new DecimalValue( $tm2->Value->getTimestamp() );
			$seconds2->Add( $tm2->Value->microseconds );

			$newSeconds = $seconds1->Sub( $seconds2 );
			$sign = $newSeconds->getIsNegative();

			$newSeconds = $newSeconds->getAbs();
			$days = $newSeconds->Div( 86400 )->getIntegerPart();
			$time = $newSeconds->Mod( 86400 );
			$hours = $time->Div( 3600 )->getIntegerPart();
			$time = $time->Sub( $hours * 3600 );
			$minutes = $time->Div( 60 )->getIntegerPart();
			$time = $time->Sub( $minutes * 60 );
			$seconds = $time->getIntegerPart();
			$micro = $time->getDecimalPart();

			$interval = new \DateInterval( sprintf( "P%02sDT%02sH%02sM%02sS", $days, $hours, $minutes, $seconds ) );
			$interval->hasValue = ! $newSeconds->getIsZero();
			$interval->invert = $sign;
			$interval->microseconds = $micro;

			return new DayTimeDurationValue( $interval );

			$diff = $tm2->Value->diff( $tm1->Value );
			return new DayTimeDurationValue( $diff );
		}
		catch( \OverflowException $ex )
		{
			throw XPath2Exception::withErrorCode( "FODT0001", Resources::FODT0001 );
		}
	}

	/**
	 * ValueAs
	 * @param SequenceType $type
	 * @param XmlNamespaceManager $nsmgr
	 * @return object
	 */
	function ValueAs( $type, $nsmgr )
	{
		switch ( $type->TypeCode )
		{
			case XmlTypeCode::AnyAtomicType:
			case XmlTypeCode::Time:

				return $this;

			case XmlTypeCode::String:

				return $this->ToString();

			case XmlTypeCode::UntypedAtomic:

				return new UntypedAtomic( $this->ToString() );

			default:

				throw new InvalidCastException();
		}
	}

	/**
	 * Unit tests
	 */
	public static function tests()
	{
		$time1 = TimeValue::Parse( "17:10:11" );
		echo "{$time1->ToString()}\n";
		$time2 = TimeValue::Parse( "17:10:11" );
		$time3 = TimeValue::Parse( "17:10:12" );

		$result = $time1->Equals( $time2 );
		$result = $time1->Equals( $time3 );

		$result = $time1->CompareTo( $time2 );
		$result = $time1->CompareTo( $time3 );

		$x = TimeValue::AddDayTimeDuration( $time1, DayTimeDurationValue::Parse( "PT1H1M" ) );
		$x = TimeValue::Sub( $time2, $time3 );
	}

}

?>
