<?php
// =================================================================
// dashboard.php: Het hoofdscherm van de applicatie
// =================================================================

// --- STAP 1: INITIALISATIE & VEILIGHEIDSCONTROLE ---

// Zorg ervoor dat de sessie is gestart, zodat we de ingelogde gebruiker kunnen controleren.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Laad de centrale configuratie en algemene functies.
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../functions.php';

// Beveiligingscheck: als er geen gebruiker is ingelogd in de sessie,
// stuur de bezoeker dan terug naar de landingspagina.
if (!isset($_SESSION['user']['id'])) {
    $_SESSION['form_error'] = "U moet ingelogd zijn om het dashboard te bekijken.";
    header("Location: landing-page.php");
    exit; // Stop de uitvoering van het script direct na een redirect.
}

// =================================================================
// API COMMUNICATIE FUNCTIE
// =================================================================
// OPMERKING: Deze functie is specifiek voor deze pagina om data op te halen.
// In een grotere opzet (MVC) zou deze functie in een 'Model' of 'ApiHandler' class staan.

/**
 * Maakt een API-call met cURL naar de backend.
 *
 * @param string $url De volledige URL van het API-endpoint.
 * @param string $method De HTTP-methode (standaard 'GET').
 * @param array $payload De data die eventueel meegestuurd wordt (voor POST).
 * @return array De response van de API als een PHP-array, of een array met een 'error' key.
 */
function callMainApi(string $url, string $method = 'GET', array $payload = []): array
{
    // Initialiseer een cURL-sessie. cURL is een standaard PHP-tool om met servers te praten.
    $ch = curl_init($url);

    // Stel de opties in voor de cURL-request.
    $options = [
        CURLOPT_RETURNTRANSFER => true, // Zorg dat we de response als string terugkrijgen.
        CURLOPT_HTTPHEADER     => ['Accept: application/json'], // Geef aan dat we JSON verwachten.
        CURLOPT_TIMEOUT        => 20, // Stel een maximale wachttijd in van 20 seconden.
    ];

    // Voeg extra opties toe als het een POST, PUT, of DELETE request is.
    if ($method !== 'GET') {
        $options[CURLOPT_HTTPHEADER][] = 'Content-Type: application/json';
        $options[CURLOPT_POSTFIELDS] = json_encode($payload);
        if ($method === 'PUT') $options[CURLOPT_CUSTOMREQUEST] = 'PUT';
        if ($method === 'DELETE') $options[CURLOPT_CUSTOMREQUEST] = 'DELETE';
        if ($method === 'POST') $options[CURLOPT_POST] = true;
    }

    // Voeg de authenticatie-token toe als die bestaat (voorbereiding voor toekomstige beveiliging).
    if (isset($_SESSION['user']['auth_token'])) {
        $options[CURLOPT_HTTPHEADER][] = 'Authorization: Bearer ' . $_SESSION['user']['auth_token'];
    }

    curl_setopt_array($ch, $options);

    // Voer de API-call uit en sla de response en HTTP-statuscode op.
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    // Controleer op fouten. Als de HTTP-code 400 of hoger is, is er iets misgegaan.
    if ($httpCode >= 400) {
        $decodedError = json_decode($response, true);
        return ['error' => $decodedError['message'] ?? "API-fout (HTTP-code: $httpCode)"];
    }

    // Geef de response terug als een PHP-array. Als het geen geldige JSON is, geef een lege array.
    return json_decode($response, true) ?: [];
}

// --- STAP 2: DATA OPHALEN ---

// Haal de ID's op die we nodig hebben om de juiste data te filteren.
$selectedOrgId = $_SESSION['selected_organisation_id'] ?? null;
$loggedInUserId = $_SESSION['user']['id'] ?? null;

// Bouw de API-URL op basis van de selectie van de gebruiker.
$flightsApiUrl = API_BASE_URL . '/vluchten';
$queryParams = [];
if ($selectedOrgId) {
    $queryParams['organisatieId'] = $selectedOrgId;
} else {
    $queryParams['pilootId'] = $loggedInUserId;
}
$flightsApiUrlWithFilter = $flightsApiUrl . '?' . http_build_query($queryParams);

// Roep de API-functie aan om de vluchten op te halen.
$flightsResponse = callMainApi($flightsApiUrlWithFilter);

// Verwerk de response van de API.
$recentFlights = [];
$apiError = null;
if (isset($flightsResponse['error'])) {
    $apiError = $flightsResponse['error'];
} elseif (is_array($flightsResponse)) {
    $recentFlights = $flightsResponse;
}

// --- STAP 3: DATA VERWERKEN EN VOORBEREIDEN ---

// Sorteer de vluchten op datum, met de nieuwste bovenaan.
usort($recentFlights, fn($a, $b) => strtotime($b['startDatumTijd'] ?? '') <=> strtotime($a['startDatumTijd'] ?? ''));

// Bereken de statistieken voor de kaarten bovenaan de pagina.
$stats = [
    'total_flights' => count($recentFlights),
    'active_flights' => count(array_filter($recentFlights, fn($f) => ($f['status'] ?? '') === 'Lopend')),
    'pending_approval' => count(array_filter($recentFlights, fn($f) => ($f['status'] ?? '') === 'Gepland'))
];

// Verzamel unieke waarden voor de filter-dropdowns.
$uniqueStatuses = array_unique(array_column($recentFlights, 'status'));
$uniquePilots = array_unique(array_column($recentFlights, 'pilootNaam'));
sort($uniqueStatuses);
sort($uniquePilots);

// --- STAP 4: PAGINA-INSTELLINGEN & HTML OUTPUT ---

$headTitle = "Dashboard";

// Start de output buffer. Alle HTML hierna wordt opgevangen in een variabele.
ob_start();
?>

<!-- Link naar het aparte CSS-bestand voor deze pagina -->
<link rel="stylesheet" href="/app/assets/styles/dashboard-styling.css">

<div class="h-full bg-gray-100 shadow-md rounded-tl-xl w-full flex flex-col">
    <div class="p-6 overflow-y-auto">

        <?php if ($apiError): ?>
            <div class="alert alert-danger mb-4" role="alert">
                Fout bij het laden van de vluchten: <?= htmlspecialchars($apiError) ?>
            </div>
        <?php endif; ?>

        <!-- KPI Grid: de drie kaarten met statistieken -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <div class="bg-white p-6 rounded-xl shadow hover:shadow-lg transition">
                <div class="flex justify-between items-center">
                    <div>
                        <p class="text-sm text-gray-500 mb-1">Actieve Vluchten</p>
                        <p class="text-3xl font-bold text-gray-800"><?= htmlspecialchars($stats['active_flights']) ?></p>
                    </div>
                    <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center">
                        <i class="fa-solid fa-rocket text-blue-700"></i>
                    </div>
                </div>
            </div>
            <div class="bg-white p-6 rounded-xl shadow hover:shadow-lg transition">
                <div class="flex justify-between items-center">
                    <div>
                        <p class="text-sm text-gray-500 mb-1">Geplande Vluchten</p>
                        <p class="text-3xl font-bold text-gray-800"><?= htmlspecialchars($stats['pending_approval']) ?></p>
                    </div>
                    <div class="w-12 h-12 bg-yellow-100 rounded-full flex items-center justify-center">
                        <i class="fa-solid fa-clock text-yellow-700"></i>
                    </div>
                </div>
            </div>
            <div class="bg-white p-6 rounded-xl shadow hover:shadow-lg transition">
                <div class="flex justify-between items-center">
                    <div>
                        <p class="text-sm text-gray-500 mb-1">Totaal Vluchten</p>
                        <p class="text-3xl font-bold text-gray-800"><?= htmlspecialchars($stats['total_flights']) ?></p>
                    </div>
                    <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center">
                        <i class="fa-solid fa-chart-line text-green-700"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filterbalk -->
        <div class="filter-bar">
            <div class="filter-group">
                <span class="filter-label">Status:</span>
                <select id="statusFilter" class="filter-select">
                    <option value="">Alle statussen</option>
                    <?php foreach ($uniqueStatuses as $status): ?>
                        <option value="<?= htmlspecialchars(strtolower($status)) ?>"><?= htmlspecialchars(ucfirst($status)) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="filter-group">
                <span class="filter-label">Piloot:</span>
                <select id="pilotFilter" class="filter-select">
                    <option value="">Alle piloten</option>
                    <?php foreach ($uniquePilots as $pilot): ?>
                        <option value="<?= htmlspecialchars($pilot) ?>"><?= htmlspecialchars($pilot) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="filter-group flex-grow">
                <input id="searchInput" type="text" placeholder="Zoek vlucht..." class="filter-search">
            </div>
        </div>

        <!-- Tabel met Recente Vluchten -->
        <div class="bg-white rounded-xl shadow overflow-hidden">
            <div class="p-6 border-b border-gray-200 flex justify-between items-center">
                <h3 class="text-xl font-semibold text-gray-800">Recente Operaties</h3>
                <a href="/app/views/flight-planning/step1.php" class="flex items-center text-blue-600 hover:text-blue-800 transition">
                    <i class="fa-solid fa-plus mr-2"></i> Nieuwe Vlucht
                </a>
            </div>
            <div class="overflow-x-auto data_grid">
                <table id="flightsTable" class="w-full">
                    <thead class="bg-gray-100 text-sm">
                        <tr>
                            <th class="p-4 text-left text-gray-600">ID</th>
                            <th class="p-4 text-left text-gray-600">Vluchtnaam</th>
                            <th class="p-4 text-left text-gray-600">Status</th>
                            <th class="p-4 text-left text-gray-600">Acties</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 text-sm">
                        <?php if (empty($recentFlights)): ?>
                            <tr>
                                <td colspan="4" class="p-4 text-center text-gray-500">Geen vluchten gevonden</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($recentFlights as $flight): ?>
                                <?php
                                $status = $flight['status'] ?? 'Onbekend';
                                $statusClass = 'bg-gray-100 text-gray-800';
                                switch ($status) {
                                    case 'Gepland':
                                        $statusClass = 'bg-blue-100 text-blue-800';
                                        break;
                                    case 'Lopend':
                                        $statusClass = 'bg-yellow-100 text-yellow-800';
                                        break;
                                    case 'Afgerond':
                                        $statusClass = 'bg-green-100 text-green-800';
                                        break;
                                    case 'Geannuleerd':
                                        $statusClass = 'bg-red-100 text-red-800';
                                        break;
                                }
                                ?>
                                <tr class="hover:bg-gray-50 transition"
                                    data-status="<?= htmlspecialchars(strtolower($status)) ?>"
                                    data-pilot="<?= htmlspecialchars($flight['pilootNaam'] ?? '') ?>">
                                    <td class="p-4 font-medium text-gray-800"><?= htmlspecialchars($flight['id'] ?? 'N/A') ?></td>
                                    <td class="p-4 text-gray-600"><?= htmlspecialchars($flight['vluchtNaam'] ?? 'Geen naam') ?></td>
                                    <td class="p-4">
                                        <span class="<?= $statusClass ?> px-3 py-1 rounded-full text-sm font-medium"><?= htmlspecialchars($status) ?></span>
                                    </td>
                                    <td class="p-4 text-right">
                                        <button onclick='openFlightDetailModal(<?= json_encode($flight) ?>)' class='text-gray-600 hover:text-gray-800 transition'>
                                            <i class='fa-solid fa-circle-info'></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal voor Vluchtdetails -->
<div id="flightDetailModal" class="modal-overlay">
    <div class="modal-content">
        <button class="modal-close-btn" aria-label="Sluit details" onclick="closeFlightDetailModal()">&times;</button>
        <h3 id="flightDetailTitle" class="flex items-center gap-2">
            <i class="fa-regular fa-file-lines text-blue-500"></i>
            Vlucht Details
        </h3>
        <div id="flightDetailContent" class="detail-grid"></div>
    </div>
</div>

<?php
// Sla de HTML op in de $bodyContent variabele en laad de hoofdtemplate.
$bodyContent = ob_get_clean();
require_once __DIR__ . '/../components/header.php';
require_once __DIR__ . '/layouts/template.php';
?>

<!-- JavaScript voor de interactiviteit van de pagina -->
<script>
    const flightDetailModal = document.getElementById('flightDetailModal');

    function openFlightDetailModal(flightData) {
        const modalContent = document.getElementById('flightDetailContent');
        if (!modalContent || !flightData) return;

        modalContent.innerHTML = '';

        const fieldsToShow = {
            'id': 'Vlucht ID',
            'vluchtNaam': 'Vluchtnaam',
            'startDatumTijd': 'Startdatum/tijd',
            'locatie': 'Locatie',
            'pilootNaam': 'Piloot',
            'droneNaam': 'Drone',
            'status': 'Status',
        };

        for (const [key, label] of Object.entries(fieldsToShow)) {
            let value = flightData[key] ?? '-';
            modalContent.innerHTML += `
                <div class='detail-group'>
                    <div class='detail-label'>${label}</div>
                    <div class='detail-value'>${value}</div>
                </div>`;
        }

        if (flightDetailModal) {
            flightDetailModal.classList.add('active');
            document.body.style.overflow = 'hidden';
        }
    }

    function closeFlightDetailModal() {
        if (flightDetailModal) {
            flightDetailModal.classList.remove('active');
            document.body.style.overflow = '';
        }
    }

    if (flightDetailModal) {
        flightDetailModal.addEventListener('click', (event) => {
            if (event.target === flightDetailModal) closeFlightDetailModal();
        });
    }

    function filterFlights() {
        const searchTerm = document.getElementById('searchInput').value.toLowerCase();
        const statusFilter = document.getElementById('statusFilter').value.toLowerCase();
        const pilotFilter = document.getElementById('pilotFilter').value.toLowerCase();
        const rows = document.querySelectorAll('#flightsTable tbody tr');

        rows.forEach(row => {
            const status = row.dataset.status || '';
            const pilot = (row.dataset.pilot || '').toLowerCase();
            const rowText = row.textContent.toLowerCase();

            const matchesSearch = rowText.includes(searchTerm);
            const matchesStatus = statusFilter === '' || status === statusFilter;
            const matchesPilot = pilotFilter === '' || pilot.includes(pilotFilter);

            row.style.display = (matchesSearch && matchesStatus && matchesPilot) ? '' : 'none';
        });
    }

    document.addEventListener('DOMContentLoaded', () => {
        document.getElementById('searchInput').addEventListener('input', filterFlights);
        document.getElementById('statusFilter').addEventListener('change', filterFlights);
        document.getElementById('pilotFilter').addEventListener('change', filterFlights);
    });
</script>