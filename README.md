# MM2CommandParser
Reads in human written commands and translates them into instructions that can be sent to Mario Maker 2 to control Mario.

# Syntax
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

## Examples

$A40 -> Jump for 0.4 seconds\
$YAR40 -> Jump to the right by holding both jump buttons for 0.4 seconds\
$D10 $YAD40 -> duck jump by holding down for 0.1 seconds before jumping while holding down for 0.4 seconds\
$YUZ5 $5 $B20 -> twirl jump by dashing, holding up, and spin jumping. then waiting 0.05 seconds. then jumping for 0.2 seconds\
