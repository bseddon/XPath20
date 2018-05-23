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
use lyquidity\XPath2\DOM\XmlSchema;
use lyquidity\xml\interfaces\IXmlSchemaType;
use lyquidity\XPath2\Properties\Resources;
use lyquidity\xml\exceptions\InvalidCastException;
use lyquidity\XPath2\XPath2Exception;

/**
 * HexBinaryValue (public)
 */
class HexBinaryValue implements IXmlConvertable, IXmlSchemaType
{
	/**
	 * CLASSNAME
	 * @var string
	 */
	public static $CLASSNAME = "lyquidity\XPath2\Value\HexBinaryValue";

	/**
	 * Constructor
	 * @param array $binaryValue
	 */
	public  function __construct( $binaryValue )
	{
		if ( is_null( $binaryValue ) )
	        throw new \InvalidArgumentException();
		$this->BinaryValue = $binaryValue;
	}

	/**
	 * Convert a string representation of a hex value to is binary version and create a HexBinaryValue instance
	 * @param unknown $string
	 */
	public static function fromString( $string )
	{
		// Make sure the string does not contain non-hex characters
		$matched = preg_match( "/[^0-9a-fA-F]+/", $string, $matches );
		if ( $matched || strlen( $string ) % 2 != 0 )
		{
			throw XPath2Exception::withErrorCodeAndParams( "FORG0001", Resources::InvalidFormat, array( $string, "xs:hexBinary" ) );
		}

		$binary = hex2bin( $string );
		return new HexBinaryValue( $binary );
	}

	/**
	 * BinaryValue
	 * @var string $BinaryValue byte[]
	 */
	public $BinaryValue;

	/**
	 * Returns a schema type for the proxy value
	 * @return XmlSchemaType
	 */
	public function getSchemaType()
	{
		return XmlSchema::$HexBinary;
	}

	/**
	 * Returns the contained value
	 * @return HexBinaryValue
	 */
	public function getValue()
	{
		return $this->BinaryValue;
	}

	/**
	 * ToString
	 * @return string
	 */
	public function ToString()
	{
		return strtoupper( bin2hex( $this->BinaryValue ) );
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
	 * Equals
	 * @param HexBinaryValue $obj
	 * @return bool
	 */
	public function Equals( $obj )
	{
		if ( ! $obj instanceof HexBinaryValue )
		{
			return false;
		}

		return strcmp( $this->BinaryValue, $obj->BinaryValue ) == 0;
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
	        case XmlTypeCode::HexBinary:

	            return $this;

	        case XmlTypeCode::String:

	            return $this->ToString();

	        case XmlTypeCode::UntypedAtomic:

	            return new UntypedAtomic( $this->ToString() );

	        case XmlTypeCode::Base64Binary:

	            return new Base64BinaryValue( $this->BinaryValue );

	        default:

	            throw new InvalidCastException();
	    }
	}

	/**
	 * Unit tests
	 */
	public static function tests()
	{}
}

?>
