Tivie/Command Changelog
=======================

## Version 0.2.2 (2014-12-24)

###Features:
  - **Command**
    - Added *Command::chdir()* method, which enables one to set a new working directory for the command. [2919ee4](https://github.com/tivie/command/commit/2919ee432dc00520d10f4ff095d843057b5f742a)
    - Added *Command::setFlags()* and *Command::getFlags()* method. This enables flag modification after Command object initialization. Credit to [Sophie-OS](https://github.com/Sophie-OS) [9635986](https://github.com/tivie/command/commit/9635986b79a64bb2abc12380bb8e0f21bb02bac0)


## Version 0.2.1 (2014-12-22)

### Notes:
  - The library is now compatible with php >= 5.3. [2a44daa](https://github.com/tivie/command/commit/2a44daa7028db165bb30a77efa5be6be7a3beddd)
  - The library now relies on a 3rd party library (tivie/php-os-detector) to perform OS checks. [2a44daa](https://github.com/tivie/command/commit/2a44daa7028db165bb30a77efa5be6be7a3beddd)
  
### Breaking Changes:
  - By default, the library no longer escapes arguments. [2a44daa](https://github.com/tivie/command/commit/2a44daa7028db165bb30a77efa5be6be7a3beddd)
  - **Argument Object**
    - Argument object is no longer responsible for escaping itself. Command object takes care of it now. [2a44daa](https://github.com/tivie/command/commit/2a44daa7028db165bb30a77efa5be6be7a3beddd)
    - Argument escaping is now done JIT, when the command is about to run. [2a44daa](https://github.com/tivie/command/commit/2a44daa7028db165bb30a77efa5be6be7a3beddd)
    - *Argument constructor* now accepts only 4 parameters, 1 required ($key) and 3 optional ($value, $os, $scape). [2a44daa](https://github.com/tivie/command/commit/2a44daa7028db165bb30a77efa5be6be7a3beddd)
  - **Command**
    - *Command::addArgument()* now only accepts one parameter, an Argument object. [2a44daa](https://github.com/tivie/command/commit/2a44daa7028db165bb30a77efa5be6be7a3beddd)
	- *Command::removeArgument()* now only accepts one parameter, an Argument object. [2a44daa](https://github.com/tivie/command/commit/2a44daa7028db165bb30a77efa5be6be7a3beddd)
	- *Command::replaceArgument()* now throws an exception if `$oldArgument` does not exist. [2a44daa](https://github.com/tivie/command/commit/2a44daa7028db165bb30a77efa5be6be7a3beddd)
	- *Command::getArgument()* now only accepts an integer parameter $index that corresponds to the position of the argument in the Command object arguments array. [2a44daa](https://github.com/tivie/command/commit/2a44daa7028db165bb30a77efa5be6be7a3beddd)

### Features:
  - **Command**
    - Added a new method, Command::searchArgument that searches the Command object for a determined Argument given it's key or identifier. [2a44daa](https://github.com/tivie/command/commit/2a44daa7028db165bb30a77efa5be6be7a3beddd)
    - Added a new method, Command::argumentExists() that checks if an argument object exists in the command object. [2a44daa](https://github.com/tivie/command/commit/2a44daa7028db165bb30a77efa5be6be7a3beddd)


## Version 0.1.1 (2014-12-16)

###Features:
  - **Command**:
    - Added an extra parameter ($escape) to Command:addArgument(). Now it's possible to escape arguments individually. [2a44daa](https://github.com/tivie/command/commit/2a44daa7028db165bb30a77efa5be6be7a3beddd)


## Version 0.1.0 (2014-12-16)

###Features:
  - Platform independent: run the same code in Unix and Windows
  - Fixes issues with `proc_open` in windows environment
  - Object Oriented command builder with fluent interface
  - Argument escaping, for safer command calls
  - Command chaining with support for conditional calls and piping in both Windows and Unix environment
