<?php
/**
 * @author        Michael Collette <metrol@metrol.net>
 * @version       1.0
 * @package       Metrol/DBSql
 * @copyright (c) 2016, Michael Collette
 */

namespace Metrol\DBSql\PostgreSQL;

use Metrol\DBSql\FromInterface;
use Metrol\DBSql\BindingsTrait;
use Metrol\DBSql\SelectInterface;

/**
 * Describe a FROM clause for use in a SELECT object.  Once a method for setting
 * the clause is called, this object becomes immutable.
 *
 */
class From implements FromInterface
{
    use BindingsTrait, QuoterTrait;

    /**
     * The actual where clause being used
     *
     * @var FromInterface
     */
    private $clause;

    /**
     * Instantiate the From object
     *
     */
    public function __construct()
    {
        $this->clause = null;

        $this->initBindings();
    }

    /**
     *
     * @return string
     */
    public function __toString()
    {
        return $this->output();
    }

    /**
     * Produce the clause to appear in the WHERE area of an SQL statement.
     *
     * @return string
     */
    public function output()
    {
        if ( $this->clause === null )
        {
            return '';
        }

        $clauseStr = $this->clause->output();
        $this->assignClauseBindings();

        return $clauseStr;
    }

    /**
     * Set the criteria based on a string
     *
     * @param string $table
     */
    public function setTable($table)
    {
        if ( $this->clause !== null )
        {
            return;
        }

        $this->clause = new From\Table($table);
    }

    /**
     * Set a criteria based on a field having/not having a value in the provided
     * data set.
     *
     * @param array   $values
     * @param string  $alias
     * @param boolean $bindFlag
     */
    public function setInList(array $values, $alias, $bindFlag = true)
    {
        if ( $this->clause !== null )
        {
            return;
        }

        $this->clause = new From\Values($values, $alias, $bindFlag);
    }

    /**
     * Set a criteria based on a field having/not having a value in the provided
     * Select result
     *
     * @param string          $fieldName
     * @param SelectInterface $subSelect
     * @param boolean         $valueIn
     */
    public function setInSelect($fieldName, SelectInterface $subSelect,
                                $valueIn = true)
    {
        if ( $this->clause !== null )
        {
            return;
        }

        $this->clause = new From\Select($fieldName, $subSelect, $valueIn);
    }

    /**
     * Look for any bindings the clause may have created and be sure to assign
     * them to the parent statement.
     *
     */
    private function assignClauseBindings()
    {
        foreach ( $this->clause->getBindings() as $key => $value )
        {
            $this->setBinding($key, $value);
        }
    }
}
