<?php
/**
 * @author        "Michael Collette" <metrol@metrol.net>
 * @package       Metrol/DBSql
 * @version       1.0
 * @copyright (c) 2016, Michael Collette
 */

namespace Metrol\DBSql\PostgreSQL;

use Metrol\DBSql\{CaseFieldInterface, BindingsTrait, IndentTrait,
                  WhenInterface, SelectInterface};

/**
 * Handles opening and closing CASE statements for the Select object
 *
 */
class CaseField implements CaseFieldInterface
{
    use BindingsTrait, IndentTrait, QuoterTrait;

    /**
     * Holds all the WHENs that belong to this object
     *
     */
    protected array $whenStack = [];

    /**
     * The Select object that called this one into being.  Saved here to pass
     * back after the case is closed.
     *
     */
    protected Select $select;

    /**
     * Alias used to identify the result of the CASE
     *
     */
    protected string $alias;

    /**
     * What the result will be if none of the When clauses have a match
     *
     */
    protected string $elseResult;

    /**
     * Instantiate and initialize the object
     *
     * @param Select $select
     */
    public function __construct(Select $select)
    {
        $this->select = $select;

        $this->initBindings();
        $this->initIndent();
    }

    /**
     * Added to properly support the Statement interface and debugging.
     * You should not normally need this when called from a Select statement
     * as this was intended.
     *
     */
    public function output(): string
    {
        return $this->buildSQL();
    }

    /**
     * Adds a WHEN statement to the stack and provides the WHEN object
     * to provide the stack.
     *
     */
    public function when(string $criteria, ?array $bindValues = null): WhenInterface
    {
        $when = new When($this);
        $when->setCriteria($criteria, $bindValues);

        $this->whenStack[] = $when;

        return $when;
    }

    /**
     * The final fall through if none of the WHEN cases match.
     *
     */
    public function elseThen(string $elseResult, ?array $bindValues = null): static
    {
        $this->elseResult = $this->bindAssign($elseResult, $bindValues);
        $this->elseResult = $this->quoter()->quoteField($this->elseResult);

        return $this;
    }

    /**
     * Assembles the CASE statement, pushes it onto the Select object, then
     * passes back the Select object to continue chaining the query.
     *
     */
    public function endCase(?string $alias = null): SelectInterface
    {
        if ( ! is_null($alias) )
        {
            $this->alias = $alias;
        }

        $quoteSetting = $this->select->quoter()->isEnabled();

        $this->select->enableQuoting(false)
            ->field($this->buildSQL())
            ->enableQuoting($quoteSetting);

        $this->select->setBindings( $this->getBindings() );

        return $this->select;
    }

    /**
     * Assembles the CASE statement for the Select statement
     *
     */
    protected function buildSQL(): string
    {
        $sql = 'CASE'.PHP_EOL;

        foreach ( $this->whenStack as $when )
        {
            $sql .= $this->indent(2).$when->output();
            $this->setBindings($when->getBindings());
        }

        if ( isset($this->elseResult) )
        {
            $sql .= $this->indent(2);
            $sql .= 'ELSE'.PHP_EOL;
            $sql .= $this->indent(3);
            $sql .= $this->elseResult;
            $sql .= PHP_EOL;
        }

        $sql .= $this->indent();
        $sql .= 'END';

        if ( isset($this->alias) )
        {
            if ( ! str_contains($this->alias, '"')
                and $this->alias !== strtolower($this->alias) )
            {
                $sql .= ' AS "' . $this->alias . '"';
            }
            else
            {
                $sql .= ' AS ' . $this->alias;
            }
        }

        return $sql;
    }
}
