<?php

header("Conten-type: text/html; charset=utf-8");

$sxml = simplexml_load_file("xml/gbook/newcatalog.xml");
$titles = $sxml->xPath("users");
echo "<pre>";
var_dump($titles);
echo "</pre>";

?>
