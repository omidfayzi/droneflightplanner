<?php
// =================================================================
// organisatieRegistratie.php: Formulier om een nieuwe organisatie aan te maken.
// =================================================================

// --- STAP 1: INITIALISATIE & VEILIGHEIDSCONTROLE ---

// Zorg ervoor dat de sessie is gestart.
session_start();

// Laad de centrale configuratie en algemene functies.
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../functions.php';

// Beveiligingscheck: alleen ingelogde gebruikers mogen een organisatie registreren.
if (!isset($_SESSION['user']['id'])) {
    $_SESSION['form_error'] = "U moet ingelogd zijn om een organisatie te registreren.";
    header("Location: landing-page.php");
    exit;
}

// --- STAP 2: API-CONFIGURATIE & COMMUNICATIE ---

// Definieer de API-endpoint voor het aanmaken van een organisatie.
if (!defined('MAIN_API_URL')) {
    define('MAIN_API_URL', 'http://devserv01.holdingthedrones.com:3006');
}
$mainApiBaseUrl = MAIN_API_URL;
$organisatiesCreateApiUrl = $mainApiBaseUrl . '/organisaties';

// Pad naar de achtergrondafbeeldingen.
$backgroundImageUrl = '/app/assets/images/droneBackgroundImage.jpg';
$droneVisualElementUrl = '/app/assets/images/drone.png';

/**
 * Functie om een API-call te maken met cURL.
 * OPMERKING: Deze functie zou idealiter in functions.php staan.
 */
function callMainApi(string $url, string $method = 'GET', array $payload = []): array
{
    $ch = curl_init($url);
    $options = [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => ['Accept: application/json'],
        CURLOPT_TIMEOUT => 20,
    ];
    if ($method === 'POST' || $method === 'PUT' || $method === 'DELETE') {
        $options[CURLOPT_HTTPHEADER][] = 'Content-Type: application/json';
        $options[CURLOPT_POSTFIELDS] = json_encode($payload);
        if ($method === 'PUT') $options[CURLOPT_CUSTOMREQUEST] = 'PUT';
        elseif ($method === 'DELETE') $options[CURLOPT_CUSTOMREQUEST] = 'DELETE';
        elseif ($method === 'POST') $options[CURLOPT_POST] = true;
    }
    curl_setopt_array($ch, $options);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($response === false) {
        return ['error' => "cURL Fout: verbinding mislukt"];
    }
    if ($httpCode >= 400) {
        $decodedError = json_decode($response, true);
        $errorMsg = $decodedError['message'] ?? $response ?: "Onbekende API Fout ($httpCode)";
        return ['error' => $errorMsg];
    }
    return json_decode($response, true) ?: ['error' => "Ongeldige JSON response."];
}

// --- STAP 3: AJAX REQUEST AFHANDELING ---

// Dit blok code wordt alleen uitgevoerd als het formulier via JavaScript wordt verstuurd.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_SERVER['HTTP_X_REQUESTED_WITH'])) {
    // Lees de JSON-data die het JavaScript heeft verstuurd.
    $formData = json_decode(file_get_contents('php://input'), true);

    // Server-side validatie: controleer of alle verplichte velden zijn ingevuld.
    if (empty($formData['organisatienaam']) || empty($formData['kvkNummer']) || empty($formData['adres']) || empty($formData['postcode']) || empty($formData['plaats']) || empty($formData['land'])) {
        http_response_code(400); // Stuur een 'Bad Request' statuscode.
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'message' => 'Vul alstublieft alle verplichte velden in.']);
        exit;
    }

    // Bereid de data voor om naar de API te sturen.
    $payload = [
        'organisatienaam' => $formData['organisatienaam'],
        'kvkNummer' => $formData['kvkNummer'],
        'adres' => $formData['adres'],
        'postcode' => $formData['postcode'],
        'plaats' => $formData['plaats'],
        'land' => $formData['land'],
        'isActive' => isset($formData['isActive']) ? 1 : 0,
        '_entry_ID' => (int)$_SESSION['user']['id'], // Koppel de ingelogde gebruiker.
        'logoBase64' => null // Logo-upload wordt hier nog niet ondersteund.
    ];

    // Roep de API aan om de nieuwe organisatie op te slaan.
    $response = callMainApi($organisatiesCreateApiUrl, 'POST', $payload);

    // Stuur een reactie terug naar het JavaScript.
    if (isset($response['error'])) {
        http_response_code(500); // Stuur een 'Internal Server Error' statuscode.
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'message' => 'Fout bij registreren: ' . $response['error']]);
    } else {
        http_response_code(201); // Stuur een 'Created' statuscode.
        header('Content-Type: application/json');
        echo json_encode(['status' => 'success', 'message' => 'Organisatie succesvol geregistreerd!']);
    }
    exit; // Stop het script na het afhandelen van de AJAX-request.
}

// --- STAP 4: DATA VOORBEREIDEN VOOR DE VIEW ---

// Maak een lijst met landen voor de dropdown in het formulier.
$countries = ['Nederland', 'België', 'Duitsland', 'Frankrijk', 'Spanje', 'Italië', 'Verenigd Koninkrijk'];
?>
<!DOCTYPE html>
<html lang="nl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Organisatie Registratie - Drone Flight Planner</title>

    <!-- Externe stylesheets -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Link naar het aparte CSS-bestand voor deze pagina -->
    <link rel="stylesheet" href="/app/assets/styles/organisatieRegistratie-styling.css">
</head>

<body>
    <img src="<?= htmlspecialchars($droneVisualElementUrl) ?>" alt="Drone Visual Element" class="drone-visual-element">

    <div class="container-wrapper">
        <div class="text-center mb-8 pt-4">
            <h1 class="text-3xl md:text-4xl font-extrabold text-gray-900 mb-3">Registreer Uw Organisatie</h1>
            <p class="text-md text-gray-600">Voer alle benodigde gegevens in en upload uw logo.</p>
        </div>

        <form id="organizationRegisterForm" class="w-full space-y-5">
            <!-- Upload Organisatie Logo -->
            <div>
                <label for="organizationLogoUpload" class="form-label">Organisatie Logo</label>
                <div class="logo-upload-container" onclick="document.getElementById('organizationLogoUpload').click()">
                    <p class="text-gray-700 font-medium">Klik om te uploaden of sleep een bestand hier</p>
                    <p class="text-gray-500 text-sm">(JPG, PNG, GIF - Max 2MB)</p>
                    <input type="file" name="organizationLogo" id="organizationLogoUpload" class="file-input" accept="image/png, image/jpeg, image/gif">
                    <img id="logoPreview" src="#" alt="Logo Preview" class="logo-preview hidden">
                </div>
            </div>

            <!-- Naam & KVK -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <div>
                    <label for="organisatienaam" class="form-label">Organisatienaam <span class="required-asterisk">*</span></label>
                    <input type="text" name="organisatienaam" id="organisatienaam" required class="form-control" placeholder="B.V. SkyView Drones">
                </div>
                <div>
                    <label for="kvkNummer" class="form-label">KVK Nummer <span class="required-asterisk">*</span></label>
                    <input type="text" name="kvkNummer" id="kvkNummer" required class="form-control" placeholder="12345678">
                </div>
            </div>

            <!-- Adresdetails -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <div>
                    <label for="adres" class="form-label">Adres <span class="required-asterisk">*</span></label>
                    <input type="text" name="adres" id="adres" required class="form-control" placeholder="Luchtweg 12">
                </div>
                <div>
                    <label for="postcode" class="form-label">Postcode <span class="required-asterisk">*</span></label>
                    <input type="text" name="postcode" id="postcode" required class="form-control" placeholder="1234AB">
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <div>
                    <label for="plaats" class="form-label">Plaats <span class="required-asterisk">*</span></label>
                    <input type="text" name="plaats" id="plaats" required class="form-control" placeholder="Amsterdam">
                </div>
                <div>
                    <label for="land" class="form-label">Land <span class="required-asterisk">*</span></label>
                    <select name="land" id="land" required class="form-select">
                        <option value="">Selecteer een land</option>
                        <?php foreach ($countries as $country): ?>
                            <option value="<?= htmlspecialchars($country) ?>"><?= htmlspecialchars($country) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <!-- Actie knoppen -->
            <div class="flex justify-end space-x-4 mt-6">
                <button type="button" onclick="window.location.href='landing-page.php'" class="btn-action btn-secondary">
                    Annuleren
                </button>
                <button type="submit" class="btn-action btn-success">
                    Organisatie Registreren
                </button>
            </div>
        </form>

        <!-- Div om succes- of foutmeldingen te tonen -->
        <div id="responseMessage" class="hidden"></div>
    </div>

    <script>
        // JavaScript voor de interactie op de pagina.

        // Functie om een preview van het geüploade logo te tonen.
        document.getElementById('organizationLogoUpload').addEventListener('change', function(event) {
            const file = event.target.files[0];
            const logoPreview = document.getElementById('logoPreview');
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    logoPreview.src = e.target.result;
                    logoPreview.classList.remove('hidden');
                };
                reader.readAsDataURL(file);
            }
        });

        // Functie die wordt uitgevoerd als het formulier wordt verstuurd.
        document.getElementById('organizationRegisterForm').addEventListener('submit', async function(event) {
            event.preventDefault(); // Voorkom dat de pagina herlaadt.

            const form = event.target;
            const formData = new FormData(form);
            const data = {};
            for (const [key, value] of formData.entries()) {
                data[key] = value;
            }

            const responseMessageDiv = document.getElementById('responseMessage');
            responseMessageDiv.textContent = 'Bezig met registreren...';
            responseMessageDiv.className = 'response-message info'; // Gebruik CSS klassen voor styling.

            try {
                // Stuur de formulierdata naar de server (dezelfde PHP-pagina).
                const response = await fetch(form.action, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify(data)
                });

                const result = await response.json();

                // Toon de reactie van de server aan de gebruiker.
                if (response.ok && result.status === 'success') {
                    responseMessageDiv.className = 'response-message success';
                    responseMessageDiv.textContent = result.message || 'Organisatie succesvol geregistreerd.';
                    form.reset(); // Maak het formulier leeg.
                    setTimeout(() => {
                        window.location.href = 'landing-page.php'; // Stuur na 2 seconden terug.
                    }, 2000);
                } else {
                    responseMessageDiv.className = 'response-message error';
                    responseMessageDiv.textContent = result.message || 'Er ging iets mis.';
                }
            } catch (error) {
                responseMessageDiv.className = 'response-message error';
                responseMessageDiv.textContent = 'Netwerkfout, probeer het opnieuw.';
            }
        });
    </script>
</body>

</html>