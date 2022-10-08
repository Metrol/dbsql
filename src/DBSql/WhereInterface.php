<?php
/**
 * @author        Michael Collette <mcollette@meetingevolution.net>
 * @version       1.0
 * @package       Metrol/DBSql
 * @copyright (c) 2016, Michael Collette
 */

namespace Metrol\DBSql;

/**
 * Describes objects that provide where clauses
 *
 */
interface WhereInterface
{
    /**
     * Produce the text of the clause
     *
     */
    public function output(): string;

    /**
     * Fetch any bindings that may have been used
     *
     */
    public function getBindings(): array;
}
