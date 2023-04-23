<?php

declare(strict_types=1);

namespace Application\Extensions\Doctrine;

use Doctrine\ORM\Query\AST\Functions\FunctionNode;
use Doctrine\ORM\Query\{
    Lexer,
    Parser,
    SqlWalker,
};

/**
 * This extension adds SQL RAND() functionality to DQL for returning random
 * database entries.
 * This code originates from https://gist.github.com/Ocramius/919465.
 *
 * Usage: Call addSelect('RAND() as HIDDEN rand') and orderBy('rand') on a QueryBuilder object
 */
class Rand extends FunctionNode
{
    /**
     * @param Parser $parser
     */
    public function parse(Parser $parser): void
    {
        $parser->match(Lexer::T_IDENTIFIER);
        $parser->match(Lexer::T_OPEN_PARENTHESIS);
        $parser->match(Lexer::T_CLOSE_PARENTHESIS);
    }

    /**
     * @param SqlWalker $sqlWalker
     *
     * @return string
     */
    public function getSql(SqlWalker $sqlWalker): string
    {
        return 'RAND()';
    }
}
