<?php
session_start(); // This must be at the very top with no output before it

function login() {
    // Check for session state and handle accordingly
    if (isset($_GET["state"]) && !isset($_SESSION["state"])) {
        $_SESSION["state"] = $_GET["state"];
    }
    
    if (isset($_GET["session_state"]) && !isset($_SESSION["session_state"])) {
        $_SESSION["session_state"] = $_GET["session_state"];
    }
    
    if (isset($_GET["code"]) && !isset($_SESSION["code"])) {
        $_SESSION["code"] = $_GET["code"];
    }
    
    if (!isset($_SESSION["oauth2state"])) {
        echo "<script> location.href='/index.php'; </script>";
        exit("exit");
    }
}

function fetchPropPrefTxt($id) {
    $url = "https://api.droneperceelvoorkeuren.nl/get-txt-with-id/$id"; 

    // Fetch the API response using file_get_contents
    $response = file_get_contents($url);

    // Check if the request was successful
    if ($response === FALSE) {
        die('Error occurred while fetching data.');
    }

    // Decode the JSON response
    $data = json_decode($response, true);

    // Validate and process the response
    if (!isset($data['users']) || empty($data['users'])) {
        die('No data found.');
    }

    // Extract the single row
    $user = $data['users'][0]; // Since it’s always one row

    // Check if the language cookie is set
    if (isset($_COOKIE['language_id'])) {
        $language_id = $_COOKIE['language_id'];
    } else {
        $language_id = "PropPrefTxt_En";
    }

    return $user[$language_id]; // Return the specific field
}
?>