<?php
/**
 * @author        "Michael Collette" <metrol@metrol.net>
 * @package       Metrol/DBSql
 * @version       1.0
 * @copyright (c) 2016, Michael Collette
 */

namespace Metrol\DBSql;

/**
 * Major statement types for MySQL
 *
 */
class MySQL
{
    /**
     *
     * @return MySQL\Select
     */
    public function select()
    {
        return new MySQL\Select;
    }

    /**
     *
     * @return MySQL\With
     */
    public function with()
    {
        return new MySQL\With;
    }

    /**
     *
     * @return MySQL\Union
     */
    public function union()
    {
        return new MySQL\Union;
    }

    /**
     *
     * @return MySQL\Insert
     */
    public function insert()
    {
        return new MySQL\Insert;
    }

    /**
     *
     * @return MySQL\Update
     */
    public function update()
    {
        return new MySQL\Update;
    }

    /**
     *
     * @return MySQL\Delete
     */
    public function delete()
    {
        return new MySQL\Delete;
    }
}
