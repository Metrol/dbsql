<?php
/**
 * @author        "Michael Collette" <metrol@metrol.net>
 * @package       Metrol/DBSql
 * @version       1.0
 * @copyright (c) 2016, Michael Collette
 */

namespace Metrol\DBSql\PostgreSQL;

use Metrol\DBSql\SelectInterface;
use Metrol\DBSql\Stacks;
use Metrol\DBSql\Bindings;
use Metrol\DBSql\Indent;

/**
 * Creates an SQL statement for PostgreSQL
 *
 */
class Select implements SelectInterface
{
    use Stacks, Bindings, Quoter, Indent, Where;

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
     * Whether or not to use the DISTINCT keyword
     *
     * @var bool
     */
    protected $distinctFlag;

    /**
     * When the distinct flag is set, this value will populate the DISTINCT ON
     * expression.  When the flag is false, this value is ignored.
     *
     * @var string
     */
    protected $distinctExpression;

    /**
     * The Limit value for how many rows can be returned.
     *
     * @var integer
     */
    protected $limitVal;

    /**
     * The Offset value for where to start the result set from
     *
     * @var integer
     */
    protected $offsetVal;

    /**
     * Instantiate and initialize the object
     *
     */
    public function __construct()
    {
        $this->initStacks();
        $this->initBindings();
        $this->initIndent();

        $this->distinctFlag       = false;
        $this->distinctExpression = '';
        $this->limitVal           = null;
        $this->offsetVal          = null;
    }

    /**
     * Just a fast way to call the output() method
     *
     * @return string
     */
    public function __toString(): string
    {
        return $this->output().PHP_EOL;
    }

    /**
     * Produces the output of all the information that was set in the object.
     *
     * @return string Formatted SQL
     */
    public function output(): string
    {
        return $this->buildSQL();
    }

    /**
     * Set the DISTINCT flag on the Select statement
     *
     * @param boolean $flag       Setting to true turns on DISTINCT.
     * @param string  $expression Comma Separated fields to be distinct about
     *
     * @return self
     */
    public function distinct(bool $flag, string $expression = ''): self
    {
        $this->distinctFlag = $flag;

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
     * @param string $fieldName  Must be quoted correctly for the database
     *
     * @return self
     */
    public function field(string $fieldName): self
    {
        $fieldString = $this->quoter()->quoteField($fieldName);

        $this->fieldStack[] = $fieldString;

        return $this;
    }

    /**
     * Add a set of fields to the select request.
     *
     * @param array $fieldNames
     *
     * @return self
     */
    public function fields(array $fieldNames): self
    {
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
     * @return CaseField
     */
    public function caseField()
    {
        return new CaseField($this);
    }

    /**
     * Add a data source to the FROM clause of the query.
     *
     * @param string $fromName
     *
     * @return self
     */
    public function from(string $fromName): self
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
     * @param string          $alias
     * @param SelectInterface $subSelect
     *
     * @return self
     */
    public function fromSub(string $alias, SelectInterface $subSelect): self
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
     * @param array  $values   Can be a list of values, or a list of arrays of
     *                         values to form sets.  Sets should all have the same
     *                         number of elements with consistent types.
     * @param string $alias    An alias is required.  You can add field names for
     *                         sets of data here.
     * @param bool   $bindFlag When set to true, all the values are automatically
     *                         given bindings.  Otherwise, they are left alone.
     *
     * @return self
     */
    public function fromValues(array $values, string $alias,
                               bool $bindFlag = true): self
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
                if ( $bindFlag )
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
            reset($values);

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
     * @param string $tableName
     * @param string $onCriteria ON criteria for the JOIN.
     * @param array  $bindValues List of values to bind into the criteria
     *
     * @return self
     */
    public function join(string $tableName, string $onCriteria,
                         array $bindValues = null): self
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
     * Adds an INNER JOIN clause to the SELECT statement with USING as the the join
     * criteria.  No data binding is provided here.
     *
     * @param string $tableName
     * @param string $criteria   Field names for the USING clause
     *
     * @return self
     */
    public function joinUsing(string $tableName, string $criteria): self
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
     * @param string $tableName
     *
     * @return self
     */
    public function joinNatural(string $tableName): self
    {
        $tableName  = $this->quoter()->quoteTable($tableName);

        $join  = 'NATURAL JOIN ';
        $join .= $tableName;

        $this->joinStack[] = $join;

        return $this;
    }

    /**
     * Adds a LEFT/RIGHT OUTER JOIN clause to the SELECT statement.
     *
     * @param string $joinType   LEFT|RIGHT|FULL
     * @param string $tableName
     * @param string $onCriteria ON criteria for the JOIN.
     * @param array  $bindValues List of values to bind into the criteria
     *
     * @return self
     */
    public function joinOuter(string $joinType, string $tableName,
                              string $onCriteria,
                              array $bindValues = null): self
    {
        $joinType = strtoupper($joinType);

        if ( ! in_array($joinType, [self::LEFT, self::RIGHT, self::FULL]) )
        {
            return $this;
        }

        $tableName  = $this->quoter()->quoteTable($tableName);
        $onCriteria = $this->bindAssign($onCriteria, $bindValues);
        $onCriteria = $this->quoter()->quoteField($onCriteria);

        $join  = '';
        $join .= $joinType.' OUTER JOIN ';
        $join .= $tableName.PHP_EOL;
        $join .= $this->indent(2);
        $join .= 'ON ';
        $join .= $onCriteria;

        $this->joinStack[] = $join;

        return $this;
    }

    /**
     * Adds an OUTER JOIN clause to the SELECT statement with USING as the the join
     * criteria.  No data binding is provided here.
     *
     * @param string $joinType   LEFT|RIGHT|FULL
     * @param string $tableName
     * @param string $criteria   Field names for the USING clause
     *
     * @return self
     */
    public function joinOuterUsing(string $joinType, string $tableName,
                                   string $criteria): self
    {
        $tableName  = $this->quoter()->quoteTable($tableName);

        $joinType = strtoupper($joinType);

        if ( ! in_array($joinType, [self::LEFT, self::RIGHT, self::FULL]) )
        {
            return $this;
        }

        $join  = '';
        $join .= $joinType.' JOIN ';
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
     * @param string $fieldName
     * @param string $direction
     * @param string $nullOrder 'NULLS FIRST' | 'NULLS LAST' Defaults to LAST
     *
     * @return self
     */
    public function order(string $fieldName, string $direction = null,
                          string $nullOrder = null): self
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
     * @param string $fieldName
     *
     * @return self
     */
    public function groupBy($fieldName): self
    {
        $groupString = $this->quoter()->quoteField($fieldName);

        $this->groupStack[] = $groupString;

        return $this;
    }

    /**
     * Add a set of fields to the GROUP BY clause
     *
     * @param string[] $fieldNames
     *
     * @return self
     */
    public function groupByFields(array $fieldNames): self
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
     * Field names will not be quoted.  You must quote where needed yourself.
     *
     * @param string $criteria   Criteria for an aggregate
     *
     * @return self
     */
    public function having(string $criteria): self
    {
        $this->havingStack[] = $criteria;

        return $this;
    }

    /**
     * Sets the limit for how many rows to pull back from the query.
     *
     * @param int $rowCount
     *
     * @return self
     */
    public function limit(int $rowCount): self
    {
        $this->limitVal = $rowCount;

        return $this;
    }

    /**
     * Sets the offset for which row to start with on the result set from the
     * query.
     *
     * @param int $startRow
     *
     * @return self
     */
    public function offset(int $startRow): self
    {
        $this->offsetVal = $startRow;

        return $this;
    }

    /**
     * Builds the SQL statement for the output by delegating out most of the
     * work to helper methods, then returns resulting string.
     *
     * @return string
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
     * @return string
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
     * @return string
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
     * @return string
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
     * @return string
     */
    protected function buildWhere(): string
    {
        $sql = '';
        $delimeter = PHP_EOL.$this->indent().'AND'.PHP_EOL.$this->indent();

        if ( ! empty($this->whereStack) )
        {
            $sql .= 'WHERE'.PHP_EOL;
            $sql .= $this->indent();
            $sql .= implode($delimeter, $this->whereStack ).PHP_EOL;
        }

        return $sql;
    }

    /**
     * Build out the GROUP BY clause
     *
     * @return string
     */
    protected function buildGroupBy()
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
     * @return string
     */
    protected function buildHaving()
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
     * @return string
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
     * @return string
     */
    protected function buildLimit(): string
    {
        $sql = '';

        if ( $this->limitVal !== null )
        {
            $sql .= 'LIMIT '.$this->limitVal.PHP_EOL;
        }

        return $sql;
    }

    /**
     * Build out the OFFSET clause
     *
     * @return string
     */
    protected function buildOffset(): string
    {
        $sql = '';

        if ( $this->offsetVal !== null )
        {
            $sql .= 'OFFSET '.$this->offsetVal.PHP_EOL;
        }

        return $sql;
    }
}
