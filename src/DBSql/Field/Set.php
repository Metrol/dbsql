<?php
/**
 * @author        Michael Collette <mcollette@meetingevolution.net>
 * @version       1.0
 * @package       Metrol/DBSql
 * @copyright (c) 2019, Michael Collette
 */

namespace Metrol\DBSql\Field;

/**
 * Maintains a set of Field Value objects for use with DB writing
 *
 * @package Metrol\DBSql
 */
class Set
{
    /**
     * The field value objects being handled here
     *
     * @var Value[]
     */
    private $values = [];

    /**
     * Instantiate the field value set
     *
     */
    public function __construct()
    {

    }

    /**
     * Add a field value to the stack
     *
     * @param Value $fieldValue
     *
     * @return $this
     */
    public function addFieldValue(Value $fieldValue): self
    {
        $this->values[] = $fieldValue;

        return $this;
    }

    /**
     * Provide all the field values that have been set into this object
     *
     * @return Value[]
     */
    public function getFieldValues(): array
    {
        return $this->values;
    }

    /**
     * Provide the field value object for the specified field name
     *
     * @param string $fieldName
     *
     * @return Value|null
     */
    public function getFieldValue(string $fieldName)
    {
        $value = null;

        foreach ( $this->values as $val )
        {
            if ( $val->getName() == $fieldName )
            {
                $value = $val;
                break;
            }
        }

        return $value;
    }

    /**
     * Provide a list of field names from this set
     *
     * @return string[]
     */
    public function getFieldNames(): array
    {
        $fieldNames = [];

        foreach ( $this->values as $val )
        {
            $fieldNames[] = $val->getName();
        }

        return $fieldNames;
    }

    /**
     * Provide a list of SQL Strings that will be put in place of actual values
     *
     * @return string[]
     */
    public function getSqlStrings(): array
    {
        $sqlStrings = [];

        foreach ( $this->values as $val )
        {
            $sqlStrings[] = $val->getSqlString();
        }

        return $sqlStrings;
    }

    /**
     * Collect all the bound values together into a single array
     *
     * @return array
     */
    public function getBoundValues(): array
    {
        $boundValues = [];

        foreach ( $this->values as $val )
        {
            $boundValues = array_merge($boundValues, $val->getBoundValues());
        }

        return $boundValues;
    }
}
