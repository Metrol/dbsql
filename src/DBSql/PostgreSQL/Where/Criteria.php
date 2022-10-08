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
 * A manually created criteria for a WHERE clause
 *
 */
class Criteria implements WhereInterface
{
    use BindingsTrait, QuoterTrait;

    /**
     * The criteria string for this clause
     *
     */
    private string $criteria;

    /**
     * Instantiate the Criteria object
     *
     */
    public function __construct(string $criteria, mixed $bindValues = null)
    {
        $this->initBindings();

        if ( ! is_array($bindValues) )
        {
            $bindValues = [$bindValues];
        }

        $this->criteria = $this->bindAssign($criteria, $bindValues);
        $this->criteria = $this->quoter()->quoteField($this->criteria);
    }

    /**
     * Output the criteria clause
     *
     */
    public function output(): string
    {
        return $this->criteria;
    }
}
