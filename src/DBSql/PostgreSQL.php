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
class PostgreSQL
{
    /**
     *
     * @return SelectInterface
     */
    public function select()
    {
        return new PostgreSQL\Select;
    }

    /**
     *
     * @return PostgreSQL\With
     */
    public function with()
    {
        return new PostgreSQL\With;
    }

    /**
     *
     * @return PostgreSQL\Union
     */
    public function union()
    {
        return new PostgreSQL\Union;
    }

    /**
     *
     * @return PostgreSQL\Insert
     */
    public function insert()
    {
        return new PostgreSQL\Insert;
    }

    /**
     *
     * @return PostgreSQL\Update
     */
    public function update()
    {
        return new PostgreSQL\Update;
    }

    /**
     *
     * @return PostgreSQL\Delete
     */
    public function delete()
    {
        return new PostgreSQL\Delete;
    }
}
