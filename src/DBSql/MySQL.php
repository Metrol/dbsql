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
class MySQL implements DriverInterface
{
    /**
     *
     * @return MySQL\Select
     */
    public function select(): MySQL\Select
    {
        return new MySQL\Select;
    }

    /**
     *
     * @return MySQL\With
     */
    public function with(): MySQL\With
    {
        return new MySQL\With;
    }

    /**
     *
     * @return MySQL\Union
     */
    public function union(): MySQL\Union
    {
        return new MySQL\Union;
    }

    /**
     *
     * @return MySQL\Insert
     */
    public function insert(): MySQL\Insert
    {
        return new MySQL\Insert;
    }

    /**
     *
     * @return MySQL\Update
     */
    public function update(): MySQL\Update
    {
        return new MySQL\Update;
    }

    /**
     *
     * @return MySQL\Delete
     */
    public function delete(): MySQL\Delete
    {
        return new MySQL\Delete;
    }
}
