<?php
/**
 * @author        "Michael Collette" <metrol@metrol.net>
 * @package       Metrol/DBSql
 * @version       1.0
 * @copyright (c) 2016, Michael Collette
 */

namespace Metrol\DBSql\MySQL;

use Metrol\DBSql\Bindings;
use Metrol\DBSql\CaseInterface;
use Metrol\DBSql\Indent;
use Metrol\DBSql\WhenInterface;

/**
 * Holds the When part of a Case/When statement
 *
 */
class When implements WhenInterface
{
    use Bindings, Indent, Quoter;

    /**
     * The Case object that called this one into being.  Saved here to pass
     * back after the Then value is given.
     *
     * @var CaseInterface
     */
    protected $caseObj;

    /**
     * The criteria string for this When
     *
     * @var string
     */
    protected $criteria;

    /**
     * The result if the criteria is true
     *
     * @var string
     */
    protected $thenResult;

    /**
     * Instantiate and initialize the object
     *
     * @param CaseInterface $caseObj
     */
    public function __construct(CaseInterface $caseObj)
    {
        $this->caseObj    = $caseObj;
        $this->criteria   = null;
        $this->thenResult = null;

        $this->initBindings();
        $this->initIndent();
    }

    /**
     * Provide the output of this WHEN object all ready to be included into the
     * CASE statement
     *
     * @return string
     */
    public function output(): string
    {
        $rtn = $this->buildSQL();

        return $rtn;
    }

    /**
     * Adds a WHEN statement to the stack and provides the WHEN object
     * to provide the stack.
     *
     * @param string $criteria
     * @param array  $bindValues
     */
    public function setCriteria(string $criteria, array $bindValues = null)
    {
        $this->criteria = $this->bindAssign($criteria, $bindValues);
        $this->criteria = $this->quoter()->quoteField($this->criteria);
    }

    /**
     * Attaches the THEN portion of the WHEN clause and provides back the CASE
     * that called this object.
     *
     * @param string $thenResult
     * @param array  $bindValues
     *
     * @return CaseInterface
     */
    public function then(string $thenResult, array $bindValues = null): CaseInterface
    {
        $this->thenResult = $this->bindAssign($thenResult, $bindValues);
        $this->thenResult = $this->quoter()->quoteField($this->thenResult);

        return $this->caseObj;
    }

    /**
     * Assembles the CASE statement for the Select statement
     *
     * @retrun string
     */
    protected function buildSQL(): string
    {
        $sql = 'WHEN ';
        $sql .= $this->criteria.' THEN'.PHP_EOL;
        $sql .= $this->indent(3);
        $sql .= $this->thenResult;
        $sql .= PHP_EOL;

        return $sql;
    }
}
