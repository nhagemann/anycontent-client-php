<?php

namespace AnyContent\Filter;

use AnyContent\AnyContentClientException;
use AnyContent\Client\Record;
use AnyContent\Filter\Interfaces\Filter;
use AnyContent\Filter\Util\ParensParser;
use CMDL\Util;

class PropertyFilter implements Filter
{

    protected $term;


    public function __construct($query)
    {
        $term = $this->parseTerm($query);

        if ($term) {
            $this->term = $term;
        } else {
            throw new AnyContentClientException('Could not parse term ' . $query);
        }
    }


    public function match(Record $record)
    {

        $operator = $this->term['operator'];
        if ($this->term['property'] == '.id') {
            $recordValue =$record->getId();
        } else {
            $property = Util::generateValidIdentifier($this->term['property']);
            $recordValue = strtolower($record->getProperty($property));
        }
        $conditionValue = strtolower($this->term['value']);

        switch ($operator) {
            case '=':
                return ($recordValue == $conditionValue);
                break;
            case '>':
                return ($recordValue > $conditionValue);
                break;
            case '<':
                return ($recordValue < $conditionValue);
                break;
            case '>=':
                return ($recordValue >= $conditionValue);
                break;
            case '<=':
                return ($recordValue <= $conditionValue);
                break;
            case '!=':
                return ($recordValue != $conditionValue);
                break;
            case '*=':
                $p = strpos($recordValue, $conditionValue);
                if ($p !== false) {
                    return true;
                }
                break;
        }

        return false;

    }


    /**
     * http://stackoverflow.com/questions/4955433/php-multiple-delimiters-in-explode
     *
     * @param $query
     *
     * @return bool
     */
    protected function parseTerm($query)
    {
        $query = $this->escape($query);

        $match = preg_match("/([^>=|<=|!=|>|<|=|\*=)]*)(>=|<=|!=|>|<|=|\*=)(.*)/", $query, $matches);

        if ($match) {
            $term = array();
            $term['property'] = trim($matches[1]);
            $term['operator'] = trim($matches[2]);
            $term['value'] = $this->decode(trim(($matches[3])));

            return $term;
        }

        return false;
    }


    protected function escape($s)
    {
//        $s = str_replace('\\+', '&#43;', $s);
//        $s = str_replace('\\,', '&#44;', $s);
        //     $s = str_replace('\\=', '&#61;', $s);

        return $s;
    }


    protected function decode($s)
    {
//        $s = str_replace('&#43;', '+', $s);
//        $s = str_replace('&#44;', ',', $s);
        //       $s = str_replace('&#61;', '=', $s);

        // remove surrounding quotes
        if (substr($s, 0, 1) == '"') {

            $s = trim($s, '"');
        } else {

            $s = trim($s, "'");
        }

        return $s;
    }


    public function __toString()
    {
        if ($this->term) {
            return $this->term['property'] . ' ' . $this->term['operator'] . ' ' . $this->term['value'];
        } else {
            return '';
        }
    }
}