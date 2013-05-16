<?php

namespace util\profile;

/**
 * represents a profiler snapshot
 */
class Snapshot
{
    /**
     * @var string
     */
    public $name;

    /**
     * @var int
     */
    public $mode;

    /**
     * @var int
     */
    public $maxmemory;

    /**
     * @var int
     */
    public $avgmemory;

    /**
     * @var int
     */
    public $currentmemory;

    /**
     * @var double
     */
    public $currenttime;

    /**
     * @var double
     */
    public $starttime;

    /**
     * @var int
     */
    public $startmemory;

    /**
     * @var double
     */
    public $endtime;

    /**
     * @var int
     */
    public $endmemory;

    /**
     * @var double
     */
    public $runtime;

    /**
     * @var array
     */
    public $trace;
}
