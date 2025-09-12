<?php
// /var/www/public/frontend/pages/flight-planning/step3.php
// ==================== Vluchtplanning Stap 3 ====================
// Mitigatiemaatregelen & Compliance op basis van SORA resultaten

session_start();

// Controle: zijn de resultaten van de vorige SORA-berekening er?
$soraResult = $_SESSION['sora_calculation_result'] ?? null;
$soraVersion = $_SESSION['sora_version'] ?? null;
$flightData = $_SESSION['flight_data'] ?? [];
$userName = $_SESSION['user']['first_name'] ?? 'Onbekende Gebruiker';

// Als stap 2 niet is voltooid: terugsturen
if (!$soraResult) {
    $_SESSION['form_error'] = "SORA berekening niet gevonden. Voltooi eerst stap 2.";
    header("Location: step2.php");
    exit;
}

// ==================== Resultaten Uitpakken ====================
// Haal relevante SORA-waarden uit de resultaten-array
$sailLevel = $soraResult['sail2_0'] ?? $soraResult['SAIL'] ?? 0;
$arcLevel = $soraResult['authorization'] ?? $soraResult['ARC'] ?? 'N/A';
$finalGrc = $soraResult['final_score_grc2_0'] ?? $soraResult['FINAL_GRC'] ?? 0;
$osoAssurance = $soraResult['oso_assurance2_0'] ?? [];
$osoIntegrity = $soraResult['oso_integrity2_0'] ?? [];

// ==================== Risico-inschatting ====================
// Bepaal welk risico-niveau hoort bij deze vlucht
$riskLevel = 'low';
$riskLabel = 'Laag Risico';
$riskColor = 'green';
if ($sailLevel >= 5) {
    $riskLevel = 'high';
    $riskLabel = 'Hoog Risico';
    $riskColor = 'red';
} elseif ($sailLevel >= 3) {
    $riskLevel = 'medium';
    $riskLabel = 'Gemiddeld Risico';
    $riskColor = 'yellow';
}

// ==================== Documenten en Mitigaties ====================

/**
 * Bepaal welke documenten je minimaal nodig hebt voor dit SAIL-niveau.
 * @param int $sailLevel Het SAIL-niveau van de operatie
 * @param string $arcLevel De ARC-classificatie
 * @return array Lijst van vereiste documenten
 */
function getRequiredDocuments($sailLevel, $arcLevel)
{
    // Altijd basisdocumenten
    $docs = [
        'basic' => [
            'flight_manual' => 'Vluchthandboek',
            'pilot_certificate' => 'Piloot Certificaat',
            'insurance_proof' => 'Verzekeringsbewijs',
            'maintenance_log' => 'Onderhoudslogboek'
        ]
    ];

    // Bij hoog risico (SAIL 4 of hoger): extra documenten nodig
    if ($sailLevel >= 4) {
        $docs['high'] = [
            'risk_assessment' => 'Uitgebreide Risicobeoordeling',
            'contingency_procedures' => 'Contingency Procedures',
            'third_party_validation' => 'Derde Partij Validatie',
            'crm_training_cert' => 'CRM Training Certificaat'
        ];
    }

    // Speciale luchtruimdocumenten bij bepaalde ARC-types
    if (strpos($arcLevel, 'ARC-A') !== false || strpos($arcLevel, 'ARC-D') !== false) {
        $docs['airspace'] = [
            'airspace_authorization' => 'Luchtruim Autorisatie',
            'atc_coordination' => 'ATC Coördinatie Document'
        ];
    }

    return $docs;
}

/**
 * Bepaal benodigde mitigatiemaatregelen op basis van SAIL-niveau en OSO's
 * @param int $sailLevel
 * @param array $osoAssurance
 * @param array $osoIntegrity
 * @return array
 */
function getRequiredMitigations($sailLevel, $osoAssurance, $osoIntegrity)
{
    $mitigations = [];

    // Basis mitigaties voor elke vlucht
    $mitigations['basic'] = [
        'pre_flight_check' => 'Pre-flight Inspectie Checklist',
        'weather_assessment' => 'Weer Beoordeling',
        'battery_management' => 'Batterij Management Plan'
    ];

    // Vanaf SAIL 2: meer eisen
    if ($sailLevel >= 2) {
        $mitigations['operational'] = [
            'vo_assignment' => 'Visual Observer Toewijzing',
            'communication_plan' => 'Communicatie Plan',
            'flight_termination' => 'Flight Termination Procedure'
        ];
    }

    // Vanaf SAIL 4: geavanceerde mitigaties
    if ($sailLevel >= 4) {
        $mitigations['advanced'] = [
            'redundant_systems' => 'Redundante Systemen',
            'ground_personnel' => 'Getraind Grondpersoneel',
            'emergency_response' => 'Emergency Response Team'
        ];
    }

    // Heel veel OSO-eisen? Dan ook speciale mitigaties
    if (count($osoAssurance) > 20) {
        $mitigations['oso_specific'] = [
            'competent_authority_approval' => 'Competent Authority Goedkeuring',
            'third_party_oversight' => 'Derde Partij Toezicht',
            'continuous_monitoring' => 'Continue Monitoring Systemen'
        ];
    }

    return $mitigations;
}

// Genereer arrays van vereiste documenten en mitigaties op basis van deze operatie
$requiredDocs = getRequiredDocuments($sailLevel, $arcLevel);
$requiredMitigations = getRequiredMitigations($sailLevel, $osoAssurance, $osoIntegrity);

// ==================== Verwerk formulierindiening ====================
// Als gebruiker op "Voltooien & Indienen" klikt
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $_SESSION['step3_data'] = $_POST;
    $_SESSION['compliance_status'] = [
        'documents_uploaded' => count($_POST['uploaded_docs'] ?? []),
        'mitigations_confirmed' => count($_POST['mitigations'] ?? []),
        'timestamp' => date('Y-m-d H:i:s')
    ];

    header("Location: step4.php");
    exit;
}

// Eventuele foutmelding ophalen voor weergave
$formError = $_SESSION['form_error'] ?? '';
unset($_SESSION['form_error']);
?>

<!-- ==================== HTML WEERGAVE ==================== -->
<!DOCTYPE html>
<html lang="nl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Flight Planning - Step 3 | DroneFlightPlanner</title>
    <!-- Styling: Tailwind + icons -->
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

        .risk-indicator {
            padding: 8px 16px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 14px;
        }

        .risk-low {
            background: #10B981;
            color: white;
        }

        .risk-medium {
            background: #F59E0B;
            color: white;
        }

        .risk-high {
            background: #EF4444;
            color: white;
        }

        .requirement-card {
            transition: all 0.2s ease;
            border: 2px solid #EAEAE6;
        }

        .requirement-card.completed {
            border-color: #10B981;
            background: #DCFDE6;
        }

        .upload-zone {
            border: 2px dashed #EAEAE6;
            transition: all 0.3s ease;
        }

        .upload-zone:hover {
            border-color: #2D69E7;
            background: #F0F7FF;
        }

        .upload-zone.dragover {
            border-color: #10B981;
            background: #DCFDE6;
        }

        .progress-bar {
            height: 8px;
            background: #EAEAE6;
            border-radius: 4px;
            overflow: hidden;
        }

        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #10B981, #059669);
            transition: width 0.3s ease;
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
                <h1 class="text-3xl font-bold text-secondary mb-2">Compliance & Mitigatiemaatregelen</h1>
                <p class="text-gray-600">Stap 3: Voltooi de vereiste maatregelen voor uw vlucht</p>
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
                    <div class="progress-step completed flex flex-col items-center">
                        <div class="w-12 h-12 rounded-full bg-green-500 flex items-center justify-center text-white font-bold mb-2">
                            <i class="fas fa-check"></i>
                        </div>
                        <span class="text-sm font-medium">SORA Analyse</span>
                    </div>
                    <div class="progress-step active flex flex-col items-center">
                        <div class="w-12 h-12 rounded-full bg-primary flex items-center justify-center text-white font-bold mb-2">3</div>
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

            <!-- SORA Results Samenvatting -->
            <div class="bg-white card-shadow rounded-xl p-6 mb-8">
                <h3 class="text-xl font-bold text-secondary mb-4 flex items-center">
                    <i class="fas fa-chart-line mr-3 text-primary"></i>
                    SORA Risicoanalyse Resultaat
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div class="text-center p-4 bg-subtleBg rounded-lg">
                        <div class="text-2xl font-bold text-primary mb-1">SAIL <?= $sailLevel ?></div>
                        <div class="risk-indicator risk-<?= $riskLevel ?> inline-block"><?= $riskLabel ?></div>
                    </div>
                    <div class="text-center p-4 bg-subtleBg rounded-lg">
                        <div class="text-lg font-bold text-secondary mb-1">ARC</div>
                        <div class="text-primary font-medium"><?= htmlspecialchars($arcLevel) ?></div>
                    </div>
                    <div class="text-center p-4 bg-subtleBg rounded-lg">
                        <div class="text-lg font-bold text-secondary mb-1">OSO Assurance</div>
                        <div class="text-primary font-medium"><?= count($osoAssurance) ?> eisen</div>
                    </div>
                    <div class="text-center p-4 bg-subtleBg rounded-lg">
                        <div class="text-lg font-bold text-secondary mb-1">OSO Integrity</div>
                        <div class="text-primary font-medium"><?= count($osoIntegrity) ?> eisen</div>
                    </div>
                </div>
            </div>

            <!-- Compliance Voortgang Balk -->
            <div class="bg-white card-shadow rounded-xl p-6 mb-8">
                <h3 class="text-lg font-bold text-secondary mb-4">Compliance Voortgang</h3>
                <div class="progress-bar mb-2">
                    <div class="progress-fill" style="width: 0%" id="overallProgress"></div>
                </div>
                <p class="text-sm text-gray-600" id="progressText">0% voltooid - Begin met het uploaden van documenten</p>
            </div>

            <!-- ==================== HOOFD FORMULIER ==================== -->
            <form action="step3.php" method="post" enctype="multipart/form-data" id="complianceForm">

                <!-- Vereiste documenten upload -->
                <div class="bg-white card-shadow rounded-xl overflow-hidden mb-8">
                    <div class="p-6 border-b border-borderColor bg-subtleBg">
                        <h3 class="text-xl font-bold text-secondary flex items-center">
                            <i class="fas fa-file-alt mr-3 text-primary"></i>
                            Vereiste Documenten
                        </h3>
                        <p class="text-gray-600 text-sm mt-1">Upload de benodigde documenten voor uw SAIL <?= $sailLevel ?> operatie</p>
                    </div>
                    <div class="p-6 space-y-6">
                        <?php foreach ($requiredDocs as $category => $docs): ?>
                            <div class="border border-borderColor rounded-lg p-4">
                                <h4 class="font-semibold text-secondary mb-3 capitalize">
                                    <i class="fas fa-folder mr-2 text-primary"></i>
                                    <?= ucfirst($category) ?> Documenten
                                </h4>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <?php foreach ($docs as $docKey => $docName): ?>
                                        <div class="requirement-card rounded-lg p-4">
                                            <div class="flex items-center justify-between mb-3">
                                                <label class="font-medium text-sm"><?= htmlspecialchars($docName) ?></label>
                                                <i class="fas fa-upload text-gray-400"></i>
                                            </div>
                                            <div class="upload-zone rounded border-2 border-dashed p-4 text-center cursor-pointer"
                                                onclick="document.getElementById('file_<?= $docKey ?>').click()">
                                                <p class="text-sm text-gray-600">Klik om bestand te selecteren</p>
                                                <input type="file" id="file_<?= $docKey ?>" name="documents[<?= $docKey ?>]"
                                                    class="hidden" accept=".pdf,.doc,.docx,.jpg,.png"
                                                    onchange="handleFileUpload(this, '<?= $docKey ?>')">
                                            </div>
                                            <div id="file_status_<?= $docKey ?>" class="mt-2 text-sm text-gray-500"></div>
                                            <input type="hidden" name="uploaded_docs[]" value="<?= $docKey ?>" disabled>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Mitigatie maatregelen -->
                <div class="bg-white card-shadow rounded-xl overflow-hidden mb-8">
                    <div class="p-6 border-b border-borderColor bg-subtleBg">
                        <h3 class="text-xl font-bold text-secondary flex items-center">
                            <i class="fas fa-shield-alt mr-3 text-primary"></i>
                            Mitigatiemaatregelen
                        </h3>
                        <p class="text-gray-600 text-sm mt-1">Bevestig dat u de volggende veiligheidsmaatregelen heeft geïmplementeerd</p>
                    </div>
                    <div class="p-6 space-y-6">
                        <?php foreach ($requiredMitigations as $category => $mitigations): ?>
                            <div class="border border-borderColor rounded-lg p-4">
                                <h4 class="font-semibold text-secondary mb-3 capitalize">
                                    <i class="fas fa-cog mr-2 text-primary"></i>
                                    <?= ucfirst(str_replace('_', ' ', $category)) ?> Mitigaties
                                </h4>
                                <div class="space-y-3">
                                    <?php foreach ($mitigations as $mitigationKey => $mitigationName): ?>
                                        <div class="flex items-center p-3 border border-borderColor rounded-lg hover:bg-subtleBg transition-colors">
                                            <input type="checkbox" id="mitigation_<?= $mitigationKey ?>"
                                                name="mitigations[]" value="<?= $mitigationKey ?>"
                                                class="mr-3 text-primary focus:ring-primary"
                                                onchange="updateProgress()">
                                            <label for="mitigation_<?= $mitigationKey ?>" class="flex-1 font-medium text-sm cursor-pointer">
                                                <?= htmlspecialchars($mitigationName) ?>
                                            </label>
                                            <i class="fas fa-info-circle text-gray-400 cursor-pointer"
                                                title="Klik voor meer informatie over deze mitigatiemaatregel"></i>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- OSO Eisen (Compact weergave) -->
                <?php if (count($osoAssurance) > 0 || count($osoIntegrity) > 0): ?>
                    <div class="bg-white card-shadow rounded-xl overflow-hidden mb-8">
                        <div class="p-6 border-b border-borderColor bg-subtleBg">
                            <h3 class="text-xl font-bold text-secondary flex items-center">
                                <i class="fas fa-clipboard-check mr-3 text-primary"></i>
                                OSO Veiligheidseisen
                            </h3>
                            <p class="text-gray-600 text-sm mt-1">Overzicht van alle OSO (Operational Safety Objectives) eisen</p>
                        </div>
                        <div class="p-6">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <?php if (count($osoAssurance) > 0): ?>
                                    <div>
                                        <h4 class="font-semibold text-secondary mb-3">
                                            Assurance Eisen (<?= count($osoAssurance) ?>)
                                        </h4>
                                        <div class="max-h-60 overflow-y-auto space-y-2">
                                            <?php foreach (array_slice($osoAssurance, 0, 5) as $oso): ?>
                                                <div class="p-3 bg-subtleBg rounded-lg text-sm">
                                                    <div class="font-medium"><?= htmlspecialchars($oso['oso_title'] ?? 'OSO Requirement') ?></div>
                                                    <div class="text-gray-600 text-xs mt-1">Level: <?= htmlspecialchars($oso['level'] ?? 'N/A') ?></div>
                                                </div>
                                            <?php endforeach; ?>
                                            <?php if (count($osoAssurance) > 5): ?>
                                                <div class="text-center">
                                                    <button type="button" class="text-primary text-sm hover:underline" onclick="toggleOSODetails('assurance')">
                                                        Toon alle <?= count($osoAssurance) ?> assurance eisen
                                                    </button>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endif; ?>

                                <?php if (count($osoIntegrity) > 0): ?>
                                    <div>
                                        <h4 class="font-semibold text-secondary mb-3">
                                            Integrity Eisen (<?= count($osoIntegrity) ?>)
                                        </h4>
                                        <div class="max-h-60 overflow-y-auto space-y-2">
                                            <?php foreach (array_slice($osoIntegrity, 0, 5) as $oso): ?>
                                                <div class="p-3 bg-subtleBg rounded-lg text-sm">
                                                    <div class="font-medium"><?= htmlspecialchars($oso['oso_title'] ?? 'OSO Requirement') ?></div>
                                                    <div class="text-gray-600 text-xs mt-1">Level: <?= htmlspecialchars($oso['level'] ?? 'N/A') ?></div>
                                                </div>
                                            <?php endforeach; ?>
                                            <?php if (count($osoIntegrity) > 5): ?>
                                                <div class="text-center">
                                                    <button type="button" class="text-primary text-sm hover:underline" onclick="toggleOSODetails('integrity')">
                                                        Toon alle <?= count($osoIntegrity) ?> integrity eisen
                                                    </button>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Verklaringen & akkoord -->
                <div class="bg-white card-shadow rounded-xl overflow-hidden mb-8">
                    <div class="p-6 border-b border-borderColor bg-subtleBg">
                        <h3 class="text-xl font-bold text-secondary flex items-center">
                            <i class="fas fa-certificate mr-3 text-primary"></i>
                            Verklaringen & Akkoord
                        </h3>
                    </div>
                    <div class="p-6 space-y-4">
                        <div class="flex items-start">
                            <input type="checkbox" id="compliance_declaration" name="declarations[]" value="compliance"
                                class="mr-3 mt-1 text-primary focus:ring-primary" required>
                            <label for="compliance_declaration" class="text-sm">
                                <strong>Compliance Verklaring:</strong> Ik verklaar dat alle bovenstaande documenten en mitigatiemaatregelen correct zijn geïmplementeerd en dat de vlucht zal worden uitgevoerd conform de SORA <?= htmlspecialchars($soraVersion) ?> richtlijnen.
                            </label>
                        </div>
                        <div class="flex items-start">
                            <input type="checkbox" id="risk_acceptance" name="declarations[]" value="risk"
                                class="mr-3 mt-1 text-primary focus:ring-primary" required>
                            <label for="risk_acceptance" class="text-sm">
                                <strong>Risico Acceptatie:</strong> Ik accepteer de geidentificeerde risico's (SAIL <?= $sailLevel ?>) en neem volledige verantwoordelijkheid voor de veilige uitvoering van deze droneoperatie.
                            </label>
                        </div>
                        <div class="flex items-start">
                            <input type="checkbox" id="regulatory_compliance" name="declarations[]" value="regulatory"
                                class="mr-3 mt-1 text-primary focus:ring-primary" required>
                            <label for="regulatory_compliance" class="text-sm">
                                <strong>Regelgeving Naleving:</strong> Ik bevestig dat deze operatie voldoet aan alle van toepassing zijnde Nederlandse en Europese drone regelgeving.
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Navigatie knoppen -->
                <div class="flex justify-between items-center p-6 bg-white card-shadow rounded-xl">
                    <a href="step2.php" class="text-gray-500 hover:text-gray-700 flex items-center text-sm font-medium">
                        <i class="fas fa-arrow-left mr-2"></i> Vorige stap
                    </a>
                    <button type="submit" id="submitBtn" disabled
                        class="bg-gray-400 text-white px-8 py-3 rounded-lg font-medium transition-colors flex items-center">
                        Voltooien & Indienen
                        <i class="fas fa-arrow-right ml-2"></i>
                    </button>
                </div>
            </form>
        </div>
    </main>
    <footer class="py-6 border-t border-borderColor">
        <div class="container mx-auto px-4 text-center">
            <p class="text-gray-600 text-sm">© 2025 DroneFlightPlanner. Alle rechten voorbehouden.</p>
        </div>
    </footer>
    <!-- ==================== JavaScript voor formulier & progress ==================== -->
    <script>
        let uploadedFiles = 0;
        let totalRequiredFiles = <?= array_sum(array_map('count', $requiredDocs)) ?>;
        let totalMitigations = <?= array_sum(array_map('count', $requiredMitigations)) ?>;
        let checkedMitigations = 0;

        function handleFileUpload(input, docKey) {
            const file = input.files[0];
            const statusDiv = document.getElementById('file_status_' + docKey);
            const hiddenInput = input.parentElement.parentElement.querySelector('input[type="hidden"]');
            const card = input.closest('.requirement-card');
            if (file) {
                statusDiv.innerHTML = `<i class="fas fa-check-circle text-green-500 mr-1"></i>${file.name}`;
                statusDiv.classList.add('text-green-600');
                hiddenInput.disabled = false;
                card.classList.add('completed');
                uploadedFiles++;
            } else {
                statusDiv.innerHTML = '';
                statusDiv.classList.remove('text-green-600');
                hiddenInput.disabled = true;
                card.classList.remove('completed');
                uploadedFiles--;
            }
            updateProgress();
        }

        function updateProgress() {
            // Aantal aangevinkte mitigaties
            checkedMitigations = document.querySelectorAll('input[name="mitigations[]"]:checked').length;

            // Aantal aangevinkte verklaringen
            const checkedDeclarations = document.querySelectorAll('input[name="declarations[]"]:checked').length;
            const totalDeclarations = document.querySelectorAll('input[name="declarations[]"]').length;

            // Progressie in procenten
            const fileProgress = (uploadedFiles / totalRequiredFiles) * 40;
            const mitigationProgress = (checkedMitigations / totalMitigations) * 40;
            const declarationProgress = (checkedDeclarations / totalDeclarations) * 20;
            const overallProgress = Math.round(fileProgress + mitigationProgress + declarationProgress);

            document.getElementById('overallProgress').style.width = overallProgress + '%';

            // Status tekst
            let statusText = `${overallProgress}% voltooid - `;
            if (overallProgress < 30) {
                statusText += 'Upload meer documenten en bevestig mitigatiemaatregelen';
            } else if (overallProgress < 70) {
                statusText += 'Goed bezig! Voltooi de resterende eisen';
            } else if (overallProgress < 100) {
                statusText += 'Bijna klaar! Controleer de verklaringen';
            } else {
                statusText += 'Alle eisen voltooid - klaar om in te dienen';
            }
            document.getElementById('progressText').textContent = statusText;

            // Zet de submit-knop aan/uit
            const submitBtn = document.getElementById('submitBtn');
            if (overallProgress === 100) {
                submitBtn.disabled = false;
                submitBtn.classList.remove('bg-gray-400');
                submitBtn.classList.add('bg-primary', 'hover:bg-blue-700');
            } else {
                submitBtn.disabled = true;
                submitBtn.classList.add('bg-gray-400');
                submitBtn.classList.remove('bg-primary', 'hover:bg-blue-700');
            }
        }

        function toggleOSODetails(type) {
            // TODO: Uitbreiden om volledige OSO-lijst te tonen
            alert(`Functie om alle ${type} OSO eisen te tonen wordt nog geïmplementeerd.`);
        }

        // Drag & drop bestand upload
        document.querySelectorAll('.upload-zone').forEach(zone => {
            zone.addEventListener('dragover', function(e) {
                e.preventDefault();
                this.classList.add('dragover');
            });
            zone.addEventListener('dragleave', function(e) {
                e.preventDefault();
                this.classList.remove('dragover');
            });
            zone.addEventListener('drop', function(e) {
                e.preventDefault();
                this.classList.remove('dragover');
                const files = e.dataTransfer.files;
                if (files.length > 0) {
                    const input = this.querySelector('input[type="file"]');
                    input.files = files;
                    const event = new Event('change', {
                        bubbles: true
                    });
                    input.dispatchEvent(event);
                }
            });
        });

        // Valideer voortgang vóór indienen
        document.getElementById('complianceForm').addEventListener('submit', function(e) {
            const progress = parseInt(document.getElementById('overallProgress').style.width);
            if (progress < 100) {
                e.preventDefault();
                alert('Voltooi alle vereiste documenten, mitigatiemaatregelen en verklaringen voordat u kunt indienen.');
                return false;
            }
            // Bevestiging popup
            const confirmed = confirm(
                `U staat op het punt om uw vluchtplanning in te dienen met de volgende kenmerken:\n\n` +
                `• SAIL Niveau: ${<?= $sailLevel ?>}\n` +
                `• Risico Classificatie: <?= $riskLabel ?>\n` +
                `• Geüploade Documenten: ${uploadedFiles}/${totalRequiredFiles}\n` +
                `• Bevestigde Mitigaties: ${checkedMitigations}/${totalMitigations}\n\n` +
                `Weet u zeker dat u wilt doorgaan?`
            );
            if (!confirmed) {
                e.preventDefault();
                return false;
            }
        });

        // Start progressbar direct na laden
        document.addEventListener('DOMContentLoaded', function() {
            updateProgress();
            document.querySelectorAll('input[type="checkbox"]').forEach(checkbox => {
                checkbox.addEventListener('change', updateProgress);
            });
        });

        // Optioneel: autosave in sessionStorage
        setInterval(function() {
            const formData = new FormData(document.getElementById('complianceForm'));
            const progress = {
                uploadedFiles: uploadedFiles,
                checkedMitigations: checkedMitigations,
                timestamp: new Date().toISOString()
            };
            sessionStorage.setItem('step3_progress', JSON.stringify(progress));
        }, 30000);

        document.addEventListener('DOMContentLoaded', function() {
            const savedProgress = sessionStorage.getItem('step3_progress');
            if (savedProgress) {
                const progress = JSON.parse(savedProgress);
                console.log('Loaded saved progress:', progress);
            }
        });
    </script>
</body>

</html>