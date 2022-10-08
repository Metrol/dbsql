<?php
/**
 * @author        Michael Collette <mcollette@meetingevolution.net>
 * @version       1.0
 * @package       DBSql
 * @copyright (c) 2016, Michael Collette
 */

namespace Metrol\DBSql\MySQL\Where;

use Metrol\DBSql\{SelectInterface, WhereInterface, IndentTrait};
use Metrol\DBSql\MySQL\QuoterTrait;

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
     */
    private SelectInterface $subSelect;

    /**
     * When using a value list or sub select, this is the flag to determine if
     * the value is IN or NOT IN the set.
     *
     */
    private bool $valueIn;

    /**
     * Field to test in a check if IN or NOT IN a set
     *
     */
    private string $field;

    /**
     * Instantiate the Select object
     *
     */
    public function __construct(string          $field,
                                SelectInterface $subSelect,
                                bool            $valueIn = true)
    {
        $this->initIndent();

        $this->field     = $this->quoter()->quoteField($field);
        $this->subSelect = $subSelect;
        $this->valueIn   = $valueIn;
    }

    /**
     * Produce the where clause in question
     *
     */
    public function output(): string
    {
        $whereClause = $this->field;

        if ( $this->valueIn )
        {
            $whereClause .= ' IN' . PHP_EOL;
        }
        else
        {
            $whereClause .= ' NOT IN' . PHP_EOL;
        }

        $whereClause .= $this->indent() . '(' . PHP_EOL;
        $whereClause .= $this->indentStatement($this->subSelect, 2);
        $whereClause .= $this->indent() . ')';

        return $whereClause;
    }

    /**
     * Provide the bindings as they are in the sub select
     *
     */
    public function getBindings(): array
    {
        return $this->subSelect->getBindings();
    }
}
