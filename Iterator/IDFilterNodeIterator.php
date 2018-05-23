<?php
/**
 * XPath 2.0 for PHP
 *  _					   _	 _ _ _
 * | |   _   _  __ _ _   _(_) __| (_) |_ _   _
 * | |  | | | |/ _` | | | | |/ _` | | __| | | |
 * | |__| |_| | (_| | |_| | | (_| | | |_| |_| |
 * |_____\__, |\__, |\__,_|_|\__,_|_|\__|\__, |
 *		 |___/	  |_|					 |___/
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

namespace lyquidity\XPath2\Iterator;

use lyquidity\XPath2\XPath2Context;
use lyquidity\XPath2\XPath2NodeIterator;
use lyquidity\xml\xpath\XPathItem;
use lyquidity\XPath2\CoreFuncs;
use lyquidity\XPath2\Value\NameValue;
use lyquidity\xml\MS\XmlQualifiedNameTest;
use lyquidity\XPath2\IContextProvider;
use lyquidity\XPath2\Properties\Resources;
use lyquidity\XPath2\Iterator\ChildOverDescendantsNodeIterator\NodeTest;
use lyquidity\XPath2\SequenceTypes;
use lyquidity\xml\schema\SchemaTypes;
use lyquidity\XPath2\XPath2Exception;

/**
 * DescendantNodeIterator (final)
 */
class IDFilterNodeIterator extends XPath2NodeIterator implements \Iterator
{


	/**
	 * A local copy of the context
	 * @var IContextProvider
	 */
	private $context;

	/**
	 * A comma separated list of IDs
	 * @var string
	 */
	private $IDRef;

	/**
	 * An iterator with the selected nodes
	 * @var XPath2NodeIterator
	 */
	private $iterator;

	/**
	 * The schema types instance
	 * @var SchemaTypes $types
	 */
	private $types;

	/**
	 * type
	 * @var bool $type False = ID, True - IDREF
	 */
	private $type;

	/**
	 * Constructor
	 * @param IContextProvider $context
	 * @param bool $type False = ID, True - IDREF
	 */
	public function __construct( $context, $type )
	{
		$this->context = $context;
		$this->type = $type;
		$this->types = SchemaTypes::getInstance();
	}

	/**
	 * Static Constructor
	 * @param XPath2Context $context
	 * @param XPath2NodeIterator $IDRef
	 * @param XPath2NodeIterator $node
	 * @param bool $type False = ID, True - IDREF
	 * @return IDFilterNodeIterator
	 */
	public static function fromNodeTest( $context, $IDRef, $node, $type )
	{
		if ( ! $IDRef instanceof XPath2NodeIterator )
		{
			return EmptyIterator::$Shared;
		}

		$IDRef = array_reduce( $IDRef->ToList(), function( $carry, $item ) {
			$items = explode( " ", $item->getValue() );
			foreach ( $items as $i ) array_push( $carry, $i );
			return $carry;
		} , array() );

		$IDRef = array_unique( $IDRef );

		foreach ( $IDRef as $idref )
		{
			if ( ! preg_match( "/" . NameValue::$nameChar . "*/", $idref ) )
			{
				throw XPath2Exception::withErrorCodeAndParam( "FODC0004", Resources::FODC0004, $idref );
			}
		}

		$test = new NodeTest( SequenceTypes::$Element );
		$iterator = ChildOverDescendantsNodeIterator::fromParts( $context, array( $test ), XPath2NodeIterator::Create( $node ) );

		$result = new IDFilterNodeIterator( $context, $type );
		$result->IDRef = $IDRef;
		$result->iterator = $iterator;
		return $result;
	}

	/**
	 * CloneInstance
	 * @return XPath2NodeIterator
	 */
	public function CloneInstance()
	{
		$result = new IDFilterNodeIterator( $this->context, $this->type );
		$result->IDRef = $this->IDRef;
		$result->iterator = $this->iterator->CloneInstance();
		$result->Reset();
		return $result;
	}

	/**
	 * NextItem
	 * @return XPathItem
	 */
	protected function NextItem()
	{
		// return null;
		$nodeTest = XmlQualifiedNameTest::Create();

		while ( true )
		{
			if ( ! $this->iterator->MoveNext() )
			{
				return null;
			}

			$node = $this->iterator->getCurrent();

			// An element with children cannot be an ID
			if ( $node->getHasChildren() ) continue;

			// Is this element a type?
			// $info = $node->getSchemaInfo();
			$type = $this->types->getElement( $node->LocalName, $node->Prefix == "" ? null : $node->Prefix );
			if ( $type )
			{
				return CoreFuncs::CloneInstance( $node );
			}
			else
			{
				// Look at the attributes
				if ( ! $node->getHasAttributes() ) continue;
				$attribs = AttributeNodeIterator::fromNodeTest( $this->context, $nodeTest, XPath2NodeIterator::Create( $node ) );
				foreach ( $attribs as $attribute )
				{
					$info = $attribute->getSchemaInfo();
					$type = $this->types->getAttribute( $attribute->LocalName, $info->Prefix == "" ? SCHEMA_PREFIX : $info->Prefix );
					if ( ! $type ) continue;
					foreach ( $type['types'] as $type )
					{
						if ( $type['prefix'] != SCHEMA_PREFIX ||
							 $type['name'] != ( $this->type ? "IDREF" : "ID" ) ||
							 ! in_array( $attribute->getValue(), $this->IDRef )
						) continue;
						return CoreFuncs::CloneInstance( $this->type ? $attribute : $node );
					}
					// if ( $attribute->LocalName == "anId" && in_array( $attribute->getValue(), $this->IDRef ) )
					// {
					// 	return CoreFuncs::CloneInstance( $node );
					// }
				}
			}
		}
	}

	/**
	 * Allow the iterators to be reset
	 */
	public function Reset()
	{
		parent::Reset();
		$this->iterator->Reset();
	}

	/**
	 * Return this iterator
	 * @return IDFilterNodeIterator
	 */
	public function getIterator()
	{
		return $this;
	}
}


?>
