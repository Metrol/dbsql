<?php
/**
 * @author        "Michael Collette" <metrol@metrol.net>
 * @package       Metrol/DBSql
 * @version       1.0
 * @copyright (c) 2016, Michael Collette
 */

namespace Metrol\DBSql\PostgreSQL;

use Metrol\DBSql\UpdateInterface;
use Metrol\DBSql\BindingsTrait;
use Metrol\DBSql\IndentTrait;
use Metrol\DBSql\StackTrait;
use Metrol\DBSql\OutputTrait;

/**
 * Creates an Update SQL statement for PostgreSQL
 *
 */
class Update implements UpdateInterface
{
    use OutputTrait, BindingsTrait, IndentTrait, StackTrait, QuoterTrait, WhereTrait;

    /**
     * The table the update is targeted at.
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
        return $this->output() . PHP_EOL;
    }

    /**
     * Set the table that is targeted for the data.
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
     * Request back an auto sequencing field by name
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
     * Build the UPDATE statement
     *
     * @return string
     */
    protected function buildSQL()
    {
        $this->buildBindings();

        $sql = 'UPDATE';

        $sql .= $this->buildTable();

        $sql .= $this->buildFieldValues();
        $sql .= $this->buildWhere();
        $sql .= $this->buildReturning();

        return $sql;
    }

    /**
     * Assign the bindings from the field value set to what will go along with
     * this update query.
     *
     */
    protected function buildBindings()
    {
        $this->setBindings( $this->fieldValueSet->getBoundValues() );
    }

    /**
     * Build the table that will be getting updated
     *
     * @return string
     */
    protected function buildTable(): string
    {
        if ( empty($this->table) )
        {
            return PHP_EOL;
        }

        $sql = PHP_EOL . $this->indent() . $this->table . PHP_EOL;

        return $sql;
    }

    /**
     * Build the field value assignements area of the statement
     *
     * @return string
     */
    protected function buildFieldValues(): string
    {
        $sql = '';

        if ( $this->fieldValueSet->isEmpty() )
        {
            return $sql;
        }

        $fieldMarkers = $this->fieldValueSet->getFieldNamesAndMarkers();
        $assign = [];

        foreach ( $fieldMarkers as $fieldName => $marker )
        {
            $fn = $this->quoter()->quoteField($fieldName);
            $assign[] = $this->indent() . $fn . ' = ' . $marker;
        }

        $sql .= 'SET'.PHP_EOL;
        $sql .= implode(',' . PHP_EOL, $assign) . PHP_EOL;

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
