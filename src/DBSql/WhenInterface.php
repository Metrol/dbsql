<?php
/**
 * @author        "Michael Collette" <metrol@metrol.net>
 * @package       Metrol/DBSql
 * @version       1.0
 * @copyright (c) 2016, Michael Collette
 */

namespace Metrol\DBSql;

/**
 * Define what the When part of a Case/When class should look like
 *
 */
interface WhenInterface extends StatementInterface
{
    /**
     * Adds a WHEN statement to the stack and provides the WHEN object
     * to provide the stack.
     *
     * @param string $criteria
     * @param array  $bindValues
     */
    public function setCriteria($criteria, array $bindValues = null);

    /**
     * Attaches the THEN portion of the WHEN clause and provides back the CASE
     * that called this object.
     *
     * @param string $thenResult
     * @param array  $bindValues
     *
     * @return CaseInterface
     */
    public function then($thenResult, array $bindValues = null);
}
