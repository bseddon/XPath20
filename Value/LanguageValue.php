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

use lyquidity\XPath2\DOM\XmlSchema;
use lyquidity\xml\interfaces\IXmlSchemaType;
use lyquidity\XPath2\Properties\Resources;
use lyquidity\XPath2\XPath2Exception;

/**
 * LanguageValue (public)
 */
class LanguageValue implements IXmlSchemaType
{
	public static $CLASSNAME = "lyquidity\XPath2\Value\LanguageValue";

	/**
	 * Constructor
	 * @param string $value
	 */
	public  function __construct( $value )
	{
		if ( ! preg_match( "/^[a-zA-Z]{1,8}(-[a-zA-Z0-9]{1,8})*$/", $value, $matches ) )
		{
			throw XPath2Exception::withErrorCodeAndParams( "FORG0001", Resources::FORG0001, array( $value, "xs:language" ) );
		}
		$this->Value = trim( $value );
	}

	/**
	 * @var array $Value
	 */
	public $Value;

	/**
	 * Returns a schema type for the proxy value
	 * @return XmlSchemaType
	 */
	public function getSchemaType()
	{
		return XmlSchema::$Language;
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
	public function Equals($obj)
	{
		if ( ! $obj instanceof LanguageValue )
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

	public static function tests()
	{
	}

}



?>
