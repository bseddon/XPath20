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

use lyquidity\xml\xpath\XPathItem;
use lyquidity\XPath2\Value\AnyUriValue;
use lyquidity\XPath2\Value\UntypedAtomic;
use lyquidity\XPath2\Proxy\ValueProxy;
use lyquidity\xml\xpath\XPathNodeIterator;
use lyquidity\xml\xpath\XPathNodeType;
use lyquidity\xml\xpath\XPathNavigator;
use lyquidity\XPath2\Iterator\ExprIterator;
use lyquidity\XPath2\Properties\Resources;
use lyquidity\xml\MS\XmlTypeCode;
use lyquidity\XPath2\Iterator\ChildNodeIterator;
use lyquidity\xml\MS\XmlReservedNs;

/**
 * TreeComparer (public)
 */
class TreeComparer
{
	/**
	 * context
	 * @var XPath2Context $context
	 */
	protected $context;

	/**
	 * The name of a collation to use when comparing items
	 * @var string
	 */
	protected  $collation = false;

	/**
	 * Constructor
	 * @param XPath2Context $context
	 * @param string $collation
	 */
	public function __construct( $context, $collation = null )
	{
		$this->context = $context;

		if ( is_null( $collation ) ) return;

		$this->$collation = $collation;

		if ( $collation == XmlReservedNs::collationCodepoint ) return;

		$result = setlocale( LC_COLLATE, $collation );
		if ( ! $result )
		{
			throw XPath2Exception::withErrorCodeAndParam( "FOCH0002", Resources::FOCH0002, $collation );
		}
	}

	/**
	 * excludeComments
	 * @var bool $excludeComments
	 */
	public $excludeComments = false;

	/**
	 * excludeWhitespace
	 * @var bool $excludeWhitespace
	 */
	public $excludeWhitespace = false;

	/**
	 * When true the comparison of element node values will taken into account the types so 001.234 will equal 1.234
	 * @var bool $useValueCompare
	 */
	public $useValueCompare = false;

	/**
	 * Set to true if untyped values can be tested as numbers if they are numeric
	 * @var bool $untypedCanBeNumeric
	 */
	public $untypedCanBeNumeric = false;

	// BMS 2018-03-21 Add because a query like:
	//	xfi:nodes-correspond( //t:P1[@id='t1'], //t:P1[@id='t2'] )
	// passes a pair of nodes including the attribute 'id' to the nodes-correspond
	// function but by definition the id values are different
	//
	/**
	 * The attributeToIgnore function allows the caller to define the selection attribute that should not affect equality.
	 * @var mixed
	 */
	public $attributeToIgnore = null;

	/**
	 * TextEqual
	 * @param string $a
	 * @param string $b
	 * @return bool
	 */
	protected function TextEqual( $a, $b )
	{

		if ( $this->excludeWhitespace )
			return strcasecmp( CoreFuncs::Normalizespace( $a ), CoreFuncs::Normalizespace( $b ) ) == 0;
		else
			return strcasecmp( $a, $b ) == 0;
	}

	/**
	 * Test if two items are equal
	 * @param XPathItem $item1
	 * @param XPathItem $item2
	 * @return bool
	 */
	protected function ItemEqual( $item1, $item2 )
	{
		$xTypeCode = $item1->getSchemaType()->TypeCode;
		$yTypeCode = $item2->getSchemaType()->TypeCode;
		$types = array( XmlTypeCode::Decimal, XmlTypeCode::Float, XmlTypeCode::Double, XmlTypeCode::Integer );

		$x = $item1->GetTypedValue();
		// BMS 2018-01-19	Could be numeric
		//					Added to support typed member comparisons in FactVariables
		if ( $this->untypedCanBeNumeric && $x instanceof UntypedAtomic )
		{
			if ( is_numeric( $x->getValue() ) )
			{
				$x = floatval( $x->getValue() );
			}
		}
		if ( $x instanceof UntypedAtomic || $x instanceof AnyUriValue )
		{
			// Special case
			// If $x is AnyUriValue or the attribute name is 'scheme' then
			// normalize by making sure the scheme starts with a scheme
			// such as http:// https:// ftp:// etc
			$special = /* $x instanceof AnyUriValue || */ ( $item1 instanceof XPathNavigator && $item1->getLocalName() == 'scheme' );
			$x = $x->ToString();
			$xTypeCode = XmlTypeCode::String;

			if ( $special && strpos( $x, "://" ) === false )
			{
				$x = "http://" . $x;
			}
		}

		$y = $item2->GetTypedValue();
		// BMS 2018-01-19	Could be numeric
		//					Added to support typed member comparisons in FactVariables
		if ( $this->untypedCanBeNumeric && $y instanceof UntypedAtomic )
		{
			if ( is_numeric( $y->getValue() ) )
			{
				$y = floatval( $y->getValue() );
			}
		}
		if ( $y instanceof UntypedAtomic || $y instanceof AnyUriValue )
		{
			// Special case
			// If $y is AnyUriValue or the attribute name is 'scheme' then
			// normalize by making sure the scheme starts with a scheme
			// such as http:// https:// ftp:// etc
			$special = /* $y instanceof AnyUriValue || */( $item2 instanceof XPathNavigator && $item2->getLocalName() == 'scheme' );
			$y = $y->ToString();
			$yTypeCode = XmlTypeCode::String;

			if ( $special && strpos( $y, "://" ) === false )
			{
				$y = "http://" . $y;
			}
		}

		if ( $this->useValueCompare )
		{
			if ( is_string( $x ) )
			{
				$x = trim( $x );
			}
		}

		if ( $this->useValueCompare )
		{
			if ( is_string( $y ) )
			{
				$y = trim( $y );
			}
		}

		if ( is_double( $x ) && is_nan( $x ) && is_double( $y ) && is_nan( $y ) )
		{
			return true;
		}

		if ( is_double( $x ) && is_infinite( $x ) && is_double( $y ) && is_infinite( $y ) )
		{
			return true;
		}

		if ( $xTypeCode != $yTypeCode )
		{
			// Handle the exceptions
			$exceptions = (
				( $xTypeCode == XmlTypeCode::Integer && in_array( $yTypeCode, $types ) ) ||
			 	( $yTypeCode == XmlTypeCode::Integer && in_array( $xTypeCode, $types ) ) ||
				( $xTypeCode == XmlTypeCode::Decimal && in_array( $yTypeCode, $types ) ) ||
				( $yTypeCode == XmlTypeCode::Decimal && in_array( $xTypeCode, $types ) )
			);
			if ( ! $exceptions )
			{
				return false;
			}
		}

		if ( ! is_null( $this->collation ) && is_string( $x ) && is_string( $y ) )
		{
			if ( $this->collation == XmlReservedNs::collationCodepoint )
			{
				$x = \normalizer_normalize( $x, \Normalizer::FORM_C );
				$y = \normalizer_normalize( $y, \Normalizer::FORM_C );

				return strcmp( $x, $y );
			}
			else
			{
				return strcoll( $x, $y ) == 0;
			}
		}

		if ( ! is_object( $x ) && ! is_object( $y ) )
		{
			if ( $x == $y )
			{
				return true;
			}
		}
		else if ( is_object( $x ) && is_object( $y ) && method_exists( $x, "Equals" ) && method_exists( $y, "Equals" ) )
		{
			if ( $x->Equals( $y ) )
			{
				return true;
			}
		}

		$res = null;
		$result = ValueProxy::EqValues( $x, $y, $res) && $res;
		return $result;

	}

	/**
	 * NodeEqual
	 * @param XPathNavigator $nav1
	 * @param XPathNavigator $nav2
	 * @return bool
	 */
	protected function NodeEqual( $nav1, $nav2 )
	{
		if ( $nav1->getNodeType() != $nav2->getNodeType() )
		{
			return false;
		}

		switch ( $nav1->getNodeType() )
		{
			case XPathNodeType::Element:
				return $this->ElementEqual( $nav1, $nav2 );

			case XPathNodeType::Attribute:
				return $this->AttributeEqual( $nav1, $nav2 );

			case XPathNodeType::Text:
			case XPathNodeType::SignificantWhitespace:
			case XPathNodeType::Whitespace:
			case XPathNodeType::Comment:

				if ( $this->useValueCompare )
				{
					if ( $nav1->getValue() == $nav2->getValue() )
					{
						// return true;
					}
					$value = $nav1->getValue();
					$result = $this->ItemEqual( $nav1, $nav2);
					return $result;
				}
				else
				{
					return $this->TextEqual( $nav1->getValue(), $nav2->getValue() );
				}

			case XPathNodeType::ProcessingInstruction:
				return $this->ProcessingInstructionEqual( $nav1, $nav2 );

			default:
				return $this->DeepEqualByNavigator( $nav1, $nav2 );
		}
	}

	/**
	 * ElementEqual
	 * @param XPathNavigator $nav1
	 * @param XPathNavigator $nav2
	 * @return bool
	 */
	protected function ElementEqual( $nav1, $nav2 )
	{
		if ( $nav1->getLocalName() != $nav2->getLocalName() || $nav1->getNamespaceURI() != $nav2->getNamespaceURI() )
		{
			return false;
		}
		if ( ! $this->ElementAttributesEqual( $nav1->CloneInstance(), $nav2->CloneInstance() ) )
		{
			return false;
		}

		return $this->DeepEqualByNavigator( $nav1, $nav2 );
	}

	/**
	 * ElementAttributesEqual
	 * @param XPathNavigator $nav1
	 * @param XPathNavigator $nav2
	 * @return bool
	 */
	protected function ElementAttributesEqual( $nav1, $nav2 )
	{
		if ( $nav1->getHasAttributes() != $nav2->getHasAttributes() )
		{
			return false;
		}

		if ( ! $nav1->getHasAttributes() ) return true;

		// Check they have the same number of attributes
		$flag1 = $nav1->MoveToFirstAttribute();
		$flag2 = $nav2->MoveToFirstAttribute();
		while ( $flag1 && $flag2 )
		{
			$flag1 = $nav1->MoveToNextAttribute();
			$flag2 = $nav2->MoveToNextAttribute();
		}

		if ( $flag1 != $flag2 ) return false;

		$nav1->MoveToParent();
		$nav2->MoveToParent();

		for ( $flag3 = $nav1->MoveToFirstAttribute(); $flag3; $flag3 = $nav1->MoveToNextAttribute() )
		{
			$flag4 = $nav2->MoveToFirstAttribute();
			while ( $flag4 )
			{
				if ( $this->AttributeEqual( $nav1, $nav2 ) )
				{
					break;
				}
				$flag4 = $nav2->MoveToNextAttribute();
			}
			$nav2->MoveToParent();
			if ( ! $flag4)
			{
				$nav1->MoveToParent();
				return false;
			}
		}

		// $nav1->MoveToParent();
		return true;
	}

	/**
	 * ProcessingInstructionEqual
	 * @param XPathNavigator $nav1
	 * @param XPathNavigator $nav2
	 * @return bool
	 */
	protected function ProcessingInstructionEqual( $nav1, $nav2 )
	{
		return $nav1->getLocalName() == $nav2->getLocalName() && $nav1->getValue() == $nav2->getValue();
	}

	/**
	 * AttributeEqual
	 * @param XPathNavigator $nav1
	 * @param XPathNavigator $nav2
	 * @return bool
	 */
	protected function AttributeEqual($nav1, $nav2)
	{
		if ( $nav1->getLocalName() != $nav2->getLocalName() || $nav1->getNamespaceURI() != $nav2->getNamespaceURI() ) return false;
		// BMS 2018-03-21 Add because a query like:
		//	xfi:nodes-correspond( //t:P1[@id='t1'], //t:P1[@id='t2'] )
		// passes a pair of nodes including the attribute 'id' to the nodes-correspond
		// function but by definition the id values are different
		// The attributeToIgnore function allows the caller to define the selection attribute that should not affect equality.
		if ( ! is_null( $this->attributeToIgnore  ) )
		{
			if ( $nav1->getLocalName() == $this->attributeToIgnore ) return true;
		}
		return $this->ItemEqual( $nav1, $nav2 );
		// return $nav1->GetTypedValue() == $nav2->GetTypedValue();
	}

	/**
	 * DeepEqualByNavigator Iterate over nodes in order-sensitive manner
	 * @param XPathNavigator $nav1
	 * @param XPathNavigator $nav2
	 * @return bool
	 */
	public function DeepEqualByNavigator( $nav1, $nav2 )
	{
		/**
		 * @var XPathNodeIterator $iter1
		 * @var XPathNodeIterator $iter2
		 */
		// $iter1 = $nav1->SelectChildrenByType( XPathNodeType::All );
		// $iter2 = $nav2->SelectChildrenByType( XPathNodeType::All );

		$nodeTest = null; // XmlQualifiedNameTest::create();
		$iter1 = ChildNodeIterator::fromNodeTest( $this->context, $nodeTest, XPath2NodeIterator::Create( $nav1 ) );
		$iter1->excludeComments = true; // $this->excludeComments;
		$iter1->excludeWhitespace = $this->excludeWhitespace;

		$iter2 = ChildNodeIterator::fromNodeTest( $this->context, $nodeTest, XPath2NodeIterator::Create( $nav2 ) );
		$iter2->excludeComments = true; // $this->excludeComments;
		$iter2->excludeWhitespace = $this->excludeWhitespace;

		$leftCount = $iter1->getCount();
		$rightCount = $iter2->getCount();

		if ( $leftCount != $rightCount )
		{
			// Maybe one or both iterators have skipped comments and all the nodes are text nodes.
			// If this is the case join them to see if they compare.
			$leftTextCount = $iter1->getCountType( XPathNodeType::Text );
			$rightTextCount = $iter2->getCountType( XPathNodeType::Text );

			if ( $leftTextCount != $leftCount ) return false;
			if ( $rightTextCount != $rightCount ) return false;

			// By this time all the nodes are text so see if when concatenated they are equal
			$text1 = ExtFuncs::StringJoin( $iter1, "" );
			$text2 = ExtFuncs::StringJoin( $iter2, "" );

			return $text1 == $text2;
		}

		$leftElementCount = $iter1->getCountType( XPathNodeType::Element );

		if ( $leftElementCount )
		{
			$textMatched = $nav1->getValue() == $nav2->getValue();

			return $this->DeepEqualByIterator( $iter1, $iter2, $textMatched );
		}

		if ( ! $this->ItemEqual( $nav1, $nav2 ) )
		{
			return false;
		}
		return true;
	}

	/**
	 * DeepEqualByIterator Alternative way to iterate over nodes in order-sensitive manner
	 * @param XPath2NodeIterator $iter1
	 * @param XPath2NodeIterator $iter2
	 * @param bool $elementsOnly
	 * @return bool
	 */
	public function DeepEqualByIterator( $iter1, $iter2, $elementsOnly = false )
	{
		$iter1 = $iter1->CloneInstance();
		$iter2 = $iter2->CloneInstance();
		$flag1 = false;
		$flag2 = false;

		do
		{
			$flag1 = $iter1->MoveNext();
			$flag2 = $iter2->MoveNext();
			if ( $flag1 != $flag2 ) return false;

			if ($flag1 && $flag2)
			{
				$iter1Current = $iter1->getCurrent();
				$iter2Current = $iter2->getCurrent();

				// If one of the iterators is a ForIterator and it returns an ExprIterator handle it
				if ( $iter1Current instanceof ExprIterator || $iter2Current instanceof ExprIterator )
				{
					if ( ! $iter1Current instanceof ExprIterator ) $iter1Current = $iter1;
					if ( ! $iter2Current instanceof ExprIterator ) $iter2Current = $iter2;
					return $this->DeepEqualByIterator( $iter1Current->CloneInstance(), $iter2Current->CloneInstance() );
				}

				if ( $iter1Current->getIsNode() != $iter2Current->getIsNode() )
				{
					return false;
				}
				else
				{
					if ( $iter1Current->getIsNode() && $iter2Current->getIsNode() )
					{
						if ( $elementsOnly && $iter1Current->getNodeType() != XPathNodeType::Element )
						{
							continue;
						}
						if ( ! $this->NodeEqual( $iter1Current, $iter2Current ) )
						{
							return false;
						}
					}
					else
					{
						if ( ! $this->ItemEqual( $iter1Current, $iter2Current ) )
						{
							return false;
						}
					}
				}
			}
		}
		while ( $flag1 && $flag2 );
		return true;
	}

}

?>
