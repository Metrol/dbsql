<?php
/**
 * @author        "Michael Collette" <metrol@metrol.net>
 * @package       Metrol/DBSql
 * @version       1.0
 * @copyright (c) 2016, Michael Collette
 */

namespace Metrol\DBSql;

/**
 * Define the methods for a WITH statement
 *
 */
interface WithInterface extends StatementInterface
{
    /**
     * Adds a statement to the stack
     *
     * @param string             $alias
     * @param StatementInterface $statement
     *
     * @return $this
     */
    public function setStatement($alias, StatementInterface $statement);

    /**
     * Sets the suffix of the SQL that is appended after the clauses of the
     * WITH statement.
     *
     * @param StatementInterface $statement
     *
     * @return $this
     */
    public function setSuffix(StatementInterface $statement);
}
