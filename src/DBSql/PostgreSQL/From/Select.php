<?php
/**
 * @author        Michael Collette <metrol@metrol.net>
 * @version       1.0
 * @package       Metrol/DBSql
 * @copyright (c) 2016, Michael Collette
 */

namespace Metrol\DBSql\PostgreSQL\From;

use Metrol\DBSql\BindingsTrait;
use Metrol\DBSql\IndentTrait;
use Metrol\DBSql\PostgreSQL\QuoterTrait;
use Metrol\DBSql\SelectInterface;

/**
 * A From clause based on a sub query
 *
 */
class Select
{
    use BindingsTrait, QuoterTrait, IndentTrait;

    /**
     * The alias for the From clause
     *
     * @var string
     */
    private $alias;

    /**
     * A sub select that will act as the data source for the From clause
     *
     * @var SelectInterface
     */
    private $subSelect;

    /**
     * Instantiate the Select object
     *
     * @param $alias
     * @param SelectInterface $subSelect
     */
    public function __construct($alias, SelectInterface $subSelect)
    {
        $this->initBindings();

        $this->alias     = $alias;
        $this->subSelect = $subSelect;
    }

    /**
     * The output of the From Select
     *
     * @return string
     */
    public function output()
    {
        // Assemble the string
        $fromClause  = '('.PHP_EOL;
        $fromClause .= $this->indentStatement($this->subSelect, 2);
        $fromClause .= $this->indent().') ';
        $fromClause .= $this->quoter()->quoteField($this->alias);

        return $fromClause;
    }
}
