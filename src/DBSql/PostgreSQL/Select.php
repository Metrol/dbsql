<?php
/**
 * @author        "Michael Collette" <metrol@metrol.net>
 * @package       Metrol/DBSql
 * @version       1.0
 * @copyright (c) 2016, Michael Collette
 */

namespace Metrol\DBSql\PostgreSQL;

use Metrol\DBSql\{SelectInterface, StackTrait, BindingsTrait, IndentTrait,
                  OutputTrait};

/**
 * Creates an SQL statement for PostgreSQL
 *
 */
class Select implements SelectInterface
{
    use StackTrait, BindingsTrait, QuoterTrait, IndentTrait, WhereTrait,
        OutputTrait;

    /**
     * Joining keywords for comparisons.
     *
     * @const
     */
    const JOIN    = 'JOIN';
    const LEFT    = 'LEFT';
    const RIGHT   = 'RIGHT';
    const NATURAL = 'NATURAL';
    const FULL    = 'FULL';

    /**
     * Sorting directions
     *
     * @const string
     */
    const ASCENDING   = 'ASC';
    const DESCENDING  = 'DESC';
    const NULLS_FIRST = 'NULLS FIRST';
    const NULLS_LAST  = 'NULLS LAST';

    /**
     * Whether to use the DISTINCT keyword
     *
     */
    protected bool $distinctFlag = false;

    /**
     * When the distinct flag is set, this value will populate the DISTINCT ON
     * expression.  When the flag is false, this value is ignored.
     *
     */
    protected string $distinctExpression = '';

    /**
     * The Limit value for how many rows can be returned.
     *
     */
    protected int $limitVal;

    /**
     * The Offset value for where to start the result set from
     *
     */
    protected int $offsetVal;

    /**
     * Instantiate and initialize the object
     *
     */
    public function __construct()
    {
        $this->initStacks();
        $this->initBindings();
        $this->initIndent();

    }

    /**
     * Just a fast way to call the output() method
     *
     */
    public function __toString(): string
    {
        return $this->output().PHP_EOL;
    }

    /**
     * Set the DISTINCT flag on the Select statement
     *
     */
    public function distinct(bool $flag, string $expression = ''): static
    {
        if ( $flag )
        {
            $this->distinctFlag = true;
        }
        else
        {
            $this->distinctFlag = false;
        }

        if ( empty($expression) )
        {
            $this->distinctExpression = '';
        }
        else
        {
            $parts = explode(',', $expression);

            foreach ( $parts as $i => $part )
            {
                $parts[ $i ] = $this->quoter()->quoteField(trim($part));
            }

            $this->distinctExpression = implode(', ', $parts);
        }

        return $this;
    }

    /**
     * Add a column/field to what is being requested
     *
     */
    public function field(string $fieldName): static
    {
        $fieldString = $this->quoter()->quoteField($fieldName);

        $this->fieldStack[] = $fieldString;

        return $this;
    }

    /**
     * Sets the fields going to the select request.
     * Replaces any fields already set.
     *
     */
    public function fields(array $fieldNames): static
    {
        $this->fieldStack = array();

        foreach ( $fieldNames as $fieldName )
        {
            $fieldString = $this->quoter()->quoteField($fieldName);

            $this->fieldStack[] = $fieldString;
        }

        return $this;
    }

    /**
     * Adds a CASE/WHEN/THEN structure to the field stack.
     * When chaining calls, you must call the Case->end() method to get this
     * object back.
     *
     */
    public function caseField(): CaseField
    {
        return new CaseField($this);
    }

    /**
     * Add a data source to the FROM clause of the query.
     *
     */
    public function from(string $fromName): static
    {
        $tableString = $this->quoter()->quoteTable($fromName);

        $this->fromStack[] = $tableString;

        return $this;
    }

    /**
     * Add a sub select as a data source in the FROM clause of the query.
     * Any bindings from the sub select will be merged with the parent SELECT
     * statement.  Conflicts will defer to the parent value.
     *
     */
    public function fromSub(string $alias, SelectInterface $subSelect): static
    {
        // Assemble the string
        $fromClause  = '('.PHP_EOL;
        $fromClause .= $this->indentStatement($subSelect, 2);
        $fromClause .= $this->indent().') ';
        $fromClause .= $this->quoter()->quoteField($alias);

        // Add it to the stack
        $this->fromStack[] = $fromClause;

        // Merge the bindings and values into here
        $this->mergeBindings($subSelect);

        return $this;
    }

    /**
     * Adds values or sets of values to the FROM clause with optional field
     * names.  Values can be automatically bound or left alone based on the
     * binding flag.
     *
     * @param array   $values   Can be a list of values, or a list of arrays of
     *                          values to form sets.  Sets should all have the same
     *                          number of elements with consistent types.
     * @param string  $alias    An alias is required.  You can add field names for
     *                          sets of data here.
     * @param boolean $bindFlag When set to true, all the values are automatically
     *                          given bindings.  Otherwise, they are left alone.
     *
     * @return $this
     */
    public function fromValues(array $values, string $alias, bool $bindFlag = true): static
    {
        if ( empty($values) )
        {
            return $this;
        }

        // Bind values as needed
        foreach ( $values as $vIdx => $value )
        {
            if ( is_array($value) )
            {
                $newSet = array();

                foreach ( $value as $setIdx => $setItem )
                {
                    if ( $bindFlag )
                    {
                        $label = $this->getBindLabel();
                        $this->setBinding($label, $setItem);
                        $newSet[ $setIdx ] = $label;
                    }
                    else
                    {
                        $newSet[ $setIdx ] = $setItem;
                    }
                }

                $values[$vIdx] = $newSet;
            }
            else
            {
                if ( $bindFlag === true )
                {
                    $label = $this->getBindLabel();
                    $this->setBinding($label, $value);
                    $values[$vIdx] = $label;
                }
            }
        }

        // Assemble the string that can go into the FROM clause
        $from = '( VALUES';

        reset($values);

        if ( is_array( current($values) ) )
        {
            $sets = array();

            foreach ( $values as $setItems )
            {
                $sets[] = '('. implode('), (', $setItems). ')';
            }

            $from .= ' ('.implode('), (', $sets). ')';
        }
        else
        {
            $from .= ' ('. implode('), (', $values) .')';
        }

        $from .= ' ) AS '.$alias;

        $this->fromStack[] = $from;

        return $this;
    }

    /**
     * Adds an INNER JOIN clause to the SELECT statement.
     *
     */
    public function join(string $tableName, string $onCriteria, array $bindValues = null): static
    {
        $tableName  = $this->quoter()->quoteTable($tableName);
        $onCriteria = $this->bindAssign($onCriteria, $bindValues);
        $onCriteria = $this->quoter()->quoteField($onCriteria);

        $join  = 'JOIN ';
        $join .= $tableName.PHP_EOL;
        $join .= $this->indent(2);
        $join .= 'ON ';
        $join .= $onCriteria;

        $this->joinStack[] = $join;

        return $this;
    }

    /**
     * Adds an INNER JOIN clause to the SELECT statement with USING as the join
     * criteria.
     * - No data binding is provided here.
     *
     */
    public function joinUsing(string $tableName, string $criteria): static
    {
        $tableName  = $this->quoter()->quoteTable($tableName);

        $join  = 'JOIN ';
        $join .= $tableName.PHP_EOL;
        $join .= $this->indent(2);
        $join .= 'USING ';

        $parts = explode(',', $criteria);

        foreach ( $parts as $i => $part)
        {
            $parts[$i] = $this->quoter()->quoteField(trim($part));
        }

        $join .= '('. implode(', ', $parts) .')';

        $this->joinStack[] = $join;

        return $this;
    }

    /**
     * Adds a NATURAL INNER JOIN clause to the SELECT statement.
     *
     */
    public function joinNatural(string $tableName): static
    {
        $tableName  = $this->quoter()->quoteTable($tableName);

        $join  = 'NATURAL JOIN ';
        $join .= $tableName;

        $this->joinStack[] = $join;

        return $this;
    }

    /**
     * Adds a LEFT/RIGHT/FULL OUTER JOIN clause to the SELECT statement.
     *
     * @param string $joinType   LEFT|RIGHT|FULL
     * @param string $tableName
     * @param string $onCriteria ON criteria for the JOIN.
     * @param ?array $bindValues List of values to bind into the criteria
     *
     * @return $this
     */
    public function joinOuter(string $joinType,
                              string $tableName,
                              string $onCriteria,
                              array  $bindValues = null): static
    {
        $joinType = strtoupper($joinType);

        if ( ! in_array($joinType, [self::LEFT, self::RIGHT, self::FULL]) )
        {
            return $this;
        }

        $tableName  = $this->quoter()->quoteTable($tableName);
        $onCriteria = $this->bindAssign($onCriteria, $bindValues);
        $onCriteria = $this->quoter()->quoteField($onCriteria);

        $join  = $joinType.' OUTER JOIN ';
        $join .= $tableName.PHP_EOL;
        $join .= $this->indent(2);
        $join .= 'ON ';
        $join .= $onCriteria;

        $this->joinStack[] = $join;

        return $this;
    }

    /**
     * Adds an OUTER JOIN clause to the SELECT statement with USING as the join
     * criteria.  No data binding is provided here.
     *
     * @param string $joinType LEFT|RIGHT|FULL
     * @param string $tableName
     * @param string $criteria Field names for the USING clause
     *
     * @return $this
     */
    public function joinOuterUsing(string $joinType, string $tableName, string $criteria): static
    {
        $tableName  = $this->quoter()->quoteTable($tableName);

        $joinType = strtoupper($joinType);

        if ( ! in_array($joinType, [self::LEFT, self::RIGHT, self::FULL]) )
        {
            return $this;
        }

        $join  = $joinType.' JOIN ';
        $join .= $tableName.PHP_EOL;
        $join .= $this->indent(2);
        $join .= 'USING ';

        $parts = explode(',', $criteria);

        foreach ( $parts as $i => $part)
        {
            $parts[$i] = $this->quoter()->quoteField(trim($part));
        }

        $join .= '('. implode(', ', $parts) .')';

        $this->joinStack[] = $join;

        return $this;
    }

    /**
     * Add fields to order the result set by
     *
     * @param string      $fieldName
     * @param string|null $direction
     * @param string|null $nullOrder 'NULLS FIRST' | 'NULLS LAST' Defaults to LAST
     *
     * @return $this
     */
    public function order(string $fieldName, string $direction = null, string $nullOrder = null): static
    {
        if ( $direction === null )
        {
            $direction = self::ASCENDING;
        }
        else
        {
            $direction = strtoupper($direction);
        }

        if ( $nullOrder !== null )
        {
            if ( $nullOrder === self::NULLS_FIRST or $nullOrder === self::NULLS_LAST )
            {
                $nullOrder = ' '.$nullOrder;
            }
        }
        else
        {
            $nullOrder = '';
        }

        if ( $direction !== self::DESCENDING and $direction !== self::ASCENDING)
        {
            $direction = self::ASCENDING;
        }

        $sql  = $this->quoter()->quoteField($fieldName);
        $sql .= ' '.$direction.$nullOrder;

        $this->orderStack[] = $sql;

        return $this;
    }

    /**
     * Add a field to the GROUP BY clause.
     *
     */
    public function groupBy(string $fieldName): static
    {
        $groupString = $this->quoter()->quoteField($fieldName);

        $this->groupStack[] = $groupString;

        return $this;
    }

    /**
     * Add a set of fields to the GROUP BY clause
     *
     */
    public function groupByFields(array $fieldNames): static
    {
        foreach ( $fieldNames as $fieldName )
        {
            $groupString = $this->quoter()->quoteField($fieldName);

            $this->groupStack[] = $groupString;
        }

        return $this;
    }

    /**
     * Add a HAVING clause to the stack of criteria in the SELECT statement.
     * Each new clause called will be included with an "AND" in between.
     * Field names will not be quoted.
     * You must quote where needed yourself.
     *
     */
    public function having(string $criteria, array $bindValues = null): static
    {
        $havingClause = $this->bindAssign($criteria, $bindValues);

        $this->havingStack[] = $havingClause;

        return $this;
    }

    /**
     * Sets the limit for how many rows to pull back from the query.
     *
     */
    public function limit(int $rowCount): static
    {
        $this->limitVal = $rowCount;

        return $this;
    }

    /**
     * Sets the offset for which row to start with on the result set from the
     * query.
     *
     */
    public function offset(int $startRow): static
    {
        $this->offsetVal = $startRow;

        return $this;
    }

    /**
     * Builds the SQL statement for the output by delegating out most of the
     * work to helper methods, then returns resulting string.
     *
     */
    protected function buildSQL(): string
    {
        $sql = 'SELECT';
        $sql .= $this->buildDistinct();
        $sql .= $this->buildFields();
        $sql .= $this->buildFrom();
        $sql .= $this->buildJoins();
        $sql .= $this->buildWhere();
        $sql .= $this->buildGroupBy();
        $sql .= $this->buildHaving();
        $sql .= $this->buildOrder();
        $sql .= $this->buildLimit();
        $sql .= $this->buildOffset();

        return $sql;
    }

    /**
     * Build out the DISTINCT section of the SELECT statement
     *
     */
    protected function buildDistinct(): string
    {
        $rtn = '';

        if ( $this->distinctFlag )
        {
            $rtn .= ' DISTINCT';

            if ( !empty($this->distinctExpression) > 0 )
            {
                $rtn .= ' ON ('.$this->distinctExpression.')';
            }
        }

        $rtn .= PHP_EOL;

        return $rtn;
    }

    /**
     * Build out the fields that will be returned.
     *
     * @return string
     */
    protected function buildFields(): string
    {
        $sql   = '';
        $delim = ','.PHP_EOL.$this->indent();

        if ( ! empty($this->fieldStack) )
        {
            $sql .= $this->indent();
            $sql .= implode($delim, $this->fieldStack).PHP_EOL;
        }
        else
        {
            $sql .= $this->indent().'*'.PHP_EOL;
        }

        return $sql;
    }

    /**
     * Build out the tables that go into the FROM clause
     *
     */
    protected function buildFrom(): string
    {
        $sql = '';
        $delim = ','.PHP_EOL.$this->indent();

        if ( ! empty($this->fromStack) )
        {
            $sql .= 'FROM'.PHP_EOL;
            $sql .= $this->indent();
            $sql .= implode($delim, $this->fromStack ).PHP_EOL;
        }

        return $sql;
    }

    /**
     * Build out the table joins that go into the FROM clause
     *
     */
    protected function buildJoins(): string
    {
        $sql = '';
        $delim = PHP_EOL.$this->indent();

        if ( ! empty($this->joinStack) )
        {
            $sql .= $this->indent();
            $sql .= implode($delim, $this->joinStack).PHP_EOL;
        }

        return $sql;
    }

    /**
     * Build out the WHERE clause
     *
     */
    protected function buildWhere(): string
    {
        $sql = '';
        $delimeter = PHP_EOL.$this->indent().'AND'.PHP_EOL.$this->indent();

        $clauses = [];

        foreach ( $this->whereStack as $whereClause )
        {
            $clauses[] = $whereClause->output();

            foreach ( $whereClause->getBindings() as $key => $value )
            {
                $this->setBinding($key, $value);
            }
        }

        if ( ! empty($clauses) )
        {
            $sql .= 'WHERE'.PHP_EOL;
            $sql .= $this->indent();
            $sql .= implode($delimeter, $clauses).PHP_EOL;
        }

        return $sql;
    }

    /**
     * Build out the GROUP BY clause
     *
     */
    protected function buildGroupBy(): string
    {
        $sql = '';
        $delim = ','.PHP_EOL.$this->indent();

        if ( ! empty($this->groupStack) )
        {
            $sql .= 'GROUP BY'.PHP_EOL;
            $sql .= $this->indent();
            $sql .= implode($delim, $this->groupStack ).PHP_EOL;
        }

        return $sql;
    }

    /**
     * Build out the HAVING clause
     *
     */
    protected function buildHaving(): string
    {
        $sql = '';
        $delimeter = PHP_EOL.$this->indent().'AND'.PHP_EOL.$this->indent();

        if ( ! empty($this->havingStack) )
        {
            $sql .= 'HAVING'.PHP_EOL;
            $sql .= $this->indent();
            $sql .= implode($delimeter, $this->havingStack ).PHP_EOL;
        }

        return $sql;
    }

    /**
     * Build out the ORDER BY clause
     *
     */
    protected function buildOrder(): string
    {
        $sql = '';
        $delim = ','.PHP_EOL.$this->indent();

        if ( ! empty($this->orderStack) )
        {
            $sql .= 'ORDER BY'.PHP_EOL;
            $sql .= $this->indent();
            $sql .= implode($delim, $this->orderStack ).PHP_EOL;
        }

        return $sql;
    }

    /**
     * Build out the LIMIT clause
     *
     */
    protected function buildLimit(): string
    {
        $sql = '';

        if ( isset($this->limitVal) )
        {
            $sql .= 'LIMIT '.$this->limitVal.PHP_EOL;
        }

        return $sql;
    }

    /**
     * Build out the OFFSET clause
     *
     */
    protected function buildOffset(): string
    {
        $sql = '';

        if ( isset($this->offsetVal) )
        {
            $sql .= 'OFFSET '.$this->offsetVal.PHP_EOL;
        }

        return $sql;
    }
}
