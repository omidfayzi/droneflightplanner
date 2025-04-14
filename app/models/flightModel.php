<?php

require_once __DIR__ . '/../includes/api_helper.php';

// Class model flightModel voor het ophalen van vluchtgevens
class FlightModel
{
    private $apiHelper;

    // Constructor object : krijgt de API-helper object mee
    public function __construct($apiHelper)
    {
        $this->apiHelper = $apiHelper;
    }

    // Method om vluchtgegevens op te halen
    public function getFlightData()
    {
        return $this->apiHelper->fetchData('flights') ?? []; // Lege array als er null of geen gegevens zijn
    }

    // Method om statistieken op halen
    public function getFlightStats()
    {
        $defaultStats = [
            'active_flights' => 0,
            'pending_approval' => 0,
            'total_flights' => 0
        ];
        return $this->apiHelper->fetchData('flights/stats') ?? $defaultStats; // Lege array als er null of geen gegevens zijn   
    }
}
