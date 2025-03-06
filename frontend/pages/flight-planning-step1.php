<?php
session_start();
require_once __DIR__ . '/../../config/config.php';

// Stel variabelen in voor template.php
$showHeader = 1;
$userName = $_SESSION['user']['first_name'] ?? 'Onbekend'; // Gebruikersnaam uit sessie
$org = isset($organisation) ? $organisation : 'Organisatie B'; // Dynamisch uit config.php of fallback
$headTitle = "Vluchtplanning";
$gobackUrl = 0; // Geen terug-knop
$rightAttributes = 0; // Geen logout, wel notificaties en profiel

// Body content voor Vluchtplanning Stap 1
$bodyContent = "
    <div class='h-[90vh] max-h-[90vh] mx-auto bg-white shadow-md rounded-tl-xl overflow-y-hidden w-13/15'>
        <!-- Hoofding -->
        <div class='p-4 bg-white border-b border-gray-200 flex justify-between items-center'>
            <div>
                <h1 class='text-2xl font-bold text-gray-900'>Vluchtplanning</h1>
                <p class='text-sm text-gray-500'>Laatste update: 15 minuten geleden, " . htmlspecialchars($org) . "</p>
            </div>
            <div class='flex items-center space-x-4'>
                <div class='relative'>
                    <i class='fa-solid fa-bell text-gray-600 hover:text-gray-800 cursor-pointer'></i>
                    <span class='absolute -top-1 -right-1 bg-red-600 text-white text-xs rounded-full w-4 h-4 flex items-center justify-center'>3</span>
                </div>
                <a href='/src/frontend/pages/profile.php' class='text-gray-600 hover:text-gray-800'>
                    <i class='fa-solid fa-user text-xl'></i>
                </a>
            </div>
        </div>

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
            <form action='/frontend/pages/flight-planning-step2.php' method='post' class='space-y-6'>
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

// Inclusie van template.php voor de volledige lay-out
require_once __DIR__ . '/template.php';
?>