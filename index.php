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
    /*
    POST options:
    username => 
    password => 
    location => 
    pwd_path => 
    verification =>
    verifier => 
    headers => 
    action =>
        verify
        store
        recall
        forget
        register
    information => 
    //// override => 
    */
    $username = tame($_POST["username"]);
    $password = tame($_POST["password"]);
    $location = tame($_POST["location"]);  //// You might want to make setting the location easier.
    if ($_POST["pwd_path"]) {
        $pwd_path = tame($_POST["pwd_path"]);
    } else {
        $pwd_path = "gs://" . strstr($location, "://") . "/" . $username . "/password";
    }
    if (tame($_POST["verification"]) === "external") {  // if the user needs to be verified through an external source
        //// set header
        if ($_POST["verifier"]) {
            $verifier = tame($_POST["verifier"]);
        } else {
            $verifier = "https://epicenterresources.appspot.com";
        }
        $options = array("http" => array(
            "method" => "POST",
            "content" => http_build_query(
                array("action" => "verify", "username" => $username, "password" => $password, "pwd_path" => $pwd_path)
            )
        ));
        if ($_POST["headers"]) {
            $options["http"]["header"] = $_POST["headers"];
        }
        $context = stream_contex_create($options);
        $destination = fopen($verifier, "r", false, $context);
        if (!$destination) {  // if the URL doesn't go anywhere
            //// throw new Exception("Problem with $url, $php_errormsg");
        }
        $verified = stream_get_contents($destination);
        if ($verified === false) {  // if there's a problem reading the data
            //// throw new Exception("Problem reading data from $url, $php_errormsg");
        } elseif ($verified === "false") {  // if the username and/or password aren't correct
            
        }
    } elseif (tame($_POST["action"]) === "verify") {  // if the script is just being run to verify a user
        //// set header
        if (password_verify($password, file_get_contents($pwd_path))) {
            $response = true;
        } else {
            $response = false;
        }
    } else {
        //// set header?
        $verified = password_verify($password, file_get_contents($pwd_path));
        if (!$verified) {
            
        }
    }
    $verified = true;  ////
    if ($verified === true || $verified === "true") {  // if the password is correct
        header("Access-Control-Allow-Origin: " . tame($_SERVER["HTTP_ORIGIN"]));
        switch (tame($_POST["action"])) {  // switch uses ==
            case "store":
                //// Make sure people don't write to their password.
                file_put_contents($location, $_POST["information"]);  // not taming the information could be bad
                $response = "You wrote to " . $location;
                break;
            case "recall":
                $response = file_get_contents($location);
                //// CloudStorageTools::serve("gs://bucket/file");
                break;
            case "forget":
                //// Make sure people don't delete their password (usually).
                unlink($location);
                $response = "You deleted " . $location;
                break;
            default:
                throw new Exception("The action requested is not availible.");
                //// trigger_error("The action requested is not availible.", E_USER_ERROR);
                // There's also E_USER_NOTICE (default) and E_USER_WARNING.
        }
    } elseif (tame($_POST["action"]) === "register") {
        //// file_put_contents($location, password_hash($password, PASSWORD_DEFAULT));
        $response = "You tried to register.";
    } elseif (tame($_POST["action"]) !== "verify") {
        $response = "The password is incorrect.";
    }
    echo $response;
}
// for deploying this app using Google Cloud Shell (when you call the file "communicator"):
# git clone https://github.com/EpicenterPrograms/communicator communicator && cd communicator && gcloud app deploy && cd ..
# rm -rf communicator
?>
