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
use lyquidity\xml\MS\XmlTypeCode;
use lyquidity\XPath2\SequenceType;
use lyquidity\XPath2\DOM\XmlSchema;
use lyquidity\xml\interfaces\IXmlSchemaType;
use lyquidity\xml\exceptions\InvalidCastException;

/**
 * NotationValue (public)
 */
class NotationValue implements IXmlConvertable, IXmlSchemaType
{
	/**
	 * CLASSNAME
	 * @var string
	 */
	public static $CLASSNAME = "lyquidity\XPath2\Value\NotationValue";

	/**
	 * Constructor
	 * @param QNameValue $name
	 */
	public  function __construct( $name )
	{
		$this->Prefix = $name->Prefix;
		$this->LocalName = $name->LocalName;
		$this->NamespaceUri = $name->NamespaceUri;
	}

	/**
	 * Prefix
	 * @var String $Prefix
	 */
	public $Prefix;

	/**
	 * LocalName
	 * @var String $LocalName
	 */
	public $LocalName;

	/**
	 * NamespaceUri
	 * @var String $NamespaceUri
	 */
	public $NamespaceUri;

	/**
	 * Returns a schema type for the proxy value
	 * @return XmlSchemaType
	 */
	public function getSchemaType()
	{
		return XmlSchema::$Notation;
	}

	/**
	 * Returns the contained value
	 * @return NotationValue
	 */
	public function getValue()
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
		if ( ! $obj instanceof NotationValue )
		{
			return false;
		}

		/**
		 * @var NotationValue $other
		 */
		$other = $obj;
		return $this->LocalName == $other->LocalName && $this->NamespaceUri == $other->NamespaceUri;

	}

	/**
	 * ToString
	 * @return string
	 */
	public function ToString()
	{
		$result = $this->LocalName;

		if ( ! empty( $this->Prefix ) )
		{
		    $result = $this->Prefix . ':' . $result;
		}
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
		    case XmlTypeCode::Notation:

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
	 * Parse
	 * @param string $name
	 * @param XmlNamespaceManager $resolver
	 * @return NotationValue
	 */
	public static function Parse( $name, $resolver )
	{
		return new NotationValue( QNameValue::fromNCName( $name, $resolver ) );
	}

	/**
	 * Unit tests
	 */
	public static function tests()
	{
		$resolver = new XmlNamespaceManager();
		$resolver->addNamespace( "xx", "xxNamespace" );
		$resolver->addNamespace( "yy", "yyNamespace" );
		$resolver->addNamespace( "", "defaultNamespace" );

		$qname1 = NotationValue::Parse( "xx:xxlocalname", $resolver );
		$qname2 = NotationValue::Parse( "xx:xxlocalname", $resolver );
		$qname3 = NotationValue::Parse( "zzlocalname", $resolver );

		$result = $qname1->Equals( $qname2 );
		$result = $qname1->Equals( $qname3 );

		$type = SequenceType::WithTypeCode( XmlTypeCode::Notation );
		$result = $qname1->ValueAs( $type, $resolver );
	}

}

?>
