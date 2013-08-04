<?php

use util\profile\Profiler;

if (Profiler::enabled()) {
    Profiler::profile([
        'mode' => Profiler::HEAVY,
        'name' => 'Profiler',
        'report' => '\util\profile\reports\Chart',
    ]);
}
