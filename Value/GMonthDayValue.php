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

use lyquidity\XPath2\Properties\Resources;
use lyquidity\XPath2\DOM\XmlSchema;
use lyquidity\xml\MS\XmlSchemaType;
use lyquidity\xml\interfaces\IFormatProvider;
use lyquidity\xml\interfaces\IXmlSchemaType;
use lyquidity\XPath2\XPath2Exception;

/**
 * GMonthDayValue (public)
 */
class GMonthDayValue extends DateTimeValueBase implements IXmlSchemaType
{
	/**
	 * CLASSNAME
	 * @var string
	 */
	public static $CLASSNAME = "lyquidity\XPath2\Value\GMonthDayValue";

	/**
	 * Constructor
	 * @param \DateTime $value
	 */
	public function __construct( $value )
	{
		// parent::__construct(,$value);
		$this->fromDateBase( false, $value );
	}

	/**
	 * Returns a schema type for the proxy value
	 * @return XmlSchemaType
	 */
	public function getSchemaType()
	{
		return XmlSchema::$GMonthDay;
	}

	/**
	 * Returns the contained value
	 * @return GMonthDayValue
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
		if ( ! $obj instanceof GMonthDayValue )
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
	 * @param IFormatProvider $provider
	 * @return string
	 */
	public function ToString( $provider = null )
	{
		$result = "";
		if ( $this->S ) $result .= "-";
		if ( $this->IsLocal )
			$result .= $this->Value->format("--m-d");
		else
			if ( $this->Value->getOffset() == 0 )
				$result .= $this->Value->format("--m-d\Z");
			else
				$result .= $this->Value->format("--m-dP");

		return $result;
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
	 * @return GMonthDayValue
	 */
	public static function Parse($text)
	{
		$text = strtoupper( trim( $text ) );

		$result = preg_match( "/^--(?<month>\d{1,2})-(?<day>\d{1,2})(?<offset>(?=[+\-a-zA-Z])(([+\-]\d{2}(:\d{2}))|Z|((\?!-|\\+)(?i)[^0-9].{3,})))?$/i", $text, $matches );
		if ( ! $result )
		{
			throw XPath2Exception::withErrorCodeAndParams( "FORG0001", Resources::FORG0001, array( $text, "xs:gYear" ) );
		}

		$error =	empty( $matches['month'] ) || $matches['month'] > 12 || $matches['month'] < 1 ||
					empty( $matches['day'] ) || $matches['day'] > 31 || $matches['day'] < 1;

		$offsetMatches = null;
		if ( ! $error && ! empty( $matches['offset'] ) )
		{
			$error = $matches['offset'] != "Z" &&
			! in_array( $matches['offset'], timezone_identifiers_list() ) &&
			! preg_match( "/^[+-](?<hours>\d{2})(:(?<minutes>\d{2}))?$/", $matches['offset'], $offsetMatches );

			if ( ! $error && ! is_null( $offsetMatches ) )
			{
				$error = empty( $offsetMatches['hours'] ) || $offsetMatches['hours'] > 14 ||
				empty( $offsetMatches['minutes'] ) || $offsetMatches['minutes'] > 59;
			}

		}

		if ( $error )
		{
			throw XPath2Exception::withErrorCodeAndParams( "FORG0001", Resources::FORG0001, array( $text, "xs:gMonthDay" ) );
		}

		// Use 1972 because its a leap year so 02-29 will work OK
		$text = "1972" . substr( $text, 1 );

		$dateTime = \DateTime::createFromFormat( "!Y-m-dO", $text );
		if ( ! $dateTime )
		{
			$dateTime = \DateTime::createFromFormat( "!Y-m-d\Z", $text );
			if ( ! $dateTime )
			{
				$dateTime = \DateTime::createFromFormat( "!Y-m-d", $text );
			}
		}

		if ( ! $dateTime || $dateTime->format('m') != $matches['month'] || $dateTime->format('d') != $matches['day'] )
		{
			throw XPath2Exception::withErrorCodeAndParams( "FORG0001", Resources::InvalidFormat, array( $text, "xs:gMonthDay" ) );
		}

		return new GMonthDayValue( $dateTime );
	}

	/**
	 * Unit tests
	 */
	public static function tests()
	{
		$gMonthDay = GMonthDayValue::Parse( "--02-03Z" );
		echo "{$gMonthDay->ToString()}\n";
	}

}



?>
