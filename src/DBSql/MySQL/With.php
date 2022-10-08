<?php
/**
 * @author        "Michael Collette" <metrol@metrol.net>
 * @package       Metrol/DBSql
 * @version       1.0
 * @copyright (c) 2016, Michael Collette
 */

namespace Metrol\DBSql\MySQL;

use Metrol\DBSql\{BindingsTrait, IndentTrait, StatementInterface, WithInterface};

/**
 * Creates a collection of statements within a WITH Common Table Expression
 *
 */
class With implements WithInterface
{
    use BindingsTrait, IndentTrait, QuoterTrait;

    /**
     * The collection of statements that are keyed by their alias name.
     *
     */
    protected array $withStack = [];

    /**
     * The last portion of the SQL following the rest of the WITH statement
     *
     */
    protected string $suffix = '';

    /**
     * Instantiate and initialize the object
     *
     */
    public function __construct()
    {
        $this->initBindings();
        $this->initIndent();
    }

    /**
     * Just a fast way to call the output() method
     *
     */
    public function __toString(): string
    {
        return $this->output() . PHP_EOL;
    }

    /**
     * Produces the output of all the information that was set in the object.
     *
     */
    public function output(): string
    {
        return $this->buildSQL();
    }

    /**
     * Adds a statement to the stack
     *
     */
    public function setStatement(string $alias, StatementInterface $statement): static
    {
        $this->withStack[$alias] = $statement;

        return $this;
    }

    /**
     * Sets the suffix of the SQL that is appended after the clauses of the
     * WITH statement.
     *
     */
    public function setSuffix(StatementInterface $statement): static
    {
        $this->suffix = $statement->output();

        return $this;
    }

    /**
     * Build out the SQL and gather all the bindings to be ready to push to PDO
     *
     */
    protected function buildSQL(): string
    {
        $sql = 'WITH';

        $sql .= $this->buildStatements();
        $sql .= $this->buildSuffix();

        return $sql;
    }

    /**
     * Builds the statements and returns the result
     *
     */
    protected function buildStatements(): string
    {
        if ( empty($this->withStack) )
        {
            return '';
        }

        $sql = PHP_EOL;

        foreach ( $this->withStack as $alias => $statement )
        {
            $sql .= $this->quoter()->quoteField($alias);
            $sql .= ' AS ' . PHP_EOL;
            $sql .= '(' . PHP_EOL;
            $sql .= $this->indentStatement($statement, 1);
            $sql .= '),' . PHP_EOL;

            $this->mergeBindings($statement);
        }

        return substr($sql, 0, -2);
    }

    /**
     * Build the suffix portion of the With statement
     *
     */
    protected function buildSuffix(): string
    {
        $sql = '';

        if ( !empty($this->suffix) )
        {
            $sql .= PHP_EOL;
            $sql .= $this->suffix;
        }

        return $sql;
    }
}
