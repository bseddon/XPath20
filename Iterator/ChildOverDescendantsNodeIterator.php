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

namespace lyquidity\XPath2\Iterator;

use lyquidity\xml\xpath\XPathNodeType;
use lyquidity\xml\MS\XmlQualifiedNameTest;
use lyquidity\xml\xpath\XPathNavigator;
use lyquidity\XPath2\SequenceType;
use lyquidity\XPath2\Properties\Resources;
use lyquidity\XPath2\Iterator\ChildOverDescendantsNodeIterator\NodeTest;
use lyquidity\xml\xpath\XPathItem;
use lyquidity\XPath2\XPath2NodeIterator;
use lyquidity\XPath2\XPath2Context;
use lyquidity\XPath2\XPath2Exception;

/**
 * ChildOverDescendantsNodeIterator (final)
 */
class ChildOverDescendantsNodeIterator extends XPath2NodeIterator implements \Iterator
{
	/**
	 * @var XPathNodeType $kind
	 */
	private  $kind;

	/**
	 * @var XPath2Context $context
	 */
	private  $context;

	/**
	 * @var array $nodeTest NodeTest[]
	 */
	private  $nodeTest;

	/**
	 * @var NodeTest $lastTest
	 */
	private  $lastTest;

	/**
	 * @var XPath2NodeIterator $iter
	 */
	private  $iter;

	/**
	 * @var XPathNavigator $curr
	 */
	private  $curr;

	/**
	 * Constructor
	 */
	public  function __construct() {}

	/**
	 * fromParts
	 * @param XPath2Context $context
	 * @param array $nodeTest NodeTest[]
	 * @param XPath2NodeIterator $iter
	 */
	public static function fromParts( $context, $nodeTest, $iter )
	{
		$result = new ChildOverDescendantsNodeIterator();
		$result->fromChildOverDescendantsNodeIteratorParts( $context, $nodeTest, $iter );
		return $result;
	}

	/**
	 * Constructor
	 * @param XPath2Context $context
	 * @param array $nodeTest NodeTest[]
	 * @param XPath2NodeIterator $iter
	 */
	protected function fromChildOverDescendantsNodeIteratorParts( $context, $nodeTest, $iter )
	{
		$this->context = $context;
		$this->nodeTest = $nodeTest;
		$this->iter = $iter;
		$this->lastTest = $nodeTest[ count( $nodeTest ) - 1 ];
		$this->kind = XPathNodeType::All;
		if ( isset( $this->lastTest->nameTest ) || ( isset( $this->lastTest->typeTest ) && $this->lastTest->typeTest->GetNodeKind() == XPathNodeType::Element ) )
		{
			$this->kind = XPathNodeType::Element;
		}
	}

	/**
	 * Constructor
	 * @param ChildOverDescendantsNodeIterator $src
	 */
	private function AssignFrom( $src )
	{
		$this->context = $src->context;
		$this->nodeTest = $src->nodeTest;
		$this->iter = $src->iter->CloneInstance();
		$this->lastTest = $src->lastTest;
		$this->kind = $src->kind;
	}

	/**
	 * Clone
	 * @return XPath2NodeIterator
	 */
	public function CloneInstance()
	{
		$result = new ChildOverDescendantsNodeIterator();
		$result->AssignFrom( $this );
		return $result;
	}

	/**
	 * CreateBufferedIterator
	 * @return XPath2NodeIterator
	 */
	public function CreateBufferedIterator()
	{
		return new BufferedNodeIterator( $this->Clone() );
	}

	/**
	 * TestItem
	 * @param XPathNavigator $nav
	 * @param NodeTest $nodeTest
	 * @return bool
	 */
	private function TestItem( $nav, $nodeTest )
	{
		/**
		 * @var XmlQualifiedNameTest $nameTest
		 */
		$nameTest = isset( $nodeTest->nameTest ) ? $nodeTest->nameTest : null;

		/**
		 * @var SequenceType $typeTest
		 */
		$typeTest = isset( $nodeTest->typeTest ) ? $nodeTest->typeTest : null;

		if ( ! is_null( $nameTest ) )
		{
			return	( $nav->getNodeType() == XPathNodeType::Element || $nav->getNodeType() == XPathNodeType::Attribute ) &&
					( $nameTest->IsNamespaceWildcard() || $nameTest->namespaceURI == $nav->getNamespaceURI() ) &&
					( $nameTest->IsNameWildcard() || $nameTest->localName == $nav->getLocalName() );
		}
		else
		{
			if ( ! is_null( $typeTest ) )
			{
				return $typeTest->Match( $nav, $this->context );
			}
		}
		return true;
	}

	/**
	 * @var int $depth
	 */
	private  $depth;

	/**
	 * @var bool $accept
	 */
	private  $accept;

	/**
	 * @var XPathNavigator $nav
	 */
	private  $nav;

	/**
	 * @var int $sequentialPosition
	 */
	private  $sequentialPosition;

	/**
	 * NextItem
	 * @return XPathItem
	 */
	protected function NextItem()
	{
		MoveNextIter:

		if ( ! $this->accept )
		{
			if ( ! $this->iter->MoveNext() )
				return null;

			if ( ! $this->iter->getCurrent() instanceof XPathNavigator )
			{
				throw XPath2Exception::withErrorCodeAndParam( "XPTY0019", Resources::XPTY0019, $this->iter->getCurrent()->Value );
			}

			/**
			 * @var XPathNavigator $current
			 */
			$current = $this->iter->getCurrent();

			if ( is_null( $this->curr ) || ! $this->curr->MoveTo( $current ) )
			{
				$this->curr = $current->CloneInstance();
			}

			$this->sequentialPosition = 0;
			$this->accept = true;
		}

		MoveToFirstChild:

		if ( $this->curr->MoveToChild( $this->kind ) )
		{
			$this->depth++;
			goto TestItem;
		}

		MoveToNext:

		if ( $this->depth == 0 )
		{
			$this->accept = false;
			goto MoveNextIter;
		}

		if ( ! $this->curr->MoveToNext( $this->kind ) )
		{
			$this->curr->MoveToParent();
			$this->depth--;
			goto MoveToNext;
		}

		TestItem:

		if ( $this->depth < count( $this->nodeTest ) || ! $this->TestItem( $this->curr, $this->lastTest ) )
			goto MoveToFirstChild;

		if ( is_null( $this->nav ) || ! $this->nav->MoveTo( $this->curr ) )
		{
			$this->nav = $this->curr->CloneInstance();
		}

		for ( $k = count( $this->nodeTest ) - 2; $k >= 0; $k--)
		{
			if ( ! ( $this->nav->MoveToParent() && $this->TestItem( $this->nav, $this->nodeTest[ $k ] ) ) )
				goto MoveToFirstChild;
		}

		$this->sequentialPosition++;
		return $this->curr;
	}

	/**
	 * @var int $SequentialPosition
	 */
	public function getSequentialPosition()
	{
		return $this->sequentialPosition;
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
	 * Allow the iterators to be reset
	 */
	public function Reset()
	{
		parent::Reset();
		$this->nav = null;
		$this->accept = false;
		$this->sequentialPosition = 0;
		$this->depth = 0;

		$this->iter->Reset();
	}

}



?>
