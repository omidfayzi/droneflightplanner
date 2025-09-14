<?php

/**
 * In deze file komen je herbruikbare functies.
 * Plaats deze in: /backend/functions/functions.php
 */

// Alleen session_start() als er nog geen sessie actief is
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * login()
 * Functie om te controleren of bepaalde session-variabelen gezet zijn.
 */
function login()
{
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
