<?php

namespace util\profile;

/**
 * profiler helper class
 * events include:
 *  - start: Profiler is first started
 *  - tick: PHP tick
 *  - complete: PHP shutting down
 */
class Profiler
{
    /**
     * tracks basic information
     */
    const LIGHT = 1;

    /**
     * saved information on every tick
     */
    const HEAVY = 2;

    /**
     * location in backtrace
     */
    protected $loc = 0;

    /**
     * tick counter
     * @var int
     */
    protected $ticks = 0;

    /**
     * debug trace with some additional information
     * @var array
     */
    protected $trace = [];

    /**
     * time when Profiler::start was called
     * @var int
     */
    protected $starttime;

    /**
     * memory at when Profiler::start was called
     * @var int
     */
    protected $startmemory;

    /**
     * time when Profiler::complete was called
     * @var int
     */
    protected $endtime;

    /**
     * memory at when Profiler::complete was called
     * @var int
     */
    protected $endmemory;

    /**
     * event handlers
     */
    protected $events = [];

    /**
     * @var string
     */
    protected $name;

    /**
     * @var int
     */
    protected $mode;

    /**
     * @param int $name
     * @param int $mode
     */
    public function __construct($name, $mode)
    {
        $this->name = $name;
        $this->mode = $mode;
    }

    /**
     * initializes profiler
     */
    public function start()
    {
        $this->startmemory = memory_get_usage();
        $this->starttime = microtime(true);

        switch ($this->mode) {
            case self::HEAVY:
                register_tick_function([ $this, 'tick' ]);
                register_shutdown_function([ $this, 'complete' ]);
                declare(ticks=1);
                break;

            case self::LIGHT:
                register_shutdown_function([ $this, 'complete' ]);
                break;
        }

        $this->trigger('init');
    }

    /**
     * saves tick information
     */
    public function tick()
    {
        $trace = array_reverse(debug_backtrace());
        $count = count($trace);
        $this->ticks++;

        for (;$this->loc < $count; $this->loc++) {
            $this->trace[] = self::parseinfo($trace[ $this->loc ]);
        }

        $this->trigger('tick');
    }

    /**
     * outputs profiling information
     */
    public function complete()
    {
        $this->endmemory = memory_get_usage();
        $this->endtime = microtime(true);
        $this->trigger('complete');
    }

    /**
     * report information
     * @return array
     */
    public function report()
    {
        $now = microtime(true);
        $mem = memory_get_usage();
        $max = memory_get_peak_usage();
        $avg = array_sum(array_map(function($trace) {
            return $trace['memory'];
        }, $this->trace)) / count($this->trace);

        $snapshot = new Snapshot;

        $snapshot->name = $this->name;
        $snapshot->mode = $this->mode;
        $snapshot->maxmemory = $max;
        $snapshot->avgmemory = $avg;
        $snapshot->currentmemory = $mem;
        $snapshot->currenttime = $now;
        $snapshot->starttime = $this->starttime;
        $snapshot->startmemory = $this->startmemory;
        $snapshot->endtime = $this->endtime;
        $snapshot->endmemory = $this->endmemory;
        $snapshot->runtime = $now - $this->starttime;
        $snapshot->trace = $this->trace;

        return $snapshot;
    }

    /**
     * on complete event listener
     * @param string $event
     * @param callback $action
     */
    public function on($event, $action)
    {
        if (!isset($this->events[ $event ])) {
            $this->events[ $event ] = [];
        }

        $this->events[ $event ][] = $action;
    }

    /**
     * trigger an event. passes a report array as the only  argument
     * @param string $event
     */
    protected function trigger($event)
    {
        if (isset($this->events[ $event ])) {
            foreach ($this->events[ $event ] as $action) {
                call_user_func($action, $this->report());
            }
        }
    }

    /**
     * array index getter
     * @param array $arr
     * @param string $pro
     * @return mixed
     */
    protected static function get(array & $arr, $prop)
    {
        return isset($arr[ $prop ]) ? $arr[ $prop ] : null;
    }

    /**
     * time and memory usage information
     * @param array $trace
     * @return array
     */
    public static function parseinfo(array & $trace)
    {
        return [
            'file' => self::get($trace, 'file'),
            'line' => self::get($trace, 'line'),
            'function' => self::get($trace, 'function'),
            'class' => self::get($trace, 'class'),
            'type' => self::get($trace, 'type'),
            'memory' => memory_get_usage(),
            'time' => microtime(true),
        ];
    }
}
