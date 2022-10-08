<?php
/**
 * @author        "Michael Collette" <metrol@metrol.net>
 * @package       Metrol/DBSql
 * @version       1.0
 * @copyright (c) 2016, Michael Collette
 */

namespace Metrol\DBSql;

/**
 * For objects using query stacks, implementing this interface will make those
 * methods available.
 *
 */
interface StackInterface
{
    /**
     * Empty the fields from the SELECT statement
     *
     */
    public function fieldReset(): static;

    /**
     * Empty the values from the Value stack
     *
     */
    public function valueReset(): static;

    /**
     * Empty the tables stack
     *
     */
    public function fromReset(): static;


    /**
     * Empty the joins stack
     *
     */
    public function joinReset(): static;

    /**
     * Empty the where clauses
     *
     */
    public function whereReset(): static;

    /**
     * Empty the having clauses
     *
     */
    public function havingReset(): static;

    /**
     * Empty the ordering
     *
     */
    public function orderReset(): static;

    /**
     * Empty the grouping fields
     *
     */
    public function groupReset(): static;
}
