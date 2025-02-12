<?php

// De API-endpoint URL
$url = $kadasterUrl;

// Initialize cURL
$curl = curl_init($url);

// Stel de benodigde headers in
$headers = [
    "Accept-Crs: epsg:28992",
    "x-api-key:  $kadasterApiKey", // Voeg hier je eigen API-sleutel toe, indien vereist
];

// Configureer cURL-opties
curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);

// Voer het verzoek uit en sla de respons op
$response = curl_exec($curl);

// Controleer of er een fout optrad
if (curl_errno($curl)) {
    echo "cURL-fout: " . curl_error($curl);
} else {
    // Decodeer de JSON-respons
    $data = json_decode($response, true);

    // Check if the 'punt' and 'coordinates' data is present
    if (isset($data['verblijfsobject']['verblijfsobject']['geometrie']['punt']['coordinates'])) {
        $punt = $data['verblijfsobject']['verblijfsobject']['documentnummer'];
        echo $punt;
        echo "<br><br>";
        $coordinates = $data['verblijfsobject']['verblijfsobject']['geometrie']['punt']['coordinates'];
        echo "Coordinates: X = " . $coordinates[0] . ", Y = " . $coordinates[1];
    } else {
        echo "Coordinates not found in the response data.";
    }

    // Toon de data
    print_r($data);
}

// Sluit de cURL-sessie
curl_close($curl);
?>
