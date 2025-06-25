<?php
// /var/www/public/frontend/pages/flight-logs.php

if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../functions.php';

// Haal data uit de API
$apiBaseUrl = "http://devserv01.holdingthedrones.com:4539";
$flightLogsUrl = "$apiBaseUrl/vluchtlogboek";
$flightLogsResponse = @file_get_contents($flightLogsUrl);
$flightLogs = $flightLogsResponse ? json_decode($flightLogsResponse, true) : [];
if (isset($flightLogs['data'])) $flightLogs = $flightLogs['data'];

// Dynamisch alle kolomnamen verzamelen
$kolomSet = [];
foreach ($flightLogs as $vlucht) {
    foreach ($vlucht as $key => $value) {
        $kolomSet[$key] = true;
    }
}
$kolommen = array_keys($kolomSet);

$showHeader = 1;
$userName = $_SESSION['user']['first_name'] ?? 'Onbekend';
$headTitle = "Vluchten Log";
$gobackUrl = 0;
$rightAttributes = 0;

// BodyContent (alleen HTML, geen <script> hierbinnen!)
$bodyContent = "
    <style>
        .modal-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.6); display: none; align-items: center; justify-content: center; z-index: 1050; opacity: 0; transition: opacity 0.3s; }
        .modal-overlay.active { display: flex; opacity: 1; }
        .modal-content { background-color: white; padding: 2rem; border-radius: 0.5rem; width: 90%; max-width: 700px; box-shadow: 0 10px 25px -5px rgba(0,0,0,0.1), 0 10px 10px -5px rgba(0,0,0,0.04); position: relative; transform: translateY(-20px) scale(0.95); opacity: 0; transition: transform 0.3s, opacity 0.3s; max-height: 90vh; overflow-y: auto; }
        .modal-overlay.active .modal-content { transform: translateY(0) scale(1); opacity: 1; }
        .modal-close-btn { position: absolute; top: 1rem; right: 1rem; font-size: 1.5rem; color: #9ca3af; background: none; border: none; cursor: pointer; padding: 0.25rem; line-height: 1; }
        .modal-close-btn:hover { color: #6b7280; }
        .modal-content h3 { font-size: 1.25rem; font-weight: 600; margin-bottom: 1.5rem; color: #1f2937; }
        .form-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1rem; }
        .form-group { margin-bottom: 1rem; }
    </style>

    <div class='h-full bg-gray-100 shadow-md rounded-tl-xl w-full flex flex-col'>
        <div class='p-6 bg-white flex justify-between items-center border-b border-gray-200 flex-shrink-0'>
            <div class='flex space-x-6 text-sm font-medium'>
                <a href='flight-logs.php' class='text-gray-900 border-b-2 border-black pb-2'>Vlucht Logs</a>
                <a href='incidents.php' class='text-gray-600 hover:text-gray-900'>Incidenten</a>
            </div>
        </div>
        <div class='p-6 overflow-y-auto flex-grow'>
            <div class='bg-white rounded-lg shadow overflow-hidden'>
                <div class='p-6 border-b border-gray-200 flex justify-between items-center'>
                    <h2 class='text-xl font-semibold text-gray-800'>Overzicht Vluchten</h2>
                    <div>
                        <input type='text' id='vluchtZoekInput' placeholder='Zoek vlucht...' class='px-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-1 focus:ring-blue-500 w-64'>
                    </div>
                </div>
                <div class='overflow-x-auto'>
                    <table class='w-full' id='flightLogsTable'>
                        <thead class='bg-gray-50 text-xs uppercase text-gray-700'>
                            <tr>";
foreach ($kolommen as $kolom) {
    $bodyContent .= "<th class='px-4 py-3 text-left'>" . htmlspecialchars($kolom) . "</th>";
}
$bodyContent .= "<th class='px-4 py-3 text-left'>Acties</th>
                            </tr>
                        </thead>
                        <tbody class='divide-y divide-gray-200 text-sm' id='flightLogsTbody'>";
if (!empty($flightLogs) && is_array($flightLogs)) {
    foreach ($flightLogs as $vlucht) {
        $bodyContent .= "<tr class='hover:bg-gray-50 transition'>";
        foreach ($kolommen as $kolom) {
            $waarde = array_key_exists($kolom, $vlucht) ? $vlucht[$kolom] : '';

            // Oplossing voor "Array to string conversion" waarschuwing
            if (is_array($waarde)) {
                $waarde = implode(', ', $waarde);
            } elseif (is_bool($waarde)) {
                $waarde = $waarde ? 'Ja' : 'Nee';
            } elseif ($waarde === null) {
                $waarde = '';
            }

            $bodyContent .= "<td class='px-4 py-3 whitespace-nowrap'>" . htmlspecialchars((string)$waarde) . "</td>";
        }
        $id = $vlucht['DFPPVluchtId'] ?? $vlucht['id'] ?? '';
        $disabledClass = $id ? "" : "opacity-50 pointer-events-none";
        $bodyContent .= "<td class='px-4 py-3 whitespace-nowrap'>
            <button onclick='viewFlightDetails(\"$id\")' class='text-blue-600 hover:text-blue-800 mr-2 $disabledClass' title='Details'><i class='fa-solid fa-eye'></i></button>
            <button onclick='openEditFlightModal(\"$id\")' class='text-gray-600 hover:text-gray-800 $disabledClass' title='Bewerk'><i class='fa-solid fa-pencil'></i></button>
        </td>";
        $bodyContent .= "</tr>";
    }
} else {
    $bodyContent .= "<tr><td colspan='" . (count($kolommen) + 1) . "' class='text-center text-gray-500 py-10'>Geen vluchten gevonden of data kon niet worden geladen.</td></tr>";
}
$bodyContent .= "
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal voor Vlucht Details -->
    <div id='flightDetailsModal' class='modal-overlay'>
        <div class='modal-content'>
            <button class='modal-close-btn' onclick='closeFlightDetailsModal()'>×</button>
            <h3>Vlucht Details</h3>
            <div id='flightDetailsContent'>Laadt details...</div>
        </div>
    </div>
    <!-- Modal voor Vlucht Bewerken -->
    <div id='editFlightModal' class='modal-overlay'>
        <div class='modal-content'>
            <button class='modal-close-btn' onclick='closeEditFlightModal()'>×</button>
            <h3>Vlucht Bewerken</h3>
            <form id='editFlightForm'>
                <div id='editFlightFormFields'>Vul hier de editvelden in...</div>
                <div class='mt-6 flex justify-end space-x-3'>
                    <button type='button' onclick='closeEditFlightModal()' class='bg-gray-200 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-300 text-sm'>Annuleren</button>
                    <button type='submit' class='bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 text-sm flex items-center'><i class='fa-solid fa-save mr-2'></i>Wijzigingen Opslaan</button>
                </div>
            </form>
        </div>
    </div>
";

// ---- INCLUDE LAYOUT ----
require_once __DIR__ . '/../../components/header.php';
require_once __DIR__ . '/../layouts/template.php';
?>

<script>
    // Dynamische vluchtdata als échte JS-array/object
    const flightLogsData = <?php echo json_encode($flightLogs, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;

    const flightDetailsModal = document.getElementById('flightDetailsModal');
    const editFlightModal = document.getElementById('editFlightModal');

    function viewFlightDetails(flightId) {
        const flightEntry = flightLogsData.find(f =>
            f.DFPPVluchtId == flightId ||
            f.id == flightId
        );
        if (flightEntry && flightDetailsModal) {
            let html = '<table class="w-full text-sm">';
            Object.entries(flightEntry).forEach(([key, value]) => {
                // Oplossing voor array-waarden in JavaScript
                let displayValue = value;
                if (Array.isArray(value)) {
                    displayValue = value.join(', ');
                } else if (value === null) {
                    displayValue = '';
                }
                html += `<tr>
                    <td class='pr-4 font-medium text-gray-600'>${escapeHTML(key)}</td>
                    <td>${escapeHTML(displayValue)}</td>
                </tr>`;
            });
            html += '</table>';
            document.getElementById('flightDetailsContent').innerHTML = html;
            flightDetailsModal.classList.add('active');
        } else {
            alert('Vlucht niet gevonden.');
        }
    }

    function closeFlightDetailsModal() {
        if (flightDetailsModal) {
            flightDetailsModal.classList.remove('active');
            document.getElementById('flightDetailsContent').innerHTML = '';
        }
    }
    if (flightDetailsModal) {
        flightDetailsModal.addEventListener('click', (event) => {
            if (event.target === flightDetailsModal) closeFlightDetailsModal();
        });
    }

    function openEditFlightModal(flightId) {
        const flightEntry = flightLogsData.find(f =>
            f.DFPPVluchtId == flightId ||
            f.id == flightId
        );
        if (flightEntry && editFlightModal) {
            let html = '';
            Object.entries(flightEntry).forEach(([key, value]) => {
                if (typeof value === "object" && value !== null) return;
                html += `
                    <div class='form-group'>
                        <label class='block text-sm font-medium text-gray-700 mb-1'>${escapeHTML(key)}</label>
                        <input type='text' name='${escapeHTML(key)}' value='${escapeHTML(value ?? "")}' class='mt-1 block w-full p-2 border border-gray-300 rounded-md shadow-sm'>
                    </div>
                `;
            });
            document.getElementById('editFlightFormFields').innerHTML = html;
            editFlightModal.classList.add('active');
        } else {
            alert('Vlucht niet gevonden voor bewerken.');
        }
    }

    function closeEditFlightModal() {
        if (editFlightModal) {
            editFlightModal.classList.remove('active');
            document.getElementById('editFlightFormFields').innerHTML = '';
        }
    }
    if (editFlightModal) {
        editFlightModal.addEventListener('click', (event) => {
            if (event.target === editFlightModal) closeEditFlightModal();
        });
    }

    // Veilig voor XSS
    function escapeHTML(unsafe) {
        if (unsafe === null || unsafe === undefined) return '';
        return String(unsafe)
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }

    // Zoek functionaliteit
    document.getElementById('vluchtZoekInput').addEventListener('input', function(e) {
        const zoekwaarde = e.target.value.toLowerCase();
        const tbody = document.getElementById('flightLogsTbody');
        tbody.querySelectorAll('tr').forEach(row => {
            const tekst = row.innerText.toLowerCase();
            row.style.display = tekst.includes(zoekwaarde) ? '' : 'none';
        });
    });
</script>