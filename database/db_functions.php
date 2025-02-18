<?php
include 'database/connection.php'; 

try {
    $pdo = getDatabaseConnection();
    echo "Verbinding succesvol!";
} catch (PDOException $e) {
    echo "Verbinding mislukt: " . $e->getMessage();
}
?>
