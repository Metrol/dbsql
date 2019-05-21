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
     * @return $this
     */
    public function table($tableName);

    /**
     * Adds a field value to the stack to update
     *
     * @param Field\Value $fieldValue
     *
     * @return $this
     */
    public function addFieldValue(Field\Value $fieldValue);

    /**
     * Add a WHERE clause to the stack of criteria in the UPDATE statement.
     * Each new clause called will be included with an "AND" in between.
     *
     * @param string      $criteria
     * @param mixed|array $bindValues
     *
     * @return $this
     */
    public function where($criteria, $bindValues = null);

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
     * Sets up a WHERE field is in the results of a sub query.  BindingsTrait from
     * the specified sub query are merged as able.  This object (the parent
     * query) has the final say on a binding value when there is a conflict.
     *
     * @param string          $fieldName
     * @param SelectInterface $subSelect
     *
     * @return $this
     */
    public function whereInSub($fieldName, SelectInterface $subSelect);
}
