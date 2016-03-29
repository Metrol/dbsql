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
               ->fieldValue('title', '', 'Bronto Crane Operator') // Empty string.
               ->fieldValue('company', null, 'Slate Rock');       // null value.

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

}