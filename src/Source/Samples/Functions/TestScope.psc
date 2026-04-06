<?php
$data = ["apple", "banana"];

function resetData() {
    $data = "cleared";
    echo $data->toUpperCase();
};

$data->push("orange");
resetData();
echo $data->join(" - ");
