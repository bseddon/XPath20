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

use lyquidity\XPath2\DOM\XmlSchema;
use lyquidity\xml\interfaces\IXmlSchemaType;
use lyquidity\XPath2\Properties\Resources;
use lyquidity\xml\schema\SchemaTypes;
use lyquidity\XPath2\XPath2Exception;
use lyquidity\xml\MS\XmlSchemaType;

/**
 * NameValue (public)
 */
class NameValue implements IXmlSchemaType
{
	/**
	 * CLASSNAME
	 * @var string
	 */
	public static $CLASSNAME = "lyquidity\XPath2\Value\NameValue";

	/**
	 * Pattern for the start character of a name
	 * @var string $nameStartChar
	 */
	public static $nameStartChar;
	/**
	 * Pattern for the character in the body of a name
	 * @var string $nameChar
	 */
	public static $nameChar;
	/**
	 * Pattern for the body of a name
	 * @var string $name
	 */
	public static $name;
	/**
	 * Pattern for the whole of a name
	 * @var string $ncName
	 */
	public static $ncName;

	/**
	 * Initialize patterns
	 */
	public static function __static()
	{
		// NameValue::$nameStartChar = "[:_\p{Ll}\p{Lu}\p{Lt}\p{Lo}\p{Nl}]";
		NameValue::$nameStartChar = SchemaTypes::$nameStartChar;
		NameValue::$nameChar = SchemaTypes::$nameChar;
		NameValue::$name = SchemaTypes::$name;
		NameValue::$ncName = SchemaTypes::$ncName;
	}

	/**
	 * Constructor
	 * @param array $value
	 */
	public  function __construct( $value )
	{
		$value = trim( $value );
		$pattern = "/^" . NameValue::$name . "$/u";
		if ( ! preg_match( $pattern, $value, $matches ) )
		{
			throw XPath2Exception::withErrorCodeAndParams( "FORG0001", Resources::FORG0001, array( $value, "xs:Name" ) );
		}
		$this->Value = trim( $value );
	}

	/**
	 * Value
	 * @var array $Value
	 */
	public $Value;

	/**
	 * Returns a schema type for the proxy value
	 * @return XmlSchemaType
	 */
	public function getSchemaType()
	{
		return XmlSchema::$Name;
	}

	/**
	 * Returns the contained value
	 * @return TokenValue
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
		if ( ! $obj instanceof NameValue )
		{
			return false;
		}

		/**
		 * @var TokenValue $other
		 */
		$other = $obj;
		return strcmp( $this->Value, $other->Value ) == 0;
	}

	/**
	 * ToString
	 * @return string
	 */
	public function ToString()
	{
		return $this->Value;
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
	 * Unit tests
	 */
	public static function tests()
	{
	}

}

NameValue::__static();

?>
