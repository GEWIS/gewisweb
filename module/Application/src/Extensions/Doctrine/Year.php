<?php

declare(strict_types=1);

namespace Application\Extensions\Doctrine;

use Doctrine\ORM\Query\AST\Functions\FunctionNode;
use Doctrine\ORM\Query\AST\Node;
use Doctrine\ORM\Query\Parser;
use Doctrine\ORM\Query\SqlWalker;
use Doctrine\ORM\Query\TokenType;
use Override;

/**
 * YearFunction ::= "YEAR" "(" ArithmeticPrimary ")"
 */
class Year extends FunctionNode
{
    public Node $yearExpression;

    #[Override]
    public function parse(Parser $parser): void
    {
        $parser->match(TokenType::T_IDENTIFIER);
        $parser->match(TokenType::T_OPEN_PARENTHESIS);
        $this->yearExpression = $parser->ArithmeticPrimary();
        $parser->match(TokenType::T_CLOSE_PARENTHESIS);
    }

    #[Override]
    public function getSql(SqlWalker $sqlWalker): string
    {
        return 'YEAR(' . $this->yearExpression->dispatch($sqlWalker) . ')';
    }
}
