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
interface CaseFieldInterface extends CaseInterface
{
    /**
     * Assembles the CASE statement, pushes it onto the Select object, then
     * passes back the Select object to continue chaining the query.
     *
     */
    public function endCase(string $alias = null): SelectInterface;
}
