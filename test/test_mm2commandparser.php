<?php
    require 'mm2commandparser.php';
	
	function runTests($tests)
	{
		foreach($tests as $test)
		{
			try
			{
				print('***** Running test: '. $test . PHP_EOL);
				$success = $test();
			}
			catch(Exception $e)
			{
				$success = false;
				print('Exception in test: ' . $test . PHP_EOL);
			}

			if($success)
			{
				print('PASSED' . PHP_EOL);
			}
			else
			{
				print('FAILED' . PHP_EOL);
			}
		}
	}

	$tests = [];

	function testSimpleCommand()
	{
		$parser = new MM2CommandParser();
		$result = $parser->parseCommand('w');

		return $result == ['.05s'];
	}
	array_push($tests, 'testSimpleCommand');

	function testAdvancedCommand()
	{
		$parser = new MM2CommandParser();
		$result = $parser->parseCommand('$A5', true);

		return $result == ['A 0.05s'];
	}
	array_push($tests, 'testAdvancedCommand');

	function testComplexAdvancedCommand()
	{
		$parser = new MM2CommandParser();
		$result = $parser->parseCommand('$YUZ5 $5 $B20', true);

		return $result == ['Y 0.05s DPAD_UP 0.05s R 0.05s', '0.05s', 'B 0.20s'];
	}
	array_push($tests, 'testComplexAdvancedCommand');

	function testAllAdvancedButtons()
	{
		$parser = new MM2CommandParser();
		$result = $parser->parseCommand('$ABXYZUDLR10', true);

		return $result == ['A 0.10s B 0.10s X 0.10s Y 0.10s R 0.10s DPAD_UP 0.10s DPAD_DOWN 0.10s DPAD_LEFT 0.10s DPAD_RIGHT 0.10s'];
	}
	array_push($tests, 'testAllAdvancedButtons');

	function testMultiCommand()
	{
		$parser = new MM2CommandParser();
		$result = $parser->parseCommand('w $A5', true);

		return $result == ['.05s', 'A 0.05s'];
	}
	array_push($tests, 'testMultiCommand');

	function testAdvancedDisabled()
	{
		$parser = new MM2CommandParser();
		$result = $parser->parseCommand('w $A5');

		return $result == ['.05s'];
	}
	array_push($tests, 'testAdvancedDisabled');

	function testMaxInstructions()
	{
		$parser = new MM2CommandParser();
		$result = $parser->parseCommand('w j w j w j');

		return $result == ['.05s', 'A .4s', '.05s', 'A .4s', '.05s'];
	}
	array_push($tests, 'testMaxInstructions');

	function testExtendMaxInstructions()
	{
		$parser = new MM2CommandParser();
		$result = $parser->parseCommand('w j w j w j w j', false, 7);

		return $result == ['.05s', 'A .4s', '.05s', 'A .4s', '.05s', 'A .4s', '.05s'];
	}
	array_push($tests, 'testExtendMaxInstructions');

	function testMinMaxHoldTime()
	{
		$parser = new MM2CommandParser();
		$result = $parser->parseCommand('$A500 $B1', true);

		return $result == ['A 2.00s', 'B 0.05s'];
	}
	array_push($tests, 'testMinMaxHoldTime');

	function testChangePrefix()
	{
		$parser = new MM2CommandParser(2, 0.05, '*', '(');
		$result = $parser->parseCommand('*w (A5 j', true);

		return $result == ['.05s', 'A 0.05s'];
	}
	array_push($tests, 'testChangePrefix');

	

	runTests($tests);
?>