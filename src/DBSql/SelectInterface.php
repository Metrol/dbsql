<?php
/**
 * @author        "Michael Collette" <metrol@metrol.net>
 * @package       Metrol/DBSql
 * @version       1.0
 * @copyright (c) 2016, Michael Collette
 */

namespace Metrol\DBSql;

/**
 * Define what every Select class needs to support
 *
 */
interface SelectInterface extends StatementInterface
{
    /**
     * Add a column/field to what is being requested
     *
     * @param string $fieldName  Must be quoted correctly for the database
     *
     * @return $this
     */
    public function field($fieldName);

    /**
     * Sets the fields going to the select request.
     * Replaces any fields already set.
     *
     * @param array $fieldNames
     *
     * @return $this
     */
    public function fields(array $fieldNames);

    /**
     * Adds a CASE/WHEN/THEN structure to the field stack.
     * When chaining calls, you must call the Case->end() method to get this
     * object back.
     *
     * @return CaseFieldInterface
     */
    public function caseField();

    /**
     * Add a data source to the FROM clause of the query.
     *
     * @param string $fromName
     *
     * @return $this
     */
    public function from($fromName);

    /**
     * Add a sub select as a data source in the FROM clause of the query.
     * Any bindings from the sub select will be merged with the parent SELECT
     * statement.  Conflicts will defer to the parent value.
     *
     * @param string          $alias
     * @param SelectInterface $subSelect
     *
     * @return $this
     */
    public function fromSub($alias, SelectInterface $subSelect);

    /**
     * Adds an INNER JOIN clause to the SELECT statement.
     *
     * @param string $tableName
     * @param string $onCriteria ON criteria for the JOIN.
     * @param array  $bindValues List of values to bind into the criteria
     *
     * @return $this
     */
    public function join($tableName, $onCriteria, array $bindValues = null);

    /**
     * Adds an INNER JOIN clause to the SELECT statement with USING as the the join
     * criteria.  No data binding is provided here.
     *
     * @param string $tableName
     * @param string $criteria   Field names for the USING clause
     *
     * @return $this
     */
    public function joinUsing($tableName, $criteria);

    /**
     * Add a WHERE clause to the stack of criteria in the SELECT statement.
     * Each new clause called will be included with an "AND" in between.
     *
     * @param string $criteria
     * @param array  $bindValues
     *
     * @return $this
     */
    public function where($criteria, array $bindValues = null);

    /**
     * Sets up a WHERE entry to see if a field has a value in the array provided
     *
     * @param string $fieldName
     * @param array  $values
     *
     * @return $this
     */
    public function whereIn($fieldName, array $values);

    /**
     * Sets up a WHERE entry to see if a field does not have value in the array
     * provided.
     *
     * @param string $fieldName
     * @param array  $values
     *
     * @return $this
     */
    public function whereNotIn($fieldName, array $values);

    /**
     * Sets up a WHERE field is in the results of a sub query.  Bindings from
     * the specified sub query are merged as able.  This object (the parent
     * query) has the final say on a binding value when there is a conflict.
     *
     * @param string          $fieldName
     * @param SelectInterface $subSelect
     *
     * @return $this
     */
    public function whereInSub($fieldName, SelectInterface $subSelect);

    /**
     * Sets up a WHERE field is not in the results of a sub query.  Bindings from
     * the specified sub query are merged as able.  This object (the parent
     * query) has the final say on a binding value when there is a conflict.
     *
     * @param string          $fieldName
     * @param SelectInterface $subSelect
     *
     * @return $this
     */
    public function whereNotInSub($fieldName, SelectInterface $subSelect);

    /**
     * Add fields to order the result set by
     *
     * @param string $fieldName
     * @param string $direction ASC by default
     * @param string $nullOrder 'NULLS FIRST' | 'NULLS LAST' Defaults to LAST
     *
     * @return $this
     */
    public function order($fieldName, $direction = null, $nullOrder = null);

    /**
     * Add a field to the GROUP BY clause.
     *
     * @param string $fieldName
     *
     * @return $this
     */
    public function groupBy($fieldName);

    /**
     * Add a set of fields to the GROUP BY clause
     *
     * @param string[] $fieldNames
     *
     * @return $this
     */
    public function groupByFields(array $fieldNames);

    /**
     * Add a HAVING clause to the stack of criteria in the SELECT statement.
     * Each new clause called will be included with an "AND" in between.
     *
     * @param string $criteria Criteria for an aggregate
     * @param array  $bindValues
     *
     * @return $this
     */
    public function having($criteria, array $bindValues = null);

    /**
     * Sets the limit for how many rows to pull back from the query.
     *
     * @param integer $rowCount
     *
     * @return $this
     */
    public function limit($rowCount);

    /**
     * Sets the offset for which row to start with on the result set from the
     * query.
     *
     * @param integer $startRow
     *
     * @return $this
     */
    public function offset($startRow);
}
