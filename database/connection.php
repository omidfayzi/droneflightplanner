<?php

// Een herbruikbare functie om verbinding te maken met de database van droneflightplannerphp

function getDatabaseConnection() {
    $host = "devserv01.holdingthedrones.com";
    $user = "Omid";
    $pass = "VluchtVoorbeiding";
    $db   = "VluchtVoorbeiding";

    $conn = mysqli_connect($host, $user, $pass, $db);

    if (!$conn) {
        die("Geen verbinding kunnen maken met de database: " . mysqli_connect_error());
    }
    return $conn;
}

$conn = getDatabaseConnection();
echo "Verbonden";
?>


// function getDatabaseConnection() {
//     $host = 'devserv01.holdingthedrones.com';
//     $db   = 'droneflightplannerphp'; 
//     $user = 'Omid';
//     $pass = 'VluchtVoorbeiding';

//     //  Data Source Name string
//     $dsn = "mysql:host=$host;dbname=$db;pass=$pass";
//     $options = [
//         PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
//         PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
//     ];

//     try {
//         return new PDO($dsn, $user, $pass, $options);
//     } catch (\PDOException $e) {
//         throw new \PDOException($e->getMessage(), (int)$e->getCode());
//     }
}
