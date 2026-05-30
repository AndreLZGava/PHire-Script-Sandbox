<?php
// Alias for external package becomes namespace
use DateTime as DateTimePhp;
// Creates a new class by calling () after identifier, and then can call the chaining methods
$date = (new DateTimePhp())->modify('+3 days')
->modify('+2 hours')
->format('d/m/Y H:i');

\print_r($date);
