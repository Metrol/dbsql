<?php
/**
 * @author        Michael Collette <mcollette@meetingevolution.net>
 * @version       1.0
 * @package       Metrol/DBSql
 * @copyright (c) 2019, Michael Collette
 */

namespace Metrol\DBSql\Field;

/**
 * Provide specific field information for values to be used for INSERTs and
 * UPDATEs.
 *
 * @package Metrol\DBSql
 */
class Value
{
    /**
     * The name of the field
     *
     * @var string
     */
    private $fieldName = null;

    /**
     * The place holder that is used in the SQL string
     *
     * @var string
     */
    private $valueMarker = null;

    /**
     * The bindings to attach to the SQL for storing this value
     *
     * @var array
     */
    private $binding = [];

    /**
     * Instantiate the field value
     *
     * @param string $fieldName The name of the field
     */
    public function __construct(string $fieldName)
    {
        $this->fieldName = $fieldName;
    }

    /**
     * Provide the name of the field
     *
     * @return string
     */
    public function getFieldName(): string
    {
        return $this->fieldName;
    }

    /**
     * Set the place holder that will go into the SQL instead of the actual
     * value.  This may be a binding key, or some combination of keys along
     * with keywords.
     *
     * @param string $marker
     *
     * @return $this
     */
    public function setValueMarker(string $marker): self
    {
        $this->valueMarker = $marker;

        return $this;
    }

    /**
     * Provide the string to put into the SQL statement in place of the actual
     * value.
     *
     * @return string|null
     */
    public function getValueMarker(): string
    {
        return $this->valueMarker;
    }

    /**
     * Set the binding array.  Will overwrite any existing values that have
     * been added.
     *
     * @param array $bindingArray
     *
     * @return $this
     */
    public function setBoundValues(array $bindingArray): self
    {
        $this->binding = $bindingArray;

        return $this;
    }

    /**
     * Add a single binding value to the stack
     *
     * @param string $key
     * @param mixed  $value
     *
     * @return $this
     */
    public function addBinding(string $key, $value): self
    {
        $this->binding[$key] = $value;

        return $this;
    }

    /**
     * Provide the bound values array
     *
     * @return array
     */
    public function getBoundValues(): array
    {
        return $this->binding;
    }

    /**
     * Provide the number of items bound to this field value
     *
     * @return integer
     */
    public function getBindCount(): int
    {
        return count($this->binding);
    }
}
