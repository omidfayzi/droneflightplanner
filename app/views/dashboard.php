<?php
// FILE: /var/www/public/app/views/dashboard.php
// Dashboard-pagina voor het Drone Vluchtvoorbereidingssysteem - MET DYNAMISCHE DATA

session_start();
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../functions.php';

// Valideren van de gebruikersstatus
if (!isset($_SESSION['user']['id'])) {
    $_SESSION['form_error'] = "U moet ingelogd zijn om het dashboard te bekijken.";
    header("Location: landing-page.php");
    exit;
}

// Sessie-gegevens
$selectedOrgId = $_SESSION['selected_organisation_id'] ?? null;
$loggedInUserId = $_SESSION['user']['id'] ?? null;

// --- API URLs configureren ---
if (!defined('MAIN_API_URL')) {
    define('MAIN_API_URL', 'https://api2.droneflightplanner.nl');
}
$mainApiBaseUrl = MAIN_API_URL;
$flightsApiUrl = $mainApiBaseUrl . '/vluchten';

// API-hulpfunctie
function callMainApi(string $url, string $method = 'GET', array $payload = []): array
{
    $ch = curl_init($url);
    $options = [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => ['Accept: application/json'],
        CURLOPT_TIMEOUT => 20,
    ];

    if ($method !== 'GET') {
        $options[CURLOPT_HTTPHEADER][] = 'Content-Type: application/json';
        $options[CURLOPT_POSTFIELDS] = json_encode($payload);
        if ($method === 'PUT') $options[CURLOPT_CUSTOMREQUEST] = 'PUT';
        if ($method === 'DELETE') $options[CURLOPT_CUSTOMREQUEST] = 'DELETE';
        if ($method === 'POST') $options[CURLOPT_POST] = true;
    }

    if (isset($_SESSION['user']['auth_token'])) {
        $options[CURLOPT_HTTPHEADER][] = 'Authorization: Bearer ' . $_SESSION['user']['auth_token'];
    }

    curl_setopt_array($ch, $options);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode >= 400) {
        $decodedError = json_decode($response, true);
        return ['error' => $decodedError['message'] ?? "API-fout ($httpCode)"];
    }

    return json_decode($response, true) ?: [];
}

// --- Vluchten ophalen via API ---
$recentFlights = [];
$apiError = null;

// Bouw API-URL met filters
$queryParams = [];
if ($selectedOrgId) {
    $queryParams[] = 'organisatieId=' . $selectedOrgId;
} else {
    $queryParams[] = 'pilootId=' . $loggedInUserId;
}

$flightsApiUrlWithFilter = $flightsApiUrl . '?' . implode('&', $queryParams);
$flightsResponse = callMainApi($flightsApiUrlWithFilter, 'GET');

if (isset($flightsResponse['error'])) {
    $apiError = $flightsResponse['error'];
    error_log("Dashboard: Fout bij ophalen vluchten: " . $apiError);
} elseif (is_array($flightsResponse)) {
    $recentFlights = $flightsResponse;
}

// Sorteer vluchten op datum (nieuwste eerst)
usort($recentFlights, function ($a, $b) {
    return strtotime($b['startDatumTijd'] ?? '') <=> strtotime($a['startDatumTijd'] ?? '');
});

// Bereken statistieken
$stats = [
    'total_flights' => count($recentFlights),
    'active_flights' => count(array_filter($recentFlights, fn($f) => ($f['status'] ?? '') === 'Lopend')),
    'pending_approval' => count(array_filter($recentFlights, fn($f) => ($f['status'] ?? '') === 'Gepland'))
];

// TOEGEVOEGD: Verzamel unieke waarden voor filters (exact zoals in incidents.php)
$uniqueStatuses = [];
$uniquePilots = [];

foreach ($recentFlights as $flight) {
    if (!empty($flight['status'])) {
        $statusLower = strtolower($flight['status']);
        if (!in_array($statusLower, $uniqueStatuses)) {
            $uniqueStatuses[] = $statusLower;
        }
    }

    $pilot = $flight['pilootNaam'] ?? ($flight['pilootId'] ?? 'Onbekend');
    if (!in_array($pilot, $uniquePilots)) {
        $uniquePilots[] = $pilot;
    }
}
sort($uniqueStatuses);
sort($uniquePilots);

// Dashboard content
$bodyContent = "
    <div class='h-[83.5vh] bg-gray-100 shadow-md rounded-tl-xl w-13/15'>
        <div class='p-6 overflow-y-auto max-h-[calc(90vh-200px)]'>";

if ($apiError) {
    $bodyContent .= "
        <div class='alert alert-danger mb-4' role='alert'>
            Fout bij laden vluchten: " . htmlspecialchars($apiError) . "
        </div>";
}

$bodyContent .= "
            <!-- KPI Grid voor Vluchtstatistieken -->
            <div class='grid grid-cols-1 md:grid-cols-3 gap-6 mb-8'>
                <div class='bg-white p-6 rounded-xl shadow hover:shadow-lg transition'>
                    <div class='flex justify-between items-center'>
                        <div>
                            <p class='text-sm text-gray-500 mb-1'>Actieve Vluchten</p>
                            <p class='text-3xl font-bold text-gray-800'>" . htmlspecialchars($stats['active_flights']) . "</p>
                        </div>
                        <div class='w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center'>
                            <i class='fa-solid fa-rocket text-blue-700'></i>
                        </div>
                    </div>
                </div>
                <div class='bg-white p-6 rounded-xl shadow hover:shadow-lg transition'>
                    <div class='flex justify-between items-center'>
                        <div>
                            <p class='text-sm text-gray-500 mb-1'>Geplande Vluchten</p>
                            <p class='text-3xl font-bold text-gray-800'>" . htmlspecialchars($stats['pending_approval']) . "</p>
                        </div>
                        <div class='w-12 h-12 bg-yellow-100 rounded-full flex items-center justify-center'>
                            <i class='fa-solid fa-clock text-yellow-700'></i>
                        </div>
                    </div>
                </div>
                <div class='bg-white p-6 rounded-xl shadow hover:shadow-lg transition'>
                    <div class='flex justify-between items-center'>
                        <div>
                            <p class='text-sm text-gray-500 mb-1'>Totaal Vluchten</p>
                            <p class='text-3xl font-bold text-gray-800'>" . htmlspecialchars($stats['total_flights']) . "</p>
                        </div>
                        <div class='w-12 h-12 bg-green-100 rounded-full flex items-center justify-center'>
                            <i class='fa-solid fa-chart-line text-green-700'></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- TOEGEVOEGD: Filter Bar (exact zoals in incidents.php) -->
            <div class='px-6 pt-4'>
                <div class='filter-bar'>
                    <div class='filter-group'>
                        <span class='filter-label'>Status:</span>
                        <select id='statusFilter' class='filter-select'>
                            <option value=''>Alle statussen</option>";

foreach ($uniqueStatuses as $status) {
    $displayStatus = ucfirst($status);
    $bodyContent .= "<option value='" . htmlspecialchars($status) . "'>" . htmlspecialchars($displayStatus) . "</option>";
}

$bodyContent .= "
                        </select>
                    </div>
                    
                    <div class='filter-group'>
                        <span class='filter-label'>Piloot:</span>
                        <select id='pilotFilter' class='filter-select'>
                            <option value=''>Alle piloten</option>";

foreach ($uniquePilots as $pilot) {
    $bodyContent .= "<option value='" . htmlspecialchars($pilot) . "'>" . htmlspecialchars($pilot) . "</option>";
}

$bodyContent .= "
                        </select>
                    </div>
                    
                    <div class='filter-group flex-grow'>
                        <input id='searchInput' type='text' placeholder='Zoek vlucht...' class='filter-search'>
                    </div>
                </div>
            </div>

            <!-- Tabel met Recente Vluchten -->
            <div class='bg-white rounded-xl shadow overflow-hidden'>
                <div class='p-6 border-b border-gray-200 flex justify-between items-center'>
                    <h3 class='text-xl font-semibold text-gray-800'>Recente Operaties</h3>
                    <a href='/app/view/flight-planning/step1.php' class='flex items-center text-blue-600 hover:text-blue-800 transition'>
                        <i class='fa-solid fa-plus mr-2'></i> Nieuwe Vlucht
                    </a>
                </div>
                <div class='overflow-x-auto'>
                    <table id='flightsTable' class='w-full'>
                        <thead class='bg-gray-100 text-sm'>
                            <tr>
                                <th class='p-4 text-left text-gray-600'>ID</th>
                                <th class='p-4 text-left text-gray-600'>Vluchtnaam</th>
                                <th class='p-4 text-left text-gray-600'>Type</th>
                                <th class='p-4 text-left text-gray-600'>Datum/tijd</th>
                                <th class='p-4 text-left text-gray-600'>Locatie</th>
                                <th class='p-4 text-left text-gray-600'>Piloot</th>
                                <th class='p-4 text-left text-gray-600'>Drone</th>
                                <th class='p-4 text-left text-gray-600'>Status</th>
                                <th class='p-4 text-left text-gray-600'>Acties</th>
                            </tr>
                        </thead>
                        <tbody class='divide-y divide-gray-200 text-sm'>";

if (empty($recentFlights)) {
    $bodyContent .= "<tr><td colspan='9' class='p-4 text-center text-gray-500'>Geen vluchten gevonden</td></tr>";
} else {
    foreach ($recentFlights as $flight) {
        $flightId = $flight['id'] ?? 'N/A';
        $flightName = htmlspecialchars($flight['vluchtNaam'] ?? 'Geen naam');
        $flightType = htmlspecialchars($flight['typeNaam'] ?? ($flight['vluchtTypeId'] ?? 'N/A'));
        $pilotName = htmlspecialchars($flight['pilootNaam'] ?? ($flight['pilootId'] ?? 'N/A'));
        $droneName = htmlspecialchars($flight['droneNaam'] ?? ($flight['droneId'] ?? 'N/A'));
        $location = htmlspecialchars($flight['locatie'] ?? 'Onbekend');
        $statusLower = !empty($flight['status']) ? strtolower($flight['status']) : '';

        // Datum/tijd formatteren
        $formattedDateTime = 'Onbekend';
        if (!empty($flight['startDatumTijd'])) {
            try {
                $date = new DateTime($flight['startDatumTijd']);
                $formattedDateTime = $date->format('d-m-Y H:i');
            } catch (Exception $e) {
                $formattedDateTime = 'Ongeldige datum';
            }
        }

        // Status styling
        $status = $flight['status'] ?? 'Onbekend';
        $statusClass = 'bg-gray-100 text-gray-800'; // Default

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

        // TOEGEVOEGD: data-attributen voor filtering
        $bodyContent .= "
            <tr class='hover:bg-gray-50 transition' 
                data-status='" . htmlspecialchars($statusLower) . "'
                data-pilot='" . htmlspecialchars($pilotName) . "'>
                <td class='p-4 font-medium text-gray-800'>$flightId</td>
                <td class='p-4 text-gray-600'>$flightName</td>
                <td class='p-4 text-gray-600'>$flightType</td>
                <td class='p-4 text-gray-600'>$formattedDateTime</td>
                <td class='p-4 text-gray-600'>$location</td>
                <td class='p-4 text-gray-600'>$pilotName</td>
                <td class='p-4 text-gray-600'>$droneName</td>
                <td class='p-4'>
                    <span class='$statusClass px-3 py-1 rounded-full text-sm font-medium'>$status</span>
                </td>
                <td class='p-4 text-right'>
                    <button onclick='openFlightDetailModal(" . htmlspecialchars(json_encode($flight, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP)) . ")' 
                            class='text-gray-600 hover:text-gray-800 transition'>
                        <i class='fa-solid fa-circle-info'></i>
                    </button>
                </td>
            </tr>";
    }
}

$bodyContent .= "
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- TOEGEVOEGD: Modal Details Vlucht (exact zoals in incidents.php) -->
    <div id='flightDetailModal' class='modal-overlay' role='dialog' aria-modal='true' aria-labelledby='flightDetailTitle'>
        <div class='modal-content'>
            <button class='modal-close-btn' aria-label='Sluit details' onclick='closeFlightDetailModal()'>&times;</button>
            <h3 id='flightDetailTitle' class='flex items-center gap-2'>
                <i class='fa-regular fa-file-lines text-blue-500'></i> 
                Vlucht Details
            </h3>
            <div id='flightDetailContent' class='detail-grid'></div>
        </div>
    </div>

    <!-- TOEGEVOEGD: CSS voor modal en filters (exact zoals in incidents.php) -->
    <style>
        .modal-overlay {
            position: fixed;
            inset: 0;
            background: rgba(17,24,39,0.70);
            z-index: 50;
            display: none;
            align-items: center;
            justify-content: center;
            transition: opacity 0.25s;
            opacity: 0;
        }
        .modal-overlay.active {
            display: flex;
            opacity: 1;
        }
        .modal-content {
            background: #fff;
            border-radius: 1.2rem;
            max-width: 700px;
            width: 100%;
            box-shadow: 0 8px 32px rgba(31, 41, 55, 0.18);
            padding: 2.5rem 2rem 1.5rem 2rem;
            position: relative;
            animation: modalIn 0.18s cubic-bezier(.4,0,.2,1);
            overflow-y: auto;
            max-height: 90vh;
        }
        @keyframes modalIn {
            from { transform: translateY(60px) scale(0.98); opacity: 0.3; }
            to   { transform: translateY(0) scale(1); opacity: 1; }
        }
        .modal-close-btn {
            position: absolute;
            right: 1.3rem;
            top: 1.3rem;
            background: transparent;
            border: none;
            font-size: 1.8rem;
            color: #bbb;
            cursor: pointer;
            transition: color 0.15s;
            line-height: 1;
        }
        .modal-close-btn:hover {
            color: #111827;
        }
        .modal-content h3 {
            margin-top: 0;
            margin-bottom: 1.7rem;
            font-size: 1.22rem;
            font-weight: 700;
            color: #1e293b;
            letter-spacing: .01em;
        }
        
        .detail-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
        }
        .detail-group {
            margin-bottom: 1.2rem;
        }
        .detail-label {
            font-size: 0.875rem;
            color: #6b7280;
            font-weight: 500;
            margin-bottom: 0.3rem;
        }
        .detail-value {
            font-size: 1rem;
            color: #1f2937;
            font-weight: 500;
        }
        
        .filter-bar {
            background: #f3f4f6;
            border: 1px solid #e5e7eb;
            border-radius: 0.75rem;
            padding: 1rem 1.5rem;
            margin: 0 1.5rem 1.5rem;
            display: flex;
            align-items: center;
            gap: 1rem;
            flex-wrap: wrap;
        }
        .filter-group {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .filter-label {
            font-size: 0.875rem;
            color: #4b5563;
            font-weight: 500;
        }
        .filter-select, .filter-search {
            background: white;
            border: 1px solid #d1d5db;
            border-radius: 0.5rem;
            padding: 0.5rem 1rem;
            font-size: 0.875rem;
            cursor: pointer;
            min-width: 180px;
        }
        .filter-select:focus, .filter-search:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }
        .filter-search {
            flex-grow: 1;
            background-image: url(\"data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' class='h-6 w-6' fill='none' viewBox='0 0 24 24' stroke='%239ca3af'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z' /%3E%3C/svg%3E\");
            background-repeat: no-repeat;
            background-position: right 0.75rem center;
            background-size: 1rem;
            min-width: 280px;
        }
        
        /* Responsive aanpassingen */
        @media (max-width: 768px) {
            .filter-bar {
                flex-direction: column;
                align-items: stretch;
            }
            .filter-group {
                width: 100%;
            }
            .filter-select, .filter-search {
                width: 100%;
            }
        }
    </style>
";

// Inclusie van header-component en template.php
require_once __DIR__ . '/../components/header.php';
require_once __DIR__ . '/layouts/template.php';
?>
<script>
    // JavaScript-code verplaatst naar extern script om PHP-waarschuwingen te voorkomen
    // Modal open/close
    const flightDetailModal = document.getElementById('flightDetailModal');

    // Vlucht detail modal
    function openFlightDetailModal(flightData) {
        const modalContent = document.getElementById('flightDetailContent');
        if (!modalContent || !flightData) return;

        modalContent.innerHTML = '';

        const fieldsToShow = {
            'id': 'Vlucht ID',
            'vluchtNaam': 'Vluchtnaam',
            'typeNaam': 'Type',
            'startDatumTijd': 'Startdatum/tijd',
            'locatie': 'Locatie',
            'pilootNaam': 'Piloot',
            'droneNaam': 'Drone',
            'status': 'Status',
            'organisatieId': 'Organisatie ID',
            'beschrijving': 'Beschrijving'
        };

        for (const [key, label] of Object.entries(fieldsToShow)) {
            let value = flightData[key] ?? '-';

            // Speciale verwerking voor bepaalde velden
            if (key === 'startDatumTijd') {
                try {
                    const dateObj = new Date(value);
                    value = dateObj.toLocaleString('nl-NL', {
                        year: 'numeric',
                        month: '2-digit',
                        day: '2-digit',
                        hour: '2-digit',
                        minute: '2-digit'
                    });
                } catch (e) {
                    // Negeer fouten
                }
            } else if (key === 'status') {
                const statusColors = {
                    'gepland': 'text-blue-600 bg-blue-100',
                    'lopend': 'text-yellow-600 bg-yellow-100',
                    'afgerond': 'text-green-600 bg-green-100',
                    'geannuleerd': 'text-red-600 bg-red-100'
                };
                const colorClass = statusColors[value.toLowerCase()] || 'bg-gray-100 text-gray-800';
                value = `<span class='px-2 py-1 rounded-full text-xs ${colorClass}'>${value}</span>`;
            }

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

    // Filter functionaliteit
    function filterFlights() {
        const searchTerm = document.getElementById('searchInput').value.toLowerCase();
        const statusFilter = document.getElementById('statusFilter').value.toLowerCase();
        const pilotFilter = document.getElementById('pilotFilter').value.toLowerCase();

        const rows = document.querySelectorAll('#flightsTable tbody tr');

        rows.forEach(row => {
            const rowText = row.textContent.toLowerCase();
            const status = row.dataset.status || '';
            const pilot = (row.dataset.pilot || '').toLowerCase();

            const matchesSearch = rowText.includes(searchTerm);
            const matchesStatus = statusFilter === '' || status === statusFilter;
            const matchesPilot = pilotFilter === '' || pilot === pilotFilter;

            row.style.display = (matchesSearch && matchesStatus && matchesPilot) ? '' : 'none';
        });
    }

    // Event listeners voor filters
    document.addEventListener('DOMContentLoaded', () => {
        document.getElementById('searchInput').addEventListener('input', filterFlights);
        document.getElementById('statusFilter').addEventListener('change', filterFlights);
        document.getElementById('pilotFilter').addEventListener('change', filterFlights);
    });
</script>