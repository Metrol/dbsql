<?php
/**
 * @author        "Michael Collette" <metrol@metrol.net>
 * @package       Metrol/DBSql
 * @version       1.0
 * @copyright (c) 2016, Michael Collette
 */

namespace Metrol\DBSql\MySQL;

use Metrol\DBSql\BindingsTrait;
use Metrol\DBSql\IndentTrait;
use Metrol\DBSql\StatementInterface;
use Metrol\DBSql\WithInterface;

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
     * @var Select[]
     */
    protected $withStack;

    /**
     * The last portion of the SQL following the rest of the WITH statement
     *
     * @var string
     */
    protected $suffix;

    /**
     * Instantiate and initialize the object
     *
     */
    public function __construct()
    {
        $this->initBindings();
        $this->initIndent();

        $this->withStack     = array();
        $this->suffix        = '';
    }

    /**
     * Just a fast way to call the output() method
     *
     * @return string
     */
    public function __toString()
    {
        return $this->output().PHP_EOL;
    }

    /**
     * Produces the output of all the information that was set in the object.
     *
     * @return string Formatted SQL
     */
    public function output()
    {
        return $this->buildSQL();
    }

    /**
     * Adds a statement to the stack
     *
     * @param string             $alias
     * @param StatementInterface $statement
     *
     * @return $this
     */
    public function setStatement($alias, StatementInterface $statement)
    {
        $this->withStack[$alias] = $statement;

        return $this;
    }

    /**
     * Sets the suffix of the SQL that is appended after the clauses of the
     * WITH statement.
     *
     * @param StatementInterface $statement
     *
     * @return $this
     */
    public function setSuffix(StatementInterface $statement)
    {
        $this->suffix = $statement->output();

        return $this;
    }

    /**
     * Build out the SQL and gather all the bindings to be ready to push to PDO
     *
     * @return string
     */
    protected function buildSQL()
    {
        $sql = 'WITH';

        $sql .= $this->buildStatements();
        $sql .= $this->buildSuffix();

        return $sql;
    }

    /**
     * Builds the statements and returns the result
     *
     * @return string
     */
    protected function buildStatements()
    {
        if ( empty($this->withStack) )
        {
            return '';
        }

        $sql = PHP_EOL;

        foreach ( $this->withStack as $alias => $statement )
        {
            $sql .= $this->quoter()->quoteField($alias);
            $sql .= ' AS '.PHP_EOL;
            $sql .= '('.PHP_EOL;
            $sql .= $this->indentStatement($statement, 1);
            $sql .= '),'.PHP_EOL;

            $this->mergeBindings($statement);
        }

        $sql = substr($sql, 0, -2);

        return $sql;
    }

    /**
     * Build the suffix portion of the With statement
     *
     * @return string
     */
    protected function buildSuffix()
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
