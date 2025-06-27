<?php
// /var/www/public/frontend/pages/assets/personeel.php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../functions.php';

// --- API Data Ophalen ---
$apiBaseUrl = defined('API_BASE_URL') ? API_BASE_URL : "http://devserv01.holdingthedrones.com:4539";
$personeelUrl = "$apiBaseUrl/personeel";
$personeelResponse = @file_get_contents($personeelUrl);
$personeel = $personeelResponse ? json_decode($personeelResponse, true) : [];

// Als de API een 'data' array teruggeeft, gebruik die
if (isset($personeel['data']) && is_array($personeel['data'])) {
    $personeel = $personeel['data'];
}

// Controleer op JSON decode fouten
if (json_last_error() !== JSON_ERROR_NONE && $personeelResponse) {
    error_log("JSON Decode Error for personnel: " . json_last_error_msg() . " | Response: " . $personeelResponse);
    $personeel = [];
}

// --- Data Voorbereiding voor Filters & Tabel ---
$kolomSet = [];
$potentialFilterFields = ['status', 'role', 'afdeling', 'functie'];

if (!empty($personeel) && is_array($personeel)) {
    foreach ($personeel as $persoon) {
        foreach ($persoon as $key => $value) {
            $kolomSet[$key] = true;
        }
    }
}
$kolommen = array_keys($kolomSet);

// Verzamel unieke waarden voor de filters
$uniqueStatuses = [];
$uniqueRoles = [];
$uniqueDepartments = [];

if (!empty($personeel) && is_array($personeel)) {
    foreach ($personeel as $persoon) {
        if (isset($persoon['status']) && !empty($persoon['status']) && !in_array($persoon['status'], $uniqueStatuses)) {
            $uniqueStatuses[] = $persoon['status'];
        }

        $roleValue = $persoon['role'] ?? $persoon['functie'] ?? null;
        if (!empty($roleValue) && !in_array($roleValue, $uniqueRoles)) {
            $uniqueRoles[] = $roleValue;
        }

        $departmentValue = $persoon['department'] ?? $persoon['afdeling'] ?? null;
        if (!empty($departmentValue) && !in_array($departmentValue, $uniqueDepartments)) {
            $uniqueDepartments[] = $departmentValue;
        }
    }

    sort($uniqueStatuses);
    sort($uniqueRoles);
    sort($uniqueDepartments);
}

function getStatusClass($status)
{
    $statusLower = strtolower($status);
    return match ($statusLower) {
        'actief', 'in dienst' => 'bg-green-100 text-green-800',
        'verlof', 'ziek' => 'bg-yellow-100 text-yellow-800',
        'inactief', 'uit dienst' => 'bg-red-100 text-red-800',
        default => 'bg-gray-100 text-gray-800'
    };
}

$showHeader = 1;
$userName = $_SESSION['user']['first_name'] ?? 'Onbekend';
$org = isset($organisation) ? $organisation : 'Holding the Drones';
$headTitle = "Personeelsbeheer";
$gobackUrl = 0;
$rightAttributes = 0;

$bodyContent = "
    <style>
        .modal-overlay {
            position: fixed;
            inset: 0;
            background: rgba(17, 24, 39, 0.70);
            z-index: 50;
            display: none;
            align-items: center;
            justify-content: center;
            transition: opacity 0.25s ease-in-out;
            opacity: 0;
        }
        .modal-overlay.active {
            display: flex;
            opacity: 1;
        }
        .modal-content {
            background: #fff;
            border-radius: 1rem;
            max-width: 580px;
            width: 100%;
            box-shadow: 0 8px 32px rgba(31, 41, 55, 0.18);
            padding: 2.5rem 2rem 1.5rem 2rem;
            position: relative;
            animation: modalIn 0.18s cubic-bezier(.4, 0, .2, 1);
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
            transition: color 0.15s ease-in-out;
            line-height: 1;
            z-index: 10;
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
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1rem;
        }
        .form-group { margin-bottom: 1rem; }
        .form-group label {
            display: block;
            margin-bottom: 0.4rem;
            font-size: 0.875rem;
            font-weight: 500;
            color: #374151;
        }
        .form-group input,
        .form-group select,
        .form-group textarea {
            border: 1px solid #d1d5db;
            border-radius: 0.5rem;
            padding: 0.6rem 0.8rem;
            width: 100%;
            font-size: 0.875rem;
            color: #1f2937;
            box-shadow: 0 1px 0px rgba(0, 0, 0, 0.03) inset;
        }
        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.15);
        }
        .form-group textarea { resize: vertical; }
        .form-group .required-star { color: #ef4444; margin-left: 4px;}

        .filter-bar {
            background: #f3f4f6;
            border: 1px solid #e5e7eb;
            border-radius: 0.75rem;
            padding: 1rem 1.5rem;
            margin-bottom: 1.5rem;
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
        
        .employee-detail-modal {
            max-width: 800px;
        }
        .employee-detail-header {
            display: flex;
            align-items: center;
            margin-bottom: 1.5rem;
        }
        .employee-icon {
            font-size: 2rem;
            color: #3b82f6;
            margin-right: 1rem;
        }
        .employee-detail-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
        }
        .detail-group {
            margin-bottom: 1.2rem;
        }
        .detail-label {
            font-weight: 600;
            color: #4b5563;
            margin-bottom: 0.3rem;
            font-size: 0.9rem;
        }
        .detail-value {
            font-size: 1rem;
            color: #1f2937;
            padding: 0.5rem 0;
            border-bottom: 1px solid #e5e7eb;
        }
        .detail-value.status {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.85rem;
            font-weight: 500;
        }
        .employee-actions {
            display: flex;
            gap: 1rem;
            margin-top: 2rem;
            justify-content: flex-end;
        }
        
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

    <div class='h-full bg-gray-100 shadow-md rounded-tl-xl w-full flex flex-col'>
        <div class='p-6 bg-white flex justify-between items-center border-b border-gray-200 flex-shrink-0'>
            <div class='flex space-x-6 text-sm font-medium'>
                <a href='drones.php' class='text-gray-600 hover:text-gray-900'>Drones</a>
                <a href='employees.php' class='text-gray-900 border-b-2 border-black pb-2'>Personeel</a>
                <a href='addons.php' class='text-gray-600 hover:text-gray-900'>Add-ons</a>
                <a href='verzekeringen.php' class='text-gray-600 hover:text-gray-900'>Verzekeringen</a>
            </div>
            <button onclick='openAddEmployeeModal()' class='bg-gradient-to-r from-blue-500 to-blue-700 text-white px-4 py-2 rounded-lg hover:bg-gray-800 transition-colors text-sm flex items-center'>
                <i class='fa-solid fa-plus mr-2'></i>Nieuw Personeelslid
            </button>
        </div>

        <div class='px-6 pt-4'>
            <div class='filter-bar'>
                <div class='filter-group'>
                    <span class='filter-label'>Status:</span>
                    <select id='statusFilter' class='filter-select'>
                        <option value=''>Alle statussen</option>";
foreach ($uniqueStatuses as $status) {
    $bodyContent .= "<option value='" . htmlspecialchars(strtolower($status)) . "'>" . htmlspecialchars(ucfirst($status)) . "</option>";
}
$bodyContent .= "
                    </select>
                </div>";

// Voeg functie filter toe indien beschikbaar
if (!empty($uniqueRoles)) {
    $bodyContent .= "
                <div class='filter-group'>
                    <span class='filter-label'>Functie:</span>
                    <select id='roleFilter' class='filter-select'>
                        <option value=''>Alle functies</option>";
    foreach ($uniqueRoles as $role) {
        $bodyContent .= "<option value='" . htmlspecialchars(strtolower($role)) . "'>" . htmlspecialchars($role) . "</option>";
    }
    $bodyContent .= "
                    </select>
                </div>";
}

// Voeg afdeling filter toe indien beschikbaar
if (!empty($uniqueDepartments)) {
    $bodyContent .= "
                <div class='filter-group'>
                    <span class='filter-label'>Afdeling:</span>
                    <select id='departmentFilter' class='filter-select'>
                        <option value=''>Alle afdelingen</option>";
    foreach ($uniqueDepartments as $dept) {
        $bodyContent .= "<option value='" . htmlspecialchars(strtolower($dept)) . "'>" . htmlspecialchars($dept) . "</option>";
    }
    $bodyContent .= "
                    </select>
                </div>";
}

$bodyContent .= "
                <div class='filter-group flex-grow'>
                    <input id='searchInput' type='text' placeholder='Zoek personeel...' class='filter-search'>
                </div>
            </div>
        </div>

        <div class='p-6 overflow-y-auto flex-grow'>
            <div class='bg-white rounded-lg shadow overflow-hidden'>
                <div class='p-6 border-b border-gray-200 flex justify-between items-center'>
                    <h2 class='text-xl font-semibold text-gray-800'>Personeelsbestand - $org</h2>
                </div>
                <div class='overflow-x-auto'>
                    <table id='employeesTable' class='w-full'>
                        <thead class='bg-gray-50 text-xs uppercase text-gray-700'>
                            <tr>";

// Toon alle kolommen
foreach ($kolommen as $kolom) {
    $bodyContent .= "<th class='px-4 py-3 text-left'>" . htmlspecialchars($kolom) . "</th>";
}
$bodyContent .= "<th class='px-4 py-3 text-left'>Acties</th>";
$bodyContent .= "</tr>
                        </thead>
                        <tbody class='divide-y divide-gray-200 text-sm'>";

if (!empty($personeel) && is_array($personeel)) {
    foreach ($personeel as $persoon) {
        $bodyContent .= "<tr class='hover:bg-gray-50 transition'";

        // Voeg data-attributen toe voor filtering
        if (isset($persoon['status'])) {
            $bodyContent .= " data-status='" . htmlspecialchars(strtolower($persoon['status'])) . "'";
        }
        if (isset($persoon['role']) || isset($persoon['functie'])) {
            $role = $persoon['role'] ?? $persoon['functie'] ?? '';
            $bodyContent .= " data-role='" . htmlspecialchars(strtolower($role)) . "'";
        }
        if (isset($persoon['department']) || isset($persoon['afdeling'])) {
            $dept = $persoon['department'] ?? $persoon['afdeling'] ?? '';
            $bodyContent .= " data-department='" . htmlspecialchars(strtolower($dept)) . "'";
        }

        $bodyContent .= ">";

        // Toon alle waarden voor deze persoon
        foreach ($kolommen as $kolom) {
            $waarde = $persoon[$kolom] ?? '';
            $displayValue = $waarde;

            if ($kolom === 'status') {
                $statusClass = getStatusClass($waarde);
                $displayValue = "<span class='$statusClass px-3 py-1 rounded-full text-xs font-semibold'>" . htmlspecialchars($waarde) . "</span>";
            } else {
                $displayValue = htmlspecialchars($waarde);
            }

            $bodyContent .= "<td class='px-4 py-3 whitespace-nowrap'>$displayValue</td>";
        }

        $persoonId = $persoon['personeelId'] ?? $persoon['id'] ?? 'unknown';
        $bodyContent .= "<td class='px-4 py-3 whitespace-nowrap text-gray-600'>
                            <button onclick='openEmployeeDetailModal(" . json_encode($persoon) . ")' class='text-blue-600 hover:text-blue-800 transition mr-3' title='Details personeel'>
                                <i class='fa-solid fa-info-circle'></i>
                            </button>
                            <button onclick='openEditEmployeeModal(" . json_encode($persoon) . ")' class='text-green-600 hover:text-green-800 transition' title='Bewerk personeel'>
                                <i class='fa-solid fa-edit'></i>
                            </button>
                        </td>";

        $bodyContent .= "</tr>";
    }
} else {
    $bodyContent .= "<tr><td colspan='" . (count($kolommen) + 1) . "' class='text-center text-gray-500 py-10'>Geen personeel gevonden of data kon niet worden geladen.</td></tr>";
}
$bodyContent .= "
                        </tbody>
                    </table>
                </div>
                <div class='p-4 border-t border-gray-200 flex justify-between items-center text-sm'>
                    <span>Toont " . (($personeel) ? ("1-" . count($personeel)) : "0") . " van " . count($personeel) . " personeelsleden</span>
                </div>
            </div>
        </div>
    </div>

    <div id='addEmployeeModal' class='modal-overlay' role='dialog' aria-modal='true' aria-labelledby='addEmployeeModalTitle'>
        <div class='modal-content'>
            <button class='modal-close-btn' aria-label='Sluit modal' onclick='closeAddEmployeeModal()'>×</button>
            <h3 id='addEmployeeModalTitle' class='flex items-center gap-2'>
              <i class=\"fa-solid fa-user text-white mr-2\"></i> 
              Nieuw Personeelslid Toevoegen
            </h3>
            <form id='addEmployeeForm' action='save_employee.php' method='POST' class='space-y-4'>
                <div class='form-grid'>
                    <div class='form-group'>
                        <label for='employee_first_name'>Voornaam <span class='required-star'>*</span></label>
                        <input type='text' id='employee_first_name' name='first_name' required placeholder='bv. Jan'>
                    </div>
                    <div class='form-group'>
                        <label for='employee_last_name'>Achternaam <span class='required-star'>*</span></label>
                        <input type='text' id='employee_last_name' name='last_name' required placeholder='bv. Jansen'>
                    </div>
                    <div class='form-group'>
                        <label for='employee_email'>E-mail <span class='required-star'>*</span></label>
                        <input type='email' id='employee_email' name='email' required placeholder='bv. jan@voorbeeld.nl'>
                    </div>
                    <div class='form-group'>
                        <label for='employee_phone'>Telefoon</label>
                        <input type='text' id='employee_phone' name='phone' placeholder='bv. 0612345678'>
                    </div>
                    <div class='form-group'>
                        <label for='employee_function'>Functie <span class='required-star'>*</span></label>
                        <input type='text' id='employee_function' name='functie' required placeholder='bv. Drone Operator'>
                    </div>
                    <div class='form-group'>
                        <label for='employee_status'>Status <span class='required-star'>*</span></label>
                        <select id='employee_status' name='status' required>
                            <option value=''>Selecteer status...</option>";
foreach ($uniqueStatuses as $status) {
    $bodyContent .= "<option value='" . htmlspecialchars(strtolower($status)) . "'>" . htmlspecialchars(ucfirst($status)) . "</option>";
}
$bodyContent .= "
                        </select>
                    </div>
                    <div class='form-group'>
                        <label for='employee_hire_date'>Datum in dienst</label>
                        <input type='date' id='employee_hire_date' name='hire_date'>
                    </div>
                    <div class='form-group'>
                        <label for='employee_end_date'>Datum uit dienst</label>
                        <input type='date' id='employee_end_date' name='end_date'>
                    </div>
                    
                    <div class='form-group col-span-full'>
                         <label for='employee_notes'>Notities</label>
                         <textarea id='employee_notes' name='notes' rows='3' placeholder='Eventuele aanvullende informatie...'></textarea>
                    </div>
                </div>
                <div class='pt-4 flex justify-end space-x-3'>
                    <button type='button' onclick='closeAddEmployeeModal()' class='bg-gray-200 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-300 text-sm'>Annuleren</button>
                    <button type='submit' class='bg-gradient-to-r from-blue-500 to-blue-700 text-white px-4 py-2 rounded-lg hover:bg-gray-800 text-sm flex items-center'>
                        <i class='fa-solid fa-save mr-2'></i>Personeelslid Opslaan
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div id='employeeDetailModal' class='modal-overlay' role='dialog' aria-modal='true' aria-labelledby='employeeDetailModalTitle'>
        <div class='modal-content employee-detail-modal'>
            <button class='modal-close-btn' aria-label='Sluit modal' onclick='closeEmployeeDetailModal()'>×</button>
            <div id='employeeDetailContent'></div>
        </div>
    </div>

    <div id='editEmployeeModal' class='modal-overlay' role='dialog' aria-modal='true' aria-labelledby='editEmployeeModalTitle'>
        <div class='modal-content'>
            <button class='modal-close-btn' aria-label='Sluit modal' onclick='closeEditEmployeeModal()'>×</button>
            <h3 id='editEmployeeModalTitle' class='flex items-center gap-2'>
              <i class=\"fa-solid fa-pen text-gray-700 mr-2\"></i> 
              Personeelslid Bewerken
            </h3>
            <form id='editEmployeeForm' action='update_employee.php' method='POST' class='space-y-4'>
                <input type='hidden' id='edit_employeeId' name='employeeId'>
                <div class='form-grid' id='editEmployeeFormContent'>
                </div>
                <div class='pt-4 flex justify-end space-x-3'>
                    <button type='button' onclick='closeEditEmployeeModal()' class='bg-gray-200 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-300 text-sm'>Annuleren</button>
                    <button type='submit' class='bg-gradient-to-r from-blue-500 to-blue-700 text-white px-4 py-2 rounded-lg hover:bg-gray-800 text-sm flex items-center'>
                        <i class='fa-solid fa-save mr-2'></i>Wijzigingen Opslaan
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        const employeesData = " . json_encode($personeel, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) . ";
        
        const addEmployeeModal = document.getElementById('addEmployeeModal');
        const employeeDetailModal = document.getElementById('employeeDetailModal');
        const editEmployeeModal = document.getElementById('editEmployeeModal');
        
        function openAddEmployeeModal() {
            if (addEmployeeModal) addEmployeeModal.classList.add('active');
            document.body.style.overflow = 'hidden';
        }
        
        function closeAddEmployeeModal() {
            if (addEmployeeModal) {
                 addEmployeeModal.classList.remove('active');
                 document.getElementById('addEmployeeForm')?.reset();
                 document.body.style.overflow = '';
            }
        }

        function openEmployeeDetailModal(employee) {
            const modalContent = document.getElementById('employeeDetailContent');
            if (modalContent) {
                let content = '<div class=\"employee-detail-header\">';
                content += '<i class=\"fa-solid fa-user employee-icon\"></i>';
                content += '<h3 class=\"text-xl font-semibold\">' + (employee.first_name || '') + ' ' + (employee.last_name || '') + '</h3>';
                content += '</div>';
                
                content += '<div class=\"employee-detail-grid\">';
                content += '<div>';
                content += '<div class=\"detail-group\"><div class=\"detail-label\">E-mail</div><div class=\"detail-value\">' + (employee.email || 'N/B') + '</div></div>';
                content += '<div class=\"detail-group\"><div class=\"detail-label\">Telefoon</div><div class=\"detail-value\">' + (employee.phone || 'N/B') + '</div></div>';
                content += '<div class=\"detail-group\"><div class=\"detail-label\">Functie</div><div class=\"detail-value\">' + (employee.functie || employee.role || 'N/B') + '</div></div>';
                let statusClass = getStatusClass(employee.status);
                content += '<div class=\"detail-group\"><div class=\"detail-label\">Status</div><div class=\"detail-value\"><span class=\"detail-value status ' + statusClass + '\">' + (employee.status || 'Onbekend') + '</span></div></div>';
                content += '</div>';
                
                content += '<div>';
                content += '<div class=\"detail-group\"><div class=\"detail-label\">Afdeling</div><div class=\"detail-value\">' + (employee.afdeling || employee.department || 'N/B') + '</div></div>';
                content += '<div class=\"detail-group\"><div class=\"detail-label\">Datum in dienst</div><div class=\"detail-value\">' + formatEmployeeDate(employee.hire_date) + '</div></div>';
                content += '<div class=\"detail-group\"><div class=\"detail-label\">Datum uit dienst</div><div class=\"detail-value\">' + formatEmployeeDate(employee.end_date) + '</div></div>';
                content += '</div></div>';
                
                content += '<div class=\"detail-group col-span-full\"><div class=\"detail-label\">Notities</div><div class=\"detail-value\">' + (employee.notes || 'Geen notities beschikbaar') + '</div></div>';

                modalContent.innerHTML = content;
            }
            
            if (employeeDetailModal) {
                employeeDetailModal.classList.add('active');
                document.body.style.overflow = 'hidden';
            }
        }
        
        function closeEmployeeDetailModal() {
            if (employeeDetailModal) {
                employeeDetailModal.classList.remove('active');
                document.body.style.overflow = '';
            }
        }
        
        function openEditEmployeeModal(employee) {
            const formContent = document.getElementById('editEmployeeFormContent');
            const employeeIdField = document.getElementById('edit_employeeId');
            
            if (employeeIdField) employeeIdField.value = employee.employeeId || employee.id || '';
            
            if (formContent) {
                let formHtml = '';
                formHtml += '<div class=\"form-group\"><label for=\"edit_first_name\">Voornaam <span class=\"required-star\">*</span></label><input type=\"text\" id=\"edit_first_name\" name=\"first_name\" value=\"' + (employee.first_name || '') + '\" required></div>';
                formHtml += '<div class=\"form-group\"><label for=\"edit_last_name\">Achternaam <span class=\"required-star\">*</span></label><input type=\"text\" id=\"edit_last_name\" name=\"last_name\" value=\"' + (employee.last_name || '') + '\" required></div>';
                formHtml += '<div class=\"form-group\"><label for=\"edit_email\">E-mail <span class=\"required-star\">*</span></label><input type=\"email\" id=\"edit_email\" name=\"email\" value=\"' + (employee.email || '') + '\" required></div>';
                formHtml += '<div class=\"form-group\"><label for=\"edit_phone\">Telefoon</label><input type=\"text\" id=\"edit_phone\" name=\"phone\" value=\"' + (employee.phone || '') + '\"></div>';
                formHtml += '<div class=\"form-group\"><label for=\"edit_functie\">Functie <span class=\"required-star\">*</span></label><input type=\"text\" id=\"edit_functie\" name=\"functie\" value=\"' + (employee.functie || employee.role || '') + '\" required></div>';
                
                formHtml += '<div class=\"form-group\"><label for=\"edit_status\">Status <span class=\"required-star\">*</span></label><select id=\"edit_status\" name=\"status\" required>';
                formHtml += '<option value=\"\">Selecteer status...</option>';
                
                const statuses = " . json_encode($uniqueStatuses) . ";
                statuses.forEach(function(status) {
                    const selected = status === employee.status ? 'selected' : '';
                    formHtml += '<option value=\"' + status + '\" ' + selected + '>' + status + '</option>';
                });
                
                formHtml += '</select></div>';
                formHtml += '<div class=\"form-group\"><label for=\"edit_afdeling\">Afdeling</label><input type=\"text\" id=\"edit_afdeling\" name=\"afdeling\" value=\"' + (employee.afdeling || employee.department || '') + '\"></div>';
                formHtml += '<div class=\"form-group\"><label for=\"edit_hire_date\">Datum in dienst</label><input type=\"date\" id=\"edit_hire_date\" name=\"hire_date\" value=\"' + formatDateForInput(employee.hire_date) + '\"></div>';
                formHtml += '<div class=\"form-group\"><label for=\"edit_end_date\">Datum uit dienst</label><input type=\"date\" id=\"edit_end_date\" name=\"end_date\" value=\"' + formatDateForInput(employee.end_date) + '\"></div>';
                formHtml += '<div class=\"form-group col-span-full\"><label for=\"edit_notes\">Notities</label><textarea id=\"edit_notes\" name=\"notes\" rows=\"3\">' + (employee.notes || '') + '</textarea></div>';
                
                formContent.innerHTML = formHtml;
            }
            
            if (editEmployeeModal) {
                editEmployeeModal.classList.add('active');
                document.body.style.overflow = 'hidden';
            }
        }
        
        function closeEditEmployeeModal() {
            if (editEmployeeModal) {
                editEmployeeModal.classList.remove('active');
                document.body.style.overflow = '';
            }
        }
        
        function formatEmployeeDate(dateString) {
            if (!dateString || dateString === '0000-00-00' || dateString === '0000-00-00 00:00:00') return 'N/B';
            try {
                const date = new Date(dateString);
                return date.toLocaleDateString('nl-NL');
            } catch (e) {
                return dateString;
            }
        }
        
        function formatDateForInput(dateString) {
            if (!dateString || dateString === '0000-00-00' || dateString === '0000-00-00 00:00:00') return '';
            try {
                const date = new Date(dateString);
                return date.toISOString().split('T')[0];
            } catch (e) {
                return '';
            }
        }
        
        function getStatusClass(status) {
            if (!status) return 'bg-gray-100 text-gray-800';
            const statusLower = status.toLowerCase();
            if (statusLower.includes('actief') || statusLower.includes('in dienst')) return 'bg-green-100 text-green-800';
            if (statusLower.includes('verlof') || statusLower.includes('ziek')) return 'bg-yellow-100 text-yellow-800';
            if (statusLower.includes('inactief') || statusLower.includes('uit dienst')) return 'bg-red-100 text-red-800';
            return 'bg-gray-100 text-gray-800';
        }

        function filterEmployees() {
            const searchTerm = document.getElementById('searchInput').value.toLowerCase();
            const statusFilter = document.getElementById('statusFilter').value.toLowerCase();
            const roleFilter = document.getElementById('roleFilter')?.value.toLowerCase() || '';
            const departmentFilter = document.getElementById('departmentFilter')?.value.toLowerCase() || '';
            
            const table = document.getElementById('employeesTable');
            if (!table) return;
            
            const tbody = table.querySelector('tbody');
            if (!tbody) return;

            const rows = tbody.querySelectorAll('tr');
            
            rows.forEach(row => {
                const rowText = row.textContent.toLowerCase();
                const status = row.dataset.status || '';
                const role = row.dataset.role || '';
                const department = row.dataset.department || '';

                const matchesSearch = rowText.includes(searchTerm);
                const matchesStatus = statusFilter === '' || status === statusFilter;
                const matchesRole = roleFilter === '' || role === roleFilter;
                const matchesDepartment = departmentFilter === '' || department === departmentFilter;
                
                row.style.display = (matchesSearch && matchesStatus && matchesRole && matchesDepartment) ? '' : 'none';
            });
        }
        
        document.getElementById('searchInput')?.addEventListener('input', filterEmployees);
        document.getElementById('statusFilter')?.addEventListener('change', filterEmployees);
        
        const roleFilter = document.getElementById('roleFilter');
        if (roleFilter) {
            roleFilter.addEventListener('change', filterEmployees);
        }
        
        const departmentFilter = document.getElementById('departmentFilter');
        if (departmentFilter) {
            departmentFilter.addEventListener('change', filterEmployees);
        }

        document.getElementById('editEmployeeForm')?.addEventListener('submit', function(e) {
            e.preventDefault();
            this.submit();
        });
        
        if (addEmployeeModal) {
            addEmployeeModal.addEventListener('click', function(event) { 
                if (event.target === addEmployeeModal) closeAddEmployeeModal(); 
            });
        }
        
        if (employeeDetailModal) {
            employeeDetailModal.addEventListener('click', function(event) { 
                if (event.target === employeeDetailModal) closeEmployeeDetailModal(); 
            });
        }
        
        if (editEmployeeModal) {
            editEmployeeModal.addEventListener('click', function(event) { 
                if (event.target === editEmployeeModal) closeEditEmployeeModal(); 
            });
        }
    </script>
";

require_once __DIR__ . '/../../components/header.php';
require_once __DIR__ . '/../layouts/template.php';
