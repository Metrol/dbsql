<?php
/**
 * @author        "Michael Collette" <metrol@metrol.net>
 * @package       Metrol/DBSql
 * @version       1.0
 * @copyright (c) 2016, Michael Collette
 */

namespace Metrol\DBSql;

/**
 * Define what every Insert class needs to support
 *
 */
interface InsertInterface extends StatementInterface
{
    /**
     * Which database table is these data going into
     *
     */
    public function table(string $tableName): static;

    /**
     * Sets a SELECT statement that will be used as the source of data for the
     * INSERT.  Any values that have been set will be ignored.  Any bindings
     * from the Select statement will be merged.
     *
     */
    public function valueSelect(SelectInterface $select): static;

    /**
     * Add a Field Value to the stack
     *
     */
    public function addFieldValue(Field\Value $fieldValue): static;

    /**
     * Add a field to return from the insert statement.  Accepts either a
     * single field name, or a list of them in the form of an array.
     *
     */
    public function returning(string|array $fieldName): static;
}
