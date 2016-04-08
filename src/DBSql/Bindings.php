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
trait Bindings
{
    /**
     * Maintains the list of binding key/value pairs.
     *
     * @var array
     */
    protected $bindings;

    /**
     * The character used to mark a place where a binding needs to go
     *
     * @var string
     */
    protected $bindChar = '?';

    /**
     * Initialize the binding values, and clears out any existing ones
     *
     * @return $this
     */
    public function initBindings()
    {
        $this->bindings = array();

        return $this;
    }

    /**
     * Provide the bindings as an array suitable for a PDO execute statement
     *
     * @return array
     */
    public function getBindings()
    {
        return $this->bindings;
    }

    /**
     * Adds a manually named binding to the list
     *
     * @param string $bindName
     * @param mixed  $bindValue
     *
     * @return $this
     */
    public function setBinding($bindName, $bindValue)
    {
        $this->bindings[$bindName] = $bindValue;

        return $this;
    }

    /**
     * Takes in an array of bindings and adds them to the stack
     *
     * @param array $bindings
     *
     * @return $this
     */
    public function setBindings(array $bindings)
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
     * @param StatementInterface $statement
     *
     * @return $this
     */
    protected function mergeBindings(StatementInterface $statement)
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
     * @return string
     */
    public function getBindLabel()
    {
        $label = ':_' . uniqid() . '_';

        return $label;
    }

    /**
     * Text that has question marks and values to associate to them should be
     * run through here prior to being added to an SQL stack.  This assigns a
     * named binding and records the value to be passed to PDO when executing
     * the statement.
     *
     * @param string $in     The sub portion of the SQL that may have ? place
     *                       holders in it.
     * @param array  $values List of values that must match the same number of
     *                       place holders.  If null, just returns the $in value
     *
     * @return string Provide the same clause back, with every ? replaced with
     *                a named binding as it has been assigned in this object
     */
    protected function bindAssign($in, array $values = null)
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
