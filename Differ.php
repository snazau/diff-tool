<?php

require("ITestable.php");
require("Tester.php");

/**
 * Class Differ
 * To get more into the understanding of algorithmic part read:
 * https://blog.jcoglan.com/2017/02/12/the-myers-diff-algorithm-part-1/
 * https://blog.jcoglan.com/2017/02/15/the-myers-diff-algorithm-part-2/
 * https://blog.jcoglan.com/2017/02/17/the-myers-diff-algorithm-part-3/
 * Original paper:
 * http://www.xmailserver.org/diff2.pdf
 */
class Differ implements ITestable
{
	const PHP_ARRAY_OUTPUT = 0;
	const HTML_OUTPUT = 1;

	private $oldText = [];
	private $newText = [];
	private $diffHistory = [];

	/**
	 * Differ constructor.
	 *
	 * @param $oldText - old linewise version of text
	 * @param $newText - new linewise version of text
	 */
	public function __construct($oldText, $newText)
	{
		$this->oldText = $oldText;
		$this->newText = $newText;
	}

	/**
	 * @param $old - old linewise version of text
	 * @param $new - new linewise version of text
	 *
	 * @return array - returns changing story of x-parameter (check the links at the class definition)
	 */
	private function shortestEditPath($old, $new)
	{
		$oldLength = count($old);
		$newLength = count($new);
		$sumLength = $oldLength + $newLength;

		$xs = array_pad([], $sumLength + 1, 0);
		$xs_holder = [];

		for ($depth = 0; $depth <= $sumLength; $depth++)
		{
			$xs_holder[] = $xs;
			//			k = x - y
			for ($k = -$depth; $k <= $depth; $k += 2)
			{
				//				Greedy choice
				if ($k === -$depth || ($k !== $depth && $xs[$k - 1] < $xs[$k + 1]))
				{
					$x = $xs[$k + 1];
				}
				else
				{
					$x = $xs[$k - 1] + 1;
				}
				$y = $x - $k;

				//				Moving diagonally
				while ($x < $oldLength && $y < $newLength && $old[$x] === $new[$y])
				{
					$x++;
					$y++;
				}

				$xs[$k] = $x;

				if ($x >= $oldLength && $y >= $newLength)
				{
					return $xs_holder;
				}
			}
		}
	}

	/**
	 * @param $old - old linewise version of text
	 * @param $new - new linewise version of text
	 *
	 * @return array - array of steps in Oxy grid (check the links at the class definition)
	 */
	private function reconstructPath($old, $new)
	{
		$x = count($old);
		$y = count($new);

		$xs_history = $this->shortestEditPath($old, $new);

		//		Stores pairs of shortest sequence of action to get $new from $old
		$path = [];
		for ($depth = count($xs_history) - 1; $depth >= 0; $depth--)
		{
			$xs = $xs_history[$depth];
			$k = $x - $y;

			//			Choosing where we came from
			if ($k === -$depth || ($k !== $depth && $xs[$k - 1] < $xs[$k + 1]))
			{
				$prev_k = $k + 1;
			}
			else
			{
				$prev_k = $k - 1;
			}

			$prev_x = $xs[$prev_k];
			$prev_y = $prev_x - $prev_k;

			//			Moving diagonally back
			while ($x > $prev_x && $y > $prev_y)
			{
				$path[] = ["x_old" => $x - 1, "y_old" => $y - 1, "x_new" => $x, "y_new" => $y];
				$x--;
				$y--;
			}

			if ($depth > 0)
			{
				$path[] = ["x_old" => $prev_x, "y_old" => $prev_y, "x_new" => $x, "y_new" => $y];
			}

			$x = $prev_x;
			$y = $prev_y;
		}

		return $path;
	}

	/**
	 * @param int $outputMode - sets output mode for diffHistory (either raw php array or html-string)
	 *
	 * @return array|string - string in case $outputMode === Diff::HTML_OUTPUT,  else php array
	 */
	public function diff($outputMode = self::PHP_ARRAY_OUTPUT)
	{
		$diffHistory = [];
		$oldLength = count($this->oldText);
		$newLength = count($this->newText);
		$path = $this->reconstructPath($this->oldText, $this->newText);

		foreach ($path as $move)
		{
			$prev_x = $move["x_old"];
			$prev_y = $move["y_old"];
			$x = $move["x_new"];
			$y = $move["y_new"];

			$oldLine = $this->oldText[$prev_x % $oldLength];
			$newLine = $this->newText[$prev_y % $newLength];

			if ($x === $prev_x)
			{
				$diffHistory[] = ["type" => "+", "oldLine" => "", "newLine" => $newLine];
			}
			elseif ($y === $prev_y)
			{
				$diffHistory[] = ["type" => "-", "oldLine" => $oldLine, "newLine" => ""];
			}
			else
			{
				$diffHistory[] = ["type" => "=", "oldLine" => $oldLine, "newLine" => $newLine];
			}
		}

		$diffHistory = array_reverse($diffHistory);

		if ($outputMode === self::HTML_OUTPUT)
		{
			$htmlDiff = "";
			foreach ($diffHistory as $action)
			{
				$typeClass = "";
				if ($action["type"] === "=")
				{
					$typeClass = "line equal-line";
				}
				else if ($action["type"] === "-")
				{
					$typeClass = "line minus-line";
				}
				else
				{
					$typeClass = "line plus-line";
				}
				$isEqual = (($action["type"] === "=") ? (true) : (false));
				$htmlDiff .= '<div class="'.$typeClass.'">';
				$htmlDiff .= '<span class="line-type"> '.$action["type"].' </span>';
				$htmlDiff .= '<span>'.$action["oldLine"].'</span>';
				if ($isEqual === false)
				{
					$htmlDiff .= '<span>'.$action["newLine"].'</span>';
				}
				$htmlDiff .= '</div>';
			}

			return $htmlDiff;
		}

		$this->diffHistory = $diffHistory;

		return $this->diffHistory;
	}

	private function shortestEditPath_TEST()
	{
		echo "shortestEditPath function test".PHP_EOL;
		$old = ["A"];
		$new = ["A"];
		$correctXsHolder = [
			0 => [0 => 0, 1 => 0, 2 => 0],
		];
		$actualXsHolder = $this->shortestEditPath($old, $new);
		echo 'Test: $old = ["A"] $new = ["A"] - '.
			 (($actualXsHolder == $correctXsHolder) ? ('success') : ('failed ('.var_export($actualXsHolder, true).')')).
			 PHP_EOL;

		$old = ["A"];
		$new = ["A", "B"];
		$correctXsHolder = [
			0 => [0 => 0, 1 => 0, 2 => 0, 3 => 0],
			1 => [0 => 1, 1 => 0, 2 => 0, 3 => 0],
		];
		$actualXsHolder = $this->shortestEditPath($old, $new);
		echo 'Test: $old = ["A"] $new = ["A", "B"] - '.
			 (($actualXsHolder == $correctXsHolder) ? ('success') : ('failed ('.var_export($actualXsHolder, true).')')).
			 PHP_EOL;

		$old = ["A"];
		$new = ["B"];
		$correctXsHolder = [
			0 => [0 => 0, 1 => 0, 2 => 0],
			1 => [0 => 0, 1 => 0, 2 => 0],
			2 => [0 => 0, 1 => 1, 2 => 0, -1 => 0],
		];
		$actualXsHolder = $this->shortestEditPath($old, $new);
		echo 'Test: $old = ["A"] $new = ["B"] - '.
			 (($actualXsHolder == $correctXsHolder) ? ('success') : ('failed ('.var_export($actualXsHolder, true).')')).
			 PHP_EOL;

		echo PHP_EOL;
	}

	private function reconstructPath_TEST()
	{
		echo "reconstructPath function test".PHP_EOL;

		$old = ["A"];
		$new = ["A"];
		$correctPath = [
			0 => [
				'x_old' => 0,
				'y_old' => 0,
				'x_new' => 1,
				'y_new' => 1,
			],
		];
		$actualPath = $this->reconstructPath($old, $new);
		echo 'Test: $old = ["A"] $new = ["A"] - '.
			 (($actualPath == $correctPath) ? ('success') : ('failed ('.var_export($actualPath, true).')')).
			 PHP_EOL;
		//
		$old = ["A"];
		$new = ["A", "B"];
		$correctPath = [
			0 => [
				'x_old' => 1,
				'y_old' => 1,
				'x_new' => 1,
				'y_new' => 2,
			],
			1 => [
				'x_old' => 0,
				'y_old' => 0,
				'x_new' => 1,
				'y_new' => 1,
			],
		];
		$actualPath = $this->reconstructPath($old, $new);
		echo 'Test: $old = ["A"] $new = ["A", "B"] - '.
			 (($actualPath == $correctPath) ? ('success') : ('failed ('.var_export($actualPath, true).')')).
			 PHP_EOL;
		//
		$old = ["A"];
		$new = ["B"];
		$correctPath = [
			0 => [
				'x_old' => 1,
				'y_old' => 0,
				'x_new' => 1,
				'y_new' => 1,
			],
			1 => [
				'x_old' => 0,
				'y_old' => 0,
				'x_new' => 1,
				'y_new' => 0,
			],
		];
		$actualPath = $this->reconstructPath($old, $new);
		echo 'Test: $old = ["A"] $new = ["B"] - '.
			 (($actualPath == $correctPath) ? ('success') : ('failed ('.var_export($actualPath, true).')')).
			 PHP_EOL;

		echo PHP_EOL;
	}

	private function diff_TEST()
	{
		echo "diff function test".PHP_EOL;

		$old = ["A"];
		$new = ["A"];
		$differ = new Differ($old, $new);
		$correctDiff = [
			0 => [
				'type' => '=',
				'oldLine' => 'A',
				'newLine' => 'A',
			],
		];
		$actualDiff = $differ->diff();
		echo 'Test: $old = ["A"] $new = ["A"] - '.
			 (($actualDiff == $correctDiff) ? ('success') : ('failed ('.var_export($actualDiff, true).')')).
			 PHP_EOL;

		$old = ["A"];
		$new = ["A", "B"];
		$differ = new Differ($old, $new);
		$correctDiff = [
			0 => [
				'type' => '=',
				'oldLine' => 'A',
				'newLine' => 'A',
			],
			1 => [
				'type' => '+',
				'oldLine' => '',
				'newLine' => 'B',
			],
		];
		$actualDiff = $differ->diff();
		echo 'Test: $old = ["A"] $new = ["A", "B"] - '.
			 (($actualDiff == $correctDiff) ? ('success') : ('failed ('.var_export($actualDiff, true).')')).
			 PHP_EOL;

		$old = ["A"];
		$new = ["B"];
		$differ = new Differ($old, $new);
		$correctDiff = [
			0 => [
				'type' => '-',
				'oldLine' => 'A',
				'newLine' => '',
			],
			1 => [
				'type' => '+',
				'oldLine' => '',
				'newLine' => 'B',
			],
		];
		$actualDiff = $differ->diff();
		echo 'Test: $old = ["A"] $new = ["B"] - '.
			 (($actualDiff == $correctDiff) ? ('success') : ('failed ('.var_export($actualDiff, true).')')).
			 PHP_EOL;

		$old = ["A"];
		$new = ["A"];
		$differ = new Differ($old, $new);
		$correctDiff = '<div class="line equal-line"><span class="line-type"> = </span><span>A</span></div>';
		$actualDiff = $differ->diff(Differ::HTML_OUTPUT);
		echo 'Test: $old = ["A"] $new = ["A"] output as HTML - '.
			 (($actualDiff == $correctDiff) ? ('success') : ('failed ('.var_export($actualDiff, true).')')).
			 PHP_EOL;

		echo PHP_EOL;
	}

	const TEST_AMOUNT = 10;
	public function getTestAmount()
	{
		return Differ::TEST_AMOUNT;
	}

	public function runTests()
	{
		$this->shortestEditPath_TEST();
		$this->reconstructPath_TEST();
		$this->diff_TEST();
	}
}

if ($argv[1] === 'debug')
{
	$differ = new Differ(["A"], ["A"]);
	Tester::test($differ);
}
