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
 * Verify that various uses of the Update statement work as expected.
 *
 */
class PostgreSQLUpdateTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Assemble asimple Insert statment without bindings
     *
     */
    public function testUpdateFieldValueNoBindings()
    {
        $update = DBSql::PostgreSQL()->update();

        $update->table('tableNeedingData')
            ->fieldValue('fname', ':firstname')
            ->fieldValue('lname', ':lastname')
            ->where('id = 12');

        $actual = $update->output();

        $expected = <<<SQL
UPDATE
    "tableNeedingData"
SET
    "fname" = :firstname,
    "lname" = :lastname
WHERE
    "id" = 12

SQL;
        $this->assertEquals($expected, $actual);
    }

    /**
     * Assemble an Update statment with automatic bindings
     *
     */
    public function testUpdateFieldValueAutomaticBindings()
    {
        $insert = DBSql::PostgreSQL()->update();

        $insert->table('tableNeedingData')
               ->fieldValue('fname', '?', 'Fred')                 // ? sets up an
               ->fieldValue('lname', '?', 'Flinstone')            // auto binding.
               ->fieldValue('title', '?', 'Bronto Crane Operator')
               ->fieldValue('company', '?', 'Slate Rock');

        $actual   = $insert->output();
        $bindings = $insert->getBindings();

        list($label1, $label2, $label3, $label4) = array_keys($bindings);

        $expected = <<<SQL
UPDATE
    "tableNeedingData"
SET
    "fname" = {$label1},
    "lname" = {$label2},
    "title" = {$label3},
    "company" = {$label4}

SQL;

        $this->assertEquals($expected, $actual);
        $this->assertCount(4, $bindings);
        $this->assertEquals('Fred', $bindings[$label1]);
        $this->assertEquals('Flinstone', $bindings[$label2]);
        $this->assertEquals('Bronto Crane Operator', $bindings[$label3]);
        $this->assertEquals('Slate Rock', $bindings[$label4]);
    }
    /**
     * Assemble Update statment with named bindings
     *
     */
    public function testUpdateFieldValueWithBindings()
    {
        $insert = DBSql::PostgreSQL()->update();

        $insert->table('tableNeedingData');
        $insert->fieldValue('fname', ':firstname', 'Fred');
        $insert->fieldValue('lname', ':lastname',  'Flinstone');

        $bindings = $insert->getBindings();
        $label1 = ':firstname';
        $label2 = ':lastname';

        $actual = $insert->output();

        $expected = <<<SQL
UPDATE
    "tableNeedingData"
SET
    "fname" = :firstname,
    "lname" = :lastname

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
    public function testUpdateWithFieldValueArrayAutomaticBinding()
    {
        $insert = DBSql::PostgreSQL()->update();
        $insert->table('tableNeedingData');

        $data = [
            'fname' => 'Fred',
            'lname' => 'Flinstone'
        ];

        $insert->fieldValues($data)
            ->where('id = ? and status = ?', [12, 'true']);

        $bindings = $insert->getBindings();

        list($label1, $label2, $label3, $label4) = array_keys($bindings);

        $actual = $insert->output();

        $expected = <<<SQL
UPDATE
    "tableNeedingData"
SET
    "fname" = {$label1},
    "lname" = {$label2}
WHERE
    "id" = {$label3} and "status" = {$label4}

SQL;

        $this->assertEquals($expected, $actual);
        $this->assertCount(4, $bindings);
        $this->assertContains('Fred', $bindings);
        $this->assertContains('Flinstone', $bindings);
        $this->assertEquals('Fred', $bindings[$label1]);
        $this->assertEquals('Flinstone', $bindings[$label2]);
        $this->assertEquals(12, $bindings[$label3]);
        $this->assertEquals('true', $bindings[$label4]);
    }

    /**
     * Put a returning field into the mix of an Update statement.
     *
     */
    public function testReturningFieldUpdate()
    {
        $update = DBSql::PostgreSQL()->update();

        $update->table('tableNeedingData')
               ->fieldValue('fname', ':firstname', 'Barney')
               ->fieldValue('lname', ':lastname', 'Rubble')
               ->where('fname = ?', ['Fred'])
               ->where('lname = ?', ['Flinstone'])
               ->returning('tndID');

        $actual   = $update->output();
        $bindings = $update->getBindings();

        list($label1, $label2, $label3, $label4 ) = array_keys($bindings);

        $expected = <<<SQL
UPDATE
    "tableNeedingData"
SET
    "fname" = {$label1},
    "lname" = {$label2}
WHERE
    "fname" = {$label3}
    AND
    "lname" = {$label4}
RETURNING
    "tndID"

SQL;

        $this->assertEquals($expected, $actual);
        $this->assertCount(4, $bindings);
        $this->assertEquals('Barney', $bindings[$label1]);
        $this->assertEquals('Rubble', $bindings[$label2]);
        $this->assertEquals('Fred', $bindings[$label3]);
        $this->assertEquals('Flinstone', $bindings[$label4]);
    }

    /**
     * Test with assigning null values to fields
     *
     */
    public function testNullValueAssignments()
    {
        $update = DBSql::PostgreSQL()->update();

        $update->table('tableNeedingData');
        $update->fieldValue('okayToBeNull', '?', null);

        $bindings = $update->getBindings();

        list($label) = array_keys($bindings);

        $expected = <<<SQL
UPDATE
    "tableNeedingData"
SET
    "okayToBeNull" = {$label}

SQL;

        $this->assertEquals($expected, $update->output());
    }
}
