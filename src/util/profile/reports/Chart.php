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
     * includes chart element holder a javascript code to generate it
     * @var string
     */
    protected static $template = <<<HTML
<div id="profile_chart"></div>
<script type="text/javascript" src="https://www.google.com/jsapi"></script>
<script type="text/javascript">
google.load("visualization", "1", {packages:["corechart"]});
google.setOnLoadCallback(function() {
    var data, chart, elem;

    // data = google.visualization.arrayToDataTable(%data);
    data = new google.visualization.DataTable;
    data.addColumn('string', 'Time (ms)');
    data.addColumn('number', 'Memory (MB)');
    data.addColumn('number', 'Max');
    data.addColumn('number', 'Avg');
    data.addColumn('number', 'Min');
    data.addColumn({type: 'string', role: 'tooltip'});
    data.addRows(%data);

    elem = document.getElementById("profile_chart");
    chart = new google.visualization.%chart_type(elem);
    chart.draw(data, %conf);
});
</script>
HTML;

    /**
     * passed to google.visualization.*Chart.draw
     * @var array
     */
    protected $config = [
        'title' => 'Profiler',
        'chart_type' => 'LineChart',
        'height' => 500,
        'curveType' => 'function',
        'titlePosition' => 'in',
        'axisTitlesPosition' => 'in',
        'annotations' => [
            'style' => 'line',
        ],
        'legend' => [
            'position' => 'in',
        ],
        'chartArea' => [
            'width' => '60%',
        ],
        'hAxis' => [
            'title' => 'Time (ms)',
            'slantedTextAngle' => 90,
        ],
    ];

    /**
     * @inheritdoc
     */
    public function prepare(Snapshot $snapshot)
    {
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

            $this->data[] = [ $htime, $memory, $max, $avg, $min, implode("\r\n", [
                self::tsection('Tick', ++$index),
                self::tsection('Function', $func),
                self::tsection('File', $file),
                self::tsection('Line', $line),
                self::tsection('Memory', "{$memory} bytes"),
                self::tsection('Time', "{$time} ms"),
            ]) ];
        }
    }

    /**
     * @inheritdoc
     */
    public function output()
    {
        echo str_replace([
            '%data',
            '%conf',
            '%chart_type',
        ], [
            json_encode($this->data),
            json_encode($this->config),
            $this->config['chart_type'],
        ], self::$template);
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
