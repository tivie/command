<?php

echo "On windows, when using Exec and stdIn is empty, press enter!".PHP_EOL;

stream_set_blocking(STDIN, false);
$stdIn = fgets(STDIN);

echo "stdIn: $stdIn" . PHP_EOL;
echo "num of Args: $argc" . PHP_EOL;
echo "Args:" . PHP_EOL;
foreach ($argv as $k => $arg) {
    echo " $k => $arg";
}
