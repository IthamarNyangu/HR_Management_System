<?php

$terminationAlertJobTitles = array_values(array_filter(array_map(
    static fn (string $title): string => trim($title),
    explode(',', (string) env('TERMINATION_ALERT_JOB_TITLES', 'IT Security Officer'))
)));

return [
    'termination_alerts' => [
        'enabled' => (bool) env('TERMINATION_ALERTS_ENABLED', true),
        'job_titles' => $terminationAlertJobTitles,
    ],
];
