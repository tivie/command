Command
=======  
[![Build Status](https://travis-ci.org/tivie/command.svg?branch=master)](https://travis-ci.org/tivie/command) [![Latest Stable Version](https://poser.pugx.org/tivie/command/v/stable.svg)](https://packagist.org/packages/tivie/command) [![License](https://poser.pugx.org/tivie/command/license.svg)](https://packagist.org/packages/tivie/command)


A cross-platform PHP utility library that harmonizes OS differences and executes external programs in a safer way

## Introduction
Command is a small lightweight utility library that enables you to run external programs or commands in a safer way.
It also harmonizes the differences between Windows and Unix environments, removing the need to create specific
code for each platform.

##Features

  - Platform independent: run the same code in Unix and Windows
  - Fixes issues with `proc_open` in windows environment
  - Object Oriented command builder with fluent interface
  - Argument escaping, for safer command calls
  - Command chaining with support for conditional calls and piping in both Windows and Unix environment
  - ~~Run commands in background~~ (coming soon)

## Installation
You can install it by cloning the git repository or using composer.

### Git clone

    git clone https://github.com/tivie/command.git

### Composer
Add these lines to your composer.json:
```json
    {
        "require": {
            "tivie/command": "*"
        }
    }
```
or run the following command:

    php composer.phar require tivie/command
    

## Quick Usage guide

### Simple example
Let's say we want to 'ping' google.com 3 times with 32bytes packets. With PHP we could do something like this:

```php
exec("ping -c 3 -s 24 www.google.com", $otp, $ec);
```

We want, however, to make our command a little bit safer and escape our arguments.
 
```php
$host = 'www.google.com';
$c = 3;
$s = 24; //Linux adds 8 bytes of ICMP header data
$cmd = sprintf("ping -c %d -s %d %s", escapeshellarg($c), escapeshellarg($s), escapeshellarg($host));
exec($cmd, $otp, $ec);
```

This will work as expected in a GNU/Linux environment but will fail on Windows, since '-c' is an unrecognized 
flag and '-s' means something entirely different. The windows version would be :

```php
$host = 'www.google.com';
$c = 3;
$s = 32;
$cmd = sprintf("ping -n %d -l %d %s", escapeshellarg($c), escapeshellarg($s), escapeshellarg($host));
exec($cmd, $otp, $ec);
```

If we want to ensure cross platform compatibility we will need to perform some kind of OS check and run the appropriate 
command based on that check:

```php
$host = 'www.google.com';
$c = 3;
if (PHP_OS === 'WINDOWS' || PHP_OS === 'WIN32' || PHP_OS === 'WINNT' /* And a few more*/ ) {
    $s = 32;
    $cmd = sprintf("ping -n %d -l %d %s", escapeshellarg($c), escapeshellarg($s), escapeshellarg($host));
} else {
    $s = 24; //Linux adds 8 bytes of ICMP header data
    $cmd = sprintf("ping -c %d -s %d %s", escapeshellarg($c), escapeshellarg($s), escapeshellarg($host));
}
exec($cmd, $otp, $ec);
```

While this works in most cases, with more complex commands (or command chains) you would be forced to repeat
yourself a lot, with a lot of conditional checks.

**With command library, you don't need to: it will do this work for you.**

```php
$cmd = new \Tivie\Command\Command(\Tivie\Command\ESCAPE);
$cmd->setCommand('ping')
    ->addArgument(
        new Argument('-n', 3, \Tivie\Command\OS\WINDOWS_FAMILY)
    )
    ->addArgument(
        new Argument('-l', 32, \Tivie\Command\OS\WINDOWS_FAMILY)
    )
    ->addArgument(
        new Argument('-c', 3, \Tivie\Command\OS\UNIX_FAMILY)
    )
    ->addArgument(
        new Argument('-s', 24, \Tivie\Command\OS\UNIX_FAMILY)
    )
    ->addArgument(
        new Argument('www.google.com')
    );
    
$result = $cmd->run();
```

`Command::run()` returns a [Result object][1] that you can access to retrieve the result of the command.

```php
echo $result->getStdOut();    // The Standard Output of the command
echo $result->getLastLine();  // The last line of the Standard Output
echo $result->getStdIn();     // The passed standard input
echo $result->getStdErr();    // The standard error
echo $result->getExitCode();  // The command's exit code
``` 

### Chaining commands

Command library supports command chaining

```php
$cmd1 = new \Tivie\Command\Command();
$cmd1->setCommand('php')
    ->addArgument(new Argument('-v'));

$cmd2 = new \Tivie\Command\Command();
$cmd2->setCommand('echo')
    ->addArgument(new Argument('foo'));
    
$results = $cmd1->chain()
                ->add($cmd2)
                /* any number of commands here */
                ->run(); 
```

`$results` will be an array of [Result objects][1].

You can also specify chaining conditions, similar to Linux's Chaining Operators. 

#### RUN_REGARDLESS (';')

```php
$cmd1->chain()->add($cmd2, \Tivie\Command\RUN_REGARDLESS)->run(); 
```

`$cmd2` will be run regardless of the exitcode of `$cmd1`. Mimics the ';' chaining operator and is the default action.

#### RUN_IF_PREVIOUS_SUCCEEDS ('&&')

```php
$cmd1->chain()->add($cmd2, \Tivie\Command\RUN_IF_PREVIOUS_SUCCEEDS)->run(); 
```

`$cmd2` will only be run if `$cmd1` is successful, that is, if it exits with exitcode 0. Mimics the '&&' chaining operator.

#### RUN_IF_PREVIOUS_FAILS ('||')

```php
$cmd1->chain()->add($cmd2, \Tivie\Command\RUN_IF_PREVIOUS_FAILS)->run(); 
```

`$cmd2` will only be run if `$cmd1` is not successful, that is, if it exits with exitcode different than 0. 
Mimics the '||' chaining operator.

#### Complex command chains

That being said, you can create complex command chains. For instance:

```php
$cmd1->chain()
     ->add($cmd2, \Tivie\Command\RUN_IF_PREVIOUS_SUCCEEDS)
     ->add($cmd3, \Tivie\Command\RUN_IF_PREVIOUS_FAILS)
     ->add($cmd4, \Tivie\Command\RUN_REGARDLESS)
     ->run(); 
```

This will:
  1. Run `$cmd1`
  2. If `$cmd1` is successful then runs `$cmd2`
  3. If `$cmd1` or `$cmd2` fails it will run `$cmd3`
  4. Finally will run `$cmd4`


### Piping

Command library supports 2 types of piping: 
  - **STDOUT->STDIN** 
  - **STDOUT->Argument**
 
#### STDOUT to STDIN 

Piping the standard output of one command to the next's standard input is easy. You just need to set the third argument
of [`Chain::add()`][2] to `true`.

```php
$cmd1->chain()->add($cmd2, \Tivie\Command\RUN_REGARDLESS, true)
```


#### STDOUT to Arguments

You can also pass the STDOUT of previous command as an argument of the next command. The library will look for the special
keyword (placeholder) ***'!PIPE!'*** in the command's argument key and values and replace them with the previous command's STDOUT.
You will then need to pass true as the third argument in [`Chain::add()`][2] function, same as the above case.

```php
$cmd2->addArgument(new Argument('foo'), \Tivie\Command\PIPE_PH); // PIPE_PH = '!PIPE!'
$cmd1->chain()->add($cmd2, \Tivie\Command\RUN_REGARDLESS, true);
```

## Add support for other OS

IF you need to more specific OS checks, you can extend [Detector class][3] or create a new class that implements [DetectorInterface][4].
For further information, please read the [php-os-detector][5] documentation.

Example:

```php
const OS_2_WARP  = 65540; //65536 + 4

class MyOSDetector extends \Tivie\OS\Detector
{
    public function detect()
    {
        $os = parent::detect();
        
        switch($os->name) {
            case "OS/2":
            case "OS/2 WARP":
                $os->family = \Tivie\Command\OS\OTHER_FAMILY;
                $os->def = OS_2_WARP;
                break;
        }
        
        return $os;
    }
}
```

You don't need to create a new constant pertaining the new OS (you can use one of the pre existing families). If, however, you
choose to do so, the new OS const value should be a unique number in the 2^n sequence plus the family the OS belongs
to. In the example we chose 16th term (65536) plus the OS family (in this case, FAMILY_OTHER) which is 4.


## Contribute
Feel free to contribute by forking or making suggestions.

Issue tracker: https://github.com/tivie/command/issues

Source code: https://github.com/tivie/command

### Contributors
[Tivie](http://tivie.github.com/Tivie)
[Sophie-OS](https://github.com/Sophie-OS)

## License
Command Library is released under Apache 2.0 license. For more information, please consult 
the [LICENSE](https://github.com/tivie/commandr/blob/master/LICENSE) file in this repository or 
http://www.apache.org/licenses/LICENSE-2.0.txt.

[1]: https://github.com/tivie/command/blob/master/src/Result.php
[2]: https://github.com/tivie/command/blob/master/src/Chain.php
[3]: https://github.com/tivie/php-os-detector/blob/master/src/Detector.php
[4]: https://github.com/tivie/php-os-detector/blob/master/src/DetectorInterface.php
[5]: https://github.com/tivie/php-os-detector
