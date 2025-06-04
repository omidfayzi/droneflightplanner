<?php
// /var/www/public/frontend/pages/organisatie.php
// Pagina voor organisatie details (standalone overzicht en statistieken) - Met Container volle breedte

session_start();

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../functions.php'; // Voor login(), etc.

// Valideren van de gebruikersstatus
if (!isset($_SESSION['user']['id'])) {
    $_SESSION['form_error'] = "U moet ingelogd zijn om organisatiegegevens te bekijken.";
    header("Location: landing-page.php");
    exit;
}

$loggedInUserId = $_SESSION['user']['id'];
$organisationIdToView = $_GET['id'] ?? null; // Organisatie ID wordt verwacht via URL-parameter

// Haal MAIN_API_URL op
if (!defined('MAIN_API_URL')) {
    define('MAIN_API_URL', 'https://api2.droneflightplanner.nl');
}
$mainApiBaseUrl = MAIN_API_URL;

// --- GECENTRALISEERDE API HULPFUNCTIE voor MAIN_API_URL ---
function callMainApi(string $url, string $method = 'GET', array $payload = []): array
{
    $ch = curl_init($url);
    $options = [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => ['Accept: application/json'],
        CURLOPT_TIMEOUT => 20, // Timeout
    ];
    if (isset($_SESSION['user']['auth_token'])) {
        $options[CURLOPT_HTTPHEADER][] = 'Authorization: Bearer ' . $_SESSION['user']['auth_token'];
    }

    curl_setopt_array($ch, $options);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    error_log("Main API Call Log (Org View): URL: $url, HTTP: $httpCode, Response: " . ($response ?: '(empty)'));

    if ($response === false) {
        $error = curl_error($ch);
        return ['error' => "cURL Fout: $error"];
    }
    if ($httpCode >= 400) {
        $decodedError = json_decode($response, true);
        $errorMsg = $decodedError['message'] ?? $response ?: "Onbekende API Fout ($httpCode)";
        return ['error' => $errorMsg];
    }

    $json = json_decode($response, true);
    return is_array($json) ? $json : ['error' => "Ongeldige JSON response."];
}


// --- DATA OPHALEN OF HARDCODED FALLBACK ---
$organization = [];
$stats = [
    'personeel' => ['totaal' => 0, 'actief' => 0],
    'drones' => ['totaal' => 0, 'actief' => 0],
    'vluchten' => ['totaal' => 0, 'maand' => 0],
    'assets' => ['totaal' => 0]
];
$pageErrorMessage = null; // Specifieke error variabele voor deze pagina


// Voeg HARDCODED FALLBACK DATA TOE VOOR TESTS ZONDER GELDIG API RESULTAAT
// Deze fallback wordt ALLEEN gebruikt als de API call voor organisatie details mislukt,
// OF als $organisationIdToView null is.
if ($organisationIdToView === null) {
    $pageErrorMessage = "Geen organisatie ID opgegeven in de URL. Gebruik van hardcoded fallback data.";
    $organisationIdToView = 'HARDCODED_DEMO_ID';
}


// Probeer API te benaderen voor organisatie details als ID beschikbaar is en geen hardcoded demo
$apiOrgDetailsSuccess = false;
if ($organisationIdToView !== 'HARDCODED_DEMO_ID') {
    $orgDetailsResponse = callMainApi($mainApiBaseUrl . '/organisaties/' . $organisationIdToView);
    if (!isset($orgDetailsResponse['error']) && !empty($orgDetailsResponse)) {
        $organization = $orgDetailsResponse;
        $apiOrgDetailsSuccess = true;
    } else {
        error_log("FALLBACK_TRIGGERED: Failed to load organization details from API for ID " . $organisationIdToView . ". Error: " . ($orgDetailsResponse['error'] ?? 'Unknown. Using hardcoded fallback.'));
        $pageErrorMessage = $pageErrorMessage ?? "Fout bij laden organisatie details van API. Toont hardcoded testdata.";
    }
}

// Vul $organization en $stats met hardcoded data indien API mislukt of ID null was
if (!$apiOrgDetailsSuccess) {
    $isHardcodedOrg1 = ($organisationIdToView == 1); // Specifiek ID 1 kan speciale hardcoded data krijgen
    $organization = [
        'organisatieId' => $isHardcodedOrg1 ? 1 : ($organisationIdToView),
        'organisatienaam' => $isHardcodedOrg1 ? 'SkyView Drones B.V. (DEMO)' : 'Demo Organisatie ' . ($organisationIdToView),
        'kvkNummer' => $isHardcodedOrg1 ? '70112233' : 'X000Y' . ($organisationIdToView),
        'adres' => $isHardcodedOrg1 ? 'Luchtweg 12' : 'Teststraat 1',
        'postcode' => $isHardcodedOrg1 ? '1234AB' : '9999ZZ',
        'plaats' => $isHardcodedOrg1 ? 'Amsterdam' : 'Demostad',
        'land' => $isHardcodedOrg1 ? 'Nederland' : 'Testland',
        'isActive' => 1,
        '_entry_ID' => $loggedInUserId,
        '_entry_Date' => '2023-01-01T10:00:00.000Z'
    ];
    // Vul ook statistieken hardcoded in voor de fallback-modus
    $stats['personeel']['totaal'] = 12;
    $stats['personeel']['actief'] = 10;
    $stats['drones']['totaal'] = 5;
    $stats['drones']['actief'] = 3;
    $stats['vluchten']['totaal'] = 35;
    $stats['vluchten']['maand'] = 8;
    $stats['assets']['totaal'] = 22;
} else { // Organisatie succesvol geladen via API, laad dan de ECHTE STATISTIEKEN via API
    $personnelResponse = callMainApi($mainApiBaseUrl . '/gebruikers?organisatieId=' . $organisationIdToView);
    if (!isset($personnelResponse['error'])) {
        $stats['personeel']['totaal'] = count($personnelResponse);
        $stats['personeel']['actief'] = count(array_filter($personnelResponse, fn($p) => ($p['isActive'] ?? 0) == 1));
    }

    $dronesResponse = callMainApi($mainApiBaseUrl . '/drones?organisatieId=' . $organisationIdToView);
    if (!isset($dronesResponse['error'])) {
        $stats['drones']['totaal'] = count($dronesResponse);
        $stats['drones']['actief'] = count(array_filter($dronesResponse, fn($d) => ($d['isActive'] ?? 0) == 1));
    }

    $flightsResponse = callMainApi($mainApiBaseUrl . '/vluchten?DFPPVlucht_OrganisatieId=' . $organisationIdToView);
    if (!isset($flightsResponse['error'])) {
        $stats['vluchten']['totaal'] = count($flightsResponse);
        $oneMonthAgo = new DateTime('-1 month');
        $stats['vluchten']['maand'] = count(array_filter($flightsResponse, function ($flight) use ($oneMonthAgo) {
            $flightDateStr = $flight['DFPPVlucht_Datum'] ?? null;
            if ($flightDateStr) {
                try {
                    $flightDate = new DateTime($flightDateStr);
                    return new DateTime($flightDateStr) >= $oneMonthAgo;
                } catch (Exception $e) {
                }
            }
            return false;
        }));
    }

    $assetsResponse = callMainApi($mainApiBaseUrl . '/overigeassets?eigenaar=' . $organisationIdToView);
    if (!isset($assetsResponse['error'])) {
        $stats['assets']['totaal'] = count($assetsResponse);
    }
}


// --- PAGINA VARIABELEN ---
$headTitle = htmlspecialchars($organization['organisatienaam'] ?? 'Organisatie Detail');

// Default waarde voor logo
$orgLogoUrl = 'https://via.placeholder.com/120/EEEEEE/888888?text=ORG+Logo';

?>
<!DOCTYPE html>
<html lang="nl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $headTitle ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body {
            background-color: #f0f2f5;
            font-family: 'Montserrat', sans-serif;
            color: #333;
            /* GEEN PADDING MEER HIER, GESTUURD DOOR CONTAINER */
        }

        .main-container {
            /* Verwijdert max-width voor volledige breedte, voegt horizontal padding toe */
            width: 100%;
            margin: 0 auto;
            /* Centrale positie */
            background-color: #fff;
            border-radius: 0;
            /* Geen randen, gaat volledig van edge to edge */
            box-shadow: none;
            /* Geen schaduw op de hoofdcontainer */
            overflow: hidden;
            padding: 0 40px;
            /* Horizontale padding NU OP DE CONTAINER ZELF */
        }

        /* Voeg responsieve padding toe voor kleinere schermen */
        @media (max-width: 768px) {
            .main-container {
                padding: 0 15px;
                /* Minder padding op kleine schermen */
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

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 10px 15px;
            font-weight: 500;
            border-radius: 8px;
            transition: all 0.2s ease;
        }

        .btn-primary {
            background-color: #3b82f6;
            border-color: #3b82f6;
            color: white;
        }

        .btn-primary:hover {
            background-color: #2563eb;
            border-color: #2563eb;
        }

        .btn-secondary {
            background-color: #e2e8f0;
            border-color: #cbd5e0;
            color: #4a5568;
        }

        .btn-secondary:hover {
            background-color: #cbd5e0;
            border-color: #a0aec0;
        }
    </style>
</head>

<body>
    <div class="main-container">
        <!-- Kruimelpad & Navigatie -->
        <div class="p-4 border-bottom d-flex justify-content-between align-items-center">
            <nav class="text-sm text-gray-600">
                <a href="dashboard.php" class="text-blue-500 hover:underline">Dashboard</a>
                <span class="mx-2">/</span>
                <span class="font-medium text-gray-800"><?= htmlspecialchars($organization['organisatienaam'] ?? 'Detail') ?></span>
            </nav>
        </div>

        <?php if ($pageErrorMessage): // Toon foutmelding bovenaan als API faalt of ID ontbreekt 
        ?>
            <div class="alert alert-danger mx-4 mt-3" role="alert"><?= htmlspecialchars($pageErrorMessage) ?></div>
        <?php endif; ?>

        <?php if ($organisationIdToView === null): // Geen ID meegegeven 
        ?>
            <div class="text-center p-5">
                <h2 class="text-2xl text-red-600 mb-4">Organisatie ID ontbreekt</h2>
                <p class="text-gray-600">U moet een organisatie ID opgeven in de URL (bijv. <code>organisatie.php?id=1</code>) om deze pagina te bekijken.</p>
                <a href="dashboard.php" class="btn btn-primary mt-4">Terug naar Dashboard</a>
            </div>
        <?php else: // Wel een ID meegegeven, toon profiel 
        ?>

            <!-- Profiel Header Card (Bovenste deel) -->
            <div class="profile-header-card">
                <div class="profile-img-container">
                    <img src="<?= htmlspecialchars($orgLogoUrl) ?>" alt="Organisatie Logo">
                </div>
                <h1><?= htmlspecialchars($organization['organisatienaam'] ?? 'N/A') ?></h1>
                <p class="mb-0"><?= htmlspecialchars(($organization['plaats'] ?? 'N/A') . ', ' . ($organization['land'] ?? 'N/A')) ?></p>
                <div class="d-flex align-items-center justify-content-center mt-3">
                    <span class="text-warning me-2">Score:</span> 4.7 <i class="fas fa-star text-warning ms-1"></i> (dummy)
                </div>
            </div>

            <!-- Statistieken Grid -->
            <div class="stats-grid">
                <div class="stat-card-small stat-card-personeel">
                    <div class="value"><?= htmlspecialchars($stats['personeel']['totaal']) ?></div>
                    <div class="label">Totaal Personeel</div>
                    <a href="personeelLijst.php?organisatieId=<?= htmlspecialchars($organization['organisatieId']) ?>" class="link">Bekijk <i class="fas fa-chevron-right ms-1"></i></a>
                </div>
                <div class="stat-card-small stat-card-drones">
                    <div class="value"><?= htmlspecialchars($stats['drones']['totaal']) ?></div>
                    <div class="label">Totaal Drones</div>
                    <a href="dronesLijst.php?organisatieId=<?= htmlspecialchars($organization['organisatieId']) ?>" class="link">Bekijk <i class="fas fa-chevron-right ms-1"></i></a>
                </div>
                <div class="stat-card-small stat-card-vluchten">
                    <div class="value"><?= htmlspecialchars($stats['vluchten']['totaal']) ?></div>
                    <div class="label">Vluchten Totaal</div>
                    <a href="vluchtenLijst.php?organisatieId=<?= htmlspecialchars($organization['organisatieId']) ?>" class="link">Bekijk <i class="fas fa-chevron-right ms-1"></i></a>
                </div>
                <div class="stat-card-small stat-card-assets">
                    <div class="value"><?= htmlspecialchars($stats['assets']['totaal']) ?></div>
                    <div class="label">Overige Assets</div>
                    <a href="assetsLijst.php?organisatieId=<?= htmlspecialchars($organization['organisatieId']) ?>" class="link">Bekijk <i class="fas fa-chevron-right ms-1"></i></a>
                </div>
            </div>

            <!-- Detailsecties van de Organisatiegegevens (Read-only) -->
            <div class="section-content">
                <div class="section-title">
                    Algemene Gegevens
                    <a href="organisatieBewerken.php?id=<?= htmlspecialchars($organization['organisatieId']) ?>" class="btn-edit-details">
                        <i class="fas fa-pencil-alt me-2"></i>Bewerken
                    </a>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-x-10 gap-y-4">
                    <div class="detail-item"><i class="fas fa-tag"></i>Naam: <strong><?= htmlspecialchars($organization['organisatienaam'] ?? 'N/A') ?></strong></div>
                    <div class="detail-item"><i class="fas fa-building"></i>KVK Nummer: <strong><?= htmlspecialchars($organization['kvkNummer'] ?? 'N.v.t.') ?></strong></div>
                    <div class="detail-item"><i class="fas fa-map-marker-alt"></i>Adres: <strong><?= htmlspecialchars($organization['adres'] ?? 'N.v.t.') ?></strong></div>
                    <div class="detail-item"><i class="fas fa-mail-bulk"></i>Postcode: <strong><?= htmlspecialchars($organization['postcode'] ?? 'N.v.t.') ?></strong></div>
                    <div class="detail-item"><i class="fas fa-city"></i>Plaats: <strong><?= htmlspecialchars($organization['plaats'] ?? 'N.v.t.') ?></strong></div>
                    <div class="detail-item"><i class="fas fa-globe"></i>Land: <strong><?= htmlspecialchars($organization['land'] ?? 'N.v.t.') ?></strong></div>
                    <div class="detail-item"><i class="fas fa-power-off"></i>Actief: <strong><?= ($organization['isActive'] ?? 0) ? 'Ja' : 'Nee' ?></strong></div>
                    <div class="detail-item"><i class="fas fa-id-badge"></i>Entry ID: <strong><?= htmlspecialchars($organization['_entry_ID'] ?? 'N.v.t.') ?></strong></div>
                    <div class="detail-item"><i class="fas fa-calendar-alt"></i>Entry Datum: <strong><?= htmlspecialchars((new DateTime($organization['_entry_Date'] ?? '1970-01-01'))->format('Y-m-d H:i') ?? 'N.v.t.') ?></strong></div>
                </div>
            </div>

            <!-- Optioneel: Sectie voor Contact Informatie (ala Jeremy Rose 'About' stijl) -->
            <div class="section-content">
                <h2 class="section-title">Contact Informatie (Dummy)</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-x-10 gap-y-4">
                    <div class="detail-item"><i class="fas fa-phone"></i>Telefoon: <strong>N.v.t.</strong></div>
                    <div class="detail-item"><i class="fas fa-envelope"></i>E-mail: <strong>N.v.t.</strong></div>
                    <div class="detail-item"><i class="fas fa-link"></i>Website: <strong>N.v.t.</strong></div>
                </div>
            </div>

        <?php endif; // Einde van else-tak voor $organisationIdToView === null 
        ?>

    </div>
</body>

</html>