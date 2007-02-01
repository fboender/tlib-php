<?php

$includePath = ini_get("include_path");
$includePath = rtrim($includePath, ':').':'.realpath("../");
ini_set("include_path", $includePath);

require_once("tlib.php");

class Foo {
    public function bar() {
        print("Foo::bar()\n");
        return("bar");
    }
}

class Baz {
    public function bah() {
        print("Baz::bah()\n");
        return("bah");
    }
}

class Test extends TLAutoDelegate {
    public function __construct() {
        $this->delegateTo(new Foo());
        $this->delegateTo(new Baz());
    }
}

$t = new Test();
print($t->bar()."\n"); // Foo::bar() \n bar
print($t->bah()."\n"); // Baz::bah() \n bah

?>
