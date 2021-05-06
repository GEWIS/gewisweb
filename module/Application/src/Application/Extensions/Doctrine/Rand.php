<?php

namespace Application\Extensions\Doctrine;

use Doctrine\ORM\Query\AST\Functions\FunctionNode;
use Doctrine\ORM\Query\Lexer;

/**
 * This extension adds SQL RAND() functionality to DQL for returning random
 * database entries.
 * This code originates from https://gist.github.com/Ocramius/919465
 *
 * Usage: Call addSelect('RAND() as HIDDEN rand') and orderBy('rand') on a QueryBuilder object
 */
class Rand extends FunctionNode
{
    public function parse(\Doctrine\ORM\Query\Parser $parser)
    {
        $parser->match(Lexer::T_IDENTIFIER);
        $parser->match(Lexer::T_OPEN_PARENTHESIS);
        $parser->match(Lexer::T_CLOSE_PARENTHESIS);
    }

    public function getSql(\Doctrine\ORM\Query\SqlWalker $sqlWalker)
    {
        return 'RAND()';
    }
}
