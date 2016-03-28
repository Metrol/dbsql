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
     * Add a field and an optionally bound value to the stack.
     *
     * To automatically bind a value, the 3rd argument must be provided a value
     * and the 2nd argument needs to be...
     * - Question mark '?'
     * - Empty string ''
     * - null
     *
     * A named binding can be accepted when the 3rd argument has a value and
     * the 2nd argument is a string that starts with a colon that contains no
     * empty spaces.
     *
     * A non-bound value is not quoted or escaped in any way.  Use with all
     * due caution.
     *
     * @param string $fieldName
     * @param mixed  $value
     * @param mixed  $boundValue
     *
     * @return self
     */
    public function fieldValue(string $fieldName, $value, $boundValue = null);

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
