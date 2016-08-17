<?php
/**
 * @author        Michael Collette <metrol@metrol.net>
 * @version       1.0
 * @package       DBSql
 * @copyright (c) 2016, Michael Collette
 */

namespace Metrol\DBSql\PostgreSQL\From;

use Metrol\DBSql\BindingsTrait;
use Metrol\DBSql\FromInterface;
use Metrol\DBSql\PostgreSQL\QuoterTrait;

/**
 * Used for FROM clauses that need to specify a set of values as the data
 * source.
 *
 */
class Values implements FromInterface
{
    use BindingsTrait, QuoterTrait;

    /**
     * Output string
     *
     * @var string
     */
    private $outString;

    /**
     * The alias for the From clause
     *
     * @var string
     */
    private $alias;

    /**
     * Instantiate the Values object
     *
     * Adds values or sets of values to the FROM clause with optional field
     * names.  Values can be automatically bound or left alone based on the
     * binding flag.
     *
     * @param array   $values   Can be a list of values, or a list of arrays of
     *                          values to form sets.  Sets should all have the same
     *                          number of elements with consistent types.
     * @param string  $alias    An alias is required.  You can add field names for
     *                          sets of data here.
     * @param boolean $bindFlag When set to true, all the values are automatically
     *                          given bindings.  Otherwise, they are left alone.
     *
     */
    public function __construct(array $values, $alias, $bindFlag = true)
    {
        $this->initBindings();

        $this->alias = $alias;

        $this->buildOutput($values, $bindFlag);
    }

    /**
     * Produce the output for this From clause
     *
     * @return string
     */
    public function output()
    {
        return $this->outString;
    }

    /**
     * Build the output string
     *
     * @param array   $values
     * @param boolean $bindFlag
     */
    protected function buildOutput(array $values, $bindFlag = true)
    {
        if ( empty($values) )
        {
            return;
        }

        // Bind values as needed
        foreach ( $values as $vIdx => $value )
        {
            if ( is_array($value) )
            {
                $newSet = array();

                foreach ( $value as $setIdx => $setItem )
                {
                    if ( $bindFlag )
                    {
                        $label = $this->getBindLabel();
                        $this->setBinding($label, $setItem);
                        $newSet[ $setIdx ] = $label;
                    }
                    else
                    {
                        $newSet[ $setIdx ] = $setItem;
                    }
                }

                $values[$vIdx] = $newSet;
            }
            else
            {
                if ( $bindFlag === true )
                {
                    $label = $this->getBindLabel();
                    $this->setBinding($label, $value);
                    $values[$vIdx] = $label;
                }
            }
        }

        // Assemble the string that can go into the FROM clause
        $from = '( VALUES';

        reset($values);

        if ( is_array( current($values) ) )
        {
            $sets = array();
            reset($values);

            foreach ( $values as $setItems )
            {
                $sets[] = '('. implode('), (', $setItems). ')';
            }

            $from .= ' ('.implode('), (', $sets). ')';
        }
        else
        {
            $from .= ' ('. implode('), (', $values) .')';
        }

        $from .= ' ) AS '.$this->alias;

        $this->outString = $from;
    }
}
