<?php
/**
 * @author        "Michael Collette" <metrol@metrol.net>
 * @package       Metrol/DBSql
 * @version       1.0
 * @copyright (c) 2016, Michael Collette
 */

namespace Metrol\DBSql;

/**
 * Major statement types for PostgreSQL
 *
 */
class PostgreSQL implements DriverInterface
{
    /**
     *
     */
    public function select(): PostgreSQL\Select
    {
        return new PostgreSQL\Select;
    }

    /**
     *
     */
    public function with(): PostgreSQL\With
    {
        return new PostgreSQL\With;
    }

    /**
     *
     */
    public function union(): PostgreSQL\Union
    {
        return new PostgreSQL\Union;
    }

    /**
     *
     */
    public function insert(): PostgreSQL\Insert
    {
        return new PostgreSQL\Insert;
    }

    /**
     *
     */
    public function update(): PostgreSQL\Update
    {
        return new PostgreSQL\Update;
    }

    /**
     *
     */
    public function delete(): PostgreSQL\Delete
    {
        return new PostgreSQL\Delete;
    }
}
