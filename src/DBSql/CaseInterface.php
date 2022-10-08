<?php
/**
 * @author        "Michael Collette" <metrol@metrol.net>
 * @package       Metrol/DBSql
 * @version       1.0
 * @copyright (c) 2016, Michael Collette
 */

namespace Metrol\DBSql;

/**
 * Define what the Case/When class should look like
 *
 */
interface CaseInterface extends StatementInterface
{
    /**
     * Adds a WHEN statement to the stack and provides the WHEN object
     * to provide the stack.
     *
     */
    public function when(string $criteria, array $bindValues = null): WhenInterface;

    /**
     * The final fall through if none of the WHEN cases match.
     *
     */
    public function elseThen(string $elseResult, array $bindValues = null): CaseInterface;

    /**
     * Assembles the CASE statement, pushes it onto the Select object.  The
     * alias is ignored when used in a WHERE clause.
     *
     */
    public function endCase(string $alias = null): SelectInterface;

}
