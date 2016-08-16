<?php
/**
 * @author        Michael Collette <mcollette@meetingevolution.net>
 * @version       1.0
 * @package       DBSql
 * @copyright (c) 2016, Michael Collette
 */

namespace Metrol\DBSql\MySQL\Where;

use Metrol\DBSql\WhereInterface;
use Metrol\DBSql\BindingsTrait;
use Metrol\DBSql\MySQL\QuoterTrait;

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
     * @var string
     */
    private $outString;

    /**
     * Instantiate the Values object
     *
     * @param string  $field
     * @param array   $values
     * @param boolean $valueIn
     */
    public function __construct($field, array $values, $valueIn = true)
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
     * @return string
     */
    public function output()
    {
        return $this->outString;
    }
}
