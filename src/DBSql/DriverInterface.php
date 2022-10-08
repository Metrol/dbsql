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
     */
    public function select(): SelectInterface;

    /**
     *
     */
    public function with(): WithInterface;

    /**
     *
     */
    public function union(): UnionInterface;

    /**
     *
     */
    public function insert(): InsertInterface;

    /**
     *
     */
    public function update(): UpdateInterface;

    /**
     *
     */
    public function delete(): DeleteInterface;
}
