<?php
/**
 * @author        "Michael Collette" <metrol@metrol.net>
 * @package       Metrol/DBSql
 * @version       1.0
 * @copyright (c) 2016, Michael Collette
 */

namespace Metrol\DBSql\MySQL;

use Metrol\DBSql\Bindings;
use Metrol\DBSql\Indent;
use Metrol\DBSql\UnionInterface;
use Metrol\DBSql\SelectInterface;

/**
 * Creates a collection of SELECT statements combined with UNION's
 *
 */
class Union implements UnionInterface
{
    use Bindings, Indent, Quoter;

    /**
     * MySQL uses a DISTINCT union by default.
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
     * Produces the output of all the information that was set in the object.
     *
     * @return string Formatted SQL
     */
    public function output()
    {
        return $this->buildSQL();
    }

    /**
     * Adds a select statement to the stack
     *
     * @param Select $select
     * @param string $unionType Ignored for the first Select, then applied to
     *                          other statements as they are added.
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
        $this->mergeBindings($select);

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
            $type   = $selectUnion[0];
            $select = $selectUnion[1];

            if ( !empty($type) )
            {
                $sql .= $type.PHP_EOL.PHP_EOL;
            }

            $sql .= $select->output().PHP_EOL;
        }

        return $sql;
    }
}
