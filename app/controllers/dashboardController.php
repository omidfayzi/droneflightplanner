<?php

require_once __DIR__ . '/../models/flightModel.php';

class dashboardController
{
    private $flightModel;

    // Constructor object : krijgt het FlightModel mee
    public function __construct($flightModel)
    {
        $this->flightModel = $flightModel;
    }

    // Hooffunctie voor dashboard
    public function dashboard()
    {
        // Haal vluchtgegevens op uit de model
        $recentFlights = $this->flightModel->getFlightData();
        $recentStats = $this->flightModel->getFlightStats();

        // Tijdelijke sessiegegevens (later verbeter)
        $userName = $_SESSION['user']['first_name'] ?? 'Onbekend';
        $org = $_SESSION['org'] ?? '';

        $data = [
            'headTitle' => "Dashboard",
            'userName' => $userName,
            'org' => $org,
            'gobackUrl' => 0, // Geen terugknop nodig
            'rightAttributes' => 0, // Geen SSO-knop, alleen profielicoon
            'recentFlights' => $recentFlights,
            'recentStats' => $recentStats
        ];

        // Laad de View met de data
        $this->renderView('dashboard', $data);
    }

    // Functie om de view te renderen
    private function renderView($viewName, $data)
    {
        // Laad de view
        require_once __DIR__ . '/../views/' . $viewName . '.php';
    }
}
