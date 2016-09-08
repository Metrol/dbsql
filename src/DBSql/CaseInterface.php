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
     * @param string $criteria
     * @param array  $bindValues
     *
     * @return WhenInterface
     */
    public function when($criteria, array $bindValues = null);

    /**
     * The final fall through if none of the WHEN cases match.
     *
     * @param string $elseResult
     * @param array  $bindValues
     *
     * @return CaseInterface
     */
    public function elseThen($elseResult, array $bindValues = null);

    /**
     * Assembles the CASE statement, pushes it onto the Select object.  The
     * alias is ignored when used in a WHERE clause.
     *
     * @param string $alias
     *
     * @return SelectInterface
     */
    public function endCase($alias = null);

}
