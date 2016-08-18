<?php
/**
 * @author        "Michael Collette" <metrol@metrol.net>
 * @package       Metrol/DBSql
 * @version       1.0
 * @copyright (c) 2016, Michael Collette
 */

namespace Metrol\DBSql;

/**
 * Set of methods to attempt proper quoting of various SQL parts
 *
 */
class Quotable
{
    /**
     * The character used to open a field quote
     *
     * @var string
     */
    protected $fieldOpenQuote;

    /**
     * The character used to close a field quote
     *
     * @var string
     */
    protected $fieldCloseQuote;

    /**
     * The character used to open a table quote
     *
     * @var string
     */
    protected $tableOpenQuote;

    /**
     * The character used to close a table quote
     *
     * @var string
     */
    protected $tableCloseQuote;

    /**
     * List of symbols that should never be quoted.
     *
     * @var array
     */
    protected $symbols;

    /**
     * List of keywords that should never be quoted.
     *
     * @var array
     */
    protected $keywords;

    /**
     * When set to false, the methods used for putting quotes around items will
     * only just return what was passed in.
     *
     * @var bool
     */
    protected $quoteOn;

    /**
     * Instantiate the Quotable object
     *
     */
    public function __construct()
    {
        $this->quoteOn = true;

        $this->fieldOpenQuote  = '"';
        $this->fieldCloseQuote = '"';
        $this->tableOpenQuote  = '"';
        $this->tableCloseQuote = '"';

        $this->symbols = [
            '<', '<>', '>', '=', '+', '-', '*', '/', '(', ')', '<=', '>='
        ];

        $this->keywords = [
            'and', 'or', 'on', 'in', 'not', 'as', 'null', 'true', 'false',
            'case', 'when', 'then', 'end', 'like', 'between', 'is'
        ];
    }

    /**
     * Enable/disable quoting
     *
     * @param bool $flag
     *
     * @return $this
     */
    public function enableQuoting($flag)
    {
        $this->quoteOn = $flag;

        return $this;
    }

    /**
     * Checks to see if quoting is enabled or not
     *
     * @return boolean
     */
    public function isEnabled()
    {
        return $this->quoteOn;
    }

    /**
     * Quote a field string
     *
     * @param string $field
     *
     * @return string
     */
    public function quoteField($field)
    {
        // No need to go further if quoting has been turned off
        if ( ! $this->quoteOn )
        {
            return $field;
        }

        // Not going to even try if there are quotes already in this string
        if ( strpos($field, $this->fieldOpenQuote) !== false )
        {
            return $field;
        }

        $parts = explode(' ', $field);

        foreach ( $parts as $i => $part )
        {
            // No quotes for keywords
            if ( in_array(strtolower($part), $this->keywords) )
            {
                continue;
            }

            // No quotes for stand along numbers
            if ( is_numeric($part) )
            {
                continue;
            }

            if ( strpos($part, ':') !== false )
            {
                // Looks like a binding, just move on.
                continue;
            }

            if ( in_array($part, $this->symbols) )
            {
                // Never quote math symbols
                continue;
            }

            if ( strpos($part, '.') )
            {
                $parts[$i] = $this->dotFieldQuote($part);
            }
            else if ( strpos($part, '(') )
            {
                $parts[$i] = $this->parenthesesFieldQuote($part);
            }
            else
            {
                $parts[$i] = $this->fieldWrapWord($part);
            }
        }

        $out = implode(' ', $parts);

        return $out;
    }

    /**
     * Quote a table string
     *
     * @param string $table
     *
     * @return string
     */
    public function quoteTable($table)
    {
        // No need to go further if quoting has been turned off
        if ( ! $this->quoteOn )
        {
            return $table;
        }

        // Not going to even try if there are quotes already in this string
        if ( strpos($table, $this->tableOpenQuote) !== false )
        {
            return $table;
        }

        $parts = explode(' ', $table);

        foreach ( $parts as $i => $part )
        {
            if ( strpos($part, ':') !== false )
            {
                // Looks like a binding, just move on.
                continue;
            }

            if ( strpos($part, '.') )
            {
                $parts[$i] = $this->dotTableQuote($part);
            }
            else if ( strpos($part, '(') )
            {
                $parts[$i] = $this->parenthesesTableQuote($part);
            }
            else
            {
                $parts[$i] = $this->tableWrapWord($part);
            }
        }

        $out = implode(' ', $parts);

        return $out;
    }

    /**
     * Takes the input text and wrap field quotes around it.
     *
     * @param string $in
     *
     * @return string
     */
    protected function fieldWrapWord($in)
    {
        $out = $this->fieldOpenQuote;
        $out .= $in;
        $out .= $this->fieldCloseQuote;

        return $out;
    }

    /**
     * Takes the input text and wrap table quotes around it.
     *
     * @param string $in
     *
     * @return string
     */
    protected function tableWrapWord($in)
    {
        $out = $this->tableOpenQuote;
        $out .= $in;
        $out .= $this->tableCloseQuote;

        return $out;
    }

    /**
     * Quotes around dot "." notation to deal with schema or alias information
     * properly
     *
     * @param string $in
     *
     * @return string
     */
    protected function dotFieldQuote($in)
    {
        $out = $in;

        $oq = $this->fieldOpenQuote;
        $cq = $this->fieldCloseQuote;

        $out = preg_replace('/\((\w+)/', '('.$oq.'$1'.$cq, $out); // (word
        $out = preg_replace('/(\w+)\(/', $oq.'$1'.$cq.'(', $out); // word(
        $out = preg_replace('/(\w+)\.(\w+)/', $oq.'$1'.$cq.'.'.$oq.'$2'.$cq, $out); // word.word
        $out = preg_replace('/\.(\w+)/', '.'.$oq.'$1'.$cq, $out); // .word
        $out = preg_replace('/(\w+)\./', $oq.'$1'.$cq.'.', $out); // word.

        return $out;
    }

    /**
     * Handles getting quotes around a string that has parentheses involved.
     *
     * @param string $in
     *
     * @return string
     */
    protected function parenthesesFieldQuote($in)
    {
        $out = $in;

        $oq = $this->fieldOpenQuote;
        $cq = $this->fieldCloseQuote;

        $out = preg_replace('/\((\w+)/', '('.$oq.'$1'.$cq, $out);
        $out = preg_replace('/(\w+)\(/', $oq.'$1'.$cq.'(', $out);

        return $out;
    }

    /**
     * Quotes around dot "." notation to deal with schema or alias information
     * properly
     *
     * @param string $in
     *
     * @return string
     */
    protected function dotTableQuote($in)
    {
        $parts = explode('.', $in);

        foreach ( $parts as $i => $part )
        {
            if ( strpos($part, '(') )
            {
                $parts[ $i ] = $this->parenthesesTableQuote($part);
            }
            else
            {
                $parts[ $i ] = $this->tableWrapWord($part);
            }
        }

        $out = implode('.', $parts);

        return $out;
    }

    /**
     * Handles getting quotes around a string that has parentheses involved.
     *
     * @param string $in
     *
     * @return string
     */
    protected function parenthesesTableQuote($in)
    {
        $out = $in;

        $firstWord = substr($in, 0, strpos($in, '(') );

        if ( $firstWord !== strtolower($firstWord) )
        {
            $out = $this->tableOpenQuote;
            $out .= $firstWord;
            $out .= $this->tableCloseQuote;
            $out .= substr($in, strpos($in, '('));
        }

        return $out;
    }
}
