<?php
/**
 * @author        "Michael Collette" <metrol@metrol.net>
 * @package       Metrol/DBSql
 * @version       1.0
 * @copyright (c) 2016, Michael Collette
 */

namespace Metrol\DBSql\PostgreSQL;

/**
 * Provides the functionality that statements and various helpers use from the
 * Quatoable object
 *
 */
trait QuoterTrait
{
    /**
     * The object that handles auto quoting field and table names.
     *
     * @var Quotable
     */
    protected $quotable;

    /**
     * Provide the quoting utility to use on Table and Field names
     *
     * @return Quotable
     */
    public function quoter()
    {
        if ( !is_object($this->quotable) )
        {
            $this->quotable = new Quotable;
        }

        return $this->quotable;
    }

    /**
     * Tell the quoter engine whether or not to try and automatically quote
     * field and table names.  When turned off, quoting is manual.
     *
     * @param bool $flag
     *
     * @return $this
     */
    public function enableQuoting(bool $flag)
    {
        $this->quoter()->enableQuoting($flag);

        return $this;
    }
}
