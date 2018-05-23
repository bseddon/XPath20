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

use lyquidity\xml\interfaces\IComparable;
use lyquidity\xml\MS\XmlTypeCode;
use lyquidity\XPath2\SequenceType;
use lyquidity\XPath2\Properties\Resources;
use lyquidity\XPath2\DOM\XmlSchema;
use lyquidity\xml\interfaces\IXmlSchemaType;
use lyquidity\xml\exceptions\ArgumentException;
use lyquidity\XPath2\XPath2Exception;

/**
 * YearMonthDurationValue (public)
 */
class YearMonthDurationValue extends DurationValue implements IComparable, IXmlSchemaType
{
	/**
	 * CLASSNAME
	 * @var string
	 */
	public static $CLASSNAME = "lyquidity\XPath2\Value\YearMonthDurationValue";

	/**
	 * @var int ProxyValueCode = 11
	 */
	const ProxyValueCode = 11;

	/**
	 * Constructor
	 * @param DateInterval $value
	 */
	public  function __construct( $value )
	{
		parent::__construct( $value );
		// if ( $this->Value->hasValue )
		// {
		// 	$this->type = XPATH20_DURATION_TYPE_YEARMONTH;
		// }
		$this->Value->microseconds = 0;
	}

	/**
	 * Create a YearMonthDurationValue instance from a DurationValue or a DateInterval
	 * @param unknown $duration
	 */
	public static function FromDuration( $duration )
	{
		if ( $duration instanceof DurationValue )
			$duration = $duration->getValue();

		// Correct the month count
		if ( $duration->d > 30 && $duration->m > 0 ) $duration->m -= 1;

		$duration->d = 0;
		$duration->h = 0;
		$duration->i = 0;
		$duration->s = 0;
		$duration->microseconds = 0;
		$duration->daysOnly = false;

		return new YearMonthDurationValue( $duration );
	}

	/**
	 * Returns a schema type for the proxy value
	 * @return XmlSchemaType
	 */
	public function getSchemaType()
	{
		return XmlSchema::$YearMonthDuration;
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
	 * ZeroStringValue
	 * @return string
	 */
	public function ZeroStringValue()
	{
		return "P0M";
	}

	/**
	 * CompareTo
	 * @param object $obj
	 * @return int
	 */
	function CompareTo( $obj )
	{
		if ( ! $obj instanceof YearMonthDurationValue )
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
	 * Returns one of XPATH20_DURATION_TYPE_ANY or XPATH20_DURATION_TYPE_YEARMONTH
	 * @return int
	 */
	public function getType()
	{
		return $this->Value->hasValue ? XPATH20_DURATION_TYPE_YEARMONTH : XPATH20_DURATION_TYPE_ANY;
	}

	/**
	 * Parse
	 * @param string $text The duration text to Parse
	 * @param string $class (optional) The name of the class type to create
	 * @return YearMonthDurationValue
	 */
	public static function Parse( $text, $class = NULL )
	{
		$text = trim( $text );

		$pattern = "^(?<plus>\+)?(?<sign>-)?(?<all>P((?<year>\d+)Y)?((?<month>\d+)M)?((?<day>\d+)D)?T?((?<hour>\d+)H)?((?<minute>\d+)M)?(((?<second>\d+)(\.(?<micro>\d*))?)S)?)?$";
		if ( empty( $text ) || ! preg_match( "/$pattern/", $text, $matches ) )
		{
			throw XPath2Exception::withErrorCodeAndParams( "FORG0001", Resources::FORG0001, array( $text, "Duration" ) );
		}

		$sign = empty( $matches['sign'] ) ? false : $matches['sign'] == "-" ;
		$plus = empty( $matches['plus'] ) ? false : $matches['plus'] == "+" ;

		if (
			in_array( $matches["all"][ strlen( $matches["all"] ) -1 ], array( "P", "T" ) ) ||
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

		if ( ! empty( $matches['day'] ) ||
			 ! empty( $matches['hour'] ) ||
			 ! empty( $matches['hour'] ) ||
			 ! empty( $matches['minute'] ) ||
			 ! empty( $matches['microseconds'] ))
		{
			throw XPath2Exception::withErrorCodeAndParams( "FORG0001", Resources::FORG0001, array( $text, "yearMonthDuration" ) );
		}

		$di = new \DateInterval( $text );
		$di->invert = $sign;

		// Normalize the interval.  It might be rational like 01Y02M  but it might be 01Y46M200D.
		// In this case the 46 will be converted into 03Y10M and the days will be dropped.
		$years = $di->y;
		$months = $di->m;

		if ( $months > 11 )
		{
			$years += intval( $months / 12 );
			$months = $months % 12;
		}

		$di = new \DateInterval( sprintf( "P%02sY%02sM", $years, $months ) );
		$di->hasValue = $di->m || $di->y;
		if ( $di->hasValue ) $di->invert = $sign;

		return new YearMonthDurationValue( $di );
	}

	/**
	 * DaysToMonth
	 * @param int $days
	 * @return int
	 */
	private static function DaysToMonth( $days )
	{
		return $days / 365 * 12 + $days % 365 / 30;
	}

	/**
	 * MonthToDays
	 * @param int $month
	 * @return int
	 */
	private static function MonthToDays( $month )
	{
		return $month / 12 * 365 + $month % 12 * 30;
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

		$totalMonths = $this->getTotalMonths() + $duration->getTotalMonths();
		$invert = 0 > $totalMonths;
		$totalMonths = abs( $totalMonths );
		$years = intval( $totalMonths / 12 );
		$months = $totalMonths % 12;

		$di = new \DateInterval( sprintf( "P%02sY%02sM", $years, $months ) );
		$di->hasValue = $di->y || $di->m;
		$di->invert = $invert;
		$di->microseconds = 0;

		$this->Value = $di;
		// $this->type = $this->Value->hasValue
		// 	? XPATH20_DURATION_TYPE_ANY
		// 	: XPATH20_DURATION_TYPE_YEARMONTH;
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
		$this->add( $duration );
	}

	/**
	 * Multiply
	 * @param YearMonthDurationValue $a
	 * @param double $b
	 * @return YearMonthDurationValue
	 */
	public static function Multiply( $a, $b )
	{
		if ( ! is_numeric( $b ) )
			throw new \InvalidArgumentException( "Division denominator is not a number" );

		if ( is_nan( $b ) )
			throw XPath2Exception::withErrorCode( "FOCA0005", Resources::FOCA0005 );

		if ( $b == INF || $b == -INF )
			throw XPath2Exception::withErrorCode( "FODT0002", Resources::FODT0002 );

		if ( ! $a instanceof YearMonthDurationValue )
			throw new ArgumentException("$a");

		// Create a new interval with the multiplied days. ->days is created by the ->diff function to hold the total days.
		$totalMonths = floor( 0.5 + ( $a->getTotalMonths() * $b ) );
		$invert = 0 > $totalMonths;
		$totalMonths = abs( $totalMonths );
		$years = intval( $totalMonths / 12 );
		$months = $totalMonths % 12;

		$di = new \DateInterval( sprintf( "P%02sY%02sM", $years, $months ) );
		$di->hasValue = $totalMonths != 0;
		$di->invert = $invert;
		$di->microseconds = 0;

		return new YearMonthDurationValue( $di );
	}

	/**
	 * Divide
	 * @param YearMonthDurationValue $a
	 * @param double $b
	 * @return YearMonthDurationValue
	 */
	public static function Divide( $a, $b )
	{
		if ( ! is_numeric( $b ) )
			throw new \InvalidArgumentException( "Division denominator is not a number" );

		if ( is_nan( $b ) )
			throw XPath2Exception::withErrorCode( "FOCA0005", Resources::FOCA0005 );

		// if ( $b == INF || $b == -INF )
		//	throw XPath2Exception::withErrorCode( "FOCA0002", Resources::FOCA0002 );

		if ( $b == 0.0 )
			throw XPath2Exception::withErrorCode( "FODT0002", Resources::FODT0002 );

		if ( ! $a instanceof YearMonthDurationValue )
			throw new ArgumentException("$a");

		$totalMonths = floor( 0.5 + ( $a->getTotalMonths() / $b ) );
		$invert = 0 > $totalMonths;
		$totalMonths = abs( $totalMonths );
		$years = intval( $totalMonths / 12 );
		$months = $totalMonths % 12;

		$di = new \DateInterval( sprintf( "P%02sY%02sM", $years, $months ) );
		$di->hasValue = $totalMonths != 0;
		$di->invert = $invert;
		$di->microseconds = 0;

		return new YearMonthDurationValue( $di );

		// // Create a new interval with the multiplied days. ->days is created by the ->diff function to hold the total days.
		// $dividedInterval = new \DateInterval("P0D");
		// $dividedInterval->m = floor( 0.5 + ( $a->getTotalMonths() / $b ) );

		// // Finally convert $multipliedInterval into a normalized interval
		// $dt = new \DateTimeImmutable();
		// $diff = $dt->add( $dividedInterval )->diff( $dt );
		// $diff->invert = ! $diff->invert;

		// return new YearMonthDurationValue( $diff );
	}

	/**
	 * DivideDurations
	 * @param YearMonthDurationValue $a
	 * @param YearMonthDurationValue $b
	 * @return DecimalValue
	 */
	public static function DivideDurations( $a, $b )
	{
		if ( ! $a instanceof YearMonthDurationValue )
			throw new ArgumentException("$a");

		if ( ! $b instanceof YearMonthDurationValue )
			throw new ArgumentException("$b");

		$numerator = new DecimalValue( $a->getTotalMonths() );
		$denominator = new DecimalValue( $b->getTotalMonths() );

		return $numerator->Div( $denominator ); //->Add( new DecimalProxy( 0.5 ) )->getFloor();
	}

	/**
	 * Unit tests
	 */
	public static function tests()
	{
		$duration = DurationValue::Parse( "P1Y1M" );
		$type = SequenceType::WithTypeCode( XmlTypeCode::YearMonthDuration );
		$yearDuration = $duration->ValueAs( $type, null );

		$multiple = YearMonthDurationValue::Multiply( $yearDuration, 2 );
		$days = $multiple->getDays();
		$hours = $multiple->getyears();
		$years = $multiple->getMonths();

		$divide = YearMonthDurationValue::Divide( $multiple, 2 );
		$days = $divide->getDays();
		$hours = $divide->getyears();
		$years = $divide->getMonths();

		$result = YearMonthDurationValue::DivideDurations( $multiple, $divide );
	}

}

?>
