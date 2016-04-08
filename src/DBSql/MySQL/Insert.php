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
        $this->select         = null;
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
     * Produces the output of all the information that was set in the object.
     *
     * @return string Formatted SQL
     */
    public function output()
    {
        return $this->buildSQL();
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
        $this->tableInto = $this->quoter()->quoteTable($tableName);

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
     * @return $this
     */
    public function fieldValue($fieldName, $value, $boundValue = null)
    {
        $this->fieldStack[] = $this->quoter()->quoteField($fieldName);

        if ( $boundValue !== null and (   $value === '?'
                                       or $value === ''
                                       or $value === null)
        )
        {
            $label = $this->getBindLabel();
            $this->setBinding($label, $boundValue);
            $this->valueStack[] = $label;
        }
        else if ( substr($value, 0, 1) === ':' // Starts with a colon
            and $boundValue !== null           // Has a bound value
            and strpos($value, ' ') === false  // No spaces in the named binding
        )
        {
            $this->setBinding($value, $boundValue);
            $this->valueStack[] = $value;
        }
        else
        {
            $this->valueStack[] = $value;
        }

        return $this;
    }

    /**
     * Add a set of the field names to show up in the INSERT statement.
     * - No value binding provided.
     *
     * @param string[] $fields
     *
     * @return $this
     */
    public function fields(array $fields)
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
     * @return $this
     */
    public function values(array $values)
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
     * @return $this
     */
    public function valueSelect(SelectInterface $select)
    {
        $this->select = $select;

        $this->mergeBindings($select);

        return $this;
    }

    /**
     * Add a set of fields with values to the select request.
     * Values automatically create bindings.
     *
     * @param array $fieldValues  Expect array['fieldName'] = 'value to insert'
     *
     * @return $this
     */
    public function fieldValues(array $fieldValues)
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
        $sql .= $this->buildValuesFromSelect();

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
        if ( empty($this->fieldStack) )
        {
            return $sql;
        }

        $sql .= $this->indent().'(';
        $sql .= implode(', ', $this->fieldStack);
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
        if ( empty($this->valueStack) or $this->select !== null )
        {
            return $sql;
        }

        $sql .= 'VALUES'.PHP_EOL;
        $sql .= $this->indent().'(';
        $sql .= implode(', ', $this->valueStack);
        $sql .= ')'.PHP_EOL;

        return $sql;
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
        }

        return $sql;
    }
}
