<?php
// /var/www/public/frontend/pages/flight-planning/step2.php
// Vluchtplanning Stap 2

session_start();
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../functions.php';


// Sla de gegevens van stap 1 op in de sessie
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $_SESSION['flight_planning']['flight_type'] = $_POST['flight_type'] ?? '';
    $_SESSION['flight_planning']['start_location'] = $_POST['start_location'] ?? '';
    $_SESSION['flight_planning']['destination'] = $_POST['destination'] ?? '';
    $_SESSION['flight_planning']['flight_datetime'] = $_POST['flight_datetime'] ?? '';
}

// Stel variabelen in voor template.php
$showHeader = 1;
$userName = $_SESSION['user']['first_name'] ?? 'Onbekend'; // Gebruikersnaam uit sessie
$org = isset($organisation) ? $organisation : 'Organisatie B'; // Dynamisch uit config.php of fallback
$headTitle = "Risicoanalyse";  // Paginatitel
$gobackUrl = 0; // Geen terug-knop
$rightAttributes = 0; // Geen logout, wel notificaties en profiel

// Body content voor Vluchtplanning Stap 2
$bodyContent = "
    <div class='h-[83.5vh] bg-gray-100 shadow-md rounded-tl-xl w-13/15'>

        <!-- Stappenbalk -->
        <div class='p-4 bg-gray-100'>
            <div class='flex justify-center items-center space-x-4'>
                <span class='w-8 h-8 bg-black text-white rounded-full flex items-center justify-center'>1</span>
                <div class='flex-1 h-1 bg-black'></div>
                <span class='w-8 h-8 bg-blue-600 text-white rounded-full flex items-center justify-center font-bold'>2</span>
                <div class='flex-1 h-1 bg-gray-300'></div>
                <span class='w-8 h-8 bg-gray-300 text-gray-600 rounded-full flex items-center justify-center'>3</span>
                <div class='flex-1 h-1 bg-gray-300'></div>
                <span class='w-8 h-8 bg-gray-300 text-gray-600 rounded-full flex items-center justify-center'>4</span>
            </div>
        </div>

        <!-- Inhoud -->
        <div class='p-6 overflow-y-auto max-h-[calc(90vh-200px)]'>
            <h2 class='text-xl font-bold mb-4 text-gray-800'>Risicoanalyse</h2>
            <form action='step3.php' method='post' class='space-y-6'>
                <div class='bg-gray-200 p-5 rounded-lg mb-4 w-full'>
                    <div class='flex items-center justify-between'>
                        <div class='text-left'>
                            <p class='text-sm font-medium text-gray-700'>SAIL Score: 1.7 Maximaal toegestaan: 2.0</p>
                        </div>
                        <button type='button' class='bg-gray-900 text-white px-3 py-1 rounded-full text-xs hover:bg-gray-700 transition-colors ml-4'>
                            Acceptabel
                        </button>
                    </div>
                </div>
                <div class='bg-gray-200 p-4 rounded-full mb-4 flex items-center'>
                    <input type='checkbox' name='risk_checklist[]' value='increase_altitude' id='increase_altitude' class='mr-2 h-4 w-4'>
                    <label for='increase_altitude' class='text-sm text-gray-700 ml-1'>1. Verhoog minimale vlieghoogte naar 150m</label>
                </div>
                <div class='grid grid-cols-1 md:grid-cols-2 gap-4 mb-4'>
                    <div class='bg-gray-200 p-5 rounded-lg flex items-center justify-center w-4/10 mx-[2.5%] h-80'>
                        <i class='fa-solid fa-chart-pie text-gray-600 text-3xl'></i>
                    </div>
                    <div class='bg-gray-200 p-5 rounded-lg flex items-center justify-center w-4/10 mx-[2.5%] h-80'>
                        <i class='fa-solid fa-list-ul text-gray-600 text-3xl'></i>
                    </div>
                </div>
                <div class='flex justify-between items-center'>
                    <a href='/app/views/flight-planning-step1.php' class='text-gray-500 hover:text-gray-700 flex items-center text-sm px-3 py-2'>
                        <i class='fa-solid fa-arrow-left mr-2'></i> Vorige stap
                    </a>
                    <button type='submit' class='bg-gray-900 text-white px-6 py-3 rounded-full hover:bg-gray-700 transition-colors flex items-center'>
                        Volgende <i class='fa-solid fa-arrow-right ml-2'></i>
                    </button>
                </div>
            </form>
        </div>
    </div>
";

require_once __DIR__ . '/../../components/header.php';
require_once __DIR__ . '/../layouts/template.php';
?>