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
     * @var string
     */
    protected $rows = '';

    /**
     * passed to google.visualization.*Chart.draw
     * @var array
     */
    protected $config = [
        'title' => 'Profiler',
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
            'height' => '70%',
            'top' => '8',
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
        $max = self::mb($snapshot->maxmemory);
        $avg = self::mb($snapshot->avgmemory);
        $min = self::mb($snapshot->startmemory);

        foreach ($snapshot->trace as $index => $trace) {
            extract($trace);

            $i = $index + 1;
            $func = $class ? $class . $type . $function : $function;
            $memory = (double) number_format(
                self::mb($memory), 4);
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

            $fullfile = $file;
            $file = explode(DIRECTORY_SEPARATOR, $file);
            $file = array_pop($file);
            $this->rows .= <<<HTML
<tr>
    <td>{$i}</td>
    <td>{$func}</td>
    <td title="{$fullfile}">{$file}</td>
    <td>{$line}</td>
    <td>{$memory}</td>
    <td>{$time}</td>
</tr>
HTML;
        }
    }

    /**
     * @inheritdoc
     */
    public function output($return = false)
    {
        $output = self::str_r($this->config['template'], [
            'view' => $_SERVER['REQUEST_URI'],
            'autohide' => isset($_COOKIE['profiler_autoshow']) ?: 'autohide',
            'profiler_name' => $this->ns->name,
            'minmemory' => self::numberf(self::mb($this->ns->startmemory), 2) . ' MB',
            'maxmemory' => self::numberf(self::mb($this->ns->maxmemory), 2) . ' MB',
            'avgmemory' => self::numberf(self::mb($this->ns->avgmemory), 2) . ' MB',
            'runtime' => self::numberf($this->ns->runtime, 2) . ' ms',
            'rows' => $this->rows,
            'data' => json_encode($this->data),
            'conf' => json_encode($this->config),
            'chart_type' => $this->config['chart_type'],
            'elem_id' => $this->config['elem_id'],
            'runtime_sec' => self::numberf($this->ns->runtime, 2),
            'maxmemory_mb' => self::numberf(self::mb($this->ns->maxmemory), 1),
        ]);

        if (!$return) {
            echo $output;
            $output = null;
        }

        return $output;
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

    /**
     * kb => mb
     * @param number $n
     * @return double
     */
    protected static function mb($n)
    {
        return $n / 1024 / 1024;
    }
}
