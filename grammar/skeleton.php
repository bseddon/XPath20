#	jay skeleton

#	character in column 1 determines outcome...
#		# is a comment
#		. is copied
#		t is copied as //t if -t is set
#	other lines are interpreted to call jay procedures

.// created by jay 0.7 (c) 1998 Axel.Schreiner@informatik.uni-osnabrueck.de
.
 prolog		## %{ ... %} prior to the first %%

.  /** error text **/
.  public errorText = array();
.
.  /** simplified error message.
.      @see <a href="#yyerror(java.lang.String, java.lang.String[])">yyerror</a>
.    * @param string $message
.    */
.  public function yyerror( $message ) {
.    yyerror( $message, null );
.  }
.
.  /** (syntax) error message.
.      Can be overwritten to control message format.
.      @param string $message text to be displayed.
.      @param array $expected vector of acceptable tokens, if available.
.    */
.  public function yyerror ( $message, $expected ) {
.    if ( ( $this->errorText != null ) && ( $expected != null ) && ( strlen( $expected ) > 0 ) ) {
.      $this->errorText->Write( $message . ", expecting" );
.      for ( $n = 0; $n < strlen( $expected); ++ $n )
.        $this->errorText->Write( " " . $expected[n] );
.        $this->errorText->WriteLine();
.    } else
.      $this->errorText->WriteLine( $message );
.  }
.
.  /** debugging support, requires the package jay.yydebug.
.      Set to null to suppress debugging messages.
.    */
t  protected yydebug.yyDebug debug;
.
 debug			## tables for debugging support
.
.  /** index-checked interface to yyName[].
.      @param int $token single character or %token value.
.      @return string token name or [illegal] or [unknown].
.    */
.  public static function yyname( $token ) {
.    if ( ( $token < 0 ) || ( $token > strlen( $yyName ) ) return "[illegal]";
.    $name;
.    if ( ( $name = $yyName[ $token ] ) != null ) return $name;
.    return "[unknown]";
.  }
.
.  /** computes list of expected tokens on error by tracing the tables.
.      @param int $state for which to compute the list.
.      @return array list of token names.
.    */
.  protected function yyExpecting ( $state) {
.    $token, $n, $len = 0;
.    $ok = array_fill( 0, count( $yyName ), false );
.
.    if ( ( $n = $yySindex[ $state ]) != 0 )
.      for ( $token = $n < 0 ? -$n : 0;
.           ( $token < count( yyName ) && ( $n + $token < count( $yyTable ) ); ++ $token)
.        if ( $yyCheck[ $n + $token ] == $token && !$ok[ $token ] && $yyName[ $token ] != null ) {
.          ++ $len;
.          $ok[ $token ] = true;
.        }
.    if ( ( $n = $yyRindex[ $state ] ) != 0 )
.      for ( $token = $n < 0 ? -$n : 0;
.           ( $token < count( $yyName ) ) && ( $n + $token < count( $yyTable ) ); ++ $token)
.        if ( $yyCheck[ $n + $token ] == $token && !$ok[ $token ] && $yyName[ $token ] != null ) {
.          ++ $len;
.          $ok[ $token] = true;
.        }
.
.    /**
.     * @var array[string] $result
.     */
.    $result = array_fill( 0, len, null );
.    for ( $n = $token = 0; $n < $len;  ++ $token)
.      if ( $ok[ $token ] ) $result[ $n++ ] = $yyName[ $token ];
.    return $result;
.  }
.
.  /** the generated parser, with debugging messages.
.      Maintains a state and a value stack, currently with fixed maximum size.
.      @param $yyLex yyParser.yyInput scanner.
.      @param yydebug $yyd debug message writer implementing yyDebug, or null.
.      @return result of the last reduction, if any.
.      @throws yyException on irrecoverable parse error.
.    */
.  public function yyparseYyd ( $yyLex, $yyd)
.	{
t    $this->debug = yyd;
.    return yyparse( $yyLex );
.  }
.
.  /** initial size and increment of the state/value stack [default 256].
.      This is not final so that it can be overwritten outside of invocations
.      of yyparse().
.    */
.  protected $yyMax;
.
.  /** executed at the beginning of a reduce action.
.      Used as $$ = yyDefault($1), prior to the user-specified action, if any.
.      Can be overwritten to provide deep copy, etc.
.      @param object $first value for $1, or null.
.      @return first.
.    */
.  protected function yyDefault ( $first) {
.    return $first;
.  }
.
.  /** the generated parser.
.      Maintains a state and a value stack, currently with fixed maximum size.
.      @param yyParser.yyInput $yyLex scanner.
.      @return result of the last reduction, if any.
.      @throws yyException on irrecoverable parse error.
.    */
.  public function yyparse ( $yyLex)
.				{
.    if ( $yyMax <= 0) $yyMax = 256;			// initial size
.    /**
.     * @var int $yyState
.     */
.    $yyState = 0;                              // state stack ptr
.    /**
.     * @var array[int] $yyState
.     */
.    $yyStates = array_fill( 0, $yyMax, 0 );	// state stack
.    $yyVal = null;                             // value stack ptr
.    /**
.     * @var array[int] $yyVals
.     */
.    $yyVals = array_fill( 0, $yyMax, 0 );	    // value stack
.    $yyToken = -1;					            // current input
.    $yyErrorFlag = 0;				            // #tks to shift
.
 local		## %{ ... %} after the first %%

.    $yyTop = 0;
.    goto skip;
.    yyLoop:
.    $yyTop++;
.    skip:
.    for (;; ++ $yyTop) {
.      if ( $yyTop >= count( $yyStates ) ) {			// dynamically increase
.        $i = array_fill( 0, $yyMax, 0 );
.        $yyStates = array_merge( yyStates, $i );
.        $o = array_fill( 0, $yyMax, 0 );
.        $yyVals = array_merge( $yyVals, $i );
.      }
.      $yyStates[ $yyTop ] = $yyState;
.      $yyVals[ $yyTop ] = $yyVal;
t      if ($this->debug != null) $this->debug->push( $yyState, $yyVal );
.
.      yyDiscarded:
.      for (;;) {	// discarding a token does not change stack
.        $yyN;
.        if ( ( $yyN = $yyDefRed[ $yyState ] ) == 0 ) {	// else [default] reduce (yyN)
.          if ( $yyToken < 0 ) {
.            $yyToken = $yyLex->advance() ? $yyLex->token() : 0;
.
t            if ($this->debug != null)
t              $this->debug->lex( $yyState, $yyToken, $yyname( $yyToken ), $yyLex->value() );
.          }
.          if ( ( $yyN = $yySindex[ $yyState ] ) != 0 && ( ( $yyN += $yyToken) >= 0 )
.              && ($yyN < count( $yyTable ) ) && ( $yyCheck[ $yyN ] == $yyToken ) ) {
t            if ( $this->debug != null )
t              $this->debug->shift( $yyState, $yyTable[ $yyN ], $yyErrorFlag - 1 );
.            $yyState = $yyTable[ $yyN ];		// shift to yyN
.            $yyVal = $yyLex->value();
.            $yyToken = -1;
.            if ( $yyErrorFlag > 0) -- $yyErrorFlag;
.            goto yyLoop;
.          }
.          if ( ( $yyN = $yyRindex[ $yyState ] ) != 0 && ( $yyN += $yyToken ) >= 0
.              && $yyN < count( $yyTable ) && $yyCheck[ $yyN ] == $yyToken )
.            $yyN = $yyTable[ $yyN ];			// reduce (yyN)
.          else
.            switch ( $yyErrorFlag) {
.
.            case 0:
.              $this->yyerror( "syntax error", $this->yyExpecting( $yyState ) );
t              if ( $this->debug != null ) $this->debug->error( "syntax error" );
.              // goto case 1;
.            case 1: case 2:
.              $yyErrorFlag = 3;
.              do {
.                if ( ( $yyN = $yySindex[ $yyStates[ $yyTop ] ] ) != 0
.                    && ( $yyN += Token::yyErrorCode ) >= 0 && $yyN < count( yyTable )
.                    && $yyCheck[ $yyN ] == Token::yyErrorCode) {
t                  if ( $this->debug != null )
t                    $this->debug->shift( $yyStates[ $yyTop ], $yyTable[ $yyN ], 3 );
.                  $yyState = $yyTable[ $yyN ];
.                  $yyVal = $yyLex->value();
.                  goto yyLoop;
.                }
t                if ( $this->debug != null ) $this->debug.pop( $yyStates[ $yyTop ] );
.              }
.              while ( --$yyTop >= 0 );
t              if ( $this->debug != null ) $this->debug.reject();
.              throw new \yyParser\yyException( "irrecoverable syntax error" );
.
.            case 3:
.              if ( $yyToken == 0 ) {
t                if ( $this->debug != null ) $this->debug.reject();
.                throw new \yyParser\yyException( "irrecoverable syntax error at end-of-file" );
.              }
t              if ( $this->debug != null )
t                $this->debug->discard( $yyState, $yyToken, $yyname( $yyToken ), $yyLex->value());
.              $yyToken = -1;
.              goto yyDiscarded;		// leave stack alone
.            }
.        }
.        $yyV = $yyTop + 1 - $yyLen[ $yyN ];
t        if ( $this->debug != null)
t          $this->debug->reduce( $yyState, $yyStates[ $yyV - 1 ], $yyN, $yyRule[ $yyN ], $yyLen[ $yyN ] );
.        $yyVal = $this->yyDefault( $yyV > $yyTop ? null : $yyVals[ $yyV ] );
.        switch( $yyN) {

 actions		## code from the actions within the grammar

.        }
.        $yyTop -= $yyLen[ $yyN ];
.        $yyState = $yyStates[ $yyTop ];
.        $yyM = $yyLhs[ $yyN ];
.        if ( $yyState == 0 && $yyM == 0) {
t          if ($this->debug != null) $this->debug.shift(0, yyFinal);
.          $yyState = $yyFinal;
.          if ( $yyToken < 0 ) {
.            $yyToken = $yyLex->advance() ? $yyLex->token() : 0;

t            if ( $this->debug != null)
t               $this->debug->lex( $yyState, $yyToken, $this->yyname( $yyToken ), $yyLex->value());
.          }
.          if ( $yyToken == 0 ) {
t            if ( $this->debug != null ) $this->debug->accept( $yyVal );
.            return $yyVal;
.          }
.          goto yyLoop;
.        }
.        if ( ( ( $yyN = $yyGindex[ $yyM ] ) != 0 ) && ( ( $yyN += $yyState ) >= 0 )
.            && ( $yyN < count( $yyTable ) ) && ( $yyCheck[ $yyN ] == $yyState ) )
.          $yyState = $yyTable[ $yyN ];
.        else
.          $yyState = $yyDgoto[ $yyM ];
t        if ( $this->debug != null ) $this->debug->shift( $yyStates[ $yyTop ], $yyState );
.	 goto yyLoop;
.      }
.    }
.  }
.
 tables			## tables for rules, default reduction, and action calls
.
 epilog			## text following second %%
.// %token constants
. public class Token {
 tokens public const int
. }
