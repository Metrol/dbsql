<?php
/**
 * @author        "Michael Collette" <metrol@metrol.net>
 * @package       Metrol/DBSql
 * @version       1.0
 * @copyright (c) 2016, Michael Collette
 */


namespace Metrol\DBSql\PostgreSQL;

use Metrol\DBSql\InsertInterface;
use Metrol\DBSql\SelectInterface;
use Metrol\DBSql\BindingsTrait;
use Metrol\DBSql\IndentTrait;
use Metrol\DBSql\StackTrait;
use Metrol\DBSql\OutputTrait;

/**
 * Creates an Insert SQL statement for PostgreSQL
 *
 */
class Insert implements InsertInterface
{
    use OutputTrait, BindingsTrait, IndentTrait, StackTrait, QuoterTrait;

    /**
     * The table the insert is targeted at.
     *
     * @var string
     */
    protected $tableInto;

    /**
     * Can be set to request a value to be returned from the insert
     *
     * @var string|null
     */
    protected $returningFields;

    /**
     * When specified, this SELECT statement will be used as the source of
     * values for the INSERT.
     *
     * @var Select|null
     */
    protected $select;

    /**
     * Instantiate and initialize the object
     *
     */
    public function __construct()
    {
        $this->initBindings();
        $this->initIndent();
        $this->initStacks();

        $this->tableInto       = '';
        $this->returningFields = [];
        $this->select          = null;
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
     * Set the table that is targeted for the data.
     *
     * @param string $tableName
     *
     * @return $this
     */
    public function table(string $tableName)
    {
        $this->tableInto = $this->quoter()->quoteTable($tableName);

        return $this;
    }

    /**
     * Sets a SELECT statement that will be used as the source of data for the
     * INSERT.
     * - Any values that have been set will be ignored.
     * - Any bindings from the Select statement will be merged.
     *
     * @param SelectInterface $select
     *
     * @return $this
     */
    public function valueSelect(SelectInterface $select)
    {
        $this->select = $select;

        return $this;
    }

    /**
     * Request back an auto sequencing field by name
     *
     * @param string|string[]
     *
     * @return $this
     */
    public function returning($fieldName)
    {
        if ( !is_array($fieldName) )
        {
            $fieldName = [$fieldName];
        }

        foreach ( $fieldName as $field )
        {
            $this->returningFields[] = $this->quoter()->quoteField($field);
        }

        return $this;
    }

    /**
     * Build the INSERT statement
     *
     * @return string
     */
    protected function buildSQL()
    {
        $sql = 'INSERT'.PHP_EOL;

        $sql .= $this->buildTable();
        $sql .= $this->buildFields();
        $sql .= $this->buildValues();
        $sql .= $this->buildBindings();
        $sql .= $this->buildValuesFromSelect();
        $sql .= $this->buildReturning();

        return $sql;
    }

    /**
     * Build the table that will have data inserted into
     *
     * @return string
     */
    protected function buildTable()
    {
        $sql = '';

        if ( empty($this->tableInto) )
        {
            return $sql;
        }

        $sql .= 'INTO'.PHP_EOL;
        $sql .= $this->indent();
        $sql .= $this->tableInto.PHP_EOL;

        return $sql;
    }

    /**
     * Build the field stack
     *
     * @return string
     */
    protected function buildFields()
    {
        $sql = '';

        // A set of fields isn't really required, even if it's a really good
        // idea to have them.  If nothings there, leave it empty.
        if ( $this->fieldValueSet->isEmpty() )
        {
            return $sql;
        }

        $fieldNames = [];

        foreach ( $this->fieldValueSet->getFieldNames() as $fn )
        {
            $fieldNames[] = $this->quoter()->quoteField($fn);
        }

        $sql .= $this->indent().'(';
        $sql .= implode(', ', $fieldNames);
        $sql .= ')'.PHP_EOL;

        return $sql;
    }

    /**
     * Build out the values to be inserted
     *
     * @return string
     */
    protected function buildValues()
    {
        $sql = '';

        // Only add values when something is on the stack and there isn't a
        // SELECT statement waiting to go in there instead.
        if ( $this->fieldValueSet->isEmpty() )
        {
            return $sql;
        }

        $markers = $this->fieldValueSet->getValueMarkers();

        if ( count($markers) == 0 )
        {
            return $sql;
        }

        $sql .= 'VALUES' . PHP_EOL;
        $sql .= $this->indent() . '(';
        $sql .= implode(', ', $markers);
        $sql .= ')' . PHP_EOL;

        return $sql;
    }

    /**
     * Push the value bindings on to the stack
     *
     */
    protected function buildBindings()
    {
        $this->bindings = $this->fieldValueSet->getBoundValues();
    }

    /**
     * If the values are coming from a sub-select, this builds this for the
     * larger query.
     *
     * @return string
     */
    protected function buildValuesFromSelect()
    {
        $sql = '';

        // Check for a SELECT statement and append if available
        if ( is_object($this->select) )
        {
            $sql .= $this->indentStatement($this->select, 1);

            $this->mergeBindings($this->select);
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

        if ( ! empty($this->returningFields) )
        {
            $sql .= 'RETURNING' . PHP_EOL;
            $sql .= $this->indent();
            $sql .= implode(', ', $this->returningFields);
            $sql .= PHP_EOL;
        }

        return $sql;
    }
}
