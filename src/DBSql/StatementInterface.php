<?php
/**
 * @author        "Michael Collette" <metrol@metrol.net>
 * @package       Metrol/DBSql
 * @version       1.0
 * @copyright (c) 2016, Michael Collette
 */

namespace Metrol\DBSql;

/**
 * Every SQL statement will need to implement these methods.
 *
 */
interface StatementInterface
{
    /**
     * Produces the output of all the information that was set in the object.
     *
     */
    public function output(): string;

    /**
     * Initialize the binding values, and clears out any existing ones
     *
     */
    public function initBindings(): static;

    /**
     * Provide the list of all the bind values suitable for passing to a PDO
     * statement.
     *
     */
    public function getBindings(): array;

    /**
     * Set a value for a named binding that appeared somewhere in the SQL
     *
     */
    public function setBinding(string $binding, mixed $value): static;

    /**
     * Takes in an array of bindings and adds them to the stack
     *
     */
    public function setBindings(array $bindings): static;

    /**
     * Tell the quoter engine whether to try and automatically quote
     * field and table names.  When turned off, quoting is manual.
     *
     */
    public function enableQuoting(bool $flag): static;
}
