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
use lyquidity\xml\MS\XmlNamespaceManager;
use lyquidity\xml\MS\XmlSchemaType;
use lyquidity\xml\MS\XmlTypeCode;
use lyquidity\XPath2\DOM\XmlSchema;
use lyquidity\xml\interfaces\IXmlSchemaType;
use lyquidity\XPath2\Properties\Resources;
use lyquidity\xml\schema\SchemaTypes;
use lyquidity\xml\exceptions\InvalidCastException;
use lyquidity\XPath2\SequenceType;
use lyquidity\XPath2\XPath2Exception;
use lyquidity\XPath2\NullReferenceException;

/**
 * Base64BinaryValue (public)
 */
class Base64BinaryValue implements IXmlConvertable, IXmlSchemaType
{
	/**
	 * CLASSNAME
	 * @var string
	 */
	public static $CLASSNAME = "lyquidity\XPath2\Value\Base64BinaryValue";

	/**
	 * Constructor
	 * @param array $binaryValue
	 */
	public function __construct( $binaryValue )
	{
		if ( is_null( $binaryValue ) )
			throw new NullReferenceException();
		$this->BinaryValue = $binaryValue;
	}

	/**
	 * Constructor
	 * @param string $data
	 * @return Base64BinaryValue
	 */
	public static function fromString( $data )
	{
		// Remove all whitespace
		$data = preg_replace( "/\s/", "", $data );
		$doubleEquals = SchemaTypes::endsWith( $data, "==" );

		if (
			strpos( rtrim( $data, "=" ), "=" ) !== false || // Equals can only appear at the end
			substr_count( $data, "=" ) > 2 || // There can be no more than 2 equals characters
			strlen( $data ) % 4 != 0 || // The string length must be a multiple of 4
			preg_match( "/[^0-9a-z+\/=]/i", $data ) || // The characters must be valid
			( SchemaTypes::endsWith( $data, "==" ) && ! in_array( $data[ strlen( $data ) - 3 ], array( "A", "Q", "g", "w" ) ) )
		)
		{
			throw XPath2Exception::withErrorCodeAndParams( "FORG0001", Resources::FORG0001, array(
				$data,
				"xs:base64Binary",
			) );
		}

		return new Base64BinaryValue( base64_decode( $data ) );
	}

	/**
	 * BinaryValue
	 * @var string $BinaryValue
	 */
	public $BinaryValue;

	/**
	 * Returns a schema type for the proxy value
	 * @return XmlSchemaType
	 */
	public function getSchemaType()
	{
		return XmlSchema::$Base64Binary;
	}

	/**
	 * Returns the contained value
	 * @return Base64BinaryValue
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
		return base64_encode( $this->BinaryValue );
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
	 * @param object $obj
	 * @return bool
	 */
	public function Equals($obj)
	{
		if ( ! $obj instanceof Base64BinaryValue )
		{
			return false;
		}

		/**
		 * @var Base64BinaryValue $other
		 */
		$other = $obj;
		return strcmp( $this->BinaryValue, $other->BinaryValue ) == 0;
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
	        case XmlTypeCode::Base64Binary:
	            return $this;

	        case XmlTypeCode::String:
	            return $this->ToString();

	        case XmlTypeCode::UntypedAtomic:
	            return new UntypedAtomic( $this->ToString() );

	        case XmlTypeCode::HexBinary:
	            return new HexBinaryValue( $this->BinaryValue );

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
