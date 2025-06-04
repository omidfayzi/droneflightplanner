<?php
// app/views/profile.php
// Pagina voor gebruikersprofiel en instellingen (volledige breedte)

session_start();

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../functions.php'; // Zorg dat fetchPropPrefTxt() en login() hierin zitten.

// Login functie aanroepen (moet $_SESSION['user']['id'] instellen)
login();

// --- Valideer gebruikerssessie ---
$user = $_SESSION['user'] ?? [];
if (empty($user) || !isset($user['id'])) {
    $_SESSION['form_error'] = "U bent niet ingelogd of uw sessie is verlopen.";
    header("Location: landing-page.php"); // Of naar de inlogpagina
    exit;
}

$loggedInUserId = $user['id'];
$userName = htmlspecialchars($user['first_name'] ?? 'Onbekend', ENT_QUOTES, 'UTF-8');
// Vul $user['last_name'] en $user['email'] indien deze beschikbaar zijn in $_SESSION['user']
$userLastName = htmlspecialchars($user['last_name'] ?? '', ENT_QUOTES, 'UTF-8');
$userEmail = htmlspecialchars($user['email'] ?? 'geen@email.com', ENT_QUOTES, 'UTF-8');

// Teksten dynamisch ophalen (als fetchPropPrefTxt() werkt)
$txt = [
    'title' => fetchPropPrefTxt(19) ?: 'Profiel',
    'language' => fetchPropPrefTxt(22) ?: 'Taal',
    'language_nl' => fetchPropPrefTxt(20) ?: 'Nederlands',
    'language_en' => fetchPropPrefTxt(21) ?: 'Engels',
    'logout' => fetchPropPrefTxt(13) ?: 'Uitloggen',
    'idin_start' => fetchPropPrefTxt(23) ?: 'Start verificatie',
    'idin_unverified' => fetchPropPrefTxt(24) ?: 'Identiteit niet geverifieerd',
    'idin_verified' => fetchPropPrefTxt(10) ?: 'Geverifieerd',
    'organization' => fetchPropPrefTxt(26) ?: 'Organisatie',
    'save' => fetchPropPrefTxt(25) ?: 'Opslaan'
];

// Configuratie laden voor API-URL's (via Dotenv in config.php)
if (!defined('USER_ORG_API_BASE_URL')) {
    define('USER_ORG_API_BASE_URL', $_ENV['USER_ORG_DATABASE_BASE_URL'] ?? 'http://devserv01.holdingthedrones.com:4539');
}
if (!defined('USER_ORG_FUNCTIES_ENDPOINT')) {
    define('USER_ORG_FUNCTIES_ENDPOINT', USER_ORG_API_BASE_URL . '/functies');
}
if (!defined('USER_ORG_ORGANISATIES_ENDPOINT')) {
    define('USER_ORG_ORGANISATIES_ENDPOINT', USER_ORG_API_BASE_URL . '/organisaties');
}
if (!defined('USER_ORG_BEARER_TOKEN')) {
    define('USER_ORG_BEARER_TOKEN', $_ENV['USER_ORG_DATABASE_BEARER_TOKEN'] ?? 'JOUW_TOKEN_HIER');
}
$userOrgFunctiesApiUrl = USER_ORG_FUNCTIES_ENDPOINT;
$userOrgOrganisatiesApiUrl = USER_ORG_ORGANISATIES_ENDPOINT;
$userOrgBearerToken = USER_ORG_BEARER_TOKEN;


// --- GECENTRALISEERDE API HULPFUNCTIE (met BEARER TOKEN) ---
function callUserOrgApi(string $url, string $token): array
{
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'Accept: application/json',
            'Authorization: Bearer ' . $token
        ],
        CURLOPT_TIMEOUT => 10,
    ]);
    $resp = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($code !== 200 || $resp === false) {
        $error = curl_error($ch) ?: "HTTP Code $code";
        error_log("User/Org API Error ($url): $error - Response: " . ($resp ?: '(empty)'));
        return ['error' => $error];
    }
    $json = json_decode($resp, true);
    return is_array($json) ? $json : ['error' => "Invalid JSON response from User/Org API."];
}


// --- DATA OPHALEN VOOR DROPDOWNS (Organisaties en Functies) ---
$organisations = [];
$functions = [];

// Haal organisaties op
if (!empty($userOrgOrganisatiesApiUrl) && !empty($userOrgBearerToken)) {
    $organisationsData = callUserOrgApi($userOrgOrganisatiesApiUrl, $userOrgBearerToken);
    if (!isset($organisationsData['error'])) {
        $organisations = array_map(function ($org) {
            return ['id' => $org['organisatieId'], 'name' => $org['organisatienaam']];
        }, $organisationsData);
    } else {
        error_log("Fout bij ophalen organisaties voor profiel: " . $organisationsData['error']);
    }
}

// Haal functies op
if (!empty($userOrgFunctiesApiUrl) && !empty($userOrgBearerToken)) {
    $functionsData = callUserOrgApi($userOrgFunctiesApiUrl, $userOrgBearerToken);
    if (!isset($functionsData['error'])) {
        $functions = array_map(function ($func) {
            return ['id' => $func['functieId'], 'name' => $func['functieNaam']];
        }, $functionsData);
    } else {
        error_log("Fout bij ophalen functies voor profiel: " . $functionsData['error']);
    }
}


// --- AFHANDELING VAN AJAX POST REQUESTS (Opslaan Taal/Org/Functie selecties) ---
// Deze POST requests komen van JavaScript en updaten sessie/voorkeuren.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
    $inputJSON = file_get_contents('php://input');
    $requestData = json_decode($inputJSON, true);

    $action = $requestData['action'] ?? '';

    // Action: Save Language Preference
    if ($action === 'save_language') {
        $selectedLanguage = $requestData['language'] ?? 'nl';
        // Hier zou je $loggedInUserId kunnen gebruiken om de voorkeurstaal in je DB op te slaan
        $_SESSION['language'] = $selectedLanguage; // Update sessie voor volgende pagina's
        http_response_code(200);
        echo json_encode(['status' => 'success', 'message' => 'Taalvoorkeur opgeslagen!']);
        exit;
    }

    // Action: Save Organization/Function Selection
    if ($action === 'save_org_function') {
        $selectedOrgId = $requestData['organisation_id'] ?? null;
        $selectedFunctionId = $requestData['function_id'] ?? null;

        if ($selectedOrgId === null || $selectedFunctionId === null || $selectedOrgId === '' || $selectedFunctionId === '') {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'Geen organisatie of functie geselecteerd.']);
            exit;
        }

        // Opslaan in de sessie (consistent met landing-page.php)
        $_SESSION['selected_organisation_id'] = ($selectedOrgId === '0') ? null : (int)$selectedOrgId;
        $_SESSION['selected_function_id'] = (int)$selectedFunctionId;

        // Dit is de cruciale update voor de user_id die door de SORA API wordt gebruikt!
        // We stellen de actieve 'user_id' voor de flow in als de ingelogde user's ID
        // DIT MOET EEN BESTAAND USER ID IN DE SORA API DATABASE ZIJN!
        // Het 'gebruiker' concept hier is niet 'selecteer een ANDERE gebruiker om deze flow uit te voeren als',
        // maar 'uitvoeren AS ingelogde gebruiker, of als onderdeel van een organisatie'.
        // Dus de $_SESSION['user_id'] die voor SORA gebruikt wordt, is gewoon de ingelogde user's ID.
        // Als je functie-ID's ook gebruikers-ID's zouden kunnen zijn, is dit complexer.
        // Maar volgens /functies endpoint is 'functie' een ROL.

        http_response_code(200);
        echo json_encode(['status' => 'success', 'message' => 'Organisatie- en functieselectie opgeslagen!']);
        exit;
    }

    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Ongeldige AJAX-actie.']);
    exit;
}


// --- HUIDIGE PAGINA LAYOUT HTML ---
?>
<!DOCTYPE html>
<html lang="nl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $txt['title'] ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        /* CSS Variabelen voor kleuren consistentie */
        :root {
            --primary-dark: #313234;
            --primary-light: #525458;
            --secondary-blue: #2563EB;
            --light-grey-bg: #F3F4F6;
            --white-surface: #FFFFFF;
            --light-surface-alt: #F9FAFB;
            --border-light: #E5E7EB;
            --accent-green: #28a745;
            --accent-red: #DC2626;
            --accent-yellow: #feca57;
        }

        /* Basis en algemene layout aanpassingen voor volle breedte */
        body {
            margin: 0;
            font-family: 'Montserrat', sans-serif;
            background-color: var(--light-grey-bg);
            /* Volledige achtergrond */
            color: var(--primary-dark);
            padding: 0;
            /* Geen padding op body, dit wordt door de container beheerd */
            min-height: 100vh;
            /* Zorgt dat de body minimaal de hoogte van de viewport is */
        }

        /* De Hoofdcontainer van de Profielpagina */
        .main-page-container {
            width: 100%;
            /* Neemt de volle breedte */
            max-width: 100%;
            /* Geen limiet op max-width */
            margin: 0 auto;
            /* Centreert wat erin zit */
            background-color: var(--light-grey-bg);
            /* Dezelfde kleur als body om seamless te zijn */
            box-shadow: none;
            /* Geen shadow hier, elementen erin krijgen shadows */
            overflow: hidden;
            /* Houdt inhoud binnen */
            padding-bottom: 50px;
            /* Wat ruimte onderaan voor de scroll */
        }

        /* Top Navigatie/Kruimelpad */
        .top-nav-bar {
            padding: 15px 40px;
            /* Ruime padding */
            border-bottom: 1px solid var(--border-light);
            background-color: var(--white-surface);
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
            /* Zachte schaduw */
        }

        .top-nav-bar a {
            color: var(--secondary-blue);
            text-decoration: none;
            font-weight: 500;
        }

        .top-nav-bar a:hover {
            text-decoration: underline;
        }

        .btn-back-dashboard {
            background-color: var(--light-surface-alt);
            border: 1px solid var(--border-light);
            color: var(--primary-dark);
            padding: 8px 15px;
            border-radius: 8px;
            font-weight: 500;
            transition: all 0.2s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        .btn-back-dashboard:hover {
            background-color: var(--border-light);
        }

        /* Profielkaart (de centrale kaart voor de content) */
        .profile-card {
            max-width: 1100px;
            /* Nu relatief breed */
            margin: 30px auto;
            /* Centeert de kaart horizontaal binnen de container */
            background: var(--white-surface);
            border-radius: 12px;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.08);
            /* Consistent met organisatie.php */
            overflow: hidden;
            padding: 0;
            /* Padding is nu binnen de secties */
        }

        /* Profielheader Sectie */
        .profile-header-section {
            background: #313234;
            /* Consistent gradient */
            padding: 40px;
            /* Ruimere padding */
            color: white;
            text-align: center;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .profile-header-section .avatar {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.2);
            /* Transparanter wit */
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3rem;
            font-weight: 600;
            color: white;
            margin-bottom: 15px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
        }

        .profile-header-section h1 {
            font-size: 2.5rem;
            font-weight: 700;
            color: white;
            margin-bottom: 5px;
        }

        .profile-header-section p {
            font-size: 1.1rem;
            opacity: 0.8;
        }

        .profile-header-section .logout-button {
            background-color: transparent;
            border: 1px solid rgba(255, 255, 255, 0.5);
            color: white;
            padding: 8px 15px;
            border-radius: 8px;
            text-decoration: none;
            margin-top: 20px;
            font-weight: 500;
            transition: background-color 0.2s ease, border-color 0.2s ease;
        }

        .profile-header-section .logout-button:hover {
            background-color: rgba(255, 255, 255, 0.1);
            border-color: white;
        }

        .profile-header-section .logout-button svg {
            width: 1.25rem;
            height: 1.25rem;
            fill: white;
            margin-right: 8px;
        }


        /* Algemene secties (Instellingen, Verificatie) */
        .profile-section {
            padding: 30px 40px;
            /* Ruime padding horizontaal */
            border-bottom: 1px solid var(--border-light);
            /* Scheiding */
        }

        .profile-section:last-child {
            border-bottom: none;
            /* Geen border op de laatste sectie */
        }

        .profile-section h2 {
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--primary-dark);
            margin-bottom: 1.5rem;
        }

        /* Formulier/Select elementen */
        .form-select-full {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 1px solid var(--border-light);
            border-radius: 0.5rem;
            background: var(--light-surface-alt);
            font-size: 1rem;
            transition: all 0.2s ease;
            margin-bottom: 1.5rem;
            /* Consistentere marge */
        }

        .form-select-full:focus {
            outline: none;
            box-shadow: 0 0 0 2px var(--secondary-blue);
        }

        button[type="submit"] {
            background: var(--secondary-blue);
            color: white;
            border: none;
            cursor: pointer;
            font-weight: 500;
            padding: 0.75rem 1rem;
            border-radius: 0.5rem;
            transition: all 0.2s ease;
        }

        button[type="submit"]:hover {
            background: #1D4ED8;
        }

        .response-message {
            margin-top: 20px;
            padding: 1rem;
            border-radius: 0.5rem;
            font-size: 0.9rem;
            text-align: center;
            font-weight: 500;
        }

        .response-message.success {
            background-color: #d1fae5;
            color: #065f46;
        }

        /* Groen */
        .response-message.error {
            background-color: #fee2e2;
            color: #991b1b;
        }

        /* Rood */
        .response-message.info {
            background-color: #bfdbfe;
            color: #1e3a8a;
        }

        /* Info blauw */
        .response-message.hidden {
            display: none;
        }


        /* IDIN Verificatie sectie */
        .verification-section {
            background-color: var(--light-surface-alt);
            padding: 30px 40px;
            border-radius: 0 0 12px 12px;
            /* Afronden onderkant */
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            /* Zachte schaduw */
        }

        .verification-section h2 {
            color: var(--secondary-blue);
            margin-bottom: 1rem;
        }

        .idin-status-box {
            /* Nieuwe container voor IDIN status en knop */
            border: 1px dashed var(--border-light);
            border-radius: 0.5rem;
            padding: 15px;
            min-height: 80px;
            display: flex;
            flex-direction: column;
            /* Icon + text onder elkaar, dan knop eronder */
            align-items: center;
            justify-content: center;
            gap: 10px;
            /* Ruimte tussen elementen */
            text-align: center;
        }

        .status-text {
            font-size: 1rem;
            font-weight: 500;
            color: var(--primary-dark);
        }

        .status-icon-text {
            /* Voor Icon + text als een geheel */
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .status-icon {
            font-size: 1.5rem;
        }

        .loading-text {
            animation: pulse 1.5s infinite ease-in-out;
            color: #777;
        }

        @keyframes pulse {
            0% {
                opacity: 1;
            }

            50% {
                opacity: 0.6;
            }

            100% {
                opacity: 1;
            }
        }

        .idin-start-button {
            background-color: var(--secondary-blue);
            color: white;
            border: none;
            cursor: pointer;
            padding: 10px 20px;
            border-radius: 8px;
            transition: background-color 0.2s;
            font-weight: 500;
        }

        .idin-start-button:hover {
            background-color: #1D4ED8;
        }

        .hidden {
            display: none;
        }

        /* Generiek voor verbergen */

        /* Kleine aanpassing voor Bootstrap overrides, als ze direct geladen zijn */
        .alert {
            margin-top: 1.5rem;
        }
    </style>
</head>

<body>
    <!-- HEADER BAR BOVENIN, als die wordt geinclude via component/header.php -->
    <?php // include $headerPath; // Deel van jouw bestaande app structuur 
    ?>

    <div class="main-page-container">
        <!-- TOP NAVIGATIE / KRUIMELPAD -->
        <div class="top-nav-bar">
            <nav class="text-sm text-gray-600">
                <a href="dashboard.php" class="font-semibold">Dashboard</a>
                <span class="mx-2">/</span>
                <span>Mijn Profiel</span>
            </nav>
            <a href="dashboard.php" class="btn-back-dashboard">
                <i class="fas fa-arrow-left me-2"></i> Terug naar Dashboard
            </a>
        </div>

        <!-- CENTRALE PROFIEL KAART -->
        <div class="profile-card">
            <!-- Profiel Header Sectie (Jouw naam, e-mail, logout) -->
            <div class="profile-header-section">
                <div class="avatar">
                    <?= strtoupper(substr($user['first_name'] ?? 'U', 0, 1)) ?>
                </div>
                <h1><?= $user['first_name'] ?? 'Onbekend' ?> <?= $userLastName ?></h1>
                <p><?= $userEmail ?></p>
                <a href="/logout.php" class="logout-button">
                    <svg fill="currentColor" viewBox="0 0 24 24" class="w-5 h-5">
                        <path d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                    </svg>
                    <span><?= $txt['logout'] ?></span>
                </a>
            </div>

            <!-- Instellingen & Voorkeuren Secties -->
            <div class="profile-section">
                <h2><?= $txt['language'] ?></h2>
                <label for="languageSelect" class="form-label">Taalvoorkeur:</label>
                <select id="languageSelect" class="form-select-full">
                    <option value="nl"><?= $txt['language_nl'] ?></option>
                    <option value="en"><?= $txt['language_en'] ?></option>
                </select>
                <button type="submit" onclick="saveLanguagePreference()" class="btn-action">Opslaan taalvoorkeur</button>
                <div id="languageResponseMessage" class="response-message hidden"></div>
            </div>

            <!-- Organisatie & Functie Selectie Sectie -->
            <div class="profile-section">
                <h2>Organisatie & Functie</h2>
                <div class="form-field-group">
                    <label for="orgSelect" class="form-label"><?= $txt['organization'] ?>:</label>
                    <select id="orgSelect" class="form-select-full">
                        <option value="0"><?= htmlspecialchars($user['first_name'] ?? 'Individueel') ?> (Persoonlijk)</option>
                        <?php foreach ($organisations as $org): ?>
                            <option value="<?= htmlspecialchars($org['id']) ?>"><?= htmlspecialchars($org['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-field-group">
                    <label for="functionSelect" class="form-label">Geselecteerde Functie:</label>
                    <select id="functionSelect" class="form-select-full">
                        <option value="" disabled selected>Selecteer uw functie</option>
                        <?php foreach ($functions as $func): ?>
                            <option value="<?= htmlspecialchars($func['id']) ?>"><?= htmlspecialchars($func['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" onclick="saveOrgAndFunction()" class="btn-action">Selectie opslaan</button>
                <div id="orgFunctionResponseMessage" class="response-message hidden"></div>
            </div>

            <!-- IDIN Verificatie Sectie -->
            <div class="verification-section">
                <h2>Identiteitsverificatie</h2>
                <div id="idinStatus" class="idin-status-box">
                    <div class="status-icon-text">
                        <i class="fas fa-spinner fa-spin status-icon"></i>
                        <span class="status-text loading-text"><?= $txt['idin_unverified'] ?>...</span>
                    </div>
                </div>
                <button type="button" onclick="startIdinVerification()" class="idin-start-button hidden">Start IDIN verificatie</button>
            </div>

        </div>
    </div>

    <!-- Bootstrap 5 JavaScript Bundle met Popper (op het einde van de body) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        // JS GLOBAL REFERENCES
        const currentLoggedInUserId = <?= json_encode($loggedInUserId) ?>; // PHP-variabele in JS context

        // AJAX POST UTILITY FUNCTION (generiek voor deze pagina)
        async function postJson(url, payload) {
            try {
                const response = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify(payload)
                });
                if (!response.ok) {
                    const errorData = await response.json().catch(() => response.text());
                    throw new Error(errorData.message || JSON.stringify(errorData) || `HTTP error! status: ${response.status}`);
                }
                return await response.json();
            } catch (error) {
                console.error('AJAX Error:', error);
                throw error; // Re-throw for specific handlers
            }
        }

        // --- HULPFUNCTIES VOOR DE UI & STATUS MELDINGEN ---
        function showMessage(elementId, message, type = 'info') {
            const div = document.getElementById(elementId);
            if (!div) return;
            div.classList.remove('hidden', 'success', 'error', 'info', 'alert-success', 'alert-danger', 'alert-info');
            div.textContent = message;
            if (type === 'success') div.classList.add('alert-success');
            if (type === 'error') div.classList.add('alert-danger');
            if (type === 'info') div.classList.add('alert-info');
        }

        function hideMessage(elementId) {
            const div = document.getElementById(elementId);
            if (div) div.classList.add('hidden');
        }

        // --- DOMContentLoaded EVENT LISTENER ---
        document.addEventListener('DOMContentLoaded', async () => {
            const languageSelect = document.getElementById('languageSelect');
            const orgSelect = document.getElementById('orgSelect');
            const functionSelect = document.getElementById('functionSelect');
            const idinStatusDiv = document.getElementById('idinStatus');
            const startIdinBtn = document.getElementById('startIdinBtn');

            // --- INSTELLEN INITIËLE TAALSELECTIE ---
            const currentSelectedLanguage = '<?= htmlspecialchars($_SESSION['language'] ?? 'nl') ?>';
            if (languageSelect) languageSelect.value = currentSelectedLanguage;

            // --- INSTELLEN INITIËLE ORG/FUNCTIE SELECTIE ---
            const currentSelectedOrgId = '<?= htmlspecialchars($_SESSION['selected_organisation_id'] ?? '0') ?>'; // '0' voor individueel
            const currentSelectedFunctionId = '<?= htmlspecialchars($_SESSION['selected_function_id'] ?? '') ?>';

            if (orgSelect) orgSelect.value = currentSelectedOrgId; // Let op: == vergelijking is cruciaal voor nummer vs string

            // Functie Selectie: alleen als er een default functie in sessie is of er options zijn
            if (functionSelect && functionSelect.options.length > 1) { // Check dat er meer is dan alleen de disabled placeholder
                if (currentSelectedFunctionId !== '') {
                    functionSelect.value = currentSelectedFunctionId;
                    if (functionSelect.value !== currentSelectedFunctionId) { // Fallback als value niet bestaat (bijv. user has a role no longer in system)
                        functionSelect.value = ''; // Reset to default placeholder
                        console.warn('Selected function ID from session not found in dropdown options. Resetting to default.');
                    }
                } else {
                    functionSelect.value = ''; // Selecteer placeholder indien niets is geselecteerd in sessie
                }
            } else {
                // Als er geen functies zijn om uit te kiezen (of slechts de placeholder), forceer dan de placeholder.
                if (functionSelect) functionSelect.value = '';
            }

            // --- iDIN STATUS INITIALISATIE ---
            // Simuleer een check van de iDIN status bij het laden van de pagina
            // In een echte implementatie: API call naar je iDIN backend om de status op te vragen.
            const isIdinVerified = Math.random() > 0.7; // 30% kans om verified te zijn voor demo
            setTimeout(() => { // Simuleer netwerklatentie voor IDIN
                if (!idinStatusDiv) return;

                const statusTextSpan = idinStatusDiv.querySelector('.status-text');
                const statusIconTextDiv = idinStatusDiv.querySelector('.status-icon-text');

                if (isIdinVerified) {
                    statusIconTextDiv.innerHTML = `<i class="fas fa-check-circle text-success status-icon"></i><span class="status-text">${IDIN_TXT_VERIFIED}</span>`;
                    startIdinBtn.classList.add('hidden'); // Verberg de knop als geverifieerd
                } else {
                    statusIconTextDiv.innerHTML = `<i class="fas fa-times-circle text-danger status-icon"></i><span class="status-text">${IDIN_TXT_UNVERIFIED}</span>`;
                    startIdinBtn.classList.remove('hidden'); // Toon de knop
                }
                statusTextSpan.classList.remove('loading-text'); // Stop pulse animatie
            }, 1500); // 1.5 seconde latIdency


            // --- Event Listeners voor Opslaan ---
            // Slaan taalvoorkeur op
            document.getElementById('saveSettingsBtn').addEventListener('click', async () => {
                const selectedLanguage = languageSelect.value;
                showMessage('languageResponseMessage', 'Bezig met opslaan taalvoorkeur...', 'info');

                try {
                    const response = await postJson(window.location.href, {
                        action: 'save_language',
                        language: selectedLanguage
                    });
                    if (response.status === 'success') {
                        showMessage('languageResponseMessage', response.message, 'success');
                        // Opslaan in sessie is server-side, hier tonen we alleen melding.
                        // window.location.reload(); // Herlaad pagina indien taal direct moet veranderen
                    } else {
                        showMessage('languageResponseMessage', response.message, 'error');
                    }
                } catch (error) {
                    showMessage('languageResponseMessage', `Netwerkfout: ${error.message}`, 'error');
                }
            });

            // Slaan Organisatie- en Functieselectie op
            document.getElementById('saveOrgAndFunctionBtn').addEventListener('click', async () => {
                const selectedOrgId = orgSelect.value;
                const selectedFunctionId = functionSelect.value;
                const responseDiv = document.getElementById('orgFunctionResponseMessage');

                if (selectedOrgId === "" || functionSelect.selectedIndex === 0 || selectedFunctionId === "") {
                    showMessage('orgFunctionResponseMessage', 'Selecteer alstublieft zowel een organisatie als een functie.', 'error');
                    return;
                }

                showMessage('orgFunctionResponseMessage', 'Bezig met opslaan selectie...', 'info');
                try {
                    const response = await postJson(window.location.href, {
                        action: 'save_org_function',
                        organisation_id: selectedOrgId,
                        function_id: selectedFunctionId
                    });
                    if (response.status === 'success') {
                        showMessage('orgFunctionResponseMessage', response.message, 'success');
                        // Na succesvol opslaan in sessie, redirect naar dashboard
                        setTimeout(() => {
                            window.location.href = 'dashboard.php';
                        }, 1000);
                    } else {
                        showMessage('orgFunctionResponseMessage', response.message, 'error');
                    }
                } catch (error) {
                    showMessage('orgFunctionResponseMessage', `Netwerkfout: ${error.message}`, 'error');
                }
            });

            // Start IDIN verificatie
            document.getElementById('startIdinBtn').addEventListener('click', async () => {
                showMessage('idinStatus', 'Bezig met starten verificatie...', 'info');
                startIdinVerification(); // Roep de iDIN flow aan
            });

            // --- HUIDIGE IDIN VERIFICATIE TEKSTEN (voor consistentie met de mock) ---
            const IDIN_TXT_VERIFIED = "<?= $txt['idin_verified'] ?>";
            const IDIN_TXT_UNVERIFIED = "<?= $txt['idin_unverified'] ?>";
        }); // Einde DOMContentLoaded

        // --- iDIN Mock Functionaliteit (gebruikt door IDIN verificatie sectie) ---
        async function startIdinVerification() {
            const idinStatusDiv = document.getElementById('idinStatus');
            const startIdinBtn = document.getElementById('startIdinBtn');

            // Simuleer een proces voor iDIN verificatie
            idinStatusDiv.innerHTML = `<div class="status-icon-text"><i class="fas fa-spinner fa-spin status-icon"></i><span class="status-text">Verificatie starten...</span></div>`;
            startIdinBtn.classList.add('hidden'); // Verberg de knop tijdens het proces

            setTimeout(() => {
                const verificationSuccessful = Math.random() > 0.3; // 70% kans op succes voor demo

                if (verificationSuccessful) {
                    idinStatusDiv.innerHTML = `<div class="status-icon-text"><i class="fas fa-check-circle text-success status-icon"></i><span class="status-text">${IDIN_TXT_VERIFIED}</span></div>`;
                } else {
                    idinStatusDiv.innerHTML = `<div class="status-icon-text"><i class="fas fa-times-circle text-danger status-icon"></i><span class="status-text">${IDIN_TXT_UNVERIFIED}</span></div>`;
                    startIdinBtn.classList.remove('hidden'); // Toon knop opnieuw bij mislukking
                }
                // Update globale boodschap voor consistentie, al is dit lokaal binnen de div
                showMessage('idinStatus', verificationSuccessful ? 'Verificatie voltooid.' : 'Verificatie mislukt. Probeer opnieuw.', verificationSuccessful ? 'success' : 'error');

            }, 2000); // Simuleer 2 seconden proces
        }
    </script>
</body>

</html>