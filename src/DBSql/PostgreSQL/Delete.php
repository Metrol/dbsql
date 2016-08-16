<?php
/**
 * @author        "Michael Collette" <metrol@metrol.net>
 * @package       Metrol/DBSql
 * @version       1.0
 * @copyright (c) 2016, Michael Collette
 */

namespace Metrol\DBSql\PostgreSQL;

use Metrol\DBSql\DeleteInterface;
use Metrol\DBSql\BindingsTrait;
use Metrol\DBSql\IndentTrait;
use Metrol\DBSql\StackTrait;
use Metrol\DBSql\OutputTrait;

/**
 * Creates an Delete SQL statement for PostgreSQL
 *
 */
class Delete implements DeleteInterface
{
    use OutputTrait, BindingsTrait, IndentTrait, StackTrait, QuoterTrait, WhereTrait;

    /**
     * The table the delete is targeted at.
     *
     * @var string
     */
    protected $table;

    /**
     * Can be set to request a value to be returned from the update
     *
     * @var string
     */
    protected $returningField;

    /**
     * Instantiate and initialize the object
     *
     */
    public function __construct()
    {
        $this->initBindings();
        $this->initIndent();
        $this->initStacks();

        $this->table          = '';
        $this->returningField = null;
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
     * Set the table that is targeted to delete data from
     *
     * @param string $tableName
     *
     * @return $this
     */
    public function table($tableName)
    {
        $this->table = $this->quoter()->quoteTable($tableName);

        return $this;
    }

    /**
     * Request back information on the rows that were deleted
     *
     * @param string $fieldName
     *
     * @return $this
     */
    public function returning($fieldName)
    {
        $this->returningField = $this->quoter()->quoteField($fieldName);

        return $this;
    }

    /**
     * Build the DELETE statement
     *
     * @return string
     */
    protected function buildSQL()
    {
        $sql = 'DELETE'.PHP_EOL;

        $sql .= $this->buildTable();
        $sql .= $this->buildWhere();
        $sql .= $this->buildReturning();

        return $sql;
    }

    /**
     * Build out the table that will have records deleted from
     *
     * @return string
     */
    protected function buildTable()
    {
        if ( empty($this->table) )
        {
            return '';
        }

        $sql = 'FROM'.PHP_EOL;
        $sql .= $this->indent().$this->table.PHP_EOL;

        return $sql;
    }

    /**
     * Build out the WHERE clause
     *
     * @return string
     */
    protected function buildWhere()
    {
        $sql = '';
        $delimeter = PHP_EOL.$this->indent().'AND'.PHP_EOL.$this->indent();

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

    /**
     * Build the returning clause of the statement
     *
     * @return string
     */
    protected function buildReturning()
    {
        $sql = '';

        if ( $this->returningField !== null )
        {
            $sql .= 'RETURNING'.PHP_EOL;
            $sql .= $this->indent().$this->returningField.PHP_EOL;
        }

        return $sql;
    }
}
