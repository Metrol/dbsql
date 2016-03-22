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
interface WhenInterface
{
    /**
     * Provide the output of this WHEN object all ready to be included into the
     * CASE statement
     *
     * @return string
     */
    public function output(): string;

    /**
     * Adds a WHEN statement to the stack and provides the WHEN object
     * to provide the stack.
     *
     * @param string $criteria
     * @param array  $bindValues
     */
    public function setCriteria(string $criteria, array $bindValues = null);

    /**
     * Attaches the THEN portion of the WHEN clause and provides back the CASE
     * that called this object.
     *
     * @param string $thenResult
     * @param array  $bindValues
     *
     * @return CaseInterface
     */
    public function then(string $thenResult, array $bindValues = null): CaseInterface;
}
