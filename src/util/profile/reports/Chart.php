<?php

namespace util\profile\reports;

use util\profile\Snapshot;
use util\profile\Report;

/**
 * outputs a report
 */
class Chart extends Report
{
    /**
     * @var Snapshot
     */
    protected $ns;

    /**
     * passed to google.visualization.*Chart.draw
     * @var array
     */
    protected $config = [
        'title' => 'Profiler',
        'google_jsapi' => 'https://www.google.com/jsapi',
        'template' => '',
        'chart_type' => 'LineChart',
        'curveType' => 'function',
        'titlePosition' => 'in',
        'axisTitlesPosition' => 'in',
        'elem_id' => 'profile_chart',
        'annotations' => [
            'style' => 'line',
        ],
        'legend' => [
            'position' => 'in',
        ],
        'chartArea' => [
            'width' => '90%',
            'height' => '50%',
        ],
        'hAxis' => [
            'title' => 'Time (ms)',
            'slantedTextAngle' => 90,
        ],
    ];

    /**
     * sets the template file (configuration item)
     */
    public function __construct()
    {
        $ds = DIRECTORY_SEPARATOR;
        $me = dirname(__FILE__);
        $this->config['template'] = "{$me}{$ds}Chart{$ds}template.html";
    }

    /**
     * @inheritdoc
     */
    public function prepare(Snapshot $snapshot)
    {
        $this->ns = & $snapshot;
        $max = $snapshot->maxmemory / 1024 / 1024;
        $avg = $snapshot->avgmemory / 1024 / 1024;
        $min = $snapshot->startmemory / 1024 / 1024;

        foreach ($snapshot->trace as $index => $trace) {
            extract($trace);

            $func = $class ? $class . $type . $function : $function;
            $file = str_replace([FABRICO_ROOT, FABRICO_PROJECT_ROOT],
                '', $file);
            $memory = (double) number_format(
                $trace['memory'] / 1024 / 1024, 4);
            $htime = !$index ? '0.00000000...' :
                (string) ($time - $snapshot->starttime);

            $this->data[] = [ $htime, $memory, implode("\r\n", [
                self::tsection('Tick', ++$index),
                self::tsection('Function', $func),
                self::tsection('File', $file),
                self::tsection('Line', $line),
                self::tsection('Memory', "{$memory} bytes"),
                self::tsection('Time', "{$time} ms"),
            ]), $max, $avg, $min ];
        }
    }

    /**
     * @inheritdoc
     */
    public function output()
    {
        return self::str_r($this->config['template'], [
            'data' => json_encode($this->data),
            'conf' => json_encode($this->config),
            'chart_type' => $this->config['chart_type'],
            'elem_id' => $this->config['elem_id'],
            'google_jsapi' => $this->config['google_jsapi'],
            'runtime_sec' => self::numberf($this->ns->runtime, 2),
            'maxmemory_mb' => self::numberf($this->ns->maxmemory / 1024 / 1024, 1),
        ]);
    }

    /**
     * format a number
     * @param numeric $num
     * @param int $dec
     * @return string
     */
    protected static function numberf($num, $dec = 0)
    {
        return preg_replace('/^0+|\.[0+]$/', '', number_format($num, $dec));
    }

    /**
     * generate tooltip text
     * @param string $name
     * @param string $name
     * @return string
     */
    protected static function tsection($name, $data)
    {
        return "{$name}: {$data}";
    }
}
