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
 * Verify that various uses of the Insert statement work as expected.
 *
 */
class PostgreSQLInsertTest extends TestCase
{
    /**
     * Assemble a simple Insert statement without bindings
     *
     */
    public function testInsertFieldValue()
    {
        $insert = DBSql::PostgreSQL()->insert();
        $fName = (new Value('fname'))
            ->setValueMarker(':firstname')
            ->addBinding(':firstname', 'Fred');
        $lName = (new Value('lname'))
            ->setValueMarker(':lastname')
            ->addBinding(':lastname', 'Flinstone');


        $insert->table('tableNeedingData')
            ->addFieldValue($fName)
            ->addFieldValue($lName);

        $actual = $insert->output();
        $expected = <<<SQL
INSERT
INTO
    "tableNeedingData"
    ("fname", "lname")
VALUES
    (:firstname, :lastname)

SQL;
        $this->assertEquals($expected, $actual);

        $bindings = $insert->getBindings();

        $this->assertCount(2, $bindings);
        $this->assertEquals('Fred', $bindings[':firstname']);
        $this->assertEquals('Flinstone', $bindings[':lastname']);
    }

    /**
     * Test an insert into a complex field type
     *
     */
    public function testInsertIntoComplexFieldType()
    {
        $insert = DBSql::PostgreSQL()->insert();
        $xKey = Value::getBindKey();
        $yKey = Value::getBindKey();

        $posField = (new Value('position'))
            ->setValueMarker('point('.$xKey.', '.$yKey.')')
            ->addBinding($xKey, [123, 456]);

        $insert->table('fancyTable')
            ->addFieldValue($posField);

        $sql = <<<SQL
INSERT
INTO
    "fancyTable"
    ("position")
VALUES
    (point({$xKey}, {$yKey}))

SQL;

        $this->assertEquals($sql, $insert->output());
    }

    /**
     * Test inserting values from a select statement's output.
     *
     */
    public function testInsertFromSelectOutput()
    {
        $select = DBSql::PostgreSQL()->select()
            ->from('tableWithData')
            ->fields(['firstName', 'lastName', 'title', 'company'])
            ->where('primaryKeyValue = ?', [12]);

        $firstName = new Value('firstName');
        $lastName  = new Value('lastName');
        $title     = new Value('title');
        $company   = new Value('company');

        $insert = DBSql::PostgreSQL()->insert()
            ->table('tableNeedingData')
            ->addFieldValue($firstName)
            ->addFieldValue($lastName)
            ->addFieldValue($title)
            ->addFieldValue($company)
            ->valueSelect($select);

        $actual   = $insert->output();
        $bindings = $insert->getBindings();

        $label = key($bindings);

        $expected = <<<SQL
INSERT
INTO
    "tableNeedingData"
    ("firstName", "lastName", "title", "company")
    SELECT
        "firstName",
        "lastName",
        "title",
        "company"
    FROM
        "tableWithData"
    WHERE
        "primaryKeyValue" = {$label}

SQL;

        $this->assertEquals($expected, $actual);
        $this->assertCount(1, $bindings);
        $this->assertEquals(12, $bindings[$label]);
    }

    /**
     * Put a returning field into the mix of an Insert statement.  Important
     * stuff for an auto incrementing serial field.
     *
     */
    public function testReturningFieldInsert()
    {
        $insert = DBSql::PostgreSQL()->insert();
        $fName = (new Value('fname'))
            ->setValueMarker(':firstname')
            ->addBinding(':firstname', 'Fred');
        $lName = (new Value('lname'))
            ->setValueMarker(':lastname')
            ->addBinding(':lastname', 'Flinstone');

        $insert->table('tableNeedingData')
            ->addFieldValue($fName)
            ->addFieldValue($lName)
            ->returning('primaryKeyValue');

        $actual = $insert->output();
        $expected = <<<SQL
INSERT
INTO
    "tableNeedingData"
    ("fname", "lname")
VALUES
    (:firstname, :lastname)
RETURNING
    "primaryKeyValue"

SQL;

        $this->assertEquals($expected, $actual);

        // Now check for multiple values being passed into the returning method
        $insert = DBSql::PostgreSQL()->insert();
        $fName = (new Value('fname'))
            ->setValueMarker(':firstname')
            ->addBinding(':firstname', 'Fred');
        $lName = (new Value('lname'))
            ->setValueMarker(':lastname')
            ->addBinding(':lastname', 'Flinstone');

        $insert->table('tableNeedingData')
               ->addFieldValue($fName)
               ->addFieldValue($lName)
               ->returning(['primaryKeyValue', 'blahBlah']);

        $actual = $insert->output();
        $expected = <<<SQL
INSERT
INTO
    "tableNeedingData"
    ("fname", "lname")
VALUES
    (:firstname, :lastname)
RETURNING
    "primaryKeyValue", "blahBlah"

SQL;

        $this->assertEquals($expected, $actual);
    }

    /**
     * Test the ability to properly bind a null value to a field
     *
     */
    public function testNullValueAssignment()
    {
        $insert = DBSql::PostgreSQL()->insert();
        $bindKey = Value::getBindKey();

        $okayField = (new Value('okayToBeNull'))
            ->setValueMarker($bindKey)
            ->addBinding($bindKey, null);

        $insert->table('tableNeedingData')
            ->addFieldValue($okayField);


        $actual  = $insert->output();
        $binding = $insert->getBindings();
        $value   = $binding[$bindKey];

        $expected =<<<SQL
INSERT
INTO
    "tableNeedingData"
    ("okayToBeNull")
VALUES
    ({$bindKey})

SQL;

        $this->assertEquals($expected, $actual);
        $this->assertNull($value);
    }
}
