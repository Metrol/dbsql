<?php
/**
 * @author        "Michael Collette" <metrol@metrol.net>
 * @package       Metrol/DBSql
 * @version       1.0
 * @copyright (c) 2016, Michael Collette
 */

namespace Metrol;

/**
 * Provides static methods used to bring in database specific SQL generators
 *
 */
class DBSql
{
    const POSTGRESQL     = 'PostgreSQL';
    const POSTGRESQL_PDO = 'pgsql';
    const MYSQL          = 'MySQL';
    const MYSQL_PDO      = 'pgsql';

    /**
     *
     * @return DBSql\PostgreSQL
     */
    static public function PostgreSQL()
    {
        return new DBSql\PostgreSQL;
    }

    /**
     * 
     * @return DBSql\MySQL
     */
    static public function MySQL()
    {
        return new DBSql\MySQL;
    }

    /**
     * Provides the same functionality as the database specific methods, but
     * allows for the value to be dynamic.
     *
     * @param $type
     *
     * @return object
     *
     * @throws \UnexpectedValueException
     */
    static public function getDriver($type)
    {
        $driver = null;

        switch ( strtoupper($type) )
        {
            case strtoupper(self::POSTGRESQL):
                $driver = self::PostgreSQL();
                break;

            case strtoupper(self::POSTGRESQL_PDO):
                $driver = self::PostgreSQL();
                break;

            case strtoupper(self::MYSQL):
                $driver = self::MySQL();
                break;

            case strtoupper(self::MYSQL_PDO):
                $driver = self::MySQL();
                break;

            default:
                $msg = 'Unknown database type requested';
                throw new \UnexpectedValueException($msg);
        }

        return $driver;
    }
}
