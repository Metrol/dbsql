<?php
/**
 * @author        "Michael Collette" <metrol@metrol.net>
 * @package       Metrol/DBSql
 * @version       1.0
 * @copyright (c) 2016, Michael Collette
 */

namespace Metrol\DBSql;

/**
 * Define what every Update class needs to support
 *
 */
interface UpdateInterface extends StatementInterface
{
    /**
     * Which database table is being updated
     *
     * @param string $tableName
     *
     * @return self
     */
    public function table(string $tableName);

    /**
     * Adds a field = value pair to be updated.  Values automatically have
     * bindings created for them.
     *
     * @param string $fieldName
     * @param mixed  $value
     *
     * @return self
     */
    public function fieldValue(string $fieldName, $value);

    /**
     * Adds a field = value pair to be updated.  Fields will be quoted, but
     * values are passed along as is, without binding or escaping.
     *
     * @param string $fieldName
     * @param mixed  $value
     *
     * @return self
     */
    public function fieldValueNoBind(string $fieldName, $value);

    /**
     * Add a set of fields with values to be assigned values to the UPDATE
     * Values automatically create bindings.
     *
     * @param array $fieldValues
     *
     * @return self
     */
    public function fieldValues(array $fieldValues);

    /**
     * Add a WHERE clause to the stack of criteria in the UPDATE statement.
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
}
