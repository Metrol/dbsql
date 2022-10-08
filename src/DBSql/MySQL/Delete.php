<?php
/**
 * @author        "Michael Collette" <metrol@metrol.net>
 * @package       Metrol/DBSql
 * @version       1.0
 * @copyright (c) 2016, Michael Collette
 */

namespace Metrol\DBSql\MySQL;

use Metrol\DBSql\{DeleteInterface, BindingsTrait, IndentTrait, StackTrait};

/**
 * Creates a Delete SQL statement for MySQL
 *
 */
class Delete implements DeleteInterface
{
    use BindingsTrait, IndentTrait, StackTrait, QuoterTrait, WhereTrait;

    /**
     * The table delete is targeted at.
     *
     */
    protected string $table = '';

    /**
     * Instantiate and initialize the object
     *
     */
    public function __construct()
    {
        $this->initBindings();
        $this->initIndent();
        $this->initStacks();
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
     * Set the table that is targeted to delete data from
     *
     */
    public function table(string $tableName): static
    {
        $this->table = $this->quoter()->quoteTable($tableName);

        return $this;
    }

    /**
     * Build the DELETE statement
     *
     */
    protected function buildSQL(): string
    {
        $sql = 'DELETE' . PHP_EOL;

        $sql .= $this->buildTable();
        $sql .= $this->buildWhere();

        return $sql;
    }

    /**
     * Build out the table that will have records deleted from
     *
     */
    protected function buildTable(): string
    {
        if ( empty($this->table) )
        {
            return '';
        }

        $sql = 'FROM' . PHP_EOL;
        $sql .= $this->indent().$this->table.PHP_EOL;

        return $sql;
    }

    /**
     * Build out the WHERE clause
     *
     */
    protected function buildWhere(): string
    {
        $sql = '';
        $delimeter = PHP_EOL . $this->indent() . 'AND' . PHP_EOL . $this->indent();

        $clauses = [];

        foreach ( $this->whereStack as $whereClause )
        {
            $clauses[] = $whereClause->output();

            foreach ( $whereClause->getBindings() as $key => $value )
            {
                $this->setBinding($key, $value);
            }
        }

        if ( ! empty($clauses) )
        {
            $sql .= 'WHERE'.PHP_EOL;
            $sql .= $this->indent();
            $sql .= implode($delimeter, $clauses).PHP_EOL;
        }

        return $sql;
    }
}
