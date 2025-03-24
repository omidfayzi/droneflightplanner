<?php
// /var/www/public/frontend/pages/flight-planning/step4.php
// Vluchtplanning Stap 4

session_start();
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../functions.php';


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $_SESSION['step3_data'] = $_POST; // Voorbeeld: sla alle POST-data op
}

// Stel variabelen in voor template.php
$showHeader = 1;
$userName = $_SESSION['user']['first_name'] ?? 'Onbekend';
$org = isset($organisation) ? $organisation : 'Organisatie A';
$headTitle = "Afronden";
$gobackUrl = 0;
$rightAttributes = 0;

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
            <div class='bg-white rounded-lg shadow-md p-8 text-center w-[85vw] h-[80vh] flex flex-col items-center justify-center'>
                <div class='w-12 h-12 bg-gray-200 rounded-full flex items-center justify-center mb-20 mx-auto'>
                    <i class='fa-solid fa-check text-gray-600'></i>
                </div>
                <h2 class='text-xl font-bold mb-16 text-gray-800'>Vluchtplanning succesvol ingediend, wachtend op akkoord van het UTM.</h2>
                <div class='flex justify-center space-x-4 mt-6'>
                    <a href='/app/views/flight-planning-details.php' class='bg-black text-white px-6 py-3 rounded-full hover:bg-gray-800 transition-colors'>
                        Details bekijken
                    </a>
                    <a href='/app/views/dashboard.php' class='bg-gray-200 text-gray-700 px-6 py-3 rounded-full hover:bg-gray-300 transition-colors'>
                        Sluiten
                    </a>
                </div>
            </div>
        </div>
    </div>
";

require_once __DIR__ . '/../../components/header.php';
require_once __DIR__ . '/../layouts/template.php';
?>