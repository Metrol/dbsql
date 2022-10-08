<?php
/**
 * @author        "Michael Collette" <metrol@metrol.net>
 * @package       Metrol/DBSql
 * @version       1.0
 * @copyright (c) 2016, Michael Collette
 */


namespace Metrol\DBSql\PostgreSQL;

use Metrol\DBSql\{InsertInterface, SelectInterface, BindingsTrait, IndentTrait,
                  StackTrait, OutputTrait};

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
     */
    protected string $tableInto = '';

    /**
     * Can be set to request a value to be returned from the insert
     *
     */
    protected array $returningFields = [];

    /**
     * When specified, this SELECT statement will be used as the source of
     * values for the INSERT.
     *
     */
    protected SelectInterface $select;

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
     * @return string
     */
    public function __toString(): string
    {
        return $this->output() . PHP_EOL;
    }

    /**
     * Set the table that is targeted for the data.
     *
     */
    public function table(string $tableName): static
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
     */
    public function valueSelect(SelectInterface $select): static
    {
        $this->select = $select;

        return $this;
    }

    /**
     * Request back an auto sequencing field by name
     *
     */
    public function returning(string|array $fieldName): static
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
     */
    protected function buildSQL(): string
    {
        $sql = 'INSERT' . PHP_EOL;

        $sql .= $this->buildTable();
        $sql .= $this->buildFields();
        $this->buildBindings();
        $sql .= $this->buildValues();
        $sql .= $this->buildValuesFromSelect();
        $sql .= $this->buildReturning();


        return $sql;
    }

    /**
     * Build the table that will have data inserted into
     *
     */
    protected function buildTable(): string
    {
        $sql = '';

        if ( empty($this->tableInto) )
        {
            return $sql;
        }

        $sql .= 'INTO' . PHP_EOL;
        $sql .= $this->indent();
        $sql .= $this->tableInto . PHP_EOL;

        return $sql;
    }

    /**
     * Build the field stack
     *
     */
    protected function buildFields(): string
    {
        $sql = '';

        // A set of fields isn't really required, even if it's a good
        // idea to have them.  If nothing is there, leave it empty.
        if ( $this->fieldValueSet->isEmpty() )
        {
            return $sql;
        }

        $fieldNames = [];

        foreach ( $this->fieldValueSet->getFieldNames() as $fn )
        {
            $fieldNames[] = $this->quoter()->quoteField($fn);
        }

        $sql .= $this->indent() . '(';
        $sql .= implode(', ', $fieldNames);
        $sql .= ')' . PHP_EOL;

        return $sql;
    }

    /**
     * Build out the values to be inserted
     *
     */
    protected function buildValues(): string
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
    protected function buildBindings(): void
    {
        $this->bindings = $this->fieldValueSet->getBoundValues();
    }

    /**
     * If the values are coming from a sub-select, this builds this for the
     * larger query.
     *
     */
    protected function buildValuesFromSelect(): string
    {
        $sql = '';

        // Check for a SELECT statement and append if available
        if ( isset($this->select) )
        {
            $sql .= $this->indentStatement($this->select, 1);

            $this->mergeBindings($this->select);
        }

        return $sql;
    }

    /**
     * Build the returning clause of the statement
     *
     */
    protected function buildReturning(): string
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
