<?php
/**
 * @author        "Michael Collette" <metrol@metrol.net>
 * @package       Metrol/DBSql
 * @version       1.0
 * @copyright (c) 2016, Michael Collette
 */

namespace Metrol\DBSql\PostgreSQL;

use Metrol\DBSql\Bindings;
use Metrol\DBSql\Indent;
use Metrol\DBSql\PostgreSQL\With\Recursive;
use Metrol\DBSql\WithInterface;
use Metrol\DBSql\StatementInterface;

/**
 * Creates a collection of statements within a WITH Common Table Expression
 *
 */
class With implements WithInterface
{
    use Bindings, Indent, Quoter;

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
    public function __toString(): string
    {
        return $this->output().PHP_EOL;
    }

    /**
     * Produces the output of all the information that was set in the object.
     *
     * @return string Formatted SQL
     */
    public function output(): string
    {
        return $this->buildSQL();
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
     * @return self
     */
    public function setRecursive(string $alias, Union $union,
                                 array $fields = null): self
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
     * @return self
     */
    public function setStatement(string $alias, StatementInterface $statement)
    {
        $this->withStack[$alias] = $statement;
        $this->mergeBindings($statement);

        return $this;
    }

    /**
     * Sets the suffix of the SQL that is appended after the clauses of the
     * WITH statement.
     *
     * @param StatementInterface $statement
     *
     * @return self
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
        if ( empty($this->withStack) and !$this->recursive->isReady() )
        {
            // return '';
        }

        $sql = 'WITH';

        if ( $this->recursive->isReady() )
        {
            $sql .= $this->recursive->output();
        }

        $sql .= PHP_EOL;

        foreach ( $this->withStack as $alias => $statement )
        {
            $sql .= $this->quoter()->quoteField($alias);
            $sql .= ' AS '.PHP_EOL;
            $sql .= '('.PHP_EOL;
            $sql .= $this->indentStatement($statement, 1);
            $sql .= '),'.PHP_EOL;
        }

        $sql = substr($sql, 0, -2).PHP_EOL;

        if ( !empty($this->suffix) )
        {
            $sql .= $this->suffix;
        }

        return $sql;
    }
}
