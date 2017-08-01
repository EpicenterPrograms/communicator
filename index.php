<?php
header("Access-Control-Allow-Methods: GET, POST");
header("Access-Control-Allow-Headers: Content-type");

# use google\appengine\api\cloud_storage\CloudStorageTools;

function tame($information) {
    /**
    makes the information passed a little more secure
    */
    $information = trim($information);
    $information = stripslashes($information);
    $information = htmlspecialchars($information);
    return $information;
}

if ($_SERVER["REQUEST_METHOD"] === "GET") {
    if (true) {  // if the resource allows any origin
        header("Access-Control-Allow-Origin: *");
    } elseif (in_array($_SERVER["HTTP_ORIGIN"], array())) {  // if the request is coming from an acceptable origin (contained within the array)
        header("Access-Control-Allow-Origin: " . tame($_SERVER["HTTP_ORIGIN"]));
    }
    echo "You tried to get something.";
} elseif ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username = tame($_POST["username"]);
    $password = tame($_POST["password"]);
    $location = tame($_POST["location"]);
    if (true) {  //// password_verify($password, file_get_contents("gs://" . $location . "/" . $username . "/password"))) {  // if the password is correct
        header("Access-Control-Allow-Origin: " . tame($_SERVER["HTTP_ORIGIN"]));
        switch (tame($_POST["action"])) {  // switch uses ==
            case "store":
                file_put_contents("gs://" . $location, $_POST["information"]);  // not taming the information could be bad
                $response = "You wrote to " . $location;
                break;
            case "recall":
                $response = file_get_contents("gs://" . $location);
                //// CloudStorageTools::serve("gs://bucket/file");
                break;
            case "forget":
                unlink("gs://" . $location);
                $response = "You deleted " . $location;
                break;
            default:
                trigger_error("The action requested is not availible.", E_USER_ERROR);
                // There's also E_USER_NOTICE (default) and E_USER_WARNING.
        }
    } elseif (tame($_POST["action"]) === "signup") {
        //// file_put_contents("gs://" . $location . "/" . $username . "/password", password_hash($password, PASSWORD_DEFAULT));
    } else {
        $response = "The password is incorrect.";
    }
    echo $response;
}
// for deploying this app using Google Cloud Shell:
# git clone https://github.com/EpicenterPrograms/communicator communicator && cd communicator && gcloud app deploy && cd ..
# rm -rf communicator
?>
