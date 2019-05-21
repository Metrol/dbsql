<?php
/**
 * @author        "Michael Collette" <metrol@metrol.net>
 * @package       Metrol/DBSql
 * @version       1.0
 * @copyright (c) 2016, Michael Collette
 */

use \Metrol\DBSql;
use \Metrol\DBSql\MySQL;

/**
 * Verification that a complex WITH statement can be built with a variety of
 * components.
 *
 * These tests will also exercise Union, Insert, Update, and Select statements
 * and their ability to bind.  The focus here is on WITH however.
 *
 */
class MySQLWithTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Assemble a simple With statment with a couple of basic Selects and a
     * suffix Select.
     *
     */
    public function testBasicSelects()
    {
        $with = DBSql::MySQL()->with();
        $sel1 = DBSql::MySQL()->select();
        $sel2 = DBSql::MySQL()->select();
        $suff = DBSql::MySQL()->select();

        $sel1->from('tableWithData')
            ->where('data > 12');

        $sel2->from('otherDataTable')
            ->fields(['id', 'name', 'created']);

        $suff->from('twd');

        $with->setStatement('twd', $sel1)
            ->setStatement('odt', $sel2)
            ->setSuffix($suff)
        ;

        $actual = $with->output();
        $expected = <<<SQL
WITH
`twd` AS 
(
    SELECT
        *
    FROM
        `tableWithData`
    WHERE
        `data` > 12
),
`odt` AS 
(
    SELECT
        `id`,
        `name`,
        `created`
    FROM
        `otherDataTable`
)
SELECT
    *
FROM
    twd

SQL;
        $this->assertEquals($expected, $actual);
    }
}
