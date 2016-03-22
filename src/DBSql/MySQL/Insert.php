<?php
/**
 * @author        "Michael Collette" <metrol@metrol.net>
 * @package       Metrol/DBSql
 * @version       1.0
 * @copyright (c) 2016, Michael Collette
 */


namespace Metrol\DBSql\MySQL;

use Metrol\DBSql\InsertInterface;
use Metrol\DBSql\SelectInterface;
use Metrol\DBSql\Bindings;
use Metrol\DBSql\Indent;
use Metrol\DBSql\Stacks;

/**
 * Creates an Insert SQL statement for MySQL
 *
 */
class Insert implements InsertInterface
{
    use Bindings, Indent, Stacks, Quoter;

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
    protected $returningField;

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

        $this->fieldStack     = array();
        $this->tableInto      = '';
        $this->returningField = null;
        $this->select         = null;
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
    public function into(string $tableName): self
    {
        $this->tableInto = $this->quoter()->quoteTable($tableName);

        return $this;
    }

    /**
     * Add a set of the field names to show up in the INSERT statement.
     * - No value binding provided.
     *
     * @param string[] $fields
     *
     * @return self
     */
    public function fields(array $fields): self
    {
        foreach ( $fields as $fieldName )
        {
            $this->fieldStack[] = $this->quoter()->quoteField($fieldName);
        }

        return $this;
    }

    /**
     * Add a set of the values to assign to the INSERT statement.
     * - No value binding provided.
     * - No automatic quoting.
     *
     * @param array $values
     *
     * @return self
     */
    public function values(array $values): self
    {
        foreach ( $values as $value )
        {
            $this->valueStack[] = $value;
        }

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
     * @return self
     */
    public function valueSelect(SelectInterface $select): self
    {
        $this->select = $select;

        return $this;
    }

    /**
     * Add a set of fields with values to the select request.
     * Values automatically create bindings.
     *
     * @param array $fieldValues  Expect array['fieldName'] = 'value to insert'
     *
     * @return self
     */
    public function fieldValues(array $fieldValues): self
    {
        foreach ( $fieldValues as $fieldName => $value )
        {
            $bindLabel = $this->getBindLabel();
            $this->setBinding($bindLabel, $value);
            $this->fieldStack[] = $this->quoter()->quoteField($fieldName);
            $this->valueStack[] = $bindLabel;
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
     * Build the INSERT statement
     *
     * @return string
     */
    protected function buildSQL()
    {
        $sql = 'INSERT'.PHP_EOL;

        if ( empty($this->tableInto) )
        {
            return $sql;
        }

        $sql .= 'INTO'.PHP_EOL;
        $sql .= $this->indent();
        $sql .= $this->tableInto.PHP_EOL;

        // A set of fields isn't really required, even if it's a really good
        // idea to have them.  If nothings there, leave it empty.
        if ( !empty($this->fieldStack) )
        {
            $sql .= $this->indent().'(';

            $sql .= implode(', ', $this->fieldStack);

            $sql .= ')'.PHP_EOL;
        }

        // Only add values when something is on the stack and there isn't a
        // SELECT statement waiting to go in there instead.
        if ( !empty($this->valueStack) and $this->select === null )
        {
            $sql .= 'VALUES'.PHP_EOL;

            $sql .= $this->indent().'(';

            $sql .= implode(', ', $this->valueStack);

            $sql .= ')'.PHP_EOL;
        }

        // Check for a SELECT statement and append if available
        if ( is_object($this->select) )
        {
            $sql .= $this->select->output();
        }

        // Using the simplest form of RETURNING
        if ( $this->returningField !== null )
        {
            $sql .= 'RETURNING '.$this->returningField.PHP_EOL;
        }

        return $sql;
    }
}