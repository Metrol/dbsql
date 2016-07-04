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
     * @param string $tableName
     *
     * @return $this
     */
    public function table($tableName);

    /**
     * Add a set of the field names to show up in the INSERT statement.
     * No value binding provided.
     *
     * @param string[] $fields
     *
     * @return $this
     */
    public function fields(array $fields);

    /**
     * Add a set of the values to assign to the INSERT statement.
     * No value binding provided.  No automatic quoting.
     *
     * @param array $values
     *
     * @return $this
     */
    public function values(array $values);

    /**
     * Sets a SELECT statement that will be used as the source of data for the
     * INSERT.  Any values that have been set will be ignored.  Any bindings
     * from the Select statement will be merged.
     *
     * @param SelectInterface $select
     *
     * @return $this
     */
    public function valueSelect(SelectInterface $select);


    /**
     * Add a set of fields with values to the select request.
     * Values automatically create bindings.
     *
     * @param array $fieldValues
     *
     * @return $this
     */
    public function fieldValues(array $fieldValues);

    /**
     * Add a field to return back from the insert statement.  Accepts either a
     * single field name, or a list of them in the form of an array.
     *
     * @param string|string[]
     *
     * @return $this
     */
    public function returning($fieldName);
}
