<?php

declare(strict_types=1);

namespace Application\Extensions\Doctrine;

use Doctrine\ORM\Query\AST\Functions\FunctionNode;
use Doctrine\ORM\Query\Parser;
use Doctrine\ORM\Query\SqlWalker;
use Doctrine\ORM\Query\TokenType;
use Override;

/**
 * This extension adds SQL RAND() functionality to DQL for returning random
 * database entries.
 * This code originates from https://gist.github.com/Ocramius/919465.
 *
 * Usage: Call addSelect('RAND() as HIDDEN rand') and orderBy('rand') on a QueryBuilder object
 */
class Rand extends FunctionNode
{
    #[Override]
    public function parse(Parser $parser): void
    {
        $parser->match(TokenType::T_IDENTIFIER);
        $parser->match(TokenType::T_OPEN_PARENTHESIS);
        $parser->match(TokenType::T_CLOSE_PARENTHESIS);
    }

    #[Override]
    public function getSql(SqlWalker $sqlWalker): string
    {
        return 'RAND()';
    }
}
