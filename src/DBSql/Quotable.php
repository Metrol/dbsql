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
     */
    protected string $fieldOpenQuote;

    /**
     * The character used to close a field quote
     *
     */
    protected string $fieldCloseQuote;

    /**
     * The character used to open a table quote
     *
     */
    protected string $tableOpenQuote;

    /**
     * The character used to close a table quote
     *
     */
    protected string $tableCloseQuote;

    /**
     * List of symbols that should never be quoted.
     *
     */
    protected array $symbols;

    /**
     * List of keywords that should never be quoted.
     *
     */
    protected array $keywords;

    /**
     * When set to false, the methods used for putting quotes around items will
     * only just return what was passed in.
     *
     */
    protected bool $quoteOn;

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
     */
    public function enableQuoting(bool $flag): static
    {
        $this->quoteOn = $flag;

        return $this;
    }

    /**
     * Checks to see if quoting is enabled or not
     *
     */
    public function isEnabled(): bool
    {
        return $this->quoteOn;
    }

    /**
     * Quote a field string
     *
     */
    public function quoteField(string $field): string
    {
        // No need to go further if quoting has been turned off
        if ( ! $this->quoteOn )
        {
            return $field;
        }

        // Not going to even try if there are quotes already in this string
        if ( str_contains($field, $this->fieldOpenQuote) )
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

            // No quotes for stand-alone numbers
            if ( is_numeric($part) )
            {
                continue;
            }

            if ( str_contains($part, ':') )
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

        return implode(' ', $parts);
    }

    /**
     * Quote a table string
     *
     */
    public function quoteTable(string $table): string
    {
        // No need to go further if quoting has been turned off
        if ( ! $this->quoteOn )
        {
            return $table;
        }

        // Not going to even try if there are quotes already in this string
        if ( str_contains($table, $this->tableOpenQuote) )
        {
            return $table;
        }

        $parts = explode(' ', $table);

        foreach ( $parts as $i => $part )
        {
            if ( str_contains($part, ':') )
            {
                // Looks like a binding, just move on.
                continue;
            }

            if ( $part === strtolower($part) )
            {
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

        return implode(' ', $parts);
    }

    /**
     * Takes the input text and wrap field quotes around it.
     *
     */
    protected function fieldWrapWord(string $in): string
    {
        $out = $this->fieldOpenQuote;
        $out .= $in;
        $out .= $this->fieldCloseQuote;

        return $out;
    }

    /**
     * Takes the input text and wrap table quotes around it.
     *
     */
    protected function tableWrapWord(string $in): string
    {
        if ( $in == strtolower($in) )
        {
            return $in;
        }

        $out = $this->tableOpenQuote;
        $out .= $in;
        $out .= $this->tableCloseQuote;

        return $out;
    }

    /**
     * Quotes around dot "." notation to deal with schema or alias information
     * properly
     *
     */
    protected function dotFieldQuote(string $in): string
    {
        $out = $in;

        $oq = $this->fieldOpenQuote;
        $cq = $this->fieldCloseQuote;
        $parts = explode('.', $in);

        if ( isset($parts[0]) )
        {
            if ( $parts[0] !== strtolower($parts[0]) )
            {
                $out = preg_replace('/(\w+)\./', $oq.'$1'.$cq.'.', $out); // word.

                if ( str_contains($parts[0], '(') )
                {
                    $out = preg_replace('/\((\w+)/', '('.$oq.'$1'.$cq, $out); // (word
                    $out = preg_replace('/(\w+)\(/', $oq.'$1'.$cq.'(', $out); // word(
                }
            }
        }

        if ( isset($parts[1]) )
        {
            if ( $parts[1] !== strtolower($parts[1]) )
            {
                $out = preg_replace('/\.(\w+)/', '.'.$oq.'$1'.$cq, $out); // .word

                if ( str_contains($parts[1], '(') )
                {
                    $out = preg_replace('/\((\w+)/', '('.$oq.'$1'.$cq, $out); // (word
                    $out = preg_replace('/(\w+)\(/', $oq.'$1'.$cq.'(', $out); // word(
                }
            }
        }

        if ( $out !== strtolower($out) )
        {
            $out = preg_replace('/(\w+)\.(\w+)/',
                                $oq . '$1' . $cq . '.' . $oq . '$2' . $cq,
                                $out); // word.word
        }

        return $out;
    }

    /**
     * Handles getting quotes around a string that has parentheses involved.
     *
     */
    protected function parenthesesFieldQuote(string $in): string
    {
        $out = $in;

        $oq = $this->fieldOpenQuote;
        $cq = $this->fieldCloseQuote;

        $out = preg_replace('/\((\w+)/', '('.$oq.'$1'.$cq, $out);

        return preg_replace('/(\w+)\(/', $oq.'$1'.$cq.'(', $out);
    }

    /**
     * Quotes around dot "." notation to deal with schema or alias information
     * properly
     *
     */
    protected function dotTableQuote(string $in): string
    {
        $parts = explode('.', $in);

        foreach ( $parts as $i => $part )
        {
            if ( $part === strtolower($part) )
            {
                continue;
            }

            if ( strpos($part, '(') )
            {
                $parts[ $i ] = $this->parenthesesTableQuote($part);
            }
            else
            {
                $parts[ $i ] = $this->tableWrapWord($part);
            }
        }

        return implode('.', $parts);
    }

    /**
     * Handles getting quotes around a string that has parentheses involved.
     *
     */
    protected function parenthesesTableQuote(string $in): string
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
