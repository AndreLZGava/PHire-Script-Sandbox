<?php

declare(strict_types=1);


namespace PHireScript\Sandbox\samples\success\case_75;


$text = 'a-b-c';
$parts = \explode('-', $text, 9223372036854775807);
