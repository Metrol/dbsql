<?php
/**
 * @author        "Michael Collette" <metrol@metrol.net>
 * @package       Metrol/DBSql
 * @version       1.0
 * @copyright (c) 2016, Michael Collette
 */

namespace Metrol\DBSql;

/**
 * Maintains the list of binding values that will be used for executing a
 * prepared PDO statement.
 *
 */
trait BindingsTrait
{
    /**
     * Maintains the list of binding key/value pairs.
     *
     */
    protected array $bindings;

    /**
     * The character used to mark a place where a binding needs to go
     *
     */
    protected string $bindChar = '?';

    /**
     * Initialize the binding values, and clears out any existing ones
     *
     */
    public function initBindings(): static
    {
        $this->bindings = array();

        return $this;
    }

    /**
     * Provide the bindings as an array suitable for a PDO execute statement
     *
     */
    public function getBindings(): array
    {
        return $this->bindings;
    }

    /**
     * Adds a manually named binding to the list
     *
     */
    public function setBinding(string $binding, mixed $value): static
    {
        $this->bindings[ $binding] = $value;

        return $this;
    }

    /**
     * Takes in an array of bindings and adds them to the stack
     *
     */
    public function setBindings(array $bindings): static
    {
        foreach ( $bindings as $key => $value )
        {
            $this->bindings[$key] = $value;
        }

        return $this;
    }

    /**
     * Looks to the specified statement and adds any missing bindings to this
     * stack.
     *
     * If a binding already exists, it is skipped.  This maintains the value
     * of the parent query.
     *
     */
    protected function mergeBindings(StatementInterface $statement): static
    {
        $subBinding = $statement->getBindings();

        foreach ( $subBinding as $key => $value )
        {
            if ( !array_key_exists($key, $this->bindings) )
            {
                $this->setBinding($key, $value);
            }
        }

        return $this;
    }

    /**
     * Provide a generated binding label to use.
     *
     */
    public function getBindLabel(): string
    {
        return ':_' . uniqid() . '_';
    }

    /**
     * Text that has question marks and values to associate to them should be
     * run through here prior to being added to an SQL stack.  This assigns a
     * named binding and records the value to be passed to PDO when executing
     * the statement.
     *
     * @param string     $in     The sub portion of the SQL that may have ? place
     *                           holders in it.
     * @param array|null $values List of values that must match the same number of
     *                           placeholders.  If null, just returns the $in value
     *
     * @return string Provide the same clause back, with every ? replaced with
     *                a named binding as it has been assigned in this object
     */
    protected function bindAssign(string $in, array $values = null): string
    {
        $out = $in;

        if ( $values === null )
        {
            return $out;
        }

        $bindingCount = substr_count($out, $this->bindChar);

        if ( $bindingCount == 0 )
        {
            return $out;
        }

        if ( count($values) !== $bindingCount )
        {
            return $out;
        }

        for ( $i = 0; $i <= $bindingCount; $i++)
        {
            $pos = strpos($out, $this->bindChar);

            if ( $pos !== false )
            {
                $bindLabel = $this->getBindLabel();
                $out  = substr_replace($out, $bindLabel, $pos, 1);

                $this->bindings[$bindLabel] = array_shift($values);
            }
        }

        return $out;
    }
}
