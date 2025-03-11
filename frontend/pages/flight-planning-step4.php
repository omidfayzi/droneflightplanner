<?php
session_start();
require_once __DIR__ . '/../../config/config.php';

// Sla de gegevens van stap 3 op in de sessie (indien van toepassing)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Hier kan logica worden toegevoegd om gegevens van stap 3 op te slaan, zoals vergunningen en documenten
    // Voor dit voorbeeld gaan we ervan uit dat de gegevens al zijn verwerkt
}

// Stel variabelen in voor template.php
$showHeader = 1;
$userName = $_SESSION['user']['first_name'] ?? 'Onbekend'; // Haal de voornaam uit de sessie
$org = isset($organisation) ? $organisation : 'Organisatie A'; // Dynamisch uit config.php, fallback naar Organisatie A
$headTitle = "Afronden"; // Paginatitel
$gobackUrl = 0; // Geen terug-knop
$rightAttributes = 0; // Geen logout-knop, wel notificatie en profiel

// Body content voor Vluchtplanning Stap 4
$bodyContent = "
    <div class='h-[83.5vh] bg-gray-100 shadow-md rounded-tl-xl w-13/15'>

        <!-- Stappenbalk -->
        <div class='p-4 bg-gray-100'>
            <div class='flex justify-center items-center space-x-4'>
                <span class='w-8 h-8 bg-black text-white rounded-full flex items-center justify-center'>1</span>
                <div class='flex-1 h-1 bg-black'></div>
                <span class='w-8 h-8 bg-black text-white rounded-full flex items-center justify-center'>2</span>
                <div class='flex-1 h-1 bg-black'></div>
                <span class='w-8 h-8 bg-black text-white rounded-full flex items-center justify-center'>3</span>
                <div class='flex-1 h-1 bg-black'></div>
                <span class='w-8 h-8 bg-blue-600 text-white rounded-full flex items-center justify-center'><i class='fa-solid fa-check'></i></span>
            </div>
        </div>

        <!-- Content -->
        <div class='p-6 overflow-y-auto max-h-[calc(90vh-200px)] flex flex-col items-center justify-center'>
            <div class='bg-white rounded-lg shadow-md p-8 text-center'>
                <div class='w-12 h-12 bg-gray-200 rounded-full flex items-center justify-center mb-4 mx-auto'>
                    <i class='fa-solid fa-check text-gray-600'></i>
                </div>
                <h2 class='text-xl font-bold mb-4 text-gray-800'>Vluchtplanning succesvol ingediend, wachtend op akkoord van het UTM.</h2>
                <div class='flex justify-center space-x-4 mt-6'>
                    <a href='/frontend/pages/flight-planning-details.php' class='bg-black text-white px-6 py-3 rounded-full hover:bg-gray-800 transition-colors'>
                        Details bekijken
                    </a>
                    <a href='/frontend/pages/dashboard.php' class='bg-gray-200 text-gray-700 px-6 py-3 rounded-full hover:bg-gray-300 transition-colors'>
                        Sluiten
                    </a>
                </div>
            </div>
        </div>
    </div>
";

require_once __DIR__ . '/components/header.php'; 
require_once __DIR__ . '/template.php';
?>