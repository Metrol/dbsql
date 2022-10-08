<?php
/**
 * @author        "Michael Collette" <metrol@metrol.net>
 * @package       Metrol/DBSql
 * @version       1.0
 * @copyright (c) 2016, Michael Collette
 */

namespace Metrol\DBSql\MySQL;

use Metrol\DBSql\{UpdateInterface, BindingsTrait, IndentTrait, StackTrait};

/**
 * Creates an Update SQL statement for MySQL
 *
 */
class Update implements UpdateInterface
{
    use BindingsTrait, IndentTrait, StackTrait, QuoterTrait, WhereTrait;

    /**
     * The table the update is targeted at.
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
        return $this->output().PHP_EOL;
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
     * Set the table that is targeted for the data.
     *
     */
    public function table(string $tableName): static
    {
        $this->table = $this->quoter()->quoteTable($tableName);

        return $this;
    }

    /**
     * Add a field and an optionally bound value to the stack.
     *
     * To automatically bind a value, the 3rd argument must be provided a value
     * and the 2nd argument needs to be...
     * - Question mark '?'
     * - Empty string ''
     * - null
     *
     * A named binding can be accepted when the 3rd argument has a value and
     * the 2nd argument is a string that starts with a colon that contains no
     * empty spaces.
     *
     * A non-bound value is not quoted or escaped in any way.  Use with all
     * due caution.
     *
     */
    public function fieldValue(string $fieldName, mixed $value, mixed $boundValue = null): static
    {
        $fieldName = $this->quoter()->quoteField($fieldName);

        if ( $boundValue !== null and (   $value === '?'
                or $value === ''
                or $value === null)
        )
        {
            $bindLabel = $this->getBindLabel();
            $this->setBinding($bindLabel, $boundValue);
            $this->fieldStack[$fieldName] = $bindLabel;
        }
        else if ( str_starts_with($value, ':') // Starts with a colon
            and $boundValue !== null           // Has a bound value
            and ! str_contains($value, ' ')    // No spaces in the named binding
        )
        {
            $this->setBinding($value, $boundValue);
            $this->fieldStack[$fieldName] = $value;
        }
        else
        {
            $this->fieldStack[$fieldName] = $value;
        }

        return $this;
    }

    /**
     * Add a set of fields with values to the select request.
     * Values automatically create bindings.
     *
     * Expects array['fieldName'] = 'value to update'
     *
     */
    public function fieldValues(array $fieldValues): static
    {
        foreach ( $fieldValues as $fieldName => $value )
        {
            $bindLabel = $this->getBindLabel();
            $this->setBinding($bindLabel, $value);
            $fieldNameQuoted = $this->quoter()->quoteField($fieldName);
            $this->fieldStack[$fieldNameQuoted] = $bindLabel;
        }

        return $this;
    }

    /**
     * Build the UPDATE statement
     *
     */
    protected function buildSQL(): string
    {
        $sql = 'UPDATE';

        $sql .= $this->buildTable();
        $sql .= $this->buildFieldValues();
        $sql .= $this->buildWhere();

        return $sql;
    }

    /**
     * Build the table that will be getting updated
     *
     */
    protected function buildTable(): string
    {
        if ( empty($this->table) )
        {
            return PHP_EOL;
        }

        return PHP_EOL . $this->indent() . $this->table . PHP_EOL;
    }

    /**
     * Build the field value assignments area of the statement
     *
     */
    protected function buildFieldValues(): string
    {
        $sql = '';

        if ( empty($this->fieldStack) )
        {
            return $sql;
        }

        $assign = array();

        foreach ( $this->fieldStack as $fieldName => $value )
        {
            $assign[] = $this->indent().$fieldName.' = '.$value;
        }

        $sql .= 'SET'.PHP_EOL;
        $sql .= implode(','.PHP_EOL, $assign).PHP_EOL;

        return $sql;
    }

    /**
     * Build out the WHERE clause
     *
     */
    protected function buildWhere(): string
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
}
