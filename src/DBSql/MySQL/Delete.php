<?php
/**
 * @author        "Michael Collette" <metrol@metrol.net>
 * @package       Metrol/DBSql
 * @version       1.0
 * @copyright (c) 2016, Michael Collette
 */

namespace Metrol\DBSql\MySQL;

use Metrol\DBSql\DeleteInterface;
use Metrol\DBSql\Bindings;
use Metrol\DBSql\Indent;
use Metrol\DBSql\Stacks;

/**
 * Creates an Delete SQL statement for MySQL
 *
 */
class Delete implements DeleteInterface
{
    use Bindings, Indent, Stacks, Quoter, Where;

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
     * Set the table that is targeted to delete data from
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
     * Request back information on the rows that were deleted
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
     * Build the DELETE statement
     *
     * @return string
     */
    protected function buildSQL()
    {
        $sql = 'DELETE'.PHP_EOL;

        if ( empty($this->table) )
        {
            return $sql;
        }

        $sql .= 'FROM'.PHP_EOL;
        $sql .= $this->indent().$this->table.PHP_EOL;

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
