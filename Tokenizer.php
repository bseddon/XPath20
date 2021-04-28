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

use lyquidity\XPath2\Properties\Resources;
use lyquidity\XPath2\Value\DecimalValue;
use lyquidity\XPath2\Value\Integer;
use lyquidity\xml\exceptions\InvalidCastException;

/**
 * Implements a tokenizer to parse a query statement and return tokens
 */
class LexerState
{
	const DefaultState = 0;
	const Operator = 1;
	const SingleType = 2;
	const ItemType = 3;
	const KindTest = 4;
	const KindTestForPi = 5;
	const CloseKindTest = 6;
	const OccurrenceIndicator = 7;
	const VarName = 8;
}

/**
 * CurrentToken ( private )
 */
class /* struct */ CurrentToken
{
	/**
	 * token
	 * @var int $token
	 */
	public $token;

	/**
	 * value
	 * @var object $value
	 */
	public  $value;

	/**
	 * anchor
	 * @var int $anchor
	 */
	public $anchor;

	/**
	 * length
	 * @var int $length
	 */
	public $length;
}

/**
 * Tokenizer ( internal )
 */
class Tokenizer implements \lyquidity\XPath2\parser\yyInput
{
	/**
	 * m_reader
	 * @var StringReader $m_reader
	 */
	private $m_reader;

	/**
	 * m_position
	 * @var int $m_position
	 */
	private $m_position = 0;

	/**
	 * m_state
	 * @var LexerState $m_state = LexerState::DefaultState
	 */
	private $m_state = LexerState::DefaultState;

	/**
	 * m_buffer
	 * @var array $m_buffer = new StringBuilder()
	 */
	private $m_buffer = array(); // new StringBuilder();

	/**
	 * m_states
	 * @var string[]
	 */
	private $m_states = array(); // new Stack<LexerState>();

	/**
	 * Holds the priorToken
	 * @var CurrentToken
	 */
	private	 $m_priorToken;

	/**
	 * m_token
	 * @var object[]
	 */
	private $m_token = array(); // new Queue<CurrentToken>();

	/**
	 * m_anchor
	 * @var int $m_anchor
	 */
	private $m_anchor = 0;

	/**
	 * m_bookmark
	 * @var int[] $m_bookmark
	 */
	private $m_bookmark;

	/**
	 * m_length
	 * @var int $m_length
	 */
	private $m_length = 0;

	/**
	 * m_value
	 * @var string 
	 */
	private $m_value;

	/**
	 * LineNo
	 * @var int $LineNo
	 */
	public $LineNo = 0;
	/**
	 * ColNo
	 * @var int $ColNo
	 */
	public $ColNo = 0;

	/**
	 * Constructor
	 * @param string $strInput
	 */
	public function __construct( $strInput = null )
	{
		$this->m_reader = new StringReader( trim( $strInput ) );

		$this->LineNo = $this->ColNo = 1;
		$this->m_bookmark = array_fill( 0, 5, 0 ); // new int[5];
	}

	/**
	 * Get the current position in the query string
	 * @var int $Position
	 */
	public function getPosition()
	{
		return $this->m_position;
	}

	/**
	 * $this->Peek
	 * @param int $lookahead
	 * @return string
	 */
	protected function Peek( $lookahead )
	{
		while ( $lookahead >= count( $this->m_buffer ) && $this->m_reader->peek() != -1 )
		{
			$this->m_buffer[] = $this->m_reader->read();
		}
		if ( $lookahead < count( $this->m_buffer ) )
		{
			return $this->m_buffer[ $lookahead ];
		}
		else
		{
			return "\0";
		}
	}

	/**
	 * Read
	 * @return string
	 */
	protected function Read()
	{
		$ch = null;
		if ( count( $this->m_buffer ) > 0 )
		{
		    $ch = array_shift( $this->m_buffer );
		}
		else
		{
		    $c = $this->m_reader->read();
		    if ( $c == -1 )
		        return '\0';
		    else
		        $ch = $c;
		}
		if ( $ch != '\r' )
		{
		    if ( $ch == '\n' )
		    {
		        $this->LineNo++;
		        $this->ColNo = 1;
		    }
		    else
		        ++$this->ColNo;
		}
		$this->m_position++;
		return $ch;
	}

	/**
	 * $this->BeginToken
	 * @param int $anchor
	 * @return void
	 */
	private function BeginToken( $anchor = null )
	{
		$this->m_anchor = is_null( $anchor ) ? $this->getPosition() : $anchor;
		$this->m_length = 0;
	}

	/**
	 * $this->EndToken
	 * @param string $s
	 * @return void
	 */
	private function EndToken( $s = null )
	{
		$this->m_length = is_null( $s )
			? $this->getPosition() - $this->m_anchor
			: strlen( $s );
	}

	/**
	 * ConsumeNumber
	 * @return void
	 */
	private function ConsumeNumber()
	{
		$tok = Token::IntegerLiteral;
		$sb = array();
		$this->BeginToken();
		// while ( XmlCharType.Instance.IsDigit( $this->Peek( 0 ) ) )
		while ( preg_match( "/^\d/", $this->Peek( 0 ) ) )
		{
			$sb[] = $this->Read();
		}
		if ( $this->Peek( 0 ) == '.' )
		{
			$tok = Token::DecimalLiteral;
			$sb[] = $this->Read();
			// while ( XmlCharType.Instance.IsDigit( $this->Peek( 0 ) ) )
			while ( preg_match( "/^\d/", $this->Peek( 0 ) ) )
			{
				$sb[] = $this->Read();
			}
		}
		$c = $this->Peek( 0 );
		if ( $c == 'E' || $c == 'e' )
		{
			$tok = Token::DoubleLiteral;
			$sb[] = $this->Read();
			$c = $this->Peek( 0 );
			if ( $c == '+' || $c == '-' )
			{
				$sb[] = $this->Read();
			}
		    // while ( XmlCharType.Instance.IsDigit( $this->Peek( 0 ) ) )
		    while ( preg_match( "/\d/",$this->Peek( 0 ) ) )
		    {
		    	$sb[] = $this->Read();
		    }
		}
		if ( preg_match( "/\p{L}/i", $this->Peek( 0 ) ) )
		{
			throw XPath2Exception::withErrorCodeAndParam( "XPST0003", Resources::XPST0003, "Expected a space" );
		}
		$this->EndToken();
		$s = implode( "", $sb );
		switch ( $tok )
		{
			case Token::IntegerLiteral:
				if ( ! is_int( $s + 0 ) )
				{
					throw new InvalidCastException( "The string literal cannot be converted to an integer: $s" );
				}

				// $this->ConsumeToken2( $tok, Integer::FromValue( $s ) );
				$this->ConsumeToken2( $tok, ( Integer )$s );
				break;

			case Token::DecimalLiteral:
				if ( strlen($s ) > 30)
				{
					throw new InvalidCastException( "The string literal converts to an number which is too large or too small for a decimal" );
				}
				$decimal = new DecimalValue( $s );
				// if ( is_infinite( $double ) && strtoupper( $s ) != "INF" )
				//	throw new InvalidCastException( "The string literal converts to an INF which is not valid" );
				$this->ConsumeToken2( $tok, $decimal );
				break;

			case Token::DoubleLiteral:
				if ( strlen($s ) > 308)
				{
					throw new InvalidCastException( "The string literal converts to an number which is too large or too small for a double" );
				}
				if ( ! is_numeric( $s ) )
				{
					throw XPath2Exception::withErrorCode( "XPST0003", Resources::FOCA0005 );
				}

				$double = doubleval( $s );
				// The number can be SO large that PHP converts the number to infinity which is not valid
				if ( is_infinite( $double ) && strtoupper( $s ) != "INF" )
				{
					throw new InvalidCastException( "The string literal converts to an INF which is not valid" );
				}
				$this->ConsumeToken2( $tok, $double );
				break;
		}
	}

	/**
	 * $this->ConsumeLiteral
	 * @return void
	 */
	private function ConsumeLiteral()
	{
		$this->BeginToken();
		$quote = $this->Read();
		$sb = array();
		$c = null;
		while ( ( $c = $this->Peek( 0 ) ) != $quote || $this->Peek( 1 ) == $quote )
		{
			// if ( $this->Peek( 0 ) == '\0' )
			if ( $c === false || ord( $c ) == 0 )
			{
				return;
			}
			if ( $c == $quote && $this->Peek( 1 ) == $quote )
			{
				$this->Read();
			}
			$sb[] = $this->Read();
		}
		$this->Read();
		$this->EndToken();
		$this->ConsumeToken2( Token::StringLiteral, CoreFuncs::NormalizeStringValue( implode( "", $sb ), false, true ) );
	}

	/**
	 * $this->ConsumeNCName
	 * @return void
	 */
	private function ConsumeNCName()
	{
		$c = null;
		$sb = array();
		$this->BeginToken();
		// while ( ( $c = $this->Peek( 0 ) ) && ord( $c ) != 0 && preg_match( "/[\p{L}\p{N}_\-.]/u", $c ) )
		while ( ( $c = ord( $this->Peek( 0 ) ) ) && preg_match( "/[\p{L}\p{N}_\-.]/u", chr( $c ) ) )
		{
			$sb[] = $this->Read();
		}
		$this->EndToken();
		$this->ConsumeToken2( Token::NCName, implode( "", $sb ) );
	}

	/**
	 * $this->ConsumeQName
	 * @return void
	 */
	private function ConsumeQName()
	{
		$c = null;
		$sb = array();
		$this->BeginToken();
		// while ( ( $c = $this->Peek( 0 ) ) && ord( $c ) != 0 && preg_match( "/^[\p{L}\p{N}_\-.:]/u", $c ) )
		while ( ( $c = ord( $this->Peek( 0 ) ) ) && preg_match( "/^[\p{L}\p{N}_\-.:]/u", chr( $c ) ) )
		{
			$sb[] = $this->Read();
		}
		$this->EndToken();
		$this->ConsumeToken2( Token::QName, implode( "", $sb ) );
	}

	/**
	 * $this->ConsumeChar
	 * @param string $token
	 * @return void
	 */
	private function ConsumeChar( $token )
	{
		$curr = new CurrentToken();
		$curr->token = $token;
		$curr->value = null;
		$curr->anchor = $this->m_anchor;
		$curr->length = 1;
		array_push( $this->m_token, $curr );
	}

	/**
	 * ConsumeToken
	 * @param int $token
	 * @return void
	 */
	private function ConsumeToken1( $token )
	{
		$this->ConsumeToken2( $token, null );
	}

	/**
	 * ConsumeToken
	 * @param int $token
	 * @param int $anchor
	 * @param int $length
	 * @return void
	 */
	private function ConsumeToken3( $token, $anchor, $length )
	{
		$this->ConsumeToken4( $token, null, $anchor, $length );
	}

	/**
	 * ConsumeToken
	 * @param int $token
	 * @param object $value
	 * @param int $anchor
	 * @param int $length
	 * @return void
	 */
	private function ConsumeToken4( $token, $value, $anchor, $length )
	{
		$curr = new CurrentToken();
		$curr->token = $token;
		$curr->value = $value;
		$curr->anchor = $anchor;
		$curr->length = $length;
		array_push( $this->m_token, $curr );
	}

	/**
	 * ConsumeToken
	 * @param int $token
	 * @param object $value
	 * @return void
	 */
	private function ConsumeToken2( $token, $value )
	{
		if ( in_array( $token, array( Token::DIV, Token::IDIV, Token::MOD ) ) && preg_match( "/\p{N}/", $this->Peek(0) ) )
		{
			throw XPath2Exception::withErrorCodeAndParam( "XPST0003", Resources::XPST0003, "The operator must be followed by space" );
		}
		$curr = new CurrentToken();
		$curr->token = $token;
		$curr->value = $value;
		$curr->anchor = $this->m_anchor;
		$curr->length = $this->m_length;
		array_push( $this->m_token, $curr );
	}

	/**
	 * MatchText
	 * @param string $text
	 * @return bool
	 */
	private function MatchText( $text )
	{
		for ( $k = 0; $k < strlen( $text ); $k++ )
		{
			$ch = $this->Peek( $k );
			if ( ord( $ch ) == 0 || $ch != $text[ $k ] )
				return false;
		}
		for ( $k = 0; $k < strlen( $text ); $k++ )
			$this->Read();
		return true;
	}

	/**
	 * $this->MatchIdentifer
	 * @param string[] $identifer
	 * @return bool
	 */
	private function MatchIdentifer( $identifer )
	{
		if ( ! is_array( $identifer ) )
		{
			$identifer = array( $identifer );
		}

		$i = 0;
		for ( $sp = 0; $sp < count( $identifer ); $sp++ )
		{
			$c = null;
			while ( true )
			{
				// if ( ( $c = $this->Peek( $i ) ) && ord( $c ) != 0 && preg_match( "/\s/", $c ) )
				if ( ( $c = ord( $this->Peek( $i ) ) ) && preg_match( "/\s/", chr( $c ) ) )
				{
					// while ( ( $c = $this->Peek( $i ) ) && ord( $c ) != 0 && preg_match( "/\s/", $c ) )
					while ( ( $c = ord( $this->Peek( $i ) ) ) && preg_match( "/\s/", chr( $c ) ) )
						$i++;
					continue;
				}
				if ( $this->Peek( $i ) == '(' && $this->Peek( $i + 1 ) == ':' )
				{
					$n = 1;
					$i += 2;
					while ( true )
					{
						$c = $this->Peek( $i );
						if ( $c ==  '\0' )
							break;
						else if ( $c == '(' && $this->Peek( $i + 1 ) == ':' )
						{
							$i += 2;
							$n++;
						}
						else if ( $c == ':' && $this->Peek( $i + 1 ) == ')' )
						{
							$i += 2;
							if ( --$n == 0 )
								break;
						}
						else
							$i++;
					}

					if ( $n )
					{
						throw XPath2Exception::withErrorCodeAndParam( "XPST0003", Resources::XPST0003, "Comment not closed" );
					}

					continue;
				}
				break;
			}
			$s = $identifer[ $sp ];
			$this->m_bookmark[ $sp ] = $this->getPosition() + $i;
			if ( strlen( $s ) > 0 )
			{
				for ( $k = 0; $k < strlen( $s ); $k++, $i++ )
					if ( ( $c = $this->Peek( $i ) ) && ord( $c ) == 0 || $c != $s[ $k ] )
						return false;
				if ( preg_match( "/[\p{L}_]/u", $s[0] ) && // NCName Start char
					 preg_match( "/[\p{L}\p{N}_\-.]/u", $this->Peek( $i ) ) ) // NCName char
					return false;
			}
		}
		while ( $i-- > 0 )
			$this->Read();
		return true;
	}

	/**
	 * $this->SkipWhitespace
	 * @return void
	 */
	private function SkipWhitespace()
	{
		do
		{
			// if ( XmlCharType.Instance.IsWhiteSpace( $this->Peek( 0 ) ) )
			if ( preg_match( "/\s/", $this->Peek( 0 ) ) )
			{
				$c = null;
				// while ( ( $c = $this->Peek( 0 ) ) && ord( $c ) != 0 && preg_match( "/\s/", $c ) )
				while ( ( $c = ord( $this->Peek( 0 ) ) ) && preg_match( "/\s/", chr( $c ) ) )
				{
					$this->Read();
				}
				continue;
			}
			if ( $this->Peek( 0 ) == '(' && $this->Peek( 1 ) == ':' )
			{
				$this->Read();
				$this->Read();
				$n = 1;
				while ( true )
				{
					$c = $this->Peek( 0 );
					if ( ord( $c ) == 0 )
					{
						break;
					}
					else if ( $c == '(' && $this->Peek( 1 ) == ':' )
					{
						$this->Read();
						$this->Read();
						$n++;
					}
					else if ( $c == ':' && $this->Peek( 1 ) == ')' )
					{
						$this->Read();
						$this->Read();
						if ( --$n == 0 )
						{
							break;
						}
					}
					else
					{
						$this->Read();
					}
				}
				if ( $n )
				{
					throw XPath2Exception::withErrorCodeAndParam( "XPST0003", Resources::XPST0003, "Comment not closed" );
				}
				continue;
			}
			break;
		}
		while ( true );
	}

	/**
	 * $this->DefaultState
	 * @return void
	 */
	private function DefaultState()
	{
		$this->SkipWhitespace();
		$this->BeginToken();
		$c = $this->Peek( 0 );
		if ( $c == '\0' )
			$this->ConsumeToken1( 0 ); // EOF
		else if ( $c == '.' )
		{
			if ( $this->Peek( 1 ) == '.' )
			{
				$this->Read();
				$this->Read();
				$this->EndToken();
				$this->ConsumeToken1( Token::DOUBLE_PERIOD );
			}
			// else if ( XmlCharType.Instance.IsDigit( $this->Peek( 1 ) ) )
			else if ( preg_match( "/\d/", $this->Peek( 1 ) ) )
			{
				$this->ConsumeNumber();
			}
			else
				$this->ConsumeChar( $this->Read() );
			$this->m_state = LexerState::Operator;
		}
		else if ( $c == ')' )
		{
		    $this->ConsumeChar( $this->Read() );
		    $this->SkipWhitespace();
		    $this->BeginToken();
		    $this->m_state = LexerState::Operator;
		}
		else if ( $c == '*' )
		{
		    $this->ConsumeChar( $this->Read() );
		    if ( $this->Peek( 0 ) == ':' )
		    {
		        $this->BeginToken();
		        $this->ConsumeChar( $this->Read() );
		        $c = $this->Peek( 0 );
		        if ( ord( $c ) != 0 && preg_match( "/[\p{L}_]/u", $c ) ) // NCName Start char
		            $this->ConsumeNCName();
		        else
		            throw XPath2Exception::withErrorCode( "XPST0003", Resources::ExpectedNCName );
		    }
		    $this->m_state = LexerState::Operator;
		}
		else if ( $c == ';' || $c == ',' || $c == '(' || $c == '-' || $c == '+' || $c == '@' || $c == '~' )
		    $this->ConsumeChar( $this->Read() );
		else if ( $c == '/' )
		{
		    if ( $this->Peek( 1 ) == '/' )
		    {
		        $this->Read();
		        $this->Read();
		        $this->EndToken();
		        $this->ConsumeToken1( Token::DOUBLE_SLASH );
		    }
		    else
		        $this->ConsumeChar( $this->Read() );
		}
		else if ( $this->MatchIdentifer( array( "if", '(' ) ) )
		{
		    $this->EndToken( "if" );
		    $this->ConsumeToken1( Token::IF_TOKEN );
		    $this->BeginToken( $this->m_bookmark[1] );
		    $this->ConsumeChar( '(' );
		}
		else if ( $this->MatchIdentifer( "for" ) )
		{
		    $this->EndToken();
		    $this->ConsumeToken1( Token::FOR_TOKEN );
		    $this->SkipWhitespace();
		    $this->BeginToken();
		    if ( $this->Peek( 0 ) == '$' )
		        $this->ConsumeChar( $this->Read() );
		    else
		        throw XPath2Exception::withErrorCodeAndParam( "XPST0003", Resources::ExpectedVariablePrefix, "for" );
		    $this->m_state = LexerState::VarName;
		}
		else if ( $this->MatchIdentifer( "some" ) )
		{
		    $this->EndToken();
		    $this->ConsumeToken1( Token::SOME );
		    $this->SkipWhitespace();
		    $this->BeginToken();
		    if ( $this->Peek( 0 ) == '$' )
		        $this->ConsumeChar( $this->Read() );
		    else
		        throw XPath2Exception::withErrorCodeAndParam( "XPST0003", Resources::ExpectedVariablePrefix, "some" );
		    $this->m_state = LexerState::VarName;
		}
		else if ( $this->MatchIdentifer( "every" ) )
		{
		    $this->EndToken();
		    $this->ConsumeToken1( Token::EVERY );
		    $this->SkipWhitespace();
		    $this->BeginToken();
		    if ( $this->Peek( 0 ) == '$' )
		        $this->ConsumeChar( $this->Read() );
		    else
		        throw XPath2Exception::withErrorCodeAndParam( "XPST0003", Resources::ExpectedVariablePrefix, "every" );
		    $this->m_state = LexerState::VarName;
		}
		else if ( $c == '$' )
		{
		    $this->ConsumeChar( $this->Read() );
		    $this->m_state = LexerState::VarName;
		}
		else if ( $this->MatchIdentifer( array( "element", '(' ) ) )
		{
		    $this->EndToken( "element" );
		    $this->ConsumeToken1( Token::ELEMENT );
		    $this->BeginToken( $this->m_bookmark[1] );
		    $this->ConsumeChar( '(' );
		    array_push( $this->m_states, LexerState::Operator );
		    $this->m_state = LexerState::KindTest;
		}
		else if ( $this->MatchIdentifer( array( "attribute", '(' ) ) )
		{
		    $this->EndToken( "attribute" );
		    $this->ConsumeToken1( Token::ATTRIBUTE );
		    $this->BeginToken( $this->m_bookmark[1] );
		    $this->ConsumeChar( '(' );
		    array_push( $this->m_states, LexerState::Operator );
		    $this->m_state = LexerState::KindTest;
		}
		else if ( $this->MatchIdentifer( array( "schema-element", '(' ) ) )
		{
		    $this->EndToken( "schema-element" );
		    $this->ConsumeToken1( Token::SCHEMA_ELEMENT );
		    $this->BeginToken( $this->m_bookmark[1] );
		    $this->ConsumeChar( '(' );
		    array_push( $this->m_states, LexerState::Operator );
		    $this->m_state = LexerState::KindTest;
		}
		else if ( $this->MatchIdentifer( array( "schema-attribute", '(' ) ) )
		{
		    $this->EndToken( "schema-attribute" );
		    $this->ConsumeToken1( Token::SCHEMA_ATTRIBUTE );
		    $this->BeginToken( $this->m_bookmark[1] );
		    $this->ConsumeChar( '(' );
		    array_push( $this->m_states, LexerState::Operator );
		    $this->m_state = LexerState::KindTest;
		}
		else if ( $this->MatchIdentifer( array( "comment", '(' ) ) )
		{
		    $this->EndToken( "comment" );
		    $this->ConsumeToken1( Token::COMMENT );
		    $this->BeginToken( $this->m_bookmark[1] );
		    $this->ConsumeChar( '(' );
		    array_push( $this->m_states, LexerState::Operator );
		    $this->m_state = LexerState::KindTest;
		}
		else if ( $this->MatchIdentifer( array( "text", '(' ) ) )
		{
		    $this->EndToken( "text" );
		    $this->ConsumeToken1( Token::TEXT );
		    $this->BeginToken( $this->m_bookmark[1] );
		    $this->ConsumeChar( '(' );
		    array_push( $this->m_states, LexerState::Operator );
		    $this->m_state = LexerState::KindTest;
		}
		else if ( $this->MatchIdentifer( array( "node", '(' ) ) )
		{
		    $this->EndToken( "node" );
		    $this->ConsumeToken1( Token::NODE );
		    $this->BeginToken( $this->m_bookmark[1] );
		    $this->ConsumeChar( '(' );
		    array_push( $this->m_states, LexerState::Operator );
		    $this->m_state = LexerState::KindTest;
		}
		else if ( $this->MatchIdentifer( array( "document-node", '(' ) ) )
		{
		    $this->EndToken( "document-node" );
		    $this->ConsumeToken1( Token::DOCUMENT_NODE );
		    $this->BeginToken( $this->m_bookmark[1] );
		    $this->ConsumeChar( '(' );
		    array_push( $this->m_states, LexerState::Operator );
		    $this->m_state = LexerState::KindTest;
		}
		else if ( $this->MatchIdentifer( array( "processing-instruction", '(' ) ) )
		{
		    $this->EndToken( "processing-instruction" );
		    $this->ConsumeToken1( Token::PROCESSING_INSTRUCTION );
		    $this->BeginToken( $this->m_bookmark[1] );
		    $this->ConsumeChar( '(' );
		    array_push( $this->m_states, LexerState::Operator );
		    $this->m_state = LexerState::KindTestForPi;
		}
		else if ( $this->MatchIdentifer( array( "ancestor-or-self", "::" ) ) )
		{
		    $this->EndToken();
		    $this->ConsumeToken1( Token::AXIS_ANCESTOR_OR_SELF );
		}
		else if ( $this->MatchIdentifer( array( "ancestor", "::" ) ) )
		{
		    $this->EndToken();
		    $this->ConsumeToken1( Token::AXIS_ANCESTOR );
		}
		else if ( $this->MatchIdentifer( array( "attribute", "::" ) ) )
		{
		    $this->EndToken();
		    $this->ConsumeToken1( Token::AXIS_ATTRIBUTE );
		}
		else if ( $this->MatchIdentifer( array( "child", "::" ) ) )
		{
		    $this->EndToken();
		    $this->ConsumeToken1( Token::AXIS_CHILD );
		}
		else if ( $this->MatchIdentifer( array( "descendant-or-self", "::" ) ) )
		{
		    $this->EndToken();
		    $this->ConsumeToken1( Token::AXIS_DESCENDANT_OR_SELF );
		}
		else if ( $this->MatchIdentifer( array( "descendant", "::" ) ) )
		{
		    $this->EndToken();
		    $this->ConsumeToken1( Token::AXIS_DESCENDANT );
		}
		else if ( $this->MatchIdentifer( array( "following-sibling", "::" ) ) )
		{
		    $this->EndToken();
		    $this->ConsumeToken1( Token::AXIS_FOLLOWING_SIBLING );
		}
		else if ( $this->MatchIdentifer( array( "following", "::" ) ) )
		{
		    $this->EndToken();
		    $this->ConsumeToken1( Token::AXIS_FOLLOWING );
		}
		else if ( $this->MatchIdentifer( array( "parent", "::" ) ) )
		{
		    $this->EndToken();
		    $this->ConsumeToken1( Token::AXIS_PARENT );
		}
		else if ( $this->MatchIdentifer( array( "preceding-sibling", "::" ) ) )
		{
		    $this->EndToken();
		    $this->ConsumeToken1( Token::AXIS_PRECEDING_SIBLING );
		}
		else if ( $this->MatchIdentifer( array( "preceding", "::" ) ) )
		{
		    $this->EndToken();
		    $this->ConsumeToken1( Token::AXIS_PRECEDING );
		}
		else if ( $this->MatchIdentifer( array( "self", "::" ) ) )
		{
		    $this->EndToken();
		    $this->ConsumeToken1( Token::AXIS_SELF );
		}
		else if ( $this->MatchIdentifer( array( "namespace", "::" ) ) )
		{
		    $this->EndToken();
		    $this->ConsumeToken1( Token::AXIS_NAMESPACE );
		}
		else if ( $c == '"' || $c == '\'' )
		{
		    $this->ConsumeLiteral();
		    $this->m_state = LexerState::Operator;
		}
		// else if ( XmlCharType.Instance.IsDigit( $c ) )
		else if ( preg_match( "/\d/", $c ) )
		{
		    $this->ConsumeNumber();
		    $this->m_state = LexerState::Operator;
		}
		else if ( preg_match( "/[\p{L}_]/u", $c ) ) // Start NCName char
		{
		    $sb = array();
			// while ( ( $c = $this->Peek( 0 ) ) && ord( $c ) != 0 && preg_match( "/[\p{L}\p{N}_\-.]/u", $c ) ) // NCName
			while ( ( $c = ord( $this->Peek( 0 ) ) ) && preg_match( "/[\p{L}\p{N}_\-.:]/u", chr( $c ) ) ) // NCName
		        $sb[] = $this->Read();
		    if ( $this->Peek( 0 ) == ':' )
		    {
		        if ( $this->Peek( 1 ) == '*' )
		        {
		            $this->EndToken();
		            $this->ConsumeToken2( Token::NCName, implode( "", $sb ) );
		            $this->BeginToken();
		            $this->ConsumeChar( $this->Read() );
		            $this->BeginToken();
		            $this->ConsumeChar( $this->Read() );
		            $this->m_state = LexerState::Operator;
		        }
		        else
		        {
		            // while ( ( $c = $this->Peek( 0 ) ) && ord( $c ) != 0 && preg_match( "/[\p{L}\p{N}_\-.:]/u", $c ) ) // Name char
		            while ( ( $c = ord( $this->Peek( 0 ) ) ) && preg_match( "/[\p{L}\p{N}_\-.:]/u", chr( $c ) ) ) // Name char
		                $sb[] = $this->Read();
	
		            $this->EndToken();
		            $this->ConsumeToken2( Token::QName, implode( "", $sb ) );
		            $this->SkipWhitespace();
		            if ( $this->Peek( 0 ) != '(' )
		                $this->m_state = LexerState::Operator;
		        }
		    }
		    else
		    {
		        $this->EndToken();
		        // $anchor = $this->m_anchor;
		        $length = $this->m_length;
		        $ncname = implode( "", $sb );
		        $this->ConsumeToken2( Token::QName, $ncname );
		        $this->SkipWhitespace();
		        if ( $this->Peek( 0 ) != '(' )
		            $this->m_state = LexerState::Operator;
		    }
		}
		// BMS 2017-06-18	This is added because without it the tokenizer does not handle
		//					valid XPath query with a trailing slash in a filter such as
		//					fn:count($context[5 * /])
		else if ( $c == ']')
		{
			if ( isset( $this->m_priorToken ) )
			{
				if ( $this->m_priorToken->token == '/' )
				{
					$this->ConsumeChar( $this->Read() );
					$this->m_state = array_pop( $this->m_states );
				}
			}
		}
	}

	/**
	 * $this->VarNameState
	 * @return void
	 */
	private function VarNameState()
	{
		$this->SkipWhitespace();
		if ( ord( $this->Peek( 0 ) ) == 0 )
			return;
		$this->BeginToken();
		$c = $this->Peek( 0 );
		if ( preg_match( "/[\p{L}\p{N}_\-.]/u", $c ) )
		{
			$prefix = "";
			$sb = array();
			while ( ( $c = $this->Peek( 0 ) ) !== 0 && ord( $c ) != 0 && preg_match( "/[\p{L}\p{N}_\-.]/u", $c ) )
				$sb[] = $this->Read();
			if ( $this->Peek( 0 ) == ':' && preg_match( "/[\p{L}\p{N}_\-.]/u", $this->Peek( 1 ) ) )
			{
				$prefix = implode( "", $sb );
				$this->Read();
				$sb = array();
				while ( ( $c = $this->Peek( 0 ) ) !== 0 && ord( $c ) != 0 && preg_match( "/[\p{L}\p{N}_\-.]/u", $c ) )
					$sb[] = $this->Read();
			}
			$this->EndToken();
			$this->ConsumeToken2( Token::VarName, new VarName( $prefix, implode( "", $sb ) ) );
			$this->m_state = LexerState::Operator;
		}
	}

	/**
	 * $this->OperatorState
	 * @return void
	 */
	private function OperatorState()
	{
		$this->SkipWhitespace();
		$this->BeginToken();
		$c = $this->Peek( 0 );
		if ( $c == "\0" )
		{
			$this->ConsumeToken1( 0 );
		}
		else if ( $c == ';' || $c == ',' || $c == '=' || $c == '+' || $c == '-' || $c == '[' || $c == '|' )
		{
			$this->ConsumeChar( $this->Read() );
			if ( $c == '[' )
				array_push( $this->m_states, $this->m_state );
			$this->m_state = LexerState::DefaultState;
		}
		else if ( $c == '*' )
		{
			$this->Read();
			$this->EndToken();
			$this->ConsumeToken1( Token::ML );
			$this->m_state = LexerState::DefaultState;
		}
		else if ( $c == ':' && $this->Peek( 1 ) == '=' )
		{
			$this->ConsumeChar( $this->Read() );
			$this->BeginToken();
			$this->ConsumeChar( $this->Read() );
			$this->m_state = LexerState::DefaultState;
		}
		else if ( $c == '!' && $this->Peek( 1 ) == '=' )
		{
			$this->ConsumeChar( $this->Read() );
			$this->BeginToken();
			$this->ConsumeChar( $this->Read() );
			$this->m_state = LexerState::DefaultState;
		}
		else if ( $c == '>' )
		{
			$this->ConsumeChar( $this->Read() );
			if ( $this->Peek( 0 ) == '=' || $this->Peek( 0 ) == '>' )
			{
				$this->BeginToken();
				$this->ConsumeChar( $this->Read() );
			}
			$this->m_state = LexerState::DefaultState;

		}
		else if ( $c == '<' )
		{
			$this->ConsumeChar( $this->Read() );
			if ( $this->Peek( 0 ) == '=' || $this->Peek( 0 ) == '<' )
			{
				$this->BeginToken();
				$this->ConsumeChar( $this->Read() );
			}
			$this->m_state = LexerState::DefaultState;

		}
		else if ( $c == '/' )
		{
			if ( $this->Peek( 1 ) == '/' )
			{
				$this->Read();
				$this->Read();
				$this->EndToken();
				$this->ConsumeToken1( Token::DOUBLE_SLASH );
			}
			else
				$this->ConsumeChar( $this->Read() );
			$this->m_state = LexerState::DefaultState;
		}
		else if ( $c == ')' )
		{
			$this->ConsumeChar( $this->Read() );
			$this->SkipWhitespace();
			$this->BeginToken();
		}
		else if ( $c == '?' )
			$this->ConsumeChar( $this->Read() );
		else if ( $c == ']' )
		{
			$this->ConsumeChar( $this->Read() );
			$this->m_state = array_pop( $this->m_states );
		}
		else if ( $this->MatchIdentifer( "then" ) )
		{
			$this->EndToken();
			$this->ConsumeToken1( Token::THEN );
			$this->m_state = LexerState::DefaultState;
		}
		else if ( $this->MatchIdentifer( "else" ) )
		{
			$this->EndToken();
			$this->ConsumeToken1( Token::ELSE_TOKEN );
			$this->m_state = LexerState::DefaultState;
		}
		else if ( $this->MatchIdentifer( "and" ) )
		{
			$this->EndToken();
			$this->ConsumeToken1( Token::AND_TOKEN );
			$this->m_state = LexerState::DefaultState;
		}
		else if ( $this->MatchIdentifer( "div" ) )
		{
			$this->EndToken();
			$this->ConsumeToken1( Token::DIV );
			$this->m_state = LexerState::DefaultState;
		}
		else if ( $this->MatchIdentifer( "except" ) )
		{
			$this->EndToken();
			$this->ConsumeToken1( Token::EXCEPT );
			$this->m_state = LexerState::DefaultState;
		}
		else if ( $this->MatchIdentifer( "idiv" ) )
		{
			$this->EndToken();
			$this->ConsumeToken1( Token::IDIV );
			$this->m_state = LexerState::DefaultState;
		}
		else if ( $this->MatchIdentifer( "intersect" ) )
		{
			$this->EndToken();
			$this->ConsumeToken1( Token::INTERSECT );
			$this->m_state = LexerState::DefaultState;
		}
		else if ( $this->MatchIdentifer( "mod" ) )
		{
			$this->EndToken();
			$this->ConsumeToken1( Token::MOD );
			$this->m_state = LexerState::DefaultState;
		}
		else if ( $this->MatchIdentifer( "or" ) )
		{
			$this->EndToken();
			$this->ConsumeToken1( Token::OR_TOKEN );
			$this->m_state = LexerState::DefaultState;
		}
		else if ( $this->MatchIdentifer( "return" ) )
		{
			$this->EndToken();
			$this->ConsumeToken1( Token::RETURN_TOKEN );
			$this->m_state = LexerState::DefaultState;
		}
		else if ( $this->MatchIdentifer( "satisfies" ) )
		{
			$this->EndToken();
			$this->ConsumeToken1( Token::SATISFIES );
			$this->m_state = LexerState::DefaultState;
		}
		else if ( $this->MatchIdentifer( "to" ) )
		{
			$this->EndToken();
			$this->ConsumeToken1( Token::TO );
			$this->m_state = LexerState::DefaultState;
		}
		else if ( $this->MatchIdentifer( "union" ) )
		{
			$this->EndToken();
			$this->ConsumeToken1( Token::UNION );
			$this->m_state = LexerState::DefaultState;
		}
		else if ( $this->MatchIdentifer( array( "castable", "as" ) ) )
		{
			$this->EndToken();
			$this->ConsumeToken1( Token::CASTABLE_AS );
			$this->m_state = LexerState::SingleType;
		}
		else if ( $this->MatchIdentifer( array( "cast", "as" ) ) )
		{
			$this->EndToken();
			$this->ConsumeToken1( Token::CAST_AS );
			$this->m_state = LexerState::SingleType;
		}
		else if ( $this->MatchIdentifer( array( "instance", "of" ) ) )
		{
			$this->EndToken();
			$this->ConsumeToken1( Token::INSTANCE_OF );
			$this->m_state = LexerState::ItemType;
		}
		else if ( $this->MatchIdentifer( array( "treat", "as" ) ) )
		{
			$this->EndToken();
			$this->ConsumeToken1( Token::TREAT_AS );
			$this->m_state = LexerState::ItemType;
		}
		else if ( $this->MatchIdentifer( "in" ) )
		{
			$this->EndToken();
			$this->ConsumeToken1( Token::IN );
			$this->m_state = LexerState::DefaultState;
		}
		else if ( $this->MatchIdentifer( "is" ) )
		{
			$this->EndToken();
			$this->ConsumeToken1( Token::IS );
			$this->m_state = LexerState::DefaultState;
		}
		else if ( $c == '$' )
		{
			$this->ConsumeChar( $this->Read() );
			$this->m_state = LexerState::VarName;
		}
		else if ( $this->MatchIdentifer( "for" ) )
		{
			$this->EndToken();
			$this->ConsumeToken1( Token::FOR_TOKEN );
			$this->SkipWhitespace();
			$this->BeginToken();
			if ( $this->Peek( 0 ) == '$' )
				$this->ConsumeChar( $this->Read() );
			else
				throw XPath2Exception::withErrorCodeAndParam( "", Resources::ExpectedVariablePrefix, "for" );
			$this->m_state = LexerState::VarName;
		}
		else if ( $this->MatchIdentifer( "eq" ) )
		{
			$this->EndToken();
			$this->ConsumeToken1( Token::EQ );
			$this->m_state = LexerState::DefaultState;
		}
		else if ( $this->MatchIdentifer( "ge" ) )
		{
			$this->EndToken();
			$this->ConsumeToken1( Token::GE );
			$this->m_state = LexerState::DefaultState;
		}
		else if ( $this->MatchIdentifer( "gt" ) )
		{
			$this->EndToken();
			$this->ConsumeToken1( Token::GT );
			$this->m_state = LexerState::DefaultState;
		}
		else if ( $this->MatchIdentifer( "le" ) )
		{
			$this->EndToken();
			$this->ConsumeToken1( Token::LE );
			$this->m_state = LexerState::DefaultState;
		}
		else if ( $this->MatchIdentifer( "lt" ) )
		{
			$this->EndToken();
			$this->ConsumeToken1( Token::LT );
			$this->m_state = LexerState::DefaultState;
		}
		else if ( $this->MatchIdentifer( "ne" ) )
		{
			$this->EndToken();
			$this->ConsumeToken1( Token::NE );
			$this->m_state = LexerState::DefaultState;
		}
		else if ( $c == '"' || $c == '\'' )
		{
			$this->ConsumeLiteral();
		}
		else
		{
			// Do nothing
		}
	}

	/**
	 * advance
	 * @return bool
	 */
	public function advance()
	{
	 	return count( $this->m_token ) > 0 || ord( $this->Peek( 0 ) ) != 0;
	}

	/**
	 * $this->SingleTypeState
	 * @return void
	 */
	private function SingleTypeState()
	{
		$this->SkipWhitespace();
		if ( ord( $this->Peek( 0 ) ) == 0 )
			return;
		// if ( XmlCharType.Instance.IsNameChar( $this->Peek( 0 ) ) )
		if ( preg_match( "/^[\p{L}\p{N}_\-.:]/u", $this->Peek( 0 ) ) )
		{
			$this->ConsumeQName();
			$this->m_state = LexerState::Operator;
		}
	}

	/**
	 * $this->ItemTypeState
	 * @return void
	 */
	private function ItemTypeState()
	{
		$this->SkipWhitespace();
		if ( ord( $this->Peek( 0 ) ) == 0 )
		{
			return;
		}
		$this->BeginToken();
		$c = $this->Peek( 0 );
		if ( $c == '$' )
		{
			$this->ConsumeChar( $this->Read() );
			$this->m_state = LexerState::VarName;
		}
		else if ( $this->MatchIdentifer( array( "empty-sequence", '(', ")" ) ) )
		{
			$this->EndToken();
			$this->ConsumeToken1( Token::EMPTY_SEQUENCE );
			$this->m_state = LexerState::Operator;
		}
		else if ( $this->MatchIdentifer( array( "element", '(' ) ) )
		{
			$this->EndToken( "element" );
			$this->ConsumeToken1( Token::ELEMENT );
			$this->BeginToken( $this->m_bookmark[1] );
			$this->ConsumeChar( '(' );
			array_push( $this->m_states, LexerState::OccurrenceIndicator );
			$this->m_state = LexerState::KindTest;
		}
		else if ( $this->MatchIdentifer( array( "attribute", '(' ) ) )
		{
			$this->EndToken( "attribute" );
			$this->ConsumeToken1( Token::ATTRIBUTE );
			$this->BeginToken( $this->m_bookmark[1] );
			$this->ConsumeChar( '(' );
			array_push( $this->m_states, LexerState::OccurrenceIndicator );
			$this->m_state = LexerState::KindTest;
		}
		else if ( $this->MatchIdentifer( array( "schema-element", '(' ) ) )
		{
			$this->EndToken( "schema-element" );
			$this->ConsumeToken1( Token::SCHEMA_ELEMENT );
			$this->BeginToken( $this->m_bookmark[1] );
			$this->ConsumeChar( '(' );
			array_push( $this->m_states, LexerState::OccurrenceIndicator );
			$this->m_state = LexerState::KindTest;
		}
		else if ( $this->MatchIdentifer( array( "schema-attribute", '(' ) ) )
		{
			$this->EndToken( "schema-attribute" );
			$this->ConsumeToken1( Token::SCHEMA_ATTRIBUTE );
			$this->BeginToken( $this->m_bookmark[1] );
			$this->ConsumeChar( '(' );
			array_push( $this->m_states, LexerState::OccurrenceIndicator );
			$this->m_state = LexerState::KindTest;
		}
		else if ( $this->MatchIdentifer( array( "comment", '(' ) ) )
		{
			$this->EndToken( "comment" );
			$this->ConsumeToken1( Token::COMMENT );
			$this->BeginToken( $this->m_bookmark[1] );
			$this->ConsumeChar( '(' );
			array_push( $this->m_states, LexerState::OccurrenceIndicator );
			$this->m_state = LexerState::KindTest;
		}
		else if ( $this->MatchIdentifer( array( "text", '(' ) ) )
		{
			$this->EndToken( "text" );
			$this->ConsumeToken1( Token::TEXT );
			$this->BeginToken( $this->m_bookmark[1] );
			$this->ConsumeChar( '(' );
			array_push( $this->m_states, LexerState::OccurrenceIndicator );
			$this->m_state = LexerState::KindTest;
		}
		else if ( $this->MatchIdentifer( array( "node", '(' ) ) )
		{
			$this->EndToken( "node" );
			$this->ConsumeToken1( Token::NODE );
			$this->BeginToken( $this->m_bookmark[1] );
			$this->ConsumeChar( '(' );
			array_push( $this->m_states, LexerState::OccurrenceIndicator );
			$this->m_state = LexerState::KindTest;
		}
		else if ( $this->MatchIdentifer( array( "document-node", '(' ) ) )
		{
			$this->EndToken( "document-node" );
			$this->ConsumeToken1( Token::DOCUMENT_NODE );
			$this->BeginToken( $this->m_bookmark[1] );
			$this->ConsumeChar( '(' );
			array_push( $this->m_states, LexerState::OccurrenceIndicator );
			$this->m_state = LexerState::KindTest;
		}
		else if ( $this->MatchIdentifer( array( "processing-instruction", '(' ) ) )
		{
			$this->EndToken( "processing-instruction" );
			$this->ConsumeToken1( Token::PROCESSING_INSTRUCTION );
			$this->BeginToken( $this->m_bookmark[1] );
			$this->ConsumeChar( '(' );
			array_push( $this->m_states, LexerState::OccurrenceIndicator );
			$this->m_state = LexerState::KindTestForPi;
		}
		else if ( $this->MatchIdentifer( array( "item", '(', ")" ) ) )
		{
			$this->EndToken();
			$this->ConsumeToken1( Token::ITEM );
			$this->m_state = LexerState::OccurrenceIndicator;
		}
		else if ( $c == ';' )
		{
			$this->ConsumeChar( $this->Read() );
			$this->m_state = LexerState::DefaultState;
		}
		else if ( $this->MatchIdentifer( "then" ) )
		{
			$this->EndToken();
			$this->ConsumeToken1( Token::THEN );
			$this->m_state = LexerState::DefaultState;
		}
		else if ( $this->MatchIdentifer( "else" ) )
		{
			$this->EndToken();
			$this->ConsumeToken1( Token::ELSE_TOKEN );
			$this->m_state = LexerState::DefaultState;
		}
		else if ( $c == '=' || $c == '(' || $c == '[' || $c == '|' )
		{
			$this->ConsumeChar( $this->Read() );
			if ( $c == '[' )
				array_push( $this->m_states, $this->m_state );
			$this->m_state = LexerState::DefaultState;
		}
		else if ( $c == ':' && $this->Peek( 1 ) == '=' )
		{
			$this->ConsumeChar( $this->Read() );
			$this->BeginToken();
			$this->ConsumeChar( $this->Read() );
			$this->m_state = LexerState::DefaultState;
		}
		else if ( $c == '!' && $this->Peek( 1 ) == '=' )
		{
			$this->ConsumeChar( $this->Read() );
			$this->BeginToken();
			$this->ConsumeChar( $this->Read() );
			$this->m_state = LexerState::DefaultState;
		}
		else if ( $c == '>' )
		{
			$this->ConsumeChar( $this->Read() );
			if ( $this->Peek( 0 ) == '=' || $this->Peek( 0 ) == '>' )
			{
				$this->BeginToken();
				$this->ConsumeChar( $this->Read() );
			}
			$this->m_state = LexerState::DefaultState;

		}
		else if ( $c == '<' )
		{
			$this->ConsumeChar( $this->Read() );
			if ( $this->Peek( 0 ) == '=' || $this->Peek( 0 ) == '<' )
			{
				$this->BeginToken();
				$this->ConsumeChar( $this->Read() );
			}
			$this->m_state = LexerState::DefaultState;

		}
		else if ( $c == ')' )
		{
			$this->ConsumeChar( $this->Read() );
			$this->SkipWhitespace();
			$this->BeginToken();
		}
		else if ( $this->MatchIdentifer( "external" ) )
		{
			$this->EndToken();
			$this->ConsumeToken1( Token::EXCEPT );
			$this->m_state = LexerState::DefaultState;
		}
		else if ( $this->MatchIdentifer( "and" ) )
		{
			$this->EndToken();
			$this->ConsumeToken1( Token::AND_TOKEN );
			$this->m_state = LexerState::DefaultState;
		}
		else if ( $this->MatchIdentifer( "div" ) )
		{
			$this->EndToken();
			$this->ConsumeToken1( Token::DIV );
			$this->m_state = LexerState::DefaultState;
		}
		else if ( $this->MatchIdentifer( "except" ) )
		{
			$this->EndToken();
			$this->ConsumeToken1( Token::EXCEPT );
			$this->m_state = LexerState::DefaultState;
		}
		else if ( $this->MatchIdentifer( "eq" ) )
		{
			$this->EndToken();
			$this->ConsumeToken1( Token::EQ );
			$this->m_state = LexerState::DefaultState;
		}
		else if ( $this->MatchIdentifer( "ge" ) )
		{
			$this->EndToken();
			$this->ConsumeToken1( Token::GE );
			$this->m_state = LexerState::DefaultState;
		}
		else if ( $this->MatchIdentifer( "gt" ) )
		{
			$this->EndToken();
			$this->ConsumeToken1( Token::GT );
			$this->m_state = LexerState::DefaultState;
		}
		else if ( $this->MatchIdentifer( "le" ) )
		{
			$this->EndToken();
			$this->ConsumeToken1( Token::LE );
			$this->m_state = LexerState::DefaultState;
		}
		else if ( $this->MatchIdentifer( "lt" ) )
		{
			$this->EndToken();
			$this->ConsumeToken1( Token::LT );
			$this->m_state = LexerState::DefaultState;
		}
		else if ( $this->MatchIdentifer( "ne" ) )
		{
			$this->EndToken();
			$this->ConsumeToken1( Token::NE );
			$this->m_state = LexerState::DefaultState;
		}
		else if ( $this->MatchIdentifer( "idiv" ) )
		{
			$this->EndToken();
			$this->ConsumeToken1( Token::IDIV );
			$this->m_state = LexerState::DefaultState;
		}
		else if ( $this->MatchIdentifer( "intersect" ) )
		{
			$this->EndToken();
			$this->ConsumeToken1( Token::INTERSECT );
			$this->m_state = LexerState::DefaultState;
		}
		else if ( $this->MatchIdentifer( "mod" ) )
		{
			$this->EndToken();
			$this->ConsumeToken1( Token::MOD );
			$this->m_state = LexerState::DefaultState;
		}
		else if ( $this->MatchIdentifer( "or" ) )
		{
			$this->EndToken();
			$this->ConsumeToken1( Token::OR_TOKEN );
			$this->m_state = LexerState::DefaultState;
		}
		else if ( $this->MatchIdentifer( "return" ) )
		{
			$this->EndToken();
			$this->ConsumeToken1( Token::RETURN_TOKEN );
			$this->m_state = LexerState::DefaultState;
		}
		else if ( $this->MatchIdentifer( "satisfies" ) )
		{
			$this->EndToken();
			$this->ConsumeToken1( Token::SATISFIES );
			$this->m_state = LexerState::DefaultState;
		}
		else if ( $this->MatchIdentifer( "to" ) )
		{
			$this->EndToken();
			$this->ConsumeToken1( Token::TO );
			$this->m_state = LexerState::DefaultState;
		}
		else if ( $this->MatchIdentifer( "union" ) )
		{
			$this->EndToken();
			$this->ConsumeToken1( Token::UNION );
			$this->m_state = LexerState::DefaultState;
		}
		else if ( $this->MatchIdentifer( array( "castable", "as" ) ) )
		{
			$this->EndToken();
			$this->ConsumeToken1( Token::CASTABLE_AS );
			$this->m_state = LexerState::SingleType;
		}
		else if ( $this->MatchIdentifer( array( "cast", "as" ) ) )
		{
			$this->EndToken();
			$this->ConsumeToken1( Token::CAST_AS );
			$this->m_state = LexerState::SingleType;
		}
		else if ( $this->MatchIdentifer( array( "instance", "of" ) ) )
		{
			$this->EndToken();
			$this->ConsumeToken1( Token::INSTANCE_OF );
		}
		else if ( $this->MatchIdentifer( array( "treat", "as" ) ) )
		{
			$this->EndToken();
			$this->ConsumeToken1( Token::TREAT_AS );
		}
		else if ( $this->MatchIdentifer( "in" ) )
		{
			$this->EndToken();
			$this->ConsumeToken1( Token::IN );
			$this->m_state = LexerState::DefaultState;
		}
		else if ( $this->MatchIdentifer( "is" ) )
		{
			$this->EndToken();
			$this->ConsumeToken1( Token::IS );
			$this->m_state = LexerState::DefaultState;
		}
		// else if ( XmlCharType.Instance.IsNameChar( $c ) )
		else if ( preg_match( "/^[\p{L}\p{N}_\-.:]/u", $c ) )
		{
			$this->ConsumeQName();
			$this->m_state = LexerState::OccurrenceIndicator;
		}
	}

	/**
	 * $this->KindTestState
	 * @return void
	 */
	private function KindTestState()
	{
	    $this->SkipWhitespace();
	    if ( ord( $this->Peek( 0 ) ) == 0 )
	        return;
	    $this->BeginToken();
	    $c = $this->Peek( 0 );
	    if ( $c == '{' )
	    {
	        $this->ConsumeChar( $this->Read() );
	        array_push( $this->m_states, LexerState::Operator );
	        $this->m_state = LexerState::DefaultState;
	    }
	    else if ( $c == ')' )
	    {
	        $this->ConsumeChar( $this->Read() );
	        $this->m_state = array_pop( $this->m_states );
	    }
	    else if ( $c == '*' )
	    {
	        $this->ConsumeChar( $this->Read() );
	        $this->m_state = LexerState::CloseKindTest;
	    }
	    else if ( $this->MatchIdentifer( array( "element", '(' ) ) )
	    {
	        $this->EndToken( "element" );
	        $this->ConsumeToken1( Token::ELEMENT );
	        $this->BeginToken( $this->m_bookmark[1] );
	        $this->ConsumeChar( '(' );
	        array_push( $this->m_states, LexerState::KindTest );
	    }
	    else if ( $this->MatchIdentifer( array( "schema-element", '(' ) ) )
	    {
	        $this->EndToken( "schema-element" );
	        $this->ConsumeToken1( Token::SCHEMA_ELEMENT );
	        $this->BeginToken( $this->m_bookmark[1] );
	        $this->ConsumeChar( '(' );
	        array_push( $this->m_states, LexerState::KindTest );
	    }
	    // else if ( XmlCharType.Instance.IsNameChar( $c ) )
	    else if ( preg_match( "/^[\p{L}\p{N}_\-.:]/u", $c ) )
	    {
	        $this->ConsumeQName();
	        $this->m_state = LexerState::CloseKindTest;
	    }
	}

	/**
	 * $this->KindTestForPiState
	 * @return void
	 */
	private function KindTestForPiState()
	{
		$this->SkipWhitespace();
		if ( ord( $this->Peek( 0 ) ) == 0 )
			return;
		$c = $this->Peek( 0 );
		$this->BeginToken();
		if ( $c == ')' )
		{
			$this->ConsumeChar( $this->Read() );
			$this->m_state = array_pop( $this->m_states );
		}
		else if ( preg_match( "/[\p{L}\p{N}_\-.]/u", $c ) )
			$this->ConsumeNCName();
		else if ( $c == '\'' || $c == '"' )
			$this->ConsumeLiteral();
	}

	/**
	 * $this->CloseKindTestState
	 * @return void
	 */
	private function CloseKindTestState()
	{
		$this->SkipWhitespace();
		if ( ord( $this->Peek( 0 ) ) == 0 )
			return;
		$c = $this->Peek( 0 );
		$this->BeginToken();
		if ( $c == ')' )
		{
			$this->ConsumeChar( $this->Read() );
			$this->m_state = array_pop( $this->m_states );
		}
		else if ( $c == ',' )
		{
			$this->ConsumeChar( $this->Read() );
			$this->m_state = LexerState::KindTest;
		}
		else if ( $c == '{' )
		{
			$this->ConsumeChar( $this->Read() );
			array_push( $this->m_states, LexerState::Operator );
			$this->m_state = LexerState::DefaultState;
		}
		else if ( $c == '?' )
			$this->ConsumeChar( $this->Read() );
	}

	/**
	 * $this->OccurrenceIndicatorState
	 * @return void
	 */
	private function OccurrenceIndicatorState()
	{
		$this->SkipWhitespace();
		$this->BeginToken();
		$c = $this->Peek( 0 );
		if ( $c == '*' )
		{
			$this->Read();
			$this->EndToken();
			$this->ConsumeToken1( Token::Indicator1 );
		}
		else if ( $c == '+' )
		{
			$this->Read();
			$this->EndToken();
			$this->ConsumeToken1( Token::Indicator2 );
		}
		else if ( $c == '?' )
		{
			$this->Read();
			$this->EndToken();
			$this->ConsumeToken1( Token::Indicator3 );
		}
		$this->m_state = LexerState::Operator;
		$this->OperatorState();
	}

	/**
	 * $this->EnterState
	 * @return void
	 */
	private function EnterState()
	{
		switch ( $this->m_state )
		{
			case LexerState::DefaultState:
				$this->DefaultState();
				break;

			case LexerState::Operator:
				$this->OperatorState();
				break;

			case LexerState::VarName:
				$this->VarNameState();
				break;


			case LexerState::SingleType:
				$this->SingleTypeState();
				break;

			case LexerState::ItemType:
				$this->ItemTypeState();
				break;

			case LexerState::KindTest:
				$this->KindTestState();
				break;

			case LexerState::KindTestForPi:
				$this->KindTestForPiState();
				break;

			case LexerState::CloseKindTest:
				$this->CloseKindTestState();
				break;

			case LexerState::OccurrenceIndicator:
				$this->OccurrenceIndicatorState();
				break;
		}
	}

	/**
	 * token
	 * @return int
	 */
	public function token()
	{
		if ( count( $this->m_token ) == 0 )
		{
			$this->EnterState();
			if ( count( $this->m_token ) == 0 )
			{
				$this->m_value = null;
				return Token::yyErrorCode;
			}
		}

		$curr = array_shift( $this->m_token );
		$this->m_value = $curr->value;
		$this->CurrentPos = $curr->anchor;
		$this->CurrentLength = $curr->length;
		$this->m_priorToken = $curr;

		return is_numeric( $curr->token ) ? $curr->token : ord( $curr->token );
	}

	/**
	 * value
	 * @return object
	 */
	public function value()
	{
		return $this->m_value;
	}

	/**
	 * CurrentPos
	 * @var int $CurrentPos
	 */
	public $CurrentPos = 0;
	/**
	 * CurrentLength
	 * @var int $CurrentLength
	 */
	public $CurrentLength = 0;
}

/**
 * VarName (public)
 */
class VarName
{
	/**
	 * Constructor
	 * @param string $prefix
	 * @param string $localName
	 */
	public function __construct( $prefix, $localName )
	{
		$this->Prefix = $prefix;
		$this->LocalName = $localName;
	}

	/**
	 * Prefix
	 * @var String $Prefix
	 */
	public $Prefix = "";
	/**
	 * LocalName
	 * @var String $LocalName
	 */
	public $LocalName = "";
	/**
	 * ToString
	 * @return string
	 */
	public function ToString()
	{
		$sb = array();
		if ( $this->Prefix != "" )
		{
			$sb[] = $this->Prefix;
			$sb[] = ':';
		}
		$sb[] = $this->LocalName;
		return implode( "", $sb );
	}
}

/**
 * Helper class that implements a string reader that knows its position
 */
class StringReader
{
	/**
	 * Buffer
	 * @var array
	 */
	private $buffer = array();

	/**
	 * Position
	 * @var int
	 */
	private $position = -1;

	/**
	 * Constructor
	 * @param string $string
	 */
	public function __construct( $string = "" )
	{
		if ( is_null( $string ) ) return;
		$this->buffer = str_split( $string );
	}

	/**
	 * Rewind the iterator to the beginning
	 */
	public function rewind()
	{
		$this->position = -1;
	}

	/**
	 * Read the buffer and the current position
	 * @return boolean|mixed
	 */
	public function read()
	{
		$this->position++;
		if ( $this->position >= count( $this->buffer ) ) return false;

		return $this->buffer[ $this->position ];
	}

	/**
	 * Look one char ahead in the buffer
	 * @return number|mixed
	 */
	public function peek()
	{
		$peekPosition = $this->position + 1;
		// BMS 2017-09-05 This should return -1.  False is equivalent to zero which is a valid buffer offset value.
		if ( $peekPosition >= count( $this->buffer ) ) return -1; // false;

		return $this->buffer[ $peekPosition ];
	}

	/**
	 * Return the butter as a string
	 * @return string
	 */
	public function toString()
	{
		return implode( "", $this->buffer );
	}
}

?>
