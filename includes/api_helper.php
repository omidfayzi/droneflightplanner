<?php

class ApiHelper
{
    // Encapsuleer de basis-URL van de API in een private variabel. de URL is alleen beschikbaar binnen de klasse.
    private $baseUrl;

    // Constructor object : krijgt de basis-URL van de API
    public function __construct($baseUrl)
    {
        $this->baseUrl = $baseUrl;
    }

    // Method om gegevens op te halen van de API
    public function fetchData($endpoint)
    {
        $url = $this->baseUrl . $endpoint;
        // Zet gegevens om in een JSON-string formaat
        $response = file_get_contents($url);

        // Checken of het ophalen van gegevens gelukt is
        if ($response === false) {
            throw new Exception("Error fetching data from API");
        }

        // Return JSON-decoded response in associative array format
        return json_decode($response, true);
    }
}
