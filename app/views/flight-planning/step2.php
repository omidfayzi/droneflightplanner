<?php
// /var/www/public/frontend/pages/flight-planning/step2.php
// Vluchtplanning Stap 2 - SORA Vragenlijst & Calculatie
// Werkt nu met gemockt sora_analysis_id als stap 1 die ID niet kan aanmaken via API

session_start();
require_once __DIR__ . '/../../../config/config.php';

// Zorg dat SORA_API_URL gedefinieerd is in config/config.php
if (!defined('SORA_API_URL')) {
    define('SORA_API_URL', 'https://api.dronesora.holdingthedrones.com/');
}
$soraApiBaseUrl = SORA_API_URL;

// Haal sessievariabelen op. Deze MOETEN nu correct zijn ingesteld door step1.php of door je dashboard/login.
$soraAnalysisId = $_SESSION['sora_analysis_id'] ?? null;
$soraVersion = $_SESSION['sora_version'] ?? null;
$userId = $_SESSION['user']['id'] ?? null; // user ID uit je algemene login/user beheer

// Cruciale check: Zijn de benodigde sessievariabelen aanwezig?
if (!$soraAnalysisId || !$soraVersion || !$userId) {
    error_log("Error: Essentiële sessievariabelen ontbreken voor SORA Step 2. Redirecting.");
    $_SESSION['form_error'] = "Sessiegegevens voor SORA-analyse ontbreken of zijn ongeldig. Start een nieuwe vlucht of selecteer een bestaande.";
    header("Location: step1.php"); // Terug naar step1.php om probleem te initialiseren
    exit;
}

// --- GECENTRALISEERDE API HULPFUNCTIES (zelfde als in step1.php, dus in real-project een gedeelde functions.php) ---
/**
 * Roept een externe API aan.
 * @param string $url De volledige URL van de endpoint.
 * @param array $payload Optionele data als JSON body.
 * @param string $method HTTP methode ('GET', 'POST', 'PUT').
 * @return array De gedecodeerde JSON response of een fout array.
 */
function callExternalApi(string $url, array $payload = [], string $method = 'GET'): array
{
    $ch = curl_init($url);
    $options = [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => ['Accept: application/json'],
        CURLOPT_TIMEOUT => 30, // Ruimere timeout
        CURLOPT_VERBOSE => true, // Zeer nuttig voor debugging: toont cURL headers/payload
        CURLOPT_STDERR => fopen('php://stderr', 'w') // Stuurt verbose info naar PHP error log
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

    if ($response === false) {
        $error = curl_error($ch);
        error_log("CURL_CONNECTION_ERROR ($method to $url): $error - Payload: " . json_encode($payload));
        return ['error' => "cURL verbinding mislukt: $error", 'http_code' => 0];
    }

    $decodedResponse = json_decode($response, true);

    // Logging van de API response, zeer uitgebreid
    error_log("API_CALL_LOG: URL: $url, Method: $method, HTTP Code: $httpCode, Payload: " . json_encode($payload) . ", Raw Response: " . $response);

    if ($httpCode >= 400) {
        $errorMsg = $decodedResponse['error'] ?? $response ?: "Onbekende API fout (HTTP $httpCode)";
        return ['error' => "API Fout ($httpCode): " . $errorMsg, 'http_code' => $httpCode];
    }

    return is_array($decodedResponse) ? $decodedResponse : ['success' => true, 'http_code' => $httpCode, 'raw_response' => $response];
}

// --- AFHANDELING VAN AJAX POST REQUESTS (Opslaan antwoorden en berekening triggeren) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
    $inputJSON = file_get_contents('php://input');
    $requestData = json_decode($inputJSON, true);
    $answersFromFrontend = $requestData['answers'] ?? [];

    $takeAnswersPayload = [];
    foreach ($answersFromFrontend as $answer) {
        $takeAnswersPayload[] = [
            'user_id' => $userId,
            'take_analysis_id' => $soraAnalysisId,
            'question_id' => $answer['question_id'],
            'content' => $answer['content'],
        ];
    }
    // Gebruik de `callExternalApi` functie om te communiceren met SORA API
    $saveAnswersResult = callExternalApi($soraApiBaseUrl . 'take_answers', $takeAnswersPayload, 'PUT');

    if (isset($saveAnswersResult['error'])) {
        http_response_code(500);
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'message' => 'Fout bij opslaan antwoorden bij SORA API.', 'details' => $saveAnswersResult['error']]);
        exit;
    }

    $calculateEndpoint = ($soraVersion === '2.0') ? 'calculate2_0' : 'calculate2_5';
    $calculationPayload = ['take_analysis_id' => $soraAnalysisId];
    // Gebruik de `callExternalApi` functie om te communiceren met SORA API
    $calculationResult = callExternalApi($soraApiBaseUrl . $calculateEndpoint, $calculationPayload, 'POST');

    if (isset($calculationResult['error'])) {
        http_response_code(500);
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'message' => 'Fout bij uitvoeren SORA berekening.', 'details' => $calculationResult['error']]);
        exit;
    }

    http_response_code(200);
    header('Content-Type: application/json');
    echo json_encode(['status' => 'success', 'calculation' => $calculationResult]);
    exit;
}


// --- AFHANDELING VAN NORMALE GET REQUESTS (Pagina Laden) ---
$steps = callExternalApi($soraApiBaseUrl . 'sora_steps', [], 'GET');
$sections = callExternalApi($soraApiBaseUrl . 'sora_sections', [], 'GET');
$allQuestions = callExternalApi($soraApiBaseUrl . 'sora_questions', [], 'GET');
$answers = callExternalApi($soraApiBaseUrl . 'sora_answers', [], 'GET');

$currentStepGUIOrder = 3; // Stap 2 in je PHP is GUIorder 3 in `sora_steps.json`
$filteredSteps = array_filter($steps, fn($s) => ($s['GUIorder'] ?? 0) === $currentStepGUIOrder && ($s['active'] ?? false));
$filteredSections = array_filter($sections, fn($s) => ($s['active'] ?? false));
$questionsByVersion = array_filter($allQuestions, fn($q) => ($q['sora_version'] === $soraVersion && ($q['active'] ?? false)));

$existingAnswers = [];
if ($soraAnalysisId) {
    $takeAnswers = callExternalApi($soraApiBaseUrl . 'take_answers', ['take_analysis_id' => $soraAnalysisId], 'GET');
    // GET takes params as query string, not payload. Correct call:
    $takeAnswers = callExternalApi($soraApiBaseUrl . 'take_answers?take_analysis_id=' . $soraAnalysisId, [], 'GET');
    foreach ($takeAnswers as $answer) {
        if (is_array($answer) && isset($answer['question_id'])) { // Voorkom non-array/lege items
            $existingAnswers[$answer['question_id']] = $answer['content'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="nl">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Stap 2: Risicoanalyse (SORA Vragen)</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
    <style>
        body {
            background: #f8f9fa;
            padding: 2rem;
        }

        section {
            background: #fff;
            padding: 1rem 1.5rem;
            margin-bottom: 2rem;
            border-radius: 8px;
            box-shadow: 0 1px 6px rgba(0, 0, 0, .1);
        }

        .question-block {
            margin-bottom: 1.25rem;
        }

        .info-icon {
            cursor: pointer;
            color: blue;
            margin-left: 5px;
        }

        .tooltip-text {
            background: #fefee3;
            border: 1px solid #ddd;
            padding: 8px;
            border-radius: 5px;
            margin-top: 5px;
            display: none;
        }
    </style>
</head>

<body>
    <div class="container">
        <h1>Stap 2: Risicoanalyse (SORA Vragen) - Versie: <?= htmlspecialchars($soraVersion) ?></h1>

        <input type="hidden" id="soraAnalysisId" value="<?= htmlspecialchars($soraAnalysisId) ?>">
        <input type="hidden" id="soraVersion" value="<?= htmlspecialchars($soraVersion) ?>">
        <input type="hidden" id="userId" value="<?= htmlspecialchars($userId) ?>">

        <form id="soraForm">
            <?php
            foreach ($filteredSteps as $step):
            ?>
                <section>
                    <h2><?= htmlspecialchars($step['step']) ?></h2>
                    <?php if (!empty($step['explanation'])): ?>
                        <p class="text-muted"><?= nl2br(htmlspecialchars($step['explanation'])) ?></p>
                    <?php endif; ?>

                    <?php
                    $currentStepSections = array_filter($filteredSections, fn($sec) => ($sec['step_id'] === $step['id']));
                    usort($currentStepSections, fn($a, $b) => ($a['GUIorder'] ?? 0) <=> ($b['GUIorder'] ?? 0));

                    foreach ($currentStepSections as $section):
                    ?>
                        <div>
                            <h4><?= htmlspecialchars($section['section']) ?>
                                <span class="info-icon" data-toggle-id="section_<?= $section['id'] ?>">🛈</span>
                            </h4>
                            <?php if (!empty($section['explaination'])): ?>
                                <p class="tooltip-text" id="section_<?= $section['id'] ?>"><?= nl2br(htmlspecialchars($section['explaination'])) ?></p>
                            <?php endif; ?>

                            <?php
                            $sectQuestions = array_filter($questionsByVersion, fn($q) => ($q['section_id'] === $section['id']));
                            usort($sectQuestions, fn($a, $b) => ($a['order_question'] ?? ($a['GUIorder'] ?? 0)) <=> ($b['order_question'] ?? ($b['GUIorder'] ?? 0)));

                            foreach ($sectQuestions as $question):
                                $questionId = $question['id'];
                                $currentAnswerContent = $existingAnswers[$questionId] ?? '';
                                $questionOptions = array_filter($answers, fn($ans) => ($ans['question_id'] === $questionId && ($ans['active'] ?? false)));
                                usort($questionOptions, fn($a, $b) => ($a['GUIorder'] ?? 0) <=> ($b['GUIorder'] ?? 0));
                            ?>
                                <div class="question-block">
                                    <label><strong><?= htmlspecialchars($question['content']) ?></strong>
                                        <span class="info-icon" data-toggle-id="question_<?= $question['id'] ?>">🛈</span>
                                    </label>
                                    <?php if (!empty($question['explaination'])): ?>
                                        <p class="tooltip-text" id="question_<?= $question['id'] ?>"><?= nl2br(htmlspecialchars($question['explaination'])) ?></p>
                                    <?php endif; ?>

                                    <?php if ($question['type'] === 'open' || $question['type'] === 'open5'):
                                        $rows = ($question['type'] === 'open5') ? '5' : '2'; ?>
                                        <textarea class="form-control" name="q_<?= $questionId ?>" rows="<?= $rows ?>" placeholder="Typ hier uw antwoord..."><?= htmlspecialchars($currentAnswerContent) ?></textarea>

                                    <?php elseif ($question['type'] === 'openM'): ?>
                                        <div class="input-group mb-3">
                                            <input type="text" class="form-control" name="q_<?= $questionId ?>" placeholder="Waarde" value="<?= htmlspecialchars($currentAnswerContent) ?>" />
                                            <span class="input-group-text"><?= htmlspecialchars($question['metric'] ?? '') ?></span>
                                        </div>

                                    <?php elseif ($question['type'] === 'y/n'): ?>
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="q_<?= $questionId ?>" id="q_<?= $questionId ?>_yes" value="yes" <?= ($currentAnswerContent === 'yes') ? 'checked' : '' ?> />
                                            <label class="form-check-label" for="q_<?= $questionId ?>_yes">Ja</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="q_<?= $questionId ?>" id="q_<?= $questionId ?>_no" value="no" <?= ($currentAnswerContent === 'no') ? 'checked' : '' ?> />
                                            <label class="form-check-label" for="q_<?= $questionId ?>_no">Nee</label>
                                        </div>

                                        <?php elseif ($question['type'] === 'mc'):
                                        foreach ($questionOptions as $opt): ?>
                                            <div class="form-check">
                                                <input class="form-check-input" type="radio" name="q_<?= $questionId ?>" id="q_<?= $questionId ?>_opt_<?= $opt['id'] ?>" value="<?= htmlspecialchars($opt['content']) ?>" <?= ($currentAnswerContent === $opt['content']) ? 'checked' : '' ?> />
                                                <label class="form-check-label" for="q_<?= $questionId ?>_opt_<?= $opt['id'] ?>"><?= htmlspecialchars($opt['content']) ?></label>
                                            </div>
                                        <?php endforeach; ?>

                                    <?php elseif ($question['type'] === 'location'): ?>
                                        <p class="text-muted small"><em>Dit veld is voor map-locatie in React app, hier als tekst.</em></p>
                                        <input type="text" class="form-control" name="q_<?= $questionId ?>" placeholder="Locatie (Map ID)" value="<?= htmlspecialchars($currentAnswerContent) ?>" />
                                        <button type="button" class="btn btn-info btn-sm mt-1" onclick="alert('Map wijzigen functie nog te implementeren!')">Wijzig map</button>

                                    <?php else: ?>
                                        <p class="text-danger">Onbekend vraagtype: <strong><?= htmlspecialchars($question['type']) ?></strong>. Getoond als tekstveld.</p>
                                        <textarea class="form-control" name="q_<?= $questionId ?>" rows="2" placeholder="Onbekend type antwoord..."><?= htmlspecialchars($currentAnswerContent) ?></textarea>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endforeach; ?>
                </section>
            <?php endforeach; ?>

            <button type="submit" class="btn btn-primary mt-4">SORA Berekening uitvoeren</button>
        </form>

        <div id="resultArea" class="mt-4 p-3 bg-light border rounded" style="display:none;">
            <h3>Resultaat Risicoanalyse</h3>
            <pre id="resultJson" class="p-3 bg-white border rounded" style="max-height:400px; overflow:auto;"></pre>
        </div>
    </div>

    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script>
        const form = document.getElementById("soraForm");
        const resultArea = document.getElementById("resultArea");
        const resultJson = document.getElementById("resultJson");
        const soraAnalysisId = document.getElementById("soraAnalysisId").value;
        const soraVersion = document.getElementById("soraVersion").value;
        const userId = document.getElementById("userId").value;

        form.addEventListener("submit", async function(e) {
            e.preventDefault();

            resultArea.style.display = "block";
            resultJson.textContent = "Laden van risicoanalyse resultaat...";
            resultJson.style.backgroundColor = "#fff";

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

                if (!response.ok) {
                    const errorDetails = await response.json();
                    throw new Error(errorDetails.message || `HTTP Status ${response.status} met onbekende details.`);
                }

                const data = await response.json();

                if (data.status === 'success') {
                    resultJson.textContent = JSON.stringify(data.calculation, null, 2);
                    console.log("SORA Berekening Resultaat:", data.calculation);
                    resultJson.style.backgroundColor = "#e6ffe6";
                } else {
                    resultJson.textContent = `Fout: ${data.message}\nDetails: ${data.details}`;
                    resultJson.style.backgroundColor = "#ffecec";
                    console.error("Fout bij SORA Berekening:", data.message, data.details);
                }

            } catch (err) {
                resultJson.textContent = `AJAX Fout: ${err.message}`;
                resultJson.style.backgroundColor = "#ffecec";
                console.error("Algemene AJAX Fetch Fout:", err);
            }
        });

        document.querySelectorAll('.info-icon').forEach(icon => {
            icon.addEventListener('click', function() {
                const tooltipId = this.dataset.toggleId;
                const tooltipElement = document.getElementById(tooltipId);
                if (tooltipElement) {
                    tooltipElement.style.display = tooltipElement.style.display === 'none' ? 'block' : 'none';
                }
            });
        });
    </script>
</body>

</html>