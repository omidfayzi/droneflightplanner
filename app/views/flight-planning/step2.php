<?php
// /var/www/public/frontend/pages/flight-planning/step2.php
// Vluchtplanning Stap 2 - SORA Vragenlijst & Calculatie met verbeterde styling

session_start();
require_once __DIR__ . '/../../../config/config.php';

// Zorg dat SORA_API_URL gedefinieerd is
if (!defined('SORA_API_URL')) {
    define('SORA_API_URL', 'https://api.dronesora.holdingthedrones.com/');
}
$soraApiBaseUrl = SORA_API_URL;

// Haal sessievariabelen op
$soraAnalysisId = $_SESSION['sora_analysis_id'] ?? null;
$soraVersion = $_SESSION['sora_version'] ?? null;
$userId = $_SESSION['user']['id'] ?? null;
$flightData = $_SESSION['flight_data'] ?? [];
$initialSoraParams = $_SESSION['initial_sora_params'] ?? [];

// Definieer userName voor template
$userName = $_SESSION['user']['first_name'] ?? 'Onbekende Gebruiker';

// Check essentiële variabelen
if (!$soraAnalysisId || !$soraVersion || !$userId) {
    $_SESSION['form_error'] = "Sessiegegevens ontbreken. Start een nieuwe vlucht.";
    header("Location: step1.php");
    exit;
}

// API helper functie
function callExternalApi(string $url, array $payload = [], string $method = 'GET'): array
{
    $ch = curl_init($url);
    $options = [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => ['Accept: application/json'],
        CURLOPT_TIMEOUT => 30,
        CURLOPT_VERBOSE => true,
        CURLOPT_STDERR => fopen('php://stderr', 'w')
    ];

    if ($method === 'POST' || $method === 'PUT') {
        $options[CURLOPT_HTTPHEADER][] = 'Content-Type: application/json';
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

    error_log("API_CALL: $url ($method) - HTTP: $httpCode - Response: " . ($response ?: '(empty)'));

    if ($response === false) {
        return ['error' => "Connection failed: " . curl_error($ch)];
    }
    if ($httpCode >= 400) {
        $decoded = json_decode($response, true);
        $errorMsg = $decoded['error'] ?? $decoded['message'] ?? $response ?: "Unknown API error";
        return ['error' => "API Error ($httpCode): $errorMsg"];
    }

    $decoded = json_decode($response, true);
    return is_array($decoded) ? $decoded : ['success' => true];
}

// Helper functie voor SORA parameter mapping
function buildSoraCalculationPayload($formAnswers, $initialParams, $soraVersion)
{
    $payload = $initialParams; // Start met basis parameters uit step1

    // Update parameters op basis van form answers
    foreach ($formAnswers as $answer) {
        $questionId = $answer['question_id'];
        $content = $answer['content'];

        // Map specific question IDs naar SORA parameters
        switch ($questionId) {
            // Mitigatie levels
            case 29:
            case 89:
                $payload[$soraVersion === '2.0' ? 'm1level' : 'm1alevel'] = $content;
                break;
            case 32:
            case 92:
                $payload['m2level'] = $content;
                break;
            case 35:
                if ($soraVersion === '2.0') $payload['m3level'] = $content;
                break;
            case 95:
                if ($soraVersion === '2.5') $payload['m1blevel'] = $content;
                break;

            // ARC vragen (8 vragen voor luchtruim classificatie)
            case 36:
            case 98:
                $payload['q1'] = $content;
                break;
            case 37:
            case 99:
                $payload['q2'] = $content;
                break;
            case 38:
            case 100:
                $payload['q3'] = $content;
                break;
            case 39:
            case 101:
                $payload['q4'] = $content;
                break;
            case 40:
            case 102:
                $payload['q5'] = $content;
                break;
            case 55:
            case 103:
                $payload['q6'] = $content;
                break;
            case 56:
            case 104:
                $payload['q7'] = $content;
                break;
            case 57:
            case 105:
                $payload['q8'] = $content;
                break;
        }
    }

    return $payload;
}

// AJAX POST handler voor opslaan en berekenen
if (
    $_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
    strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest'
) {

    $inputJSON = file_get_contents('php://input');
    $requestData = json_decode($inputJSON, true);
    $answersFromFrontend = $requestData['answers'] ?? [];

    try {
        // Bouw SORA calculation payload
        $soraPayload = buildSoraCalculationPayload($answersFromFrontend, $initialSoraParams, $soraVersion);

        // Voer SORA berekening uit via vluchtvoorbereiding endpoint
        $vluchtEndpoint = ($soraVersion === '2.0') ? 'vluchtvoorbereiding/2_0' : 'vluchtvoorbereiding/2_5';
        $calculationResult = callExternalApi($soraApiBaseUrl . $vluchtEndpoint, $soraPayload, 'POST');

        if (isset($calculationResult['error'])) {
            throw new Exception($calculationResult['error']);
        }

        // Sla resultaat op in sessie voor gebruik in volgende stappen
        $_SESSION['sora_calculation_result'] = $calculationResult;
        $_SESSION['sora_answers'] = $answersFromFrontend;

        // Return success response
        http_response_code(200);
        header('Content-Type: application/json');
        echo json_encode([
            'status' => 'success',
            'calculation' => $calculationResult,
            'payload_used' => $soraPayload
        ]);
        exit;
    } catch (Exception $e) {
        http_response_code(500);
        header('Content-Type: application/json');
        echo json_encode([
            'status' => 'error',
            'message' => 'SORA berekening mislukt',
            'details' => $e->getMessage()
        ]);
        exit;
    }
}

// GET request - Laad pagina met vragen
$questions = [];
$currentAnswers = [];

// Voor deze demo gebruiken we een vereenvoudigde vragenset
$demoQuestions = [
    // Mitigatie vragen
    [
        'id' => ($soraVersion === '2.0' ? 29 : 89),
        'content' => 'M1 Strategic mitigatie level',
        'type' => 'mc',
        'options' => ['low', 'medium', 'high'],
        'section' => 'Mitigatie Maatregelen',
        'info' => 'Strategische mitigatie voor operationele risicos'
    ],
    [
        'id' => ($soraVersion === '2.0' ? 32 : 92),
        'content' => 'M2 Human Error mitigatie level',
        'type' => 'mc',
        'options' => ['low', 'medium', 'high'],
        'section' => 'Mitigatie Maatregelen',
        'info' => 'Mitigatie voor menselijke fouten'
    ],
    [
        'id' => ($soraVersion === '2.0' ? 35 : 95),
        'content' => ($soraVersion === '2.0' ? 'M3 Technical mitigatie level' : 'M1B Tactical mitigatie level'),
        'type' => 'mc',
        'options' => ['low', 'medium', 'high'],
        'section' => 'Mitigatie Maatregelen',
        'info' => $soraVersion === '2.0' ? 'Technische risico mitigatie' : 'Tactische mitigatie maatregelen'
    ],

    // ARC vragen (Airspace Risk Class)
    [
        'id' => ($soraVersion === '2.0' ? 36 : 98),
        'content' => 'Operations in atypical airspace?',
        'type' => 'y/n',
        'section' => 'Luchtruim Risico',
        'info' => 'Vlucht in niet-standaard luchtruim'
    ],
    [
        'id' => ($soraVersion === '2.0' ? 37 : 99),
        'content' => 'Operations in airspace above 600ft AGL?',
        'type' => 'y/n',
        'section' => 'Luchtruim Risico',
        'info' => 'Vlucht boven 600 voet hoogte'
    ],
    [
        'id' => ($soraVersion === '2.0' ? 38 : 100),
        'content' => 'Operations in Airport/Heliport Environment in Class B, C or D Airspace?',
        'type' => 'y/n',
        'section' => 'Luchtruim Risico',
        'info' => 'Vlucht nabij gecontroleerde luchthavens'
    ],
    [
        'id' => ($soraVersion === '2.0' ? 39 : 101),
        'content' => 'Operations in Airport/Heliport Environment in Class E, F or G Airspace?',
        'type' => 'y/n',
        'section' => 'Luchtruim Risico',
        'info' => 'Vlucht nabij ongecontroleerde luchthavens'
    ],
    [
        'id' => ($soraVersion === '2.0' ? 40 : 102),
        'content' => 'Operations in airspace between 500ft and 600ft AGL?',
        'type' => 'y/n',
        'section' => 'Luchtruim Risico',
        'info' => 'Vlucht in kritieke hoogtezone'
    ],
    [
        'id' => ($soraVersion === '2.0' ? 55 : 103),
        'content' => 'Operations within Mode S-Veil/TMZ?',
        'type' => 'y/n',
        'section' => 'Luchtruim Risico',
        'info' => 'Vlucht in transponder verplicht gebied'
    ],
    [
        'id' => ($soraVersion === '2.0' ? 56 : 104),
        'content' => 'Operations within Controlled Airspace?',
        'type' => 'y/n',
        'section' => 'Luchtruim Risico',
        'info' => 'Vlucht in gecontroleerd luchtruim'
    ],
    [
        'id' => ($soraVersion === '2.0' ? 57 : 105),
        'content' => 'Operations within Uncontrolled Airspace over urban area?',
        'type' => 'y/n',
        'section' => 'Luchtruim Risico',
        'info' => 'Vlucht boven stedelijk gebied'
    ]
];

// Group questions by section
$questionsBySection = [];
foreach ($demoQuestions as $question) {
    $section = $question['section'];
    if (!isset($questionsBySection[$section])) {
        $questionsBySection[$section] = [];
    }
    $questionsBySection[$section][] = $question;
}
?>

<!DOCTYPE html>
<html lang="nl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Flight Planning - Step 2 | DroneFlightPlanner</title>
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

        .progress-step.completed {
            color: black;
        }

        .question-card {
            transition: all 0.2s ease;
            border: 1px solid #EAEAE6;
        }

        .question-card:hover {
            border-color: #2D69E7;
            box-shadow: 0 4px 12px rgba(45, 105, 231, 0.1);
        }

        .info-icon {
            cursor: pointer;
            color: #2D69E7;
        }

        .tooltip {
            visibility: hidden;
            position: absolute;
            z-index: 1;
            width: 280px;
            background: #1F2937;
            color: #F7F9F8;
            text-align: center;
            padding: 12px;
            border-radius: 8px;
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
            border-width: 6px;
            border-style: solid;
            border-color: #1F2937 transparent transparent transparent;
        }

        .info-icon:hover .tooltip {
            visibility: visible;
            opacity: 1;
        }

        .result-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 16px;
            color: white;
        }

        .sail-indicator {
            padding: 8px 16px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 14px;
        }

        .sail-1,
        .sail-2 {
            background: #10B981;
            color: white;
        }

        .sail-3,
        .sail-4 {
            background: #F59E0B;
            color: white;
        }

        .sail-5,
        .sail-6 {
            background: #EF4444;
            color: white;
        }

        .loading-spinner {
            border: 3px solid #f3f3f3;
            border-top: 3px solid #2D69E7;
            border-radius: 50%;
            width: 30px;
            height: 30px;
            animation: spin 1s linear infinite;
            margin: 0 auto;
        }

        @keyframes spin {
            0% {
                transform: rotate(0deg);
            }

            100% {
                transform: rotate(360deg);
            }
        }
    </style>
</head>

<body class="min-h-screen flex flex-col bg-lightBg">
    <!-- Header -->
    <header class="bg-white shadow-sm">
        <div class="container mx-auto px-4 py-4 flex justify-between items-center">
            <div class="flex items-center">
                <div class="bg-primary p-2 rounded-lg mr-3">
                    <i class="fas fa-drone text-white text-xl"></i>
                </div>
                <h1 class="text-xl font-bold text-secondary">DroneFlightPlanner</h1>
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
                <h1 class="text-3xl font-bold text-secondary mb-2">SORA Risicoanalyse</h1>
                <p class="text-gray-600">Stap 2: Beantwoord de vragen voor een nauwkeurige risicoberekening</p>
            </div>

            <!-- Progress Bar -->
            <div class="mb-10">
                <div class="flex justify-between relative">
                    <div class="absolute h-1 bg-gray-200 top-1/2 left-0 right-0 -translate-y-1/2 -z-10"></div>

                    <div class="progress-step completed flex flex-col items-center">
                        <div class="w-12 h-12 rounded-full bg-green-500 flex items-center justify-center text-white font-bold mb-2">
                            <i class="fas fa-check"></i>
                        </div>
                        <span class="text-sm font-medium">Basisgegevens</span>
                    </div>

                    <div class="progress-step active flex flex-col items-center">
                        <div class="w-12 h-12 rounded-full bg-primary flex items-center justify-center text-white font-bold mb-2">2</div>
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

            <!-- Current parameters display -->
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-6 mb-8">
                <h4 class="font-semibold text-blue-800 mb-4 flex items-center">
                    <i class="fas fa-info-circle mr-2"></i>
                    Huidige Parameters (uit Stap 1)
                </h4>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                    <div class="bg-white p-3 rounded-lg">
                        <span class="text-blue-600 block font-medium">Max UAS:</span>
                        <strong class="text-lg"><?= $initialSoraParams['max_uas'] ?? 'N/A' ?>m</strong>
                    </div>
                    <div class="bg-white p-3 rounded-lg">
                        <span class="text-blue-600 block font-medium">Snelheid:</span>
                        <strong class="text-lg"><?= $initialSoraParams['speed'] ?? $flightData['flight_speed'] ?? 'N/A' ?>m/s</strong>
                    </div>
                    <?php if ($soraVersion === '2.0'): ?>
                        <div class="bg-white p-3 rounded-lg">
                            <span class="text-blue-600 block font-medium">Kin. Energie:</span>
                            <strong class="text-lg"><?= round($initialSoraParams['kinetic_energy'] ?? 0) ?>J</strong>
                        </div>
                        <div class="bg-white p-3 rounded-lg">
                            <span class="text-blue-600 block font-medium">Scenario:</span>
                            <strong class="text-lg"><?= $initialSoraParams['operational_scenario'] ?? 'N/A' ?></strong>
                        </div>
                    <?php else: ?>
                        <div class="bg-white p-3 rounded-lg">
                            <span class="text-blue-600 block font-medium">IGRC:</span>
                            <strong class="text-lg"><?= $initialSoraParams['igrc'] ?? 'N/A' ?></strong>
                        </div>
                        <div class="bg-white p-3 rounded-lg">
                            <span class="text-blue-600 block font-medium">Gewicht:</span>
                            <strong class="text-lg"><?= $flightData['mtom'] ?? 'N/A' ?>kg</strong>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Form -->
            <form id="soraForm" class="space-y-8">
                <input type="hidden" id="soraAnalysisId" value="<?= htmlspecialchars($soraAnalysisId) ?>">
                <input type="hidden" id="soraVersion" value="<?= htmlspecialchars($soraVersion) ?>">
                <input type="hidden" id="userId" value="<?= htmlspecialchars($userId) ?>">

                <?php foreach ($questionsBySection as $sectionName => $questions): ?>
                    <div class="bg-white card-shadow rounded-xl overflow-hidden">
                        <div class="p-6 border-b border-borderColor bg-subtleBg">
                            <h3 class="text-xl font-bold text-secondary flex items-center">
                                <i class="fas fa-<?= $sectionName === 'Mitigatie Maatregelen' ? 'shield-alt' : 'plane' ?> mr-3 text-primary"></i>
                                <?= htmlspecialchars($sectionName) ?>
                            </h3>
                            <p class="text-gray-600 text-sm mt-1">
                                <?= $sectionName === 'Mitigatie Maatregelen' ? 'Configureer uw veiligheidsmaatregelen' : 'Beantwoord vragen over het luchtruim' ?>
                            </p>
                        </div>

                        <div class="p-6 space-y-6">
                            <?php foreach ($questions as $question): ?>
                                <div class="question-card rounded-lg p-5 bg-cardBg">
                                    <div class="flex justify-between items-start mb-4">
                                        <label class="block text-sm font-medium text-secondary leading-relaxed flex-1 pr-4">
                                            <?= htmlspecialchars($question['content']) ?>
                                        </label>
                                        <div class="info-icon relative">
                                            <i class="fas fa-info-circle"></i>
                                            <span class="tooltip"><?= htmlspecialchars($question['info']) ?></span>
                                        </div>
                                    </div>

                                    <?php if ($question['type'] === 'y/n'): ?>
                                        <div class="flex space-x-4">
                                            <div class="flex items-center">
                                                <input type="radio" name="q_<?= $question['id'] ?>" id="q_<?= $question['id'] ?>_yes"
                                                    value="yes" class="mr-2 text-primary focus:ring-primary"
                                                    <?= $sectionName === 'Luchtruim Risico' ? '' : '' ?>>
                                                <label for="q_<?= $question['id'] ?>_yes" class="text-sm font-medium">Ja</label>
                                            </div>
                                            <div class="flex items-center">
                                                <input type="radio" name="q_<?= $question['id'] ?>" id="q_<?= $question['id'] ?>_no"
                                                    value="no" class="mr-2 text-primary focus:ring-primary" checked>
                                                <label for="q_<?= $question['id'] ?>_no" class="text-sm font-medium">Nee</label>
                                            </div>
                                        </div>

                                    <?php elseif ($question['type'] === 'mc' && isset($question['options'])): ?>
                                        <div class="grid grid-cols-3 gap-3">
                                            <?php foreach ($question['options'] as $option): ?>
                                                <div class="flex items-center p-3 border border-borderColor rounded-lg hover:border-primary transition-colors">
                                                    <input type="radio" name="q_<?= $question['id'] ?>"
                                                        id="q_<?= $question['id'] ?>_<?= $option ?>"
                                                        value="<?= $option ?>" class="mr-3 text-primary focus:ring-primary"
                                                        <?= $option === 'medium' ? 'checked' : '' ?>>
                                                    <label for="q_<?= $question['id'] ?>_<?= $option ?>" class="text-sm font-medium capitalize flex-1">
                                                        <?= htmlspecialchars($option) ?>
                                                    </label>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>

                <div class="text-center">
                    <button type="submit" class="bg-primary hover:bg-blue-700 text-white px-8 py-4 rounded-lg font-medium transition-colors flex items-center mx-auto">
                        <i class="fas fa-calculator mr-2"></i>
                        SORA Risicoberekening Uitvoeren
                    </button>
                </div>
            </form>

            <!-- Result area -->
            <div id="resultArea" class="mt-8" style="display: none;">
                <div class="result-card p-8 text-center">
                    <div id="loadingState">
                        <div class="loading-spinner mb-4"></div>
                        <h3 class="text-xl font-semibold mb-2">SORA Berekening wordt uitgevoerd...</h3>
                        <p class="opacity-90">Even geduld, we analyseren uw risicoparameters</p>
                    </div>

                    <div id="resultContent" style="display: none;"></div>
                </div>
            </div>

            <!-- Navigation -->
            <div class="flex justify-between items-center mt-8 p-6 bg-white card-shadow rounded-xl">
                <a href="step1.php" class="text-gray-500 hover:text-gray-700 flex items-center text-sm font-medium">
                    <i class="fas fa-arrow-left mr-2"></i> Vorige stap
                </a>
                <button id="nextStepBtn" onclick="window.location.href='step3.php'"
                    class="bg-primary text-white px-6 py-3 rounded-lg hover:bg-blue-700 transition-colors font-medium flex items-center"
                    style="display: none;">
                    Volgende: Mitigaties
                    <i class="fas fa-arrow-right ml-2"></i>
                </button>
            </div>
        </div>
    </main>

    <footer class="py-6 border-t border-borderColor">
        <div class="container mx-auto px-4 text-center">
            <p class="text-gray-600 text-sm">© 2023 DroneFlightPlanner. Alle rechten voorbehouden.</p>
        </div>
    </footer>

    <script>
        const form = document.getElementById("soraForm");
        const resultArea = document.getElementById("resultArea");
        const loadingState = document.getElementById("loadingState");
        const resultContent = document.getElementById("resultContent");
        const nextStepBtn = document.getElementById("nextStepBtn");

        form.addEventListener("submit", async function(e) {
            e.preventDefault();

            // Show loading state
            resultArea.style.display = "block";
            loadingState.style.display = "block";
            resultContent.style.display = "none";
            resultArea.scrollIntoView({
                behavior: 'smooth'
            });

            // Collect form data
            const formData = new FormData(form);
            const answersToSave = [];

            for (const [key, value] of formData.entries()) {
                if (key.startsWith('q_')) {
                    const questionId = parseInt(key.replace('q_', ''));
                    answersToSave.push({
                        question_id: questionId,
                        content: value
                    });
                }
            }

            try {
                const response = await fetch(window.location.href, {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                        "X-Requested-With": "XMLHttpRequest",
                    },
                    body: JSON.stringify({
                        answers: answersToSave
                    }),
                });

                const data = await response.json();

                // Hide loading, show results
                setTimeout(() => {
                    loadingState.style.display = "none";
                    resultContent.style.display = "block";

                    if (response.ok && data.status === 'success') {
                        displaySuccessResult(data.calculation);
                        nextStepBtn.style.display = "block";
                    } else {
                        displayErrorResult(data.message, data.details);
                    }
                }, 1500); // Simulate processing time

            } catch (err) {
                setTimeout(() => {
                    loadingState.style.display = "none";
                    resultContent.style.display = "block";
                    displayErrorResult("Verbindingsfout", err.message);
                }, 1000);
            }
        });

        function displaySuccessResult(calc) {
            const sailLevel = calc.sail2_0 || calc.SAIL || 'N/A';
            const grc = calc.grc2_0 || calc.GRC || 'N/A';
            const mgr = calc.mgr2_0 || calc.MGR || 'N/A';
            const finalGrc = calc.final_score_grc2_0 || calc.FINAL_GRC || 'N/A';
            const arc = calc.authorization || calc.ARC || 'N/A';
            const osoAssuranceCount = calc.oso_assurance2_0 ? calc.oso_assurance2_0.length : 0;
            const osoIntegrityCount = calc.oso_integrity2_0 ? calc.oso_integrity2_0.length : 0;

            let riskLevel = '';
            let riskIcon = '';
            let riskColor = '';

            if (sailLevel <= 2) {
                riskLevel = 'Lage Risico Operatie';
                riskIcon = 'fa-check-circle';
                riskColor = 'text-green-400';
            } else if (sailLevel <= 4) {
                riskLevel = 'Gemiddeld Risico Operatie';
                riskIcon = 'fa-exclamation-triangle';
                riskColor = 'text-yellow-400';
            } else {
                riskLevel = 'Hoge Risico Operatie';
                riskIcon = 'fa-times-circle';
                riskColor = 'text-red-400';
            }

            resultContent.innerHTML = `
                <div class="text-white">
                    <div class="mb-6">
                        <i class="fas ${riskIcon} text-4xl ${riskColor} mb-3"></i>
                        <h3 class="text-2xl font-bold mb-2">SORA Berekening Voltooid</h3>
                        <p class="text-lg opacity-90">${riskLevel}</p>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                        <div class="bg-white bg-opacity-20 rounded-lg p-4">
                            <h4 class="font-semibold mb-3 text-lg">Hoofdresultaten</h4>
                            <div class="space-y-2 text-sm">
                                <div class="flex justify-between">
                                    <span>GRC (Ground Risk):</span>
                                    <strong>${grc}</strong>
                                </div>
                                <div class="flex justify-between">
                                    <span>MGR (Mitigation):</span>
                                    <strong>${mgr}</strong>
                                </div>
                                <div class="flex justify-between">
                                    <span>Final GRC:</span>
                                    <strong>${finalGrc}</strong>
                                </div>
                                <div class="flex justify-between">
                                    <span>ARC (Airspace):</span>
                                    <strong>${arc}</strong>
                                </div>
                            </div>
                        </div>

                        <div class="bg-white bg-opacity-20 rounded-lg p-4">
                            <h4 class="font-semibold mb-3 text-lg">Veiligheidsniveau</h4>
                            <div class="text-center">
                                <div class="sail-indicator sail-${sailLevel} inline-block mb-3">
                                    SAIL ${sailLevel}
                                </div>
                                <p class="text-sm opacity-90">
                                    ${osoAssuranceCount} Assurance + ${osoIntegrityCount} Integrity eisen
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white bg-opacity-20 rounded-lg p-4 mb-6">
                        <h4 class="font-semibold mb-3">Interpretatie</h4>
                        <p class="text-sm opacity-90 leading-relaxed">
                            ${sailLevel <= 2 ? 
                                '✅ Uw operatie valt in de lage risicocategorie. Standaard veiligheidsprocedures zijn voldoende.' : 
                                sailLevel <= 4 ? 
                                '⚠️ Uw operatie vereist gemiddelde risicomitigatie. Extra documentatie en training kunnen nodig zijn.' : 
                                '❌ Uw operatie valt in de hoge risicocategorie. Uitgebreide certificering en speciale procedures zijn vereist.'
                            }
                        </p>
                    </div>

                    <details class="bg-white bg-opacity-10 rounded-lg">
                        <summary class="p-4 cursor-pointer font-medium">
                            <i class="fas fa-code mr-2"></i>Toon technische details
                        </summary>
                        <div class="p-4 pt-0">
                            <pre class="text-xs bg-black bg-opacity-30 p-3 rounded overflow-auto max-h-40">${JSON.stringify(calc, null, 2)}</pre>
                        </div>
                    </details>
                </div>
            `;
        }

        function displayErrorResult(message, details) {
            resultContent.innerHTML = `
                <div class="text-white text-center">
                    <i class="fas fa-exclamation-triangle text-4xl text-red-400 mb-4"></i>
                    <h3 class="text-xl font-bold mb-3">Berekening Mislukt</h3>
                    <div class="bg-white bg-opacity-20 rounded-lg p-4 mb-4">
                        <p class="font-medium mb-2">Foutmelding:</p>
                        <p class="text-sm opacity-90">${message}</p>
                        ${details ? `<p class="text-xs opacity-75 mt-2">Details: ${details}</p>` : ''}
                    </div>
                    <button onclick="location.reload()" class="bg-white bg-opacity-20 hover:bg-opacity-30 px-4 py-2 rounded-lg transition-colors">
                        <i class="fas fa-redo mr-2"></i>Probeer opnieuw
                    </button>
                </div>
            `;
        }

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
    </script>
</body>

</html>