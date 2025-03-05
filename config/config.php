<?php
// /var/www/public/config/config.php
// Configuratiebestand voor de Drone Vluchtvoorbereidingssysteem applicatie

// Start de sessie alleen als deze nog niet actief is
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Laad Composer autoloader
require_once __DIR__ . '/../vendor/autoload.php';

// Laad omgevingsvariabelen uit .env-bestand
use Dotenv\Dotenv;
$dotenv = Dotenv::createImmutable('/var/www/env');
$dotenv->load();

// Definieer globale configuratie
return [
    'env' => [
        'overpassApiUrl' => $_ENV['OVERPASS_API_URL'] ?? 'https://overpass-api.de/api/interpreter',
        'mainUrl' => $_ENV['MAIN_URL'] ?? 'https://app2.droneflightplanner.nl',
        'bluemToken' => $_ENV['BLUEM_TOKEN'] ?? '00009800012f151b0701000000e3010700002ff7000ad9a5',
        'bluemSenderId' => $_ENV['BLUEM_SENDER_ID'] ?? 'S2336',
        'bluemIdinBrandId' => $_ENV['BLUEM_IDIN_BRAND_ID'] ?? 'HoldingtheDronesIdentity',
        'bluemIdealBrandId' => $_ENV['BLUEM_IDEAL_BRAND_ID'] ?? 'HoldingtheDronesPayment',
        'bluemCreateTransaction' => $_ENV['BLUEM_CREATE_TRANSACTION_URL'] ?? 'https://test.viamijnbank.net/ir/createTransactionWithToken',
        'bluemRequestTransaction' => $_ENV['BLUEM_REQUEST_TRANSACTION_URL'] ?? 'https://test.viamijnbank.net/ir/requestTransactionStatusWithToken',
        'mapBoxAccessToken' => $_ENV['MAPBOX_ACCESS_TOKEN'] ?? 'pk.eyJ1IjoiaG9sZGluZ3RoZWRyb25lcyIsImEiOiJjbTJrNGdha2EwOGFoMmtxcmk4N3k4aHVjIn0.eGMg9n4Lm0Z66YUftICFAg',
        'kadasterBagAccesToken' => $_ENV['KADASTER_BAG_ACCESS_TOKEN'] ?? 'l7683c0bb796eb4f4a88e8bdcfe9ea9c29',
        'kadasterKadataAccesToken' => $_ENV['KADASTER_KADATA_ACCESS_TOKEN'] ?? 'nogniet',
        'keyCloakClientSecret' => $_ENV['KEYCLOAK_CLIENT_SECRET'] ?? '2wfEwaBj0QwwV6274OmTTiCo4QQmEYMu',
        'mapboxGlCss' => $_ENV['MAPBOX_GL_CSS'] ?? 'https://api.mapbox.com/mapbox-gl-js/v2.11.0/mapbox-gl.css',
        'mapboxGlJs' => $_ENV['MAPBOX_GL_JS'] ?? 'https://api.mapbox.com/mapbox-gl-js/v2.11.0/mapbox-gl.js',
        'mapboxGlGeocoderJs' => $_ENV['MAPBOX_GL_GEOCODER_JS'] ?? 'https://api.mapbox.com/mapbox-gl-js/plugins/mapbox-gl-geocoder/v5.0.0/mapbox-gl-geocoder.min.js',
        'mapboxGlGeocoderCss' => $_ENV['MAPBOX_GL_GEOCODER_CSS'] ?? 'https://api.mapbox.com/mapbox-gl-js/plugins/mapbox-gl-geocoder/v5.0.0/mapbox-gl-geocoder.css',
        'jquery' => $_ENV['JQUERY'] ?? 'https://ajax.googleapis.com/ajax/libs/jquery/3.1.1/jquery.min.js',
        'jqueryScript' => $_ENV['JQUERY_SCRIPT'] ?? 'https://code.jquery.com/jquery-3.6.0.min.js',
        'tailwindDev' => $_ENV['TAILWIND_DEV'] ?? 'https://cdn.tailwindcss.com',
        'getTextWithId' => $_ENV['GET_TEXT_WITH_ID'] ?? 'https://api2.droneflightplanner.nl/get-txt-with-id/',
        'getAllPrefList' => $_ENV['GET_ALL_PREF_LIST'] ?? 'https://api2.droneflightplanner.nl/get-all-pref-lists',
        'getHighestIdPrefList' => $_ENV['GET_HIGHEST_ID_PREF_LIST'] ?? 'https://api2.droneflightplanner.nl/get-highest-id-pref-list',
        'getAllPlots' => $_ENV['GET_ALL_PLOTS'] ?? 'https://api2.droneflightplanner.nl/get-all-plots',
        'getAllLinkedPreferences' => $_ENV['GET_ALL_LINKED_PREFERENCES'] ?? 'https://api2.droneflightplanner.nl/get-all-linked-preferences',
        'getAllPreferences' => $_ENV['GET_ALL_PREFERENCES'] ?? 'https://api2.droneflightplanner.nl/get-preferences',
        'getUsersWithKeycloak' => $_ENV['GET_USERS_WITH_KEYCLOAK'] ?? 'https://api2.droneflightplanner.nl/get-users-with-keycloak/',
        'insertLinkedPreferences' => $_ENV['INSERT_LINKED_PREFERENCES'] ?? 'https://api2.droneflightplanner.nl/insert-linked-pref',
        'insertPlot' => $_ENV['INSERT_PLOT'] ?? 'https://api2.droneflightplanner.nl/addscanned/',
        'insertIntoPrefList' => $_ENV['INSERT_INTO_PREF_LIST'] ?? 'https://api2.droneflightplanner.nl/insert-into-pref-list/',
        'insertIntoUsers' => $_ENV['INSERT_INTO_USERS'] ?? 'https://api2.droneflightplanner.nl/insert-into-users/',
        'updateIdinCheckAddress' => $_ENV['UPDATE_IDIN_CHECK_ADRESS'] ?? 'https://api2.droneflightplanner.nl/update-idincheck-address/',
        'deleteFromPlots' => $_ENV['DELETE_FROM_PLOTS'] ?? '',
        'deleteFromLinkedPreferencesWithPropId' => $_ENV['DELETE_FROM_LINKED_PREFERENCES_WITH_PROP_ID'] ?? '',
        'mapBoxReverseGeocoding' => $_ENV['MAPBOX_REVERSE_GEOCODING'] ?? 'https://api.mapbox.com/geocoding/v5/mapbox.places/',
        'mapboxStaticImageApi' => $_ENV['MAPBOX_STATIC_IMAGE_API'] ?? 'https://api.mapbox.com/styles/v1/mapbox/streets-v11/static/pin-s+ff0000',
        'corsAnywhere' => $_ENV['CORS_ANYWHERE'] ?? 'https://cors-anywhere.herokuapp.com/',
        'userOrgDatabaseUser' => $_ENV['USER_ORG_DATABASE_USER'] ?? 'http://146.59.195.111:4539/user',
        'userOrgDatabaseOrg' => $_ENV['USER_ORG_DATABASE_ORG'] ?? 'http://146.59.195.111:4539/organisationFull',
        'userOrgDatabaseBearerToken' => $_ENV['USER_ORG_DATABASE_BEARER_TOKEN'] ?? 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJhdXRodG9rZW4iOiJiY28ybG5OVFhYTWZlUFNrVm1sZHFLaWFlTVh5WHc1VSIsImlhdCI6MTczMzk5NTI5NywiZXhwIjoxNzM0MDgxNjk3fQ.A3dMQDhKAgFowtxvjaFtetA-zCGoVf-P4HiQ0Eb6IKA',
        'kadasterUrl' => $_ENV['KADASTER_URL'] ?? 'https://api.bag.kadaster.nl/lvbag/individuelebevragingen/v2/adresseerbareobjecten/0226010000038820',
        'kadasterApiKey' => $_ENV['KADASTER_API_KEY'] ?? 'l7683c0bb796eb4f4a88e8bdcfe9ea9c29',
    ],
    'defaults' => [
        'showHeader' => 1,
        'userName' => 'Onbekend',
        'org' => '',
        'headTitle' => 'Drone Vluchtvoorbereidingssysteem',
        'gobackUrl' => 0,
        'rightAttributes' => 0,
        'bodyContent' => '<div class="container mx-auto p-4">
            <h1 class="text-3xl font-bold text-gray-900">Welkom bij het Drone Vluchtvoorbereidingssysteem</h1>
            <p class="mt-4 text-gray-700">Gebruik het navigatiemenu om verder te gaan.</p>
        </div>',
    ],
    'paths' => [
        'assets' => __DIR__ . '/frontend/assets/',
    ],
];