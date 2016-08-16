<?php
/**
 * @author        "Michael Collette" <metrol@metrol.net>
 * @package       Metrol/DBSql
 * @version       1.0
 * @copyright (c) 2016, Michael Collette
 */

namespace Metrol\DBSql\MySQL;

use Metrol\DBSql\StatementInterface;
use Metrol\DBSql\SelectInterface;

/**
 * Provides handling a WHERE clause for statements that can use it.  This
 * assumes that statement has a QuoterTrait and has include the StackTrait.
 *
 */
trait WhereTrait
{
    /**
     * Add a WHERE clause to the stack of criteria in the SELECT statement.
     * Each new clause called will be included with an "AND" in between.
     *
     * @param string      $criteria
     * @param mixed|array $bindValues
     *
     * @return $this
     */
    public function where($criteria, $bindValues = null)
    {
        if ( !is_array($bindValues) )
        {
            $bindValues = [$bindValues];
        }

        $whereClause = $this->bindAssign($criteria, $bindValues);
        $whereClause = $this->quoter()->quoteField($whereClause);

        $this->wherePush($whereClause);

        return $this;
    }

    /**
     * Sets up a WHERE entry to see if a field has a value in the array provided
     *
     * @param string $fieldName
     * @param array  $values
     *
     * @return $this
     */
    public function whereIn($fieldName, array $values)
    {
        // Don't add a thing without some values to plug in.
        if ( count($values) == 0 )
        {
            return $this;
        }

        $fieldString = $this->quoter()->quoteField($fieldName);

        $placeHolders = '(';
        $placeHolders .= implode(', ', array_fill(0, count($values), '?') );
        $placeHolders .= ')';

        $whereClause = $fieldString.' IN ';
        $whereClause .= $this->bindAssign($placeHolders, $values);

        $this->wherePush($whereClause);

        return $this;
    }

    /**
     * Sets up a WHERE entry to see if a field does not have value in the array
     * provided.
     *
     * @param string $fieldName
     * @param array  $values
     *
     * @return $this
     */
    public function whereNotIn($fieldName, array $values)
    {
        // Don't add a thing without some values to plug in.
        if ( count($values) == 0 )
        {
            return $this;
        }

        $fieldString = $this->quoter()->quoteField($fieldName);

        $placeHolders = '(';
        $placeHolders .= implode(', ', array_fill(0, count($values), '?') );
        $placeHolders .= ')';

        $whereClause = $fieldString.' NOT IN ';
        $whereClause .= $this->bindAssign($placeHolders, $values);

        $this->wherePush($whereClause);

        return $this;
    }

    /**
     * Sets up a WHERE field is in the results of a sub query.  BindingsTrait from
     * the specified sub query are merged as able.  This object (the parent
     * query) has the final say on a binding value when there is a conflict.
     *
     * @param string          $fieldName
     * @param SelectInterface $subSelect
     *
     * @return $this
     */
    public function whereInSub($fieldName, SelectInterface $subSelect)
    {
        $fieldString = $this->quoter()->quoteField($fieldName);

        $whereClause = $fieldString.' IN'.PHP_EOL;
        $whereClause .= $this->indent().'('.PHP_EOL;
        $whereClause .= $this->indentStatement($subSelect, 2);
        $whereClause .= $this->indent().')';

        $this->wherePush($whereClause);

        $this->mergeBindings($subSelect);

        return $this;
    }

    /**
     * Sets up a WHERE field is not in the results of a sub query.  BindingsTrait from
     * the specified sub query are merged as able.  This object (the parent
     * query) has the final say on a binding value when there is a conflict.
     *
     * @param string          $fieldName
     * @param SelectInterface $subSelect
     *
     * @return $this
     */
    public function whereNotInSub($fieldName, SelectInterface $subSelect)
    {
        $fieldString = $this->quoter()->quoteField($fieldName);

        $whereClause = $fieldString.' NOT IN'.PHP_EOL;
        $whereClause .= $this->indent().'('.PHP_EOL;
        $whereClause .= $this->indentStatement($subSelect, 2);
        $whereClause .= $this->indent().')';

        $this->wherePush($whereClause);

        $this->mergeBindings($subSelect);

        return $this;
    }

    /*
     * Everything from this point is documenting the methods that will need
     * to be implemented by the class using this trait.
     */

    /**
     * Push a value on to the WHERE stack
     *
     * @param string $whereClause
     *
     * @return $this
     */
    abstract protected function wherePush($whereClause);

    /**
     *
     * @param StatementInterface $statement
     * @param int                $depth
     *
     * @return string
     */
    abstract public function indentStatement(StatementInterface $statement,
                                             $depth);

    /**
     * Provide the indentation string to prefix text with.
     *
     * @param integer $depth
     *
     * @return string
     */
    abstract protected function indent($depth = 1);

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
    abstract protected function mergeBindings(StatementInterface $statement);

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
    abstract protected function bindAssign($in, array $values = null);


    /**
     * Provide the quoting utility to use on Table and Field names
     *
     * @return Quotable
     */
    abstract protected function quoter();
}