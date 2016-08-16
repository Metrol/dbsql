<?php
/**
 * @author        "Michael Collette" <metrol@metrol.net>
 * @package       Metrol/DBSql
 * @version       1.0
 * @copyright (c) 2016, Michael Collette
 */

namespace Metrol\DBSql\MySQL;

use Metrol\DBSql\StatementInterface;
use Metrol\DBSql\SelectInterface;
use Metrol\DBSql\WhereInterface;

/**
 * Provides handling a WHERE clause for statements that can use it.  This
 * assumes that statement has a QuoterTrait and has include the StackTrait.
 *
 */
trait WhereTrait
{
    /**
     * Add a WHERE clause to the stack of criteria in the SELECT statement.
     * Each new clause called will be included with an "AND" in between.
     *
     * @param string      $criteria
     * @param mixed|array $bindValues
     *
     * @return $this
     */
    public function where($criteria, $bindValues = null)
    {
        if ( !is_array($bindValues) )
        {
            $bindValues = [$bindValues];
        }

        /**
         * @var StatementInterface $this
         */
        $whereClause = new Where($this);

        $whereClause->setCriteria($criteria, $bindValues);

        $this->wherePush($whereClause);

        return $this;
    }

    /**
     * Sets up a WHERE entry to see if a field has a value in the array provided
     *
     * @param string $fieldName
     * @param array  $values
     *
     * @return $this
     */
    public function whereIn($fieldName, array $values)
    {
        /**
         * @var StatementInterface $this
         */
        $whereClause = new Where($this);

        $whereClause->setInList($fieldName, $values, true);

        $this->wherePush($whereClause);

        return $this;
    }

    /**
     * Sets up a WHERE entry to see if a field does not have value in the array
     * provided.
     *
     * @param string $fieldName
     * @param array  $values
     *
     * @return $this
     */
    public function whereNotIn($fieldName, array $values)
    {
        /**
         * @var StatementInterface $this
         */
        $whereClause = new Where($this);

        $whereClause->setInList($fieldName, $values, false);

        $this->wherePush($whereClause);

        return $this;
    }

    /**
     * Sets up a WHERE field is in the results of a sub query.  BindingsTrait from
     * the specified sub query are merged as able.  This object (the parent
     * query) has the final say on a binding value when there is a conflict.
     *
     * @param string          $fieldName
     * @param SelectInterface $subSelect
     *
     * @return $this
     */
    public function whereInSub($fieldName, SelectInterface $subSelect)
    {
        /**
         * @var StatementInterface $this
         */
        $whereClause = new Where($this);

        $whereClause->setInSelect($fieldName, $subSelect, true);

        $this->wherePush($whereClause);

        return $this;
    }

    /**
     * Sets up a WHERE field is not in the results of a sub query.  BindingsTrait from
     * the specified sub query are merged as able.  This object (the parent
     * query) has the final say on a binding value when there is a conflict.
     *
     * @param string          $fieldName
     * @param SelectInterface $subSelect
     *
     * @return $this
     */
    public function whereNotInSub($fieldName, SelectInterface $subSelect)
    {
        /**
         * @var StatementInterface $this
         */
        $whereClause = new Where($this);

        $whereClause->setInSelect($fieldName, $subSelect, false);

        $this->wherePush($whereClause);

        return $this;
    }

    /*
     * Everything from this point is documenting the methods that will need
     * to be implemented by the class using this trait.
     */

    /**
     * Push a value on to the WHERE stack
     *
     * @param WhereInterface $whereClause
     *
     * @return $this
     */
    abstract protected function wherePush(WhereInterface $whereClause);
}
