<?php
// app/views/profile.php
// Pagina voor gebruikersprofiel en instellingen (volledige breedte)

session_start();

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../functions.php';

// De login() functie controleert of de gebruiker is ingelogd.
login();

// --- STAP 1: GEBRUIKER VALIDEREN ---
// Haal de gebruikersgegevens op uit de sessie.
$user = $_SESSION['user'] ?? [];
if (empty($user) || !isset($user['id'])) {
    // Als er geen gebruiker in de sessie zit, stuur terug naar de landingspagina.
    $_SESSION['form_error'] = "U bent niet ingelogd of uw sessie is verlopen.";
    header("Location: landing-page.php");
    exit;
}

// Sla de gebruikersgegevens op in variabelen voor makkelijker gebruik.
// htmlspecialchars() wordt gebruikt om XSS-aanvallen te voorkomen.
$loggedInUserId = $user['id'];
$userName = htmlspecialchars($user['first_name'] ?? 'Onbekend', ENT_QUOTES, 'UTF-8');
$userLastName = htmlspecialchars($user['last_name'] ?? '', ENT_QUOTES, 'UTF-8');
$userEmail = htmlspecialchars($user['email'] ?? 'geen@email.com', ENT_QUOTES, 'UTF-8');

// --- STAP 2: API-CONFIGURATIE & COMMUNICATIE ---
// Definieer de API-endpoints. Deze worden uit het .env bestand gehaald.
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

/**
 * Functie om een API-call te maken met een Bearer Token.
 */
function callUserOrgApi(string $url, string $token): array
{
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => ['Accept: application/json', 'Authorization: Bearer ' . $token],
        CURLOPT_TIMEOUT => 10,
    ]);
    $resp = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($code !== 200 || $resp === false) {
        $error = curl_error($ch) ?: "HTTP Code $code";
        error_log("User/Org API Error ($url): $error");
        return ['error' => $error];
    }
    return json_decode($resp, true) ?: ['error' => "Invalid JSON response"];
}

// --- STAP 3: DATA OPHALEN VOOR DROPDOWNS ---
$organisations = [];
$functions = [];

// Haal de lijst met organisaties op.
$organisationsData = callUserOrgApi($userOrgOrganisatiesApiUrl, $userOrgBearerToken);
if (!isset($organisationsData['error'])) {
    $organisations = array_map(fn($org) => ['id' => $org['organisatieId'], 'name' => $org['organisatienaam']], $organisationsData);
}

// Haal de lijst met functies (rollen) op.
$functionsData = callUserOrgApi($userOrgFunctiesApiUrl, $userOrgBearerToken);
if (!isset($functionsData['error'])) {
    $functions = array_map(fn($func) => ['id' => $func['functieId'], 'name' => $func['functieNaam']], $functionsData);
}

// --- STAP 4: AJAX REQUEST AFHANDELING ---
// Dit blok code wordt alleen uitgevoerd als de pagina wordt aangeroepen door JavaScript.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_SERVER['HTTP_X_REQUESTED_WITH'])) {
    $requestData = json_decode(file_get_contents('php://input'), true);
    $action = $requestData['action'] ?? '';

    // Actie om de taalvoorkeur op te slaan.
    if ($action === 'save_language') {
        $_SESSION['language'] = $requestData['language'] ?? 'nl';
        http_response_code(200);
        echo json_encode(['status' => 'success', 'message' => 'Taalvoorkeur opgeslagen!']);
        exit;
    }

    // Actie om de organisatie- en functiekeuze op te slaan.
    if ($action === 'save_org_function') {
        $_SESSION['selected_organisation_id'] = ($requestData['organisation_id'] === '0') ? null : (int)$requestData['organisation_id'];
        $_SESSION['selected_function_id'] = (int)$requestData['function_id'];
        http_response_code(200);
        echo json_encode(['status' => 'success', 'message' => 'Organisatie- en functieselectie opgeslagen!']);
        exit;
    }

    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Ongeldige actie.']);
    exit;
}
?>
<!DOCTYPE html>
<html lang="nl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profiel</title>
    <!-- Externe stylesheets -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
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
    <div class="main-page-container">
        <!-- Kruimelpad-navigatie bovenaan de pagina -->
        <div class="top-nav-bar">
            <nav>
                <a href="dashboard.php">Dashboard</a> / <span>Mijn Profiel</span>
            </nav>
            <a href="dashboard.php">Terug naar Dashboard</a>
        </div>

        <!-- De centrale kaart met alle profielinformatie -->
        <div class="profile-card">
            <!-- De donkere header met gebruikersnaam en avatar -->
            <div class="profile-header-section">
                <div class="avatar"><?= strtoupper(substr($userName, 0, 1)) ?></div>
                <h1><?= $userName ?> <?= $userLastName ?></h1>
                <p><?= $userEmail ?></p>
                <a href="/logout.php">Uitloggen</a>
            </div>

            <!-- Sectie voor het instellen van de taal -->
            <div class="profile-section">
                <h2>Taal</h2>
                <label for="languageSelect">Taalvoorkeur:</label>
                <select id="languageSelect" class="form-select-full">
                    <option value="nl">Nederlands</option>
                    <option value="en">Engels</option>
                </select>
                <button type="button" onclick="saveLanguagePreference()">Opslaan taalvoorkeur</button>
                <div id="languageResponseMessage" class="response-message hidden"></div>
            </div>

            <!-- Sectie voor het kiezen van organisatie en functie -->
            <div class="profile-section">
                <h2>Organisatie & Functie</h2>
                <div>
                    <label for="orgSelect">Organisatie:</label>
                    <select id="orgSelect" class="form-select-full">
                        <option value="0"><?= $userName ?> (Persoonlijk)</option>
                        <?php foreach ($organisations as $org): ?>
                            <option value="<?= htmlspecialchars($org['id']) ?>"><?= htmlspecialchars($org['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label for="functionSelect">Geselecteerde Functie:</label>
                    <select id="functionSelect" class="form-select-full">
                        <option value="" disabled selected>Selecteer uw functie</option>
                        <?php foreach ($functions as $func): ?>
                            <option value="<?= htmlspecialchars($func['id']) ?>"><?= htmlspecialchars($func['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="button" onclick="saveOrgAndFunction()">Selectie opslaan</button>
                <div id="orgFunctionResponseMessage" class="response-message hidden"></div>
            </div>

            <!-- Sectie voor iDIN-verificatie -->
            <div class="verification-section">
                <h2>Identiteitsverificatie</h2>
                <div id="idinStatus" class="idin-status-box">
                    <div class="status-icon-text">
                        <i class="fas fa-spinner fa-spin status-icon"></i>
                        <span class="status-text loading-text">Status ophalen...</span>
                    </div>
                </div>
                <button type="button" onclick="startIdinVerification()" id="startIdinBtn" class="idin-start-button hidden">Start IDIN verificatie</button>
            </div>
        </div>
    </div>

    <script>
        // --- JAVASCRIPT VOOR DE INTERACTIVITEIT VAN DE PAGINA ---

        /**
         * Stuurt de geselecteerde taal naar de server om op te slaan.
         */
        function saveLanguagePreference() {
            const selectedLanguage = document.getElementById('languageSelect').value;
            showMessage('languageResponseMessage', 'Bezig met opslaan...', 'info');

            postJson(window.location.href, {
                    action: 'save_language',
                    language: selectedLanguage
                })
                .then(response => {
                    showMessage('languageResponseMessage', response.message, 'success');
                })
                .catch(error => {
                    showMessage('languageResponseMessage', 'Opslaan mislukt: ' + error.message, 'error');
                });
        }

        /**
         * Stuurt de geselecteerde organisatie en functie naar de server om op te slaan.
         */
        function saveOrgAndFunction() {
            const selectedOrgId = document.getElementById('orgSelect').value;
            const selectedFunctionId = document.getElementById('functionSelect').value;

            if (!selectedFunctionId) {
                alert('Selecteer alstublieft een functie.');
                return;
            }

            showMessage('orgFunctionResponseMessage', 'Bezig met opslaan...', 'info');

            postJson(window.location.href, {
                    action: 'save_org_function',
                    organisation_id: selectedOrgId,
                    function_id: selectedFunctionId
                })
                .then(response => {
                    showMessage('orgFunctionResponseMessage', response.message, 'success');
                    setTimeout(() => window.location.reload(), 1000);
                })
                .catch(error => {
                    showMessage('orgFunctionResponseMessage', 'Opslaan mislukt: ' + error.message, 'error');
                });
        }

        /**
         * Een herbruikbare functie om data via een POST request te versturen.
         */
        async function postJson(url, payload) {
            const response = await fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify(payload)
            });
            if (!response.ok) {
                const errorData = await response.json().catch(() => ({
                    message: 'Onbekende serverfout'
                }));
                throw new Error(errorData.message);
            }
            return response.json();
        }

        /**
         * Toont een bericht (bv. succes of fout) aan de gebruiker.
         */
        function showMessage(elementId, message, type) {
            const div = document.getElementById(elementId);
            div.className = 'response-message ' + type;
            div.textContent = message;
        }

        /**
         * Simuleert het starten van de iDIN-verificatie.
         */
        function startIdinVerification() {
            const idinStatusDiv = document.getElementById('idinStatus');
            const startIdinBtn = document.getElementById('startIdinBtn');
            idinStatusDiv.innerHTML = `<div class="status-icon-text"><i class="fas fa-spinner fa-spin status-icon"></i><span class="status-text">Verificatie starten...</span></div>`;
            startIdinBtn.classList.add('hidden');

            setTimeout(() => {
                const isVerified = Math.random() > 0.3; // 70% kans op succes voor demo
                updateIdinStatus(isVerified);
            }, 2000);
        }

        /**
         * Werkt de iDIN-status in de UI bij.
         */
        function updateIdinStatus(isVerified) {
            const idinStatusDiv = document.getElementById('idinStatus');
            const startIdinBtn = document.getElementById('startIdinBtn');
            const statusText = isVerified ? 'Geverifieerd' : 'Identiteit niet geverifieerd';
            const statusIcon = isVerified ? 'fa-check-circle text-success' : 'fa-times-circle text-danger';

            idinStatusDiv.innerHTML = `<div class="status-icon-text"><i class="fas ${statusIcon} status-icon"></i><span class="status-text">${statusText}</span></div>`;

            if (!isVerified) {
                startIdinBtn.classList.remove('hidden');
            }
        }

        // Zodra de pagina volledig is geladen, stellen we de dropdowns in en controleren we de iDIN-status.
        document.addEventListener('DOMContentLoaded', () => {
            // Stel de dropdowns in op de huidige waarden uit de sessie.
            const currentSelectedOrgId = '<?= $_SESSION['selected_organisation_id'] ?? '0' ?>';
            const currentSelectedFunctionId = '<?= $_SESSION['selected_function_id'] ?? '' ?>';
            document.getElementById('orgSelect').value = currentSelectedOrgId;
            if (currentSelectedFunctionId) {
                document.getElementById('functionSelect').value = currentSelectedFunctionId;
            }

            // Simuleer een check van de iDIN-status bij het laden.
            setTimeout(() => {
                const isVerified = Math.random() > 0.7; // 30% kans dat de gebruiker al geverifieerd is.
                updateIdinStatus(isVerified);
            }, 1500);
        });
    </script>
</body>

</html>