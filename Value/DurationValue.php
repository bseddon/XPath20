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

use lyquidity\xml\MS\XmlTypeCode;
use lyquidity\XPath2\SequenceType;
use lyquidity\XPath2\DOM\XmlSchema;
use lyquidity\xml\interfaces\IXmlSchemaType;
use lyquidity\XPath2\Properties\Resources;
use lyquidity\xml\exceptions\InvalidCastException;
use lyquidity\XPath2\XPath2Exception;

if ( ! defined( 'XPATH20_DURATION_TYPE_ANY' ) ) define( 'XPATH20_DURATION_TYPE_ANY', 0 );
if ( ! defined( 'XPATH20_DURATION_TYPE_DAYTIME' ) ) define( 'XPATH20_DURATION_TYPE_DAYTIME', 1 );
if ( ! defined( 'XPATH20_DURATION_TYPE_YEARMONTH' ) ) define( 'XPATH20_DURATION_TYPE_YEARMONTH', 2 );

/**
 * DurationValue (public)
 */
class DurationValue implements IXmlConvertable, IXmlSchemaType
{
	/**
	 * CLASSNAME
	 * @var string
	 */
	public static $CLASSNAME = "lyquidity\XPath2\Value\DurationValue";

	/**
	 * @var int $ProxyValueCode = 15
	 */
	const ProxyValueCode = 15;

	/**
	 * Constructor
	 * @param \DateInterval $value
	 */
	public function __construct( $value )
	{
		if ( $value instanceof IXmlSchemaType )
		{
			$value = $value->getValue();
		}

		$this->Value = $value;
	}

	/**
	 * Value
	 * @var \DateInterval $Value
	 */
	public $Value;

	/**
	 * @var int 0 = any; 1 = dayTime; 2 = yearMonth
	 */
	public $type = XPATH20_DURATION_TYPE_ANY;

	/**
	 * Returns the contained value
	 * @return DurationValue
	 */
	public function getValue()
	{
		return $this->Value;
	}

	/**
	 * Returns a schema type for the proxy value
	 * @return XmlSchemaType
	 */
	public function getSchemaType()
	{
		return XmlSchema::$Duration;
	}

	/**
	 * Equals
	 * @param object $obj
	 * @return bool
	 */
	public function Equals( $obj )
	{
		if ( ! $obj instanceof DurationValue )
		{
			return false;
		}

		/**
		 * @var DurationValue $other
		 */
	    $other = $obj;

		if ( $other->getIsZero() && $this->getIsZero())
			return true;

		if ( $this->getType() != $other->getType() ) return false;

		return $this->ToString() == $other->ToString();
	}

	/**
	 * Add a duration to the current duration
	 * @param DurationValue $duration
	 */
	public function Add( $duration )
	{
		if ( ! $duration instanceof DurationValue )
		{
			throw new \InvalidArgumentException();
		}

		$thisSeconds = $this->getTotalSeconds();
		$durationSeconds = $duration->getTotalSeconds();

		/**
		 * @var DecimalValue $interval
		 */
		$interval = $thisSeconds->Add( $durationSeconds );
		$hasValue = 0 > $interval->CompareTo( 0 ) != 0;
		$invert = 0 > $interval->CompareTo( 0 ) == -1;

		if ( ! $hasValue )
		{
			$di = new \DateInterval( $this->ZeroStringValue() );
			$di->hasValue = false;
			$di->microseconds = 0;
			return $di;
		}

		$di = new \DateInterval( "PT{$interval->getAbs()->getIntegerPart()}S" );

		// Normalize
		$dt = new \DateTimeImmutable();
		$diff = $dt->add( $di );
		$diff = $diff->diff( $dt );
		$diff->hasValue = true;
		$diff->invert = $invert;
		$diff->microseconds = $interval->getDecimalPart() + 0;
		// if ( $hasValue ) $this->type = XPATH20_DURATION_TYPE_DAYTIME;
		$this->Value = $diff;
	}

	/**
	 * Subtract a duration from the current duration
	 * @param DurationValue $duration
	 */
	public function Sub( $duration )
	{
		if ( ! $duration instanceof DurationValue )
		{
			throw new \InvalidArgumentException();
		}

		$duration->Value->invert = ! $duration->Value->invert;
		$this->Add( $duration );

		// // Finally convert $multipliedInterval into a normalized interval
		// /**
		//  * @var \DateTime $dt
		//  */
		// $dt = new \DateTimeImmutable( "2017-06-07" );
		// /**
		//  * @var \DateTime $added
		//  */
		// $added = $dt->add( $this->Value );
		// $duration->Value->invert = ! $duration->Value->invert;
		// /**
		//  * @var \DateTime $subtracted
		//  */
		// $subtracted = $added->add( $duration->Value );
		// /**
		//  * @var \DateInterval $diff
		//  */
		// $diff = $subtracted->diff( $dt );
		// $diff->invert = $diff->days == 0 ? false : ! $diff->invert;
        //
		// $this->Value = $diff;
	}

	/**
	 * Returns one of XPATH20_DURATION_TYPE_ANY, XPATH20_DURATION_TYPE_DAYTIME or XPATH20_DURATION_TYPE_YEARMONTH
	 * @return int
	 */
	public function getType()
	{
		return @isset( $this->Value->type ) ? $this->Value->type : XPATH20_DURATION_TYPE_ANY;
	}

	/**
	 * getIsZero
	 * @var bool $IsZero
	 */
	public function getIsZero()
	{
		return ! $this->Value->hasValue;

		// $dt = new \DateTimeImmutable();
		// $seconds = $dt->getTimestamp();
		// return $dt->add( $this->Value )->getTimestamp() - $seconds == 0;
	}

	/**
	 * getYears
	 * @var int
	 */
	public function getYears()
	{
		return $this->Value->y;
	}

	/**
	 * getMonths
	 * @var int
	 */
	public function getMonths()
	{
		return $this->Value->m;
	}

	/**
	 * getDays
	 * @var int
	 */
	public function getDays()
	{
		return @isset( $this->Value->daysOnly ) && $this->Value->daysOnly && $this->Value->days ? $this->Value->days : $this->Value->d;
		return $this->Value->d;
	}

	/**
	 * getHours
	 * @var int getHours
	 */
	public function getHours()
	{
		return $this->Value->h;
	}

	/**
	 * getMinutes
	 * @var int
	 */
	public function getMinutes()
	{
		return $this->Value->i;
	}

	/**
	 * getSeconds
	 * @var int|double
	 */
	public function getSeconds()
	{
		return $this->Value->s + ( @isset( $this->Value->microseconds ) ? "0." . $this->Value->microseconds : 0 );
	}

	/**
	 * getMilliseconds
	 * @var int
	 */
	public function getMilliseconds()
	{
		return empty( $this->Value->microseconds ) ? 0 : $this->Value->microseconds;
	}

	/**
	 * getInverted
	 * @var bool
	 */
	public function getInverted()
	{
		return $this->Value->invert;
	}

	/**
	 * Get the total number of seconds in the interval
	 * @return DecimalValue
	 */
	public function getTotalSeconds()
	{
		$dt = new \DateTimeImmutable();
		$seconds = $dt->getTimestamp();
		$result = new DecimalValue( $dt->add( $this->Value )->getTimestamp() - $seconds );
		$result = $result->Add( ( $this->Value->invert ? "-" : "" ) . "0.{$this->Value->microseconds}" );
		if ( $this->Value->invert )
		{
			// $result = $result->Mul( -1 );
		}
		return $result;
	}

	/**
	 * Return the total days of the duration
	 * @return int
	 */
	public function getTotalDays()
	{
		// Get a count of days.  The 'diff' function generates an interval with a count of days in ->days
		$dt = new \DateTimeImmutable();
		return $dt->add( $this->Value )->diff( $dt )->days;
	}

	/**
	 * Return the total months of the duration
	 * @return int
	 */
	public function getTotalMonths()
	{
		return ( $this->getInverted() ? -1 : 1 ) * ( $this->Value->y * 12 + $this->Value->m );
	}

	/**
	 * ToString
	 * @return string
	 */
	public function ToString()
	{
		$daysOnly = @isset( $this->Value->daysOnly ) && $this->Value->daysOnly;

		// Reading all non-zero date parts.
		$date = array_filter(array(
			'Y' => $daysOnly ? 0 : $this->Value->y,
			'M' => $daysOnly ? 0 : $this->Value->m,
			'D' => $daysOnly ? $this->Value->days : $this->Value->d,
		));

		$microseconds = "";
		if ( ! empty( @$this->Value->microseconds ) && ! empty( rtrim( $this->Value->microseconds, "0" ) ) )
		{
			$microseconds = str_pad( substr( "." . $this->Value->microseconds, 0, 4 ), 4, "0", STR_PAD_RIGHT );
		}

		// Reading all non-zero time parts.
		$time = array_filter(array
		(
			'H' => $this->Value->h,
			'M' => $this->Value->i,
			'S' => $this->Value->s . $microseconds,
		));

		$specString = 'P';

		// Adding each part to the spec-string.
		foreach ($date as $key => $value)
		{
			$specString .= sprintf( "%s%s", $value, $key );
		}

		if ( count( $time ) > 0 )
		{
			$specString .= 'T';
			foreach ( $time as $key => $value )
			{
				$specString .= $value . $key;
			}
		}
	    return $specString == "P"
			? $this->ZeroStringValue()
			: ( $this->Value->invert ? "-" : "" ) . $specString;
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
	 * ZeroStringValue
	 * @return string
	 */
	public function ZeroStringValue()
	{
	    return "PT0S";
	}

	/**
	 * Parse a formatted string P1D
	 * @param string $text The duration value string to parse
	 * @return DurationValue
	 */
	public static function Parse( $text )
	{
		return DurationValue::_parse( $text, __CLASS__ );
	}

	/**
	 * Returns true if the date interval parameter has a non zero value
	 * @param \DateInterval $di
	 * @return bool
	 */
	public static function hasValue( $di )
	{
		$components = array( "s", "i", "h", "d", "m", "y" );
		$hasValue = false;
		foreach( $components as $component )
		{
			$hasValue = isset( $di->$component ) && $di->$component != 0;
			if ( $hasValue ) break;
		}
		return $hasValue;
	}

	/**
	 * Parse
	 * @param string $text The duration value string to parse
	 * @param string $class (optional) The name of the class to create
	 * @return DurationValue
	 */
	protected static function _parse( $text, $class = null )
	{
		$text = trim( $text );

		$pattern = "^(?<plus>\+)?(?<sign>-)?(?<all>P((?<year>\d+)Y)?((?<month>\d+)M)?((?<day>\d+)D)?T?((?<hour>\d+)H)?((?<minute>\d+)M)?(((?<second>\d+)(\.(?<micro>\d*))?)S)?)$";
		if ( empty( $text ) || ! preg_match( "/$pattern/", $text, $matches ) )
		{
			throw XPath2Exception::withErrorCodeAndParams( "FORG0001", Resources::FORG0001, array( $text, "Duration" ) );
		}

		$sign = empty( $matches['sign'] ) ? false : $matches['sign'] == "-" ;
		$plus = empty( $matches['plus'] ) ? false : $matches['plus'] == "+" ;

		if (
			( in_array( $matches["all"][ strlen( $matches["all"] ) -1 ], array( "P", "T" ) ) ) ||
			( in_array( $matches["all"][ strlen( $matches["all"] ) -1 ], array( "H", "S" ) ) && strpos( $matches["all"], "T" ) === false ) ||
			$plus
		)
		{
			throw XPath2Exception::withErrorCodeAndParams( "FORG0001", Resources::FORG0001, array( $text, "Duration" ) );
		}

		if ($sign || $plus )
		{
			$text = substr( $text, 1 );
		}

		$daysOnly = false;
		if ( $class == DayTimeDurationValue::$CLASSNAME )
		{
			// Can't have
			if ( ! empty( $matches['year'] ) ||
				 ! empty( $matches['month'] ) )
			{
				throw XPath2Exception::withErrorCodeAndParams( "FORG0001", Resources::FORG0001, array( $text, "DayTimeDuration" ) );
			}
		}
		else
		{
			$daysOnly = empty( $matches["year"] ) && empty( $matches["month"] );
		}

		$microseconds = empty( $matches['micro'] ) ? 0 : $matches['micro'];
		if ( $microseconds )
		{
			$text = str_replace( ".$microseconds", "", $text );
		}

		$di = new \DateInterval( $text );
		// $di->invert = $sign;

		// Record whether the duration has any time
		$hasValue = DurationValue::hasValue( $di );
		$type = $hasValue
			? ( $di->y || $di->m ? XPATH20_DURATION_TYPE_YEARMONTH : XPATH20_DURATION_TYPE_DAYTIME )
			: XPATH20_DURATION_TYPE_ANY;

		// Normalize the interval
		// $dt = new \DateTimeImmutable( "2016-12-31" );
		$dt = new \DateTimeImmutable( "now", new \DateTimeZone("UTC") );
		/**
		 * @var \DateInterval $diff
		 */
		$diff = $dt->add( $di )->diff( $dt );
		$diff->invert = $hasValue ? $sign : 0;
		$diff->hasValue = $hasValue;
		$diff->d = $di->d;
		$diff->microseconds = $microseconds;
		$diff->type = $type;
		$diff->daysOnly = $daysOnly;

		if ( is_null( $class ) ) $class = __CLASS__;
		$duration = new $class( $diff );
		// $duration->type = $type;
		return $duration;

        // return new DurationValue( $diff );
	}

	/**
	 * ValueAs
	 * @param SequenceType $type
	 * @param XmlNamespaceManager $nsmgr
	 * @return object
	 */
	public function ValueAs( $type, $nsmgr )
	{
	    switch ( $type->TypeCode )
	    {
	        case XmlTypeCode::AnyAtomicType:
	        case XmlTypeCode::Duration:

	            return $this;

	        case XmlTypeCode::YearMonthDuration:

	            return new YearMonthDurationValue( $this->Value );

	        case XmlTypeCode::DayTimeDuration:

	            return new DayTimeDurationValue( $this->Value );

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
		$duration1Hour = DurationValue::Parse("-PT1H1.1S");

		$durationZero = DurationValue::Parse( $duration1Hour->ZeroStringValue() );
		$result = $durationZero->getIsZero();

		$result = $duration1Hour->getIsZero();
		$result = $duration1Hour->getYears();
		$result = $duration1Hour->getMonths();
		$result = $duration1Hour->getDays();
		$result = $duration1Hour->getHours();
		$result = $duration1Hour->getSeconds();
		$result = $duration1Hour->getMilliseconds();
		$result = $duration1Hour->getTotalSeconds();
		$result = $duration1Hour->getTotalDays();
		$result = $duration1Hour->getTotalMonths();
		$result = $duration1Hour->ToString();

		$result = $duration1Hour->Equals( $durationZero );
		$result = $duration1Hour->Equals( $duration1Hour );
		$result = $duration1Hour->Equals( DurationValue::Parse("-PT1H1.1S") );

		$type = SequenceType::WithTypeCode( XmlTypeCode::AnyAtomicType );

		$result = $duration1Hour->ValueAs( $type, null );

		$result = $duration1Hour->Add( $duration1Hour );
		$result = $duration1Hour->Sub( DurationValue::Parse("-PT2H2.2S") );
	}

}

?>
