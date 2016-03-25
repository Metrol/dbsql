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
 * Verification that a complex WITH statement can be built with a variety of
 * components.
 *
 * These tests will also exercise Union, Insert, Update, and Select statements
 * and their ability to bind.  The focus here is on WITH however.
 *
 */
class PostgreSQLWithTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Assemble a simple With statment with a couple of basic Selects and a
     * suffix Select.
     *
     */
    public function testBasicSelects()
    {
        $with = DBSql::PostgreSQL()->with();
        $sel1 = DBSql::PostgreSQL()->select();
        $sel2 = DBSql::PostgreSQL()->select();
        $suff = DBSql::PostgreSQL()->select();

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
"twd" AS 
(
    SELECT
        *
    FROM
        "tableWithData"
    WHERE
        "data" > 12
),
"odt" AS 
(
    SELECT
        "id",
        "name",
        "created"
    FROM
        "otherDataTable"
)
SELECT
    *
FROM
    "twd"

SQL;
        $this->assertEquals($expected, $actual);
    }

    /**
     * PostgreSQL has a little tweak to the With clause, allowing for Recursive
     * calls.  This sample SQL was modeled after the example in the PostgreSQL
     * manual
     *
     */
    public function testRecursiveSelect()
    {
        $union = DBSql::PostgreSQL()->union();
        $sel1  = DBSql::PostgreSQL()->select();
        $sel2  = DBSql::PostgreSQL()->select();
        $suff  = DBSql::PostgreSQL()->select();

        $recursFields = ['sub_part', 'part', 'quantity'];

        $sel1->fields(['sub_part', 'part', 'quantity'])
            ->from('parts')
            ->where('part = ?', ['our_product'])
        ;

        $sel2->fields(['p.sub_part', 'p.part', 'p.quantity'])
            ->from('included_parts pr')
            ->from('parts p')
            ->where('p.part = pr.sub_part');

        $suff->field('sub_part')
            ->enableQuoting(false)
            ->field('SUM(quantity) total_quantity')
            ->enableQuoting(true)
            ->from('included_parts')
            ->groupBy('sub_part');

        $union->setSelect($sel1)
            ->setSelect($sel2, $union::UNION_ALL);

        // print PHP_EOL.$sel1->output();
        // print PHP_EOL.$sel2->output().'X';
        // print PHP_EOL.$suff->output();
        // print PHP_EOL.$union->output().'X';

        // First pass, just make sure that all the queries so far are solid
        $actual   = $sel1->output();
        $label    = key($sel1->getBindings());
        $expected = <<<SQL
SELECT
    "sub_part",
    "part",
    "quantity"
FROM
    "parts"
WHERE
    "part" = {$label}

SQL;

        $this->assertEquals($expected, $actual);

        $actual   = $sel2->output();
        $expected = <<<SQL
SELECT
    "p"."sub_part",
    "p"."part",
    "p"."quantity"
FROM
    "included_parts" "pr",
    "parts" "p"
WHERE
    "p"."part" = "pr"."sub_part"

SQL;

        $this->assertEquals($expected, $actual);

        $actual   = $suff->output();
        $expected = <<<SQL
SELECT
    "sub_part",
    SUM(quantity) total_quantity
FROM
    "included_parts"
GROUP BY
    "sub_part"

SQL;

        $this->assertEquals($expected, $actual);

        $actual   = $union->output();
        $label    = key($union->getBindings());
        $expected = <<<SQL
SELECT
    "sub_part",
    "part",
    "quantity"
FROM
    "parts"
WHERE
    "part" = {$label}

UNION ALL

SELECT
    "p"."sub_part",
    "p"."part",
    "p"."quantity"
FROM
    "included_parts" "pr",
    "parts" "p"
WHERE
    "p"."part" = "pr"."sub_part"

SQL;

        $this->assertEquals($expected, $actual);

        // Now to start with just adding all the statements together without
        // the recursive ability.
        $with  = DBSql::PostgreSQL()->with();

        $with->setStatement('parts_is_parts', $sel1)
            ->setStatement('which_parts', $sel2)
            ->setSuffix($suff);

        $actual = $with->output();
        $label  = key($with->getBindings());
        $expected = <<<SQL
WITH
"parts_is_parts" AS 
(
    SELECT
        "sub_part",
        "part",
        "quantity"
    FROM
        "parts"
    WHERE
        "part" = {$label}
),
"which_parts" AS 
(
    SELECT
        "p"."sub_part",
        "p"."part",
        "p"."quantity"
    FROM
        "included_parts" "pr",
        "parts" "p"
    WHERE
        "p"."part" = "pr"."sub_part"
)
SELECT
    "sub_part",
    SUM(quantity) total_quantity
FROM
    "included_parts"
GROUP BY
    "sub_part"

SQL;

        $this->assertEquals($expected, $actual);
        $this->assertCount(1, $with->getBindings());
        $this->assertContains('our_product', $with->getBindings());

        // Next up, put together a With statement using the union of the first
        // two select statements.  No fields specified.
        $with  = DBSql::PostgreSQL()->with();

        $with->setRecursive('included_parts', $union)
             ->setSuffix($suff);

        $actual = $with->output();
        $label  = key($with->getBindings());

        $expected = <<<SQL
WITH RECURSIVE "included_parts" AS 
(
    SELECT
        "sub_part",
        "part",
        "quantity"
    FROM
        "parts"
    WHERE
        "part" = {$label}
    
    UNION ALL
    
    SELECT
        "p"."sub_part",
        "p"."part",
        "p"."quantity"
    FROM
        "included_parts" "pr",
        "parts" "p"
    WHERE
        "p"."part" = "pr"."sub_part"
)
SELECT
    "sub_part",
    SUM(quantity) total_quantity
FROM
    "included_parts"
GROUP BY
    "sub_part"

SQL;

        $this->assertEquals($expected, $actual);
        $this->assertCount(1, $with->getBindings());
        $this->assertContains('our_product', $with->getBindings());

        // Next up, put together a With statement using the union of the first
        // two select statements.  This time with fields
        $with  = DBSql::PostgreSQL()->with();

        $with->setRecursive('included_parts', $union, $recursFields)
             ->setSuffix($suff);

        $actual = $with->output();
        $label  = key($with->getBindings());

        $expected = <<<SQL
WITH RECURSIVE "included_parts"("sub_part", "part", "quantity") AS 
(
    SELECT
        "sub_part",
        "part",
        "quantity"
    FROM
        "parts"
    WHERE
        "part" = {$label}
    
    UNION ALL
    
    SELECT
        "p"."sub_part",
        "p"."part",
        "p"."quantity"
    FROM
        "included_parts" "pr",
        "parts" "p"
    WHERE
        "p"."part" = "pr"."sub_part"
)
SELECT
    "sub_part",
    SUM(quantity) total_quantity
FROM
    "included_parts"
GROUP BY
    "sub_part"

SQL;

        $this->assertEquals($expected, $actual);
        $this->assertCount(1, $with->getBindings());
        $this->assertContains('our_product', $with->getBindings());
    }
}
