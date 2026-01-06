<?php
/**
 * @author        "Michael Collette" <metrol@metrol.net>
 * @package       Metrol/DBSql
 * @version       1.0
 * @copyright (c) 2016, Michael Collette
 */

namespace Metrol\DBSql\MySQL;

use Metrol\DBSql\{BindingsTrait, IndentTrait, UnionInterface, SelectInterface};

/**
 * Creates a collection of SELECT statements combined with UNION's
 *
 */
class Union implements UnionInterface
{
    use BindingsTrait, IndentTrait, QuoterTrait;

    /**
     * MySQL uses a DISTINCT union by default.
     *
     * @const
     */
    const string DEFAULT_UNION = self::UNION_DISTINCT;

    /**
     * The collection of Select Statements and Union types
     *
     */
    protected array $unionStack = [];

    /**
     * Instantiate and initialize the object
     *
     */
    public function __construct()
    {
        $this->initBindings();
        $this->initIndent();
    }

    /**
     * Just a fast way to call the output() method
     *
     */
    public function __toString(): string
    {
        return $this->output().PHP_EOL;
    }

    /**
     * Produces the output of all the information that was set in the object.
     *
     */
    public function output(): string
    {
        return $this->buildSQL();
    }

    /**
     * Adds a select statement to the stack
     *
     */
    public function setSelect(SelectInterface $select, ?string $unionType = null): static
    {
        $ut = '';

        if ( ! empty($this->unionStack) )
        {
            if ( strtoupper($unionType) == self::UNION_ALL )
            {
                $ut = 'UNION '. self::UNION_ALL;
            }
            else if ( strtoupper($unionType) == self::UNION_DISTINCT )
            {
                $ut = 'UNION '. self::UNION_DISTINCT;
            }
            else if ( $unionType === null )
            {
                $ut = 'UNION '. self::DEFAULT_UNION;
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
     */
    protected function buildSQL(): string
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

            if ( ! empty($type) )
            {
                $sql .= $type.PHP_EOL.PHP_EOL;
            }

            $sql .= $select->output().PHP_EOL;

            $this->mergeBindings($select);
        }

        return $sql;
    }
}
