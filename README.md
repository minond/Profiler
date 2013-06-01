Profiler
========

## Profiler object
Creating new profilers:
```php
use util\profile\Profiler;
use util\profile\Snapshot;

$profiler = new Profiler('AcmeAnvils');
```

Starting up and shutting down a profiler:
```php
$profiler->start();
$profiler->stop();
```

Listening to profiler events:
```php
// triggered by calling Profiler::start
$profiler->on('start', function(Snapshot $snapshot) {
  youroutput('profiling: %s', $snapshot->name);
});

// triggered on every PHP tick
$profiler->on('tick', function(Snapshot $snapshot) {
  youroutput('.');
});

// triggered by calling Profiler::stop
// you'll at least want to register to this event,
// otherwise the profiling data it worthless
$profiler->on('stop', function(Snapshot $snapshot) {
  youroutput('done profiling: %s', $snapshot->name);
  print_r($snapshot);
});
```

It is not necessary to call the <code>stop</code> method on a profiler, as it will register a shutdown function that will trigger a call to <code>stop</code>.

## Profiling
Sample of profiling code:
```php
use util\profile\Profiler;
use util\profile\Snapshot;

$profiler = new Profiler('AcmeAnvils');
$profiler->start();
$profiler->on('stop', function(Snapshot $snapshot) {
  print_r($snapshot);
});

// code you want to profile
// ...

$profiler->stop();
```
## Snapshot object
Sample <code>Snapshot</code>:
```php
object(util\profile\Snapshot)[33]
  public 'name' => string 'AcmeAnvils' (length=10)
  public 'mode' => int 2
  public 'maxmemory' => int 1135472
  public 'avgmemory' => float 1128570.4
  public 'currentmemory' => int 1128568
  public 'currenttime' => float 1368675239.0269
  public 'starttime' => float 1368675239.0263
  public 'startmemory' => int 1122604
  public 'endtime' => float 1368675239.0269
  public 'endmemory' => int 1128520
  public 'runtime' => float 0.00060391426086426
  public 'trace' =>
    array (size=5)
      0 =>
        array (size=7)
          'file' => string 'file.php' (length=53)
          'line' => int 10
          'function' => string 'function_name' (length=14)
          'class' => null
          'type' => null
          'memory' => int 1126948
          'time' => float 1368675239.0264
```
### Reports
Reports are used to output Snapshot objects in a human/environment friendly format:
```php
use util\profile\reports\Chart;

$chart = new Chart;
$chart->prepare($snapshot);
$chart->configure(['chart_type' => 'LineChart']);
echo $chart->output();
```

## Installation
Install via composer: <code>"minond/profiler": "dev-master"</code>
