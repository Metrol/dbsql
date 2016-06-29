<?php
/**
 * @author        "Michael Collette" <metrol@metrol.net>
 * @package       Metrol/DBSql
 * @version       1.0
 * @copyright (c) 2016, Michael Collette
 */

namespace Metrol\DBSql;

/**
 * Defines the basic driver factories across all the SQL types
 *
 */
interface DriverInterface
{
    /**
     *
     * @return SelectInterface
     */
    public function select();

    /**
     *
     * @return WithInterface
     */
    public function with();

    /**
     *
     * @return UnionInterface
     */
    public function union();

    /**
     *
     * @return InsertInterface
     */
    public function insert();

    /**
     *
     * @return UpdateInterface
     */
    public function update();

    /**
     *
     * @return DeleteInterface
     */
    public function delete();
}
