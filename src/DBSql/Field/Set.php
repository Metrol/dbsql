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
 */
class Set
{
    /**
     * The field value objects being handled here
     *
     */
    private array $values = [];

    /**
     * Instantiate the field value set
     *
     */
    public function __construct()
    {

    }

    /**
     * Returns true if the set of values is empty
     *
     */
    public function isEmpty(): bool
    {
        $rtn = true;

        if ( count($this->values) > 0 )
        {
            $rtn = false;
        }

        return $rtn;
    }

    /**
     * Returns true if there are values in the set
     *
     */
    public function isNotEmpty(): bool
    {
        $rtn = true;

        if ( count($this->values) == 0 )
        {
            $rtn = false;
        }

        return $rtn;
    }

    /**
     * Add a field value to the stack
     *
     */
    public function addFieldValue(Value $fieldValue): static
    {
        $this->values[] = $fieldValue;

        return $this;
    }

    /**
     * Provide all the field values that have been set into this object
     *
     */
    public function getFieldValues(): array
    {
        return $this->values;
    }

    /**
     * Provide the field value object for the specified field name
     *
     */
    public function getFieldValue(string $fieldName): ?Value
    {
        $value = null;

        foreach ( $this->values as $val )
        {
            if ( $val->getFieldName() == $fieldName )
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
     */
    public function getFieldNames(): array
    {
        $fieldNames = [];

        foreach ( $this->values as $val )
        {
            $fieldNames[] = $val->getFieldName();
        }

        return $fieldNames;
    }

    /**
     * Provide a list of SQL Strings that will be put in place of actual values
     *
     */
    public function getValueMarkers(): array
    {
        $valueMarkers = [];

        foreach ( $this->values as $val )
        {
             $marker = $val->getValueMarker();

             if ( !empty($marker) )
             {
                 $valueMarkers[] = $marker;
             }
        }

        return $valueMarkers;
    }

    /**
     * Provide an array keyed on the field name, with the value being its
     * value marker for the SQL.
     *
     */
    public function getFieldNamesAndMarkers(): array
    {
        $fieldMarkers = [];

        foreach ( $this->values as $val )
        {
            $fn = $val->getFieldName();
            $mk = $val->getValueMarker();

            if ( empty($fn) or empty($mk) )
            {
                continue;
            }

            $fieldMarkers[$fn] = $mk;
        }

        return $fieldMarkers;
    }

    /**
     * Collect all the bound values together into a single array
     *
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
