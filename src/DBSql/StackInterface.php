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
     * @return $this
     */
    public function fieldReset();

    /**
     * Empty the values from the Value stack
     *
     * @return $this
     */
    public function valueReset();

    /**
     * Empty the tables stack
     *
     * @return $this
     */
    public function fromReset();


    /**
     * Empty the joins stack
     *
     * @return $this
     */
    public function joinReset();

    /**
     * Empty the where clauses
     *
     * @return $this
     */
    public function whereReset();

    /**
     * Empty the having clauses
     *
     * @return $this
     */
    public function havingReset();

    /**
     * Empty the ordering
     *
     * @return $this
     */
    public function orderReset();

    /**
     * Empty the grouping fields
     *
     * @return $this
     */
    public function groupReset();
}
