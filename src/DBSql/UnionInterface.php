<?php
/**
 * @author        "Michael Collette" <metrol@metrol.net>
 * @package       Metrol/DBSql
 * @version       1.0
 * @copyright (c) 2016, Michael Collette
 */

namespace Metrol\DBSql;

/**
 * Define what is needed for a Union
 *
 */
interface UnionInterface extends StatementInterface
{
    /**
     * The kinds of Unions supported
     *
     * @const
     */
    const string UNION_ALL      = 'ALL';
    const string UNION_DISTINCT = 'DISTINCT';

    /**
     * Adds a select statement to the stack
     *
     * @param SelectInterface $select
     * @param string|null     $unionType Ignored for the first Select, then
     *                                   applied to other statements as they
     *                                   are added.
     *
     * @return $this
     */
    public function setSelect(SelectInterface $select, ?string $unionType = null): static;
}
