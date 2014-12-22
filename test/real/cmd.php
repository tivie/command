<?php

// On windows, stream_set_blocking(STDIN, 0) does not work.
// When using exec, it's not possible to read from STDIN without pressing ENTER
// This means this MOCK COMMAND cannot be used to automate testes in windows
// When using exec or passing an empty STDIN

// A dirty hack around this is using a flag (--hasStdIn) so that the command knows
// if it's using named pipes and STDIN has data


$stdIn = null;
$stdOut = '';
$stdErr = '';
$numOfArgs = 0;
$args = array();
$opts = array();

//Flag to check for stdIn
$longOpts = array('hasStdIn', 'otp:');
$opts = getopt('', $longOpts);

if (isset($opts['hasStdIn'])) {
    while ($line = fgets(STDIN)) {
        $stdIn .= $line;
    }
}

if (isset($opts['otp'])) {
    echo $opts['otp'];
} else {
    echo json_encode(
        array(
            "STDIN" => $stdIn,
            "NumOfArgs" => $argc,
            "Args" => $argv,
        )
    );
}
