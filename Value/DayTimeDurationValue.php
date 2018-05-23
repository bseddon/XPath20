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

use lyquidity\xml\interfaces\IComparable;
use lyquidity\XPath2\SequenceType;
use lyquidity\xml\MS\XmlTypeCode;
use lyquidity\XPath2\Properties\Resources;
use lyquidity\XPath2\DOM\XmlSchema;
use lyquidity\xml\interfaces\IXmlSchemaType;
use lyquidity\xml\exceptions\ArgumentException;
use lyquidity\XPath2\XPath2Exception;

/**
 * DayTimeDurationValue (public)
 */
class DayTimeDurationValue extends DurationValue implements IComparable, IXmlSchemaType
{
	/**
	 * CLASSNAME
	 * @var string
	 */
	public static $CLASSNAME = "lyquidity\XPath2\Value\DayTimeDurationValue";

	/**
	 * @var int ProxyValueCode = 12
	 */
	const ProxyValueCode = 12;

	/**
	 * Constructor
	 * @param DateInterval $value
	 */
	public function __construct( $value )
	{
		$value->hasValue = DurationValue::hasValue( $value );
		$value->daysOnly = true;
		parent::__construct( $value );
	}


	/**
	 * Create a YearMonthDurationValue instance from a DurationValue or a DateInterval
	 * @param unknown $duration
	 */
	public static function FromDuration( $duration )
	{
		if ( $duration instanceof DurationValue )
			$duration = $duration->getValue();

		$duration->y = 0;
		$duration->m = 0;
		$duration->days = 0;

		$dt = new \DateTimeImmutable();
		$diff = $dt->add( $duration )->diff( $dt );
		$diff->invert = $duration->invert;
		$diff->microseconds = $duration->microseconds;

		return new DayTimeDurationValue( $diff );
	}

	/**
	 * Returns a schema type for the proxy value
	 * @return XmlSchemaType
	 */
	public function getSchemaType()
	{
		return XmlSchema::$DayTimeDuration;
	}

	/**
	 * Returns the contained value
	 * @return DayTimeDurationValue
	 */
	public function getValue()
	{
		return $this->Value;
	}

	/**
	 * CompareTo
	 * @param object $obj
	 * @return int
	 */
	function CompareTo( $obj )
	{
		if ( ! $obj instanceof DayTimeDurationValue )
	        throw new ArgumentException("$obj");

		/**
		 * @var DayTimeDurationValue $other
		 */
	    $other = $obj;
	    /**
	     * @var DecimalValue $otherInterval
	     */
		$otherInterval = $other->getTotalSeconds();
		/**
		 * @var DecimalValue $valueInterval
		 */
		$valueInterval = $this->getTotalSeconds();

	    return $valueInterval->CompareTo( $otherInterval );
	}

	/**
	 * Returns one of XPATH20_DURATION_TYPE_ANY or XPATH20_DURATION_TYPE_DAYTIME
	 * @return int
	 */
	public function getType()
	{
		return $this->Value->hasValue ? XPATH20_DURATION_TYPE_DAYTIME : XPATH20_DURATION_TYPE_ANY;
	}

	/**
	 * Get the total number of seconds in the interval
	 * @return DecimalValue
	 */
	public function getTotalSeconds()
	{
		// Use the days value if it is available otherwise the months value will be used which gives
		// nonsense results when the duration has been created from a day interval because the
		// DateInterval assumes 30 days to every month!
		// BMS 2018-03-20 Changed to look at the 'daysOnly' property and use the 'd' value if it is.
		//				  On this date the parent::getTotalSeconds() function returns a value that is out by an hour
		//				  when returning the interval of this expression:
		//				  (xs:dateTime("1999-10-23T09:08:07Z") - xs:dateTime("1998-09-09T04:03:02Z"))
		//				  which appears in test 'op-subtract-dateTimes-yielding-DTD-19' in 'dateTimesSubtract.xml'
		//				  This interval is 408 days 22 hours 58 minutes and 59 seconds

		if ( ! $this->Value->days && ( $this->Value->y || $this->Value->m ) ) return parent::getTotalSeconds();
		// if ( ! $this->Value->days && ! $this->Value->daysOnly ) return parent::getTotalSeconds();

		$seconds = new DecimalValue( ( $this->Value->days ? $this->Value->days : $this->Value->d ) * 86400 + $this->Value->h * 3600 + $this->Value->i * 60 + $this->Value->s );
		$seconds = $seconds->Add( "0.{$this->Value->microseconds}" );
		if ( $this->Value->invert )
		{
			$seconds = $seconds->Mul( -1 );
		}

		return $seconds;
	}

	/**
	 * Parse
	 * @param string $text
	 * @return DayTimeDurationValue
	 */
	public static function Parse( $text )
	{
		return DurationValue::_parse( $text, __CLASS__ );
	}

	/**
	 * Subtract a duration from the current duration
	 * @param DurationValue $duration
	 */
	public function Add( $duration )
	{
		if ( ! $duration instanceof DurationValue )
		{
			throw new \InvalidArgumentException();
		}

		// $durationFactor = $duration->Value->invert ? -1 : 1;
		// $thisFactor = $this->Value->invert ? -1 : 1;

		// $thisSeconds = new DecimalValue( $this->Value->days * 86400 + $this->Value->h * 3600 + $this->Value->i * 60 + $this->Value->s );
		// $thisSeconds = $thisSeconds->Add( "0." . $this->Value->microseconds );
		// $thisSeconds = $thisSeconds->Mul( $thisFactor );
		// $durationSeconds = new DecimalValue( $duration->Value->days * 86400 + $duration->Value->h * 3600 + $duration->Value->i * 60 + $duration->Value->s );
		// $durationSeconds = $durationSeconds->Add( "0." . $duration->microseconds );
		// $durationSeconds = $thisSeconds->Mul( $durationFactor );

		$thisSeconds = $this->getTotalSeconds();
		$durationSeconds = $duration->getTotalSeconds();

		/**
		 * @var DecimalValue $interval
		 */
		$interval = $thisSeconds->Add( $durationSeconds );
		$hasValue = $interval->CompareTo( 0 ) != 0;
		$invert = 0 > $interval->CompareTo( 0 ) == -1;
		$interval = $interval->getAbs();

		$days = $interval->Div( 86400 )->getIntegerPart();
		$interval = $interval->Sub( $days * 86400 );
		$h = $interval->Div( 3600 )->getIntegerPart();
		$interval = $interval->Sub( $h * 3600 );
		$i = $interval->Div( 60 )->getIntegerPart();
		$interval->Sub( $i * 60 );
		$interval = $interval->Sub( $i * 60 );
		$s = $interval->getIntegerPart();
		$microseconds = $interval->getDecimalPart();

		$specString = "P%02sDT%02sH%02sM%02sS";
		$interval = sprintf( $specString, $days, $h, $i, $s );

		$di = new \DateInterval( $interval );
		$di->hasValue = $hasValue;
		$di->invert = $invert;
		$di->microseconds = $microseconds;
		$this->Value = $di;

		// if ( $hasValue )
		// {
		// 	$this->type =  XPATH20_DURATION_TYPE_DAYTIME;
        //
		// }

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
	}

	/**
	 * Multiply
	 * @param DayTimeDurationValue $a
	 * @param double $b
	 * @return DayTimeDurationValue
	 */
	public static function Multiply( $a, $b )
	{
		if ( ! is_numeric( $b ) )
			throw new \InvalidArgumentException( "Division denominator is not a number" );

		if ( is_nan( $b ) )
	        throw XPath2Exception::withErrorCode( "FOCA0005", Resources::FOCA0005 );

        if ( $b == INF || $b == -INF )
        	throw XPath2Exception::withErrorCode( "FODT0002", Resources::FODT0002 );

		if ( ! $a instanceof DayTimeDurationValue )
	        throw new ArgumentException("$a");

		// Create a new interval with the multiplied days. ->days is created by the ->diff function to hold the total days.
		$multipliedSeconds = $a->getTotalSeconds()->Mul( $b )->getRound(3);
		$multipliedInterval = new \DateInterval("P0D");
		$multipliedInterval->s = $multipliedSeconds->getIntegerPart();
		$microseconds = $multipliedSeconds->getDecimalPart() + 0;
		$sign = $multipliedSeconds->getIsNegative();

		// Finally convert $multipliedInterval into a normalized interval
		$dt = new \DateTimeImmutable();
		$tz = new \DateTimeZone("Z");
		$dt = $dt->setTimezone( $tz );
		$diff = $dt->add( $multipliedInterval )->diff( $dt );
		$diff->hasValue = $multipliedSeconds->getValue() != 0;
		$diff->invert = ! $diff->invert;
		$diff->microseconds = $microseconds;
		// $diff->daysOnly = false;

	    return new DayTimeDurationValue( $diff );
	}

	/**
	 * Divide
	 * @param DayTimeDurationValue $a
	 * @param double $b
	 * @return DayTimeDurationValue
	 */
	public static function Divide( $a, $b )
	{
		if ( ! is_numeric( $b ) )
			throw new \InvalidArgumentException( "Division denominator is not a number" );

	    if ( is_nan( $b ) )
	        throw XPath2Exception::withErrorCode( "FOCA0005", Resources::FOCA0005 );

        // if ( $b == INF || $b == -INF )
        //	throw XPath2Exception::withErrorCode( "FOCA0005", Resources::FOCA0005 );

	    if ( $b == 0.0 )
	        throw XPath2Exception::withErrorCode( "FODT0002", Resources::FODT0002 );

		if ( ! $a instanceof DayTimeDurationValue )
	        throw new ArgumentException("$a");

		// Create a new interval with the multiplied days. ->days is created by the ->diff function to hold the total days.
		$dividedSeconds = doubleval( $a->getTotalSeconds()->getValue() / $b );
		$dividedInterval = new \DateInterval("P0D");
		if ( round( $dividedSeconds ) == 0 )
		{
			$dividedInterval->hasValue = false;
			$dividedInterval->microseconds = 0;
			return new DayTimeDurationValue( $dividedInterval );
		}

		$dividedInterval->s = intval( $dividedSeconds );
		$microseconds = round( $dividedSeconds - $dividedInterval->s, 3 ) * 1000;

		// Finally convert $multipliedInterval into a normalized interval
		$dt = new \DateTimeImmutable();
		$tz = new \DateTimeZone("Z");
		$dt = $dt->setTimezone( $tz );
		$diff = $dt->add( $dividedInterval )->diff( $dt );
		$diff->hasValue = $dividedSeconds != 0;
		$diff->invert = ! $diff->invert;
		$diff->microseconds = $microseconds;

	    return new DayTimeDurationValue( $diff );
	}

	/**
	 * DivideDurations
	 * @param DayTimeDurationValue $a
	 * @param DayTimeDurationValue $b
	 * @return DecimalValue
	 */
	public static function DivideDurations( $a, $b )
	{
		if ( ! $a instanceof DayTimeDurationValue )
			throw new ArgumentException("$a");

		if ( ! $b instanceof DayTimeDurationValue )
			throw new ArgumentException("$b");

		$numerator = $a->getTotalSeconds();
		$denominator = $b->getTotalSeconds();

		return $numerator->Div( $denominator, 21 );
	}

	/**
	 * ToString
	 * @return string
	 */
	public function ToString()
	{
		// Reading all non-zero date parts.
		$date = array_filter(array(
			'D' => @isset( $this->Value->daysOnly ) && $this->Value->daysOnly && $this->Value->days ? $this->Value->days : $this->Value->d
			// 'D' => $this->Value->days
		));

		$microseconds = "";
		if ( ! @empty( $this->Value->microseconds ) && ! empty( rtrim( $this->Value->microseconds, "0" ) ) )
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
			$specString .= $value . $key;
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
	 * Tests
	 */
	public static function tests()
	{
		$duration = DurationValue::Parse( "P1DT1H1.1S" );
		$type = SequenceType::WithTypeCode( XmlTypeCode::DayTimeDuration );
		$dayDuration = $duration->ValueAs( $type, null );

		$multiple = DayTimeDurationValue::Multiply( $dayDuration, 2 );
		$days = $multiple->getDays();
		$hours = $multiple->getHours();

		$divide = DayTimeDurationValue::Divide( $multiple, 2 );
		$days = $divide->getDays();
		$hours = $divide->getHours();

		$result = DayTimeDurationValue::DivideDurations( $multiple, $divide );
	}

}



?>
