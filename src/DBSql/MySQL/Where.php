<?php
/**
 * @author        Michael Collette <mcollette@meetingevolution.net>
 * @version       1.0
 * @package       Metrol/DBSql
 * @copyright (c) 2016, Michael Collette
 */

namespace Metrol\DBSql\MySQL;

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
     */
    protected StatementInterface $statement;

    /**
     * The actual where clause being used
     *
     */
    private WhereInterface $clause;

    /**
     * Instantiate the Where object
     *
     */
    public function __construct(StatementInterface $statement)
    {
        $this->statement = $statement;

        $this->initBindings();
    }

    /**
     *
     */
    public function __toString(): string
    {
        return $this->output();
    }

    /**
     * Produce the clause to appear in the WHERE area of an SQL statement.
     *
     */
    public function output(): string
    {
        if ( ! isset($this->clause) )
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
     */
    public function setCriteria(string $criteria, mixed $bindValues = null): void
    {
        if ( isset($this->clause) )
        {
            return;
        }

        $this->clause = new Where\Criteria($criteria, $bindValues);
    }

    /**
     * Set a criteria based on a field having/not having a value in the provided
     * data set.
     *
     */
    public function setInList(string $fieldName, array $values, bool $valueIn = true)
    {
        if ( isset($this->clause) )
        {
            return;
        }

        $this->clause = new Where\Values($fieldName, $values, $valueIn);
    }

    /**
     * Set a criteria based on a field having/not having a value in the provided
     * Select result
     *
     */
    public function setInSelect(string          $fieldName,
                                SelectInterface $subSelect,
                                bool            $valueIn = true)
    {
        if ( isset($this->clause) )
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
    private function assignClauseBindings(): void
    {
        foreach ( $this->clause->getBindings() as $key => $value )
        {
            $this->setBinding($key, $value);
        }
    }
}
