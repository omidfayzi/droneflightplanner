<?php
// /var/www/public/frontend/pages/flight-planning/step4.php
// Vluchtplanning Stap 4 - Finale Beoordeling & Indiening

session_start();

// Zorg dat SORA_API_URL gedefinieerd is
if (!defined('SORA_API_URL')) {
    define('SORA_API_URL', 'https://api.dronesora.holdingthedrones.com/');
}
$soraApiBaseUrl = SORA_API_URL;

// Haal alle sessievariabelen op
$soraResult = $_SESSION['sora_calculation_result'] ?? null;
$soraVersion = $_SESSION['sora_version'] ?? null;
$flightData = $_SESSION['flight_data'] ?? [];
$step3Data = $_SESSION['step3_data'] ?? [];
$complianceStatus = $_SESSION['compliance_status'] ?? [];
$userName = $_SESSION['user']['first_name'] ?? 'Onbekende Gebruiker';
$userId = $_SESSION['user']['id'] ?? 999;

// Check essentiële data
if (!$soraResult || !$soraVersion) {
    $_SESSION['form_error'] = "Vluchtgegevens ontbreken. Start een nieuwe vluchtplanning.";
    header("Location: step1.php");
    exit;
}

// Parse SORA resultaten
$sailLevel = $soraResult['sail2_0'] ?? $soraResult['SAIL'] ?? 0;
$arcLevel = $soraResult['authorization'] ?? $soraResult['ARC'] ?? 'N/A';
$finalGrc = $soraResult['final_score_grc2_0'] ?? $soraResult['FINAL_GRC'] ?? 0;
$grc = $soraResult['grc2_0'] ?? $soraResult['GRC'] ?? 0;
$mgr = $soraResult['mgr2_0'] ?? $soraResult['MGR'] ?? 0;
$osoAssurance = $soraResult['oso_assurance2_0'] ?? [];
$osoIntegrity = $soraResult['oso_integrity2_0'] ?? [];

// Bepaal operatie status
$operationApproved = ($sailLevel <= 4); // SAIL 5-6 zijn hoog risico
$approvalStatus = $operationApproved ? 'approved' : 'requires_authority_approval';

// API helper functie
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

    error_log("API_CALL: $url ($method) - HTTP: $httpCode");

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

// PDF generatie functie
function generateSoraPDF($soraAnalysisId, $soraVersion)
{
    global $soraApiBaseUrl;

    $pdfEndpoint = ($soraVersion === '2.0') ? 'generate-pdf2_0' : 'generate-pdf2_5';
    $pdfPayload = ['sora_analysis_id' => $soraAnalysisId];

    $pdfResult = callExternalApi($soraApiBaseUrl . $pdfEndpoint, $pdfPayload, 'POST');

    if (isset($pdfResult['error'])) {
        error_log("PDF Generation failed: " . $pdfResult['error']);
        return false;
    }

    return true;
}

// Verwerk finale indiening
$submissionResult = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['final_submit'])) {
    try {
        // Genereer PDF rapport
        $soraAnalysisId = $_SESSION['sora_analysis_id'] ?? null;
        $pdfGenerated = false;

        if ($soraAnalysisId) {
            $pdfGenerated = generateSoraPDF($soraAnalysisId, $soraVersion);
        }

        // Sla finale submission data op
        $submissionData = [
            'user_id' => $userId,
            'flight_name' => $flightData['flight_name'] ?? 'Unnamed Flight',
            'sora_version' => $soraVersion,
            'sail_level' => $sailLevel,
            'arc_level' => $arcLevel,
            'approval_status' => $approvalStatus,
            'submitted_at' => date('Y-m-d H:i:s'),
            'pdf_generated' => $pdfGenerated,
            'compliance_complete' => !empty($complianceStatus)
        ];

        $_SESSION['submission_result'] = $submissionData;
        $submissionResult = $submissionData;

        // Clear werkelijke sessie data na succesvolle indiening
        unset($_SESSION['sora_analysis_id']);
        unset($_SESSION['sora_calculation_result']);
        unset($_SESSION['sora_answers']);
        unset($_SESSION['initial_sora_params']);
    } catch (Exception $e) {
        $submissionResult = ['error' => $e->getMessage()];
        error_log("Final submission error: " . $e->getMessage());
    }
}

// Helper functie om risico interpretatie te geven
function getRiskInterpretation($sailLevel)
{
    if ($sailLevel <= 2) {
        return [
            'level' => 'Laag Risico',
            'color' => 'green',
            'icon' => 'fa-check-circle',
            'description' => 'Deze operatie valt in de lage risicocategorie. Standaard veiligheidsprocedures zijn voldoende.',
            'requirements' => 'Minimale documentatie en standaard piloot competenties vereist.'
        ];
    } elseif ($sailLevel <= 4) {
        return [
            'level' => 'Gemiddeld Risico',
            'color' => 'yellow',
            'icon' => 'fa-exclamation-triangle',
            'description' => 'Deze operatie vereist verhoogde veiligheidsmaatregelen en documentatie.',
            'requirements' => 'Uitgebreide risicobeoordeling, extra training en monitoring vereist.'
        ];
    } else {
        return [
            'level' => 'Hoog Risico',
            'color' => 'red',
            'icon' => 'fa-times-circle',
            'description' => 'Deze operatie valt in de hoge risicocategorie en vereist speciale autorisatie.',
            'requirements' => 'Uitgebreide certificering, derde partij validatie en authority approval vereist.'
        ];
    }
}

$riskInfo = getRiskInterpretation($sailLevel);

$formError = $_SESSION['form_error'] ?? '';
unset($_SESSION['form_error']);
?>

<!DOCTYPE html>
<html lang="nl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Flight Planning - Step 4 | DroneFlightPlanner</title>
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

        .progress-step {
            transition: all 0.3s ease;
        }

        .progress-step.active {
            color: black;
        }

        .progress-step.completed {
            color: black;
        }

        .status-badge {
            padding: 6px 12px;
            border-radius: 16px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .status-approved {
            background: #DCFDE6;
            color: #059669;
        }

        .status-pending {
            background: #FEF3C7;
            color: #D97706;
        }

        .status-rejected {
            background: #FEE2E2;
            color: #DC2626;
        }

        .summary-card {
            transition: all 0.2s ease;
            border: 1px solid #EAEAE6;
        }

        .summary-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(16, 24, 38, 0.1);
        }

        .success-animation {
            animation: successPulse 2s ease-in-out infinite;
        }

        @keyframes successPulse {

            0%,
            100% {
                transform: scale(1);
            }

            50% {
                transform: scale(1.05);
            }
        }

        .final-summary-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
        }

        @media (max-width: 768px) {
            .final-summary-grid {
                grid-template-columns: 1fr;
            }
        }

        .print-section {
            page-break-inside: avoid;
        }

        @media print {
            .no-print {
                display: none !important;
            }

            body {
                background: white !important;
            }
        }
    </style>
</head>

<body class="min-h-screen flex flex-col bg-lightBg">
    <!-- Header -->
    <header class="bg-white shadow-sm no-print">
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

            <?php if ($submissionResult && !isset($submissionResult['error'])): ?>
                <!-- Success State -->
                <div class="text-center mb-10">
                    <div class="success-animation inline-block mb-6">
                        <div class="w-20 h-20 bg-green-100 rounded-full flex items-center justify-center">
                            <i class="fas fa-check text-3xl text-green-600"></i>
                        </div>
                    </div>
                    <h1 class="text-3xl font-bold text-secondary mb-2">Vluchtplanning Succesvol Ingediend!</h1>
                    <p class="text-gray-600">Uw drone operatie is beoordeeld en <?= $operationApproved ? 'goedgekeurd' : 'doorverwezen voor additionele beoordeling' ?></p>
                </div>

                <!-- Final Status Card -->
                <div class="bg-white card-shadow rounded-xl p-8 mb-8 text-center">
                    <div class="status-badge status-<?= $operationApproved ? 'approved' : 'pending' ?> inline-block mb-4">
                        <?= $operationApproved ? 'Operatie Goedgekeurd' : 'Wacht op Authority Goedkeuring' ?>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mt-6">
                        <div class="text-center">
                            <div class="text-2xl font-bold text-primary mb-1">SAIL <?= $sailLevel ?></div>
                            <div class="text-sm text-gray-600"><?= $riskInfo['level'] ?></div>
                        </div>
                        <div class="text-center">
                            <div class="text-2xl font-bold text-primary mb-1"><?= htmlspecialchars($arcLevel) ?></div>
                            <div class="text-sm text-gray-600">Airspace Classification</div>
                        </div>
                        <div class="text-center">
                            <div class="text-2xl font-bold text-primary mb-1"><?= count($osoAssurance) + count($osoIntegrity) ?></div>
                            <div class="text-sm text-gray-600">OSO Requirements</div>
                        </div>
                    </div>
                </div>

            <?php else: ?>
                <!-- Review State -->
                <div class="mb-10 text-center">
                    <h1 class="text-3xl font-bold text-secondary mb-2">Finale Beoordeling</h1>
                    <p class="text-gray-600">Stap 4: Controleer alle gegevens en dien uw vluchtplanning in</p>
                </div>
            <?php endif; ?>

            <!-- Progress Bar -->
            <div class="mb-10 no-print">
                <div class="flex justify-between relative">
                    <div class="absolute h-1 bg-gray-200 top-1/2 left-0 right-0 -translate-y-1/2 -z-10"></div>

                    <div class="progress-step completed flex flex-col items-center">
                        <div class="w-12 h-12 rounded-full bg-green-500 flex items-center justify-center text-white font-bold mb-2">
                            <i class="fas fa-check"></i>
                        </div>
                        <span class="text-sm font-medium">Basisgegevens</span>
                    </div>

                    <div class="progress-step completed flex flex-col items-center">
                        <div class="w-12 h-12 rounded-full bg-green-500 flex items-center justify-center text-white font-bold mb-2">
                            <i class="fas fa-check"></i>
                        </div>
                        <span class="text-sm font-medium">SORA Analyse</span>
                    </div>

                    <div class="progress-step completed flex flex-col items-center">
                        <div class="w-12 h-12 rounded-full bg-green-500 flex items-center justify-center text-white font-bold mb-2">
                            <i class="fas fa-check"></i>
                        </div>
                        <span class="text-sm font-medium">Mitigaties</span>
                    </div>

                    <div class="progress-step active flex flex-col items-center">
                        <div class="w-12 h-12 rounded-full bg-primary flex items-center justify-center text-white font-bold mb-2">
                            <?= $submissionResult ? '<i class="fas fa-check"></i>' : '4' ?>
                        </div>
                        <span class="text-sm font-medium">Beoordeling</span>
                    </div>
                </div>
            </div>

            <?php if (!empty($formError)): ?>
                <div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-lg text-red-700 no-print">
                    <i class="fas fa-exclamation-circle mr-2"></i>
                    <?= htmlspecialchars($formError) ?>
                </div>
            <?php endif; ?>

            <!-- Executive Summary -->
            <div class="bg-white card-shadow rounded-xl overflow-hidden mb-8 print-section">
                <div class="p-6 border-b border-borderColor bg-subtleBg">
                    <h3 class="text-xl font-bold text-secondary flex items-center">
                        <i class="fas fa-clipboard-list mr-3 text-primary"></i>
                        Executive Summary
                    </h3>
                    <p class="text-gray-600 text-sm mt-1">Overzicht van uw complete drone operatie beoordeling</p>
                </div>

                <div class="p-6">
                    <div class="final-summary-grid">
                        <!-- Left Column - Flight Details -->
                        <div class="space-y-6">
                            <div>
                                <h4 class="font-semibold text-secondary mb-3 flex items-center">
                                    <i class="fas fa-plane mr-2 text-primary"></i>
                                    Vluchtgegevens
                                </h4>
                                <div class="space-y-2 text-sm">
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">Vluchtnaam:</span>
                                        <strong><?= htmlspecialchars($flightData['flight_name'] ?? 'N/A') ?></strong>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">SORA Versie:</span>
                                        <strong><?= htmlspecialchars($soraVersion) ?></strong>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">Max Dimensie:</span>
                                        <strong><?= htmlspecialchars($flightData['characteristic_dimension'] ?? 'N/A') ?>m</strong>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">Gewicht:</span>
                                        <strong><?= htmlspecialchars($flightData['mtom'] ?? 'N/A') ?>kg</strong>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">Vliegsnelheid:</span>
                                        <strong><?= htmlspecialchars($flightData['flight_speed'] ?? 'N/A') ?>m/s</strong>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">Scenario:</span>
                                        <strong><?= htmlspecialchars($flightData['operational_scenario_id'] ?? 'N/A') ?></strong>
                                    </div>
                                </div>
                            </div>

                            <div>
                                <h4 class="font-semibold text-secondary mb-3 flex items-center">
                                    <i class="fas fa-map-marker-alt mr-2 text-primary"></i>
                                    Operationeel Gebied
                                </h4>
                                <div class="space-y-2 text-sm">
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">Populatiedichtheid:</span>
                                        <strong><?= htmlspecialchars($flightData['population_density_id'] ?? 'N/A') ?></strong>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">Max Hoogte:</span>
                                        <strong><?= htmlspecialchars($flightData['max_flight_height'] ?? 'N/A') ?>m</strong>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Right Column - Risk Assessment -->
                        <div class="space-y-6">
                            <div>
                                <h4 class="font-semibold text-secondary mb-3 flex items-center">
                                    <i class="fas fa-<?= $riskInfo['icon'] ?> mr-2 text-<?= $riskInfo['color'] ?>-500"></i>
                                    Risicobeoordeling
                                </h4>
                                <div class="space-y-3">
                                    <div class="p-4 bg-<?= $riskInfo['color'] ?>-50 border border-<?= $riskInfo['color'] ?>-200 rounded-lg">
                                        <div class="font-medium text-<?= $riskInfo['color'] ?>-800 mb-1"><?= $riskInfo['level'] ?></div>
                                        <p class="text-<?= $riskInfo['color'] ?>-700 text-sm"><?= $riskInfo['description'] ?></p>
                                    </div>

                                    <div class="grid grid-cols-2 gap-3 text-sm">
                                        <div class="bg-gray-50 p-3 rounded-lg text-center">
                                            <div class="font-bold text-lg text-secondary">GRC <?= $grc ?></div>
                                            <div class="text-gray-600 text-xs">Ground Risk</div>
                                        </div>
                                        <div class="bg-gray-50 p-3 rounded-lg text-center">
                                            <div class="font-bold text-lg text-secondary">MGR <?= $mgr ?></div>
                                            <div class="text-gray-600 text-xs">Mitigation</div>
                                        </div>
                                        <div class="bg-gray-50 p-3 rounded-lg text-center">
                                            <div class="font-bold text-lg text-secondary">Final <?= $finalGrc ?></div>
                                            <div class="text-gray-600 text-xs">Final GRC</div>
                                        </div>
                                        <div class="bg-gray-50 p-3 rounded-lg text-center">
                                            <div class="font-bold text-lg text-secondary"><?= $arcLevel ?></div>
                                            <div class="text-gray-600 text-xs">Airspace</div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div>
                                <h4 class="font-semibold text-secondary mb-3 flex items-center">
                                    <i class="fas fa-shield-alt mr-2 text-primary"></i>
                                    Veiligheidseisen
                                </h4>
                                <div class="space-y-2 text-sm">
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">SAIL Niveau:</span>
                                        <strong class="text-<?= $riskInfo['color'] ?>-600">SAIL <?= $sailLevel ?></strong>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">OSO Assurance:</span>
                                        <strong><?= count($osoAssurance) ?> eisen</strong>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">OSO Integrity:</span>
                                        <strong><?= count($osoIntegrity) ?> eisen</strong>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">Compliance Status:</span>
                                        <strong class="text-green-600">
                                            <?= !empty($complianceStatus) ? 'Voltooid' : 'Gedeeltelijk' ?>
                                        </strong>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Compliance Summary -->
            <?php if (!empty($complianceStatus)): ?>
                <div class="bg-white card-shadow rounded-xl overflow-hidden mb-8 print-section">
                    <div class="p-6 border-b border-borderColor bg-subtleBg">
                        <h3 class="text-xl font-bold text-secondary flex items-center">
                            <i class="fas fa-check-circle mr-3 text-green-500"></i>
                            Compliance Overzicht
                        </h3>
                    </div>

                    <div class="p-6">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <div class="text-center p-4 bg-green-50 rounded-lg">
                                <div class="text-2xl font-bold text-green-600 mb-1">
                                    <?= $complianceStatus['documents_uploaded'] ?? 0 ?>
                                </div>
                                <div class="text-sm text-green-700">Documenten Geüpload</div>
                            </div>
                            <div class="text-center p-4 bg-blue-50 rounded-lg">
                                <div class="text-2xl font-bold text-blue-600 mb-1">
                                    <?= $complianceStatus['mitigations_confirmed'] ?? 0 ?>
                                </div>
                                <div class="text-sm text-blue-700">Mitigaties Bevestigd</div>
                            </div>
                            <div class="text-center p-4 bg-gray-50 rounded-lg">
                                <div class="text-2xl font-bold text-gray-600 mb-1">
                                    <i class="fas fa-check"></i>
                                </div>
                                <div class="text-sm text-gray-700">Verklaringen Ondertekend</div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Decision Matrix -->
            <div class="bg-white card-shadow rounded-xl overflow-hidden mb-8 print-section">
                <div class="p-6 border-b border-borderColor bg-subtleBg">
                    <h3 class="text-xl font-bold text-secondary flex items-center">
                        <i class="fas fa-balance-scale mr-3 text-primary"></i>
                        Goedkeuringsbeslissing
                    </h3>
                </div>

                <div class="p-6">
                    <?php if ($operationApproved): ?>
                        <div class="bg-green-50 border border-green-200 rounded-lg p-6">
                            <div class="flex items-center mb-4">
                                <i class="fas fa-check-circle text-2xl text-green-600 mr-3"></i>
                                <div>
                                    <h4 class="font-bold text-green-800">Operatie Goedgekeurd</h4>
                                    <p class="text-green-700 text-sm">Uw drone operatie voldoet aan alle SORA vereisten</p>
                                </div>
                            </div>
                            <div class="space-y-2 text-sm text-green-700">
                                <p><strong>✓</strong> SAIL niveau acceptabel (≤ 4)</p>
                                <p><strong>✓</strong> Alle mitigatiemaatregelen geïmplementeerd</p>
                                <p><strong>✓</strong> Compliance documentatie compleet</p>
                                <p><strong>✓</strong> Operationele procedures gevalideerd</p>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-6">
                            <div class="flex items-center mb-4">
                                <i class="fas fa-exclamation-triangle text-2xl text-yellow-600 mr-3"></i>
                                <div>
                                    <h4 class="font-bold text-yellow-800">Authority Goedkeuring Vereist</h4>
                                    <p class="text-yellow-700 text-sm">Hoog risico operatie - speciale autorisatie nodig</p>
                                </div>
                            </div>
                            <div class="space-y-2 text-sm text-yellow-700">
                                <p><strong>!</strong> SAIL niveau hoog (5-6) - speciale beoordeling vereist</p>
                                <p><strong>!</strong> Uitgebreide documentatie moet worden ingediend bij de autoriteit</p>
                                <p><strong>!</strong> Derde partij validatie van procedures vereist</p>
                                <p><strong>!</strong> Additionele certificering kan noodzakelijk zijn</p>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Next Steps -->
            <div class="bg-white card-shadow rounded-xl overflow-hidden mb-8 print-section">
                <div class="p-6 border-b border-borderColor bg-subtleBg">
                    <h3 class="text-xl font-bold text-secondary flex items-center">
                        <i class="fas fa-route mr-3 text-primary"></i>
                        Vervolgstappen
                    </h3>
                </div>

                <div class="p-6">
                    <?php if ($submissionResult && !isset($submissionResult['error'])): ?>
                        <!-- Post-submission steps -->
                        <div class="space-y-4">
                            <div class="flex items-center p-4 bg-green-50 rounded-lg">
                                <div class="w-8 h-8 bg-green-500 rounded-full flex items-center justify-center text-white font-bold mr-4">1</div>
                                <div>
                                    <h4 class="font-semibold text-green-800">Vluchtplanning Ingediend</h4>
                                    <p class="text-green-700 text-sm">Uw complete SORA assessment is opgeslagen en ingediend</p>
                                </div>
                                <i class="fas fa-check text-green-500 ml-auto"></i>
                            </div>

                            <?php if ($operationApproved): ?>
                                <div class="flex items-center p-4 bg-blue-50 rounded-lg">
                                    <div class="w-8 h-8 bg-blue-500 rounded-full flex items-center justify-center text-white font-bold mr-4">2</div>
                                    <div>
                                        <h4 class="font-semibold text-blue-800">Operatie Goedgekeurd</h4>
                                        <p class="text-blue-700 text-sm">U kunt uw drone operatie uitvoeren volgens de gespecificeerde parameters</p>
                                    </div>
                                    <i class="fas fa-check text-blue-500 ml-auto"></i>
                                </div>

                                <div class="flex items-center p-4 bg-gray-50 rounded-lg">
                                    <div class="w-8 h-8 bg-gray-400 rounded-full flex items-center justify-center text-white font-bold mr-4">3</div>
                                    <div>
                                        <h4 class="font-semibold text-gray-800">Pre-Flight Checks</h4>
                                        <p class="text-gray-700 text-sm">Voer alle vereiste pre-flight inspections uit voor de vlucht</p>
                                    </div>
                                </div>
                            <?php else: ?>
                                <div class="flex items-center p-4 bg-yellow-50 rounded-lg">
                                    <div class="w-8 h-8 bg-yellow-500 rounded-full flex items-center justify-center text-white font-bold mr-4">2</div>
                                    <div>
                                        <h4 class="font-semibold text-yellow-800">Wacht op Authority Beoordeling</h4>
                                        <p class="text-yellow-700 text-sm">Uw aanvraag wordt doorgestuurd naar de competente autoriteit</p>
                                    </div>
                                    <i class="fas fa-clock text-yellow-500 ml-auto"></i>
                                </div>

                                <div class="flex items-center p-4 bg-gray-50 rounded-lg">
                                    <div class="w-8 h-8 bg-gray-400 rounded-full flex items-center justify-center text-white font-bold mr-4">3</div>
                                    <div>
                                        <h4 class="font-semibold text-gray-800">Additionele Documentatie</h4>
                                        <p class="text-gray-700 text-sm">Bereid eventuele extra documentatie voor zoals gevraagd door de autoriteit</p>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <!-- Pre-submission steps -->
                        <div class="space-y-4">
                            <div class="flex items-center p-4 bg-blue-50 rounded-lg">
                                <div class="w-8 h-8 bg-blue-500 rounded-full flex items-center justify-center text-white font-bold mr-4">1</div>
                                <div>
                                    <h4 class="font-semibold text-blue-800">Controleer Alle Gegevens</h4>
                                    <p class="text-blue-700 text-sm">Verifieer dat alle informatie correct en compleet is</p>
                                </div>
                            </div>

                            <div class="flex items-center p-4 bg-gray-50 rounded-lg">
                                <div class="w-8 h-8 bg-gray-400 rounded-full flex items-center justify-center text-white font-bold mr-4">2</div>
                                <div>
                                    <h4 class="font-semibold text-gray-800">Dien Vluchtplanning In</h4>
                                    <p class="text-gray-700 text-sm">Bevestig uw finale indiening om het proces af te ronden</p>
                                </div>
                            </div>

                            <div class="flex items-center p-4 bg-gray-50 rounded-lg">
                                <div class="w-8 h-8 bg-gray-400 rounded-full flex items-center justify-center text-white font-bold mr-4">3</div>
                                <div>
                                    <h4 class="font-semibold text-gray-800">Ontvang Goedkeuring</h4>
                                    <p class="text-gray-700 text-sm">Wacht op de finale beoordeling en goedkeuring</p>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Technical Details (Collapsible) -->
            <div class="bg-white card-shadow rounded-xl overflow-hidden mb-8 print-section">
                <div class="p-6 border-b border-borderColor bg-subtleBg">
                    <h3 class="text-xl font-bold text-secondary flex items-center">
                        <i class="fas fa-cogs mr-3 text-primary"></i>
                        Technische Details
                        <button type="button" onclick="toggleTechnicalDetails()" class="ml-auto no-print">
                            <i class="fas fa-chevron-down" id="technicalChevron"></i>
                        </button>
                    </h3>
                </div>

                <div id="technicalDetails" class="hidden">
                    <div class="p-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <h4 class="font-semibold text-secondary mb-3">SORA Parameters</h4>
                                <div class="bg-gray-50 p-4 rounded-lg">
                                    <pre class="text-xs text-gray-700 overflow-auto"><?= json_encode([
                                                                                            'sora_version' => $soraVersion,
                                                                                            'max_uas' => $flightData['characteristic_dimension'] ?? 'N/A',
                                                                                            'speed' => $flightData['flight_speed'] ?? 'N/A',
                                                                                            'weight' => $flightData['mtom'] ?? 'N/A',
                                                                                            'scenario' => $flightData['operational_scenario_id'] ?? 'N/A',
                                                                                            'population_density' => $flightData['population_density_id'] ?? 'N/A'
                                                                                        ], JSON_PRETTY_PRINT) ?></pre>
                                </div>
                            </div>

                            <div>
                                <h4 class="font-semibold text-secondary mb-3">Calculation Results</h4>
                                <div class="bg-gray-50 p-4 rounded-lg">
                                    <pre class="text-xs text-gray-700 overflow-auto max-h-40"><?= json_encode([
                                                                                                    'sail_level' => $sailLevel,
                                                                                                    'arc_level' => $arcLevel,
                                                                                                    'grc' => $grc,
                                                                                                    'mgr' => $mgr,
                                                                                                    'final_grc' => $finalGrc,
                                                                                                    'oso_assurance_count' => count($osoAssurance),
                                                                                                    'oso_integrity_count' => count($osoIntegrity),
                                                                                                    'approval_status' => $approvalStatus
                                                                                                ], JSON_PRETTY_PRINT) ?></pre>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <?php if (!$submissionResult): ?>
                <!-- Final Submission Form -->
                <form action="step4.php" method="post" class="bg-white card-shadow rounded-xl overflow-hidden mb-8">
                    <div class="p-6 border-b border-borderColor bg-subtleBg">
                        <h3 class="text-xl font-bold text-secondary flex items-center">
                            <i class="fas fa-file-signature mr-3 text-primary"></i>
                            Finale Bevestiging
                        </h3>
                    </div>

                    <div class="p-6">
                        <div class="space-y-4 mb-6">
                            <div class="flex items-start">
                                <input type="checkbox" id="final_review_check" required
                                    class="mr-3 mt-1 text-primary focus:ring-primary">
                                <label for="final_review_check" class="text-sm">
                                    <strong>Finale Review:</strong> Ik heb alle bovenstaande informatie gecontroleerd en bevestig dat deze correct en volledig is.
                                </label>
                            </div>

                            <div class="flex items-start">
                                <input type="checkbox" id="operational_responsibility" required
                                    class="mr-3 mt-1 text-primary focus:ring-primary">
                                <label for="operational_responsibility" class="text-sm">
                                    <strong>Operationele Verantwoordelijkheid:</strong> Ik neem volledige verantwoordelijkheid voor de veilige uitvoering van deze drone operatie conform de SORA <?= htmlspecialchars($soraVersion) ?> beoordeling.
                                </label>
                            </div>

                            <div class="flex items-start">
                                <input type="checkbox" id="regulatory_compliance_final" required
                                    class="mr-3 mt-1 text-primary focus:ring-primary">
                                <label for="regulatory_compliance_final" class="text-sm">
                                    <strong>Regelgeving Naleving:</strong> Ik verklaar dat deze operatie wordt uitgevoerd in overeenstemming met alle van toepassing zijnde Nederlandse en Europese drone wetgeving.
                                </label>
                            </div>
                        </div>

                        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-6">
                            <div class="flex items-center mb-2">
                                <i class="fas fa-exclamation-triangle text-yellow-600 mr-2"></i>
                                <strong class="text-yellow-800">Belangrijke Opmerking</strong>
                            </div>
                            <p class="text-yellow-700 text-sm">
                                Na indiening kan deze vluchtplanning niet meer worden gewijzigd. Zorg ervoor dat alle gegevens correct zijn voordat u indient.
                                <?= !$operationApproved ? ' Deze operatie zal doorverwezen worden naar de competente autoriteit voor additionele beoordeling.' : '' ?>
                            </p>
                        </div>

                        <div class="text-center">
                            <button type="submit" name="final_submit" value="1"
                                class="bg-primary hover:bg-blue-700 text-white px-8 py-4 rounded-lg font-medium transition-colors flex items-center mx-auto">
                                <i class="fas fa-paper-plane mr-2"></i>
                                Vluchtplanning Definitief Indienen
                            </button>
                        </div>
                    </div>
                </form>
            <?php endif; ?>

            <!-- Action Buttons -->
            <div class="flex justify-between items-center p-6 bg-white card-shadow rounded-xl no-print">
                <?php if ($submissionResult && !isset($submissionResult['error'])): ?>
                    <!-- Post-submission actions -->
                    <div class="flex space-x-4 mx-auto">
                        <button onclick="window.print()" class="bg-gray-500 hover:bg-gray-600 text-white px-6 py-3 rounded-lg font-medium transition-colors flex items-center">
                            <i class="fas fa-print mr-2"></i>
                            Print Rapport
                        </button>
                        <a href="/app/views/flight-planning-details.php?id=<?= $submissionResult['user_id'] ?? '' ?>"
                            class="bg-primary hover:bg-blue-700 text-white px-6 py-3 rounded-lg font-medium transition-colors flex items-center">
                            <i class="fas fa-eye mr-2"></i>
                            Details Bekijken
                        </a>
                        <a href="/app/views/dashboard.php"
                            class="bg-green-600 hover:bg-green-700 text-white px-6 py-3 rounded-lg font-medium transition-colors flex items-center">
                            <i class="fas fa-home mr-2"></i>
                            Naar Dashboard
                        </a>
                    </div>
                <?php else: ?>
                    <!-- Pre-submission actions -->
                    <a href="step3.php" class="text-gray-500 hover:text-gray-700 flex items-center text-sm font-medium">
                        <i class="fas fa-arrow-left mr-2"></i> Vorige stap
                    </a>
                    <div class="flex space-x-4">
                        <a href="step1.php" class="bg-gray-500 hover:bg-gray-600 text-white px-6 py-3 rounded-lg font-medium transition-colors flex items-center">
                            <i class="fas fa-edit mr-2"></i>
                            Wijzigingen Maken
                        </a>
                        <button onclick="window.print()" class="bg-gray-500 hover:bg-gray-600 text-white px-6 py-3 rounded-lg font-medium transition-colors flex items-center">
                            <i class="fas fa-print mr-2"></i>
                            Preview Print
                        </button>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Footer Information -->
            <div class="text-center text-sm text-gray-600 mt-8">
                <p>
                    Gegenereerd op <?= date('d-m-Y H:i:s') ?> |
                    SORA <?= htmlspecialchars($soraVersion) ?> |
                    Gebruiker: <?= htmlspecialchars($userName) ?> |
                    <?php if ($submissionResult): ?>
                        Referentie: #<?= substr(md5($submissionResult['submitted_at'] ?? time()), 0, 8) ?>
                    <?php endif; ?>
                </p>
            </div>
        </div>
    </main>

    <footer class="py-6 border-t border-borderColor no-print">
        <div class="container mx-auto px-4 text-center">
            <p class="text-gray-600 text-sm">© 2025 DroneFlightPlanner. Alle rechten voorbehouden.</p>
        </div>
    </footer>

    <script>
        function toggleTechnicalDetails() {
            const details = document.getElementById('technicalDetails');
            const chevron = document.getElementById('technicalChevron');

            if (details.classList.contains('hidden')) {
                details.classList.remove('hidden');
                chevron.classList.remove('fa-chevron-down');
                chevron.classList.add('fa-chevron-up');
            } else {
                details.classList.add('hidden');
                chevron.classList.remove('fa-chevron-up');
                chevron.classList.add('fa-chevron-down');
            }
        }

        // Form validation before final submission
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.querySelector('form[action="step4.php"]');
            if (form) {
                form.addEventListener('submit', function(e) {
                    const checkboxes = this.querySelectorAll('input[type="checkbox"][required]');
                    let allChecked = true;

                    checkboxes.forEach(checkbox => {
                        if (!checkbox.checked) {
                            allChecked = false;
                            checkbox.focus();
                        }
                    });

                    if (!allChecked) {
                        e.preventDefault();
                        alert('Alle verplichte verklaringen moeten worden bevestigd voordat u kunt indienen.');
                        return false;
                    }

                    // Final confirmation
                    const confirmed = confirm(
                        'U staat op het punt om uw vluchtplanning definitief in te dienen.\n\n' +
                        'Dit kan niet ongedaan worden gemaakt. Weet u zeker dat u wilt doorgaan?'
                    );

                    if (!confirmed) {
                        e.preventDefault();
                        return false;
                    }

                    // Show loading state
                    const submitBtn = this.querySelector('button[type="submit"]');
                    submitBtn.disabled = true;
                    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Indienen...';
                });
            }
        });

        // Auto-save functionality for review notes
        function autoSave() {
            const reviewData = {
                timestamp: new Date().toISOString(),
                page_viewed: 'step4_final_review',
                sail_level: <?= $sailLevel ?>,
                approval_status: '<?= $approvalStatus ?>'
            };

            sessionStorage.setItem('step4_review', JSON.stringify(reviewData));
        }

        // Save review timestamp
        document.addEventListener('DOMContentLoaded', autoSave);

        // Print optimization
        window.addEventListener('beforeprint', function() {
            document.body.classList.add('printing');
        });

        window.addEventListener('afterprint', function() {
            document.body.classList.remove('printing');
        });

        // Success animation for submitted state
        <?php if ($submissionResult && !isset($submissionResult['error'])): ?>
            document.addEventListener('DOMContentLoaded', function() {
                // Scroll to top smoothly
                window.scrollTo({
                    top: 0,
                    behavior: 'smooth'
                });

                // Trigger success animation
                setTimeout(function() {
                    const successIcon = document.querySelector('.success-animation');
                    if (successIcon) {
                        successIcon.style.transform = 'scale(1.1)';
                        setTimeout(function() {
                            successIcon.style.transform = 'scale(1)';
                        }, 200);
                    }
                }, 500);
            });
        <?php endif; ?>
    </script>
</body>

</html>