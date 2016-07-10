<?php
/**
 * @author        "Michael Collette" <metrol@metrol.net>
 * @package       Metrol/DBSql
 * @version       1.0
 * @copyright (c) 2016, Michael Collette
 */

use \Metrol\DBSql;
use \Metrol\DBSql\PostgreSQL;

/**
 * Verify that various uses of the Delete statement work as expected.
 *
 */
class PostgreSQLDeleteTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Assemble asimple Insert statment without bindings
     *
     */
    public function testDelete()
    {
        $delete = DBSql::PostgreSQL()->delete();

        $delete->table('tableTooMuchData')
            ->where('id = ?', 12);

        $actual = $delete->output();
        list($label) = array_keys($delete->getBindings());

        $expected = <<<SQL
DELETE
FROM
    "tableTooMuchData"
WHERE
    "id" = {$label}

SQL;
        $this->assertEquals($expected, $actual);
    }
}
