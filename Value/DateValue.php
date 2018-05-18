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

use lyquidity\xml\MS\XmlTypeCode;
use lyquidity\XPath2\SequenceType;
use lyquidity\XPath2\Properties\Resources;
use lyquidity\XPath2\DOM\XmlSchema;
use lyquidity\xml\interfaces\IXmlSchemaType;
use lyquidity\XPath2\CoreFuncs;
use lyquidity\xml\exceptions\InvalidCastException;
use lyquidity\xml\exceptions\ArgumentOutOfRangeException;
use lyquidity\XPath2\XPath2Exception;

/**
 * DateValue (public)
 */
class DateValue extends DateTimeValueBase implements IXmlConvertable, IXmlSchemaType
{
	public static $CLASSNAME = "lyquidity\XPath2\Value\DateValue";

	/**
	 * @var int ProxyValueCode = 13
	 */
	const ProxyValueCode = 13;

	/**
	 * Constructor
	 * @param bool $sign
	 * @param DateTime $value
	 */
	public function __construct( $sign, $value, $notLocal = false )
	{
		// parent::__construct($sign,$value );
		$this->fromDateBase( $sign, $value, $notLocal );
		$this->Value->setTime( 0, 0, 0 );
		$this->Value->isDate = true;
		$this->Value->isTime = false;
	}

	/**
	 * @param bool $sign
	 * @param \DateTime $value
	 * @return DateTimeValue
	 */
	public static function fromDate( $sign, $value, $notLocal = false )
	{
		/**
		 * @var DateTimeValue $result
		 */
		$result = new DateValue( $sign, $value, $notLocal );
		return $result;
	}

	/**
	 * Returns a schema type for the proxy value
	 * @return XmlSchemaType
	 */
	public function getSchemaType()
	{
		return XmlSchema::$Date;
	}

	/**
	 * Returns the contained value
	 * @return DateValue
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
		if ( ! $obj instanceof DateValue )
		{
			return false;
		}

		/**
		 * @var DateTimeValueBase $other
		 */
		$other = $obj;
		return $this->Value == $other->Value;
		// return $this->ToString( null ) == $other->ToString( null );
	}

	/**
	 * ToString
	 * @return string
	 */
	public function ToString( $provider = null )
	{
		$format = "";
		if ( $this->S ) $format .= "-";
		$format .= "Y-m-d";

		if ( ! empty( $this->Value->microseconds ) )
		{
			// $format .= str_pad( substr( "." . $this->Value->microseconds, 0, 4 ), 4, "0", STR_PAD_RIGHT );
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
	 * Parse
	 * @param string $text
	 * @return DateValue
	 */
	public static function Parse( $text )
	{
		// $text = trim( $text );
		$text = CoreFuncs::NormalizeSpace( $text );

		// $pattern = "^(?<sign>-?(?=[0-9]))(?<date>(?<year>\d{2,4})-(?<month>\d{1,2}(?![0-9]))-(?<day>\d{1,2}(?![0-9])))?T?((?<time>(?<hour>[0-9]{1,2})(:(?<minute>[0-9]{1,2}))?(:(?<second>[0-9]{1,2}))?)(\.(?<micro>[0-9]+))?)?(?<offset>(?=[+\-a-zA-Z]).*)?$";
		$pattern = "^(?<sign>-?)(?<date>(?<year>\d{4,4})-(?<month>\d{2,2}(?![0-9]))-(?<day>\d{2,2}(?![0-9])))(?<offset>(?=[+\-a-zA-Z])(([+\-]\d{2}(:\d{2}))|Z|((\?!-|\\+)(?i)[^0-9].{3,})))?$";

		$result = ! empty( $text ) && preg_match( "/$pattern/", $text, $matches );
		$error = ! $result ||
				 ( ! empty( $matches['offset'] ) && $matches['offset'] == $text ) ||
				 ( ! empty( $matches['month'] )  && ( $matches['month']  < 1 || $matches['month'] > 12 ) ) ||
				 ( ! empty( $matches['month'] )  && ( $matches['day']    < 1 || $matches['day'] > 32 ) ) ||
				 ( ! empty( $matches['date'] )   && ! checkdate( $matches['month'], $matches['day'], $matches['year'] ) );

		if ( ! empty( $matches['time'] ) )
		{
			// Make sure the
		}

		if ( ! $error && ! empty( $matches['offset'] ) && preg_match( "/^[+\-](?<hours>\d{2})(:(?<minutes>\d{2}))?$/", $matches['offset'], $offsetMatches ) )
		{
			$error = ( ! empty( $offsetMatches['hours'] ) && $offsetMatches['hours'] > 14 ) ||
					   empty( $offsetMatches['minutes'] ) || $offsetMatches['minutes'] > 59;
		}

		if ( $error )
		{
			throw XPath2Exception::withErrorCodeAndParams( "FORG0001", Resources::FORG0001, array( $text, "Date" ) );
		}

		$sign = empty( $matches['sign'] ) ? false : $matches['sign'] == "-" ;
		$date = empty( $matches['date'] ) ? "" : $matches['date'];
		$time = empty( $matches['time'] ) ? "00:00:00" : $matches['time'];
		$offset = empty( $matches['offset'] ) ? "" : $matches['offset'];

		$microseconds = empty( $matches['micro'] ) ? 0 : $matches['micro'];
		$notlocal = ! empty( $matches['offset'] );

		/**
		 * @var DateTime $dateTime
		 */
		$dateTime = new \DateTime( "{$date}" . ( $time ? "T$time" : "" ) . $offset );
		$dateTime->microseconds = $microseconds;

		return new DateValue( $sign, $dateTime, $notlocal );
	}

	/**
	 * Add
	 * @param DateValue $dat
	 * @param YearMonthDurationValue $duration
	 * @return DateValue
	 */
	public static function AddYearMonthDuration( $dat, $duration )
	{
		try
		{
			if ( ! $dat instanceof DateValue )
				throw new \InvalidArgumentException("$dat");

	   		if ( ! $duration instanceof YearMonthDurationValue )
				throw new \InvalidArgumentException("$duration");

			// Shortcut in the event there is no duration
			if ( ! $duration->Value->hasValue ) return $dat;

			// Calculate the date according to this algorithm: https://www.w3.org/TR/xmlschema-2/#d0e11648
			// Which is Appendix E of XML Schema Part 2: Datatypes Second Edition
			$dt = $dat->Value;
			$year = $dat->S ? -$dt->format("Y") : $dt->format("Y") - 1;
			$m = ( $dt->format("m") - 1) + ( $duration->Value->invert ? -1 : 1 ) * $duration->Value->m;
			$year = $year + ( $duration->Value->invert ? -1 : 1 ) * $duration->Value->y + intval( $m / 12 );

			if ( $year >= 0)
				$year = $year + 1;

			$m = $m % 12;
			if ( $m < 0 )
			{
				$m += 12;
				$year -= 1;
			}
			$m++;

			$day = min( $dt->format("d"), cal_days_in_month( 1, $m, abs( $year) ) );
			$dateString = sprintf( "%04s-%02s-%02sT%02s:%02s:%02s%s", ( $year >= 0 ? 1 : -1 ) * $year, $m, $day, $dt->format("h"), $dt->format("i"), $dt->format("s"), ( $dat->IsLocal ? "" : $dt->getTimezone()->getName() ) );
			$dt = new \DateTime( $dateString );

			return DateValue::fromDate( $year < 0, $dt, ! $dat->IsLocal );

			return $dat->Value->add( $duration->Value );
		}
		catch( ArgumentOutOfRangeException $ex )
		{
			throw XPath2Exception::withErrorCode( "FODT0001", Resources::FODT0001 );
		}
	}

	/**
	 * Add
	 * @param DateValue $dat
	 * @param DayTimeDurationValue $duration
	 * @return DateValue
	 */
	public static function AddDayTimeDuration( $dat, $duration )
	{
		try
		{
			if ( ! $dat instanceof DateValue )
				throw new \InvalidArgumentException("$dat");

	   		if ( ! $duration instanceof DayTimeDurationValue && ! $duration instanceof YearMonthDurationValue )
				throw new \InvalidArgumentException("$duration");

			// Shortcut for when there is no duration
			if ( ! $duration->Value->hasValue ) return $dat;

			$dt = $dat->Value;

			// This is a convoluted way to add dates.  But its necessary because the
			// PHP DateTime call cannot handle negative years and, so, all not the
			// weirdness that happens around year zero.  So instead of relying on the
			// ability of the DataTime class to handle negative year these lines convert
			// the date to the corresponding julian days and perform maths on this number
			// of days then converting the result back to a gregorian date.  Julian dates
			// rooted in 4713BC so maths on dates at the turn of the gregorian calendar
			// are 5000 years in the future.
			$durationSeconds = $duration->getTotalSeconds();
			$julianDay = gregoriantojd( $dt->format("m"), $dt->format("d"), ( $dat->S ? -1 : 1 ) * $dt->format("Y") );
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

			$gregorianDate = jdtogregorian( $newDay );
			if ( ! preg_match( "!^(?<month>\d+?)/(?<day>\d+?)/(?<sign>-?)(?<year>\d+?)$!", $gregorianDate, $matches ) )
				throw new \UnexpectedValueException();

			$sign = ! empty( $matches['sign'] );

			$gregorianDate = sprintf( "%04s-%02s-%02sT%02d:%02d:%02d.%d", $matches['year'], $matches['month'], $matches['day'], $newHour, $newMinute, $newSecond, $newMicro );
			$newDate = new \DateTime( $gregorianDate, $dat->IsLocal ? null : $dt->getTimezone() );
			$newDate->microseconds = $newMicro;
			return DateValue::fromDate( $sign, $newDate, ! $dat->IsLocal );

			return $dat->Value->add( $duration->Value );
		}
		catch( ArgumentOutOfRangeException $ex )
		{
			throw XPath2Exception::withErrorCode( "FODT0001", Resources::FODT0001 );
		}
	}

	/**
	 * Sub
	 * @param DateValue $dat1
	 * @param DateValue $dat2
	 * @return DayTimeDurationValue
	 */
	public static function Sub( $dat1, $dat2 )
	{
		try
		{
			if ( ! $dat1 instanceof DateValue )
				throw new \InvalidArgumentException("$dat1");

			if ( ! $dat2 instanceof DateValue )
				throw new \InvalidArgumentException("$dat2");

			$diff = $dat1->Value->diff( $dat2->Value );
			// Is this necessary?  It is if the 'Sub' means dat1 - dat2
			$diff->invert = $diff->days == 0 ? false : ! $diff->invert;
			$diff->hasValue = DurationValue::hasValue( $diff );
			$diff->microseconds = "";
			// $diff->daysOnly = false;  daysOnly is used to control how whether the 'toString' rendering uses days or the whole date

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
		switch ( $type->TypeCode)
		{
			case XmlTypeCode::AnyAtomicType:
			case XmlTypeCode::Date:

				return $this;

			case XmlTypeCode::DateTime:

				$timezone = $this->Value->getTimezone()->getName();
				return DateTimeValue::fromDate( $this->S, new \DateTime( "{$this->Value->format("Y")}-{$this->Value->format("m")}-{$this->Value->format("d")}$timezone" ) );

			case XmlTypeCode::GYear:

				return $this->ToGYear();

			case XmlTypeCode::GYearMonth:

				return $this->ToGYearMonth();

			case XmlTypeCode::GMonth:

				return $this->ToGMonth();

			case XmlTypeCode::GMonthDay:

				return $this->ToGMonthDay();

			case XmlTypeCode::GDay:

				return $this->ToGDay();

			case XmlTypeCode::String:

				return $this->ToString();

			case XmlTypeCode::UntypedAtomic:

				return new UntypedAtomic( $this->ToString() );

			default:
				throw new InvalidCastException();
		}
	}

	public static function tests()
	{
		$dateTime1 = DateValue::fromDate( false, new \DateTime( "2018-07-05T17:01:20-02:00" ) );
		$dateTime2 = DateValue::fromDate( false, new \DateTime( "2017-07-05T17:01:20-02:00" ) );
		$dateTime3 = DateValue::Parse("2017-07-05T17:01:20-02:00");
		echo $dateTime3->ToString();

		$type = SequenceType::WithTypeCode( XmlTypeCode::DateTime );
		$value = $dateTime3->ValueAs( $type, null );

		$result = $dateTime1->Equals( $dateTime1 );
		$result = $dateTime2->Equals( $dateTime3 );

		$result = $dateTime2->CompareTo( $dateTime3 );
		$result = $dateTime1->GetTypeCode();

		$result = $dateTime1->ToGYearMonth();
		$result = $dateTime1->ToGYear();
		$result = $dateTime1->ToGDay();
		$result = $dateTime1->ToGMonth();
		$result = $dateTime1->ToGMonthDay();

		$provider = null;
		$result = $dateTime1->ToBoolean( $provider );
		$result = $dateTime1->ToByte( $provider );
		$result = $dateTime1->ToChar( $provider );
		$result = $dateTime1->ToDateTime( $provider );
		$result = $dateTime1->ToDecimal( $provider );
		$result = $dateTime1->ToDouble( $provider );
		$result = $dateTime1->ToInt16( $provider );
		$result = $dateTime1->ToInt( $provider );
		$result = $dateTime1->ToInt32( $provider );
		$result = $dateTime1->ToInt64( $provider );
		$result = $dateTime1->ToSByte( $provider );
		$result = $dateTime1->ToSingle( $provider );
		$result = $dateTime1->ToString();
		$type = SequenceType::WithTypeCode( XmlTypeCode::DateTime )->ItemType;
		$result = $dateTime1->ToType( $type, $provider );
		$result = $dateTime1->ToUInt16( $provider );
		$result = $dateTime1->ToUInt32( $provider );
		$result = $dateTime1->ToUInt64( $provider );

		$x = DateTimeValue::AddDayTimeDuration( $dateTime1, DayTimeDurationValue::Parse( "P1D" ) );
		$x = DateTimeValue::AddYearMonthDuration( $dateTime1, YearMonthDurationValue::Parse( "P1Y" ) );

	}
}



?>
