<?php
header("Access-Control-Allow-Origin: *");  // If multiple headers of this type are set, an error might be thrown.
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-type");
/*
if (in_array($_SERVER['HTTP_ORIGIN'], array())) {  // if the request is coming from an acceptable origin (contained within the array)
    header("Access-Control-Allow-Origin: " . $_SERVER["HTTP_ORIGIN"]);
}
*/

use google\appengine\api\cloud_storage\CloudStorageTools;

function tame($information) {
    /**
    makes the information passed a little more secure
    */
    $information = trim($information);
    $information = stripslashes($information);
    $information = htmlspecialchars($information);
    return $information;
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $location = tame($_POST["location"]);
    switch (tame($_POST["action"])) {
        case "write":
            $information = tame($_POST["information"]);
            file_put_contents("gs://superb-beach-171102.appspot.com/" . $location, $information);
            $text = "You wrote to " . $location;
            break;
        case "read":
            //// CloudStorageTools::serve("gs://bucket/file");
            $text = file_get_contents("gs://superb-beach-171102.appspot.com/" . $location);
            break;
        case "delete":
            unlink("gs://superb-beach-171102.appspot.com/" . $location);
            $text = "You deleted " . $location;
            break;
        default:
            trigger_error("The action requested is not availible.", E_USER_ERROR);
            // There's also E_USER_NOTICE (default) and E_USER_WARNING.
    }
    echo $text;
}
// for deploying this app using Google Cloud Shell:
# git clone https://github.com/EpicenterPrograms/communicator communicator && cd communicator && gcloud app deploy && cd ..
# rm -rf communicator
?>
