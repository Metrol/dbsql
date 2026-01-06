<?php
/**
 * @author        "Michael Collette" <metrol@metrol.net>
 * @package       Metrol/DBSql
 * @version       1.0
 * @copyright (c) 2016, Michael Collette
 */

namespace Metrol\DBSql\MySQL;

use Metrol\DBSql\{BindingsTrait, CaseInterface, IndentTrait, WhenInterface};

/**
 * Holds the When part of a Case/When statement
 *
 */
class When implements WhenInterface
{
    use BindingsTrait, IndentTrait, QuoterTrait;

    /**
     * The Case object that called this one into being.  Saved here to pass
     * back after the Then value is given.
     *
     */
    protected CaseInterface $caseObj;

    /**
     * The criteria string for this When
     *
     */
    protected string $criteria;

    /**
     * The result if the criteria is true
     *
     */
    protected string $thenResult;

    /**
     * Instantiate and initialize the object
     *
     */
    public function __construct(CaseInterface $caseObj)
    {
        $this->caseObj = $caseObj;

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
        return $this->buildSQL();
    }

    /**
     * Adds a WHEN statement to the stack and provides the WHEN object
     * to provide the stack.
     *
     */
    public function setCriteria(string $criteria, ?array $bindValues = null): void
    {
        $this->criteria = $this->bindAssign($criteria, $bindValues);
        $this->criteria = $this->quoter()->quoteField($this->criteria);
    }

    /**
     * Attaches the THEN portion of the WHEN clause and provides back the CASE
     * that called this object.
     *
     */
    public function then(string $thenResult, ?array $bindValues = null): CaseInterface
    {
        $this->thenResult = $this->bindAssign($thenResult, $bindValues);
        $this->thenResult = $this->quoter()->quoteField($this->thenResult);

        return $this->caseObj;
    }

    /**
     * Assembles the CASE statement for the Select statement
     *
     */
    protected function buildSQL(): string
    {
        $sql = 'WHEN ';
        $sql .= $this->criteria . ' THEN' . PHP_EOL;
        $sql .= $this->indent(3);
        $sql .= $this->thenResult;
        $sql .= PHP_EOL;

        return $sql;
    }
}
