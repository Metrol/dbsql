<?php
/**
 * @author        Michael Collette <mcollette@meetingevolution.net>
 * @version       1.0
 * @package       DBSql
 * @copyright (c) 2016, Michael Collette
 */

namespace Metrol\DBSql\PostgreSQL\Where;

use Metrol\DBSql\{WhereInterface, BindingsTrait};
use Metrol\DBSql\PostgreSQL\QuoterTrait;

/**
 * A where clause that looks for a field to be in a list of values
 *
 */
class Values implements WhereInterface
{
    use BindingsTrait, QuoterTrait;

    /**
     * Output string
     *
     */
    private string $outString;

    /**
     * Instantiate the Values object
     *
     */
    public function __construct(string $field, array $values, bool $valueIn = true)
    {
        $fieldString = $this->quoter()->quoteField($field);

        $placeHolders = '(';
        $placeHolders .= implode(', ', array_fill(0, count($values), '?') );
        $placeHolders .= ')';

        $this->outString = $fieldString;

        if ( $valueIn )
        {
            $this->outString .= ' IN ';
        }
        else
        {
            $this->outString .= ' NOT IN ';
        }

        $this->outString .= $this->bindAssign($placeHolders, $values);
    }

    /**
     * Produce the WHERE clause
     *
     */
    public function output(): string
    {
        return $this->outString;
    }
}
