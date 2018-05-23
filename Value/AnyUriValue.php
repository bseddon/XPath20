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

use lyquidity\XPath2\SequenceType;
use lyquidity\xml\MS\XmlNamespaceManager;
use lyquidity\xml\MS\XmlTypeCode;
use lyquidity\xml\interfaces\IComparable;
use lyquidity\XPath2\DOM\XmlSchema;
use lyquidity\xml\interfaces\IXmlSchemaType;
use lyquidity\XPath2\Properties\Resources;
use lyquidity\xml\exceptions\InvalidCastException;
use lyquidity\xml\exceptions\InvalidOperationException;
use lyquidity\xml\exceptions\ArgumentException;
use lyquidity\XPath2\XPath2Exception;

/**
 * AnyUriValue (public)
 */
class AnyUriValue implements IXmlConvertable, IComparable, IXmlSchemaType
{
	/**
	 * CLASSNAME
	 * @var string
	 */
	public static $CLASSNAME = "lyquidity\XPath2\Value\AnyUriValue";

	/**
	 * Constructor
	 * @param string $value
	 */
	public function __construct( $value )
	{
		if ( is_null( $value ) )
			throw new InvalidOperationException();

		// Normalize the uri
		$this->Value = trim( preg_replace( "/\\s+/", " ", $value ) );

		// % cannot be followed by a char sequence unless they for a hex value.
		$result = preg_match( "/%[g-z][a-z%#]/i", $this->Value, $matches );

		// A valid uri cannot be only one of the control characters alone
		$result |= in_array( $this->Value, array( "%", "#", "?" ) );

		// The URI cannot start with a colon
		$result |= preg_match( "/^:/i", $this->Value, $matches );

		if ( $result )
		{
			throw XPath2Exception::withErrorCodeAndParams( "FORG0001", Resources::FORG0001, array( $value, "xs:anyUri" ) );
		}
	}

	/**
	 * Value
	 * @var string $Value
	 */
	public $Value;

	/**
	 * Returns a schema type for the proxy value
	 * @return XmlSchemaType
	 */
	public function getSchemaType()
	{
		return XmlSchema::$AnyUri;
	}

	/**
	 * Returns the contained value
	 * @return AnyUriValue
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
		if ( is_string( $obj ) )
		{
	        return $this->Value == $obj;
		}
		else if ( $obj instanceof AnyUriValue )
		{
			/**
			 * @var AnyUriValue $other
			 */
			$other = $obj;
	        return $this->Value == $other->Value;
		}

	    return false;
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
	        case XmlTypeCode::AnyUri:

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
	 * CompareTo
	 * @param object $obj
	 * @return int
	 */
	public function CompareTo( $obj )
	{
		if ( ! $obj instanceof AnyUriValue )
	        throw new ArgumentException();

		/**
		 * @var AnyUriValue $other
		 */
	    $other = $obj;
	    return strcmp( $this->Value, $other->Value );
	}

	/**
	 * Unit tests
	 */
	public static function tests()
	{
		try
		{
			$result = new AnyUriValue( null );
		}
		catch( \Exception $ex )
		{
			$class = get_class( $ex );
			echo "Error: $class {$ex->getMessage()}\n";
		}

		$result = new AnyUriValue( "Some uri" );
		echo "{$result->ToString()}\n";

		$result2 = new AnyUriValue( "Some other uri" );
		echo "{$result2->ToString()}\n";

		$equals = $result->Equals( $result );
		$equals = $result->Equals( $result2 );

		$compare = $result->CompareTo( $result2 );

		try
		{
			$value = $result->ValueAs( SequenceType::WithTypeCode( XmlTypeCode::AnyAtomicType ), null );
			$value = $result->ValueAs( SequenceType::WithTypeCode( XmlTypeCode::AnyUri ), null );
			$value = $result->ValueAs( SequenceType::WithTypeCode( XmlTypeCode::String ), null );
			$value = $result->ValueAs( SequenceType::WithTypeCode( XmlTypeCode::UntypedAtomic ), null );
			$value = $result->ValueAs( SequenceType::WithTypeCode( XmlTypeCode::Base64Binary ), null );  // Should throw an exception
		}
		catch( \Exception $ex )
		{
			$class = get_class( $ex );
			echo "Error: $class {$ex->getMessage()}\n";
		}
	}

}



?>
