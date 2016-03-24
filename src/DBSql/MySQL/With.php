<?php
/**
 * @author        "Michael Collette" <metrol@metrol.net>
 * @package       Metrol/DBSql
 * @version       1.0
 * @copyright (c) 2016, Michael Collette
 */

namespace Metrol\DBSql\MySQL;

use Metrol\DBSql\Bindings;
use Metrol\DBSql\Indent;
use Metrol\DBSql\StatementInterface;
use Metrol\DBSql\WithInterface;

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
        if ( empty($this->withStack) )
        {
            return '';
        }

        $sql = 'WITH';
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
