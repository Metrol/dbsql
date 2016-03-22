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
 * Verification that the PostgreSQL SELECT statements and supporting methods
 * produce the expected output SQL and data bindings.
 *
 */
class PostgreSQLSelectTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Testing some basic Select work
     *
     */
    public function testSelectBasic()
    {
        $expected = <<<SQL
SELECT
    *
FROM
    "tableWithData"

SQL;

        $select = DBSql::PostgreSQL()->select();

        $select->from('tableWithData');

        $this->assertEquals($expected, $select->output());

        $expected = <<<SQL
SELECT
    "twd"."Index",
    "at"."aPersonName",
    "twd"."description"
FROM
    "tableWithData" "twd",
    "anotherTable" "at"
WHERE
    "at"."Index" = "twd"."primaryKey"

SQL;

        // Now add in another table and specify some fields.
        $select->fromReset()
            ->from('tableWithData twd')
            ->from('anotherTable at')
            ->field('twd.Index')
            ->fields(['at.aPersonName', 'twd.description'])
            ->where('at.Index = twd.primaryKey');

        $this->assertEquals($expected, $select->output());
    }

    /**
     * Testing a SELECT statement with CASE/WHEN structure in it.
     *
     */
    public function testSelectCaseWhen()
    {
        $expected = <<<SQL
SELECT
    CASE
        WHEN "twd"."Index" < "twd"."relation" THEN
            'Get er done'
        ELSE
            'Got er did'
    END AS "foo"
FROM
    "tableWithData" "twd"

SQL;

        $actual = DBSql::PostgreSQL()->select()
            ->from('tableWithData twd')
            ->caseField()
                ->when('twd.Index < twd.relation')
                ->enableQuoting(false) // Disable automatic quoting for a manual string
                ->then("'Get er done'")
                ->enableQuoting(false) // then() returns a Case object, so need to turn off quoting here as well
                ->elseThen("'Got er did'")
                ->endCase('foo')
            ->output();

        $this->assertEquals($expected, $actual);
    }
}
