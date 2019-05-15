<?php
/**
 * @author        Michael Collette <mcollette@meetingevolution.net>
 * @version       1.0
 * @package       Metrol/DBSql
 * @copyright (c) 2016, Michael Collette
 */

namespace Metrol\DBSql;

/**
 * Provide specific field information for values to be used for INSERTs and
 * UPDATEs.
 *
 * @package Metrol\DBSql
 */
class FieldValue
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
    private $sqlStr = null;

    /**
     * The bindings to attach to the SQL for storing this value
     *
     * @var array
     */
    private $binding = [];

    /**
     * Instantiate the field value
     *
     * @param string $fieldName
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
    public function getName(): string
    {
        return $this->fieldName;
    }

    /**
     * Set the string place holder that will go into the SQL
     *
     * @param string $sql
     *
     * @return $this
     */
    public function setSqlString(string $sql): self
    {
        $this->sqlStr = $sql;

        return $this;
    }

    /**
     * Provide the string to put into the SQL statement
     *
     * @return string|null
     */
    public function getSqlString(): string
    {
        return $this->sqlStr;
    }

    /**
     * Set the binding array for the field with values
     *
     * @param array $binding
     *
     * @return $this
     */
    public function setBinding(array $binding): self
    {
        $this->binding = $binding;

        return $this;
    }

    /**
     * Set a single binding value
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
