<?php

namespace PHireScript\Sandbox\samples\success\case_40;

// Alias for external package becomes namespace
use DateTime as DateTimePhp;
// Creates a new instance assigned to variable
$date = new DateTimePhp();
$date->modify('+3 days');
// Format and assign the result
$result = $date->format('d/m/Y H:i');