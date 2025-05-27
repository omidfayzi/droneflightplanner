<?php
// /var/www/public/frontend/pages/reports/flight-logs.php

if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../functions.php';

// Haal data op van de API
$apiBaseUrl = "http://devserv01.holdingthedrones.com:4539";
$flightLogsUrl = "$apiBaseUrl/vluchten";
$flightLogsResponse = @file_get_contents($flightLogsUrl);
$flightLogs = $flightLogsResponse ? json_decode($flightLogsResponse, true) : [];
if (json_last_error() !== JSON_ERROR_NONE && $flightLogsResponse) {
    error_log("JSON Decode Error for flight logs: " . json_last_error_msg() . " | Response: " . $flightLogsResponse);
    $flightLogs = [];
}
if (isset($flightLogs['data'])) {
    $flightLogs = $flightLogs['data'];
}

// Verzamel dynamisch alle kolomnamen
$kolomSet = [];
foreach ($flightLogs as $log) {
    foreach ($log as $key => $value) {
        $kolomSet[$key] = true;
    }
}
$kolommen = array_keys($kolomSet);

$showHeader = 1;
$userName = $_SESSION['user']['first_name'] ?? 'Onbekend';
$headTitle = "Vluchtlogs";
$gobackUrl = 0;
$rightAttributes = 0;

require_once __DIR__ . '/../../components/header.php';
require_once __DIR__ . '/../layouts/template.php';
?>

<style>
    .modal-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.6);
        display: none;
        align-items: center;
        justify-content: center;
        z-index: 1050;
        opacity: 0;
        transition: opacity 0.3s;
    }

    .modal-overlay.active {
        display: flex;
        opacity: 1;
    }

    .modal-content {
        background-color: white;
        padding: 2rem;
        border-radius: 0.5rem;
        width: 90%;
        max-width: 800px;
        box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        position: relative;
        transform: translateY(-20px) scale(0.95);
        opacity: 0;
        transition: transform 0.3s, opacity 0.3s;
        max-height: 90vh;
        overflow-y: auto;
    }

    .modal-overlay.active .modal-content {
        transform: translateY(0) scale(1);
        opacity: 1;
    }

    .modal-close-btn {
        position: absolute;
        top: 1rem;
        right: 1rem;
        font-size: 1.5rem;
        color: #9ca3af;
        background: none;
        border: none;
        cursor: pointer;
        padding: 0.25rem;
        line-height: 1;
    }

    .modal-close-btn:hover {
        color: #6b7280;
    }

    .modal-content h3 {
        font-size: 1.25rem;
        font-weight: 600;
        margin-bottom: 1.5rem;
        color: #1f2937;
    }

    .form-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 1rem;
    }

    .form-group {
        margin-bottom: 1rem;
    }
</style>

<div class='h-full bg-gray-100 shadow-md rounded-tl-xl w-full flex flex-col'>
    <div class='p-6 bg-white flex justify-between items-center border-b border-gray-200 flex-shrink-0'>
        <div class='flex space-x-6 text-sm font-medium'>
            <a href='flight-logs.php' class='text-gray-900 border-b-2 border-black pb-2'>Vlucht Logs</a>
            <a href='incidents.php' class='text-gray-600 hover:text-gray-900'>Incidenten</a>
        </div>
        <button onclick='openReportFlightModal()' class='bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors text-sm flex items-center'>
            <i class='fa-solid fa-file-medical-alt mr-2'></i>+ Vlucht Rapporteren
        </button>
    </div>

    <div class='p-6 overflow-y-auto flex-grow'>
        <div class='bg-white rounded-lg shadow overflow-hidden'>
            <div class='p-6 border-b border-gray-200 flex justify-between items-center'>
                <h2 class='text-xl font-semibold text-gray-800'>Overzicht Vlucht Logs</h2>
                <div>
                    <input type='text' placeholder='Zoek vlucht...' class='px-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-1 focus:ring-blue-500'>
                </div>
            </div>
            <div class='overflow-x-auto'>
                <table class='w-full'>
                    <thead class='bg-gray-50 text-xs uppercase text-gray-700'>
                        <tr>
                            <?php foreach ($kolommen as $kolom): ?>
                                <th class='px-4 py-3 text-left'><?= htmlspecialchars($kolom) ?></th>
                            <?php endforeach; ?>
                            <th class='px-4 py-3 text-left'>Acties</th>
                        </tr>
                    </thead>
                    <tbody class='divide-y divide-gray-200 text-sm'>
                        <?php if (!empty($flightLogs) && is_array($flightLogs)): ?>
                            <?php foreach ($flightLogs as $log):
                                // Uniek ID bepalen voor modal-openers
                                $logId = $log['DFPPLF_Id'] ?? $log['TaakReferentie'] ?? $log['id'] ?? '';
                            ?>
                                <tr class='hover:bg-gray-50 transition'>
                                    <?php foreach ($kolommen as $kolom): ?>
                                        <td class='px-4 py-3 whitespace-nowrap'>
                                            <?= htmlspecialchars(is_array($log[$kolom] ?? '') ? json_encode($log[$kolom]) : ($log[$kolom] ?? '')) ?>
                                        </td>
                                    <?php endforeach; ?>
                                    <td class='px-4 py-3 whitespace-nowrap'>
                                        <button onclick='viewFlightLogDetails("<?= addslashes($logId) ?>")' class='text-blue-600 hover:text-blue-800 mr-2' title='Details'><i class='fa-solid fa-eye'></i></button>
                                        <button onclick='openEditFlightLogModal("<?= addslashes($logId) ?>")' class='text-gray-600 hover:text-gray-800' title='Bewerk'><i class='fa-solid fa-pencil'></i></button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan='<?= count($kolommen) + 1 ?>' class='text-center text-gray-500 py-10'>Geen vluchtlogs gevonden.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal voor Details Vluchtlog (Alleen-lezen) -->
<div id='detailFlightLogModal' class='modal-overlay'>
    <div class='modal-content'>
        <button class='modal-close-btn' onclick='closeDetailFlightLogModal()'>×</button>
        <h3>Vluchtlog Details</h3>
        <div id="flightLogDetailContent" class='form-grid'></div>
        <div class='mt-6 flex justify-end'>
            <button type='button' onclick='closeDetailFlightLogModal()' class='bg-gray-200 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-300 text-sm'>Sluiten</button>
        </div>
    </div>
</div>

<!-- Modal voor Nieuwe Vlucht Rapporteren -->
<div id='reportFlightModal' class='modal-overlay'>
    <div class='modal-content'>
        <button class='modal-close-btn' onclick='closeReportFlightModal()'>×</button>
        <h3>Nieuwe Vlucht Rapporteren (Handmatig Loggen)</h3>
        <form id='reportFlightForm' action='save_flight_log.php' method='POST'>
            <div class='form-grid'>
                <!-- Voeg hier je form velden toe zoals in je oude code -->
                <!-- Of dynamisch indien je wilt, of statisch zoals eerder -->
                <!-- ... zie vorige voorbeeld ... -->
            </div>
            <div class='mt-6 flex justify-end space-x-3'>
                <button type='button' onclick='closeReportFlightModal()' class='bg-gray-200 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-300 text-sm'>Annuleren</button>
                <button type='submit' class='bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 text-sm flex items-center'><i class='fa-solid fa-plus-circle mr-2'></i>Log Rapporteren</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal voor Bewerk Vluchtlog -->
<div id='editFlightLogModal' class='modal-overlay'>
    <div class='modal-content'>
        <button class='modal-close-btn' onclick='closeEditFlightLogModal()'>×</button>
        <h3>Vluchtlog Bewerken</h3>
        <form id='editFlightLogForm' action='update_flight_log.php' method='POST'>
            <input type='hidden' name='edit_flight_log_id' id='edit_flight_log_id'>
            <div class='form-grid'>
                <!-- Voeg hier je form velden toe zoals in je oude code -->
            </div>
            <div class='mt-6 flex justify-end space-x-3'>
                <button type='button' onclick='closeEditFlightLogModal()' class='bg-gray-200 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-300 text-sm'>Annuleren</button>
                <button type='submit' class='bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 text-sm flex items-center'><i class='fa-solid fa-save mr-2'></i>Wijzigingen Opslaan</button>
            </div>
        </form>
    </div>
</div>

<script>
    const flightLogsData = <?= json_encode($flightLogs, JSON_UNESCAPED_UNICODE); ?>;
    const editFlightLogModal = document.getElementById('editFlightLogModal');
    const reportFlightModal = document.getElementById('reportFlightModal');
    const detailFlightLogModal = document.getElementById('detailFlightLogModal');

    function openReportFlightModal() {
        if (reportFlightModal) reportFlightModal.classList.add('active');
    }

    function closeReportFlightModal() {
        if (reportFlightModal) {
            reportFlightModal.classList.remove('active');
            document.getElementById('reportFlightForm')?.reset();
        }
    }
    if (reportFlightModal) {
        reportFlightModal.addEventListener('click', function(event) {
            if (event.target === reportFlightModal) closeReportFlightModal();
        });
    }

    function openEditFlightLogModal(flightLogId) {
        const logEntry = flightLogsData.find(log => (log.DFPPLF_Id == flightLogId) || (log.TaakReferentie == flightLogId) || (log.id == flightLogId));
        if (logEntry && editFlightLogModal) {
            // VUL HIER VELDEN IN
            document.getElementById('edit_flight_log_id').value = logEntry.DFPPLF_Id || logEntry.TaakReferentie || logEntry.id || '';
            // Voeg meer velden toe afhankelijk van je formulier
            editFlightLogModal.classList.add('active');
        } else {
            alert('Vluchtlog niet gevonden voor bewerken.');
        }
    }

    function closeEditFlightLogModal() {
        if (editFlightLogModal) {
            editFlightLogModal.classList.remove('active');
            document.getElementById('editFlightLogForm')?.reset();
        }
    }
    if (editFlightLogModal) {
        editFlightLogModal.addEventListener('click', function(event) {
            if (event.target === editFlightLogModal) closeEditFlightLogModal();
        });
    }

    function viewFlightLogDetails(flightLogId) {
        const logEntry = flightLogsData.find(log => (log.DFPPLF_Id == flightLogId) || (log.TaakReferentie == flightLogId) || (log.id == flightLogId));
        if (logEntry && detailFlightLogModal) {
            let html = '';
            Object.entries(logEntry).forEach(([label, safeValue]) => {
                html += `<div class='form-group'><label class='font-medium text-gray-700'>${label.replace(/([A-Z])/g, ' $1').replace(/^./, str => str.toUpperCase())}</label><div class='mt-1 text-gray-900'>${Array.isArray(safeValue) ? safeValue.join(', ') : safeValue ?? ''}</div></div>`;
            });
            document.getElementById('flightLogDetailContent').innerHTML = html;
            detailFlightLogModal.classList.add('active');
        } else {
            alert('Vluchtlog niet gevonden voor details.');
        }
    }

    function closeDetailFlightLogModal() {
        if (detailFlightLogModal) {
            detailFlightLogModal.classList.remove('active');
            document.getElementById('flightLogDetailContent').innerHTML = '';
        }
    }
    if (detailFlightLogModal) {
        detailFlightLogModal.addEventListener('click', function(event) {
            if (event.target === detailFlightLogModal) closeDetailFlightLogModal();
        });
    }
</script>