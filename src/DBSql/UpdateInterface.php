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
     */
    public function table(string $tableName): static;

    /**
     * Adds a field value to the stack to update
     *
     */
    public function addFieldValue(Field\Value $fieldValue): static;

    /**
     * Add a WHERE clause to the stack of criteria in the UPDATE statement.
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
     * Sets up a WHERE field is in the results of a sub query.  BindingsTrait from
     * the specified sub query are merged as able.  This object (the parent
     * query) has the final say on a binding value when there is a conflict.
     *
     */
    public function whereInSub(string $fieldName, SelectInterface $subSelect): static;
}
