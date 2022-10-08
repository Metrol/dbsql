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
interface SelectInterface extends StatementInterface, StackInterface
{
    /**
     * Add a column/field to what is being requested
     *
     */
    public function field(string $fieldName): static;

    /**
     * Sets the fields going to the select request.
     * Replaces any fields already set.
     *
     */
    public function fields(array $fieldNames): static;

    /**
     * Adds a CASE/WHEN/THEN structure to the field stack.
     * When chaining calls, you must call the Case->end() method to get this
     * object back.
     *
     */
    public function caseField(): CaseFieldInterface;

    /**
     * Add a data source to the FROM clause of the query.
     *
     */
    public function from(string $fromName): static;

    /**
     * Add a sub select as a data source in the FROM clause of the query.
     * Any bindings from the sub select will be merged with the parent SELECT
     * statement.  Conflicts will defer to the parent value.
     *
     */
    public function fromSub(string $alias, SelectInterface $subSelect): static;

    /**
     * Adds an INNER JOIN clause to the SELECT statement.
     *
     */
    public function join(string $tableName, string $onCriteria, array $bindValues = null): static;

    /**
     * Adds an INNER JOIN clause to the SELECT statement with USING as the join
     * criteria.  No data binding is provided here.
     *
     */
    public function joinUsing(string $tableName, string $criteria): static;

    /**
     * Adds a LEFT/RIGHT/FULL OUTER JOIN clause to the SELECT statement.
     *
     */
    public function joinOuter(string $joinType, string $tableName, string $onCriteria,
                              array $bindValues = null): static;

    /**
     * Adds an OUTER JOIN clause to the SELECT statement with USING as the
     * join criteria.  No data binding is provided here.
     *
     */
    public function joinOuterUsing(string $joinType, string $tableName, string $criteria): static;

    /**
     * Add a WHERE clause to the stack of criteria in the SELECT statement.
     * Each new clause called will be included with an "AND" in between.
     *
     */
    public function where(string $criteria, mixed $bindValues = null): static;

    /**
     * Sets up a WHERE entry to see if a field has a value in the array provided
     *
     */
    public function whereIn(string $fieldName, array $values): static;

    /**
     * Sets up a WHERE entry to see if a field does not have value in the array
     * provided.
     *
     */
    public function whereNotIn(string $fieldName, array $values): static;

    /**
     * Sets up a WHERE field is in the results of a sub query.  BindingsTrait from
     * the specified sub query are merged as able.  This object (the parent
     * query) has the final say on a binding value when there is a conflict.
     *
     */
    public function whereInSub(string $fieldName, SelectInterface $subSelect): static;

    /**
     * Sets up a WHERE field is not in the results of a sub query.  BindingsTrait from
     * the specified sub query are merged as able.  This object (the parent
     * query) has the final say on a binding value when there is a conflict.
     *
     */
    public function whereNotInSub(string $fieldName, SelectInterface $subSelect): static;

    /**
     * Add fields to order the result set by
     *
     */
    public function order(string $fieldName, string $direction = null, string $nullOrder = null): static;

    /**
     * Add a field to the GROUP BY clause.
     *
     */
    public function groupBy(string $fieldName): static;

    /**
     * Add a set of fields to the GROUP BY clause
     *
     */
    public function groupByFields(array $fieldNames): static;

    /**
     * Add a HAVING clause to the stack of criteria in the SELECT statement.
     * Each new clause called will be included with an "AND" in between.
     *
     */
    public function having(string $criteria, array $bindValues = null): static;

    /**
     * Sets the limit for how many rows to pull back from the query.
     *
     */
    public function limit(int $rowCount): static;

    /**
     * Sets the offset for which row to start with on the result set from the
     * query.
     *
     */
    public function offset(int $startRow): static;
}
