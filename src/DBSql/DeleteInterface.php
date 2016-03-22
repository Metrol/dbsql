<?php
/**
 * @author        "Michael Collette" <metrol@metrol.net>
 * @package       Metrol/DBSql
 * @version       1.0
 * @copyright (c) 2016, Michael Collette
 */

namespace Metrol\DBSql;

/**
 * Define what every Delete class needs to support
 *
 */
interface DeleteInterface extends StatementInterface
{
    /**
     * Which database table is having rows deleted from it
     *
     * @param string $tableName
     *
     * @return self
     */
    public function table(string $tableName);

    /**
     * Add a WHERE clause to the stack of criteria in the DELETE statement.
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
