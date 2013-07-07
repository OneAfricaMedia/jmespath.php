<?php

namespace JamesPath;

/**
 * JamesPath recursive descent lexer
 */
class Lexer implements \Iterator
{
    const T_IDENTIFIER = 0;
    const T_DOT = 1;
    const T_STAR = 2;
    const T_LBRACKET = 3;
    const T_RBRACKET = 4;
    const T_NUMBER = 5;
    const T_OR = 6;
    const T_IGNORE = 7;
    const T_EOF = 8;
    const T_UNKNOWN = 9;

    private $input;
    private $tokens;

    private $regex = '/
        (\w+)     # T_IDENTIFIER
        |\s+      # Ignore whitespace
        |(\.)     # T_DOT
        |(\*)     # T_STAR
        |(\[)     # T_LBRACKET
        |(\])     # T_RBRACKET
        |(\-?\d+) # T_NUMBER
        |(\|\|)   # T_OR
        |(.)      # T_UNKNOWN
    /x';

    private $simpleTokens = array(
        '.'  => self::T_DOT,
        '*'  => self::T_STAR,
        '['  => self::T_LBRACKET,
        ']'  => self::T_RBRACKET,
        '||' => self::T_OR
    );

    /**
     * Set the expression to parse and reset state
     *
     * @param string $input Input expression
     */
    public function setInput($input)
    {
        $this->input = $input;
        $this->tokenize();
        $this->rewind();
    }

    /**
     * @return string
     */
    public function getInput()
    {
        return $this->input;
    }

    /**
     * Get the name of a token
     *
     * @param int $token Token integer
     * @return string|bool
     */
    public function getTokenName($token)
    {
        $ref = new \ReflectionClass($this);

        return array_search($token, $ref->getConstants());
    }

    public function current()
    {
        return current($this->tokens) ?: Token::getEof();
    }

    public function key()
    {
        return key($this->tokens);
    }

    public function rewind()
    {
        reset($this->tokens);
    }

    public function valid()
    {
        return (bool) current($this->tokens);
    }

    public function next()
    {
        next($this->tokens);
    }

    private function tokenize()
    {
        $this->tokens = array();
        $tokens = preg_split(
            $this->regex,
            $this->input,
            -1,
            PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY | PREG_SPLIT_OFFSET_CAPTURE
        );

        foreach ($tokens as $token) {
            if (isset($this->simpleTokens[$token[0]])) {
                $this->tokens[] = new Token($this->simpleTokens[$token[0]], $token[0], $token[1]);
            } elseif (is_numeric($token[0])) {
                $this->tokens[] = new Token(self::T_NUMBER, (int) $token[0], $token[1]);
            } elseif (ctype_alnum(($token[0]))) {
                $this->tokens[] = new Token(self::T_IDENTIFIER, $token[0], $token[1]);
            } else {
                $this->tokens[] = new Token(self::T_UNKNOWN, $token[0], $token[1]);
            }
        }
    }
}
