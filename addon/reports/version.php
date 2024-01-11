<?php

defined("MOODLE_INTERNAL") || die();


$plugin->component = 'skilladdon_reports';
$plugin->version = 2021010500;
$plugin->requires  = 2021051700;
$plugin->maturity  = MATURITY_ALPHA;
$plugin->release = '1.0';
$plugin->dependencies = [
    'tool_skills' => 2023102505
];

