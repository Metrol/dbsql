<?php
/**
 * @author        "Michael Collette" <metrol@metrol.net>
 * @package       Metrol/DBSql
 * @version       1.0
 * @copyright (c) 2016, Michael Collette
 */

namespace Metrol\DBSql;

/**
 * Provide code indenting support for SQL statements
 *
 */
trait Indent
{
    /**
     * String of spaces used to indent the SQL
     *
     * @var string
     */
    protected $indent;

    /**
     * Initialize the indent spacer
     *
     */
    protected function initIndent()
    {
        $defaultIndent = 4;

        $this->indent = str_repeat(' ', $defaultIndent);
    }

    /**
     * Sets the number of spaces to indent the SQL
     *
     * @param int $spaces
     *
     * @return self
     */
    public function setIndent(int $spaces): self
    {
        $this->indent = str_repeat(' ', $spaces);

        return $this;
    }

    /**
     * Provide the indentation string to prefix text with.
     *
     * @param int $depth How many levels of indent deep to return
     *                   
     * @return string
     */
    protected function indent(int $depth = 1): string
    {
        return str_repeat($this->indent, $depth);
    }

    /**
     * Used to indent a multiline string
     *
     * @param string $text  String to indent each line by
     * @param int    $depth How far to indent
     *
     * @return string Indented text
     */
    protected function indentMultiline(string $text, int $depth): string
    {
        return preg_replace('/^/m', str_repeat($this->indent, $depth), $text);
    }

    /**
     * Takes in a statement and indents every line by the indent value times
     * the specified depth.
     *
     * @param StatementInterface $statement
     * @param int                $depth     How far to indent
     *
     * @return string Indented statement
     */
    protected function indentStatement(StatementInterface $statement, int $depth): string
    {
        return $this->indentMultiline($statement->output(), $depth);
    }
}
