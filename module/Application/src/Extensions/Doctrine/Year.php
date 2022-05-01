<?php

namespace Application\Extensions\Doctrine;

use Doctrine\ORM\Query\AST\Functions\FunctionNode;
use Doctrine\ORM\Query\{
    AST\Node,
    Lexer,
    Parser,
    SqlWalker,
};

/**
 * YearFunction ::= "YEAR" "(" ArithmeticPrimary ")"
 */
class Year extends FunctionNode
{
    public Node $yearExpression;

    public function parse(Parser $parser): void
    {
        $parser->match(Lexer::T_IDENTIFIER);
        $parser->match(Lexer::T_OPEN_PARENTHESIS);
        $this->yearExpression = $parser->ArithmeticPrimary();
        $parser->match(Lexer::T_CLOSE_PARENTHESIS);
    }

    public function getSql(SqlWalker $sqlWalker): string
    {
        return 'YEAR(' . $this->yearExpression->dispatch($sqlWalker) . ')';
    }
}
