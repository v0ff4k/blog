<?php

namespace AppBundle\DBAL;

use AppBundle\Helper\UserHelper;
use Doctrine\ORM\Query\AST\Functions\FunctionNode;
use Doctrine\ORM\Query\AST\Subselect;
use Doctrine\ORM\Query\Lexer;
use Doctrine\ORM\Query\Parser;
use Doctrine\ORM\Query\SqlWalker;

/**
 * LimFunction ::=
 *     "LIM" "(" Subselect ")"
 *
 * Class LimFunction - for enable support 'limit' in sub queries.
 * @package AppBundle\DBAL
 */
class LimFunction extends FunctionNode
{
    /**
     * @var Subselect
     */
    private $subselect;

    /**
     * {@inheritdoc}
     * @throws \Doctrine\ORM\Query\QueryException
     */
    public function parse(Parser $parser)
    {
        $parser->match(Lexer::T_IDENTIFIER);
        $parser->match(Lexer::T_OPEN_PARENTHESIS);
        $this->subselect = $parser->Subselect();
        $parser->match(Lexer::T_CLOSE_PARENTHESIS);
    }

    /**
     * {@inheritdoc}
     * @throws \Doctrine\ORM\Query\AST\ASTException
     */
    public function getSql(SqlWalker $sqlWalker)
    {
        return '(' . $this->subselect->dispatch($sqlWalker) . ' LIMIT 1 )';
    }
}
