<?php
/**
 * @author        Michael Collette <mcollette@meetingevolution.net>
 * @version       1.0
 * @package       Metrol/DBSql
 * @copyright (c) 2016, Michael Collette
 */

namespace Metrol\DBSql;

/**
 * Describes objects that provide FROM clauses
 *
 */
interface FromInterface
{
    /**
     * Produce the text of the clause
     *
     * @return string
     */
    public function output();

    /**
     * Fetch any bindings that may have been used
     *
     * @return array
     */
    public function getBindings();
}
