<?php

namespace util\profile;

/**
 * outputs a Snapshot object
 */
abstract class Report
{
    /**
     * prepared data
     * @var array
     */
    protected $data = [];

    /**
     * additional (optional) output configuration
     * @var array
     */
    protected $config = [];

    /**
     * convert a Snapshot into an output friendly format
     * @param Snapshot $snapshot
     */
    abstract public function prepare(Snapshot $snapshot);

    /**
     * should make final preparations to data they output it
     * ie. a js output/report would call json_encode on the prepared snapshot
     * @return string
     */
    abstract public function output();

    /**
     * updates $config
     * @param array $config
     */
    public function configure(array $config = array())
    {
        $this->config = array_merge($this->config, $config);
    }

    /**
     * merge mergefields
     * @param string
     * @param array $fields
     * @return string
     */
    protected static function str_r($str, array $fields)
    {
        if (is_file($str)) {
            $str = file_get_contents($str);
        }

        return str_replace(array_map(function($field) {
            return "{{{$field}}}";
        }, array_keys($fields)), $fields, $str);
    }
}
