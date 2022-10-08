<?php
/**
 * @author        Michael Collette <mcollette@meetingevolution.net>
 * @version       1.0
 * @package       Metrol/DBSql
 * @copyright (c) 2019, Michael Collette
 */

namespace Metrol\DBSql\Field;

/**
 * Provide specific field information for values to be used for inserts and
 * updates.
 *
 */
class Value
{
    /**
     * The name of the field
     *
     */
    private string $fieldName;

    /**
     * The placeholder that is used in the SQL string
     *
     */
    private string $valueMarker = '';

    /**
     * The bindings to attach to the SQL for storing this value
     *
     */
    private array $binding = [];

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
     */
    public function getFieldName(): string
    {
        return $this->fieldName;
    }

    /**
     * Set the placeholder that will go into the SQL instead of the actual
     * value.  This may be a binding key, or some combination of keys along
     * with keywords.
     *
     */
    public function setValueMarker(string $marker): static
    {
        $this->valueMarker = $marker;

        return $this;
    }

    /**
     * Provide the string to put into the SQL statement in place of the actual
     * value.
     *
     */
    public function getValueMarker(): string
    {
        return $this->valueMarker;
    }

    /**
     * Set the binding array.  Will overwrite any existing values that have
     * been added.
     *
     */
    public function setBoundValues(array $bindingArray): static
    {
        $this->binding = $bindingArray;

        return $this;
    }

    /**
     * Add a single binding value to the stack.
     * Accepts any string as a key, or a ? for automatic binding
     *
     */
    public function addBinding(string $key, mixed $value): static
    {
        $this->binding[$key] = $value;

        return $this;
    }

    /**
     * Provide the bound values array
     *
     */
    public function getBoundValues(): array
    {
        return $this->binding;
    }

    /**
     * Provide the number of items bound to this field value
     *
     */
    public function getBindCount(): int
    {
        return count($this->binding);
    }

    /**
     * Provide a unique binding key
     *
     */
    static public function getBindKey(): string
    {
        return uniqid(':_') . '_';
    }
}
