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

namespace lyquidity\XPath2\ExtFuncs;

use lyquidity\XPath2\Value\AnyUriValue;
use lyquidity\XPath2\Value\UntypedAtomic;
use lyquidity\XPath2\Proxy\ValueProxy;
use lyquidity\xml\interfaces\IComparer;
use lyquidity\xml\MS\XmlTypeCode;
use lyquidity\XPath2\XPath2Item;

/**
 * DistinctComparer ( private )
 */
class DistinctComparer implements IComparer
{
	/**
	 * A collection identifier
	 * @var string
	 */
	private $collation = null;

	/**
	 * A set of analogous numeric types
	 * @var array
	 */
	private static $numericTypes = array();

	/**
	 * A set of analogous duration types
	 * @var array
	 */
	private static $durationTypes = array();

	/**
	 * Constructor
	 * @param string $collation
	 */
	public  function __construct( $collation = null )
	{
		$this->collation = $collation;

		self::$numericTypes = array( XmlTypeCode::Decimal, XmlTypeCode::Float, XmlTypeCode::Double, XmlTypeCode::Integer );
		self::$durationTypes = array( XmlTypeCode::YearMonthDuration, XmlTypeCode::DayTimeDuration );
	}

	/**
	 * Compare
	 * @param object $a
	 * @param object $b
	 * @return int
	 */
	function Compare( $a, $b )
	{
		if ( $a instanceof UntypedAtomic || $a instanceof AnyUriValue )
		{
			$a = $a->ToString();
		}

		if ( $b instanceof UntypedAtomic || $b instanceof AnyUriValue )
		{
			$b = $b->ToString();
		}

		if ( is_float( $a ) && is_nan( $a ) && is_float( $b ) && is_nan( $b )  )
		{
			return 0;
		}

		if ( ! is_null( $this->collation ) && is_string( $a ) && is_string( $b ) )
		{
			if ( $this->collation == "http://www.w3.org/2005/xpath-functions/collation/codepoint" )
			{
				$a = \normalizer_normalize( $a, \Normalizer::FORM_C );
				$b = \normalizer_normalize( $b, \Normalizer::FORM_C );

				return strcmp( $a, $b );
			}
			else
			{
				return strcoll( $a, $b ) == 0;
			}
		}

		if ( true )
		{
			$aTypeCode = $a instanceof XPath2Item ? $a->getSchemaType()->TypeCode : ( is_string( $a ) ? XmlTypeCode::String : XmlTypeCode::AnyAtomicType );
			$bTypeCode = $b instanceof XPath2Item ? $b->getSchemaType()->TypeCode : ( is_string( $b ) ? XmlTypeCode::String : XmlTypeCode::AnyAtomicType );
			$numericTypes =& DistinctComparer::$numericTypes;
			$durationTypes =& DistinctComparer::$durationTypes;

			if ( $aTypeCode != $bTypeCode )
			{
				// Handle the exceptions
				$exceptions = (
					( $aTypeCode == XmlTypeCode::Integer && in_array( $bTypeCode, $numericTypes ) ) ||
				 	( $bTypeCode == XmlTypeCode::Integer && in_array( $aTypeCode, $numericTypes ) ) ||
					( $aTypeCode == XmlTypeCode::Decimal && in_array( $bTypeCode, $numericTypes ) ) ||
					( $bTypeCode == XmlTypeCode::Decimal && in_array( $aTypeCode, $numericTypes ) ) ||
					( $aTypeCode == XmlTypeCode::Double && in_array( $bTypeCode, $numericTypes ) ) ||
					( $bTypeCode == XmlTypeCode::Double && in_array( $aTypeCode, $numericTypes ) ) ||
					( $aTypeCode == XmlTypeCode::Float && in_array( $bTypeCode, $numericTypes ) ) ||
					( $bTypeCode == XmlTypeCode::Float && in_array( $aTypeCode, $numericTypes ) ) ||

					( $aTypeCode == XmlTypeCode::YearMonthDuration && in_array( $bTypeCode, $durationTypes ) ) ||
					( $bTypeCode == XmlTypeCode::YearMonthDuration && in_array( $aTypeCode, $durationTypes ) ) ||
					( $aTypeCode == XmlTypeCode::DayTimeDuration && in_array( $bTypeCode, $durationTypes ) ) ||
					( $bTypeCode == XmlTypeCode::DayTimeDuration && in_array( $aTypeCode, $durationTypes ) )
				);
				if ( ! $exceptions )
				{
					return 1;
				}
			}

			// Check for NaN in numeric XPath2Item
			if ( in_array( $aTypeCode, $numericTypes ) && in_array( $bTypeCode, $numericTypes ) )
			{
				$aValue = $a->getTypedValue();
				$bValue = $b->getTypedValue();

				if ( ! is_object( $aValue ) && ! is_object( $bValue ) && is_nan( $aValue ) && is_nan( $bValue ) )
				{
					return 0;
				}
			}

			if ( in_array( $aTypeCode, $durationTypes ) && in_array( $bTypeCode, $durationTypes ) )
			{
				$a = $a->getTypedValue();
				$b = $b->getTypedValue();
			}
		}

		if ( ! is_object( $a ) && ! is_object( $b ) )
		{
			if ( $a == $b )
			{
				return 0;
			}
		}
		else if ( is_object( $a ) && is_object( $b ) && method_exists( $a, "Equals" ) && method_exists( $b, "Equals" ) )
		{
			if ( $a->Equals( $b ) )
			{
				return 0;
			}
		}

		$res = 0;
		if ( ValueProxy::EqValues( $a, $b, $res ) && $res )
    		return 0;

		if ( ValueProxy::GtValues( $a, $b, $res ) && $res )
			return 1;

		return -1;
	}

}

