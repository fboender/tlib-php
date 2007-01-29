<?php

$includePath = ini_get("include_path");
$includePath = rtrim($includePath, ':').':'.realpath("../");
ini_set("include_path", $includePath);

require_once("tlib.php");

////
//// PropertyManager: Keep a list of general unconstrained properties.
////
//class PropertyManager 
//{
//    private $properties = array();
//    private $filename = null;
//
//    // Construct a new PropertyManager object. Automatically loads the
//    // properties from the supplied filename (if it's present).
//    public function __construct($filename) {
//        $this->filename = $filename;
//        $this->load($this->filename);
//    }
//
//    // Load the properties from the supplied filename (if it's present).
//    private function load($filename) {
//        if (file_exists($filename)) {
//            $contents = file($filename);
//            foreach($contents as $line) {
//                if (strpos($line, '=') !== false) {
//                    $parts = explode("=", $line, 2);
//                    if (count($parts) == 2) {
//                        list($key, $value) = $parts;
//                        $this->properties[$key] = str_replace("\\n", "\n", $value);
//                    }
//                }
//            }
//        }
//    }
//
//    // Save the properties to $this->filename.
//    public function saveProperties() {
//        $contents = "";
//        foreach($this->properties as $key=>$value) {
//            $contents .= $key.':'.str_replace("\n", "\\n", $value)."\n";
//        }
//        return(file_put_contents($this->filename, $contents));
//    }
//
//    // Get the value of a property.
//    public function getProperty($key) {
//        if (array_key_exists($key, $this->properties)) {
//            return($this->properties[$key]);
//        } else {
//            return(null);
//        }
//    }
//
//    // Set the value of a property
//    public function setProperty($key, $value) {
//        $this->properties[$key] = $value;
//    }
//}
//
////
//// AccessManager: Provide general access restrictions.
////
//class AccessManager 
//{
//    private $accessList = array();
//
//    public function addAccess($username, $password) {
//        $this->accessList[$username] = $password;
//    }
//
//    public function hasAccess($username, $password) {
//        if (array_key_exists($username, $this->accessList) &&
//            $this->accessList[$username] == $password) 
//        {
//            return(true);
//        } else {
//            return(false);
//        }
//    }
//}
//
//class User extends TLAutoDelegate
//{
//    public function __construct() {
//        $this->delegateTo(new PropertyManager("/home/todsah/.tlautodelegate.prop"));
//        $this->delegateTo(new AccessManager());
//    }
//}
//
//$user = new User();
//$user->setProperty("realname", "Jonathon Johnson"); // Calls PropertyManager->setProperty()
//$user->addAccess("john", "p@55w0Rd");               // Calls AccessManager->addAccess()

class Foo {
    public function bar() {
        print("Foo::bar()\n");
    }
}

class Baz {
    public function bah() {
        print("Baz::bah()\n");
    }
}

class Test extends TLAutoDelegate {
    public function __construct() {
        $this->delegateTo(new Foo());
        $this->delegateTo(new Baz());
    }
}

$t = new Test();
$t->bar(); // Foo::bar()
$t->bah(); // Baz::bah()

?>
