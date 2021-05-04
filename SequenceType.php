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

namespace lyquidity\XPath2;

use \lyquidity\xml\MS\XmlTypeCardinality;
use \lyquidity\xml\MS\XmlQualifiedNameTest;
use \lyquidity\xml\MS\XmlTypeCode;
use \lyquidity\XPath2\DOM\DOMSchemaType;
use \lyquidity\xml\MS\XmlSchemaType;
use \lyquidity\xml\xpath\XPathNavigator;
use \lyquidity\xml\xpath\XPathNodeType;
use \lyquidity\xml\xpath\XPathItem;
use \lyquidity\xml\MS\IXmlSchemaInfo;
use \lyquidity\xml\MS\XmlSchemaDerivationMethod;
use \lyquidity\xml\MS\XmlSchemaAttribute;
use \lyquidity\xml\MS\XmlSchemaElement;
use \lyquidity\XPath2\lyquidity\Type;
use \lyquidity\xml\TypeCode;
use \lyquidity\XPath2\DOM\XmlSchema;
use \lyquidity\XPath2\lyquidity\Types;
use lyquidity\XPath2\DOM\DOMXPathNavigator;
use lyquidity\XPath2\Value\UntypedAtomic;
use lyquidity\XPath2\Value\Integer;
use lyquidity\XPath2\Value\DateTimeValue;
use lyquidity\XPath2\Value\DateValue;
use lyquidity\XPath2\Value\TimeValue;
use lyquidity\XPath2\Value\DurationValue;
use lyquidity\XPath2\Value\YearMonthDurationValue;
use lyquidity\XPath2\Value\DayTimeDurationValue;
use lyquidity\XPath2\Value\GYearMonthValue;
use lyquidity\XPath2\Value\GYearValue;
use lyquidity\XPath2\Value\GDayValue;
use lyquidity\XPath2\Value\GMonthValue;
use lyquidity\XPath2\Value\GMonthDayValue;
use lyquidity\XPath2\Value\QNameValue;
use lyquidity\XPath2\Value\AnyUriValue;
use lyquidity\XPath2\Value\HexBinaryValue;
use lyquidity\XPath2\Value\Base64BinaryValue;
use lyquidity\XPath2\Value\IDREFSValue;
use lyquidity\XPath2\Value\ENTITIESValue;
use lyquidity\XPath2\Value\NMTOKENSValue;
use lyquidity\XPath2\Value\DecimalValue;
use lyquidity\xml\QName;
use lyquidity\xml\schema\SchemaTypes;
use lyquidity\xml\exceptions\InvalidOperationException;
use lyquidity\xml\exceptions\ArgumentException;

/**
 * SequenceType (public)
 */
class SequenceType
{
	/**
	 * TypeCode
	 * @var XmlTypeCode $TypeCode
	 */
	public  $TypeCode;
	/**
	 * NameTest
	 * @var XmlQualifiedNameTest $NameTest
	 */
	public  $NameTest;
	/**
	 * Cardinality
	 * @var XmlTypeCardinality $Cardinality
	 */
	public  $Cardinality;
	/**
	 * SchemaType
	 * @var XmlSchemaType $SchemaType
	 */
	public  $SchemaType;
	/**
	 * SchemaElement
	 * @var XmlSchemaElement $SchemaElement
	 */
	public  $SchemaElement;
	/**
	 * SchemaAttribute
	 * @var XmlSchemaAttribute $SchemaAttribute
	 */
	public  $SchemaAttribute;
	/**
	 * Nillable
	 * @var bool $Nillable
	 */
	public  $Nillable;
	// public Type ParameterType { get; set; }
	/**
	 * ItemType
	 * @var Type $ItemType
	 */
	public  $ItemType;
	/**
	 * IsNode
	 * @var bool $IsNode
	 */
	public  $IsNode;
	/**
	 * Constructor
	 */
	public  function __construct()
	{
		$x = 1;
	}

	/**
	 * WithTypeCode
	 * @param XmlTypeCode $typeCode
	 * @return SequenceType
	 */
	public static function WithTypeCode( $typeCode )
	{
		return SequenceType::WithTypeCodeWithQNameTest( $typeCode, XmlQualifiedNameTest::getWildcard() );
	}

	/**
	 * WithTypeCodeAndCardinality
	 * @param XmlTypeCode $typeCode
	 * @param XmlTypeCardinality $cardinality
	 * @return SequenceType
	 */
	public static function WithTypeCodeAndCardinality( $typeCode, $cardinality )
	{
		$result = SequenceType::WithTypeCodeWithQNameTest( $typeCode, XmlQualifiedNameTest::getWildcard() );
		$result->Cardinality = $cardinality;
		return $result;
	}

	/**
	 * WithTypeCodeWithQNameTest
	 * @param XmlTypeCode $typeCode
	 * @param XmlQualifiedNameTest $nameTest
	 * @return SequenceType
	 */
	public static function WithTypeCodeWithQNameTest($typeCode, $nameTest)
	{
		$result = new SequenceType();

		$result->TypeCode = $typeCode;
		$result->Cardinality = XmlTypeCardinality::One;
		$result->NameTest = $nameTest;
		$result->IsNode = $result->TypeCodeIsNodeType( $result->TypeCode);
		if ( $result->TypeCode != XmlTypeCode::Item && ! $result->IsNode )
			$result->SchemaType = DOMSchemaType::GetBuiltInSimpleTypeByTypecode( $result->TypeCode );
		$result->ItemType = $result->TypeCodeToItemType( $result->TypeCode, $result->SchemaType );

		return $result;
	}

	/**
	 * WithTypeCodeWithQNameTestWithSchemaType
	 * @param XmlTypeCode $typeCode
	 * @param XmlQualifiedNameTest $nameTest
	 * @param XmlSchemaType $schemaType
	 * @return SequenceType
	 */
	public static function WithTypeCodeWithQNameTestWithSchemaType( $typeCode, $nameTest, $schemaType )
	{
		return SequenceType::WithTypeCodeWithQNameTestWithSchemaTypeWithIsOptional( $typeCode, $nameTest, $schemaType, false );
	}

	/**
	 * WithTypeCodeWithQNameTestWithSchemaTypeWithIsOptional
	 * @param XmlTypeCode $typeCode
	 * @param XmlQualifiedNameTest $nameTest
	 * @param XmlSchemaType $schemaType
	 * @param bool $isOptional
	 * @return SequenceType
	 */
	public static function WithTypeCodeWithQNameTestWithSchemaTypeWithIsOptional( $typeCode, $nameTest, $schemaType, $isOptional )
	{
		$result = new SequenceType();

		$result->TypeCode = $typeCode;
		$result->Cardinality = XmlTypeCardinality::One;
		$result->NameTest = $nameTest;
		$result->SchemaType = $schemaType;
		$result->IsNode = $result->TypeCodeIsNodeType( $result->TypeCode );
		$result->ItemType = $result->TypeCodeToItemType( $result->TypeCode, $result->SchemaType );

		return $result;
	}

	/**
	 * WithElement
	 * @param XmlSchemaElement $schemaElement
	 * @return SequenceType
	 */
	public static function WithElement( $schemaElement )
	{
		$result = new SequenceType();

		$result->TypeCode = XmlTypeCode::Element;
		$result->SchemaElement = $schemaElement;
		$result->IsNode = true;
		$result->ItemType = $result->TypeCodeToItemType( $result->TypeCode, $result->SchemaType );

		return $result;
	}

	/**
	 * WithAttribute
	 * @param XmlSchemaAttribute $schemaAttribute
	 * @return SequenceType
	 */
	public static function WithAttribute( $schemaAttribute )
	{
		$result = new SequenceType();

		$result->TypeCode = XmlTypeCode::Attribute;
		$result->SchemaAttribute = $schemaAttribute;
		$result->IsNode = true;
		$result->ItemType = $result->TypeCodeToItemType( $result->TypeCode, $result->SchemaType );

		return $result;
	}

	/**
	 * WithSchemaTypeWithCardinality
	 * @param XmlSchemaType $schemaType
	 * @param XmlTypeCardinality $cardinality
	 * @return SequenceType
	 */
	public static function WithSchemaTypeWithCardinality( $schemaType, $cardinality )
	{
		$result = new SequenceType();

		$result->TypeCode = $schemaType->TypeCode;
		$result->SchemaType = $schemaType;
		$result->Cardinality = $cardinality;
		$result->IsNode = $result->TypeCodeIsNodeType( $result->TypeCode );
		$result->ItemType = $result->TypeCodeToItemType( $result->TypeCode, $result->SchemaType );

		return $result;
	}

	/**
	 * FromSequenceType
	 * @param SequenceType $src
	 * @return SequenceType
	 */
	public static function FromSequenceType( $src )
	{
		$result = new SequenceType();

		$result->TypeCode = $src->TypeCode;
		$result->NameTest = $src->NameTest;
		$result->Cardinality = $src->Cardinality;
		$result->SchemaType = $src->SchemaType;
		$result->SchemaElement = $src->SchemaElement;
		$result->SchemaAttribute = $src->SchemaAttribute;
		$result->Nillable = $src->Nillable;
		$result->IsNode = $src->IsNode;
		$result->ItemType = $src->ItemType;

		return $result;
	}

	/**
	 * Get the type of the value
	 * @var Type $ValueType
	 */
	public function getValueType()
	{
		if ( $this->Cardinality == XmlTypeCardinality::ZeroOrMore ||
			 $this->Cardinality == XmlTypeCardinality::OneOrMore )
			return "\lyquidity\XPath2\XPath2NodeIterator";

		if ( $this->IsNode )
			return "\lyquidity\xml\xpath\XPathNavigator";

		if ($this->Cardinality == XmlTypeCardinality::One)
			return $this->ItemType;

		return "object";
	}

	/**
	 * Get the type of the atomized value
	 * @return Type
	 */
	public function getAtomizedValueType()
	{
		if ( $this->IsNode )
		{
			switch ( $this->TypeCode )
			{
				case XmlTypeCode::Text:
				case XmlTypeCode::ProcessingInstruction:
				case XmlTypeCode::Comment:
				case XmlTypeCode::UntypedAtomic:
					return Types::$UntypedAtomicType;

				default:

					if ( ! is_null( $this->SchemaType ) )
						return $this->SchemaType->Datatype->ValueType;
					else if ( ! is_null( $this->SchemaElement ) )
					{
						if ( ! is_null( $this->SchemaElement->ElementSchemaType ) && ! is_null( $this->SchemaElement->ElementSchemaType->Datatype ) )
							return $this->SchemaElement->ElementSchemaType->Datatype->ValueType;
					}
					else if ( ! is_null( $this->SchemaAttribute ) )
					{
						if ( ! is_null( $this->SchemaAttribute->AttributeSchemaType ) && ! is_null( $this->SchemaAttribute->AttributeSchemaType->Datatype ) )
							return $this->SchemaAttribute->AttributeSchemaType->Datatype->ValueType;
					}
					return Types::$UntypedAtomicType;
			}
		}
		else
			return $this->ItemType;
	}

	/**
	 * Returns true if the value is numeric
	 * @return bool
	 */
	public function getIsNumeric()
	{
		switch ( $this->TypeCode )
		{
			case XmlTypeCode::Decimal:
			case XmlTypeCode::Float:
			case XmlTypeCode::Double:
			case XmlTypeCode::Integer:
			case XmlTypeCode::NonPositiveInteger:
			case XmlTypeCode::NegativeInteger:
			case XmlTypeCode::Long:
			case XmlTypeCode::Int:
			case XmlTypeCode::Short:
			case XmlTypeCode::Byte:
			case XmlTypeCode::NonNegativeInteger:
			case XmlTypeCode::UnsignedLong:
			case XmlTypeCode::UnsignedInt:
			case XmlTypeCode::UnsignedShort:
			case XmlTypeCode::UnsignedByte:
			case XmlTypeCode::PositiveInteger:
				return true;
		}
		return false;
	}

	/**
	 * Returns true if the atomic value is untyped
	 * @return bool
	 */
	public function getIsUntypedAtomic()
	{
		return $this->TypeCode == XmlTypeCode::UntypedAtomic;
	}

	/**
	 * MatchName
	 * @param XPathNavigator $nav
	 * @param XPath2Context $context
	 * @return bool
	 */
	private function MatchName( $nav, $context )
	{
		return ( $this->NameTest->IsNamespaceWildcard() || $this->NameTest->namespaceURI == $nav->getNamespaceURI() ) &&
		   ($this->NameTest->IsNameWildcard() || $this->NameTest->localName == $nav->getLocalName() );
	}

	/**
	 * GetNodeKind
	 * @return XPathNodeType
	 */
	public function GetNodeKind()
	{
		switch ( $this->TypeCode )
		{
			case XmlTypeCode::Item:
				return XPathNodeType::All;
			case XmlTypeCode::Document:
				return XPathNodeType::Root;
			case XmlTypeCode::Element:
				return XPathNodeType::Element;
			case XmlTypeCode::Attribute:
				return XPathNodeType::Attribute;
			case XmlTypeCode::Namespace_:
				return XPathNodeType::NamespaceURI;
			case XmlTypeCode::Text:
				return XPathNodeType::Text;
			case XmlTypeCode::Comment:
				return XPathNodeType::Comment;
			case XmlTypeCode::ProcessingInstruction:
				return XPathNodeType::ProcessingInstruction;
			default:
				throw new InvalidOperationException( "GetNodeKind()" );
		}
	}

	/**
	 * Match
	 * @param XPathItem $item
	 * @param XPath2Context $context
	 * @return bool
	 */
	public function Match( $item, $context )
	{
		if ( $this->TypeCode == $item->getSchemaType()->TypeCode ) return true;

		switch ( $this->TypeCode )
		{
			case XmlTypeCode::None:
				return false;

			case XmlTypeCode::Item:
				return true;

			case XmlTypeCode::Node:
				return $item->getIsNode();

			case XmlTypeCode::AnyAtomicType:
				return ! $item->getIsNode();

			case XmlTypeCode::UntypedAtomic:
				return ! $item->getIsNode() && $item->GetSchemaType() == XmlSchema::$UntypedAtomic;

			case XmlTypeCode::Document:

				if ( $item instanceof XPathNavigator )
				{
					/**
					 * @var XPathNavigator $nav
					 */
					$nav = $item;
					if ( $nav->getNodeType() == XPathNodeType::Root)
					{
						/**
						 * @var XPathNavigator $cur
						 */
						$cur = $nav->CloneInstance();
						if ( is_null( $this->SchemaElement ) )
						{
							if ( $cur->MoveToChild( XPathNodeType::Element ) && $this->MatchName( $cur, $context ) )
							{
								if ( is_null( $this->SchemaType ) || $this->SchemaType == XmlSchema::$UntypedAtomic )
								{
									return true;
								}
								/**
								 * @var IXmlSchemaInfo $schemaInfo
								 */
								$schemaInfo = $cur->getSchemaInfo();
								if ( ! is_null( $this->schemaInfo ) )
								{
									if ( DOMSchemaType::IsDerivedFrom( $schemaInfo->getSchemaType(), $this->SchemaType, XmlSchemaDerivationMethod::Empty_ ) )
										return ! $schemaInfo->getIsNil() || $this->Nillable;
								}
								else
								{
									return DOMSchemaType::IsDerivedFrom( XmlSchema::$UntypedAtomic, $this->SchemaType, XmlSchemaDerivationMethod::Empty_ );
								}
							}
						}
						else
						{
							if ( ! $cur->MoveToChild( XPathNodeType::Element ) )
							{
								return false;
							}

							/**
							 * @var IXmlSchemaInfo $schemaInfo
							 */
							$schemaInfo = $cur->getSchemaInfo();
							if ( ! is_null( $schemaInfo ) )
							{
								return $schemaInfo->getSchemaElement()->QualifiedName->equals( $this->SchemaElement->QualifiedName );
							}
						}
					}
				}
				break;

			case XmlTypeCode::Element:

				if ( $item instanceof XPathNavigator )
				{
					/**
				   	 * @var XPathNavigator $nav
				   	 */
					$nav = $item;
					if ( $nav->getNodeType() == XPathNodeType::Element )
					{
						if ( is_null( $this->SchemaElement ) )
						{
							if ( $this->MatchName( $nav, $context ) )
							{
								if ( is_null( $this->SchemaType ) || $this->SchemaType == XmlSchema::$UntypedAtomic )
								{
									return true;
								}

								/**
								 * @var IXmlSchemaInfo $schemaInfo
								 */
								$schemaInfo = $nav->getSchemaInfo();
								if ( is_null( $schemaInfo ) )
								{
									return DOMSchemaType::IsDerivedFrom( XmlSchema::$UntypedAtomic, $this->SchemaType, XmlSchemaDerivationMethod::Empty_ );
								}
								else
								{
									if ( DOMSchemaType::IsDerivedFrom( $schemaInfo->getSchemaType(), $this->SchemaType, XmlSchemaDerivationMethod::Empty_ ) )
									{
										return ! $schemaInfo->getIsNil() || $this->Nillable;
									}
								}
							}
						}
						else
						{
							/**
							 * @var IXmlSchemaInfo $schemaInfo
							 */
							$schemaInfo = $nav->getSchemaInfo();
							if ( ! is_null( $schemaInfo ) )
								return $schemaInfo->getSchemaElement()->QualifiedName->equals( $this->SchemaElement->QualifiedName );
						}
					}
				}
				break;

			case XmlTypeCode::Attribute:

				if ( $item instanceof XPathNavigator )
				{
					/**
					 * @var XPathNavigator $nav
					 */
					$nav = $item;
					if ( $nav->getNodeType() == XPathNodeType::Attribute )
					{
						if ( is_null( $this->SchemaAttribute ) )
						{
							if ( $this->MatchName( $nav, $context ) )
							{
								if ( is_null( $this->SchemaType ) || $this->SchemaType == XmlSchema::$UntypedAtomic )
								{
									return true;
								}
								/**
								 * @var IXmlSchemaInfo $schemaInfo
								 */
								$schemaInfo = $nav->getSchemaInfo();
								if ( is_null( $schemaInfo ) )
									return DOMSchemaType::IsDerivedFrom( XmlSchema::$UntypedAtomic, $this->SchemaType, XmlSchemaDerivationMethod::Empty_ );
								else
									return DOMSchemaType::IsDerivedFrom( $schemaInfo->getSchemaType(), $this->SchemaType, XmlSchemaDerivationMethod::Empty_ );
							}
						}
						else
						{
							/**
							 * @var IXmlSchemaInfo $schemaInfo
							 */
							$schemaInfo = $nav->getSchemaInfo();
							if ( ! is_null( $schemaInfo ) )
								return $schemaInfo->getSchemaAttribute()->QualifiedName->equals( $this->SchemaAttribute->QualifiedName );
						}
					}
				}
				break;

			case XmlTypeCode::ProcessingInstruction:

				if ( $item instanceof XPathNavigator )
				{
					/**
					 * @var XPathNavigator $nav
					 */
					$nav = $item;
					return ( $nav->getNodeType() == XPathNodeType::ProcessingInstruction && ( $this->NameTest->IsNameWildcard() || $this->NameTest->localName == $nav->getLocalName() ) );
				}
				break;

			case XmlTypeCode::Comment:

				if ( $item instanceof XPathNavigator )
				{
					/**
					 * @var XPathNavigator $nav
					 */
					$nav = $item;
					return $nav->getNodeType() == XPathNodeType::Comment;
				}
				break;

			case XmlTypeCode::Text:

				if ( $item instanceof XPathNavigator )
				{
					/**
					 * @var XPathNavigator $nav
					 */
					$nav = $item;
					return $nav->getNodeType() == XPathNodeType::Text || $nav->getNodeType() == XPathNodeType::SignificantWhitespace;
				}
				break;

			case XmlTypeCode::PositiveInteger:
				switch ( $item->GetSchemaType()->TypeCode )
				{
					case XmlTypeCode::Byte:
					case XmlTypeCode::Short:
					case XmlTypeCode::Int:
					case XmlTypeCode::Integer:
						return $item->getValueAsInt() >= 0;

					case XmlTypeCode::Long:
						return false;
				}
				break;

			case XmlTypeCode::NegativeInteger:
				switch ( $item->GetSchemaType()->TypeCode )
				{
					case XmlTypeCode::Byte:
					case XmlTypeCode::Short:
					case XmlTypeCode::Int:
					case XmlTypeCode::Integer:
						return $item->getValueAsInt() <= 0;

					case XmlTypeCode::Long:
						return false;
				}
				break;

			case XmlTypeCode::NonPositiveInteger:
				switch ( $item->GetSchemaType()->TypeCode )
				{
					case XmlTypeCode::Byte:
					case XmlTypeCode::Short:
					case XmlTypeCode::Int:
					case XmlTypeCode::Long:
					case XmlTypeCode::Integer:
						return $item->getValueAsInt() <= 0;
				}
				break;

			case XmlTypeCode::NonNegativeInteger:
				switch ( $item->GetSchemaType()->TypeCode )
				{
					case XmlTypeCode::Byte:
					case XmlTypeCode::Short:
					case XmlTypeCode::Int:
					case XmlTypeCode::Integer:
						return $item->getValueAsInt() >= 0;

					case XmlTypeCode::Long:
						return $item->getValueAsLong() == 0;

					case XmlTypeCode::UnsignedByte:
					case XmlTypeCode::UnsignedShort:
					case XmlTypeCode::UnsignedInt:
					case XmlTypeCode::UnsignedLong:
						return true;
				}
				break;

			case XmlTypeCode::Int:
			case XmlTypeCode::Byte:
			case XmlTypeCode::Short:
			case XmlTypeCode::Long:
			case XmlTypeCode::Integer:
			case XmlTypeCode::UnsignedLong:
			case XmlTypeCode::UnsignedInt:
			case XmlTypeCode::UnsignedByte:
			case XmlTypeCode::UnsignedShort:
				switch ( $item->GetSchemaType()->TypeCode )
				{
					case XmlTypeCode::Byte:
					case XmlTypeCode::Short:
					case XmlTypeCode::Int:
					case XmlTypeCode::Long:
					case XmlTypeCode::Integer:
					case XmlTypeCode::UnsignedByte:
					case XmlTypeCode::UnsignedShort:
					case XmlTypeCode::UnsignedInt:
					case XmlTypeCode::UnsignedLong:
						return true;

					case XmlTypeCode::Decimal:
						// BMS 2017-07-20 Changed result
						/**
						 * @var DecimalValue $dec
						 */
						$dec = $item->getTypedValue();
						return $dec->getIntegerPart() == $dec->getValue();
						// BMS 2017-07-10 Changed result
						return false;
						return ! $item->ValueAs( Types::$DecimalType, $context->NamespaceManager )->getIsDecimal();

					case XmlTypeCode::Double:
						// BMS 2017-07-10 Changed result
						return false;
						$value = $item->getTypedValue(); // ->ValueAs( Types::$DoubleType );
						return $value == floor( (float)$value );
				}
				break;

			case XmlTypeCode::Entity:
				return	( $item->GetSchemaType()->TypeCode == XmlTypeCode::String ) ||
						( $item->GetSchemaType()->TypeCode == XmlTypeCode::Entity );

			// In PHP these are synonymous
			// BMS 2017-07-10 Changed result
			case XmlTypeCode::Float:
				switch ( $item->GetSchemaType()->TypeCode )
				{
					// Note the order of these cases is significant
					// If the order is different then float will be
					// reported as an instance of double and will fail
					// test instanceof49 in the exprSeqTypes test set.
					case XmlTypeCode::Float:
						return true;

					case XmlTypeCode::Double:
						if ( is_nan( (float)$item->getTypedValue() ) )
						{
							return true;
						}
				}
				break;

			// BMS 2017-07-10 Changed result
			case XmlTypeCode::Double:
				switch ( $item->GetSchemaType()->TypeCode )
				{
					case XmlTypeCode::Double:
						return true;
				}
				break;

			// BMS 2017-07-10 Changed result
			case XmlTypeCode::Decimal:
				switch ( $item->GetSchemaType()->TypeCode )
				{
					case XmlTypeCode::Byte:
					case XmlTypeCode::Short:
					case XmlTypeCode::Int:
					case XmlTypeCode::Long:
					case XmlTypeCode::Integer:
					case XmlTypeCode::UnsignedByte:
					case XmlTypeCode::UnsignedShort:
					case XmlTypeCode::UnsignedInt:
					case XmlTypeCode::UnsignedLong:
					case XmlTypeCode::Decimal:
						return true;
				}
				break;

			default:
				{
					$schemaType = $item->getSchemaType();
					if ( ! is_null( $schemaType ) )
					{
						// BMS 2017-09-03	Added this condition. The XPath documentation indicates that an
						//					untyped type can be matched with any other type.
						// https://www.w3.org/TR/xpath-functions/#casting-from-primitive-to-primitive
						if ( $schemaType->TypeCode == XmlTypeCode::UntypedAtomic )
						{
							return true;
						}
						return DOMSchemaType::IsDerivedFrom( $schemaType, $this->SchemaType, XmlSchemaDerivationMethod::Empty_ );
					}
				}
				break;
		}
		return false;
	}

	/**
	 * Magic function returns a string representation of this
	 * @return string
	 */
	public function __toString()
	{
		return $this->ToString();
	}

	/**
	 * ToString
	 * @return string
	 */
	public function ToString()
	{
		$sb = array();
		switch ( $this->TypeCode )
		{
			case XmlTypeCode::AnyAtomicType:
				$sb[] = "AnyAtomicType";
				break;

			case XmlTypeCode::UntypedAtomic:
				$sb[] = "UntypedAtomic";
				break;

			case XmlTypeCode::None:
				$sb[] = "empty-sequence()";
				break;

			case XmlTypeCode::Item:
				$sb[] = "item()";
				break;

			case XmlTypeCode::Node:
				$sb[] = "node()";
				break;

			case XmlTypeCode::Document:
				$sb[] = "document-node(";
				if ( is_null( $this->SchemaElement ) )
				{
					if ( ! $this->NameTest->IsWildcard() || is_null( $this->SchemaType ) )
					{
						$sb[] = "element(";
						$sb[] = $this->NameTest->ToString();
						if ( ! is_null( $this->SchemaType ) )
						{
							$sb[] = ",";
							$sb[] = get_class( $this->SchemaType );
							if ( $this->Nillable )
								$sb[] = "?";
						}
						else
						{
							$sb[] = ", xs:untyped";
						}
						$sb[] = ")";
					}
				}
				else
				{
					$sb[] = "schema-element(";
					$sb[] = $this->SchemaElement->QualifiedName->__toString();
					$sb[] = ")";
				}
				$sb[] = ")";
				break;

			case XmlTypeCode::Element:
				if ( is_null( $this->SchemaElement ) )
				{
					$sb[] = "element(";
					$sb[] = $this->NameTest->ToString();
					if ( ! is_null( $this->SchemaType ) )
					{
						$sb[] = ",";
						$sb[] = get_class( $this->SchemaType );
						if ( $this->Nillable )
						{
							$sb[] = "?";
						}
					}
					else
					{
						$sb[] = ", xs:untyped";
					}
				}
				else
				{
					$sb[] = "schema-element(";
					$sb[] = $this->SchemaElement->QualifiedName->__toString();
				}
				$sb[] = ")";
				break;

			case XmlTypeCode::Attribute:
				if ( is_null( $this->SchemaAttribute ) )
				{
					$sb[] = "attribute(";
					$sb[] = $this->NameTest->ToString();
					if ( ! is_null( $this->SchemaType ) )
					{
						$sb[] = ",";
						$sb[] = get_class( $this->SchemaType );
					}
				}
				else
				{
					$sb[] = "schema-attribute(";
					$sb[] = $this->SchemaAttribute->QualifiedName;
				}
				$sb[] = ")";
				break;

			case XmlTypeCode::ProcessingInstruction:
				$sb[] = "processing-instruction(";
				break;

			case XmlTypeCode::Comment:
				$sb[] = "comment()";
				break;

			case XmlTypeCode::Text:
				$sb[] = "text()";
				break;

			case XmlTypeCode::String:
				$sb[] = "xs:string";
				break;

			case XmlTypeCode::Boolean:
				$sb[] = "xs:boolean";
				break;

			case XmlTypeCode::Decimal:
				$sb[] = "xs:decimal";
				break;

			case XmlTypeCode::Float:
				$sb[] = "xs:float";
				break;

			case XmlTypeCode::Double:
				$sb[] = "xs:double";
				break;

			case XmlTypeCode::Duration:
				$sb[] = "xs:Duration";
				break;

			case XmlTypeCode::DateTime:
				$sb[] = "xs:dateTime";
				break;

			case XmlTypeCode::Time:
				$sb[] = "xs:time";
				break;

			case XmlTypeCode::Date:
				$sb[] = "xs:date";
				break;

			case XmlTypeCode::GYearMonth:
				$sb[] = "xs:gYearMonth";
				break;

			case XmlTypeCode::GYear:
				$sb[] = "xs:gYear";
				break;

			case XmlTypeCode::GMonthDay:
				$sb[] = "xs:gMonthDay";
				break;

			case XmlTypeCode::GDay:
				$sb[] = "xs:gDay";
				break;

			case XmlTypeCode::GMonth:
				$sb[] = "xs:gMonth";
				break;

			case XmlTypeCode::HexBinary:
				$sb[] = "xs:hexBinary";
				break;

			case XmlTypeCode::Base64Binary:
				$sb[] = "xs:base64Binary";
				break;

			case XmlTypeCode::AnyUri:
				$sb[] = "xs:anyURI";
				break;

			case XmlTypeCode::QName:
				$sb[] = "xs:QName";
				break;

			case XmlTypeCode::Notation:
				$sb[] = "xs:NOTATION";
				break;

			case XmlTypeCode::NormalizedString:
				$sb[] = "xs:normalizedString";
				break;

			case XmlTypeCode::Token:
				$sb[] = "xs:token";
				break;

			case XmlTypeCode::Language:
				$sb[] = "xs:language";
				break;

			case XmlTypeCode::NmToken:
				if ( $this->SchemaType == XmlSchema::$NMTOKENS )
					$sb[] = "xs:NMTOKENS";
				else
					$sb[] = "xs:NMTOKEN";
				break;

			case XmlTypeCode::Name:
				$sb[] = "xs:Name";
				break;

			case XmlTypeCode::NCName:
				$sb[] = "xs:NCName";
				break;

			case XmlTypeCode::Id:
				$sb[] = "xs:ID";
				break;

			case XmlTypeCode::Idref:
				if ( $this->SchemaType == XmlSchema::$IDREFS )
					$sb[] = "xs:IDREFS";
				else
					$sb[] = "xs:IDREF";
				break;

			case XmlTypeCode::Entity:
				if ( $this->SchemaType == XmlSchema::$ENTITIES )
					$sb[] = "xs:ENTITYS";
				else
					$sb[] = "xs:ENTITY";
				break;

			case XmlTypeCode::Integer:
				$sb[] = "xs:integer";
				break;

			case XmlTypeCode::NonPositiveInteger:
				$sb[] = "xs:nonPositiveInteger";
				break;

			case XmlTypeCode::NegativeInteger:
				$sb[] = "xs:negativeInteger";
				break;

			case XmlTypeCode::Long:
				$sb[] = "xs:long";
				break;

			case XmlTypeCode::Int:
				$sb[] = "xs:int";
				break;

			case XmlTypeCode::Short:
				$sb[] = "xs:short";
				break;

			case XmlTypeCode::Byte:
				$sb[] = "xs:byte";
				break;

			case XmlTypeCode::NonNegativeInteger:
				$sb[] = "xs:nonNegativeInteger";
				break;

			case XmlTypeCode::UnsignedLong:
				$sb[] = "xs:unsignedLong";
				break;

			case XmlTypeCode::UnsignedInt:
				$sb[] = "xs:unsignedInt";
				break;

			case XmlTypeCode::UnsignedShort:
				$sb[] = "xs:unsignedShort";
				break;

			case XmlTypeCode::UnsignedByte:
				$sb[] = "xs:unsignedByte";
				break;

			case XmlTypeCode::PositiveInteger:
				$sb[] = "xs:positiveInteger";
				break;

			case XmlTypeCode::DayTimeDuration:
				$sb[] = "xs:dayTimeDuration";
				break;

			case XmlTypeCode::YearMonthDuration:
				$sb[] = "xs:yearMonthDuration";
				break;

			default:
				$sb[] = "[]";
				break;
		}

		switch ( $this->Cardinality )
		{
			case XmlTypeCardinality::OneOrMore:
				$sb[] = "+";
				break;

			case XmlTypeCardinality::ZeroOrMore:
				$sb[] = "*";
				break;

			case XmlTypeCardinality::ZeroOrOne:
				$sb[] = "?";
				break;
		}

		return implode( "", $sb );
	}

	/**
	 * GetXmlTypeCode
	 * @param object $value
	 * @return XmlTypeCode
	 */
	static public function GetXmlTypeCodeFromObject( $value )
	{
		if ( $value instanceof XPathNavigator )
		{
			/**
			 * @var XPathNavigator $nav
			 */
			$nav = $value;
			switch ( $nav->getNodeType() )
			{
				case XPathNodeType::Attribute:
					return XmlTypeCode::Attribute;

				case XPathNodeType::Comment:
					return XmlTypeCode::Comment;

				case XPathNodeType::Element:
					return XmlTypeCode::Element;

				case XPathNodeType::NamespaceURI:
					return XmlTypeCode::Namespace_;

				case XPathNodeType::ProcessingInstruction:
					return XmlTypeCode::ProcessingInstruction;

				case XPathNodeType::Root:
					return XmlTypeCode::Document;

				case XPathNodeType::SignificantWhitespace:
				case XPathNodeType::Whitespace:
				case XPathNodeType::Text:
					return XmlTypeCode::Text;

				default:
					return XmlTypeCode::None;
			}
		}
		if ( $value instanceof  XPathItem )
		{
			/**
			 * @var XPathItem $item
			 */
			$item = $value;
			if ( is_null( $item->getSchemaType() ) )
			{
				return XmlTypeCode::UntypedAtomic;
			}

			return $item->getSchemaType()->TypeCode;
		}

		return SequenceType::GetXmlTypeCodeFromType( Type::FromValue( $value ) );
	}

	/**
	 * TypeCodeIsNodeType
	 * @param XmlTypeCode $typeCode
	 * @return bool
	 */
	public static function TypeCodeIsNodeType( $typeCode )
		{
			switch ( $typeCode )
			{
				case XmlTypeCode::Node:
				case XmlTypeCode::Element:
				case XmlTypeCode::Attribute:
				case XmlTypeCode::Document:
				case XmlTypeCode::Comment:
				case XmlTypeCode::Text:
				case XmlTypeCode::ProcessingInstruction:
					return true;
			}
			return false;
		}

	/**
	 * TypeCodeToItemType
	 * @param XmlTypeCode $typeCode
	 * @param XmlSchemaType $schemaType
	 * @return Type
	 */
	public static function TypeCodeToItemType( $typeCode, $schemaType )
	{
		switch ($typeCode)
		{
			case XmlTypeCode::Boolean:
				return Types::$BooleanType;

			case XmlTypeCode::Short:
				return Types::$ShortType;

			case XmlTypeCode::Int:
				return Types::$IntType;

			case XmlTypeCode::Long:
				return Types::$LongType;

			case XmlTypeCode::UnsignedShort:
				return Types::$UInt16Type;

			case XmlTypeCode::UnsignedInt:
				return Types::$UInt32Type;

			case XmlTypeCode::UnsignedLong:
				return Types::$UInt64Type;

			case XmlTypeCode::Byte:
				return Types::$ByteType;

			case XmlTypeCode::UnsignedByte:
				return Types::$ByteType;

			case XmlTypeCode::Float:
				return Types::$FloatType;

			case XmlTypeCode::Decimal:
				return Types::$DecimalType;

			case XmlTypeCode::Integer:
			case XmlTypeCode::PositiveInteger:
			case XmlTypeCode::NegativeInteger:
			case XmlTypeCode::NonPositiveInteger:
			case XmlTypeCode::NonNegativeInteger:
				return Types::$IntType;

			case XmlTypeCode::Double:
				return Types::$DoubleType;

			case XmlTypeCode::DateTime:
				return Types::$DateTimeValueType;

			case XmlTypeCode::Date:
				return Types::$DateValueType;

			case XmlTypeCode::Time:
				return Types::$TimeValueType;

			case XmlTypeCode::AnyUri:
				return Types::$AnyUriValueType;

			case XmlTypeCode::String:
			case XmlTypeCode::NormalizedString:
			case XmlTypeCode::Token:
			case XmlTypeCode::Language:
			case XmlTypeCode::Name:
			case XmlTypeCode::NCName:
			case XmlTypeCode::Id:
				return Types::$StringType;

			case XmlTypeCode::Idref:
				if ($schemaType == XmlSchema::$IDREFS)
					return Types::$IDREFSValueType;
				else
					return Types::$StringType;
			case XmlTypeCode::NmToken:
				if ($schemaType == XmlSchema::$NMTOKENS)
					return Types::$NMTOKENSValueType;
				else
					return Types::$StringType;
			case XmlTypeCode::Entity:
				if ($schemaType == XmlSchema::$ENTITIES)
					return Types::$ENTITIESValueType;
				else
					return Types::$StringType;
			case XmlTypeCode::UntypedAtomic:
				return Types::$UntypedAtomicType;
			case XmlTypeCode::Duration:
				return Types::$DurationValueType;
			case XmlTypeCode::DayTimeDuration:
				return Types::$DayTimeDurationValueType;
			case XmlTypeCode::YearMonthDuration:
				return Types::$YearMonthDurationValueType;
			case XmlTypeCode::GYearMonth:
				return Types::$GYearMonthValueType;
			case XmlTypeCode::GYear:
				return Types::$GYearValueType;
			case XmlTypeCode::GMonth:
				return Types::$GMonthValueType;
			case XmlTypeCode::GMonthDay:
				return Types::$GMonthDayValueType;
			case XmlTypeCode::GDay:
				return Types::$GDayValueType;
			case XmlTypeCode::QName:
				return Types::$QNameValueType;
			case XmlTypeCode::HexBinary:
				return Types::$HexBinaryValueType;
			case XmlTypeCode::Base64Binary:
				return Types::$Base64BinaryValueType;
			default:
				return Types::$ObjectType;
		}
	}

	/**
	 * GetXmlTypeCode
	 * @param Type $type
	 * @return XmlTypeCode
	 */
	public static function GetXmlTypeCodeFromType( $type )
	{
		/**
		 * @var TypeCode
		 */
		switch ( $type->getTypeCode() )
		{
			case TypeCode::Boolean:
				return XmlTypeCode::Boolean;
			case TypeCode::Int16:
				return XmlTypeCode::Short;
			case TypeCode::Int32:
				return XmlTypeCode::Int;
			case TypeCode::Int64:
				return XmlTypeCode::Long;
			case TypeCode::UInt16:
				return XmlTypeCode::UnsignedShort;
			case TypeCode::UInt32:
				return XmlTypeCode::UnsignedInt;
			case TypeCode::UInt64:
				return XmlTypeCode::UnsignedLong;
			case TypeCode::SByte:
				return XmlTypeCode::Byte;
			case TypeCode::Byte:
				return XmlTypeCode::UnsignedByte;
			case TypeCode::Single:
				return XmlTypeCode::Float;
			case TypeCode::Decimal:
				return XmlTypeCode::Decimal;
			case TypeCode::Double:
				return XmlTypeCode::Double;
			case TypeCode::Char:
			case TypeCode::String:
				return XmlTypeCode::String;
			default:
				$typeName = $type->getTypeName();
				if ($typeName == DOMXPathNavigator::$CLASSNAME )
					return XmlTypeCode::Node;
				if ($typeName == UntypedAtomic::$CLASSNAME )
					return XmlTypeCode::UntypedAtomic;
				if ($typeName == Integer::$CLASSNAME )
					return XmlTypeCode::Integer;
				if ($typeName == DateTimeValue::$CLASSNAME )
					return XmlTypeCode::DateTime;
				if ($typeName == DateValue::$CLASSNAME )
					return XmlTypeCode::Date;
				if ($typeName == TimeValue::$CLASSNAME )
					return XmlTypeCode::Time;
				if ($typeName == DurationValue::$CLASSNAME )
					return XmlTypeCode::Duration;
				if ($typeName == YearMonthDurationValue::$CLASSNAME)
					return XmlTypeCode::YearMonthDuration;
				if ($typeName == DayTimeDurationValue::$CLASSNAME )
					return XmlTypeCode::DayTimeDuration;
				if ($typeName == GYearMonthValue::$CLASSNAME )
					return XmlTypeCode::GYearMonth;
				if ($typeName == GYearValue::$CLASSNAME )
					return XmlTypeCode::GYear;
				if ($typeName == GDayValue::$CLASSNAME )
					return XmlTypeCode::GDay;
				if ($typeName == GMonthValue::$CLASSNAME )
					return XmlTypeCode::GMonth;
				if ($typeName == GMonthDayValue::$CLASSNAME )
					return XmlTypeCode::GMonthDay;
				if ($typeName == QNameValue::$CLASSNAME )
					return XmlTypeCode::QName;
				if ($typeName == AnyUriValue::$CLASSNAME )
					return XmlTypeCode::AnyUri;
				if ($typeName == HexBinaryValue::$CLASSNAME )
					return XmlTypeCode::HexBinary;
				if ($typeName == Base64BinaryValue::$CLASSNAME )
					return XmlTypeCode::Base64Binary;
				if ($typeName == IDREFSValue::$CLASSNAME )
					return XmlTypeCode::Idref;
				if ($typeName == ENTITIESValue::$CLASSNAME )
					return XmlTypeCode::Entity;
				if ($typeName == NMTOKENSValue::$CLASSNAME )
					return XmlTypeCode::NmToken;
				return XmlTypeCode::Item;
		}
	}

	/**
	 * IsDerivedFrom
	 * @param SequenceType $src
	 * @return bool
	 */
	public function IsDerivedFrom( $src )
	{
		switch ( $src->TypeCode )
		{
			case XmlTypeCode::Node:
				if ( ! $this->IsNode )
					return false;
				break;

			case XmlTypeCode::AnyAtomicType:
			case XmlTypeCode::UntypedAtomic:
				if ( $this->IsNode )
					return false;
				break;

			case XmlTypeCode::Document:
				if ( $this->TypeCode != XmlTypeCode::Document || $this->SchemaElement != $src->SchemaElement)
					return false;
				break;

			case XmlTypeCode::Element:
				if ( $this->TypeCode != XmlTypeCode::Element || $this->SchemaElement != $src->SchemaElement)
					return false;
				break;

			case XmlTypeCode::Attribute:
				if ($this->TypeCode != XmlTypeCode::Attribute || $this->SchemaAttribute != $src->SchemaAttribute)
					return false;
				break;

			case XmlTypeCode::ProcessingInstruction:
				if ($this->TypeCode != XmlTypeCode::ProcessingInstruction)
					return false;
				break;

			case XmlTypeCode::Comment:
				if ( $this->TypeCode != XmlTypeCode::Comment )
					return false;
				break;

			case XmlTypeCode::Text:
				if ( $this->TypeCode != XmlTypeCode::Text )
					return false;
				break;
		}
		if ( ! is_null( $this->SchemaType ) || ! is_null( $src->SchemaType ) )
		{
			if ( ! is_null( $this->SchemaType ) && ! is_null( $src->SchemaType ) )
			{
				if ( ! DOMSchemaType::IsDerivedFrom( $this->SchemaType, $src->SchemaType, XmlSchemaDerivationMethod::Empty_ ) )
					return false;
			}
			else
				return false;
		}

		if ( $this->Cardinality != $src->Cardinality)
		{
			if ( ( $this->Cardinality == XmlTypeCardinality::ZeroOrOne || $this->Cardinality == XmlTypeCardinality::ZeroOrMore ) &&
				 ($src->Cardinality == XmlTypeCardinality::One || $src->Cardinality == XmlTypeCardinality::OneOrMore))
				return false;

			if ( $this->Cardinality == XmlTypeCardinality::One && $src->Cardinality == XmlTypeCardinality::OneOrMore)
				return false;
		}
		return true;
	}

	/**
	 * Equals
	 * @param object $obj
	 * @return bool
	 */
	public function Equals( $obj )
	{
		/**
		 * @var SequenceType $dest
		 */
		$dest = $obj;
		if ( ! is_null( $obj ) )
		{
			return $this->TypeCode == $dest->TypeCode &&
				$this->SchemaElement == $dest->SchemaElement &&
				$this->SchemaAttribute == $dest->SchemaAttribute &&
				$this->SchemaType == $dest->SchemaType &&
				$this->Cardinality == $dest->Cardinality;
		}
		return false;
	}

	/**
	 * Create
	 * @param string $name
	 * @return SequenceType
	 */
	public static function Create( $name )
	{
		if ( $name == "" || $name == "empty-sequence()")
			return null;
		else
		{
			/**
			 * @var XmlTypeCardinality $cardinality
			 */
			$cardinality = XmlTypeCardinality::One;
			if ( SchemaTypes::endsWith( $name, "?" ) )
			{
				$cardinality = XmlTypeCardinality::ZeroOrOne;
				$name = substr( $name, 1, strlen( $name ) - 1 );
			}
			else if ( SchemaTypes::endsWith( $name,"+") )
			{
				$cardinality = XmlTypeCardinality::OneOrMore;
				$name = substr( $name, 1, strlen( $name ) - 1 );
			}
			else if ( SchemaTypes::endsWith( $name, "*" ) )
			{
				$cardinality = XmlTypeCardinality::ZeroOrMore;
				$name = substr( $name, 1, strlen( $name ) - 1 );
			}
			if ( $name == "xs:AnyAtomicType" )
			{
				return SequenceType::WithSchemaTypeWithCardinality( XmlSchema::$AnyAtomicType /* DOMSchemaType::GetBuiltInSimpleTypeByTypecode( XmlTypeCode::AnyAtomicType ) */, $cardinality );
			}
			else if ( $name == "item()" )
			{
				return SequenceType::WithTypeCodeAndCardinality( XmlTypeCode::Item, $cardinality);
			}
			else if ( $name == "node()" )
			{
				return SequenceType::WithTypeCodeAndCardinality( XmlTypeCode::Node, $cardinality );
			}
			else
			{
				/**
				 * @var QName $qname
				 */
				$qname = \lyquidity\xml\qname( $name, array( SCHEMA_PREFIX => SCHEMA_NAMESPACE ) + SchemaTypes::getInstance()->getProcessedSchemas() );
				$prefix = $qname->prefix;
				$localName = $qname->localName;

				if ( $prefix != SCHEMA_PREFIX && $prefix != SCHEMA_PREFIX_ALTERNATIVE )
				{
					throw new ArgumentException( "$name" );
				}

				$schemaType = DOMSchemaType::GetBuiltInSimpleTypeByQName( $qname );
				if ( is_null( $schemaType ) )
				{
					throw new ArgumentException( "Failed to create a built-in schema type with name: $name" );
				}

				return SequenceType::WithSchemaTypeWithCardinality( $schemaType, $cardinality );
			}
		}
	}

	/**
	 * Unit test
	 */
	public static function test()
	{
	}
}

?>
