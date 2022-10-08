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
trait StackTrait
{
    /**
     * A set of Field Value objects
     *
     */
    protected Field\Set $fieldValueSet;

    /**
     * List of the fields to be used in the SQL
     *
     */
    protected array $fieldStack = [];

    /**
     * List of values to fill into the VALUES area of an INSERT
     *
     */
    protected array $valueStack = [];

    /**
     * Tables and other data sources
     *
     */
    protected array $fromStack = [];

    /**
     * Join statements
     *
     */
    protected array $joinStack = [];

    /**
     * Clauses that will make up the WHERE section of a statement
     *
     */
    protected array $whereStack = [];

    /**
     * Clauses that will make up the HAVING section of a statement
     *
     */
    protected array $havingStack = [];

    /**
     * List of fields that are used to sort the result set
     *
     */
    protected array $orderStack = [];

    /**
     * List of fields that are used to group the result set
     *
     */
    protected array $groupStack = [];

    /**
     * Initialize all the SQL stacks to their default state.
     *
     */
    protected function initStacks(): void
    {
        $this->fieldValueSet = new Field\Set;
        $this->fieldStack  = [];
        $this->valueStack  = [];
        $this->fromStack   = [];
        $this->joinStack   = [];
        $this->whereStack  = [];
        $this->havingStack = [];
        $this->orderStack  = [];
        $this->groupStack  = [];
    }

    /**
     * Add a new Field Value to the set
     *
     */
    public function addFieldValue(Field\Value $fieldValue): static
    {
        $this->fieldValueSet->addFieldValue($fieldValue);

        return $this;
    }

    /**
     * Empty the fields from the SELECT statement
     *
     */
    public function fieldReset(): static
    {
        $this->fieldStack = [];

        return $this;
    }

    /**
     * Empty the values from the Value stack
     *
     */
    public function valueReset(): static
    {
        $this->valueStack = [];

        return $this;
    }

    /**
     * Empty the tables stack
     *
     */
    public function fromReset(): static
    {
        $this->fromStack = [];

        return $this;
    }

    /**
     * Empty the joins stack
     *
     */
    public function joinReset(): static
    {
        $this->joinStack = [];

        return $this;
    }

    /**
     * Push a value on to the WHERE stack
     *
     */
    protected function wherePush(WhereInterface $whereClause): static
    {
        $this->whereStack[] = $whereClause;

        return $this;
    }

    /**
     * Empty the where clauses
     *
     */
    public function whereReset(): static
    {
        $this->whereStack = [];

        return $this;
    }

    /**
     * Empty the having clauses
     *
     */
    public function havingReset(): static
    {
        $this->havingStack = [];

        return $this;
    }

    /**
     * Empty the ordering
     *
     */
    public function orderReset(): static
    {
        $this->orderStack = [];

        return $this;
    }

    /**
     * Empty the grouping fields
     *
     */
    public function groupReset(): static
    {
        $this->groupStack = [];

        return $this;
    }
}
