<?php
/**
 * @author        "Michael Collette" <metrol@metrol.net>
 * @package       Metrol/DBSql
 * @version       1.0
 * @copyright (c) 2016, Michael Collette
 */

namespace Metrol\DBSql\PostgreSQL;

use Metrol\DBSql\{BindingsTrait, IndentTrait, OutputTrait, WithInterface,
                  StatementInterface};
use Metrol\DBSql\PostgreSQL\With\Recursive;

/**
 * Creates a collection of statements within a WITH Common Table Expression
 *
 */
class With implements WithInterface
{
    use OutputTrait, BindingsTrait, IndentTrait, QuoterTrait;

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
     * Contains the Select statement, the fields, and alias for a recursive
     * With statement.
     *
     */
    protected Recursive $recursive;

    /**
     * Instantiate and initialize the object
     *
     */
    public function __construct()
    {
        $this->initBindings();
        $this->initIndent();

        $this->recursive = new Recursive;
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
     * Sets up the first clause of the With statement to be recursive.
     * This needs the alias of the clause, a union statement to appear within it,
     * and optional fields.
     *
     */
    public function setRecursive(string $alias,
                                 Union  $union,
                                 ?array $fields = null): static
    {
        $this->recursive->setUnion($alias, $union);

        if ( ! empty($fields) )
        {
            $this->recursive->setFields($fields);
        }

        $this->mergeBindings($union);

        return $this;
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

        $sql .= $this->buildRecursive();
        $sql .= $this->buildStatements();
        $sql .= $this->buildSuffix();

        return $sql;
    }

    /**
     * Build the Recursive portion of the SQL
     *
     */
    protected function buildRecursive(): string
    {
        $sql = '';

        if ( $this->recursive->isReady() )
        {
            $sql .= $this->recursive->output();
            $sql = substr($sql, 0, -1);
        }

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

        $sql = '';

        if ( $this->recursive->isReady() )
        {
            $sql .= ',';
        }

        $sql .= PHP_EOL;

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
