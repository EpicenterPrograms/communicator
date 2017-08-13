<?php
header("Access-Control-Allow-Methods: GET, POST");
header("Access-Control-Allow-Headers: Content-type");


function tame($information) {
    /**
    makes the information passed a little more secure
    */
    $information = trim($information);
    $information = stripslashes($information);
    $information = htmlspecialchars($information);
    return $information;
}

function get_info($location) {
    /**
    returns the value of file_get_contents() and parse_str() without polluting the namespace
    */
    parse_str(file_get_contents($location), $result);
    return $result;
}


if ($_SERVER["REQUEST_METHOD"] === "GET") {
    if (true) {  // if the resource allows any origin
        header("Access-Control-Allow-Origin: *");
    } elseif (in_array($_SERVER["HTTP_ORIGIN"], array())) {  // if the request is coming from an acceptable origin (contained within the array)
        header("Access-Control-Allow-Origin: " . tame($_SERVER["HTTP_ORIGIN"]));
    }
    echo "You tried to get something.";
} elseif ($_SERVER["REQUEST_METHOD"] === "POST") {
    /**
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
    header("Access-Control-Allow-Origin: " . tame($_SERVER["HTTP_ORIGIN"]));
        # This needs to be set at the beginning or else it would be impossible to verify the user.
        # The header can be changed later.
    $username = tame($_POST["username"]);
    $password = tame($_POST["password"]);
    $location = tame($_POST["location"]);  //// You might want to make setting the location easier.
    $information = $_POST["information"];
    if ($_POST["pwd_path"]) {  // if the path to the password is specified
        $pwd_path = tame($_POST["pwd_path"]);
    } else {
        $pwd_path = "gs://" . substr(substr($location, strpos($location,"://")+3), 0, strpos(substr($location, strpos($location,"://")+3),"/")) . "/users/" . $username . "/security/password";
    }
    $response = array("messages" => array(), "warnings" => array(), "errors" => array());
    if (tame($_POST["verification"]) === "external") {  // if the user needs to be verified through an external source
        if ($_POST["verifier"]) {
            $verifier = tame($_POST["verifier"]);
        } else {
            $verifier = "https://epicenterresources.appspot.com";
            if (!$_POST["pwd_path"]) {
                $pwd_path = "gs://epicenterresources.appspot.com/users/" . $username . "/security/password";
            }
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
            array_push($response["errors"], "There's something wrong with URL for password verification.");
        }
        $verifier_response = stream_get_contents($destination);
        if ($verifier_response === false) {  // if there's a problem reading the data
            array_push($response["errors"], "The data from the verifying URL can't be read.");
        } else {
            parse_str($verifier_response, $external_verifier);  // undoes http_build_query()
            $response["verified"] = $external_verifier["value"];
        }
    } elseif (tame($_POST["action"]) === "verify") {  // if the script is just being run to verify a user
        parse_str(file_get_contents($pwd_path), $pwd_info);
        if (in_array($username, $pwd_info["owners"]) && password_verify($password, $pwd_info["information"])) {
            $response["value"] = true;
        } else {
            $response["value"] = false;
        }
    } else {
        parse_str(file_get_contents($pwd_path), $pwd_info);
        $response["verified"] = in_array($username, $pwd_info["owners"]) && password_verify($password, $pwd_info["information"]);
    }
    if ($response["verified"] === true || $response["verified"] === "true") {  // if the password is correct
        //// Make sure people can only modify their own stuff.
        switch (tame($_POST["action"])) {  // switch uses ==
            case "store":
                //// Make sure people don't write to their password.
                if (file_get_contents($location) === false) {
                    file_put_contents($location, http_build_query(array("information" => $information, "owners" => array($username))));  // not taming the information could be bad
                    array_push($response["messages"], "You made a file at " . $location);
                } else {
                    if (in_array($username, get_info($location)["owners"])) {
                        file_put_contents($location, http_build_query(array("information" => $information, "owners" => array($username))));
                        array_push($response["messages"], "You modified the file at " . $location);
                    } else {
                        array_push($response["warnings"], "The location " . $location . " is in use by (a) different user(s).");
                    }
                }
                break;
            case "recall":
                if (file_get_contents($location) !== false) {
                    parse_str(file_get_contents($location), $contents);
                    if (in_array($username, $contents["owners"])) {
                        $response["value"] = $contents["information"];
                        array_push($response["messages"], "You read from " . $location);
                    } else {
                        array_push($response["warnings"], "You don't have permission to access " . $location);
                    }
                } else {
                    array_push($response["warnings"], "The location " . $location . " has no information.");
                }
                break;
            case "forget":
                //// Make sure people don't delete their password (usually).
                if (file_get_contents($location) === false) {
                    array_push($response["warnings"], "There's nothing to forget at " . $location);
                } elseif (in_array($username, get_info($location)["owners"])) {
                    unlink($location);
                    array_push($response["messages"], "You deleted " . $location);
                } else {
                    array_push($response["warnings"], "You don't have permission to delete " . $location);
                }
                break;
            case "permit":
                $file = get_info($location);
                if (in_array($username, $file["owners"])) {
                    if (in_array($information, $file["owners"])) {
                        array_push($response["messages"], $information . " is already an owner of " . $location);
                    } else {
                        array_push($file["owners"], $information);
                        file_put_contents($location, http_build_query($file));
                        array_push($response["messages"], "You made " . $information . " an owner of " . $location);
                    }
                } else {
                    array_push($response["warnings"], "You aren't allowed to manage permissions to " . $location);
                }
                break;
            case "block":
                $file = get_info($location);
                if (in_array($username, $file["owners"])) {
                    $index = array_search($information, $file["owners"]);
                    if ($index === false) {
                        array_push($response["messages"], "You already removed " . $information . "'s permission to access " . $location);
                    } elseif ($username === $information) {
                        array_push($response["warnings"], "You aren't allowed to remove your own permission to access a file.");
                    } else {
                        array_splice($file["owners"], $index, 1);
                        file_put_contents($location, http_build_query($file));
                        array_push($response["messages"], "You removed " . $information . "'s permission to access " . $location);
                    }
                } else {
                    array_push($response["warnings"], "You aren't allowed to manage permissions to " . $location);
                }
                break;
            case "list_files":
                break;
            case "list_owners":
                break;
            case "register":
                array_push($response["messages"], "You're already registered.");
                break;
            default:
                array_push($response["errors"], "The action requested is not availible.");
        }
    } elseif (tame($_POST["action"]) === "register") {
        if (file_get_contents($pwd_path) === false) {
            file_put_contents($pwd_path, http_build_query(array("information" => password_hash($password, PASSWORD_DEFAULT), "owners" => array($username))));
            array_push($response["messages"], 'You registered the password "' . $password . '" in ' . $pwd_path);
        } else {
            array_push($response["warnings"], "That username is already taken.");
        }
    } elseif (tame($_POST["action"]) !== "verify") {
        array_push($response["warnings"], "The username and/or password isn't correct.");
    }
    echo http_build_query($response);  // Arrays can't be echoed: they have to be converted into a string.
}
// for deploying this app using Google Cloud Shell (when you call the file "communicator"):
# rm -rf communicator && git clone https://github.com/EpicenterPrograms/communicator communicator && cd communicator && gcloud app deploy && cd ..
?>
