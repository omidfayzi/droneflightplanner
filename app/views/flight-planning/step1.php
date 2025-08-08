<?php
// /var/www/public/app/views/flight-planning/step1.php
// Fixed version met correcte variabele definitie

session_start();

// API URLs
if (!defined('SORA_API_URL')) {
    define('SORA_API_URL', 'https://api.dronesora.holdingthedrones.com/');
}
if (!defined('MAIN_API_URL')) {
    define('MAIN_API_URL', 'https://api2.droneflightplanner.nl/');
}

$soraApiBaseUrl = SORA_API_URL;
$mainApiBaseUrl = MAIN_API_URL;

// Dummy user voor testen - VERPLAATS NAAR BOVEN VOOR HTML GEBRUIK
if (!isset($_SESSION['user']['id'])) {
    $_SESSION['user']['id'] = 999;
    $_SESSION['user']['first_name'] = 'Developer';
}
$currentUserId = $_SESSION['user']['id'];

// ✅ FIX: Definieer $userName VOOR de HTML output
$userName = $_SESSION['user']['first_name'] ?? 'Onbekende Gebruiker';

// API call functie
function callExternalApi(string $url, array $payload = [], string $method = 'POST'): array
{
    $ch = curl_init($url);
    $options = [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'Accept: application/json'],
        CURLOPT_TIMEOUT => 30,
        CURLOPT_VERBOSE => true,
        CURLOPT_STDERR => fopen('php://stderr', 'w')
    ];

    if ($method === 'POST' || $method === 'PUT') {
        $options[CURLOPT_POSTFIELDS] = json_encode($payload);
    }
    if ($method === 'PUT') {
        $options[CURLOPT_CUSTOMREQUEST] = 'PUT';
    } elseif ($method === 'POST') {
        $options[CURLOPT_POST] = true;
    }

    curl_setopt_array($ch, $options);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    error_log("API_CALL: URL: $url, Method: $method, HTTP: $httpCode, Payload: " . json_encode($payload) . ", Response: " . ($response ?: '(empty)'));

    if ($response === false) {
        return ['error' => "cURL error: " . curl_error($ch), 'http_code' => 0];
    }
    if ($httpCode >= 400) {
        $decodedError = json_decode($response, true);
        $errorMsg = $decodedError['error'] ?? $decodedError['message'] ?? $response ?: "API error (HTTP $httpCode)";
        return ['error' => "API Error ($httpCode): $errorMsg", 'http_code' => $httpCode];
    }

    $decoded = json_decode($response, true);
    return is_array($decoded) ? $decoded : ['success' => true, 'http_code' => $httpCode];
}

// Helper functie om SORA parameters te mappen
function mapToSoraParameters($formData, $soraVersion)
{
    $soraParams = [];

    if ($soraVersion === '2.0') {
        $soraParams['max_uas'] = floatval($formData['characteristic_dimension']);
        $soraParams['kinetic_energy'] = calculateKineticEnergy($formData['mtom'], $formData['flight_speed']);
        $soraParams['operational_scenario'] = mapOperationalScenario($formData['operational_scenario_id']);

        // Default mitigatie levels
        $soraParams['m1level'] = 'medium';
        $soraParams['m2level'] = 'medium';
        $soraParams['m3level'] = 'medium';

        // Default ARC vragen
        $soraParams['q1'] = 'no';
        $soraParams['q2'] = 'no';
        $soraParams['q3'] = 'no';
        $soraParams['q4'] = 'no';
        $soraParams['q5'] = 'no';
        $soraParams['q6'] = 'no';
        $soraParams['q7'] = 'no';
        $soraParams['q8'] = 'no';
    } else {
        // SORA 2.5 parameters
        $soraParams['max_uas'] = floatval($formData['characteristic_dimension']);
        $soraParams['speed'] = floatval($formData['flight_speed']);
        $soraParams['igrc'] = mapPopulationDensity($formData['population_density_id']);

        // Default mitigatie levels
        $soraParams['m1alevel'] = 'medium';
        $soraParams['m1blevel'] = 'medium';
        $soraParams['m2level'] = 'medium';

        // Default ARC vragen
        $soraParams['q1'] = 'no';
        $soraParams['q2'] = 'no';
        $soraParams['q3'] = 'no';
        $soraParams['q4'] = 'no';
        $soraParams['q5'] = 'no';
        $soraParams['q6'] = 'no';
        $soraParams['q7'] = 'no';
        $soraParams['q8'] = 'no';
    }

    return $soraParams;
}

function calculateKineticEnergy($mass, $velocity)
{
    return 0.5 * floatval($mass) * pow(floatval($velocity), 2);
}

function mapOperationalScenario($scenario)
{
    $mapping = [
        'Visual line of sight (VLOS)' => 'VLOS',
        'Extended visual line of sight (EVLOS)' => 'EVLOS',
        'Beyond visual line of sight (BVLOS)' => 'BVLOS'
    ];
    return $mapping[$scenario] ?? 'VLOS';
}

function mapPopulationDensity($density)
{
    $mapping = [
        'controlled ground area' => '<25',
        '<25' => '<25',
        '<250' => '<250',
        '<2500' => '<2500',
        '<25000' => '<25000',
        '<250000' => '<250000',
        '>250000' => '>250000'
    ];
    return $mapping[$density] ?? '<25';
}

// Verwerk formulier submit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $flightName = $_POST['flight_name'] ?? 'Nieuwe Vlucht';
    $selectedSoraVersion = $_POST['sora_version'] ?? '2.5';

    try {
        // STAP 1: Maak SORA analysis aan
        $analysisPayload = ['analysis_id' => ($selectedSoraVersion === '2.0' ? 1 : 2)];
        $createAnalysisResult = callExternalApi($soraApiBaseUrl . 'create_analysis_id', $analysisPayload, 'POST');

        if (isset($createAnalysisResult['error'])) {
            throw new Exception("SORA Analysis creatie mislukt: " . $createAnalysisResult['error']);
        }

        $soraAnalysisId = $createAnalysisResult['insertedId'] ?? null;
        if (!$soraAnalysisId) {
            throw new Exception("Geen analysis ID ontvangen van SORA API");
        }

        error_log("SORA Analysis created with ID: " . $soraAnalysisId);

        // STAP 2: Test initial SORA calculation
        $soraParams = mapToSoraParameters($_POST, $selectedSoraVersion);
        $vluchtVoorbereidingEndpoint = $selectedSoraVersion === '2.0' ? 'vluchtvoorbereiding/2_0' : 'vluchtvoorbereiding/2_5';

        $testCalculation = callExternalApi($soraApiBaseUrl . $vluchtVoorbereidingEndpoint, $soraParams, 'POST');

        if (isset($testCalculation['error'])) {
            error_log("WARNING: Initial SORA calculation failed: " . $testCalculation['error']);
        } else {
            error_log("SUCCESS: Initial SORA calculation completed");
        }

        // STAP 3: Zet sessie variabelen
        $_SESSION['sora_analysis_id'] = $soraAnalysisId;
        $_SESSION['sora_version'] = $selectedSoraVersion;
        $_SESSION['flight_name'] = $flightName;
        $_SESSION['flight_data'] = $_POST;
        $_SESSION['initial_sora_params'] = $soraParams;

        // Redirect naar step2
        header("Location: step2.php");
        exit;
    } catch (Exception $e) {
        $errorMessage = "Fout bij aanmaken vlucht: " . $e->getMessage();
        $_SESSION['form_error'] = $errorMessage;
        error_log("FLIGHT_CREATION_ERROR: " . $errorMessage);
        header("Location: step1.php");
        exit;
    }
}

// ✅ FIX: Haal foutmelding op en definieer voor HTML
$formError = $_SESSION['form_error'] ?? '';
unset($_SESSION['form_error']);
?>

<!DOCTYPE html>
<html lang="nl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Flight Planning - Step 1 | DroneFlightPlanner</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: "#2D69E7",
                        primaryDark: "#1F2937",
                        secondary: "#101826",
                        lightBg: "#F7F9F8",
                        cardBg: "#FEFEFE",
                        accent: "#FFF8C3",
                        success: "#DCFDE6",
                        borderColor: "#EAEAE6",
                        subtleBg: "#F2F5F6"
                    }
                }
            }
        }
    </script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');

        body {
            font-family: 'Inter', sans-serif;
            background-color: #F2F5F6;
            color: #101826;
        }

        .card-shadow {
            box-shadow: 0 4px 20px rgba(16, 24, 38, 0.08);
        }

        .input-focus:focus {
            border-color: #2D69E7;
            box-shadow: 0 0 0 3px rgba(45, 105, 231, 0.15);
        }

        .progress-step {
            transition: all 0.3s ease;
        }

        .progress-step.active {
            color: black;
        }


        .parameter-card {
            transition: all 0.2s ease;
            border: 1px solid #EAEAE6;
        }

        .parameter-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(16, 24, 38, 0.08);
            border-color: #2D69E7;
        }

        .info-icon {
            cursor: pointer;
            color: #2D69E7;
        }

        .tooltip {
            visibility: hidden;
            position: absolute;
            z-index: 1;
            width: 220px;
            background: #1F2937;
            color: #F7F9F8;
            text-align: center;
            padding: 10px;
            border-radius: 6px;
            font-size: 13px;
            bottom: 125%;
            left: 50%;
            transform: translateX(-50%);
            opacity: 0;
            transition: opacity 0.3s;
        }

        .tooltip::after {
            content: "";
            position: absolute;
            top: 100%;
            left: 50%;
            margin-left: -5px;
            border-width: 5px;
            border-style: solid;
            border-color: #1F2937 transparent transparent transparent;
        }

        .info-icon:hover .tooltip {
            visibility: visible;
            opacity: 1;
        }
    </style>
</head>

<body class="min-h-screen flex flex-col bg-lightBg">
    <!-- Header -->
    <header class="bg-white shadow-sm">
        <div class="container mx-auto px-4 py-4 flex justify-between items-center">
            <div class="flex items-center">
                <P class="text-l">
                    <span class="font-bold">HTD</span> DroneFlightPlanner
                </P>
            </div>
            <div class="flex items-center space-x-4">
                <div class="flex items-center bg-subtleBg py-1 px-3 rounded-full">
                    <div class="w-8 h-8 bg-primary rounded-full flex items-center justify-center text-white font-bold mr-2">U</div>
                    <span class="font-medium text-sm"><?= htmlspecialchars($userName) ?></span>
                </div>
            </div>
        </div>
    </header>

    <main class="flex-grow container mx-auto px-4 py-8">
        <div class="max-w-6xl mx-auto">
            <div class="mb-10 text-center">
                <h1 class="text-3xl font-bold text-secondary mb-2">Nieuwe Vluchtplanning</h1>
                <p class="text-gray-600">Stap 1: Configureer basisgegevens en risicoparameters voor uw vlucht</p>
            </div>

            <!-- Progress Bar -->
            <div class="mb-10">
                <div class="flex justify-between relative">
                    <div class="absolute h-1 bg-gray-200 top-1/2 left-0 right-0 -translate-y-1/2 -z-10"></div>

                    <div class="progress-step active flex flex-col items-center">
                        <div class="w-12 h-12 rounded-full bg-primary flex items-center justify-center text-white font-bold mb-2">1</div>
                        <span class="text-sm font-medium">Basisgegevens</span>
                    </div>

                    <div class="progress-step flex flex-col items-center">
                        <div class="w-12 h-12 rounded-full bg-gray-200 flex items-center justify-center text-gray-500 font-bold mb-2">2</div>
                        <span class="text-sm font-medium">SORA Analyse</span>
                    </div>

                    <div class="progress-step flex flex-col items-center">
                        <div class="w-12 h-12 rounded-full bg-gray-200 flex items-center justify-center text-gray-500 font-bold mb-2">3</div>
                        <span class="text-sm font-medium">Mitigaties</span>
                    </div>

                    <div class="progress-step flex flex-col items-center">
                        <div class="w-12 h-12 rounded-full bg-gray-200 flex items-center justify-center text-gray-500 font-bold mb-2">4</div>
                        <span class="text-sm font-medium">Beoordeling</span>
                    </div>
                </div>
            </div>

            <?php if (!empty($formError)): ?>
                <div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-lg text-red-700">
                    <i class="fas fa-exclamation-circle mr-2"></i>
                    <?= htmlspecialchars($formError) ?>
                </div>
            <?php endif; ?>

            <form action="step1.php" method="post" class="bg-white card-shadow rounded-xl overflow-hidden">
                <div class="p-6 border-b border-borderColor">
                    <h2 class="text-xl font-bold text-secondary">Basisgegevens</h2>
                    <p class="text-gray-600 text-sm">Algemene informatie over uw vlucht</p>
                </div>

                <div class="p-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="flight_name" class="block text-sm font-medium text-secondary mb-2">
                                Vluchtnaam
                                <span class="text-red-500">*</span>
                            </label>
                            <input type="text" name="flight_name" id="flight_name" required
                                class="w-full p-3 border border-borderColor rounded-lg input-focus"
                                placeholder="Bijv. Inspectie Windmolenpark A" value="TestVlucht_<?= time() ?>">
                        </div>

                        <div>
                            <label for="sora_version_select" class="block text-sm font-medium text-secondary mb-2">
                                SORA Versie
                                <span class="text-red-500">*</span>
                            </label>
                            <div class="relative">
                                <select name="sora_version" id="sora_version_select" required
                                    class="w-full p-3 border border-borderColor rounded-lg appearance-none input-focus bg-white">
                                    <option value="2.5" selected>SORA 2.5 (Aanbevolen)</option>
                                    <option value="2.0">SORA 2.0</option>
                                </select>
                                <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-gray-500">
                                    <i class="fas fa-chevron-down"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="p-6 bg-subtleBg border-t border-b border-borderColor">
                    <h2 class="text-xl font-bold text-secondary">Risicoparameters</h2>
                    <p class="text-gray-600 text-sm">Configureer de parameters voor uw SORA-risicoanalyse</p>
                </div>

                <div class="p-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Operational Scenario Card -->
                        <div class="parameter-card rounded-lg p-5 bg-cardBg">
                            <div class="flex justify-between items-start mb-3">
                                <label class="block text-sm font-medium text-secondary">
                                    Operationeel Scenario
                                    <span class="text-red-500">*</span>
                                </label>
                                <div class="info-icon relative">
                                    <i class="fas fa-info-circle"></i>
                                    <span class="tooltip">Het type vlucht: VLOS (zichtvlucht), EVLOS (uitgebreid zicht), of BVLOS (buiten zicht)</span>
                                </div>
                            </div>
                            <div class="relative">
                                <select name="operational_scenario_id" required
                                    class="w-full p-3 border border-borderColor rounded-lg appearance-none input-focus bg-white">
                                    <option value="">Selecteer scenario...</option>
                                    <option value="Visual line of sight (VLOS)">Visual line of sight (VLOS)</option>
                                    <option value="Extended visual line of sight (EVLOS)">Extended visual line of sight (EVLOS)</option>
                                    <option value="Beyond visual line of sight (BVLOS)">Beyond visual line of sight (BVLOS)</option>
                                </select>
                                <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-gray-500">
                                    <i class="fas fa-chevron-down"></i>
                                </div>
                            </div>
                        </div>

                        <!-- Population Density Card -->
                        <div class="parameter-card rounded-lg p-5 bg-cardBg">
                            <div class="flex justify-between items-start mb-3">
                                <label class="block text-sm font-medium text-secondary">
                                    Populatiedichtheid
                                    <span class="text-red-500">*</span>
                                </label>
                                <div class="info-icon relative">
                                    <i class="fas fa-info-circle"></i>
                                    <span class="tooltip">Aantal mensen per km² in het vluchtgebied</span>
                                </div>
                            </div>
                            <div class="relative">
                                <select name="population_density_id" required
                                    class="w-full p-3 border border-borderColor rounded-lg appearance-none input-focus bg-white">
                                    <option value="">Selecteer dichtheid...</option>
                                    <option value="controlled ground area">Controlled ground area</option>
                                    <option value="<25">&lt; 25 personen/km²</option>
                                    <option value="<250">&lt; 250 personen/km²</option>
                                    <option value="<2500">&lt; 2.500 personen/km²</option>
                                    <option value="<25000">&lt; 25.000 personen/km²</option>
                                    <option value="<250000">&lt; 250.000 personen/km²</option>
                                    <option value=">250000">&gt; 250.000 personen/km²</option>
                                </select>
                                <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-gray-500">
                                    <i class="fas fa-chevron-down"></i>
                                </div>
                            </div>
                        </div>

                        <!-- Drone Dimension Card -->
                        <div class="parameter-card rounded-lg p-5 bg-cardBg">
                            <div class="flex justify-between items-start mb-3">
                                <label class="block text-sm font-medium text-secondary">
                                    Maximale drone dimensie (m)
                                    <span class="text-red-500">*</span>
                                </label>
                                <div class="info-icon relative">
                                    <i class="fas fa-info-circle"></i>
                                    <span class="tooltip">De grootste afmeting van uw drone in meters</span>
                                </div>
                            </div>
                            <input type="number" step="0.1" name="characteristic_dimension" required value="1"
                                class="w-full p-3 border border-borderColor rounded-lg input-focus"
                                placeholder="Bijv. 1.5">
                            <p class="text-xs text-gray-500 mt-2">Voor SORA GRC berekening</p>
                        </div>

                        <!-- Weight Card -->
                        <div class="parameter-card rounded-lg p-5 bg-cardBg">
                            <div class="flex justify-between items-start mb-3">
                                <label class="block text-sm font-medium text-secondary">
                                    Maximaal gewicht (kg)
                                    <span class="text-red-500">*</span>
                                </label>
                                <div class="info-icon relative">
                                    <i class="fas fa-info-circle"></i>
                                    <span class="tooltip">Maximaal startgewicht inclusief payload</span>
                                </div>
                            </div>
                            <input type="number" step="0.1" name="mtom" required value="4"
                                class="w-full p-3 border border-borderColor rounded-lg input-focus"
                                placeholder="Bijv. 4.0">
                            <p class="text-xs text-gray-500 mt-2">Voor kinetische energie berekening</p>
                        </div>

                        <!-- Speed Card -->
                        <div class="parameter-card rounded-lg p-5 bg-cardBg">
                            <div class="flex justify-between items-start mb-3">
                                <label class="block text-sm font-medium text-secondary">
                                    Vliegsnelheid (m/s)
                                    <span class="text-red-500">*</span>
                                </label>
                                <div class="info-icon relative">
                                    <i class="fas fa-info-circle"></i>
                                    <span class="tooltip">Maximale operationele snelheid in meters per seconde</span>
                                </div>
                            </div>
                            <input type="number" step="0.1" name="flight_speed" required value="15"
                                class="w-full p-3 border border-borderColor rounded-lg input-focus"
                                placeholder="Bijv. 15.0">
                            <p class="text-xs text-gray-500 mt-2">Voor SORA 2.5 en kinetische energie</p>
                        </div>

                        <!-- Height Card -->
                        <div class="parameter-card rounded-lg p-5 bg-cardBg">
                            <div class="flex justify-between items-start mb-3">
                                <label class="block text-sm font-medium text-secondary">
                                    Vlieghoogte (m)
                                    <span class="text-red-500">*</span>
                                </label>
                                <div class="info-icon relative">
                                    <i class="fas fa-info-circle"></i>
                                    <span class="tooltip">Maximale vlieghoogte boven grondniveau</span>
                                </div>
                            </div>
                            <input type="number" name="max_flight_height" required value="80"
                                class="w-full p-3 border border-borderColor rounded-lg input-focus"
                                placeholder="Bijv. 80">
                            <p class="text-xs text-gray-500 mt-2">Voor luchtruim classificatie</p>
                        </div>
                    </div>
                </div>

                <div class="p-6 border-t border-borderColor bg-subtleBg">
                    <div class="flex flex-col md:flex-row justify-between items-center">
                        <div class="mb-4 md:mb-0">
                            <p class="text-sm text-gray-600">
                                <i class="fas fa-user-circle mr-1"></i>
                                Aangemaakt door: <span class="font-medium"><?= htmlspecialchars($userName) ?></span>
                            </p>
                        </div>
                        <div class="flex space-x-3">
                            <button type="button" onclick="window.location.href='dashboard.php'"
                                class="px-6 py-3 rounded-lg border border-gray-300 text-secondary hover:bg-gray-50 font-medium transition-colors">
                                <i class="fas fa-times mr-2"></i>Annuleren
                            </button>
                            <button type="submit"
                                class="px-6 py-3 rounded-lg bg-primary hover:bg-blue-700 text-white font-medium transition-colors flex items-center">
                                Volgende stap
                                <i class="fas fa-arrow-right ml-2"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </form>

            <div class="mt-8 text-center text-sm text-gray-600">
                <p>Stap 1 van 4</p>
            </div>
        </div>
    </main>

    <footer class="py-6 border-t border-borderColor">
        <div class="container mx-auto px-4 text-center">
            <p class="text-gray-600 text-sm">© 2025 DroneFlightPlanner. Alle rechten voorbehouden.</p>
        </div>
    </footer>

    <script>
        // Tooltip functionality
        document.querySelectorAll('.info-icon').forEach(icon => {
            icon.addEventListener('mouseenter', function() {
                const tooltip = this.querySelector('.tooltip');
                tooltip.style.visibility = 'visible';
                tooltip.style.opacity = '1';
            });

            icon.addEventListener('mouseleave', function() {
                const tooltip = this.querySelector('.tooltip');
                tooltip.style.visibility = 'hidden';
                tooltip.style.opacity = '0';
            });
        });

        // Form validation before submit
        document.querySelector('form').addEventListener('submit', function(e) {
            let valid = true;
            const requiredFields = this.querySelectorAll('[required]');

            requiredFields.forEach(field => {
                if (!field.value.trim()) {
                    valid = false;
                    field.classList.add('border-red-500');

                    // Add error message if not present
                    if (!field.nextElementSibling || !field.nextElementSibling.classList.contains('error-msg')) {
                        const errorMsg = document.createElement('p');
                        errorMsg.className = 'error-msg text-red-500 text-xs mt-1';
                        errorMsg.textContent = 'Dit veld is verplicht';
                        field.parentNode.appendChild(errorMsg);
                    }
                } else {
                    field.classList.remove('border-red-500');
                    const errorMsg = field.parentNode.querySelector('.error-msg');
                    if (errorMsg) errorMsg.remove();
                }
            });

            if (!valid) {
                e.preventDefault();
            }
        });
    </script>
</body>

</html>