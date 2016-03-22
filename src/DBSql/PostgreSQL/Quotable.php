<?php
/**
 * @author        "Michael Collette" <metrol@metrol.net>
 * @package       Metrol/DBSql
 * @version       1.0
 * @copyright (c) 2016, Michael Collette
 */

namespace Metrol\DBSql\PostgreSQL;

/**
 * Set of methods to attempt proper quoting of various SQL parts
 *
 */
class Quotable extends \Metrol\DBSql\Quotable
{
    const FIELD_OPEN  = '"';
    const FIELD_CLOSE = '"';
    const TABLE_OPEN  = '"';
    const TABLE_CLOSE = '"';

    /**
     * Instantiate the Quotable object
     *
     */
    public function __construct()
    {
        parent::__construct();

        $this->fieldOpenQuote  = self::FIELD_OPEN;
        $this->fieldCloseQuote = self::FIELD_CLOSE;
        $this->tableOpenQuote  = self::TABLE_OPEN;
        $this->tableCloseQuote = self::TABLE_CLOSE;

        $this->keywords[] = 'like';
        $this->keywords[] = 'ilike';
    }
}
