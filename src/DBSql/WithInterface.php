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
     */
    public function setStatement(string $alias, StatementInterface $statement): static;

    /**
     * Sets the suffix of the SQL that is appended after the clauses of the
     * WITH statement.
     *
     */
    public function setSuffix(StatementInterface $statement): static;
}
