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
     * Adds a field = value pair to be updated.  Values automatically have
     * bindings created for them.
     *
     * @param string $fieldName
     * @param mixed  $value
     *
     * @return self
     */
    public function fieldValue(string $fieldName, $value): self
    {
        // Assign the value to a new binding label
        $bindLabel = $this->getBindLabel();
        $this->setBinding($bindLabel, $value);

        $fieldName = $this->quoter()->quoteField($fieldName);

        // Save the binding label against the quoted field name
        $this->fieldStack[$fieldName] = $bindLabel;

        return $this;
    }

    /**
     * Adds a field = value pair to be updated.  Fields will be quoted, but
     * values are passed along as is, without binding or escaping.
     *
     * @param string $fieldName
     * @param mixed  $value
     *
     * @return self
     */
    public function fieldValueNoBind(string $fieldName, $value): self
    {
        $fieldName = $this->quoter()->quoteField($fieldName);

        // For better or worse, push the value as it came in
        $this->fieldStack[$fieldName] = $value;

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
    protected function buildSQL()
    {
        $sql = 'UPDATE';

        if ( empty($this->table) )
        {
            return $sql;
        }

        $sql .= PHP_EOL.$this->indent().$this->table.PHP_EOL;

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

        if ( ! empty($this->whereStack) )
        {
            $delimeter = PHP_EOL.$this->indent().'AND'.PHP_EOL;
            $sql .= 'WHERE'.PHP_EOL;
            $sql .= implode($delimeter, $this->whereStack ).PHP_EOL;
        }

        if ( $this->returningField !== null )
        {
            $sql .= 'RETURNING '.$this->returningField.PHP_EOL;
        }

        return $sql;
    }
}
