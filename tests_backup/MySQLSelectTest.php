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
 * Verification that the MySQL SELECT statements and supporting methods
 * produce the expected output SQL and data bindings.
 *
 */
class MySQLSelectTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Testing some basic Select work.
     * Starts into testing the ability to reset the FROM stack.
     * Properly quote fields and tables.
     * Default indenting must also be working.
     *
     */
    public function testSelectBasic()
    {
        $expected = <<<SQL
SELECT
    *
FROM
    `tableWithData`

SQL;

        $select = DBSql::MySQL()->select();

        $select->from('tableWithData');

        $this->assertEquals($expected, $select->output());

        $expected = <<<SQL
SELECT
    at.`aPersonName`,
    twd.description
FROM
    `tableWithData` twd,
    `anotherTable` at
WHERE
    at.`Index` = twd.`primaryKey`

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
     * Can disable quoting on the fly.
     *
     */
    public function testSelectCaseWhen()
    {
        $expected = <<<SQL
SELECT
    CASE
        WHEN twd.`Index` < twd.relation THEN
            'Get er done'
        ELSE
            'Got er did'
    END AS foo
FROM
    `tableWithData` twd

SQL;

        $actual = DBSql::MySQL()->select()
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

    /**
     * See if some basic binding into a WHERE clause is working correctly.
     * Did the label get put into the binding as well as the SQL.
     *
     */
    public function testAutoSingleBindingInWhere()
    {
        $select = DBSql::MySQL()->select()
            ->from('tableWithData twd')
            ->where('twd.value = ?', [42]);

        $select->output();
        $bindings = $select->getBindings();

        $this->assertCount(1, $bindings);
        $this->assertContains(42, $bindings);

        $label = key($bindings);

        $expected = <<<SQL
SELECT
    *
FROM
    `tableWithData` twd
WHERE
    twd.value = {$label}

SQL;

        $this->assertEquals($expected, $select->output());
    }

    /**
     * Test multiple automatic bindings into a WHERE clause is working correctly.
     *
     */
    public function testAutoMultiBindingInWhere()
    {
        $select = DBSql::MySQL()->select()
            ->from('tableWithData twd')
            ->where('(twd.Value = ? OR twd.Value = ?)', [42, 36]);

        $select->output();
        $bindings = $select->getBindings();

        $this->assertCount(2, $bindings);
        $this->assertContains(42, $bindings);
        $this->assertContains(36, $bindings);

        list($label1, $label2) = array_keys($bindings);

        $expected = <<<SQL
SELECT
    *
FROM
    `tableWithData` twd
WHERE
    (twd.`Value` = {$label1} OR twd.`Value` = {$label2})

SQL;

        $this->assertEquals($expected, $select->output());
        $this->assertCount(2, $bindings);
        $this->assertEquals(42, $bindings[$label1]);
        $this->assertEquals(36, $bindings[$label2]);
    }

    /**
     * Test the ability of using one SELECT statement to reference a sub-SELECT
     * in the FROM clause.  Also tests that the sub-SELECT's bindings are passed
     * up to the parent properly.
     *
     */
    public function testSubSelectInFrom()
    {
        $select = DBSql::MySQL()->select();
        $sub    = DBSql::MySQL()->select();

        $sub->field('description')
            ->from('relatedData')
            ->where('id = ?', [86]);

        $select->fromSub('reldtq', $sub);

        // Fetch bindings from the parent SELECT.  The sub select should have
        // merged with the parent.
        $bindings = $select->getBindings();
        $label = key($bindings);

        $expected = <<<SQL
SELECT
    *
FROM
    (
        SELECT
            `description`
        FROM
            `relatedData`
        WHERE
            `id` = {$label}
    ) `reldtq`

SQL;

        $this->assertEquals($expected, $select->output());
        $this->assertCount(1, $bindings);
        $this->assertContains(86, $bindings);
        $this->assertEquals(86, $bindings[$label]);
    }

    /**
     * See if the sub select works properly in a WHERE IN statement.
     * To keep things interesting, mix that in with other criteria in the WHERE
     * statement.
     *
     */
    public function testSubSelectInWhere()
    {
        $select = DBSql::MySQL()->select();
        $sub    = DBSql::MySQL()->select();

        $sub->field('description')
            ->from('relatedData')
            ->where('id = ?', [86]);

        $select->field('*')
            ->from('tableWithData twd')
            ->where('twd.value = ?', [42])
            ->whereInSub('twd.description', $sub)
            ->where('twd.id < ?', [97]);

        $actual   = $select->output();
        $bindings = $select->getBindings();

        $this->assertCount(3, $bindings);
        $this->assertContains(86, $bindings);
        $this->assertContains(42, $bindings);
        $this->assertContains(97, $bindings);

        $label1 = array_search(86, $bindings);
        $label2 = array_search(42, $bindings);
        $label3 = array_search(97, $bindings);

        $this->assertEquals(86, $bindings[$label1]);
        $this->assertEquals(42, $bindings[$label2]);
        $this->assertEquals(97, $bindings[$label3]);

        $expected = <<<SQL
SELECT
    *
FROM
    `tableWithData` twd
WHERE
    twd.value = {$label2}
    AND
    twd.description IN
    (
        SELECT
            `description`
        FROM
            `relatedData`
        WHERE
            `id` = {$label1}
    )
    AND
    twd.id < {$label3}

SQL;

        $this->assertEquals($expected, $actual);
    }

    /**
     * See if the sub select works properly in a WHERE NOT IN statement.
     *
     */
    public function testSubSelectNotInWhere()
    {
        $select = DBSql::MySQL()->select();
        $sub    = DBSql::MySQL()->select();

        $sub->field('description')
            ->from('relatedData')
            ->where('id = ?', [86]);

        $select->field('*')
            ->from('tableWithData twd')
            ->where('twd.value = ?', [42])
            ->whereNotInSub('twd.description', $sub)
            ->where('twd.id < ?', [97]);

        $actual   = $select->output();
        $bindings = $select->getBindings();

        $this->assertCount(3, $bindings);
        $this->assertContains(86, $bindings);
        $this->assertContains(42, $bindings);
        $this->assertContains(97, $bindings);

        $label1 = array_search(86, $bindings);
        $label2 = array_search(42, $bindings);
        $label3 = array_search(97, $bindings);

        $this->assertEquals(86, $bindings[$label1]);
        $this->assertEquals(42, $bindings[$label2]);
        $this->assertEquals(97, $bindings[$label3]);

        $expected = <<<SQL
SELECT
    *
FROM
    `tableWithData` twd
WHERE
    twd.value = {$label2}
    AND
    twd.description NOT IN
    (
        SELECT
            `description`
        FROM
            `relatedData`
        WHERE
            `id` = {$label1}
    )
    AND
    twd.id < {$label3}

SQL;

        $this->assertEquals($expected, $actual);
    }

    /**
     * Test the ability to pass into a WHERE clause a list of values that can
     * be a match for a field.
     *
     */
    public function testWhereInValues()
    {
        $select = DBSql::MySQL()->select();

        $valueChar = ['Bob',
                      'Mary',
                      'Sally',
                      'Fred',
                      'George'];

        $select->from('tableWithData twd')
            ->whereIn('twd.name', $valueChar);

        $select->output();
        $bindings = $select->getBindings();

        $this->assertCount(5, $bindings);
        $this->assertContains('Bob',    $bindings);
        $this->assertContains('Mary',   $bindings);
        $this->assertContains('Sally',  $bindings);
        $this->assertContains('Fred',   $bindings);
        $this->assertContains('George', $bindings);

        $label1 = array_search('Bob',    $bindings);
        $label2 = array_search('Mary',   $bindings);
        $label3 = array_search('Sally',  $bindings);
        $label4 = array_search('Fred',   $bindings);
        $label5 = array_search('George', $bindings);

        $actual = $select->output();
        $expected = <<<SQL
SELECT
    *
FROM
    `tableWithData` twd
WHERE
    twd.name IN ({$label1}, {$label2}, {$label3}, {$label4}, {$label5})

SQL;

        $this->assertEquals($expected, $actual);

        // Run through it again, this time with numeric values
        $select = DBSql::MySQL()->select();
        $valueNum = [45, 32, 65, 21, 44];

        $select->from('tableWithData twd')
               ->whereIn('twd.index', $valueNum);

        $select->output();
        $bindings = $select->getBindings();

        $this->assertCount(5, $bindings);
        $this->assertContains(45, $bindings);
        $this->assertContains(32, $bindings);
        $this->assertContains(65, $bindings);
        $this->assertContains(21, $bindings);
        $this->assertContains(44, $bindings);

        $label1 = array_search(45, $bindings);
        $label2 = array_search(32, $bindings);
        $label3 = array_search(65, $bindings);
        $label4 = array_search(21, $bindings);
        $label5 = array_search(44, $bindings);

        $actual = $select->output();
        $expected = <<<SQL
SELECT
    *
FROM
    `tableWithData` twd
WHERE
    twd.index IN ({$label1}, {$label2}, {$label3}, {$label4}, {$label5})

SQL;

        $this->assertEquals($expected, $actual);
    }

    /**
     * Test the ability to pass into a WHERE clause a list of values that should
     * not be a match for a field.
     *
     */
    public function testWhereNotInValues()
    {
        $select = DBSql::MySQL()->select();

        $valueChar = ['Bob',
                      'Mary',
                      'Sally',
                      'Fred',
                      'George'];

        $select->from('tableWithData twd')
            ->whereNotIn('twd.name', $valueChar);

        $select->output();
        $bindings = $select->getBindings();

        $this->assertCount(5, $bindings);
        $this->assertContains('Bob',    $bindings);
        $this->assertContains('Mary',   $bindings);
        $this->assertContains('Sally',  $bindings);
        $this->assertContains('Fred',   $bindings);
        $this->assertContains('George', $bindings);

        $label1 = array_search('Bob',    $bindings);
        $label2 = array_search('Mary',   $bindings);
        $label3 = array_search('Sally',  $bindings);
        $label4 = array_search('Fred',   $bindings);
        $label5 = array_search('George', $bindings);

        $actual   = $select->output();
        $expected = <<<SQL
SELECT
    *
FROM
    `tableWithData` twd
WHERE
    twd.name NOT IN ({$label1}, {$label2}, {$label3}, {$label4}, {$label5})

SQL;

        $this->assertEquals($expected, $actual);
    }

    /**
     * Testing the Group By and Having methods
     *
     */
    public function testGroupByHaving()
    {
        $select = DBSql::MySQL()->select();

        $select->fields(['id', 'stuff', 'moreStuff', 'count(*) `Table Count`'])
            ->from('tableWithData twd')
            ->groupBy('id')                         // Adding only a single field at a time
            ->groupByFields(['stuff', 'moreStuff']) // Multiple group by fields
            ->having('count(*) > ?', [2])           // Check that binding works properly
            ->having('max(stuff) > \'moreStuff\'');   // Having doesn't quote automatically

        $bindings = $select->getBindings();
        $label = key($bindings);

        $this->assertCount(1, $bindings);
        $this->assertContains(2, $bindings);

        $actual = $select->output();

        $expected = <<<SQL
SELECT
    `id`,
    `stuff`,
    `moreStuff`,
    count(*) `Table Count`
FROM
    `tableWithData` twd
GROUP BY
    `id`,
    `stuff`,
    `moreStuff`
HAVING
    count(*) > {$label}
    AND
    max(stuff) > 'moreStuff'

SQL;
        $this->assertEquals($expected, $actual);
    }

    /**
     * Put in a Limit and and Offset into the Select statement
     *
     */
    public function testLimitOffset()
    {
        $select = DBSql::MySQL()->select();

        $select
            ->from('tableWithData twd')
            ->limit(22);

        $actual = $select->output();

        $expected = <<<SQL
SELECT
    *
FROM
    `tableWithData` twd
LIMIT 22

SQL;

        $this->assertEquals($expected, $actual);

        $select = DBSql::MySQL()->select();

        $select
            ->from('tableWithData twd')
            ->limit(22)
            ->offset(45);

        $actual = $select->output();

        $expected = <<<SQL
SELECT
    *
FROM
    `tableWithData` twd
LIMIT 22
OFFSET 45

SQL;

        $this->assertEquals($expected, $actual);
    }

    /**
     * Test the most basic JOIN that's for an INNER join bringing in the
     * criteria with an ON clause.
     *
     */
    public function testJoinInnerOn()
    {
        $select = DBSql::MySQL()->select();

        $select->from('tableWithData twd')
            ->join('otherTable ot',      // The table with alias
                   'ot.twdID = twd.id'); // The ON criteria without bindings

        $actual = $select->output();

        $expected = <<<SQL
SELECT
    *
FROM
    `tableWithData` twd
    JOIN `otherTable` ot
        ON ot.`twdID` = twd.id

SQL;

        $this->assertEquals($expected, $actual);

        $select = DBSql::MySQL()->select();
        $select->from('tableWithData twd')  // The primary table data is from
               ->join('moreData md', 'md.twdID = twd.id') // Simple join
               ->join('otherTable ot', // Join Table
                      'ot.twdID = ?',  // Criteria with a binding place holder
                      [42]);           // The bound value

        $actual   = $select->output();
        $bindings = $select->getBindings();
        $label    = key($bindings);

        $this->assertCount(1, $bindings);
        $this->assertContains(42, $bindings);

        $expected = <<<SQL
SELECT
    *
FROM
    `tableWithData` twd
    JOIN `moreData` md
        ON md.`twdID` = twd.id
    JOIN `otherTable` ot
        ON ot.`twdID` = {$label}

SQL;

        $this->assertEquals($expected, $actual);
    }

    /**
     * Test a JOIN USING that's an INNER join.  Probably the simplest form of
     * a table join that you can do.  No data binding happening here.
     *
     */
    public function testJoinInnerUsing()
    {
        $select = DBSql::MySQL()->select();

        $select->from('tableWithData twd')  // The primary table data is from
               ->join('moreData md', 'md.twdID = twd.id') // Simple join
               ->joinUsing('otherData od', // Table with alias
                           'id, name');    // List of comma separated fields

        $actual = $select->output();

        $expected = <<<SQL
SELECT
    *
FROM
    `tableWithData` twd
    JOIN `moreData` md
        ON md.`twdID` = twd.id
    JOIN `otherData` od
        USING (`id`, `name`)

SQL;

        $this->assertEquals($expected, $actual);
    }

    /**
     * Test a natural join.
     *
     */
    public function testJoinNatural()
    {
        $select = DBSql::MySQL()->select();

        $select->from('tableWithData twd')  // The primary table data is from
               ->joinNatural('moreData md');

        $actual = $select->output();

        $expected = <<<SQL
SELECT
    *
FROM
    `tableWithData` twd
    NATURAL JOIN `moreData` md

SQL;

        $this->assertEquals($expected, $actual);
    }

    /**
     * Testing the outer joins with ON
     *
     */
    public function testJoinOuterOn()
    {
        $select = DBSql::MySQL()->select();
        $select->from('tableWithData twd')  // The primary table data is from
               ->join('moreData md', 'md.twdID = twd.id') // Simple join
               ->joinOuter(MySQL\Select::LEFT,
                      'otherTable ot', // Join Table
                      'ot.twdID = ?',  // Criteria with a binding place holder
                      [42]);           // The bound value

        $actual   = $select->output();
        $bindings = $select->getBindings();
        $label    = key($bindings);

        $this->assertCount(1, $bindings);
        $this->assertContains(42, $bindings);

        $expected = <<<SQL
SELECT
    *
FROM
    `tableWithData` twd
    JOIN `moreData` md
        ON md.`twdID` = twd.id
    LEFT OUTER JOIN `otherTable` ot
        ON ot.`twdID` = {$label}

SQL;

        $this->assertEquals($expected, $actual);
    }

    /**
     * Test a JOIN USING that's an OUTER join.  Probably the simplest form of
     * a table join that you can do.  No data binding happening here.
     *
     */
    public function testJoinOuterUsing()
    {
        $select = DBSql::MySQL()->select();

        $select->from('tableWithData twd')  // The primary table data is from
               ->join('moreData md', 'md.twdID = twd.id') // Simple join
               ->joinOuterUsing(MySQL\Select::FULL,
                     'otherData od', // Table with alias
                     'id, name');    // List of comma separated fields

        $actual = $select->output();

        $expected = <<<SQL
SELECT
    *
FROM
    `tableWithData` twd
    JOIN `moreData` md
        ON md.`twdID` = twd.id
    FULL JOIN `otherData` od
        USING (`id`, `name`)

SQL;

        $this->assertEquals($expected, $actual);
    }

    /**
     * Test ordering the output of the Select statement
     *
     */
    public function testOrderBy()
    {
        $select = DBSql::MySQL()->select();

        $select->from('tableWithData twd')
            ->fields(['id', 'name', 'update'])
            ->order('twd.name', MySQL\Select::ASCENDING)
            ->order('twd.update', MySQL\Select::DESCENDING);

        $actual = $select->output();

        $expected = <<<SQL
SELECT
    `id`,
    `name`,
    `update`
FROM
    `tableWithData` twd
ORDER BY
    twd.name ASC,
    twd.update DESC

SQL;

        $this->assertEquals($expected, $actual);
    }
}
