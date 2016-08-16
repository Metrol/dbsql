<?php
/**
 * @author        "Michael Collette" <metrol@metrol.net>
 * @package       Metrol/DBSql
 * @version       1.0
 * @copyright (c) 2016, Michael Collette
 */

namespace Metrol\DBSql\PostgreSQL;

use Metrol\DBSql\BindingsTrait;
use Metrol\DBSql\IndentTrait;
use Metrol\DBSql\UnionInterface;
use Metrol\DBSql\SelectInterface;
use Metrol\DBSql\OutputTrait;

/**
 * Creates a collection of SELECT statements combined with UNION's
 *
 */
class Union implements UnionInterface
{
    use OutputTrait, BindingsTrait, IndentTrait, QuoterTrait;

    /**
     * PostgreSQL uses a DISTINCT union by default.
     *
     * @const
     */
    const DEFAULT_UNION = self::UNION_DISTINCT;

    /**
     * The collection of Select Statements and Union types
     *
     * @var array
     */
    protected $unionStack;

    /**
     * Instantiate and initialize the object
     *
     */
    public function __construct()
    {
        $this->initBindings();
        $this->initIndent();

        $this->unionStack = array();
    }

    /**
     * Just a fast way to call the output() method
     *
     * @return string
     */
    public function __toString()
    {
        return $this->output().PHP_EOL;
    }

    /**
     * Adds a select statement to the stack
     *
     * @param SelectInterface $select
     * @param string          $unionType Ignored for the first Select, then
     *                                   applied to other statements as they
     *                                   are added.
     *
     * @return $this
     */
    public function setSelect(SelectInterface $select, $unionType = null)
    {
        $ut = '';

        if ( !empty($this->unionStack) )
        {
            if ( strtoupper($unionType) == self::UNION_ALL )
            {
                $ut = 'UNION '.self::UNION_ALL;
            }
            else if ( strtoupper($unionType) == self::UNION_DISTINCT )
            {
                $ut = 'UNION '.self::UNION_DISTINCT;
            }
            else if ( $unionType === null )
            {
                $ut = 'UNION '.self::DEFAULT_UNION;
            }
            else
            {
                return $this;
            }
        }

        $this->unionStack[] = [$ut, $select];

        return $this;
    }

    /**
     * Build out the SQL and gather all the bindings to be ready to push to PDO
     *
     * @return string
     */
    protected function buildSQL()
    {
        // Takes two to tango in this rodeo
        if ( count($this->unionStack) < 2 )
        {
            return '';
        }

        $sql = '';

        foreach ( $this->unionStack as $selectUnion )
        {
            /**
             * @var string          $type
             * @var SelectInterface $select
             */
            $type   = $selectUnion[0];
            $select = $selectUnion[1];

            if ( !empty($type) )
            {
                $sql .= PHP_EOL.$type.PHP_EOL.PHP_EOL;
            }

            $sql .= $select->output();

            $this->mergeBindings($select);
        }

        return $sql;
    }
}
