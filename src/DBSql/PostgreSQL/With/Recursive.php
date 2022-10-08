<?php
/**
 * @author        "Michael Collette" <metrol@metrol.net>
 * @package       Metrol/DBSql
 * @version       1.0
 * @copyright (c) 2016, Michael Collette
 */

namespace Metrol\DBSql\PostgreSQL\With;

use Metrol\DBSql\PostgreSQL\{QuoterTrait, Union};
use Metrol\DBSql\{BindingsTrait, IndentTrait};

/**
 * All the parts of a Recursive clause for a With statement
 *
 */
class Recursive
{
    use BindingsTrait, IndentTrait, QuoterTrait;

    /**
     * The SQL Union Statement to be included in the Recursive clause
     *
     */
    protected Union $union;

    /**
     * The alias this clause will be referred to as
     *
     */
    protected string $alias = '';

    /**
     * List of fields to be specified
     *
     */
    protected array $fields = [];

    /**
     * Instantiate and initialize the object
     *
     */
    public function __construct()
    {
        $this->initBindings();
        $this->initIndent();
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
     * Sets the union for the recursive clause
     *
     */
    public function setUnion(string $alias, Union $union): static
    {
        $this->union = $union;
        $this->alias = $alias;

        return $this;
    }

    /**
     * Set the fields that will be referenced back in the recursive clause
     *
     */
    public function setFields(array $fields): static
    {
        $this->fields = $fields;

        return $this;
    }

    /**
     * Used to determine if this object has been populated.  Must have a
     * union statement and alias to return true.
     *
     */
    public function isReady(): bool
    {
        $rtn = false;

        if ( isset($this->union) and ! empty($this->alias) )
        {
            $rtn = true;
        }

        return $rtn;
    }

    /**
     * Build out the SQL and gather all the bindings to be ready to push to PDO
     *
     */
    protected function buildSQL(): string
    {
        if ( !$this->isReady() )
        {
            return '';
        }

        $sql = ' RECURSIVE ';
        $sql .= $this->quoter()->quoteField($this->alias);

        if ( empty($this->fields) )
        {
            $sql .= ' ';
        }
        else
        {
            $fields = array();

            foreach ( $this->fields as $field )
            {
                $fields[] = $this->quoter()->quoteField($field);
            }

            $sql .= '(';
            $sql .= implode(', ', $fields);
            $sql .= ') ';
        }

        $sql .= 'AS '.PHP_EOL;
        $sql .= '('.PHP_EOL;
        $sql .= $this->indentStatement($this->union, 1);
        $sql .= '),';

        return $sql;
    }
}
