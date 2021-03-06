<?php

namespace util\profile;

/**
 * profiler helper class
 * events include:
 *  - start: Profiler is first started
 *  - tick: PHP tick
 *  - stop: PHP shutting down
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
     * @var array
     */
    protected $uniq = [];

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
     * time when Profiler::stop was called
     * @var int
     */
    protected $endtime;

    /**
     * memory at when Profiler::stop was called
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
     * active/running flag
     * @var boolean
     */
    protected $running = false;

    /**
     * @param int $mode, default = Profiler::HEAVY
     * @param string $name, default = null
     */
    public function __construct($mode = self::LIGHT, $name = null)
    {
        $this->name = $name ?: date('Y/m/d H:i:s');
        $this->mode = $mode;
    }

    /**
     * initializes profiler
     */
    public function start()
    {
        $this->running = true;
        $this->startmemory = memory_get_usage();
        $this->starttime = microtime(true);

        register_tick_function([ $this, 'tick' ]);
        register_shutdown_function([ $this, 'stop' ]);
        declare(ticks=1);

        $this->trigger('start');
    }

    /**
     * saves tick information
     */
    public function tick()
    {
        $trace = array_reverse(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS));
        array_pop($trace); // this function
        $count = count($trace);
        $this->ticks++;

        switch ($this->mode) {
            case self::HEAVY:
                for ($i = 0; $i < $count; $i++) {
                    $hash = md5(serialize($trace[ $i ]));

                    if (!in_array($hash, $this->uniq)) {
                        $this->uniq[] = $hash;
                        $this->trace[] = self::parseinfo($trace[ $i ]);
                    }
                }

                break;

            case self::LIGHT:
                for (;$this->loc < $count; $this->loc++) {
                    $this->trace[] = self::parseinfo($trace[ $this->loc ]);
                }

                break;
        }

        $this->trigger('tick');
    }

    /**
     * outputs profiling information
     */
    public function stop()
    {
        if (!$this->running) {
            return;
        }

        $this->running = false;
        $this->endmemory = memory_get_usage();
        $this->endtime = microtime(true);

        unregister_tick_function([ $this, 'tick' ]);
        $this->trigger('stop');
    }

    /**
     * report information
     * @return Snapshot
     */
    public function report()
    {
        $now = microtime(true);
        $runtime = $now - $this->starttime;
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
        $snapshot->runtime = $runtime;
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
    protected static function parseinfo(array & $trace)
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

    /**
     * keys if profiling is enabled
     * @return boolean
     */
    public static function enabled()
    {
        $key = '__profile_on';
        return isset($_REQUEST[ $key ]) || isset($_ENV[ $key ]) ||
            getenv($key) !== false;
    }

    /**
     * args:
     * - int mode
     * - string name
     * - array conf
     * - string report
     * @param array $args
     * @return Profiler
     */
    public static function profile($args = array())
    {
        $mode = isset($args['mode']) ? $args['mode'] : self::LIGHT;
        $name = isset($args['name']) ? $args['name'] : null;
        $conf = isset($args['conf']) ? $args['conf'] : null;
        $report = isset($args['report']) ? $args['report'] : null;

        $profiler = new static($mode, $name);
        $profiler->start();
        $profiler->on('stop', function(Snapshot $snapshot) use ($conf, $report) {
            if ($report) {
                $report = new $report;
                $report->prepare($snapshot);

                if ($conf) {
                    $report->configure($conf);
                }

                $report->output();
            }
        });

        return $profiler;
    }
}
