<?php
/**
 * @author        "Michael Collette" <metrol@metrol.net>
 * @package       Metrol/DBSql
 * @version       1.0
 * @copyright (c) 2016, Michael Collette
 */

namespace Metrol\DBSql\PostgreSQL\With;

use Metrol\DBSql\PostgreSQL\QuoterTrait;
use Metrol\DBSql\PostgreSQL\Union;
use Metrol\DBSql\BindingsTrait;
use Metrol\DBSql\IndentTrait;

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
     * @var Union
     */
    protected $union;

    /**
     * The alias this clause will be referred to as
     *
     * @var string
     */
    protected $alias;

    /**
     * List of fields to be specified
     *
     * @var array
     */
    protected $fields;

    /**
     * Instantiate and initialize the object
     *
     */
    public function __construct()
    {
        $this->initBindings();
        $this->initIndent();

        $this->union = null;
        $this->alias     = '';
        $this->fields    = array();
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
     * Sets the union for the recursive clause
     *
     * @param string $alias
     * @param Union  $union
     *
     * @return $this
     */
    public function setUnion($alias, Union $union)
    {
        $this->union = $union;
        $this->alias = $alias;

        return $this;
    }

    /**
     * Set the fields that will be referenced back in the recursive clause
     *
     * @param array $fields
     *
     * @return $this
     */
    public function setFields(array $fields)
    {
        $this->fields = $fields;

        return $this;
    }

    /**
     * Used to determine if this object has been populated.  Must have a
     * union statement and alias to return true.
     *
     * @return boolean
     */
    public function isReady()
    {
        $rtn = false;

        if ( $this->union !== null and !empty($this->alias) )
        {
            $rtn = true;
        }

        return $rtn;
    }

    /**
     * Build out the SQL and gather all the bindings to be ready to push to PDO
     *
     * @return string
     */
    protected function buildSQL()
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
