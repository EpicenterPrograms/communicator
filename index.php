<?php
header("Access-Control-Allow-Origin: *");  // If multiple headers of this type are set, an error might be thrown.
/*
if (in_array($_SERVER['HTTP_ORIGIN'], array())) {  // if the request is coming from an acceptable origin (contained within the array)
    header("Access-Control-Allow-Origin: " . $_SERVER["HTTP_ORIGIN"]);
}
*/
echo "This is a test.";
?>
