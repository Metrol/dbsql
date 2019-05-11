<?php
/**
 * @author        "Michael Collette" <metrol@metrol.net>
 * @package       Metrol/DBSql
 * @version       1.0
 * @copyright (c) 2016, Michael Collette
 */

use \Metrol\DBSql;


/**
 * Verify that various uses of the Insert statement work as expected.
 *
 */
class MySQLInsertTest extends PHPUnit_Framework_TestCase
{
    /**
     * Assemble asimple Insert statment without bindings
     *
     */
    public function testInsertFieldValueNoBindings()
    {
        $insert = DBSql::MySQL()->insert();

        $insert->table('tableNeedingData tnd')
            ->fieldValue('fname', ':firstname')
            ->fieldValue('lname', ':lastname');

        $actual = $insert->output();
        $expected = <<<SQL
INSERT
INTO `tableNeedingData` tnd
    
    (`fname`, `lname`)
VALUES
    (:firstname, :lastname)

SQL;
        $this->assertEquals($expected, $actual);
    }

    /**
     * Assemble an Insert statment with automatic bindings
     *
     */
    public function testInsertFieldValueAutomaticBindings()
    {
        $insert = DBSql::MySQL()->insert();

        $insert->table('tableNeedingData tnd')
            ->fieldValue('fName', '?', 'Fred')                 // ? sets up an
            ->fieldValue('lName', '?', 'Flinstone')            // auto binding.
            ->fieldValue('title', '', 'Bronto Crane Operator') // Empty string.
            ->fieldValue('company', null, 'Slate Rock');       // null value.

        $actual   = $insert->output();
        $bindings = $insert->getBindings();

        list($label1, $label2, $label3, $label4) = array_keys($bindings);

        $expected = <<<SQL
INSERT
INTO
    `tableNeedingData` tnd
    (`fName`, `lName`, `title`, `company`)
VALUES
    ({$label1}, {$label2}, {$label3}, {$label4})

SQL;

        $this->assertEquals($expected, $actual);
        $this->assertCount(4, $bindings);
        $this->assertEquals('Fred', $bindings[$label1]);
        $this->assertEquals('Flinstone', $bindings[$label2]);
        $this->assertEquals('Bronto Crane Operator', $bindings[$label3]);
        $this->assertEquals('Slate Rock', $bindings[$label4]);
    }

    /**
     * Assemble Insert statment with named bindings
     *
     */
    public function testInsertFieldValueWithBindings()
    {
        $insert = DBSql::MySQL()->insert();

        $insert->table('tableNeedingData tnd');
        $insert->fieldValue('fname', ':firstname', 'Fred');
        $insert->fieldValue('lname', ':lastname',  'Flinstone');

        $bindings = $insert->getBindings();
        $label1 = ':firstname';
        $label2 = ':lastname';

        $actual = $insert->output();
        $expected = <<<SQL
INSERT
INTO
    `tableNeedingData` tnd
    (`fname`, `lname`)
VALUES
    (:firstname, :lastname)

SQL;
        $this->assertEquals($expected, $actual);

        $this->assertCount(2, $bindings);
        $this->assertContains('Fred', $bindings);
        $this->assertContains('Flinstone', $bindings);
        $this->assertEquals('Fred', $bindings[$label1]);
        $this->assertEquals('Flinstone', $bindings[$label2]);
    }

    /**
     * Test assigning an array of fields and values with automatic binding
     *
     */
    public function testInsertWithFieldValueArrayAutomaticBinding()
    {
        $insert = DBSql::MySQL()->insert();
        $insert->table('tableNeedingData tnd');

        $data = [
            'fname' => "'Fred'",
            'lname' => "'Flinstone'"
        ];

        $insert->fieldValues($data);

        $bindings = $insert->getBindings();

        list($label1, $label2) = array_keys($bindings);

        $actual = $insert->output();
        $expected = <<<SQL
INSERT
INTO
    `tableNeedingData` tnd
    (`fname`, `lname`)
VALUES
    ({$label1}, {$label2})

SQL;

        $this->assertEquals($expected, $actual);
        $this->assertCount(2, $bindings);
        $this->assertContains("'Fred'", $bindings);
        $this->assertContains("'Flinstone'", $bindings);
        $this->assertEquals("'Fred'", $bindings[$label1]);
        $this->assertEquals("'Flinstone'", $bindings[$label2]);
    }

    /**
     * Insert allows for fields and values to be pushed into the statement
     * at different times.
     *
     * Testing this ability that does not use any automatic data bindings.
     * Instead, you could assign labels to setBindings with the values.
     * Good example of this in the next test.
     *
     */
    public function testInsertPushingFieldsAndValuesSeparately()
    {
        $insert = DBSql::MySQL()->insert();

        $fields = ['firstName',
                   'lastName',
                   'title',
                   'company'];
        $values = ["'Fred'",
                   "'Flinstone'",
                   "'Bronto Crane Operator'",
                   "'Slate Rock'"];

        $insert->fields($fields)->values($values)->table('tableNeedingData');

        $actual   = $insert->output();
        $expected = <<<SQL
INSERT
INTO
    `tableNeedingData`
    (`firstName`, `lastName`, `title`, `company`)
VALUES
    ('Fred', 'Flinstone', 'Bronto Crane Operator', 'Slate Rock')

SQL;

        $this->assertEquals($expected, $actual);
    }

    /**
     * This is the exact same as the above test, but shows how you can manually
     * bind names and values.  This would be a great technique for writing out
     * many records.
     *
     */
    public function testInsertPushingFieldsAndValuesManualBinding()
    {
        $insert = DBSql::MySQL()->insert();

        $fields = ['firstName', 'lastName', 'title', 'company'];
        $values = [
            ':fname'   => 'Fred',
            ':lname'   => 'Flinstone',
            ':title'   => 'Bronto Crane Operator',
            ':company' => 'Slate Rock'];
        $labels = array_keys($values);

        $insert->fields($fields)
            ->values($labels)
            ->setBindings($values)
            ->table('tableNeedingData');

        $actual   = $insert->output();
        $bindings = $insert->getBindings();
        $expected = <<<SQL
INSERT
INTO
    `tableNeedingData`
    (`firstName`, `lastName`, `title`, `company`)
VALUES
    (:fname, :lname, :title, :company)

SQL;

        $this->assertEquals($expected, $actual);
        $this->assertCount(4, $bindings);
        $this->assertEquals('Fred', $bindings[':fname']);
        $this->assertEquals('Flinstone', $bindings[':lname']);
        $this->assertEquals('Bronto Crane Operator', $bindings[':title']);
        $this->assertEquals('Slate Rock', $bindings[':company']);
    }

    /**
     * This test assembles an array of FieldName/Value pairs that automatically
     * quote the fields and bind the values.
     *
     */
    public function testFieldValuePairArrayWithAutomaticBinding()
    {
        $fieldValues = ['firstName' => 'Fred',
                        'lastName'  => 'Flinstone',
                        'title'     => 'Bronto Crane Operator',
                        'company'   => 'Slate Rock'];

        $insert = DBSql::MySQL()->insert();

        $insert->table('tableNeedingData')->fieldValues($fieldValues);

        $actual   = $insert->output();
        $bindings = $insert->getBindings();

        // I should mention that you should never do something like this in
        // production.  Only in a case when you are 132% certain no other code
        // will ever mess with bindings, like in a unit test, can you kind of
        // trust the order of labels.  Use named bindings if you need to work
        // with specific labels.
        list($label1, $label2, $label3, $label4) = array_keys($bindings);

        $expected = <<<SQL
INSERT
INTO
    `tableNeedingData`
    (`firstName`, `lastName`, `title`, `company`)
VALUES
    ({$label1}, {$label2}, {$label3}, {$label4})

SQL;

        $this->assertEquals($expected, $actual);
    }

    /**
     * Test inserting values from a select statement's output.
     *
     */
    public function testInsertFromSelectOutput()
    {
        $select = DBSql::MySQL()->select()
            ->from('tableWithData')
            ->fields(['firstName', 'lastName', 'title', 'company'])
            ->where('primaryKeyValue = ?', [12]);

        $insert = DBSql::MySQL()->insert()
            ->table('tableNeedingData')
            ->fields(['firstName', 'lastName', 'title', 'company'])
            ->valueSelect($select);

        $actual = $insert->output();
        $bindings = $insert->getBindings();

        $label = key($bindings);

        $expected = <<<SQL
INSERT
INTO
    `tableNeedingData`
    (`firstName`, `lastName`, `title`, `company`)
    SELECT
        `firstName`,
        `lastName`,
        `title`,
        `company`
    FROM
        `tableWithData`
    WHERE
        `primaryKeyValue` = {$label}

SQL;

        $this->assertEquals($expected, $actual);
        $this->assertCount(1, $bindings);
        $this->assertEquals(12, $bindings[$label]);
    }
}
