<?php
/**
 * XPath 2.0 for PHP
 * 	_					   _	 _ _ _
 * | |   _   _  _  _ _   _(_) __| (_) |_ _   _
 * | | 	| | | |/ _` | | | | |/ _` | | __| | | |
 * | |__| |_| | (_| | |_| | | (_| | | |_| |_| |
 * |_____\__, |\__, |\__,_|_|\__,_|_|\__|\__, |
 *		 |___/	  |_|					 |___/
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

namespace lyquidity\XPath2\Iterator;

use lyquidity\XPath2\XPath2NodeIterator;
use lyquidity\XPath2\XPath2Context;
use lyquidity\XPath2\SequenceType;
use lyquidity\xml\xpath\XPathNavigator;
use lyquidity\xml\MS\XmlQualifiedNameTest;
use lyquidity\XPath2\Properties\Resources;
use lyquidity\xml\xpath\XPathNodeType;
use lyquidity\XPath2\SequenceTypes;
use lyquidity\xml\QName;
use lyquidity\XPath2\XPath2Exception;

/**
 * AxisNodeIterator (abstract)
 */
class AxisNodeIterator extends XPath2NodeIterator implements \Iterator
{
	public static $CLASSNAME = "lyquidity\XPath2\Iterator\AxisNodeIterator";

	/**
	 * @var XPath2Context $context
	 */
	protected $context;

	/**
	 * @var XmlQualifiedNameTest $nameTest
	 */
	protected $nameTest;

	/**
	 * @var SequenceType $typeTest
	 */
	protected $typeTest;

	/**
	 * @var bool $matchSelf
	 */
	protected $matchSelf;

	/**
	 * @var XPath2NodeIterator $iter
	 */
	protected $iter;

	/**
	 * @var XPathNavigator $curr
	 */
	protected $curr;

	/**
	 * @var int $sequentialPosition
	 */
	protected $sequentialPosition;

	/**
	 * @var bool $accept
	 */
	protected $accept;

	/**
	 * Constructor
	 * In the original this uses a protected modifier but PHP does not allow
	 * a child to have a protected modifier if the parent is public
	 */
	public function __construct() {}

	// /**
	//  * Constructor
	//  * @param XPath2Context $context
	//  * @param object $nodeTest
	//  * @param bool $matchSelf
	//  * @param XPath2NodeIterator $iter
	//  */
	// public static function fromParts( $context, $nodeTest, $matchSelf, $iter )
	// {
	// 	$result = new AxisNodeIterator();
	// 	$result->fromAxisNodeIteratorParts( $context, $nodeTest, $matchSelf, $iter );
	// 	return $result;
	// }

	/**
	 * fromAxisNodeIteratorParts
	 * @param XPath2Context $context
	 * @param object $nodeTest
	 * @param bool $matchSelf
	 * @param XPath2NodeIterator $iter
	 */
	protected function fromAxisNodeIteratorParts( $context, $nodeTest, $matchSelf, $iter )
	{
		$this->context = $context;
		if ( $nodeTest instanceof QName )
		{
			$this->nameTest = $nodeTest;
		}
		else if ( $nodeTest instanceof SequenceType && $nodeTest != SequenceTypes::$Node )
		{
			$this->typeTest = $nodeTest;
		}

		$this->matchSelf = $matchSelf;
		$this->iter = $iter;
	}

	/**
	 * CreateBufferedIterator
	 * @return XPath2NodeIterator
	 */
	public function CreateBufferedIterator()
	{
		return new BufferedNodeIterator( $this );
	}

	/**
	 * AssignFrom
	 * @param AxisNodeIterator $src
	 * @return void
	 */
	protected function AssignFrom( $src )
	{
		$this->context = $src->context;
		$this->typeTest = $src->typeTest;
		$this->nameTest = $src->nameTest;
		$this->matchSelf = $src->matchSelf;
		$this->iter = $src->iter->CloneInstance();
	}

	/**
	 * TestItem
	 * @return bool
	 */
	protected function TestItem()
	{
		if ( ! is_null( $this->nameTest ) )
		{
			return ( $this->curr->getNodeType() == XPathNodeType::Element || $this->curr->getNodeType() == XPathNodeType::Attribute ) &&
				( $this->nameTest->IsNamespaceWildcard() || $this->nameTest->namespaceURI == $this->curr->getNamespaceURI() ) &&
				( $this->nameTest->IsNameWildcard() || $this->nameTest->localName == $this->curr->getLocalName() );
		}
		else
			return is_null( $this->typeTest )
				? true
				: $this->typeTest->Match( $this->curr, $this->context );
	}

	/**
	 * Tests whether $node is an instance of XPathNavigator and throws an exception if not
	 * @param object $node
	 */
	protected function IsNode( $node )
	{
		if ( ! $node instanceof XPathNavigator )
		{
			throw XPath2Exception::withErrorCodeAndParam( "XPTY0019", Resources::XPTY0019, $node->getValue() );
		}
	}

	/**
	 * MoveNextIter
	 * @return bool
	 */
	protected function MoveNextIter()
	{
		if ( ! $this->iter->MoveNext() )
		{
			return false;
		}

		// if ( ! $this->iter->getCurrent() instanceof  XPathNavigator )
		// {
		// 	throw XPath2Exception::withErrorCodeAndParam( "XPTY0019", Resources::XPTY0019, $this->iter->getCurrent()->getValue() );
		// }
		$this->IsNode( $this->iter->getCurrent() );

		/**
		 * @var XPathNavigator $nav
		 */
		$nav = $this->iter->getCurrent();
		if ( is_null( $this->curr ) || ! $this->curr->MoveTo( $nav ) )
		{
			$this->curr = $nav->CloneInstance();
		}
		$this->sequentialPosition = 0;
		$this->accept = true;
		return true;
	}

	/**
	 * @var int $SequentialPosition
	 */
	public function getSequentialPosition()
	{
		return $this->sequentialPosition;
	}

	/**
	 * Allow the iterators to be reset
	 */
	public function Reset()
	{
		parent::Reset();
		if ( is_null( $this->iter ) ) return;
		$this->iter->Reset();
	}

	/**
	 * ResetSequentialPosition
	 * @return void
	 */
	public function ResetSequentialPosition()
	{
		$this->accept = false;
	}

	/**
	 * Returns the node test type if defined or null
	 * @return SequenceType
	 */
	public function getDestinationType()
	{
		return $this->typeTest;
	}
}



?>
