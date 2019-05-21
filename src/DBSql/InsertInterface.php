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
     * Add a Field Value to the stack
     *
     * @param Field\Value $fieldValue
     *
     * @return $this
     */
    public function addFieldValue(Field\Value $fieldValue);

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
