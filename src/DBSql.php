<?php
/**
 * @author        "Michael Collette" <metrol@metrol.net>
 * @package       Metrol/DBSql
 * @version       1.0
 * @copyright (c) 2016, Michael Collette
 */

namespace Metrol;

use Metrol\DBSql\{PostgreSQL, MySQL, DriverInterface};
use UnexpectedValueException;

/**
 * Provides static methods used to bring in database specific SQL generators
 *
 */
class DBSql
{
    const string POSTGRESQL     = 'PostgreSQL';
    const string POSTGRESQL_PDO = 'pgsql';
    const string MYSQL          = 'MySQL';
    const string MYSQL_PDO      = 'mysql';

    /**
     *
     */
    static public function PostgreSQL(): PostgreSQL
    {
        return new PostgreSQL;
    }

    /**
     *
     */
    static public function MySQL(): MySQL
    {
        return new MySQL;
    }

    /**
     * Provides the same functionality as the database specific methods, but
     * allows for the value to be dynamic.
     *
     * @throws UnexpectedValueException
     */
    static public function getDriver(string $type): DriverInterface
    {
        $driver = null;

        switch ( strtoupper($type) )
        {
            case strtoupper(self::POSTGRESQL_PDO):
            case strtoupper(self::POSTGRESQL):
                $driver = self::PostgreSQL();
                break;

            case strtoupper(self::MYSQL_PDO):
            case strtoupper(self::MYSQL):
                $driver = self::MySQL();
                break;

            default:
                $msg = 'Unknown database type requested';
                throw new UnexpectedValueException($msg);
        }

        return $driver;
    }
}
