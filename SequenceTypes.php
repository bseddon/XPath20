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

namespace lyquidity\XPath2;

use lyquidity\xml\MS\XmlTypeCode;
use lyquidity\xml\MS\XmlTypeCardinality;

/**
 * Defines a collection of sequence types
 */
class SequenceTypes
{
	/**
	 * Static constructor
	 */
	public static function __static()
	{
		SequenceTypes::$Void = SequenceType::WithTypeCode( XmlTypeCode::None );
		SequenceTypes::$Item = SequenceType::WithTypeCode( XmlTypeCode::Item );
		SequenceTypes::$ItemS = SequenceType::WithTypeCodeAndCardinality( XmlTypeCode::Item, XmlTypeCardinality::ZeroOrMore );
		SequenceTypes::$Node = SequenceType::WithTypeCode( XmlTypeCode::Node );
		SequenceTypes::$ProcessingInstruction = SequenceType::WithTypeCode( XmlTypeCode::ProcessingInstruction );
		SequenceTypes::$Text = SequenceType::WithTypeCode( XmlTypeCode::Text );
		SequenceTypes::$Comment = SequenceType::WithTypeCode( XmlTypeCode::Comment );
		SequenceTypes::$Element = SequenceType::WithTypeCode( XmlTypeCode::Element );
		SequenceTypes::$Attribute = SequenceType::WithTypeCode( XmlTypeCode::Attribute );
		SequenceTypes::$Document = SequenceType::WithTypeCode( XmlTypeCode::Document );
		SequenceTypes::$Boolean = SequenceType::WithTypeCode( XmlTypeCode::Boolean );
		SequenceTypes::$AnyAtomicType = SequenceType::WithTypeCode( XmlTypeCode::AnyAtomicType );
		SequenceTypes::$AnyAtomicTypeO = SequenceType::WithTypeCodeAndCardinality( XmlTypeCode::AnyAtomicType, XmlTypeCardinality::ZeroOrOne );
		SequenceTypes::$Double = SequenceType::WithTypeCode( XmlTypeCode::Double );
		SequenceTypes::$Float = SequenceType::WithTypeCode( XmlTypeCode::Float );
		SequenceTypes::$Date = SequenceType::WithTypeCode( XmlTypeCode::Date );
		SequenceTypes::$Time = SequenceType::WithTypeCode( XmlTypeCode::Time );
		SequenceTypes::$DateTime = SequenceType::WithTypeCode( XmlTypeCode::DateTime );
		SequenceTypes::$StringX = SequenceType::WithTypeCodeAndCardinality (XmlTypeCode::String, XmlTypeCardinality::ZeroOrOne );
		SequenceTypes::$Int = SequenceType::WithTypeCode( XmlTypeCode::Int );
		SequenceTypes::$AnyUri = SequenceType::WithTypeCode( XmlTypeCode::AnyUri );
	}

	/**
	 * Void
	 * @var SequenceType $Void
	 */
	public static $Void;

	/**
	 * Item
	 * @var SequenceType $Item
	 */
	public static $Item;

	/**
	 * ItemS
	 * @var SequenceType $ItemS
	 */
	public static $ItemS;

	/**
	 * Node
	 * @var SequenceType $Node
	 */
	public static $Node;

	/**
	 * ProcessingInstruction
	 * @var SequenceType $ProcessingInstruction
	 */
	public static $ProcessingInstruction;

	/**
	 * Text
	 * @var SequenceType $Text
	 */
	public static $Text;

	/**
	 * Comment
	 * @var SequenceType $Comment
	 */
	public static $Comment;

	/**
	 * Element
	 * @var SequenceType $Element
	 */
	public static $Element;

	/**
	 * Attribute
	 * @var SequenceType $Attribute
	 */
	public static $Attribute;

	/**
	 * Document
	 * @var SequenceType $Document
	 */
	public static $Document;

	/**
	 * Boolean
	 * @var SequenceType $Boolean
	 */
	public static $Boolean;

	/**
	 * AnyAtomicType
	 * @var SequenceType $AnyAtomicType
	 */
	public static $AnyAtomicType;

	/**
	 * AnyAtomicTypeO
	 * @var SequenceType $AnyAtomicTypeO
	 */
	public static $AnyAtomicTypeO;

	/**
	 * Double
	 * @var SequenceType $Double
	 */
	public static $Double;

	/**
	 * Float
	 * @var SequenceType $Float
	 */
	public static $Float;

	/**
	 * Date
	 * @var SequenceType $Date
	 */
	public static $Date;

	/**
	 * Time
	 * @var SequenceType $Time
	 */
	public static $Time;

	/**
	 * DateTime
	 * @var SequenceType $DateTime
	 */
	public static $DateTime;

	/**
	 * StringX
	 * @var SequenceType $StringX
	 */
	public static $StringX;

	/**
	 * Int
	 * @var SequenceType $Int
	 */
	public static $Int;

	/**
	 * AnyUri
	 * @var SequenceType $AnyUri
	 */
	public static $AnyUri;
}

SequenceTypes::__static();
