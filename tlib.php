<?php

// vim: set noexpandtab tabstop=4 shiftwidth=4 softtabstop=4:

/**
 * @mainpage
 * <h1>TLib-PHP</h1>
 * This is TLib-PHP, a collection of PHP classes/functions/stuff that I (Ferry Boender) deem useful. 
 *
 * Examples on how to use the various classes and methods are found at the bottom of tlib.php. Enjoy!
 * 
 * <h2>License</h2>
 *
 * TLib-PHP is released under the <a href="http://www.gnu.org/copyleft/gpl.html"> General Public License</a> (LGPL). 
 */

/**
 * @brief String manipulation class.
 * 
 * Various string manipulation stuff. Please note that this is NOT A GENERIC STRING CLASS! This may also very well clash with other user libraries.
 */
class TLString
{
	/**
	 * @brief Split string using a seperator and assign the resulting parts to variables.
	 * @param $string (string) String to split.
	 * @param $seperator (char) Character to split on.
	 * @param $vars (ass. array) Associative array containing pass-by-reference variables to which to asign the parts of the exploded string. Number of vars must be equal to the number of parts that are the result of the split (or false will be returned).
	 * @returns True if the parts were succesfully assigned to the variables in $var. False if otherwise.
	 * @post The values of the variables in $var will be assigned the parts that resulted from the splitting of $string.
	 * @note Variables in $vars must be passed by reference. That is: array(&$var1, &$var2), not array($var1, $var2)!
	 */
	public static function explodeAssign($string, $seperator, $vars) 
	{
		$temp = explode($seperator, $string);
		if (count($temp) != count($vars)) {
			return(false);
		} else {
			for ($i = 0; $i != count($temp); $i++) {
				$vars[$i] = $temp[$i];
			}
			return(true);
		}
	}

	/**
	 * @brief Assign the elements in an array to separate variables.
	 * @param $array (array) Array of which to assign elements
	 * @param $vars (ass. array) Associative array containing pass-by-reference variables to which to asign the parts of the exploded string. Number of vars must be equal to the number of parts that are the result of the split (or false will be returned).
	 * @returns True if the parts were succesfully assigned to the variables in $var. False if otherwise.
	 * @post The values of the variables in $var will be assigned the values of the elements in $array
	 * @note Variables in $vars must be passed by reference. That is: array(&$var1, &$var2), not array($var1, $var2)!
	 */
	public static function arrayAssign($array, $vars) 
	{
		if (count($array) != count($vars)) {
			return(false);
		} else {
			for ($i = 0; $i != count($array); $i++) {
				$vars[$i] = $array[$i];
			}
			return(true);
		}
	}

	/**
	 * @brief Check if a string consists of (and only of) a bunch of characters.
	 * @param $string (string) String to check. Must be a string otherwise return value may not be correct.
	 * @param $chars (string) Collection of character to check for. Ranges can be defined by using a-z. 
	 * @returns True if $string consists of (and only of) characters in $chars. False otherwise.
	 * @note Case-sensitive.
	 * @note If you want to check for a dash ('-'), you must escape it: isOfChars("-hey-", "a-z\-")
	 */
	public static function isOfChars($string, $chars) 
	{
		return(!preg_match("/[^".preg_quote($chars, '/')."]/", $string));
	}

	/**
	 * @brief Check if a string starts with a certain other string.
	 * @param $string (string) String to check start of. 
	 * @param $start (string) Start value.
	 * @returns True if $string starts with $start. False otherwise.
	 * @note Case-sensitive.
	 */
	public static function startsWith($string, $start) 
	{
		if (strlen($string) >= strlen($start)) {
			if (substr($string, 0, strlen($start)) == $start) {
				return(true);
			}
		}
		return(false);
	}

	/**
	 * @brief Check if a string ends with a certain other string.
	 * @param $string (string) String to check end of. 
	 * @param $end (string) End value.
	 * @returns True if $string ends with $end. False otherwise.
	 * @note Case-sensitive.
	 */
	public static function endsWith($string, $end) 
	{
		if (strlen($string) >= strlen($end)) {
			if (substr($string, strlen($string)-strlen($end)) == $end) {
				return(true);
			}
		}
		return(false);
	}
}

/**
 * @brief Variable manipulation and tool class.
 * 
 * Various variable manipulation and checking class. 
 */
class TLVars
{
	/**
	 * @brief Check if a variable is empty. This does roughly the same as PHP's empty() function but works on indirect variables.
	 * @param $var (mixed) Variable to check.
	 * @returns True if $var is 'empty'. "", null, false, 0 and an empty array are considered empty (strict type checking is enforced (===)). False if not empty.
	 * @note To supress 'Undefined variable' errors, you should prepend the call to this method with an '@'.
	 */
	public static function isEmpty($var = null) 
	{
		if ($var === "" || $var === null || $var === false || $var === 0 || $var === array()) {
			return(true);
		}
		return(false);
	}

	/**
	 * @brief Use this function to bring the value of a variable from the client side/serverside into the current scope. This function tries to safely implement a kind of register_globals.
	 * @param varName (string) The name of the variable to import.
	 * @param from (string) Specifies the source from where to import the variable. I.e. cookies, GET, POST, etc. It should be in the form of s string containing one or more of the chars in 'SPGC'. Last char in the string overrides previous sources.
	 * @param default (string) When no value is found in sources, assign this value.
	 * @pre Use of 'S' in From requires session_start() to already be called
	 * @returns Contents of the variable as gotten from the last valid source described by $From. If VarName was not found in any of the specified sources, this function returns 'undefined', and the the var to which assignment is done should also be undefined.
	 * @note The behaviour of an assignment from a function which doesn't return anything is not specified I believe. Results may vary.
	 */
	public static function import($varName, $from, $default = "") {
		$i = $c = "";
		$varValue = FALSE;

		for ($i = 0; $i < strlen($from); $i++) {

			$c = $from[$i];

			switch ($c) {
				case 'F' :
					if (array_key_exists($varName, $_FILES)) {
						$varValue = $_FILES[$varName];
					}
					break;
				case 'S' :
					if (array_key_exists($varName, $_SESSION)) {
						$varValue = $_SESSION[$varName];
					}
					break;
				case 'P' :
					if (array_key_exists($varName, $_POST)) {
						$varValue = $_POST[$varName];
					}
					break;
				case 'G' :
					if (array_key_exists($varName, $_GET)) {
						$varValue = $_GET[$varName];
					}
					break;
				case 'C' :
					if (array_key_exists($varName, $_COOKIE)) {
						$varValue = $_COOKIE[$varName];
					}
					break;
				default: break;
			}
		}

		if ($varValue === FALSE) {
			if ($default != "") {
				return ($default);
			} else {
				return(null); // Not defined.
			}
		} else {
			return ($varValue);
		}
	}
}

/**
 * @brief Additional control structure tools
 */
class TLControlStruct
{
	/**
	 * @brief Check if the calling script is included or if it is the main script being run. Comes in handy when you need to determine whether to run tests in a library or not.
	 * @param $from (string) Pass the __FILE__ constant to this variable when you call this method.
	 * @returns True if the caller is the main script. False  if it's just an included file.
	 */
	public static function isMain($from) 
	{
		if ($from == realpath($_SERVER["SCRIPT_FILENAME"])) {
			return(true);
		} else {
			return(false);
		}
	}
}

/**
 * @brief Very simple unit tester.
 * 
 * This class can be used as a very simple Unit tester. It simply runs all
 * methods on a class (both static and non-static) and catches all thrown
 * exceptions and PHP errors. Alternatively, you can call
 * <tt>$test->assert(ASSERTION);</tt> from the methods in the class to test for
 * things.
 *
 * If the test class you pass to the constructor contains a property named
 * 'testNames', it will be used to display pretty names for the methods (tests)
 * in the class. testNames should be public, should be an associative array
 * where the keys match the methodnames  and should look like this: 
 * <tt>public $testNames = array("GroupName_TestName" => "test the foobar", ...); </tt>
 *
 * For each called method in the class to be tested, the first parameter passed
 * to the method will be the Tester class. You can use this to assert things
 * from your test case like this: 
 * <tt>public function MyTestCase($test) { $test->assert(a != b); }</tt>
 * 
 * You can subdivide test methods in groups by putting underscores in the
 * method names. Methods are called in the order as they appear in the test
 * class but may be sorted by group in the final results.
 *
 * An example test class could look like this:
 *
 * <pre>
 * class TestMe {
 *   public $testNames = array(
 *     "User_Load" => "Load a user",
 *     "User_Save" => "Save a user",
 *     "Grant_User" => "Give rights to a user",
 *     "Grant_Group" => "Give rights to a group",
 *   );
 *  
 *   public function User_Load($test) {
 *     $this->user = new MyProgramUser("john");
 *     $test->assert($this->user->getUserId() == "john");
 *   }
 *   public function User_Save($test) {
 *     $this->user->save();
 *   }
 *   public function Grant_User($test) {
 *     $this->user->grantPrivilge(READ);
 *   }
 *   public function Grant_Group($test) {
 *     $this->user->group->grantPrivilge(READ);
 *   }
 *   public function User_Load_NonExisting($test) {
 *     $test->failed(new Exception("Non existing user loaded."));
 *     try {
 *       new MyProgramUser("AmeliaEarhart");
 *     catch (Exception $e) {
 *       $test->passed();
 *     }
 *   }
 * }
 * </pre>
 *
 * Reports can be generated from the results using the ->dumpFoo() methods.
 */
class TLUnitTest {

	private $appName = "";
	private $cnt = 1;
	private $testOutput = array();
	private $testResults = array();
	private $currentTest = array();
    private $otherErros = "";

	/**
	 * @brief Create a new Unit test controller
	 * @param $appName (string) The name of the application you're testing.
	 * @param $testClass (string or object instance) An instance of or name (when only using static methods) of a class to test.
	 */
	public function __construct($appName, $testClass) {
		error_reporting(E_ALL);
		set_error_handler(array(&$this, "errorHandler"));
		$this->appName = $appName;
		$this->testClass = $testClass;
		$this->run($this->testClass);
	}

	/**
	 * @brief The custom error handler. Don't use this.
	 * @note DO NOT USE THIS, ITS FOR INTERNAL USE ONLY.
	 */
	public function errorHandler($errno, $errmsg, $filename, $linenum, $vars) {
        if ($this->currentTest == array()) {
            //$this->otherErrors .= "$filename($linenum): Error $errno: '$errmsg': ".var_export($vars, true)."\n";
            $this->otherErrors .= "$filename($linenum): Error $errno: '$errmsg'\n";
        } else {
            $e = new Exception($errmsg, $errno);
            $this->failed($e);
        }
	}

	/**
	 * @brief The custom exception handler. Don't use this.
	 * @note DO NOT USE THIS, ITS FOR INTERNAL USE ONLY.
	 */
	public function exceptionHandler($e) {
		$this->failed($e);
	}

	private function run($testClass) {
        $props = get_object_vars($this->testClass);
        if (array_key_exists("testNames", $props)) {
            $hasNames = true;
        } else {
            $hasNames = false;
        }
		foreach(get_class_methods($this->testClass) as $method) {
			if (!TLString::explodeAssign($method, "_", array(&$group, &$name))) {
				$group = "";
				$name = $method;
			}
			if ($hasNames && array_key_exists($method, $this->testClass->testNames)) {
				$name = $this->testClass->testNames[$method];
			}

			// Start the test
			$this->start($group, $name);
			try {
				call_user_func(array($testClass, $method), $this);
			} catch(Exception $e) {
				$this->exceptionHandler($e);
			}
			$this->end();
		}
	}

	private function start($testGroup, $testName) {
		$this->currentTest = array(
			"group"  => $testGroup,
			"nr"     => $this->cnt++,
			"name"   => $testName,
			"result" => "",
            "dump"   => "",
		);
		$this->passed();
	}

	/**
	 * @brief Mark a single test as having passed. 
	 */
	public function passed() {
		$this->currentTest["passed"] = true;
		$this->currentTest["result"] = "";
		$this->currentTest["dump"] = "";

		$this->currentTest = array_merge($this->currentTest, array(
			"passed" => true,
			)
		);
	}

	/**
	 * @brief Mark a single test as having failed. It will mark the test as such and generate a (useable) backtrace.
	 * @param $e (Exception) The exception that occured.
	 * @note This is also called by the custom error handler, which mimics a thrown exception
	 * @note You can safely call this method to set the default pass/fail status of a test method and later on in the test mark it as having passed.
	 */
	public function failed($e) {
		$this->currentTest["passed"] = false;
		$this->currentTest["result"] .= $e->getMessage()."\n";
        $this->currentTest["dump"] .= $e->getMessage()."\n";
		foreach($e->getTrace() as $stackFrame) {
			if (array_key_exists("file", $stackFrame)) {
				$this->currentTest["dump"] .= "  ".
					$stackFrame["file"] . ":".$stackFrame["line"]." - ";
					if (array_key_exists("class", $stackFrame)) {
						$this->currentTest["dump"] .= $stackFrame["class"];
					}
					if (array_key_exists("type", $stackFrame)) {
						$this->currentTest["dump"] .= " ".$stackFrame["type"]." ";
					}
					if (array_key_exists("function", $stackFrame)) {
						$this->currentTest["dump"] .= $stackFrame["function"]."(";
					}
					if (array_key_exists("args", $stackFrame) && count($stackFrame["args"] > 0)) {
						foreach($stackFrame["args"] as $arg) {
							$this->currentTest["dump"] .= $arg.", ";
						}
                        if (substr($this->currentTest["dump"], -2) == ", ") {
                            $this->currentTest["dump"] = substr($this->currentTest["dump"], 0, -2);
                        }
					}
					if (array_key_exists("function", $stackFrame)) {
						$this->currentTest["dump"] .= ")";
					}
					$this->currentTest["dump"] .= "\n";
			}
		}
		$this->currentTest["dump"] .= "\n";
	}

	/**
	 * @brief Assert a boolean expression. If it returns 'true', the test will be marked as passed. Otherwise it will be marked as failed.
	 * @param $bool (Boolean) Boolean result of an expression.
	 */
	public function assert($bool) {
		if ($bool) {
			$this->passed();
		} else {
			throw new Exception("Assertion failed");
		}
	}

	private function end() {
		$this->testResults[] = $this->currentTest;
		$this->currentTest = array();
	}

	private function sortResultsByGroup() {
		$cmp = create_function('$a,$b', '
			if ($a["group"] == $b["group"]) {
				return (0); 
			}; 
			return($a["group"] > $b["group"] ? -1 : 1);
		');
		usort($this->testResults, $cmp);
	}

	/**
	 * @brief Generate a beautiful dump of the test results in HTML format. This outputs a single HTML page.
	 * @param $hidePassed (Boolean) If true, passed tests will not be shown. This is useful when you've got alot of testcases and only want the failed ones to show up.
	 * @param $sortGroups (Boolean) If true, the results will be sorted by group. Otherwise the results will be listed in the same order they where executed.
	 */
	public function dumpHtml($hidePassed = false, $sortGroups = false) {
		if ($sortGroups) { 
			$this->sortResultsByGroup();
		}
		$out = "
			<html>
				<body>
					<style>
						body { font-family: sans-serif; }
						table { border: 1px solid #000000; }
						th { empty-cells: show; font-family: sans-serif; border-left: 1px solid #FFFFFF; border-top: 1px solid #FFFFFF; border-bottom: 1px solid #000000; border-right: 1px solid #000000; margin: 0px; font-size: x-small; color: #FFFFFF; background-color: #404040; padding: 2px 4px 2px 4px; }
						td { empty-cells: show; font-family: sans-serif; border-bottom: 1px solid #909090; margin: 0px; padding: 2px 4px 2px 4px; border-left: 1px solid #E0E0E0; font-size: x-small; }
						div.dump { background-color: #F0F0F0; }
						a.dump { text-decoration: underline; cursor: pointer; }
					</style>
				</body>
				<h1>Test results for ".$this->appName."</h1>
				<h2>Test results</h2>
				<table cellspacing='0' cellpadding='0'>
					";
					$prevResult = $this->testResults[0];
					$nrOfPassed = 0;
					$nrOfFailed = 0;
					foreach ($this->testResults as $result) {
						if ($result["passed"] == false) {
							$nrOfFailed++;
							$textResult = "FAILED";
							$rowColor = "#FF0000";
							$dump = "<a class='dump' onclick='document.getElementById(\"dump_".$result["nr"]."\").style.display=\"block\"'>Dump</a><div style='border: 1px solid #000000; background-color: #F0F0F0; display:none;' class='dump' id='dump_".$result["nr"]."'><pre>".$result["dump"]."</pre></div>";
						} else {
							$nrOfPassed++;
							if ($hidePassed) {
								continue;
							}
							$textResult = "passed";
							$rowColor = "#50FF00";
							$dump = "";
						}
						if ($result["group"] != $prevResult["group"]) {
							$out .= "
							<tr valign='top' align='left'>
								<td colspan='6'></td>
							</tr>\n";
						}
						$out .= "
							<tr valign='top' align='left'>
								<th>".$result["nr"]."</th>
								<th>".$result["group"]."</th>
								<th>".$result["name"]."</th>
								<td bgcolor='".$rowColor."'>".$textResult."</td>
								<td>".str_replace("\n", "<br />\n", $result["result"])."</td>
								<td>".$dump."</td>
							</tr>\n";

						$prevResult = $result;
					}
					$out .= "
				</table>
				<h2>Total results</h2>
				<table cellspacing='0' cellpadding='0'>
					<tr><th>Passed:</th><td>".$nrOfPassed."</td></tr>
					<tr><th>Failed:</th><td>".$nrOfFailed."</td></tr>
				</table>
				<h2>Non-testcase errors</h2>
                <pre>\n".$this->otherErrors."
                </pre>
			</html>
		";

		return($out);
	}

	/**
	 * @brief Generate a beautiful dump of the test results in Text format. 
	 * @param $hidePassed (Boolean) If true, passed tests will not be shown. This is useful when you've got alot of testcases and only want the failed ones to show up.
	 */
	public function dumpPrettyText($hidePassed = false) {
		$fmt = "%3s | %-60s | %-6s | %s\n";
		$sep = "%3s-+-%-60s-+-%-6s-+-%s\n";

		$out = "";
		$out .= sprintf($fmt, "Nr", "Test", "Passed", "Result");
		$out .= sprintf($sep, str_repeat('-', 3), str_repeat('-', 60), str_repeat('-', 6), str_repeat('-', 40));
		foreach ($this->testResults as $result) {
			if ($result["passed"] == false) {
				$textResult = "FAILED";
			} else {
				if ($hidePassed) {
					continue;
				}
				$textResult = "passed";
			}
			$out .= sprintf($fmt, $result["nr"], $result["group"].":".$result["name"], $textResult, $result["result"]);
		}
		return($out);
	}
}

error_reporting(E_ALL);

/* Perform a bunch of tests/examples if we're the main script */
if (TLControlStruct::isMain(__FILE__)) {
	//###########################################################################
	// TLString
	//###########################################################################
	// TLString::explode()
	if (!TLString::explodeAssign("Peterson:Pete:25", ':', array(&$last,&$first,&$age))) {
		$last = "Doe";
		$first = "John";
		$age = "38";
	}
	print ("Hello $first $last. You are $age years old.\n");
	//---------------------------------------------------------------------------
	// TLString::isOfChars()
	$username = "-^myusername102^-";
	if (!TLString::isOfChars($username, "a-z0-9")) {
		print ("Invalid username\n");
	}
	//---------------------------------------------------------------------------
	// TLString::startsWith()
	if (TLString::startsWith("Hello", "He")) {
		print ("'Hello' starts with 'He'\n");
	}
	//---------------------------------------------------------------------------
	// TLString::endsWith()
	if (TLString::endsWith("Hello", "lo")) {
		print ("'Hello' ends with 'lo'\n");
	}

	//###########################################################################
	// TLVars
	//###########################################################################
	// TLVars::isEmpty()
	if (@TLVars::isEmpty(false)) { print ("Empty\n"); }
	if (@TLVars::isEmpty($ape)) { print ("Empty\n"); } /* $ape is undefined */
	//---------------------------------------------------------------------------
	// TLVars::import()
	$foo = TLVars::import("foo", "GP", "default value");
	print($foo."\n");

	//###########################################################################
	// TLControlStruct
	//###########################################################################
	// TLControlStruct::isIncluded()
	if (TLControlStruct::isMain(__FILE__)) {
		print ("We are the main script!\n");
	} else {
		print ("We are merely being included\n");
	}
	//###########################################################################
	// TLUnitTest
	//###########################################################################
	// TLControlStruct::isIncluded()
	class TestMe {
		public $testNames = array(
			"User_Load" => "Load user",
			"Group_AddUser" => "Add user to group",
			"FailingCase" => "Deliberitely fail",
		);
	 
		public function User_Load($test) {
			$this->user = "john";
			$test->assert($this->user == "john");
		}
		public function Group_AddUser($test) {
			$this->group[] = $this->user;
		}
		public function FailingCase($test) {
			throw new Exception("This testcase will fail");
		}
	}
	$cases = new TestMe();
	$test = new TLUnitTest("ExampleTest", $cases);
	print($test->dumpPrettyText());
}
?>
