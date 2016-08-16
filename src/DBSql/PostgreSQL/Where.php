<?php
/**
 * @author        Michael Collette <mcollette@meetingevolution.net>
 * @version       1.0
 * @package       Metrol/DBSql
 * @copyright (c) 2016, Michael Collette
 */

namespace Metrol\DBSql\PostgreSQL;

use Metrol\DBSql\WhereInterface;
use Metrol\DBSql\BindingsTrait;
use Metrol\DBSql\StatementInterface;
use Metrol\DBSql\SelectInterface;

/**
 * Describe a WHERE clause for use a SELECT, DELETE, or UPDATE object.  Once a
 * method for setting the clause is called, this object becomes immutable.
 *
 */
class Where implements WhereInterface
{
    use BindingsTrait, QuoterTrait;

    /**
     * The parent statement this where clause belongs to
     *
     * @var StatementInterface
     */
    protected $statement;

    /**
     * The actual where clause being used
     *
     * @var WhereInterface
     */
    private $clause;

    /**
     * Instantiate the Where object
     *
     * @param StatementInterface $statement
     */
    public function __construct(StatementInterface $statement)
    {
        $this->statement = $statement;
        $this->clause    = null;

        $this->initBindings();
    }

    /**
     *
     * @return string
     */
    public function __toString()
    {
        return $this->output();
    }

    /**
     * Produce the clause to appear in the WHERE area of an SQL statement.
     *
     * @return string
     */
    public function output()
    {
        if ( $this->clause === null )
        {
            return '';
        }

        $clauseStr = $this->clause->output();
        $this->assignClauseBindings();

        return $clauseStr;
    }

    /**
     * Set the criteria based on a string
     *
     * @param string $criteria
     * @param mixed|array $bindValues
     */
    public function setCriteria($criteria, $bindValues = null)
    {
        if ( $this->clause !== null )
        {
            return;
        }

        $this->clause = new Where\Criteria($criteria, $bindValues);
    }

    /**
     * Set a criteria based on a field having/not having a value in the provided
     * data set.
     *
     * @param string  $fieldName
     * @param array   $values
     * @param boolean $valueIn
     */
    public function setInList($fieldName, array $values, $valueIn = true)
    {
        if ( $this->clause !== null )
        {
            return;
        }

        $this->clause = new Where\Values($fieldName, $values, $valueIn);
    }

    /**
     * Set a criteria based on a field having/not having a value in the provided
     * Select result
     *
     * @param string          $fieldName
     * @param SelectInterface $subSelect
     * @param boolean         $valueIn
     */
    public function setInSelect($fieldName, SelectInterface $subSelect,
                                $valueIn = true)
    {
        if ( $this->clause !== null )
        {
            return;
        }

        $this->clause = new Where\Select($fieldName, $subSelect, $valueIn);
    }

    /**
     * Look for any bindings the clause may have created and be sure to assign
     * them to the parent statement.
     *
     */
    private function assignClauseBindings()
    {
        foreach ( $this->clause->getBindings() as $key => $value )
        {
            $this->setBinding($key, $value);
        }
    }
}
