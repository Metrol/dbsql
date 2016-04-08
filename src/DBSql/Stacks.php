<?php
/**
 * @author        "Michael Collette" <metrol@metrol.net>
 * @package       Metrol/DBSql
 * @version       1.0
 * @copyright (c) 2016, Michael Collette
 */

namespace Metrol\DBSql;

/**
 * Most statements require a set of arrays to keep track of what is
 * being added in.  This trait provides those arrays along with some helper
 * methods.
 *
 */
trait Stacks
{
    /**
     * List of the fields to be used in the SQL
     *
     * @var array
     */
    protected $fieldStack;

    /**
     * List of values to fill into the VALUES area of an INSERT
     *
     * @var array
     */
    protected $valueStack;

    /**
     * Tables and other data sources
     *
     * @var array
     */
    protected $fromStack;

    /**
     * Join statements
     *
     * @var array
     */
    protected $joinStack;

    /**
     * Clauses that will make up the WHERE section of a statement
     *
     * @var array
     */
    protected $whereStack;

    /**
     * Clauses that will make up the HAVING section of a statement
     *
     * @var array
     */
    protected $havingStack;

    /**
     * List of fields that are used to sort the result set
     *
     * @var array
     */
    protected $orderStack;

    /**
     * List of fields that are used to group the result set
     *
     * @var array
     */
    protected $groupStack;

    /**
     * Initialize all the SQL stacks to their default state.
     *
     */
    protected function initStacks()
    {
        $this->fieldStack  = array();
        $this->valueStack  = array();
        $this->fromStack   = array();
        $this->joinStack   = array();
        $this->whereStack  = array();
        $this->havingStack = array();
        $this->orderStack  = array();
        $this->groupStack  = array();
    }

    /**
     * Empty the fields from the SELECT statement
     *
     * @return $this
     */
    public function fieldReset()
    {
        $this->fieldStack = array();

        return $this;
    }

    /**
     * Empty the values from the Value stack
     *
     * @return $this
     */
    public function valueReset()
    {
        $this->valueStack = array();

        return $this;
    }

    /**
     * Empty the tables stack
     *
     * @return $this
     */
    public function fromReset()
    {
        $this->fromStack = array();

        return $this;
    }

    /**
     * Empty the joins stack
     *
     * @return $this
     */
    public function joinReset()
    {
        $this->joinStack = array();

        return $this;
    }

    /**
     * Push a value on to the WHERE stack
     *
     * @param string $whereClause
     *
     * @return $this
     */
    protected function wherePush($whereClause)
    {
        $this->whereStack[] = $whereClause;

        return $this;
    }

    /**
     * Empty the where clauses
     *
     * @return $this
     */
    public function whereReset()
    {
        $this->whereStack = array();

        return $this;
    }

    /**
     * Empty the having clauses
     *
     * @return $this
     */
    public function havingReset()
    {
        $this->havingStack = array();

        return $this;
    }

    /**
     * Empty the ordering
     *
     * @return $this
     */
    public function orderReset()
    {
        $this->orderStack = array();

        return $this;
    }

    /**
     * Empty the grouping fields
     *
     * @return $this
     */
    public function groupReset()
    {
        $this->groupStack = array();

        return $this;
    }
}
