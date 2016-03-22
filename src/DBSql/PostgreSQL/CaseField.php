<?php
/**
 * @author        "Michael Collette" <metrol@metrol.net>
 * @package       Metrol/DBSql
 * @version       1.0
 * @copyright (c) 2016, Michael Collette
 */

namespace Metrol\DBSql\PostgreSQL;

use Metrol\DBSql\CaseFieldInterface;
use Metrol\DBSql\CaseInterface;
use Metrol\DBSql\Bindings;
use Metrol\DBSql\Indent;
use Metrol\DBSql\WhenInterface;
use Metrol\DBSql\SelectInterface;

/**
 * Handles opening and closing CASE statements for the Select object
 *
 */
class CaseField implements CaseFieldInterface
{
    use Bindings, Indent, Quoter;

    /**
     * Holds all the WHENs that belong to this object
     *
     * @var When[]
     */
    protected $whenStack;

    /**
     * The Select object that called this one into being.  Saved here to pass
     * back after the case is closed.
     *
     * @var Select
     */
    protected $select;

    /**
     * Alias used to identify the result of the CASE
     *
     * @var string
     */
    protected $alias;

    /**
     * What the result will be if none of the When clauses have a match
     *
     * @var string
     */
    protected $elseResult;

    /**
     * Instantiate and initialize the object
     *
     * @param Select $select
     */
    public function __construct(Select $select)
    {
        $this->select     = $select;
        $this->whenStack  = array();
        $this->alias      = null;
        $this->elseResult = null;

        $this->initBindings();
        $this->initIndent();
    }

    /**
     * Adds a WHEN statement to the stack and provides the WHEN object
     * to provide the stack.
     *
     * @param string $criteria
     * @param array  $bindValues
     *
     * @return WhenInterface
     */
    public function when(string $criteria, array $bindValues = null): WhenInterface
    {
        $when = new When($this);
        $when->setCriteria($criteria, $bindValues);

        $this->whenStack[] = $when;

        return $when;
    }

    /**
     * The final fall through if none of the WHEN cases match.
     *
     * @param string $elseResult
     * @param array  $bindValues
     *
     * @return CaseInterface
     */
    public function elseThen(string $elseResult, array $bindValues = null): CaseInterface
    {
        $this->elseResult = $this->bindAssign($elseResult, $bindValues);
        $this->elseResult = $this->quoter()->quoteField($this->elseResult);

        return $this;
    }

    /**
     * Assembles the CASE statement, pushes it onto the Select object, then
     * passes back the Select object to continue chaining the query.
     *
     * @param string $alias
     *
     * @return SelectInterface
     */
    public function endCase(string $alias): SelectInterface
    {
        $this->alias = $alias;

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
     * @retrun string
     */
    protected function buildSQL(): string
    {
        $sql = 'CASE'.PHP_EOL;

        foreach ( $this->whenStack as $when )
        {
            $sql .= $this->indent(2).$when->output();
            $this->setBindings($when->getBindings());
        }

        if ( $this->elseResult !== null )
        {
            $sql .= $this->indent(2);
            $sql .= 'ELSE'.PHP_EOL;
            $sql .= $this->indent(3);
            $sql .= $this->elseResult;
            $sql .= PHP_EOL;
        }

        $sql .= $this->indent();
        $sql .= 'END';

        if ( $this->alias !== null )
        {
            if ( strpos($this->alias, '"') === false )
            {
                $sql .= ' AS "'.$this->alias.'"';
            }
            else
            {
                $sql .= ' AS ' . $this->alias;
            }
        }

        return $sql;
    }
}