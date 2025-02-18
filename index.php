<?php
include './functions/functions.php';

$includeSetPlotName = 0;
$includeSetPrefName = 0;

$showHeader = 0;

// De body van de welkomstpagina
$bodyContent = "
    <!-- Hero Section -->
    <div class='min-h-screen flex flex-col justify-center items-center relative bg-cover bg-center' style='background-image: url(\"/images/background_background.jpg\");'>
        <!-- Extra Overlay met overlay_background.png -->
        <div class='absolute inset-0' style='background-image: url(\"/images/overlay_background.png\"); background-size: cover; background-position: center;'></div>
        <!-- Overlay voor een donker effect -->
        <div class='absolute inset-0 bg-black opacity-50'></div>
        
        <!-- Content in de hero -->
        <div class='relative z-10 text-center px-4'>
            <img src='/images/holding_the_drone_logo.png' alt='Holding the Drones' class='w-65 mx-auto mb-14'>
            <h1 class='text-5xl font-extrabold text-white mb-4'>Welkom bij Holding the Drones</h1>
            <p class='text-xl text-gray-200 mb-16'>Beheer al uw dronevluchten met ons innovatieve vluchtmanagementsysteem</p>
            <a href='./login' class='inline-block bg-blue-700 hover:bg-blue-800 text-white font-semibold py-3 px-6 rounded-full transition duration-300'>
                Inloggen
            </a>
        </div>
    </div>
    
    <!-- Extra Info Section (optioneel) -->
    <div class='py-12 bg-gray-100'>
        <div class='max-w-5xl mx-auto px-4'>
            <div class='grid md:grid-cols-3 gap-8'>
                <div class='text-center'>
                    <img src='/images/fast_icon.svg' alt='Snel' class='w-16 mx-auto mb-10'>
                    <h2 class='text-2xl font-bold mb-2'>Snel</h2>
                    <p class='text-gray-700'>Ervaar een snelle en efficiënte vluchtplanning.</p>
                </div>
                <div class='text-center'>
                    <img src='/images/secure_icon.svg' alt='Veilig' class='w-16 mx-auto mb-10'>
                    <h2 class='text-2xl font-bold mb-2'>Veilig</h2>
                    <p class='text-gray-700'>Onze tools zorgen voor maximale veiligheid.</p>
                </div>
                <div class='text-center'>
                    <img src='/images/organized_icon.svg' alt='Overzichtelijk' class='w-16 mx-auto mb-10'>
                    <h2 class='text-2xl font-bold mb-2'>Overzichtelijk</h2>
                    <p class='text-gray-700'>Altijd een helder overzicht van jouw operaties.</p>
                </div>
            </div>
        </div>
    </div>
";

include './includes/header.php';
?>
