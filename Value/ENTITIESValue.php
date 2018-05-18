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

namespace lyquidity\XPath2\Value;

use lyquidity\XPath2\DOM\XmlSchema;
use lyquidity\xml\interfaces\IXmlSchemaType;
use lyquidity\xml\exceptions\ArgumentException;

/**
 * ENTITIESValue (public)
 */
class ENTITIESValue implements IXmlSchemaType
{
	public static $CLASSNAME = "lyquidity\XPath2\Value\ENTITIESValue";

	/**
	 * Constructor
	 * @param array $value
	 */
	public  function __construct( $value )
	{
		if ( is_null( $value ) || ! is_array( $value ) )
	        throw new ArgumentException("$value");
		$this->ValueList = $value;
	}

	/**
	 * @var array $ValueList
	 */
	public  $ValueList;

	/**
	 * Returns a schema type for the proxy value
	 * @return XmlSchemaType
	 */
	public function getSchemaType()
	{
		return XmlSchema::$ENTITIES;
	}

	/**
	 * Returns the contained value
	 * @return DayTimeDurationValue
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
		if ( ! $obj instanceof ENTITIESValue )
		{
			return false;
		}

		/**
		 * @var ENTITIESValue $other
		 */
	    $other = $obj;
	    if ( count( $other->ValueList ) != count( $this->ValueList ) ) return false;
		return count( array_diff( $other->ValueList, $this->ValueList ) ) == 0;
	}

	/**
	 * ToString
	 * @return string
	 */
	public function ToString()
	{
		return implode( " ", $this->ValueList );
	}


	/**
	 * Return a stringified version of the object
	 * @return string
	 */
	public function __toString()
	{
		return $this->ToString();
	}

	public static function tests()
	{
		$entities1 = new ENTITIESValue( array( "a", "b", "c" ) );
		$entities2 = new ENTITIESValue( array( "a", "b", "c" ) );
		$entities3 = new ENTITIESValue( array( "x", "y", "z" ) );
		$entities4 = new ENTITIESValue( array( "a", "b", "c", "d" ) );

		echo "{$entities1->ToString()}\n";

		$result = $entities1->Equals( $entities1 ); // True
		$result = $entities1->Equals( $entities2 ); // True
		$result = $entities1->Equals( $entities3 ); // False
		$result = $entities1->Equals( $entities4 ); // False
	}

}

?>
