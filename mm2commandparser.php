<?php

	class MM2CommandParser 
	{
		private $max_hold_time;
		private $min_hold_time;
		private $simple_command_prefix;
		private $advanced_command_prefix;
	
		function __construct( $max_hold_time = 2.0, $min_hold_time = 0.05, $simple_command_prefix = '', $advanced_command_prefix = '$' ) {
			$this->max_hold_time = $max_hold_time;
			$this->min_hold_time = $min_hold_time;
			$this->simple_command_prefix = $simple_command_prefix;
			$this->advanced_command_prefix = $advanced_command_prefix;

			$logging_enabled = false;
		}

		function enableLogging($enable)
		{
			$this->logging_enabled = $enable;
		}
	
		function parseCommand($command, $allow_advanced = false, $max_instructions = 5) 
		{
			$instructions = [];
			$instruction_count = 0;

			//ensure string is lowercase for parsing
			$command = strtolower($command);
			
			//determine length of first instruction
			$length = $this->getFirstInstructionLength($command);
			
			//loop until no more instructions or hit max instructions
			while($length > 0 and $instruction_count < $max_instructions)
			{
				//add this instruction to the list
				$instructions = array_merge($instructions, $this->processInstruction(substr($command, 0, $length), $allow_advanced));
				
				//trim the instruction from the command
				$command = substr($command, $length);
				
				//if there's more, process the separator
				if(strlen($command) > 0)
				{
					//trim the separator from the command
					$command = substr($command, 1);
				}
				
				//get length of the next instruction so we can determine if there's more to do
				$length = $this->getFirstInstructionLength($command);
				
				//increase the count so we can check against max
				$instruction_count++;
			}
			
			if($length > 0)
			{
				$this->logDebug("Too many instructions in command only running first " . $max_instructions);
			}

			$this->logDebug("Parsed command result: " . implode(", ", $instructions));

			return $instructions;
		}
	
		//gets the length of the first instruction in the given command
		function getFirstInstructionLength($command)
		{
			$length = 0;
			
			//loop over each character
			for(; $length < strlen($command); $length++)
			{
				//get this character
				$char = $command[$length];
				
				//if the charcter is a separator (space for now), break out of the loop
				if($char == ' ') break;
			}
			
			//either hit the end or a separator, return the length
			return $length;
		}

		function processInstruction($instruction, $allow_advanced = false)
		{
			//check advanced first because simple instructions might not have a prefix
			if($this->isAdvanced($instruction))
			{
				if(!$allow_advanced)
				{
					//advanced instructions not allowed, need to ignore
					$this->logDebug("Ignoring advanced instruction: " . $instruction);
					return [];
				}

				//strip the prefix and process the instruction
				return $this->processAdvancedInstruction($this->stripAdvancedPrefix($instruction));
			}
			else if($this->isSimple($instruction))
			{
				//strip the prefix and process the instruction
				return $this->processSimpleInstruction($this->stripSimplePrefix($instruction));
			}

			$this->logDebug("Ignoring unknown instruction type: " . $instruction);
			return [];
		}

		function isSimple($instruction)
		{
			//check the start of the string for the prefix
			return substr($instruction, 0, strlen($this->simple_command_prefix)) == $this->simple_command_prefix;
		}
	
		function stripSimplePrefix($instruction)
		{
			//assume this is only called if we already know there is a prefix
			//remove the start of the string up to the prefix length
			return substr($instruction, strlen($this->simple_command_prefix));
		}

		function isAdvanced($instruction)
		{
			//check the start of the string for the prefix
			return substr($instruction, 0, strlen($this->advanced_command_prefix)) == $this->advanced_command_prefix;
		}
	
		function stripAdvancedPrefix($instruction)
		{
			//assume this is only called if we already know there is a prefix
			//remove the start of the string up to the prefix length
			return substr($instruction, strlen($this->advanced_command_prefix));
		}

		function processSimpleInstruction($instruction)
		{
			//TODO - read these options from a file

			$instructions = []; //empty instruction list by default
			
			//convert chat input into a simple instruction
			if ($instruction == 'jump' or $instruction == 'j'){$instructions = ["A .4s"];}
			if ($instruction == 'rightjump' or $instruction == 'rrj'){$instructions = ["Y .4s A .4s DPAD_RIGHT .4s"];}
			if ($instruction == 'leftjump' or $instruction == 'lj'){$instructions = ["Y .4s A .4s DPAD_LEFT .4s"];}
			if ($instruction == 'right' or $instruction == 'r'){$instructions = ["Y .4s DPAD_RIGHT .4s"];}
			if ($instruction == 'left' or $instruction == 'l'){$instructions = ["Y .4s DPAD_LEFT .4s"];}
			if ($instruction == 'down' or $instruction == 'd'){$instructions = ["DPAD_DOWN .4s"];}
			if ($instruction == 'up' or $instruction == 'u'){$instructions = ["DPAD_UP .4s"];}
			if ($instruction == 'f' or $instruction == 'fire'){$instructions = ["Y .2s"];}
			if ($instruction == 'duckjump' or $instruction == 'dj'){$instructions = ["DPAD_DOWN .1s","Y .4s A .4s DPAD_DOWN .4s"];}
			if ($instruction == 'twirljump' or $instruction == 'tj'){$instructions = ["Y .05s DPAD_UP .05s R .05s",".05s","B .2s"];}
			if ($instruction == 'w'){$instructions = [".05s"];}
			if ($instruction == 'ww'){$instructions = [".5s"];}
			if ($instruction == 'www'){$instructions = ["2.0s"];}
			
			if(count($instructions) == 0) $this->logDebug("Unknown instruction: " . $instruction . " - Skipping instruction");
			
			//return the simple instructions
			return $instructions;
		}

		function processAdvancedInstruction($instruction)
		{
			/*
			Overview:
				Loops over the instruction interpreting each character and translating it into the proper output text for that button, then adds it to the array.
				Once it runs into a number, parses the rest of the instruction as the duration
				
				Then takes all of the found buttons and adds them to the instruction along with the duration found at the end of the instruction or 0.05 if not specified
			*/
			
			//how long to hold for, 0.05 seconds by default
			$duration = 5;
			
			//the list of buttons to hold
			$buttons = [];
			
			//loop over each character in the instruction
			for ($i = 0; $i < strlen($instruction); $i++) {
				$char = $instruction[$i];
				
				//if the character is a number
				if(ctype_digit($char)) 
				{
					//parse the rest of the string as a floating point number
					$duration = (float)substr($instruction, $i);
					
					// there should be no more characters after the duration number
					break;
				} 
				
				//convert the button letter into the button instruction text
				$button = $this->translateButton($char);
	
				//only add the button if we understood what it was
				if(strlen($button) > 0)
				{
					//add the button to the list of buttons to hold
					array_push($buttons, $button);
				}
			}
			
			//remove any duplicate buttons
			$buttons = array_unique($buttons);
			
			//convert the duration from hundredths of a second to seconds
			$duration /= 100.0;
			
			//prevent the user from holding the buttons too long or too short
			if($duration < $this->min_hold_time) 
			{
				$this->logDebug("Duration too small: " . $duration . "s Min allowed: " . $this->min_hold_time . "s, Setting to: ". $this->min_hold_time . "s");
				$duration = $this->min_hold_time;
			}
			if($duration > $this->max_hold_time) 
			{
				$this->logDebug("Duration too large: " . $duration . "s Max allowed: " . $this->max_hold_time . "s, Setting to: ". $this->max_hold_time . "s");
				$duration = $this->max_hold_time;
			}
			
			
			//convert the buttons and duration into an instruction
			$result = '';

			if(count($buttons) == 0)
			{
				//wait insttruction
				$result .= number_format($duration, 2) . 's ';
			}
			else
			{
				foreach($buttons as $button)
				{
					//for each button add the button and duration to the instruction
					$result .= $button . ' ' . number_format($duration, 2) . 's ';
				}
			}
			
			//remove the trailing space
			$result = substr($result, 0, -1);
			
			//return as an array of a single instruction
			return [$result];
		}

		function translateButton($button)
		{
			//converts chat instruction button into the correct button text for sending to the controller
			switch($button)
			{
				case 'a':
					return 'A';
				case 'b':
					return 'B';
				case 'x':
					return 'X';
				case 'y':
					return 'Y';
				case 'z':
					return 'R';
				case 'l':
					return 'DPAD_LEFT';
				case 'r':
					return 'DPAD_RIGHT';
				case 'u':
					return 'DPAD_UP';
				case 'd':
					return 'DPAD_DOWN';
				default:
					logDebug("Unknown button requested: " . $button . " - skipping button");
					return ''; // don't know this button, just return empty so nothing happens
			}
		}
		
	
		function logDebug($message)
		{
			if($this->logging_enabled)
			{
				print($message . PHP_EOL);
			}
		}
	}
	
?>