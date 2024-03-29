<?php
/**
 * @author        "Michael Collette" <metrol@metrol.net>
 * @package       Metrol/DBSql
 * @version       1.0
 * @copyright (c) 2016, Michael Collette
 */

namespace Metrol\Tests;

use PHPUnit\Framework\TestCase;
use Metrol\DBSql;

/**
 * Verify that various uses of the Delete statement work as expected.
 *
 */
class PostgreSQLDeleteTest extends TestCase
{
    /**
     * Assemble a simple Delete statement without bindings
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
