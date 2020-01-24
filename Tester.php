<?php
class Tester {
	public static function test(ITestable $class) { // only these classes that implements interface ITestable will pass here
		echo "Test amount: ".$class->getTestAmount().PHP_EOL;
		echo $class->runTests();
	}
}