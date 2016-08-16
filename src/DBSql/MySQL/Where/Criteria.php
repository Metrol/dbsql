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
 * A manually created criteria for a WHERE clause
 *
 */
class Criteria implements WhereInterface
{
    use BindingsTrait, QuoterTrait;

    /**
     * The criteria string for this clause
     *
     * @var string
     */
    private $criteria;

    /**
     * Instantiate the Criteria object
     *
     * @param string      $criteria
     * @param mixed|array $bindValues
     */
    public function __construct($criteria, $bindValues = null)
    {
        $this->initBindings();

        if ( !is_array($bindValues) )
        {
            $bindValues = [$bindValues];
        }

        $this->criteria = $this->bindAssign($criteria, $bindValues);
        $this->criteria = $this->quoter()->quoteField($this->criteria);
    }

    /**
     * Output the criteria clause
     *
     * @return string
     */
    public function output()
    {
        return $this->criteria;
    }
}
