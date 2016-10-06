<?php
/**
 * @author        "Michael Collette" <metrol@metrol.net>
 * @package       Metrol/DBSql
 * @version       1.0
 * @copyright (c) 2016, Michael Collette
 */

namespace Metrol\DBSql\MySQL;

use Metrol\DBSql\SelectInterface;
use Metrol\DBSql\StackTrait;
use Metrol\DBSql\BindingsTrait;
use Metrol\DBSql\IndentTrait;

/**
 * Creates an SQL statement for MySQL
 *
 */
class Select implements SelectInterface
{
    use StackTrait, BindingsTrait, QuoterTrait, IndentTrait, WhereTrait;

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
    public function __toString()
    {
        return $this->output().PHP_EOL;
    }

    /**
     * Produces the output of all the information that was set in the object.
     *
     * @return string Formatted SQL
     */
    public function output()
    {
        return $this->buildSQL();
    }

    /**
     * Set the DISTINCT flag on the Select statement
     *
     * @param boolean $flag       Setting to true turns on DISTINCT.
     * @param string  $expression Comma Separated fields to be distinct about
     *
     * @return $this
     */
    public function distinct($flag, $expression = '')
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
     * @param string $fieldName  Must be quoted correctly for the database
     *
     * @return $this
     */
    public function field($fieldName)
    {
        $fieldString = $this->quoter()->quoteField($fieldName);

        $this->fieldStack[] = $fieldString;

        return $this;
    }

    /**
     * Sets the fields going to the select request.
     * Replaces any fields already set.
     *
     * @param array $fieldNames
     *
     * @return $this
     */
    public function fields(array $fieldNames)
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
     * @return $this
     */
    public function from($fromName)
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
     * @return $this
     */
    public function fromSub($alias, SelectInterface $subSelect)
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
     * Adds an INNER JOIN clause to the SELECT statement.
     *
     * @param string $tableName
     * @param string $onCriteria ON criteria for the JOIN.
     * @param array  $bindValues List of values to bind into the criteria
     *
     * @return $this
     */
    public function join($tableName, $onCriteria, array $bindValues = null)
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
     * @return $this
     */
    public function joinUsing($tableName, $criteria)
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
     * @return $this
     */
    public function joinNatural($tableName)
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
     * @return $this
     */
    public function joinOuter($joinType, $tableName, $onCriteria,
                              array $bindValues = null)
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
     * @return $this
     */
    public function joinOuterUsing($joinType, $tableName, $criteria)
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
     * @return $this
     */
    public function order($fieldName, $direction = null, $nullOrder = null)
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
     * @return $this
     */
    public function groupBy($fieldName)
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
     * @return $this
     */
    public function groupByFields(array $fieldNames)
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
     * @param string $criteria   Criteria for an aggregate
     * @param array  $bindValues
     *
     * @return $this
     */
    public function having($criteria, array $bindValues = null)
    {
        $havingClause = $this->bindAssign($criteria, $bindValues);

        $this->havingStack[] = $havingClause;

        return $this;
    }

    /**
     * Sets the limit for how many rows to pull back from the query.
     *
     * @param int $rowCount
     *
     * @return $this
     */
    public function limit($rowCount)
    {
        $this->limitVal = intval($rowCount);

        return $this;
    }

    /**
     * Sets the offset for which row to start with on the result set from the
     * query.
     *
     * @param int $startRow
     *
     * @return $this
     */
    public function offset($startRow)
    {
        $this->offsetVal = intval($startRow);

        return $this;
    }

    /**
     * Builds the SQL statement for the output by delegating out most of the
     * work to helper methods, then returns resulting string.
     *
     * @return string
     */
    protected function buildSQL()
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
    protected function buildDistinct()
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
    protected function buildFields()
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
    protected function buildFrom()
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
    protected function buildJoins()
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
    protected function buildWhere()
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
    protected function buildOrder()
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
    protected function buildLimit()
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
    protected function buildOffset()
    {
        $sql = '';

        if ( $this->offsetVal !== null )
        {
            $sql .= 'OFFSET '.$this->offsetVal.PHP_EOL;
        }

        return $sql;
    }
}
