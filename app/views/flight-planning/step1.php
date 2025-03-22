<?php
// /var/www/public/frontend/pages/flight-planning/step1.php
// Vluchtplanning Stap 1

session_start();
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../functions.php';

// Stel variabelen in voor template.php
$showHeader = 1;
$userName = $_SESSION['user']['first_name'] ?? 'Onbekend'; // Gebruikersnaam uit sessie
$headTitle = "Basis"; // Paginatitel
$gobackUrl = 0; // Geen terug-knop
$rightAttributes = 0; // Geen logout, wel notificaties en profiel

// Body content voor Vluchtplanning Stap 1
$bodyContent = "
    <div class='h-[83.5vh] bg-gray-100 shadow-md rounded-tl-xl w-13/15'>

        <!-- Stappenbalk -->
        <div class='p-4 bg-gray-100'>
            <div class='flex justify-center items-center space-x-4'>
                <span class='w-8 h-8 bg-blue-600 text-white rounded-full flex items-center justify-center font-bold'>1</span>
                <div class='flex-1 h-1 bg-gray-300'></div>
                <span class='w-8 h-8 bg-gray-300 text-gray-600 rounded-full flex items-center justify-center'>2</span>
                <div class='flex-1 h-1 bg-gray-300'></div>
                <span class='w-8 h-8 bg-gray-300 text-gray-600 rounded-full flex items-center justify-center'>3</span>
                <div class='flex-1 h-1 bg-gray-300'></div>
                <span class='w-8 h-8 bg-gray-300 text-gray-600 rounded-full flex items-center justify-center'>4</span>
            </div>
        </div>

        <!-- Content -->
        <div class='p-6 overflow-y-auto max-h-[calc(90vh-200px)]'>
            <h2 class='text-xl font-bold mb-4 text-gray-800'>Basisgegevens</h2>
            <form action='step2.php' method='post' class='space-y-6'>
                <div>
                    <label class='block text-sm font-medium text-gray-700 mb-1'>Vluchttype</label>
                    <select name='flight_type' class='w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500' required>
                        <option value=''>Selecteer vluchttype</option>
                        <option value='bvlos' selected>BVLOS Routevlucht</option>
                        <option value='objectinspectie'>Objectinspectie</option>
                        <option value='thermische'>Thermische Scanning</option>
                    </select>
                </div>
                <div class='mt-6'>
                    <h3 class='text-lg font-semibold text-gray-800 mb-2'>4D Routeplanning</h3>
                    <div class='w-full h-72 bg-gray-200 rounded-lg flex items-center justify-center border border-dashed border-gray-400'>
                        <i class='fa-solid fa-location-dot text-gray-600 text-2xl'></i>
                    </div>
                </div>
                <div class='flex justify-between items-center'>
                    <p class='text-sm text-gray-500'>" . htmlspecialchars($userName) . "</p>
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