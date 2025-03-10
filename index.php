<?php
// /src/index.php

// Start de sessie als deze nog niet gestart is
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Laad de Composer-autoloader
require __DIR__ . '/vendor/autoload.php';

// Laad de configuratie
require_once __DIR__ . '/config/config.php'; // Pas dit pad aan naar de juiste locatie

// Inclusie van backend-functies (indien nodig)
include __DIR__ . '/backend/functions/functions.php';

// Stel variabelen voor template.php
$showHeader = 0; // Geen navigatiemenu tonen
$headTitle = 'Drone Vluchtvoorbereidingssysteem';
$userName = $_SESSION['user']['first_name'] ?? 'Onbekend';
$org = isset($organisation) ? $organisation : 'Organisatie A'; // Dynamisch uit config.php, fallback naar Organisatie A
$gobackUrl = 0; // Geen terug-knop
$rightAttributes = 0; // Geen extra knoppen

// Welkomstpagina (landing-page) content met de juiste paden voor afbeeldingen en styling
$bodyContent = '
    <!-- Hero Section -->
    <div class="min-h-screen flex flex-col justify-center items-center relative bg-cover bg-center" style="background-image: url(\'/frontend/assets/images/background_background.jpg\');">
        <!-- Overlay met extra afbeelding -->
        <div class="absolute inset-0" style="background-image: url(\'/frontend/assets/images/overlay_background.png\'); background-size: cover; background-position: center;"></div>
        <!-- Donkere overlay -->
        <div class="absolute inset-0 bg-black opacity-50"></div>
        <!-- Inhoud van de Hero -->
        <div class="relative z-10 text-center px-4">
            <img src="/frontend/assets/images/holding_the_drone_logo.png" alt="Holding the Drones" class="w-65 mx-auto mb-14">
            <h1 class="text-5xl font-extrabold text-white mb-4">Welkom bij Holding the Drones</h1>
            <p class="text-xl text-gray-200 mb-16">Beheer al uw dronevluchten met ons innovatieve vluchtmanagementsysteem</p>
            <a href="/login" class="inline-block bg-blue-700 hover:bg-blue-800 text-white font-semibold py-3 px-6 rounded-full transition duration-300">
                Inloggen
            </a>
        </div>
    </div>
    <!-- Extra Info Section -->
    <div class="py-12 bg-gray-100">
        <div class="max-w-5xl mx-auto px-4">
            <div class="grid md:grid-cols-3 gap-8">
                <div class="text-center">
                    <img src="/frontend/assets/images/fast_icon.svg" alt="Snel" class="w-16 mx-auto mb-10">
                    <h2 class="text-2xl font-bold mb-2">Snel</h2>
                    <p class="text-gray-700">Ervaar een snelle en efficiënte vluchtplanning.</p>
                </div>
                <div class="text-center">
                    <img src="/frontend/assets/images/secure_icon.svg" alt="Veilig" class="w-16 mx-auto mb-10">
                    <h2 class="text-2xl font-bold mb-2">Veilig</h2>
                    <p class="text-gray-700">Onze tools zorgen voor maximale veiligheid.</p>
                </div>
                <div class="text-center">
                    <img src="/frontend/assets/images/organized_icon.svg" alt="Overzichtelijk" class="w-16 mx-auto mb-10">
                    <h2 class="text-2xl font-bold mb-2">Overzichtelijk</h2>
                    <p class="text-gray-700">Altijd een helder overzicht van jouw operaties.</p>
                </div>
            </div>
        </div>
    </div>
';

// Inclusie van template.php voor de volledige lay-out
require_once __DIR__ . '/frontend/pages/template.php';
?>