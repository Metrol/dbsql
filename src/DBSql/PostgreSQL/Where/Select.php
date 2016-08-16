<?php
/**
 * @author        Michael Collette <mcollette@meetingevolution.net>
 * @version       1.0
 * @package       DBSql
 * @copyright (c) 2016, Michael Collette
 */

namespace Metrol\DBSql\PostgreSQL\Where;

use Metrol\DBSql\WhereInterface;
use Metrol\DBSql\IndentTrait;
use Metrol\DBSql\PostgreSQL\QuoterTrait;
use Metrol\DBSql\SelectInterface;

/**
 * A where clause that looks for a field to have a value in the results of a
 * select statement.
 *
 */
class Select implements WhereInterface
{
    use QuoterTrait, IndentTrait;

    /**
     * A sub select to look for a field value IN or NOT IN
     *
     * @var SelectInterface
     */
    private $subSelect;

    /**
     * When using a value list or sub select, this is the flag to determine if
     * the value is IN or NOT IN the set.
     *
     * @var boolean
     */
    private $valueIn;

    /**
     * Field to test in a check if IN or NOT IN a set
     *
     * @var string
     */
    private $field;

    /**
     * Instantiate the Select object
     *
     * @param string          $field
     * @param SelectInterface $subSelect
     * @param boolean         $valueIn
     */
    public function __construct($field, SelectInterface $subSelect,
                                $valueIn = true)
    {
        $this->initIndent();

        $this->field     = $this->quoter()->quoteField($field);
        $this->subSelect = $subSelect;

        if ( $valueIn )
        {
            $this->valueIn = true;
        }
        else
        {
            $this->valueIn = false;
        }
    }

    /**
     * Produce the where clause in question
     *
     * @return string
     */
    public function output()
    {
        $whereClause = $this->field;

        if ( $this->valueIn )
        {
            $whereClause .= ' IN ' . PHP_EOL;
        }
        else
        {
            $whereClause .= ' NOT IN ' . PHP_EOL;
        }

        $whereClause .= $this->indent() . '(' . PHP_EOL;
        $whereClause .= $this->indentStatement($this->subSelect, 2);
        $whereClause .= $this->indent() . ')';

        return $whereClause;
    }

    /**
     * Provide the bindings as they are in the sub select
     *
     * @retrun array
     */
    public function getBindings()
    {
        return $this->subSelect->getBindings();
    }
}
