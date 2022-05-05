# MM2CommandParser
Reads in human written commands and translates them into instructions that can be sent to Mario Maker 2 to control Mario.

# How To Use
Require the library at the top of your php file
```
require 'mm2commandparser.php';
```

Create an instance of the command parser
```
$parser = new MM2CommandParser();
```

Set the simple instruction list if you want the change the defaults
```
$parser->setSimpleInstructionList('instructionListFile.txt');
```

Parse any commands you want to
```
$result = $parser->parseCommand($command);
$other_result = $parser->parseCommand($advancedCommand, true); //second parameter is true to allow for advanced instructions
```

# Simple Instruction List File Format
* Each Instruction should be on a new line
* Each instruction may have multiple key words separated by a pipe character ('|')
* The result for the instruction is a csv format of individual instructions

## Example Instructions

A jump instruction where the user can input either 'jump' or 'j'
```
jump|j = "A .4s"
```

A duck jump instruction where the user and input either 'duckjump' or 'dj'
```
duckjump|dj = "DPAD_DOWN .1s","Y .4s A .4s DPAD_DOWN .4s"
```

# Command Syntax
## Syntax Examples

JUMP -> Jump for 0.4 seconds (assumes the default instruction list is being used)\
$A40 -> Jump for 0.4 seconds\
JUMP w $A80 -> Jump for 0.4 seconds, wait for 0.05 seconds, jump for 0.8 seconds (assumes the default instruction list is being used)\
$YAR40 -> Jump to the right by holding both jump buttons for 0.4 seconds\
$D10 $YAD40 -> duck jump by holding down for 0.1 seconds before jumping while holding down for 0.4 seconds\
$YUZ5 $5 $B20 -> twirl jump by dashing, holding up, and spin jumping. then waiting 0.05 seconds. then jumping for 0.2 seconds

## Syntax Overview
The general syntax is that you specify an instruction followed by any number of instructions with a separator in between\
**command**: \[instruction\](\[separator\]\[instruction\])*\
\
An instruction can be either simple or advanced\
**instruction**: \[simple instruction\]\|$\[advanced instruction\]\
\
**simple instruction**: One of the simple instructions included in the simple instructions file. This must be an exact match.\
\
An advanced instruction includes any number of buttons to hold (zero is allowed) and a duration to hold them for. If no buttons are specified, the instruction is a wait instruction for the length of the duration.\
**advanced instruction**: \[buttons\]\[duration\]\
\
The buttons include a, b, x, y, z (right bumper), u (up), d (down), r (right), l (left)\
**buttons**: abxyzudrl\
\
**duration**: The number of hundreths of a second to hold the instruction for\
\
For now a separator is expected to be a space (' '), but may include other characters in the future\
**separator**: space


