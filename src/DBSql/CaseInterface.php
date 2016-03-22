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
interface CaseInterface
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
    public function when(string $criteria, array $bindValues = null): WhenInterface;

    /**
     * The final fall through if none of the WHEN cases match.
     *
     * @param string $elseResult
     * @param array  $bindValues
     *
     * @return CaseInterface
     */
    public function elseThen(string $elseResult, array $bindValues = null): CaseInterface;
}
