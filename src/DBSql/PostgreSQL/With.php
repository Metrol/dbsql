<?php
/**
 * @author        "Michael Collette" <metrol@metrol.net>
 * @package       Metrol/DBSql
 * @version       1.0
 * @copyright (c) 2016, Michael Collette
 */

namespace Metrol\DBSql\PostgreSQL;

use Metrol\DBSql\BindingsTrait;
use Metrol\DBSql\IndentTrait;
use Metrol\DBSql\OutputTrait;
use Metrol\DBSql\PostgreSQL\With\Recursive;
use Metrol\DBSql\WithInterface;
use Metrol\DBSql\StatementInterface;

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
     * Contains the Select statement, the fields, and alias for a recursive
     * With statement.
     *
     * @var With/Recursive
     */
    protected $recursive;

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
        $this->recursive     = new Recursive;
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
     * Sets up the first clause of the With statement to be recursive.
     * This needs the alias of the clause, a union statement to appear within it,
     * and optional fields.
     *
     * @param string $alias
     * @param Union  $union
     * @param array  $fields
     *
     * @return $this
     */
    public function setRecursive($alias, Union $union,
                                 array $fields = null)
    {
        $this->recursive->setUnion($alias, $union);

        if ( $fields !== null and !empty($fields) )
        {
            $this->recursive->setFields($fields);
        }

        $this->mergeBindings($union);

        return $this;
    }

    /**
     * Adds a statement to the stack
     *
     * @param string             $alias
     * @param StatementInterface $statement
     *
     * @return $this
     */
    public function setStatement(string $alias, StatementInterface $statement)
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

        $sql .= $this->buildRecursive();
        $sql .= $this->buildStatements();
        $sql .= $this->buildSuffix();

        return $sql;
    }

    /**
     * Build the Recursive portion of the SQL
     *
     * @return string
     */
    protected function buildRecursive()
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
     * @return string
     */
    protected function buildStatements()
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
