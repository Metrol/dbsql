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
     * @return self
     */
    public function field(string $fieldName);

    /**
     * Add a set of fields to the select request.
     *
     * @param array $fieldNames
     *
     * @return self
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
     * @return self
     */
    public function from(string $fromName);

    /**
     * Add a sub select as a data source in the FROM clause of the query.
     * Any bindings from the sub select will be merged with the parent SELECT
     * statement.  Conflicts will defer to the parent value.
     *
     * @param string          $alias
     * @param SelectInterface $subSelect
     *
     * @return self
     */
    public function fromSub(string $alias, SelectInterface $subSelect);

    /**
     * Adds an INNER JOIN clause to the SELECT statement.
     *
     * @param string $tableName
     * @param string $onCriteria ON criteria for the JOIN.
     * @param array  $bindValues List of values to bind into the criteria
     *
     * @return self
     */
    public function join(string $tableName, string $onCriteria,
                         array $bindValues = null);

    /**
     * Adds an INNER JOIN clause to the SELECT statement with USING as the the join
     * criteria.  No data binding is provided here.
     *
     * @param string $tableName
     * @param string $criteria   Field names for the USING clause
     *
     * @return self
     */
    public function joinUsing(string $tableName, string $criteria);

    /**
     * Add a WHERE clause to the stack of criteria in the SELECT statement.
     * Each new clause called will be included with an "AND" in between.
     *
     * @param string $criteria
     * @param array  $bindValues
     *
     * @return self
     */
    public function where(string $criteria, array $bindValues = null);

    /**
     * Sets up a WHERE entry to see if a field has a value in the array provided
     *
     * @param string $fieldName
     * @param array  $values
     *
     * @return self
     */
    public function whereIn(string $fieldName, array $values);

    /**
     * Sets up a WHERE field is in the results of a sub query.  Bindings from
     * the specified sub query are merged as able.  This object (the parent
     * query) has the final say on a binding value when there is a conflict.
     *
     * @param string          $fieldName
     * @param SelectInterface $subSelect
     *
     * @return self
     */
    public function whereInSub(string $fieldName, SelectInterface $subSelect);

    /**
     * Add fields to order the result set by
     *
     * @param string $fieldName
     * @param string $direction
     * @param string $nullOrder 'NULLS FIRST' | 'NULLS LAST' Defaults to LAST
     *
     * @return self
     */
    public function order(string $fieldName, string $direction = null,
                          string $nullOrder = null);


    /**
     * Add a field to the GROUP BY clause.
     *
     * @param string $fieldName
     *
     * @return self
     */
    public function groupBy($fieldName);

    /**
     * Add a set of fields to the GROUP BY clause
     *
     * @param string[] $fieldNames
     *
     * @return self
     */
    public function groupByFields(array $fieldNames);

    /**
     * Add a HAVING clause to the stack of criteria in the SELECT statement.
     * Each new clause called will be included with an "AND" in between.
     *
     * @param string $criteria Criteria for an aggregate
     *
     * @return self
     */
    public function having(string $criteria);
}
