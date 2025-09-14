<?php
// /var/www/public/frontend/pages/organisatie.php

// --- STAP 1: INITIALISATIE ---

// Start de PHP-sessie om toegang te krijgen tot ingelogde gebruikersgegevens.
session_start();
// Laad de centrale configuratie en algemene functies.
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../functions.php';

// --- STAP 2: BEVEILIGING ---

// Valideer of de gebruiker is ingelogd. Zo niet, stuur weg.
if (!isset($_SESSION['user']['id'])) {
    $_SESSION['form_error'] = "U moet ingelogd zijn om organisatiegegevens te bekijken.";
    header("Location: landing-page.php");
    exit;
}

// Sla het ID van de ingelogde gebruiker op.
$loggedInUserId = $_SESSION['user']['id'];

// --- STAP 3: BEPAAL WELKE ORGANISATIE GETOOND MOET WORDEN ---

// Kijk eerst of er een 'id' in de URL staat (bv. /organisatie.php?id=123).
// Zo niet, gebruik dan de organisatie die de gebruiker bij het inloggen heeft gekozen.
$organisationIdToView = $_GET['id'] ?? ($_SESSION['selected_organisation_id'] ?? null);

// --- STAP 4: API-CONFIGURATIE & COMMUNICATIE ---

// Definieer de basis-URL voor de API.
if (!defined('MAIN_API_URL')) {
    define('MAIN_API_URL', 'http://devserv01.holdingthedrones.com:4539');
}
$mainApiBaseUrl = MAIN_API_URL;

/**
 * Functie om een API-call te maken met cURL.
 * Deze functie is specifiek voor deze pagina.
 */
function callMainApi(string $url, string $method = 'GET', array $payload = []): array
{
    $ch = curl_init($url);
    $options = [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FAILONERROR => true,
        CURLOPT_HTTPHEADER => ['Accept: application/json'],
        CURLOPT_TIMEOUT => 20,
    ];

    if ($method === 'POST') {
        $options[CURLOPT_POST] = true;
        $options[CURLOPT_POSTFIELDS] = json_encode($payload);
        $options[CURLOPT_HTTPHEADER][] = 'Content-Type: application/json';
    }

    curl_setopt_array($ch, $options);
    $response = curl_exec($ch);

    if (curl_errno($ch)) {
        return ['error' => "CURL Fout: " . curl_error($ch)];
    }

    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($response === false) return ['error' => "API niet bereikbaar"];
    if ($httpCode >= 400) {
        $decodedError = json_decode($response, true);
        $errorMsg = $decodedError['message'] ?? $response ?: "Onbekende API Fout ($httpCode)";
        return ['error' => $errorMsg];
    }
    $json = json_decode($response, true);
    return is_array($json) ? $json : ['error' => "Ongeldige JSON response"];
}

// --- STAP 5: DATA OPHALEN VAN DE API ---

// Maak standaard (lege) variabelen aan om fouten te voorkomen.
$organization = [];
$stats = [
    'personeel' => ['totaal' => 0, 'actief' => 0],
    'drones' => ['totaal' => 0, 'actief' => 0],
    'vluchten' => ['totaal' => 0, 'maand' => 0],
    'assets' => ['totaal' => 0]
];
$pageErrorMessage = null;

// Variabele om bij te houden of het ophalen van de organisatie gelukt is.
$apiOrgDetailsSuccess = false;

// Haal alleen data op als we een ID hebben om op te zoeken.
if ($organisationIdToView !== null) {
    // Roep de API aan om de details van de specifieke organisatie op te halen.
    $orgDetailsResponse = callMainApi($mainApiBaseUrl . '/organisaties/' . $organisationIdToView);

    // Controleer of de API-call succesvol was.
    if (!isset($orgDetailsResponse['error']) && !empty($orgDetailsResponse)) {
        $organization = $orgDetailsResponse;
        $apiOrgDetailsSuccess = true;
    } else {
        // Als er een fout was, maak een foutmelding voor de gebruiker.
        $errorMsg = $orgDetailsResponse['error'] ?? 'Onbekende fout';
        $pageErrorMessage = "Fout bij laden organisatie details: " . htmlspecialchars($errorMsg);
    }
} else {
    // Als er geen organisatie-ID is, toon een duidelijke melding.
    $pageErrorMessage = "Geen organisatie geselecteerd. Gebruik het selectiescherm.";
}

// Als het ophalen van de organisatie gelukt is, haal dan ook de statistieken op.
if ($apiOrgDetailsSuccess) {
    // Haal het aantal personeelsleden op.
    $personnelResponse = callMainApi($mainApiBaseUrl . '/gebruikers?organisatieId=' . $organisationIdToView);
    if (!isset($personnelResponse['error'])) {
        $stats['personeel']['totaal'] = count($personnelResponse);
        $stats['personeel']['actief'] = count(array_filter($personnelResponse, fn($p) => ($p['isActive'] ?? 0) == 1));
    }

    // Haal het aantal drones op.
    $dronesResponse = callMainApi($mainApiBaseUrl . '/drones?organisatieId=' . $organisationIdToView);
    if (!isset($dronesResponse['error'])) {
        $stats['drones']['totaal'] = count($dronesResponse);
        $stats['drones']['actief'] = count(array_filter($dronesResponse, fn($d) => ($d['isActive'] ?? 0) == 1));
    }

    // Haal het aantal vluchten op.
    $flightsResponse = callMainApi($mainApiBaseUrl . '/vluchten?DFPPVlucht_OrganisatieId=' . $organisationIdToView);
    if (!isset($flightsResponse['error'])) {
        $stats['vluchten']['totaal'] = count($flightsResponse);
        // Bereken het aantal vluchten in de afgelopen maand.
        $oneMonthAgo = new DateTime('-1 month');
        $stats['vluchten']['maand'] = count(array_filter($flightsResponse, function ($flight) use ($oneMonthAgo) {
            $flightDateStr = $flight['DFPPVlucht_Datum'] ?? null;
            if ($flightDateStr) {
                try {
                    $flightDate = new DateTime($flightDateStr);
                    return $flightDate >= $oneMonthAgo;
                } catch (Exception $e) {
                    // Negeer ongeldige datums
                }
            }
            return false;
        }));
    }

    // Haal het aantal overige assets op.
    $assetsResponse = callMainApi($mainApiBaseUrl . '/overige_assets?eigenaar=' . $organisationIdToView);
    if (!isset($assetsResponse['error'])) {
        $stats['assets']['totaal'] = count($assetsResponse);
    }
}

// --- STAP 6: PAGINA-INSTELLINGEN VOOR DE TEMPLATE ---

// Bepaal de titel van de pagina.
$headTitle = htmlspecialchars($organization['organisatienaam'] ?? 'Organisatie Detail');
// Stel een standaard logo in als de organisatie er geen heeft.
$orgLogoUrl = $organization['logoUrl'] ?? 'https://via.placeholder.com/120/EEEEEE/888888?text=ORG+Logo';

?>
<!DOCTYPE html>
<html lang="nl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $headTitle ?></title>
    <!-- Laad externe stylesheets voor styling -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        /* Dit is de originele styling, 1-op-1 overgenomen. */
        body {
            background-color: #f0f2f5;
            font-family: 'Montserrat', sans-serif;
            color: #333;
        }

        .main-container {
            width: 100%;
            margin: 0 auto;
            background-color: #fff;
            border-radius: 0;
            box-shadow: none;
            overflow: hidden;
            padding: 0 40px;
        }

        @media (max-width: 768px) {
            .main-container {
                padding: 0 15px;
            }
        }

        .profile-header-card {
            background: #313234;
            color: white;
            padding: 30px;
            text-align: center;
            position: relative;
        }

        .profile-header-card .profile-img-container {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            border: 4px solid white;
            background-color: #eee;
            overflow: hidden;
            margin: 0 auto 15px auto;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
        }

        .profile-header-card .profile-img-container img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .profile-header-card h1 {
            font-size: 2.2rem;
            font-weight: 800;
            margin-bottom: 5px;
            color: white;
        }

        .profile-header-card p {
            font-size: 1rem;
            color: rgba(255, 255, 255, 0.8);
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 15px;
            padding: 20px 30px;
            background-color: #fff;
            border-bottom: 1px solid #eee;
        }

        .stat-card-small {
            border-radius: 8px;
            padding: 15px;
            box-shadow: 0 1px 8px rgba(0, 0, 0, 0.05);
            text-align: center;
            position: relative;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .stat-card-small .value {
            font-size: 1.8rem;
            font-weight: 800;
            margin-bottom: 5px;
        }

        .stat-card-small .label {
            font-size: 0.9rem;
            color: #555;
        }

        .stat-card-small .link {
            font-size: 0.85rem;
            color: #3b82f6;
            text-decoration: none;
            display: block;
            margin-top: 10px;
        }

        .stat-card-small .link:hover {
            text-decoration: underline;
        }

        .stat-card-personeel {
            background: linear-gradient(135deg, #e0f2f7, #c1e2f7);
            color: #01579b;
        }

        .stat-card-drones {
            background: linear-gradient(135deg, #e3f2fd, #bbdefb);
            color: #1565c0;
        }

        .stat-card-vluchten {
            background: linear-gradient(135deg, #ffe0b2, #ffcc80);
            color: #e65100;
        }

        .stat-card-assets {
            background: linear-gradient(135deg, #e6ffe6, #c2f0c2);
            color: #2e7d32;
        }

        .section-content {
            padding: 30px;
            border-bottom: 1px solid #eee;
        }

        .section-title {
            font-size: 1.7rem;
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 25px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .detail-item {
            display: flex;
            align-items: flex-start;
            margin-bottom: 12px;
            font-size: 1rem;
            color: #555;
            flex-wrap: wrap;
        }

        .detail-item i {
            margin-right: 10px;
            color: #666;
            width: 20px;
            text-align: center;
            flex-shrink: 0;
        }

        .detail-item strong {
            margin-left: 5px;
            color: #333;
        }

        .btn-edit-details {
            background-color: #3b82f6;
            color: white;
            padding: 8px 15px;
            border-radius: 6px;
            font-size: 0.9rem;
            text-decoration: none;
            transition: background-color 0.2s;
        }

        .btn-edit-details:hover {
            background-color: #2563eb;
        }
    </style>
</head>

<body>
    <div class="main-container">
        <!-- Kruimelpad-navigatie bovenaan de pagina -->
        <div class="p-4 border-bottom d-flex justify-content-between align-items-center">
            <nav class="text-sm text-gray-600">
                <a href="dashboard.php" class="text-blue-500 hover:underline">Dashboard</a>
                <span class="mx-2">/</span>
                <span class="font-medium text-gray-800"><?= htmlspecialchars($organization['organisatienaam'] ?? 'Detail') ?></span>
            </nav>
        </div>

        <?php if ($pageErrorMessage): ?>
            <!-- Toon een foutmelding als er iets mis is gegaan bij het ophalen van de data -->
            <div class="alert alert-danger mx-4 mt-3" role="alert"><?= $pageErrorMessage ?></div>
        <?php else: ?>
            <!-- Toon de pagina-inhoud als alles goed is gegaan -->

            <!-- Organisatie Header met logo en naam -->
            <div class="profile-header-card">
                <div class="profile-img-container">
                    <img src="<?= htmlspecialchars($orgLogoUrl) ?>" alt="Organisatie Logo">
                </div>
                <h1><?= htmlspecialchars($organization['organisatienaam'] ?? 'N/A') ?></h1>
                <p class="mb-0"><?= htmlspecialchars(($organization['plaats'] ?? 'N/A') . ', ' . ($organization['land'] ?? 'N/A')) ?></p>
            </div>

            <!-- Statistieken Grid met kaarten -->
            <div class="stats-grid">
                <div class="stat-card-small stat-card-personeel">
                    <div class="value"><?= htmlspecialchars($stats['personeel']['totaal']) ?></div>
                    <div class="label">Totaal Personeel</div>
                    <a href="personeelLijst.php?organisatieId=<?= htmlspecialchars($organization['organisatieId']) ?>" class="link">Bekijk</a>
                </div>
                <div class="stat-card-small stat-card-drones">
                    <div class="value"><?= htmlspecialchars($stats['drones']['totaal']) ?></div>
                    <div class="label">Totaal Drones</div>
                    <a href="dronesLijst.php?organisatieId=<?= htmlspecialchars($organization['organisatieId']) ?>" class="link">Bekijk</a>
                </div>
                <div class="stat-card-small stat-card-vluchten">
                    <div class="value"><?= htmlspecialchars($stats['vluchten']['totaal']) ?></div>
                    <div class="label">Vluchten Totaal</div>
                    <a href="vluchtenLijst.php?organisatieId=<?= htmlspecialchars($organization['organisatieId']) ?>" class="link">Bekijk</a>
                </div>
                <div class="stat-card-small stat-card-assets">
                    <div class="value"><?= htmlspecialchars($stats['assets']['totaal']) ?></div>
                    <div class="label">Overige Assets</div>
                    <a href="assetsLijst.php?organisatieId=<?= htmlspecialchars($organization['organisatieId']) ?>" class="link">Bekijk</a>
                </div>
            </div>

            <!-- Sectie met algemene gegevens van de organisatie -->
            <div class="section-content">
                <div class="section-title">
                    Algemene Gegevens
                    <a href="organisatieBewerken.php?id=<?= htmlspecialchars($organization['organisatieId']) ?>" class="btn-edit-details">
                        Bewerken
                    </a>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-x-10 gap-y-4">
                    <div class="detail-item">Naam: <strong><?= htmlspecialchars($organization['organisatienaam'] ?? 'N/A') ?></strong></div>
                    <div class="detail-item">KVK Nummer: <strong><?= htmlspecialchars($organization['kvkNummer'] ?? 'N.v.t.') ?></strong></div>
                    <div class="detail-item">Adres: <strong><?= htmlspecialchars($organization['adres'] ?? 'N.v.t.') ?></strong></div>
                    <div class="detail-item">Postcode: <strong><?= htmlspecialchars($organization['postcode'] ?? 'N.v.t.') ?></strong></div>
                    <div class="detail-item">Plaats: <strong><?= htmlspecialchars($organization['plaats'] ?? 'N.v.t.') ?></strong></div>
                    <div class="detail-item">Land: <strong><?= htmlspecialchars($organization['land'] ?? 'N.v.t.') ?></strong></div>
                </div>
            </div>

            <!-- Sectie met contactgegevens van de organisatie -->
            <div class="section-content">
                <h2 class="section-title">Contact Informatie</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-x-10 gap-y-4">
                    <div class="detail-item">
                        Telefoon: <strong><?= !empty($organization['telefoon']) ? htmlspecialchars($organization['telefoon']) : 'N.v.t.' ?></strong>
                    </div>
                    <div class="detail-item">
                        E-mail: <strong><?= !empty($organization['email']) ? htmlspecialchars($organization['email']) : 'N.v.t.' ?></strong>
                    </div>
                    <div class="detail-item">
                        Website:
                        <strong>
                            <?php if (!empty($organization['website'])): ?>
                                <a href="<?= htmlspecialchars($organization['website']) ?>" target="_blank"><?= htmlspecialchars($organization['website']) ?></a>
                            <?php else: ?>
                                N.v.t.
                            <?php endif; ?>
                        </strong>
                    </div>
                </div>
            </div>

        <?php endif; ?>
    </div>
</body>

</html>