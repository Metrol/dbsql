<?php
/**
 * @author        "Michael Collette" <metrol@metrol.net>
 * @package       Metrol/DBSql
 * @version       1.0
 * @copyright (c) 2016, Michael Collette
 */

namespace Metrol\DBSql\PostgreSQL;

use Metrol\DBSql\UpdateInterface;
use Metrol\DBSql\Bindings;
use Metrol\DBSql\Indent;
use Metrol\DBSql\Stacks;

/**
 * Creates an Update SQL statement for PostgreSQL
 *
 */
class Update implements UpdateInterface
{
    use Bindings, Indent, Stacks, Quoter, Where;

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
     * Set the table that is targeted for the data.
     *
     * @param string $tableName
     *
     * @return self
     */
    public function table(string $tableName): self
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
     * @param string $fieldName
     * @param mixed  $value
     * @param mixed  $boundValue
     *
     * @return self
     */
    public function fieldValue(string $fieldName, $value, $boundValue = null): self
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
        else if ( substr($value, 0, 1) === ':' // Starts with a colon
            and $boundValue !== null           // Has a bound value
            and strpos($value, ' ') === false  // No spaces in the named binding
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
     * @param array $fieldValues  Expect array['fieldName'] = 'value to update'
     *
     * @return self
     */
    public function fieldValues(array $fieldValues): self
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
     * Request back an auto sequencing field by name
     *
     * @param string $fieldName
     *
     * @return self
     */
    public function returning($fieldName): self
    {
        $this->returningField = $this->quoter()->quoteField($fieldName);

        return $this;
    }

    /**
     * Build the UPDATE statement
     *
     * @return string
     */
    protected function buildSQL(): string
    {
        $sql = 'UPDATE';

        $sql .= $this->buildTable();
        $sql .= $this->buildFieldValues();
        $sql .= $this->buildWhere();
        $sql .= $this->buildReturning();

        return $sql;
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

        $sql = PHP_EOL.$this->indent().$this->table.PHP_EOL;

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
     * @return string
     */
    protected function buildWhere(): string
    {
        $sql = '';
        $delimeter = PHP_EOL.$this->indent().'AND'.PHP_EOL.$this->indent();

        if ( ! empty($this->whereStack) )
        {
            $sql .= 'WHERE'.PHP_EOL;
            $sql .= $this->indent();
            $sql .= implode($delimeter, $this->whereStack ).PHP_EOL;
        }

        return $sql;
    }

    /**
     * Build the returning clause of the statement
     *
     * @return string
     */
    protected function buildReturning(): string
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
