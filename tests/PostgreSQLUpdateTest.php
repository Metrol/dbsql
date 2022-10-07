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
use Metrol\DBSql\Field\Value;

/**
 * Verify that various uses of the Update statement work as expected.
 *
 */
class PostgreSQLUpdateTest extends TestCase
{
    /**
     * Assemble a simple Update statement without bindings
     *
     */
    public function testUpdateFieldValues()
    {
        $update = DBSql::PostgreSQL()->update();

        $fName = (new Value('fname'))
            ->setValueMarker(':firstname')
            ->addBinding(':firstname', 'Fred');
        $lName = (new Value('lname'))
            ->setValueMarker(':lastname')
            ->addBinding(':lastname', 'Flinstone');

        $update->table('tableNeedingData')
            ->addFieldValue($fName)
            ->addFieldValue($lName)
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
     * Test an update into a complex field type
     *
     */
    public function testInsertIntoComplexFieldType()
    {
        $update = DBSql::PostgreSQL()->update();
        $xKey = Value::getBindKey();
        $yKey = Value::getBindKey();

        $posField = (new Value('position'))
            ->setValueMarker('point('.$xKey.', '.$yKey.')')
            ->addBinding($xKey, [123, 456]);

        $update->table('fancyTable')
               ->addFieldValue($posField);

        $sql = <<<SQL
UPDATE
    "fancyTable"
SET
    "position" = point({$xKey}, {$yKey})

SQL;

        $this->assertEquals($sql, $update->output());
    }


    /**
     * Put a returning field into the mix of an Update statement.
     *
     */
    public function testReturningFieldUpdate()
    {
        $update = DBSql::PostgreSQL()->update();

        $fName = (new Value('fname'))
            ->setValueMarker(':firstname')
            ->addBinding(':firstname', 'Barney');
        $lName = (new Value('lname'))
            ->setValueMarker(':lastname')
            ->addBinding(':lastname', 'Rubble');

        $update->table('tableNeedingData')
               ->addFieldValue($fName)
               ->addFieldValue($lName)
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
    public function xtestNullValueAssignments()
    {
        $update = DBSql::PostgreSQL()->update();
        $bindKey = Value::getBindKey();

        $okayField = (new Value('okayToBeNull'))
            ->setValueMarker($bindKey)
            ->addBinding($bindKey, null);


        $update->table('tableNeedingData');
        $update->addFieldValue($okayField);
        $actual = $update->output();
        $value  = $update->getBindings()[$bindKey];

        $expected = <<<SQL
UPDATE
    "tableNeedingData"
SET
    "okayToBeNull" = {$bindKey}

SQL;

        $this->assertEquals($expected, $actual);
        $this->assertNull($value);
    }
}
