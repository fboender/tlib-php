<?php

// vim: set noexpandtab tabstop=4 shiftwidth=4 softtabstop=4:

/**
 * @mainpage
 * This is TLib-PHP, a collection of PHP classes/functions/stuff that I (Ferry Boender) deem useful. 
 *
 * Examples on how to use the various classes and methods are found at the bottom of tlib.php. Enjoy!
 * 
 * <h2>Web</h2>
 * - TLSelfURL: Helper class for referring to the current page (location).
 * - TLWebControl: Simple website controller framework with OO, views and templates.
 * 
 * <h2>Exceptions</h2>
 * - TLTypeException: Throw this when a passed parameter is not of the expected type.
 * - TLValueException: Throw this when a passed parameter does not have a correct value.
 * - TLSQLException: Throw this when your program encounters an SQL error.
 * 
 * <h2>Information manipulation classes</h2>
 * - TLString: Additional string manipulation methods.
 * - TLVars: Variable manipulation class.
 * - TLNetwork: Network information manipulation class.
 * - TLPath: Helpers for constructing paths.
 * 
 * <h2>Development helpers</h2>
 * - TLValidate: Variable contents validation.
 * - TLDebug: Additional debugging stuff.
 * - TLUnitTest: Very simple Unit Tester.
 * 
 * <h2>Misc</h2>
 * - TLControlStruct: Additional control structure tools.
 * 
 * TLib-PHP is released under the <a href="http://www.gnu.org/copyleft/gpl.html"> General Public License</a> (LGPL). 
 */

/////////////////////////////////////////////////////////////////////////////
// Web
/////////////////////////////////////////////////////////////////////////////

/**
 * @brief Self-referring URL helper class
 *
 * This class provides various methods for refering to the current
 * server/host/URL/etc. It provides abstractions against HTTP/HTTPS usage,
 * ports and parameters. 
 *
 * Examples:
 * @code
 * $s = new SelfURL();
 * print ($s->getServerURL());
 * print ($s->getAbsolutePathURL());
 * print ($s->getAbsoluteScriptURL());
 * print ($s->getAbsoluteFullURL());
 * print ($s->getAbsoluteFullURL("strip_"));
 * print ($s->getRelativePathURL());
 * print ($s->getRelativeScriptURL());
 * print ($s->getRelativeFullURL());
 * print ($s->getRelativeFullURL("strip_"));
 * @endcode
 *
 * With the following URL: http://example.com/svcmon/class.url.php?name=ferry&age=26&strip_foo=bar
 * Outputs:
 * <pre>
 * http://example.com
 * http://example.com/svcmon
 * http://example.com/svcmon/class.url.php
 * http://example.com/svcmon/class.url.php?name=ferry&age=26&strip_foo=bar
 * http://example.com/svcmon/class.url.php?name=ferry&age=26
 * /svcmon
 * /svcmon/class.url.php
 * /svcmon/class.url.php?name=ferry&age=26&strip_foo=bar
 * /svcmon/class.url.php?name=ferry&age=26
 * </pre>
 */
class TLSelfURL {
	
	public $type = "";     /**< Type of the URL (http or https) */
	public $host = "";     /**< The hostname as found in the URL */
	public $port = "";     /**< The port that was used to connect */
	public $path = "";     /**< The full path to the script without the script name itself */
	public $script = "";   /**< The script name */
	public $params = null; /**< Any parameters that where given on the URL */

	/**
	 * @brief Constructor.
	 */
	public function __construct() {
		if (array_key_exists("HTTPS", $_SERVER)) {
			$this->type = "https";
		} else {
			$this->type = "http";
		}

		// Get raw data
		if (!array_key_exists("HTTP_HOST", $_SERVER)) {
			throw new Exception("Not running on a HTTP server");
		}

		$this->host = $_SERVER["HTTP_HOST"];
		$this->port = $_SERVER["SERVER_PORT"];
		$this->path = dirname($_SERVER["PHP_SELF"]);
		$this->script = basename($_SERVER["PHP_SELF"]);

		$this->params = $_REQUEST;
		if (array_key_exists("PHPSESSID", $this->params)) {
			// Work around PHP's idiotic definition of developer-friendly nonsense.
			unset($this->params["PHPSESSID"]);
		}

		// Verify and correct raw data
		if (strlen($this->path) == 0 || $this->path[0] != '/') {
			$this->path = '/'.$this->path;
		}
		if ($this->path[strlen($this->path) - 1] == '/') {
			$this->path = substr($this->path, 0, strlen($this->path) - 1);
		}
		
		assert($this->self_check());
	}

	/**
	 * Returns the server URL. I.e. "https://example.com". If a non-default
	 * port was used (not 80 for http or not 443 for https), the port number
	 * will be appended to the URL.
	 * @returns (string) Server URL
	 */
	public function getServerURL() {
		$serverURL = $this->type."://".$this->host;
		if ($this->port != "80" && $this->port != "443") {
			$serverURL .= ":" . $this->port;
		}
		return($serverURL);
	}

	/**
	 * Returns the absolute URL upto and including the path to the script (but
	 * not the script name itself). I.e. "http://example.com/svcmon". Does not
	 * append a trailing backslash.
	 * @returns (string) Server URL + Pathname
	 */
	public function getAbsolutePathURL() {
		$pathURL = $this->getServerURL();
		$pathURL .= $this->path;
		return($pathURL);
	}

	/**
	 * Returns the relative (to the Server URL) URL upto and including the path
	 * to the script (but not the script name itself). I.e. "/svcmon". Does not
	 * append a trailing slash. Always starts with a slash.
	 * @returns (string) Pathname
	 */
	public function getRelativePathURL() {
		$pathURL = $this->path;
		return($pathURL);
	}

	/** 
	 * Returns the absolute URL upto and including the script name. I.e.
	 * "http://example.com/svcmon/class.url.php". 
	 * @returns (string) Server URL + Pathname + Scriptname.
	 */
	public function getAbsoluteScriptURL() {
		$scriptURL = $this->getServerURL();
		$scriptURL .= $this->path . '/' . $this->script;
		return($scriptURL);
	}

	/** 
	 * Returns the relative (to the Server URL) URL upto and including the
	 * script name. I.e.  "/svcmon/class.url.php". Always starts with a slash.
	 * @returns (string) Server URL + Pathname + Scriptname.
	 */
	public function getRelativeScriptURL() {
		$scriptURL = $this->path . '/' . $this->script;
		return($scriptURL);
	}

	/**
	 * Returns a serialized form of the parameters of the URL. I.e. :
	 * "name=ferry&age=26&strip_foo=bar". If stripVarsPrefix is set, any
	 * variable starting with $stripVarsPrefix will not be included in the
	 * serialized parameters.
	 * @param stripVarsPrefix (string) Prefix of variables that need to be removed from the final result.
	 * @returns Serialized parameters
	 */
	public function getParams($stripVarsPrefix = null) {
		$paramsString = "";

		// Only process params if there are any
		if (count($this->params) > 0) {
			// Create a duplicate of the params and remove unwanted ones
			if ($stripVarsPrefix != null) {
				$vars = $this->params;
				$prefixLen = strlen($stripVarsPrefix);
				foreach(array_keys($vars) as $key) {
					if (substr($key, 0, $prefixLen) == $stripVarsPrefix) {
						unset($vars[$key]);
					}
				}
				$paramsString .= "?".http_build_query($vars);
			} else {
				$paramsString .= "?".http_build_query($this->params);
			}
		}

		return($paramsString);
	}

	/** 
	 * Returns the absolute URL upto and including the serialized parameters. I.e.
	 * "http://example.com/svcmon/class.url.php?name=ferry&age=26&strip_foo=bar". 
	 * @returns (string) Server URL + Pathname + Scriptname + Serialized Params.
	 */
	public function getAbsoluteFullURL($stripVarsPrefix = null) {
		$fullURL = $this->getAbsoluteScriptURL();
		$fullURL .= $this->getParams($stripVarsPrefix);

		return($fullURL);
	}

	/** 
	 * Returns the relative URL upto and including the serialized parameters. I.e.
	 * "/svcmon/class.url.php?name=ferry&age=26&strip_foo=bar". Always starts
	 * with a slash.
	 * @returns (string) Pathname + Scriptname + Serialized Params.
	 */
	public function getRelativeFullURL($stripVarsPrefix = null) {
		$fullURL = $this->getRelativeScriptURL();
		$fullURL .= $this->getParams($stripVarsPrefix);
		return($fullURL);
	}

	/**
	 * Verify that this object was constructed properly and that all values are
	 * intelligible. This is a purely defensive-coding practice. It can be
	 * turned off once this code is considered stable (which is never).
	 */
	private function self_check() {
		if (
			$this->type == "" || 
			$this->host == "" || 
			$this->port == "" || 
			$this->path == "" || 
			$this->script == "" || 
			$this->params == "") {
				return(false);
		} else {
			return(true);
		}
	}
}

class TLWebControlException extends Exception { }
/**
 * @brief Simple website controller framework with OO, views and templates.
 * 
 * TLWebControl offers a small, simple framework for creating websites. It
 * offers an Object Oriented approach to defining the various parts of your web
 * application (actions). It also has templates and views. It handles the
 * calling of actions, passing variables from the webserver to the various
 * actions, views and templates.
 *
 * To use this web control simply create a class that extends the TLWebControl
 * class. 
 *
 * The _init() method can be used to intialize your web application. You MUST 
 * implement this method. 
 *
 * Next, you can create methods in the derived class which will correspond to
 * the actions that can be called on your interactive website. If you specify
 * parameters in the methods you define, TLWebControl will automatically scan
 * the POST and GET variables to see if the variables are available. GET
 * variables will override POST variables. If you need variables from outside
 * POST and GET variables, just use the super globals. All output from the
 * actions (such as print, etc) will be captured in the output buffer. You 
 * can access the output buffer using $this->_getOutputBuffer().
 *
 * Views can be used to create an abstraction between application logic and
 * presentation. Simply call $this->_view(filename) in a method in the derived
 * class to load and execute the view. Variables can be offered to the view by
 * setting $this->variableName. The view can than access these using
 * $this->variableName.
 *
 * When you instantiate the derived class, you can assign templates and then
 * get the output using _getOutput(). A template can include the output of 
 * your application by displaying the return value of $this->_getOutputBuffer().
 *
 * Here's a little example to get you started:
 *
 * <b>index.php</b>
 * @code
 * <?
 *     
 * include("tlib.php");
 * 
 * class WebTest extends TLWebControl
 * {
 *     public function _init($action) {
 *         $this->appName = "Name processor";
 *     }
 * 
 *     public function getName() {
 *         $this->title = "Enter your name";
 *         $this->_view("getName.php");
 *     }
 * 
 *     public function processName($name) {
 *         $this->title = "Name processed";
 *         $this->name = $name;
 *         $this->_view("processName.php");
 *     }
 * }
 * 
 * try {
 *     $wt = new WebTest("getName");
 *     $wt->_setTemplateFromFile("template.php");
 *     print($wt->_getOutput());
 * } catch (TLWebControlException $e) {
 *     print("Internal appliation error.");
 * }
 * 
 * ?>
 * @endcode
 * 
 * <b>template.php</b>
 * @code
 * <html>
 *   <head><title><?=$this->appName?> - <?=$this->title?></title></head>
 *   <body>
 *     <h1><?=$this->appName?></h1>
 *     <h2><?=$this->title?></h2>
 *     <?=$this->_getOutputBuffer()?>
 *   </body>
 * </html>
 * @endcode
 *
 * <b>getName.php</b>
 * @code
 * <form name='name'>
 *     <input type='hidden' name='action' value='processName' />
 *     <input type='text' name='name' value='' />
 *     <input type='submit' value='Process my name' />
 * </form>
 * @endcode
 *
 * <b>processName.php</b>
 * @code
 * Ah, so your name is <b><?=$this->name?></b> huh? Well <?=$this->name?>, welcome
 * to this application. Wanna try it <a href="?action=getName">again</a>?
 * @endcode
 *
 * You can have multiple classes that extend TLWebControl. If an action
 * contains a dot, the TLWebControl class called will find a class that can
 * handle that action and delegate the action to it. This can be nested as deep
 * as you want. You can access the delegating (parent) class using
 * $this->parent. Tip: Use the autoloader functionality. For example:
 *
 * @code
 * class Addresslist extends TLWebControl
 * {
 *     public function _init($action) {
 *     }
 *     public function showlist() {
 *         print("An addressbook listing");
 *     }
 * }
 * class Addressbook extends TLWebControl
 * {
 *     public function _init($action) {
 *     }
 * }
 * $ab = new Addressbook("Addresslist.showlist");
 * $output = $ab->_getOutput();
 * @endcode
 *
 * $output will contain "An addressbook listing".
 */
abstract class TLWebControl
{
	private $defaultAction;
	private $action;
	private $outputBuffer = "";
	private $templateContents = '<?=$this->_getOutputBuffer(); ?>';
	private $templateFile = null;
	protected $parent = null;  /**< Parent class if action was delegated */

	/**
	 * @brief Create a new TLWebControl framework.
	 * @param $defaultAction (string) The default action to run if no action has been specified.
	 * @param $parent (object) TLWebControl object that delegated the current action to us.
	 */
	public function __construct($defaultAction, $parent = null) {
		// The parent can be another TLWebControl class that delegated the
		// handling of the action to this TLWebControl. If so, we don't need to
		// find a default action but can just use what's been passed to us.
		if ($parent != null) {
			$this->parent = $parent;
			$action = $defaultAction;
		} else {
			// Find the current action
			$this->defaultAction = $defaultAction;
			$action = TLVars::import("action", "GP", $defaultAction);
		}

		// Call the init function so the implementor can initialize sessions 'n
		// stuff.
		$this->_init($action);

		// Run the action
		$this->_action($action);
	}

	/**
	 * @brief Run an action that's defined in this or another web control.
	 * @param $action (string) The action to run.
	 */
	public function _action($action) {
		$output = "";

		if (strpos($action, '.') !== false) {
			// Actions with a dot in them have a seperate class that implements
			// TLWebControl to handle the actions. Try to find an class that is
			// supposed to handle this action and then delegate handling the
			// action to that class.
			$parts = explode('.', $action);
			if (class_exists($parts[0]) && get_parent_class($parts[0]) == "TLWebControl") {
				$className = array_shift($parts);
				$c = new $className(implode('.', $parts), $this);
				$output = $c->_getOutput();
			} else {
				// Cannot find an object which can handle the action.
				throw new TLWebControlException("There is no object for the requested action '".$action."'", 2);
			}
		} else {
			// We map requested actions directly to methods defined in the derived
			// class.
			if (method_exists($this, $action)) {
				// Check the requested action to see if it's valid. Valid actions
				// are methods that have been defined and implemented by the class
				// that's extending a TLWebControl class. In other words, you can't
				// call methods that are native to TLWebControl.
				$rClass = new ReflectionClass('TLWebControl');
				foreach($rClass->getMethods() as $method) {
					if ($action == $method->name) {
						throw new TLWebControlException("Cannot call an internal method", 1);
					}
				}

				// Okay, we can continue with running the requested action. Inspect
				// the parameters for the method that corresponds to the action so we
				// can automatically pass them.
				$params = array();
				$rObj = new ReflectionObject($this);
				$rMethod = $rObj->getMethod($action);
				foreach($rMethod->getParameters() as $param) {
					$paramName = $param->getName();
					$paramValue = TLVars::import($paramName, "PG");
					if (!$paramValue) {
						if ($param->isDefaultValueAvailable()) {
							$paramValue = $param->getDefaultValue();
						} else {
							throw new TLWebControlException("Missing data '$paramName' for action '$action'", 4);
						}
					}
					$params[$paramName] = $paramValue;
				}

				// Call the method with the parameters.
				ob_start();
				call_user_func_array(array($this, $action), $params);
				$output = ob_get_clean();
			} else {
				// Cannot map the action to a method, because it does not exist
				throw new TLWebControlException("There is no method for the requested action '".$action."'", 3);
			}
		}
		$this->outputBuffer .= $output;
	}

	/** 
	 * @brief Returns the output of the action.
	 * @return (string) The output of the action.
	 */
	public function _getOutputBuffer() {
		return($this->outputBuffer);
	}

	/**
	 * @brief Returns the output of the template, including the output of the action.
	 * @return (string) Parsed template output.
	 */
	public function _getOutput() {
		ob_start();
		// FIXME: Syntax check eval using Parsekit
		eval("?>".$this->templateContents);
		$out = ob_get_clean();
		return($out);
	}

	/**
	 * @brief Parse a view
	 * @param $filename (string) Filename of the view to parse.
	 */
	public function _view($filename) {
		$contents = @file_get_contents($filename);
		if ($contents === false) {
			throw new TLWebControlException("Cannot read view from file '$filename'", 6);
		}
		// FIXME: Syntax check eval using Parsekit
		eval("?>".$contents);
	}

	/**
	 * @brief Parse a view.
	 * @param $contents (string) String (view) to parse.
	 */
	public function _viewString($contents) {
		// FIXME: Syntax check eval using Parsekit
		eval("?>".$contents);
	}

	/**
	 * @brief Set a template from the contents of a file.
	 *
	 * Templates can be used to give your website / application a generic look and feel. Templates won't be parsed until _getOutput() is called, so they can include the output of, i.e. actions.
	 * @param $filename (string) Filename of the template to use.
	 */
	public function _setTemplateFromFile($filename) {
		$contents = @file_get_contents($filename);
		if ($contents === false) {
			throw new TLWebControlException("Cannot read template '$filename'", 5);
		}
		$this->templateFilename = $filename;
		$this->templateContents = $contents;
	}

	/**
	 * @brief Set a template from a string
	 *
	 * Templates can be used to give your website / application a generic look and feel. Templates won't be parsed until _getOutput() is called, so they can include the output of, i.e. actions.
	 * @param $contents (string) Template contents.
	 */
	public function _setTemplateFromString($contents) {
		$this->templateContents = $contents;
	}

	/**
	* @brief Initialisation method for the web control framework.
	*
	* This method will be automatically called when istantiating a new TLWebControl derived class. Implement this method to init your application. For instance, load session stuff.
	* @param $action (string) The action that will run in the framework.
	*/
	abstract function _init($action);
	
}

/////////////////////////////////////////////////////////////////////////////
// Exceptions
/////////////////////////////////////////////////////////////////////////////

/**
 * @brief Built-in exception for type errors.
 *
 * This exception can be thrown when a variable is expected to have a certain type, but the type does not match.
 * 
 * Example:
 * @code
 * function setAge($age) {
 *   if (!is_int($age)) {
 *     throw new TypeException("int", $age, "age");
 *   }
 *   print("You're age is $age");
 * }
 * function setPerson($person) {
 *   if (!is_object($person) || get_class($person) != "Person") {
 *     throw new TypeException("object(Person)", $person, "person");
 *   }
 * }
 * setAge("fourty");
 * $monkey = new Animal("Monkey");
 * setPerson($monkey);
 * @endcode
 *
 * Results in:
 * 
 * <pre>
 *    Uncaught exception 'TypeException' with message 'Expected "int", got "string(fourty)"' in [stacktrace]
 *    Uncaught exception 'TypeException' with message 'Expected "object(Person)", got "object(Monkey)"' in [stacktrace]
 * </pre>
 */
class TLTypeException extends Exception {

	/**
	 * @brief Construct a new TypeException
	 * @param $expectedType (string) The type you where expecting (int, string, object(ClassType))
	 * @param $gotVar (mixed) The variable you got who's type didn't match.
	 * @param $varName (string) The name of the variable you got who's type didn't match.
	 */
	public function __construct($expectedType, $gotVar, $varName = null) {
		
		$this->expectedType = $expectedType; // The type the caller expected to get
		$this->type = gettype($gotVar);      // The type of the variable which the caller got
		if ($varName != null) {
			$this->name = $varName;              // The name of the variable which the caller got
		}
		if (is_object($gotVar)) {
		$this->value = get_class($gotVar);
		$this->class = get_class($gotVar);   // The class of the variable which the caller got
		} else {
			$this->value = $gotVar;
		}

		if (isset($this->name)) {
			$message = sprintf("Expected \"%s\", got \"%s(%s)\" for variable \"%s\"", $this->expectedType, $this->type, $this->value, $this->name);
		} else {
			$message = sprintf("Expected \"%s\", got \"%s(%s)\"", $this->expectedType, $this->type, $this->value);
		}

		parent::__construct($message);
	}
}

/**
 * @brief Default exception for SQL problems.
 *
 * If a query problem arises (mysql_query() returns false for instance), throw this exception with the mysql_error() and the query as parameters.
 * 
 * Example:
 * @code
 *  $qry = "INSERT INTO foo VALUES (10, 'bar');";
 *  $res = mysql_query($qry);
 *  if (!$res) { throw new SQLException(mysql_error(), $qry); }
 * @endcode
 */
class TLSQLException extends Exception {

	/**
	 * @brief Construct a new SQLException
	 * @param $message (string) The message for the exception. Usually the return value of mysql_error() is passed here.
	 * @param $query (string) The query which contained the caused the error.
	 */
	public function __construct($message, $query = "") {
		$this->message = $message;
		$this->query = $query;
	}

	/**
	 * @brief Return the query that caused this exception to be thrown.
	 * @returns (string) The query that caused this exception to be thrown.
	 */
	public function getQuery() {
		return($this->query);
	}
}

/**
 * @brief Built-in exception for value errors.
 *
 * This exception can be thrown when a variable is expected to have a certain value or adhere to certain criteria, but it does not.
 * 
 * Example:
 * @code
 * function setAge($age) {
 *   if ($age < 5 || $age > 120)) {
 *     throw new ValueException("Age too small or large", $age, "age");
 *   }
 *   print("You're age is $age");
 * }
 * function setEMail($address) {
 *   if (strpos("@", $address) === False) {
 *     throw new ValueException("Should contain '@' char", $address, "address");
 * }
 * setAge(3);
 * setEmail("f dot boender at zx dot nl");
 *
 * try {
 *   setAge(3);
 * } catch (TLValueException $e) {
 *   print($e->getVarMessage());
 * }
 * @endcode
 *
 * Results in:
 * 
 * <pre>
 * Uncaught exception 'TLValueException' with message 'Age too small or large' in [stacktrace]
 * Uncaught exception 'TLValueException' with message 'Should contain '@' char' in [stacktrace]
 * Uncaught exception 'TLValueException' with message 'Age too small or large' in [stacktrace]
 * </pre>
 */
class TLValueException extends Exception {

	/**
	 * @brief Construct a new ValueException 
	 * @param $message (string) The message which describes the value problem.
	 * @param $varValue (mixed) The variable you got which did not follow expectations.
	 * @param $varName (string) The name of the variable (without the $ prepended) you got which did not follow expectations.
	 */
	public function __construct($message, $varValue, $varName) {
		parent::__construct($message);
		$this->varMessage = sprintf("\"$%s(%s)\": %s", $varName, $varValue, $message);
		$this->varName = $varName;
		$this->varValue = $varValue;
	}

	/**
	 * @brief Get a message containing variable information
	 * @returns (string) Full message containing variable information and the message
	 */
	public function getVarMessage() {
		return($this->varMessage);
	}

	/**
	 * @brief Get the name of the variable that caused the error 
	 * @returns (string) The variable name that caused the error (without the leading $)
	 */
	public final function getVarName() {
		return($this->varName);
	}

	/**
	 * @brief Get the contents of the variable that caused the error.
	 * @returns (mixed) The contents of the variable that caused the error.
	 */
	public final function getVarValue() {
		return($this->varValue);
	}
}


/////////////////////////////////////////////////////////////////////////////
// Information manipulation classes
/////////////////////////////////////////////////////////////////////////////

/**
 * @brief String manipulation class.
 * 
 * Various string manipulation stuff. Please note that this is NOT A GENERIC STRING CLASS! This may also very well clash with other user libraries.
 */
class TLString
{
	/**
	 * @brief Split string using a seperator and assign the resulting parts to variables.
	 *
	 * Example:
	 * @code
	 * $foo = "bar,bas,beh";
	 * TLString::explodeAssign($foo, ",", (&bar, &$bas, &$beh) );
	 * @endcode
	 *
	 * @param $string (string) String to split.
	 * @param $seperator (char) Character to split on.
	 * @param $vars (ass. array) Associative array containing pass-by-reference variables to which to asign the parts of the exploded string. Number of vars must be equal to the number of parts that are the result of the split (or false will be returned).
	 * @returns True if the parts were succesfully assigned to the variables in $var. False if otherwise.
	 * @post The values of the variables in $var will be assigned the parts that resulted from the splitting of $string.
	 * @note Variables in $vars must be passed by reference. That is: array(&$var1, &$var2), not array($var1, $var2)!
	 * @note DEPRECATED: Use list(). I.e. list($bar, $bas, $beh) = explode($foo);
	 */
	public static function explodeAssign($string, $seperator, $vars) {
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
	 *
	 * @code
	 * $foo = array('bar', 'bas');
	 * TLString::arrayAssign($foo, (&$bar, &$bas) );
	 * @endcode
	 *
	 * @param $array (array) Array of which to assign elements
	 * @param $vars (ass. array) Associative array containing pass-by-reference variables to which to asign the parts of the exploded string. Number of vars must be equal to the number of parts that are the result of the split (or false will be returned).
	 * @returns True if the parts were succesfully assigned to the variables in $var. False if otherwise.
	 * @post The values of the variables in $var will be assigned the values of the elements in $array
	 * @note Variables in $vars must be passed by reference. That is: array(&$var1, &$var2), not array($var1, $var2)!
	 * @note DEPRECATED: Use list(). I.e. list($bar, $bas) = $foo;
	 */
	public static function arrayAssign($array, $vars) {
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
	public static function isOfChars($string, $chars) {
		return(!preg_match("/[^".preg_quote($chars, '/')."]/", $string));
	}

	/**
	 * @brief Check if a string starts with a certain other string.
	 * @param $string (string) String to check start of. 
	 * @param $start (string) Start value.
	 * @returns True if $string starts with $start. False otherwise.
	 * @note Case-sensitive.
	 */
	public static function startsWith($string, $start) {
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
	public static function endsWith($string, $end) {
		if (strlen($string) >= strlen($end)) {
			if (substr($string, strlen($string)-strlen($end)) == $end) {
				return(true);
			}
		}
		return(false);
	}
}

/**
 * @brief Work with a range between two dates.
 *
 */
class TLDateRange {
    protected $fromTS= null; /**< From timestamp */
    protected $toTS = null;  /**< To timetamp */

	/**
	 * @brief Create a new TLDateRange object.
	 * @param $fromTS (int) Timestamp for date to start with.
	 * @param $toTS (int) Timestamp for date to end with.
	 */
    public function __construct($fromTS, $toTS) {
        $this->fromTS = $fromTS;
        $this->toTS   = $toTS;
        $this->fromDateInfo = getdate($this->fromTS);
        $this->toDateInfo = getdate($this->toTS);
    }

    /**
     * @brief Return an array with the years that fromTS and toTS span.
	 * @returns Array with the years from fromTS upto and including toTS. 
     */
    public function getYears() {
        $years = range($this->fromDateInfo["year"], $this->toDateInfo["year"]);
        return($years);
    }

    /**
     * @brief Return an array with timestamps for the years that fromTS and toTS span where the timestamp is exactelly on the month, day, hour, minute and second of the starting timestamp.
     */
    public function getYearsTS() {
        $years = $this->getYears();
        $timestamps = array();
        foreach($years as $year) {
            $timestamps[] = mktime(
                $this->fromDateInfo["hours"],
                $this->fromDateInfo["minutes"],
                $this->fromDateInfo["seconds"],
                $this->fromDateInfo["mon"],
                $this->fromDateInfo["mday"],
                $year
            );
        }
        return($timestamps);
    }

    /**
     * @brief Return an array with timestamps for the years that fromTS and toTS span where the timestamp is exactelly from midnight at the first dayof the years that span.
     */
    public function getYearsTSFull() {
        $years = $this->getYears();
        $timestamps = array();
        foreach($years as $year) {
            $timestamps[] = mktime(0, 0, 0, 1, 1, $year);
        }
        return($timestamps);
    }

    /**
     * @brief Return an array with timestamps for all the days from fromTS to toTS where the timestamp is exactelly from midminght on each day.
     */
    public function getDaysTSFull() {
        var_dump($this->fromDateInfo);
        $firstDay = mktime(0, 0, 0, $this->fromDateInfo["mon"], $this->fromDateInfo["mday"], $this->fromDateInfo["year"]);
        $lastDay = mktime(0, 0, 0, $this->toDateInfo["mon"], $this->toDateInfo["mday"], $this->toDateInfo["year"]);
        $currentDay = $firstDay;
        $timestamps = array($currentDay);
        while ($currentDay < $lastDay) {
            $nextDay = strtotime("+1 day", $currentDay);
            $currentDay = $nextDay;
            $timestamps[] = $currentDay;
        }
        return($timestamps);
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
	 * @brief Check if a variable is empty. 
	 * 
	 * This does roughly the same as PHP's empty() function but works on
	 * indirect variables. That is, a variable returned from a function or
	 * method can't be used with PHP's empty() function. This method does.
	 *
	 * @param $var (mixed) Variable to check.
	 * @returns True if $var is 'empty'. "", null, false, 0 and an empty array are considered empty (strict type checking is enforced (===)). False if not empty.
	 * @note To supress 'Undefined variable' errors, you should prepend the call to this method with an '@'.
	 */
	public static function isEmpty($var = null) {
		if ($var === "" || $var === null || $var === false || $var === 0 || $var === array()) {
			return(true);
		}
		return(false);
	}

	/**
	 * @brief Bring the value of variable into the current scope.
	 * 
	 * Use this function to bring the value of a variable from the client
	 * side/serverside into the current scope. This function tries to safely
	 * implement a kind of register_globals. You can set the priorities of the
	 * various import sources (POST, GET, etc) by changing the order of the
	 * contents of the $from parameter. Additionally, you can specify a default
	 * value which will be set if the variable wasn't found.
	 *
	 * Example:
	 * @code
	 * $language = import("language", "SCPG", "en");
	 * setcookie("language", $language, time() + 86400);
	 * @endcode
	 *
	 * The code above import the 'language' variables value. It first tries the
	 * session, then a cookie, then POST and GET data. If all fails, the
	 * default 'en' will be used. After that a cookie is set so the next time
	 * the user returns,the language is automatically loaded. The cookie can be
	 * overridden with GET or POST.
	 * 
	 * @param varName (string) The name of the variable to import.
	 * @param from (string) Specifies the source from where to import the variable. It should be in the form of a string containing one or more of the chars in 'FSPGC', where F=Files, S=Session, P=Post, G=Get, C=Cookie. Last char in the string overrides previous sources.
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
				case 'F' : if (isset($_FILES) && array_key_exists($varName, $_FILES)) { $varValue = $_FILES[$varName]; } break;
				case 'S' : if (isset($_SESSION) && array_key_exists($varName, $_SESSION)) { $varValue = $_SESSION[$varName]; } break;
				case 'P' : if (isset($_POST) && array_key_exists($varName, $_POST)) { $varValue = $_POST[$varName]; } break;
				case 'G' : if (isset($_GET) && array_key_exists($varName, $_GET)) { $varValue = $_GET[$varName]; } break;
				case 'C' : if (isset($_COOKIE) && array_key_exists($varName, $_COOKIE)) { $varValue = $_COOKIE[$varName]; } break;
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
 * @brief Network information manipulation class.
 */
class TLNetwork
{
	/**
	 * @brief Convert a dotted netmask representation (255.255.255.0) to a bit notation (/24).
	 * @param $nmDot (string) Dotted netmask representation (255.255.255.0)
	 * @returns (int) Integer representation of the bits. (24)
	 */
	public static function netmaskDot2Bit($nmDot) {
		$netmask_full = 0; $netmask_full = ~$netmask_full; /* Fill netmask with binary 1's */
		$bitnetmask = $netmask_full - ip2long($nmDot);
		return (32-strlen(decbin($bitnetmask))); /* ugly, but working */
	}

	/**
	 * @brief Convert a bit netmask representation (/24) to a dotted notation (255.255.255.0).
	 * @param $nmBit (int) Bit netmask representation (24)
	 * @returns (string) Dotted representation of the bits. (255.255.255.0)
	 */
	public static function netmaskBit2Dot ($nmBit) {
		$bitnetmask_inv = pow(2, (int)(32-$nmBit)) - 1; /* I.e. /24 -> 255 */
		$netmask_full = 0; $netmask_full = ~$netmask_full; /* Fill netmask with binary 1's */

		return (long2ip($netmask_full - $bitnetmask_inv));
	}
}

/////////////////////////////////////////////////////////////////////////////
// Development helpers
/////////////////////////////////////////////////////////////////////////////

/**
 * @brief Variable validation class
 * 
 * The TLValidate class offers a varying range of methods with which you can validate the values of variables. 
 */
class TLValidate
{
	/**
	 * @brief Validate an email address
	 * @param $email (string) String containing what might possibly be an email address
	 * @returns (boolean) True if $email contains a valid email address. False otherwise.
	 */
	public static function isEmail($email) {
		return(preg_match("/^.+@.+\.[a-zA-Z]+$/", $email));
	}

	/**
	 * @brief Validate an hostname
	 * @param $hostname (string) String containing what might possibly be a hostname
	 * @returns (boolean) True if $hostname contains a valid hostname. False otherwise.
	 */
	public static function isHostname($hostname) {
		return(preg_match("/^.+\.[a-zA-Z]+$/", $hostname));
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
		return(TLString::isOfChars($string, $chars));
	}
}

/**
 * @brief Additional debugging routines
 * 
 * This class contains additional convenience debugging routines.
 */
class TLDebug {

	/**
	 * @brief Return a backtrace of all visited functions/classes as a single string where each function/method is on a new line.
	 *
	 * Example output:
	 * <pre>
	 * test/debug.php:11 Test::foo(15, abc, Array(1, 2, Array))
	 * test/debug.php:18 Test->bar()
	 * test/debug.php:22 go()
	 * </pre>
	 * @param $relative (Boolean) If true (default), filenames will be shortened by making them relative to the document root.
	 * @returns (string) A string with each function/methode call on a new line, including file, line, function/methodname and parameter info.
	 */
	static function backtraceString($relative = True) {
		$out = "";

		if ($relative) {
			$docRootLen = strlen($_SERVER["DOCUMENT_ROOT"]);
		} else {
			$docRootLen = 0;
		}

		$trace = debug_backtrace();
		if (is_array($trace)) {
			for ($i = 0; $i != count($trace); $i++) {
				$stackFrame = $trace[$i];

				// Skip frames relating to this class
				if (array_key_exists("class", $stackFrame) && $stackFrame["class"] == __CLASS__) {
					continue;
				}

				if (array_key_exists("file", $stackFrame)) { 
					$out .= substr($stackFrame["file"], $docRootLen, strlen($stackFrame["file"]) - $docRootLen).":"; 
				} else {
					$out .= "??:";
				}
				if (array_key_exists("line", $stackFrame)) {
					$out .= $stackFrame["line"]." ";
				} else {
					$out .= "?? ";
				}
				if (array_key_exists("class", $stackFrame)) {
					$out .= $stackFrame["class"].$stackFrame["type"];
				}
				if (array_key_exists("function", $stackFrame)) {
					$out .= $stackFrame["function"]."(";
					$args = array();
					if (array_key_exists("args", $stackFrame)) {
						foreach($stackFrame["args"] as $arg) {
							if (gettype($arg) == "array") {
								$args[] = "Array(".@implode(", ", $arg).")";
							} else {
								$args[] = $arg;
							}
						}
						$out .= implode(", ", $args);
					}
					$out .= ")";
				}
				$out .= "\n";
			}
		}

		return($out);
	}
	
	/**
	 * @brief Return a backtrace of all visited functions/classes as a single, one-lined string.
	 *
	 * Example output:
	 * <pre>
	 * test/debug.php:11 Test::foo(15, abc, Array(1, 2, Array)); test/debug.php:18 Test->bar(); test/debug.php:22 go(); 
	 * </pre>
	 * @param $relative (Boolean) If true (default), filenames will be shortened by making them relative to the document root.
	 * @returns (string) A on-lined string including file, line, function/methodname and parameter info of each function called on the stack.
	 */
	static function backtraceSingleLine($relative = True) {
		$out = TLDebug::backtraceString($relative);
		$out = str_replace("\n", "; ", $out);
		return($out);
	}

	/** 
	 * @brief Set some PHP configuration options so that all errors and warnings will be shown and will be fatal.
	 */
	static function startPedantic() {
		assert_options(ASSERT_ACTIVE, 1);
		assert_options(ASSERT_WARNING, 1);
		assert_options(ASSERT_BAIL, 1);
		if (defined("E_STRICT")) {
			error_reporting(E_ALL | E_STRICT);
		} else {
			error_reporting(E_ALL);
		}
		ini_set("display_errors", "1");
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
 * @code
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
 *     // Test PASSES if an error is thrown! (it's supposed to do so)
 *     $test->failed(new Exception("Non existing user loaded."));
 *     try {
 *       new MyProgramUser("AmeliaEarhart");
 *     catch (Exception $e) {
 *       $test->passed();
 *     }
 *   }
 * }
 * @endcode
 *
 * Example output could look like this:
 * <pre>
 *  Nr | Test                        | Passed | Result
 * ----+-----------------------------+--------+-----------------------------------------
 *   1 | User:Load a user            | passed | 
 *   2 | User:Save a user            | passed | 
 *   3 | User:Load_NonExisting       | FAILED | Non existing user loaded.
 *   4 | Grant:Give rights to a user | passed |
 *   5 | Grant:Give rights to a group| passed |
 *   6 | Group:Add user to group     | passed | 
 * </pre>
 *
 * Reports can be generated from the results using the ->dumpFoo() methods.
 *
 * @warning Please be advised that this class does strange things to error
 * reporting and handling. Do not be surprised if errors suddenly don't turn up
 * anymore after you've created an instance of this class.
 */
class TLUnitTest {

	private $appName = "";
	private $cnt = 1;
	private $testOutput = array();
	private $testResults = array();
	private $currentTest = array();
	private $otherErros = "";

	private $prevErrorReporting = 0;
	/**
	 * @brief Create a new Unit test controller
	 * @param $appName (string) The name of the application you're testing.
	 * @param $testClass (string or object instance) An instance of or name (when only using static methods) of a class to test.
	 * @warning Make sure to destroy this class when you're done unit testing, or errors will not show up anymore.
	 */
	public function __construct($appName, $testClass) {
		$this->prevErrorReporting = error_reporting(E_ALL);
		set_error_handler(array(&$this, "errorHandler"));
		$this->appName = $appName;
		$this->testClass = $testClass;
		$this->run($this->testClass);
	}

	/**
	 * @brief Destructor.
	 */
	public function __destruct() {
		restore_error_handler();
		error_reporting($this->prevErrorReporting);
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

/////////////////////////////////////////////////////////////////////////////
// Misc
/////////////////////////////////////////////////////////////////////////////

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
	public static function isMain($from) {
		if ($from == realpath($_SERVER["SCRIPT_FILENAME"])) {
			return(true);
		} else {
			return(false);
		}
	}
}

/**
 * @brief Methods for working with paths.
 */
class TLPath
{
	/**
	 * @brief Construct a path from a variable number of arguments.
	 * 
	 * This method will construct a path from the arguments you pass it. It
	 * will automatically remove pending and trailing slashes of needed.
	 *
	 * @param ... Elements that will make up the path. If the last argument is True (boolean), a slash will be appended to the path.
	 */
	public static function concat() {
		$args = func_get_args();
		$path = "";
		foreach($args as $arg) {
			if (!is_bool($arg)) {
				$path .= '/';
				$path .= ltrim(rtrim($arg, '/'), '/');
			} else {
				if ($arg === true) {
					$path .= '/';
				}
			}
		}
		return($path);
	}
}
/* Perform a bunch of tests/examples if we're the main script */
if (TLControlStruct::isMain(__FILE__)) {
	TLDebug::startPedantic();

	//###########################################################################
	// TLNetwork
	//###########################################################################
	// TLNetwork::netmaskDot2Bit()
	print (TLNetwork::netmaskDot2Bit("255.255.254.0")."\n");
	//---------------------------------------------------------------------------
	// TLNetwork::netmaskBit2Dot()
	print (TLNetwork::netmaskBit2Dot(24)."\n");

	//###########################################################################
	// TLString
	//###########################################################################
	// TLString::explodeAssign()
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
	$test->__destruct(); // PHP Bug work-around.

	//###########################################################################
	// TLTypeException
	//###########################################################################
	// TLTypeException
	function TLTypeExceptionTest($name) {
		if (!is_string($name)) {
			throw new TLTypeException("string", $name, "name");
		}
	}
	try {
		TLTypeExceptionTest("ferry");
		TLTypeExceptionTest(26);
	} catch (TLTypeException $e) {
		print($e->getMessage()."\n");
	}

	//###########################################################################
	// TLValueException
	//###########################################################################
	// TLValueException
	function TLValueExceptionTest($age) {
		if (!($age > -1 and $age < 200)) {
			throw new TLValueException("Age should be between 0 and 200", $age, "age");
		}
	}
	try {
		TLValueExceptionTest(50);
		TLValueExceptionTest(230);
	} catch (TLValueException $e) {
		print($e->getMessage()."\n");
	}

	//###########################################################################
	// TLSelfURL 
	//###########################################################################
	// 
	try {
		$s = new TLSelfURL();
		print ($s->getServerURL());
		print ($s->getAbsolutePathURL());
		print ($s->getAbsoluteScriptURL());
		print ($s->getAbsoluteFullURL());
		print ($s->getAbsoluteFullURL("strip_"));
		print ($s->getRelativePathURL());
		print ($s->getRelativeScriptURL());
		print ($s->getRelativeFullURL());
		print ($s->getRelativeFullURL("strip_"));
	} catch (Exception $e) {
		// Not running in a HTTP server.
		;
	}
}
?>
