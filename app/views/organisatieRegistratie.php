<?php
// /var/www/public/frontend/pages/organisatieRegistratie.php
// Pagina voor het registreren van een nieuwe organisatie (Visueel Verfijnd met zwevende drone)

session_start();

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../functions.php';

// Valideren van gebruikersstatus (moet ingelogd zijn om te kunnen registreren)
if (!isset($_SESSION['user']['id'])) {
    $_SESSION['form_error'] = "U moet ingelogd zijn om organisaties te registreren.";
    header("Location: landing-page.php");
    exit;
}

$loggedInUserId = $_SESSION['user']['id'];

// Haal MAIN_API_URL op uit configuratie
if (!defined('MAIN_API_URL')) {
    define('MAIN_API_URL', 'https://api2.droneflightplanner.nl');
}
$mainApiBaseUrl = MAIN_API_URL;

// Endpoint voor het toevoegen van organisaties
$organisatiesCreateApiUrl = $mainApiBaseUrl . '/organisaties';

// Afbeeldingspaden - Zorg dat deze URL's direct toegankelijk zijn via de webserver
$backgroundImageUrl = '/app/assets/images/droneBackgroundImage.jpg';
$droneVisualElementUrl = '/app/assets/images/drone.png'; // Dit is nu het visuele element, niet het logo


// --- GECENTRALISEERDE API HULPFUNCTIE voor MAIN_API_URL ---
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
        if ($method === 'PUT') {
            $options[CURLOPT_CUSTOMREQUEST] = 'PUT';
        } elseif ($method === 'DELETE') {
            $options[CURLOPT_CUSTOMREQUEST] = 'DELETE';
        } elseif ($method === 'POST') {
            $options[CURLOPT_POST] = true;
        }
    }
    if (isset($_SESSION['user']['auth_token'])) {
        $options[CURLOPT_HTTPHEADER][] = 'Authorization: Bearer ' . $_SESSION['user']['auth_token'];
    }

    curl_setopt_array($ch, $options);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    error_log("Org Reg API Call Log: URL: $url, Method: $method, HTTP: $httpCode, Response: " . ($response ?: '(empty)'));

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


// --- POST Verwerking van Formulier (via AJAX) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
    $inputJSON = file_get_contents('php://input');
    $formData = json_decode($inputJSON, true);

    if (empty($formData['organisatienaam']) || empty($formData['kvkNummer']) || empty($formData['adres']) || empty($formData['postcode']) || empty($formData['plaats']) || empty($formData['land'])) {
        http_response_code(400);
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'message' => 'Vul alstublieft alle verplichte velden in.']);
        exit;
    }

    $payload = [
        'organisatienaam' => $formData['organisatienaam'],
        'kvkNummer' => $formData['kvkNummer'],
        'adres' => $formData['adres'],
        'postcode' => $formData['postcode'],
        'plaats' => $formData['plaats'],
        'land' => $formData['land'],
        'isActive' => isset($formData['isActive']) ? 1 : 0,
        '_entry_ID' => $loggedInUserId,
    ];

    $response = callMainApi($organisatiesCreateApiUrl, 'POST', $payload);

    if (isset($response['error'])) {
        http_response_code(500);
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'message' => 'Fout bij registreren organisatie: ' . $response['error']]);
    } else {
        http_response_code(200);
        header('Content-Type: application/json');
        echo json_encode(['status' => 'success', 'message' => 'Organisatie succesvol geregistreerd!', 'insertedId' => $response['organisatieId'] ?? null]);
    }
    exit;
}

// --- Ophalen landen voor dropdown (GET) ---
$countries = [
    'Nederland',
    'België',
    'Duitsland',
    'Frankrijk',
    'Spanje',
    'Italië',
    'Verenigd Koninkrijk',
    'Verenigde Staten',
    'Australië',
    'Canada',
    'India',
    'China',
    'Japan',
    'Brazilië',
    'Zuid-Afrika',
    'Mexico',
    'Zwitserland'
];

// --- Start van de HTML voor een stand-alone pagina ---
?>
<!DOCTYPE html>
<html lang="nl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Organisatie Registratie - Drone Flight Planner</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body {
            background-color: #f5f7fa;
            font-family: 'Montserrat', sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
            padding: 20px;
            background-image: url('<?= htmlspecialchars($backgroundImageUrl) ?>');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            backdrop-filter: blur(5px) brightness(0.7);
        }

        .container-wrapper {
            background-color: rgba(255, 255, 255, 0.98);
            border-radius: 12px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            padding: 40px;
            width: 100%;
            max-width: 800px;
            display: flex;
            flex-direction: column;
            align-items: center;
            position: relative;
            /* De formulier-container zelf, als relatief gepositioneerde ouder van responseMessage */
            overflow: visible;
            /* Dit is BELANGRIJK! Anders wordt de drone-afbeelding binnen de grenzen geclipt */
            margin: 20px 0;
        }

        /* Het drone-visuele element (NU MET position: absolute TEN OPZICHTE VAN BODY!) */
        .drone-visual-element {
            width: 300px;
            /* Grotere drone afbeelding */
            height: auto;
            position: absolute;
            /* Zeer belangrijk: Absolute t.o.v. de dichtstbijzijnde *positioneerde* ouder.
                                   Als body geen position heeft, is het de initial containing block (viewport). */
            /* BEGIN DEMO POSITIONERING (VERPLAATS DEZE WAARDEN ZELF VOOR DE JUISTE PLAATSING) */
            top: 7%;
            left: 65%;
            /* EINDE DEMO POSITIONERING */
            z-index: 100;
            /* Zorgt dat het boven alles zweeft */
            filter: drop-shadow(0 10px 20px rgba(0, 0, 0, 0.3));
            /* Prominentere schaduw */
            pointer-events: none;
            /* Negeer muisklikken zodat je formulier niet blokkeert */
        }

        /* De .container-wrapper ZELF heeft geen overflow:hidden meer, dus de drone mag daarbuiten */

        /* Organisatie Logo Upload */
        .logo-upload-container {
            border: 2px dashed #a7bed3;
            border-radius: 8px;
            padding: 20px;
            text-align: center;
            background-color: #f7fafd;
            cursor: pointer;
            transition: background-color 0.2s ease, border-color 0.2s ease;
        }

        .logo-upload-container:hover {
            background-color: #eaf1f8;
            border-color: #7ab2e2;
        }

        .logo-preview {
            max-width: 120px;
            /* Grotere preview */
            max-height: 120px;
            border-radius: 8px;
            margin-top: 15px;
            object-fit: contain;
            border: 1px solid #ddd;
            /* Dunne rand om preview */
        }

        .file-input {
            display: none;
        }

        h1,
        h2 {
            color: #2c3e50;
            font-weight: 700;
        }

        .form-label {
            font-weight: 600;
            color: #34495e;
            margin-bottom: 8px;
        }

        .form-control,
        .form-select {
            border: 1px solid #dcdfe6;
            border-radius: 8px;
            padding: 12px 18px;
            font-size: 0.95rem;
            color: #34495e;
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
        }

        .form-control:focus,
        .form-select:focus {
            border-color: #4a90e2;
            box-shadow: 0 0 0 3px rgba(74, 144, 226, 0.25);
            outline: none;
        }

        .form-check-input {
            margin-top: 0.3rem;
        }

        .form-check-label {
            color: #4a5568;
            font-weight: normal;
        }

        .btn-action {
            padding: 12px 28px;
            font-size: 1rem;
            border-radius: 8px;
            transition: background-color 0.2s ease, border-color 0.2s ease, transform 0.1s ease;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }

        .btn-primary {
            background-color: #3b82f6;
            color: white;
            border-color: #3b82f6;
        }

        .btn-primary:hover {
            background-color: #2563eb;
            transform: translateY(-2px);
        }

        .btn-success {
            background-color: #28a745;
            color: white;
            border-color: #28a745;
        }

        .btn-success:hover {
            background-color: #218838;
            transform: translateY(-2px);
        }

        .btn-secondary {
            background-color: #f0f0f0;
            color: #34495e;
            border-color: #ccc;
        }

        .btn-secondary:hover {
            background-color: #e0e0e0;
            transform: translateY(-2px);
        }

        #responseMessage {
            position: absolute;
            bottom: 20px;
            left: 20px;
            right: 20px;
            padding: 15px;
            border-radius: 8px;
            text-align: center;
            font-weight: 600;
            z-index: 10;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .bg-green-100 {
            background-color: #d1fae5;
        }

        .text-green-700 {
            color: #065f46;
        }

        .bg-red-100 {
            background-color: #fee2e2;
        }

        .text-red-700 {
            color: #991b1b;
        }

        .bg-gray-100 {
            background-color: #e2e8f0;
        }

        .text-gray-700 {
            color: #4a5568;
        }

        .required-asterisk {
            color: #ef4444;
            margin-left: 4px;
        }
    </style>
</head>

<body>
    <!-- Het drone-visuele element (deze staat NIET meer IN de container-wrapper) -->
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
                    <i class="fa-solid fa-cloud-arrow-up text-blue-500 text-4xl mb-2"></i>
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
                    <input type="text" name="organisatienaam" id="organisatienaam" required
                        class="form-control"
                        placeholder="B.V. SkyView Drones">
                </div>
                <div>
                    <label for="kvkNummer" class="form-label">KVK Nummer <span class="required-asterisk">*</span></label>
                    <input type="text" name="kvkNummer" id="kvkNummer" required
                        class="form-control"
                        placeholder="12345678">
                </div>
            </div>

            <!-- Adresdetails -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <div>
                    <label for="adres" class="form-label">Adres <span class="required-asterisk">*</span></label>
                    <input type="text" name="adres" id="adres" required
                        class="form-control"
                        placeholder="Luchtweg 12">
                </div>
                <div>
                    <label for="postcode" class="form-label">Postcode <span class="required-asterisk">*</span></label>
                    <input type="text" name="postcode" id="postcode" required
                        class="form-control"
                        placeholder="1234AB">
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <div>
                    <label for="plaats" class="form-label">Plaats <span class="required-asterisk">*</span></label>
                    <input type="text" name="plaats" id="plaats" required
                        class="form-control"
                        placeholder="Amsterdam">
                </div>
                <div>
                    <label for="land" class="form-label">Land <span class="required-asterisk">*</span></label>
                    <select name="land" id="land" required
                        class="form-select">
                        <option value="">Selecteer een land</option>
                        <?php foreach ($countries as $country): ?>
                            <option value="<?= htmlspecialchars($country) ?>"><?= htmlspecialchars($country) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <!-- isActive (checkbox) - Conform DFPP_Organisaties schema -->
            <div>
                <div class="form-check">
                    <input type="checkbox" name="isActive" id="isActive" class="form-check-input" value="1" checked>
                    <label class="form-check-label" for="isActive">Organisatie Actief (standaard is actief)</label>
                </div>
            </div>

            <!-- Actie knoppen -->
            <div class="flex justify-end space-x-4 mt-6">
                <button type="button" onclick="window.location.href='landing-page.php'"
                    class="btn-action btn-secondary">
                    Annuleren
                </button>
                <button type="submit"
                    class="btn-action btn-success">
                    Organisatie Registreren
                </button>
            </div>
        </form>

        <div id="responseMessage" class="hidden"></div>
    </div>

    <script>
        // Preview van het geüploade logo
        document.getElementById('organizationLogoUpload').addEventListener('change', function(event) {
            const file = event.target.files[0];
            const logoPreview = document.getElementById('logoPreview');
            const uploadIcon = document.querySelector('.logo-upload-container .fa-cloud-arrow-up');
            const uploadText = document.querySelector('.logo-upload-container p:nth-of-type(1)');
            const uploadHint = document.querySelector('.logo-upload-container p:nth-of-type(2)');

            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    logoPreview.src = e.target.result;
                    logoPreview.classList.remove('hidden');
                    if (uploadIcon) uploadIcon.classList.add('hidden');
                    if (uploadText) uploadText.classList.add('hidden');
                    if (uploadHint) uploadHint.classList.add('hidden');
                };
                reader.readAsDataURL(file);
            } else {
                logoPreview.src = '#';
                logoPreview.classList.add('hidden');
                if (uploadIcon) uploadIcon.classList.remove('hidden');
                if (uploadText) uploadText.classList.remove('hidden');
                if (uploadHint) uploadHint.classList.remove('hidden');
            }
        });

        document.getElementById('organizationRegisterForm').addEventListener('submit', async function(event) {
            event.preventDefault(); // Voorkom standaard formulier submit

            const form = event.target;
            const formData = new FormData(form);
            const data = {};
            for (const [key, value] of formData.entries()) {
                data[key] = value;
            }

            data['isActive'] = document.getElementById('isActive').checked ? 1 : 0;

            const responseMessageDiv = document.getElementById('responseMessage');
            responseMessageDiv.classList.remove('hidden', 'bg-red-100', 'text-red-700', 'bg-green-100', 'text-green-700', 'bg-gray-100', 'text-gray-700');
            responseMessageDiv.textContent = 'Bezig met registreren...';
            responseMessageDiv.classList.add('bg-gray-100', 'text-gray-700');
            responseMessageDiv.classList.remove('hidden');

            try {
                const response = await fetch(form.action, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify(data)
                });

                const result = await response.json();

                if (response.ok) {
                    responseMessageDiv.classList.add('bg-green-100', 'text-green-700');
                    responseMessageDiv.textContent = result.message || 'Organisatie succesvol geregistreerd.';
                    form.reset(); // Reset tekstvelden
                    document.getElementById('isActive').checked = true; // Actief veld blijft standaard aan

                    // Reset logo preview
                    document.getElementById('logoPreview').src = '#';
                    document.getElementById('logoPreview').classList.add('hidden');
                    document.querySelector('.logo-upload-container .fa-cloud-arrow-up').classList.remove('hidden');
                    document.querySelector('.logo-upload-container p:nth-of-type(1)').classList.remove('hidden');
                    document.querySelector('.logo-upload-container p:nth-of-type(2)').classList.remove('hidden');

                    setTimeout(() => {
                        window.location.href = 'landing-page.php';
                    }, 2000);
                } else {
                    responseMessageDiv.classList.add('bg-red-100', 'text-red-700');
                    responseMessageDiv.textContent = result.message || 'Er ging iets mis tijdens de registratie.';
                }
            } catch (error) {
                console.error('Fout bij AJAX registratie:', error);
                responseMessageDiv.classList.add('bg-red-100', 'text-red-700');
                responseMessageDiv.textContent = 'Netwerkfout of onverwachte response: ' + error.message;
            } finally {
                responseMessageDiv.classList.remove('bg-gray-100', 'text-gray-700');
            }
        });
    </script>
</body>

</html>