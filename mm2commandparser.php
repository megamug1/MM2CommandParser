<?php
	
	$c = 'Hey how\'s it going?????'. PHP_EOL .'I\'m having a lot of fun!!!';
	$c = strtolower($c);
	
	//********* MAKE SURE TO PUT THESE AT THE TOP OF THE SCRIPT *********//
	const MAX_HOLD_TIME = 2.0; //maximum time to allow a user to press buttons in seconds
	const MIN_HOLD_TIME = 0.05; //minimum time to allow a user to press buttons in seconds (may be needed if controller has minimum threshold)
	const CUSTOM_COMMAND_PREFIX = '$'; //can be changed to whatever character(s) you want 
	const MAX_INSTRUCTIONS = 5; //maximum number of instructions that can be chained at once
	
	
	//********* REPLACE YOUR EXISTING COMMAND PROCESSING LOGIC WITH THIS *********//
	
	
	//****** PROCESS COMMAND ******//
	$t = []; //empty instruction list by default
	
	//ensure there is actually a chat command to process
	if(strlen($c) > 0)
	{
		if(is_precan($c))
		{
			$t = precan_process_command($c);
		}
		else
		{
			$t = custom_process_command(strip_custom_prefix($c));
		}
	}
	
	function is_precan($c)
	{
		//precan means there should be no prefix, check the start of the string for the prefix
		return substr($c, 0, strlen(CUSTOM_COMMAND_PREFIX)) != CUSTOM_COMMAND_PREFIX;
	}
	
	function strip_custom_prefix($c)
	{
		//assume this is only called if we already know there is a prefix
		//remove the start of the string up to the prefix length
		return substr($c, strlen(CUSTOM_COMMAND_PREFIX));
	}
	
	debug_log("Resulting Instructions: " . implode(", ", $t));
	
	//****** END PROCESS COMMAND ******//
	
	
	//****** PROCESS PRECANNED COMMAND ******//
	
	function precan_process_command($c)
	{
		$t = [];
		$instruction_count = 0;
		
		//determine length of first instruction
		$length = precan_find_instruction_length($c);
		
		//loop until no more instructions or hit max instructions
		while($length > 0 and $instruction_count < MAX_INSTRUCTIONS)
		{
			//add this instruction to the list
			$t = array_merge($t, precan_process_instruction(substr($c, 0, $length)));
			
			//trim the instruction from the command
			$c = substr($c, $length);
			
			//if there's more process the separator
			if(strlen($c) > 0)
			{
				//add separator instruction to the list
				$t = array_merge($t, precan_process_separator(substr($c, 0, 1)));
				//trim the separator from the command
				$c = substr($c, 1);
			}
			
			//get length of the next instruction so we can determine if there's more
			$length = precan_find_instruction_length($c);
			
			//increase the count so we can check against max
			$instruction_count++;
		}
		
		if($length > 0)
		{
			debug_log("Too many instructions in command only running first " . MAX_INSTRUCTIONS);
		}
		
		return $t;
	}
	
	function precan_find_instruction_length($c)
	{
		$length = 0;
		
		//loop over each character
		for(; $length < strlen($c); $length++)
		{
			//get this character
			$char = $c[$length];
			
			//if the charcter is a space or a dash we hit the end, break out of the loop
			if($char == ' ' || $char == '-') break;
		}
		
		//either hit the end or a separator, return the length
		return $length;
	}
	
	function precan_process_instruction($c)
	{
		$t = []; //empty command list by default
		
		//convert chat input into a precanned command to the controller
		if ($c == 'jump' or $c == 'j'){$t = ["A .4s"];}
		if ($c == 'rightjump' or $c == 'rrj'){$t = ["Y .4s A .4s DPAD_RIGHT .4s"];}
		if ($c == 'leftjump' or $c == 'lj'){$t = ["Y .4s A .4s DPAD_LEFT .4s"];}
		if ($c == 'right' or $c == 'r'){$t = ["Y .4s DPAD_RIGHT .4s"];}
		if ($c == 'left' or $c == 'l'){$t = ["Y .4s DPAD_LEFT .4s"];}
		if ($c == 'down' or $c == 'd'){$t = ["DPAD_DOWN .4s"];}
		if ($c == 'up' or $c == 'u'){$t = ["DPAD_UP .4s"];}
		if ($c == 'f' or $c == 'fire'){$t = ["Y .2s"];}
		if ($c == 'duckjump' or $c == 'dj'){$t = ["DPAD_DOWN .1s","Y .4s A .4s DPAD_DOWN .4s"];}
		if ($c == 'twirljump' or $c == 'tj'){$t = ["Y .05s DPAD_UP .05s R .05s",".05s","B .2s"];}
		
		if(count($t) == 0) debug_log("Unknown instruction: " . $c . " - Skipping instruction");
		
		//return the precanned command(s)
		return $t;
	}
	
	function precan_process_separator($c)
	{
		//if it's a space, add a pause
		if($c == ' ') return ["0.05s"];
		
		//otherwise don't add anything
		return [];
	}
	
	//****** END PROCESS PRECANNED COMMAND ******//
	
	
	//****** PROCESS CUSTOM COMMAND ******//
	
	function custom_process_command($c)
	{
		/*
			Allows chat to contain two sequential custom commands. Each command specifies all buttons to press and the duration to hold them.
			Allows two commands so spin jumps are possible, but no more so a single user doesn't completely take over.
		
			Format: [buttons][duration][space or dash][buttons][duration]
			Everything after the first set of buttons is optional
			[buttons] = A,B,X,Y,Z(Right bumper),L,R,U,D
			[duration] = (optional: default is 0.05 seconds) number from 5 to 200 representing the number of hundreths of a second to hold the button for
			[space or dash] = ' ' or '-', if space waits 0.05 seconds before running the second command, if dash runs the second command immediately
			
			examples:
				A40 -> Jump for 0.4 seconds
				YAR40 -> Jump to the right by holding both jump buttons for 0.4 seconds
				D10-YAD40 -> duck jump by holding down for 0.1 seconds before jumping while holding down for 0.4 seconds
				YUZ5 B20 -> twirl jump by dashing, holding up, and spin jumping. then waiting 0.05 seconds. then jumping for 0.2 seconds
				
		*/
		
		$t = [];
		$instruction_count = 0;
		
		//determine length of first instruction
		$length = custom_find_instruction_length($c);
		
		//loop until no more instructions or hit max instructions
		while($length > 0 and $instruction_count < MAX_INSTRUCTIONS)
		{
			//add this instruction to the list
			$t = array_merge($t, custom_process_instruction(substr($c, 0, $length)));
			
			//trim the instruction from the command
			$c = substr($c, $length);
			
			//if there's more process the separator
			if(strlen($c) > 0)
			{
				//add separator instruction to the list
				$t = array_merge($t, custom_process_separator(substr($c, 0, 1)));
				//trim the separator from the command
				$c = substr($c, 1);
			}
			
			//get length of the next instruction so we can determine if there's more
			$length = custom_find_instruction_length($c);
			
			//increase the count so we can check against max
			$instruction_count++;
		}
		
		if($length > 0)
		{
			debug_log("Too many instructions in command only running first " . MAX_INSTRUCTIONS);
		}
		
		return $t;
	}
	
	function custom_find_instruction_length($c)
	{
		$length = 0;
		
		//loop over each character
		for(; $length < strlen($c); $length++)
		{
			//get this character
			$char = $c[$length];
			
			//if the charcter is a space or a dash we hit the end, break out of the loop
			if($char == ' ' || $char == '-') break;
		}
		
		//either hit the end or a separator, return the length
		return $length;
	}
	
	function custom_process_instruction($c)
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
		for ($i = 0; $i < strlen($c); $i++) {
			$char = $c[$i];
			
			//if the character is a number
			if(ctype_digit($char)) 
			{
				//parse the rest of the string as a floating point number
				$duration = (float)substr($c, $i);
				
				// there should be no more characters after the duration number
				break;
			} 
			
			//convert the button letter into the button instruction text
			$button = custom_translate_button($char);

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
		if($duration < MIN_HOLD_TIME) 
		{
			debug_log("Duration too small: " . $duration . "s Min allowed: " . MIN_HOLD_TIME . "s, Setting to: ". MIN_HOLD_TIME . "s");
			$duration = MIN_HOLD_TIME;
		}
		if($duration > MAX_HOLD_TIME) 
		{
			debug_log("Duration too large: " . $duration . "s Max allowed: " . MAX_HOLD_TIME . "s, Setting to: ". MAX_HOLD_TIME . "s");
			$duration = MAX_HOLD_TIME;
		}
		
		
		//convert the buttons and duration into an instruction
		$instruction = '';
		foreach($buttons as $button)
		{
			//for each button add the button and duration to the instruction
			$instruction .= $button . ' ' . number_format($duration, 2) . 's ';
		}
		
		//remove the trailing space
		$instruction = substr($instruction, 0, -1);
		
		//return as an array of a single instruction
		return [$instruction];
	}
	
	function custom_process_separator($c)
	{
		//if it's a space, add a pause
		if($c == ' ') return ["0.05s"];
		
		//otherwise don't add anything
		return [];
	}
	
	function custom_translate_button($button)
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
				debug_log("Unknown button requested: " . $button . " - skipping button");
				return ''; // don't know this button, just return empty so nothing happens
		}
	}
	
	//****** END PROCESS CUSTOM COMMAND ******//
	
	//****** LOGGING ******//
	
	function debug_log($message)
	{
		//TODO - replace this with your logging mechanism
		//print($message . PHP_EOL);
	}
	
	//****** END LOGGING ******//
	
?>