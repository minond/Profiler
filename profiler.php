<?php

use util\profile\Profiler;

return Profiler::profile([
    'mode' => Profiler::HEAVY,
    'name' => 'Profiler',
    'report' => '\util\profile\reports\Chart',
]);
