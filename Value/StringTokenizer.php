<?php
/**
 * XPath 2.0 for PHP
 *  _					   _	 _ _ _
 * | |   _   _  __ _ _   _(_) __| (_) |_ _   _
 * | |  | | | |/ _` | | | | |/ _` | | __| | | |
 * | |__| |_| | (_| | |_| | | (_| | | |_| |_| |
 * |_____\__, |\__, |\__,_|_|\__,_|_|\__|\__, |
 *	     |___/	  |_|					  |___/
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

/**
 * StringTokenizer (private)
 * This class is not required.  It was need in the C# implementation to parse
 * duration values like P1Y2M but in PHP this is handled by the DateInterval class
 */
class StringTokenizer
{
	/**
	 * _text
	 * @var string $_text
	 */
	private $_text;

	/**
	 * _offset
	 * @var int $_offset
	 */
	private $_offset;

	/**
	 * Constructor
	 * @param string $text
	 */
	public function __construct( $text )
	{
		$this->_text = $text;
	}

	/**
	 * Token
	 * @var int $Token
	 */
	public $Token;

	/**
	 * Value
	 * @var string $Value
	 */
	public $Value;

	/**
	 * LineCount
	 * @var int $LineCount
	 */
	public $LineCount;

	/**
	 * Return the offset value
	 * @return int
	 */
	public function getOffset()
	{
		return $this->_offset;
	}

	/**
	 * TokenInt
	 * @var int $TokenInt = 1
	 */
	public const TokenInt = 1;

	/**
	 * ToString
	 * @return string
	 */
	public function ToString()
	{
		return $this->_text;
	}

	/**
	 * NextToken
	 * @return int
	 */
	public function NextToken()
	{
		$anchor = $this->_offset;
		$ch = '\0';
		$this->Value = "";
		while ( $this->_offset < strlen( $this->_text ) )
		{
			$ch = $this->_text[ $this->_offset ];
			if ( preg_match( "/\s/", $ch ) && $ch != '\n' && $ch != '\r' )
				$this->_offset++;
			else
				break;
		}
		switch ( $ch )
		{
			case '\0':

				$this->Token = $ch;
				break;

			case '\n':

				$this->_offset++;
				if ($this->_offset < strlen( $this->_text ) - 1 && $this->_text[ $this->_offset ] == '\r' )
					$this->_offset++;
				$this->LineCount++;
				$this->Token = $ch;
				break;

			case '\r':

				$this->_offset++;
				if ( $this->_offset < strlen( $this->_text ) - 1 && $this->_text[ $this->_offset ] == '\n')
					$this->_offset++;
				$this->LineCount++;
				$this->Token = '\n';
				break;

			default:

				if ( is_numeric( $ch ) )
				{
					$sb = array();
					while ( $this->_offset < strlen( $this->_text ) )
					{
						$ch = $this->_text[ $this->_offset ];
						if ( ! is_numeric( $ch ) )
							break;

						$sb[] = $ch;
						$this->_offset++;
					}
					$this->Token = StringTokenizer::TokenInt;
					$this->Value = implode( "", $sb );
				}
				else
				{
					$this->Token = $ch;
					$this->_offset++;
				}
				break;

		}
		return $this->Token;
	}

	/**
	 * SkipTo
	 * @param string $ch
	 * @return void
	 */
	public function SkipToChar( $ch )
	{
		$this->SkipToArray( array( $ch ) );
	}

	/**
	 * SkipTo
	 * @param array $charset
	 * @return void
	 */
	public function SkipToArray( $charset )
	{
		$this->SkipToString( implode( "", $charset ) );
	}

	/**
	 * SkipTo
	 * @param string $charset
	 * @return void
	 */
	public function SkipToString( $charset )
	{

		$anchor = $this->_offset;
		while ( $this->Token != 0 && strpos( $charset, $this->Token ) == -1 )
			$this->NextToken();
		$this->Value = substr( $this->_text, $anchor, $this->_offset - $anchor );
	}

	/**
	 * SkipToEOL
	 * @return string
	 */
	public function SkipToEOL()
	{
		if ( $this->_offset == strlen( $this->_text ) )
			return "";

		/**
		 * @var int $anchor
		 */
		$anchor = $this->_offset;
		while ( $this->_offset < strlen( $this->_text ) )
		{
			$ch = $this->_text[ $this->_offset ];
			if ( $ch == '\n' )
			{
				$this->_offset++;
				if ( $this->_offset < strlen( $this->_text ) && $this->_text[ $this->_offset ] == '\r' )
					$this->_offset++;
				$this->LineCount++;
				$this->Token = '\n';
				return substr( $this->_text, $anchor, $this->_offset - $anchor );
			}
			else if ( $ch == '\r' )
			{
				$this->_offset++;
				if ( $this->_offset < strlen( $this->_text ) && $this->_text[ $this->_offset ] == '\n' )
					$this->_offset++;
				$this->LineCount++;
				$this->Token = '\n';
				return substr( $this->_text, $anchor, $this->_offset - $anchor );
			}
			else
				$this->_offset++;
		}
		return substr( $this->_text, $anchor );
	}

}

?>
