<?php
/**
 * @author        Michael Collette <metrol@metrol.net>
 * @version       1.0
 * @package       Metrol/DBSql
 * @copyright (c) 2016, Michael Collette
 */

namespace Metrol\DBSql\PostgreSQL\From;

use Metrol\DBSql\BindingsTrait;
use Metrol\DBSql\FromInterface;
use Metrol\DBSql\PostgreSQL\QuoterTrait;

/**
 * A from clause with a Table name
 *
 */
class Table implements FromInterface
{
    use QuoterTrait, BindingsTrait;

    /**
     * The name of the table
     *
     * @var string
     */
    private $tableName;

    /**
     * Instantiate the Table object
     *
     * @param string $tableName
     */
    public function __construct($tableName)
    {
        $this->initBindings();
        $this->tableName = $tableName;
    }

    /**
     * Produce the Table From clause
     *
     * @return string
     */
    public function output()
    {
        return $this->quoter()->quoteTable($this->tableName);
    }
}
