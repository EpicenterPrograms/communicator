<?php
header("Access-Control-Allow-Origin: *");  // If multiple headers of this type are set, an error might be thrown.
/*
if (in_array($_SERVER['HTTP_ORIGIN'], array())) {  // if the request is coming from an acceptable origin (contained within the array)
    header("Access-Control-Allow-Origin: " . $_SERVER["HTTP_ORIGIN"]);
}
*/

function tame($information) {
    /**
    makes the information passed a little more secure
    */
    $information = trim($information);
    $information = stripslashes($information);
    $information = htmlspecialchars($information);
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $text = tame($_POST["text"]);
    $color = tame($_POST["color"]);
    echo '<span style="color:$color">$text</span>';
}
?>
