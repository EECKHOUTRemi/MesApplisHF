<?php

namespace App\Doctrine\DQL;

use Doctrine\ORM\Query\AST\Functions\FunctionNode;
use Doctrine\ORM\Query\Parser;
use Doctrine\ORM\Query\SqlWalker;
use Doctrine\ORM\Query\TokenType;

/**
 * Fonction DQL UNACCENT qui délègue à la fonction PostgreSQL unaccent().
 * Enregistrée dans la configuration Doctrine pour permettre des recherches insensibles aux accents.
 */
class Unaccent extends FunctionNode
{
    /** @var \Doctrine\ORM\Query\AST\Node */
    public $stringExpression;

    /**
     * @param Parser $parser
     * @return void
     */
    public function parse(Parser $parser): void
    {
        $parser->match(TokenType::T_IDENTIFIER);
        $parser->match(TokenType::T_OPEN_PARENTHESIS);
        $this->stringExpression = $parser->StringPrimary();
        $parser->match(TokenType::T_CLOSE_PARENTHESIS);
    }

    /**
     * @param SqlWalker $sqlWalker
     * @return string
     */
    public function getSql(SqlWalker $sqlWalker): string
    {
        return 'unaccent(' . $this->stringExpression->dispatch($sqlWalker) . ')';
    }
}
