<?php
// /var/www/public/app/views/reports/incidents.php

if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../functions.php';

// API data ophalen
$apiBaseUrl = "http://devserv01.holdingthedrones.com:4539";
$incidentsUrl = "$apiBaseUrl/incidenten";
$incidentsResponse = @file_get_contents($incidentsUrl);
$incidents = $incidentsResponse ? json_decode($incidentsResponse, true) : [];
if (isset($incidents['data'])) $incidents = $incidents['data'];

// Kolommen dynamisch bepalen
$kolomSet = [];
foreach ($incidents as $incident) {
  foreach ($incident as $key => $value) {
    $kolomSet[$key] = true;
  }
}
$kolommen = array_keys($kolomSet);

$showHeader = 1;
$userName = $_SESSION['user']['first_name'] ?? 'Onbekend';
$headTitle = "Incidenten Overzicht";

$bodyContent = "
<style>
  /* Modal Styling - Modern/Material */
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
    max-width: 480px;
    width: 100%;
    box-shadow: 0 8px 32px rgba(31, 41, 55, 0.18);
    padding: 2.5rem 2rem 1.5rem 2rem;
    position: relative;
    animation: modalIn 0.18s cubic-bezier(.4,0,.2,1);
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
  .details-row {
    display: flex;
    align-items: flex-start;
    margin-bottom: 1rem;
    gap: 1.2rem;
  }
  .details-label {
    min-width: 110px;
    font-weight: 600;
    color: #64748b;
    font-size: 0.97rem;
    flex-shrink: 0;
  }
  .details-value {
    color: #1e293b;
    font-size: 0.97rem;
    word-break: break-word;
    flex-grow: 1;
  }
  
  /* Filter Bar */
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
  .filter-select {
    background: white;
    border: 1px solid #d1d5db;
    border-radius: 0.5rem;
    padding: 0.5rem 1rem;
    font-size: 0.875rem;
    cursor: pointer;
    min-width: 180px;
  }
  .filter-select:focus {
    outline: none;
    border-color: #3b82f6;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
  }
  .filter-search {
    background: white;
    border: 1px solid #d1d5db;
    border-radius: 0.5rem;
    padding: 0.5rem 1rem;
    font-size: 0.875rem;
    min-width: 280px;
    flex-grow: 1;
    background-image: url(\"data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' class='h-6 w-6' fill='none' viewBox='0 0 24 24' stroke='%239ca3af'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z' /%3E%3C/svg%3E\");
    background-repeat: no-repeat;
    background-position: right 0.75rem center;
    background-size: 1rem;
  }
  .filter-search:focus {
    outline: none;
    border-color: #3b82f6;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
  }
  
  /* Responsive fix */
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
  @media (max-width: 520px) {
    .modal-content {
      max-width: 98vw;
      padding: 1.1rem;
    }
    .details-label { min-width: 70px;}
  }
</style>

<div class='h-full bg-gray-100 shadow-md rounded-tl-xl w-full flex flex-col'>
  <div class='p-6 bg-white flex justify-between items-center border-b border-gray-200 flex-shrink-0'>
    <h2 class='text-xl font-semibold text-gray-800 flex items-center gap-2'>
      <i class=\"fa-solid fa-circle-exclamation text-red-500\"></i>
      Incidenten Overzicht
    </h2>
    <button onclick='openAddIncidentModal()' class='bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700 transition-colors text-sm flex items-center gap-2'>
      <i class=\"fa-solid fa-plus-circle\"></i> Nieuw Incident
    </button>
  </div>
  
  <!-- Filter Bar -->
  <div class='px-6 pt-4'>
    <div class='filter-bar'>
      <div class='filter-group'>
        <span class='filter-label'>Status:</span>
        <select id='statusFilter' class='filter-select'>
          <option value=''>Alle statussen</option>
          <option value='open'>Open</option>
          <option value='in behandeling'>In Behandeling</option>
          <option value='gesloten'>Gesloten</option>
        </select>
      </div>
      
      <div class='filter-group'>
        <span class='filter-label'>Ernst:</span>
        <select id='severityFilter' class='filter-select'>
          <option value=''>Alle ernstniveaus</option>
          <option value='laag'>Laag</option>
          <option value='middel'>Middel</option>
          <option value='hoog'>Hoog</option>
          <option value='kritiek'>Kritiek</option>
        </select>
      </div>
      
      <div class='filter-group flex-grow'>
        <input id='searchInput' type='text' placeholder='Zoek incident...' class='filter-search'>
      </div>
    </div>
  </div>
  
  <div class='p-6 overflow-y-auto flex-grow'>
    <div class='bg-white rounded-lg shadow overflow-hidden'>
      <div class='overflow-x-auto'>
        <table id='incidentsTable' class='w-full'>
          <thead class='bg-gray-50 text-xs uppercase text-gray-700'>
            <tr>";
foreach ($kolommen as $kolom) {
  $bodyContent .= "<th class='px-4 py-3 text-left'>" . htmlspecialchars($kolom) . "</th>";
}
$bodyContent .= "<th class='px-4 py-3 text-left'>Acties</th>
            </tr>
          </thead>
          <tbody class='divide-y divide-gray-200 text-sm'>";
if (!empty($incidents) && is_array($incidents)) {
  foreach ($incidents as $incident) {
    $bodyContent .= "<tr class='hover:bg-gray-50 transition'>";
    foreach ($kolommen as $kolom) {
      $waarde = $incident[$kolom] ?? '';
      if (is_array($waarde)) {
        $waarde = json_encode($waarde, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
      } elseif (is_bool($waarde)) {
        $waarde = $waarde ? 'Ja' : 'Nee';
      }
      $bodyContent .= "<td class='px-4 py-3 whitespace-nowrap'>" . htmlspecialchars((string)$waarde) . "</td>";
    }
    $id = $incident['incidentId'] ?? '';
    $disabledClass = $id ? "" : "opacity-50 pointer-events-none";
    $bodyContent .= "<td class='px-4 py-3 whitespace-nowrap'>
            <button onclick='viewIncidentDetails(\"$id\")' class='text-blue-600 hover:text-blue-800 $disabledClass' title='Details'>
              <i class='fa-regular fa-file-lines'></i>
            </button>
        </td>";
    $bodyContent .= "</tr>";
  }
} else {
  $bodyContent .= "<tr><td colspan='" . (count($kolommen) + 1) . "' class='text-center text-gray-500 py-10'>Geen incidenten gevonden of data kon niet worden geladen.</td></tr>";
}
$bodyContent .= "
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<!-- Modal Nieuw Incident -->
<div id='addIncidentModal' class='modal-overlay' role='dialog' aria-modal='true' aria-labelledby='addIncidentTitle'>
  <div class='modal-content'>
    <button class='modal-close-btn' aria-label='Sluit modal' onclick='closeAddIncidentModal()'>&times;</button>
    <h3 id='addIncidentTitle' class='flex items-center gap-2'>
      <i class=\"fa-solid fa-plus-circle text-red-500\"></i> 
      Nieuw Incident Melden
    </h3>
    <form id='addIncidentForm' action='save_incident.php' method='POST' class='space-y-4'>
      <div class='grid grid-cols-1 md:grid-cols-2 gap-4'>
        <div class='space-y-1'>
          <label for='add_incident_datetime' class='block text-sm font-medium text-gray-700'>Datum & Tijd Incident <span class='text-red-500'>*</span></label>
          <input type='datetime-local' name='datum' id='add_incident_datetime' class='w-full p-2 border border-gray-300 rounded-md shadow-sm' required>
        </div>
        
        <div class='space-y-1'>
          <label for='add_incident_type' class='block text-sm font-medium text-gray-700'>Type Incident <span class='text-red-500'>*</span></label>
          <select name='incident_type' id='add_incident_type' class='w-full p-2 border border-gray-300 rounded-md shadow-sm' required>
            <option value=''>Selecteer type...</option>
            <option value='Technisch - Motor'>Technisch - Motor</option>
            <option value='Technisch - Batterij'>Technisch - Batterij</option>
            <option value='Technisch - Communicatie'>Technisch - Communicatie</option>
            <option value='Technisch - Software'>Technisch - Software</option>
            <option value='Operationeel - Procedurefout'>Operationeel - Procedurefout</option>
            <option value='Operationeel - Pilootfout'>Operationeel - Pilootfout</option>
            <option value='Omgeving - Weer'>Omgeving - Weer</option>
            <option value='Omgeving - Obstakel'>Omgeving - Obstakel (bv. vogel)</option>
            <option value='Omgeving - GPS/Signaal verlies'>Omgeving - GPS/Signaal verlies</option>
            <option value='Beveiliging - Ongeautoriseerde toegang'>Beveiliging - Ongeautoriseerde toegang</option>
            <option value='Overig'>Overig</option>
          </select>
        </div>
        
        <div class='space-y-1'>
          <label for='add_incident_severity' class='block text-sm font-medium text-gray-700'>Ernst <span class='text-red-500'>*</span></label>
          <select name='ernst' id='add_incident_severity' class='w-full p-2 border border-gray-300 rounded-md shadow-sm' required>
            <option value='laag'>Laag</option>
            <option value='middel'>Middel</option>
            <option value='hoog'>Hoog</option>
            <option value='kritiek'>Kritiek</option>
          </select>
        </div>
        
        <div class='space-y-1'>
          <label for='add_incident_flight_id' class='block text-sm font-medium text-gray-700'>Gerelateerde Vlucht ID</label>
          <input type='text' name='vluchtId' id='add_incident_flight_id' class='w-full p-2 border border-gray-300 rounded-md shadow-sm' placeholder='bv. 1'>
        </div>
      </div>
      
      <div class='space-y-1'>
        <label for='add_incident_details' class='block text-sm font-medium text-gray-700'>Beschrijving <span class='text-red-500'>*</span></label>
        <textarea name='beschrijving' id='add_incident_details' rows='3' class='w-full p-2 border border-gray-300 rounded-md shadow-sm' placeholder='Wat is er gebeurd?' required></textarea>
      </div>
      
      <div class='space-y-1'>
        <label for='add_incident_action_taken' class='block text-sm font-medium text-gray-700'>Genomen Acties <span class='text-red-500'>*</span></label>
        <textarea name='actie_ondernomen' id='add_incident_action_taken' rows='2' class='w-full p-2 border border-gray-300 rounded-md shadow-sm' placeholder='Wat is er gedaan?' required></textarea>
      </div>
      
      <div class='grid grid-cols-1 md:grid-cols-2 gap-4'>
        <div class='space-y-1'>
          <label for='add_incident_reporter' class='block text-sm font-medium text-gray-700'>Rapporteur ID <span class='text-red-500'>*</span></label>
          <input type='number' name='rapporteur_id' id='add_incident_reporter' value='' class='w-full p-2 border border-gray-300 rounded-md shadow-sm' required>
        </div>
        
        <div class='space-y-1'>
          <label for='add_incident_status' class='block text-sm font-medium text-gray-700'>Status</label>
          <select name='status' id='add_incident_status' class='w-full p-2 border border-gray-300 rounded-md shadow-sm'>
            <option value='open' selected>Open</option>
            <option value='in behandeling'>In Behandeling</option>
            <option value='gesloten'>Gesloten</option>
          </select>
        </div>
      </div>
      
      <div class='pt-4 flex justify-end space-x-3'>
        <button type='button' onclick='closeAddIncidentModal()' class='bg-gray-200 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-300 text-sm'>Annuleren</button>
        <button type='submit' class='bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700 text-sm flex items-center gap-2'>
          <i class='fa-solid fa-paper-plane'></i> Incident Melden
        </button>
      </div>
    </form>
  </div>
</div>

<!-- Modal Details Incident -->
<div id='detailsIncidentModal' class='modal-overlay' role='dialog' aria-modal='true' aria-labelledby='detailsIncidentTitle'>
  <div class='modal-content'>
    <button class='modal-close-btn' aria-label='Sluit details' onclick='closeDetailsIncidentModal()'>&times;</button>
    <h3 id='detailsIncidentTitle' class='flex items-center gap-2'>
      <i class=\"fa-regular fa-file-lines text-blue-500\"></i> 
      Incident Details
    </h3>
    <div id='detailsIncidentContent'>
      <!-- Dynamische content -->
    </div>
  </div>
</div>

<script>
  // Incidents data als JS-array
  const incidentsData = " . json_encode($incidents, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) . ";

  // Modal open/close
  const addIncidentModal = document.getElementById('addIncidentModal');
  const detailsIncidentModal = document.getElementById('detailsIncidentModal');

  function openAddIncidentModal() {
    if (addIncidentModal) addIncidentModal.classList.add('active');
    document.body.style.overflow = 'hidden';
    
    // Stel huidige datum/tijd in als default
    const now = new Date();
    const localDateTime = new Date(now.getTime() - now.getTimezoneOffset() * 60000).toISOString().slice(0, 16);
    document.getElementById('add_incident_datetime').value = localDateTime;
  }
  
  function closeAddIncidentModal() {
    if (addIncidentModal) {
      addIncidentModal.classList.remove('active');
      document.getElementById('addIncidentForm').reset();
      document.body.style.overflow = '';
    }
  }
  
  if (addIncidentModal) {
    addIncidentModal.addEventListener('click', (event) => { 
      if (event.target === addIncidentModal) closeAddIncidentModal(); 
    });
  }

  // Details modal
  function openDetailsIncidentModal(incidentDbId) {
    let incidentEntry = null;
    try {
      const data = Array.isArray(incidentsData) ? incidentsData : [];
      incidentEntry = data.find(inc => String(inc.incidentId) === String(incidentDbId));
    } catch (e) {
      console.error('Error parsing incident data:', e);
    }
    
    const modal = document.getElementById('detailsIncidentModal');
    const container = document.getElementById('detailsIncidentContent');
    
    if (!incidentEntry || !container || !modal) {
      alert('Incident niet gevonden.');
      return;
    }

    // Definieer de velden die we willen tonen met hun labels
    const fieldsToShow = {
      'incidentId': 'Incident ID',
      'incidentCode': 'Incident Code',
      'beschrijving': 'Beschrijving',
      'datum': 'Datum & Tijd',
      'organisatieId': 'Organisatie ID',
      'vluchtId': 'Vlucht ID',
      'pilootId': 'Piloot ID',
      'locatie_latitude': 'Locatie Latitude',
      'locatie_longitude': 'Locatie Longitude',
      'incident_type': 'Type Incident',
      'ernst': 'Ernst',
      'weeromstandigheden': 'Weersomstandigheden',
      'camera_status': 'Camera Status',
      'batterij_status': 'Batterij Status',
      'actie_ondernomen': 'Actie Ondernomen',
      'rapporteur_id': 'Rapporteur ID',
      'status': 'Status',
      'bijlagen': 'Bijlagen'
    };

    container.innerHTML = '';
    
    // Loop door alle velden en toon ze
    for (const [key, label] of Object.entries(fieldsToShow)) {
      let value = incidentEntry[key];
      
      // Als waarde leeg is, toon '-'
      if (value === null || value === undefined || value === '') {
        value = '-';
      } 
      // Bijlagen als links tonen
      else if (key === 'bijlagen' && Array.isArray(value)) {
        value = value.map(url => `<a href=\"${url}\" target=\"_blank\" class=\"text-blue-500 hover:underline\">Bekijk bijlage</a>`).join('<br>');
      } 
      // Datum formaat aanpassen
      else if (key === 'datum') {
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
          console.error('Error formatting date:', e);
        }
      }
      // Locatie als Google Maps link
      else if (key === 'locatie_latitude' && incidentEntry['locatie_longitude']) {
        value = `<a href=\"https://www.google.com/maps/search/?api=1&query=${incidentEntry['locatie_latitude']},${incidentEntry['locatie_longitude']}\" 
                  target=\"_blank\" class=\"text-blue-500 hover:underline\">
                  ${incidentEntry['locatie_latitude']}, ${incidentEntry['locatie_longitude']}
                </a>`;
      }
      // Status met kleurcodering
      else if (key === 'status') {
        const statusColors = {
          'open': 'text-yellow-600 bg-yellow-100',
          'in behandeling': 'text-blue-600 bg-blue-100',
          'gesloten': 'text-green-600 bg-green-100'
        };
        const colorClass = statusColors[value.toLowerCase()] || 'bg-gray-100 text-gray-800';
        value = `<span class=\"px-2 py-1 rounded-full text-xs ${colorClass}\">${value}</span>`;
      }
      // Ernst met kleurcodering
      else if (key === 'ernst') {
        const severityColors = {
          'laag': 'text-green-600 bg-green-100',
          'middel': 'text-yellow-600 bg-yellow-100',
          'hoog': 'text-orange-600 bg-orange-100',
          'kritiek': 'text-red-600 bg-red-100'
        };
        const colorClass = severityColors[value.toLowerCase()] || 'bg-gray-100 text-gray-800';
        value = `<span class=\"px-2 py-1 rounded-full text-xs ${colorClass}\">${value}</span>`;
      }
      
      container.innerHTML += `
        <div class='details-row'>
          <span class='details-label'>${label}:</span>
          <span class='details-value'>${value}</span>
        </div>`;
    }
    
    modal.classList.add('active');
    document.body.style.overflow = 'hidden';
  }
  
  function closeDetailsIncidentModal() {
    if (detailsIncidentModal) {
      detailsIncidentModal.classList.remove('active');
      document.getElementById('detailsIncidentContent').innerHTML = '';
      document.body.style.overflow = '';
    }
  }
  
  if (detailsIncidentModal) {
    detailsIncidentModal.addEventListener('click', (event) => {
      if (event.target === detailsIncidentModal) closeDetailsIncidentModal();
    });
  }
  
  function viewIncidentDetails(id) { 
    openDetailsIncidentModal(id); 
  }

  // Zoek- en filterfuncties
  function filterIncidents() {
    const searchTerm = document.getElementById('searchInput').value.toLowerCase();
    const statusFilter = document.getElementById('statusFilter').value.toLowerCase();
    const severityFilter = document.getElementById('severityFilter').value.toLowerCase();
    
    const table = document.getElementById('incidentsTable');
    if (!table) return;
    
    const trs = table.querySelectorAll('tbody tr');
    trs.forEach(tr => {
      const rowText = tr.textContent.toLowerCase();
      const status = tr.querySelector('td[data-status]')?.dataset.status || '';
      const severity = tr.querySelector('td[data-severity]')?.dataset.severity || '';
      
      const matchesSearch = rowText.includes(searchTerm);
      const matchesStatus = statusFilter === '' || status === statusFilter;
      const matchesSeverity = severityFilter === '' || severity === severityFilter;
      
      tr.style.display = (matchesSearch && matchesStatus && matchesSeverity) ? '' : 'none';
    });
  }
  
  // Event listeners voor filters
  document.getElementById('searchInput')?.addEventListener('input', filterIncidents);
  document.getElementById('statusFilter')?.addEventListener('change', filterIncidents);
  document.getElementById('severityFilter')?.addEventListener('change', filterIncidents);
  
  // Markeer status en ernst kolommen voor filtering
  document.addEventListener('DOMContentLoaded', function() {
    const table = document.getElementById('incidentsTable');
    if (!table) return;
    
    const rows = table.querySelectorAll('tbody tr');
    rows.forEach(row => {
      const cells = row.querySelectorAll('td');
      cells.forEach((cell, index) => {
        const headerText = table.querySelectorAll('thead th')[index].textContent.toLowerCase();
        
        if (headerText.includes('status')) {
          cell.dataset.status = cell.textContent.trim().toLowerCase();
        }
        else if (headerText.includes('ernst')) {
          cell.dataset.severity = cell.textContent.trim().toLowerCase();
        }
      });
    });
  });
</script>
";

// INCLUDE HEADER & TEMPLATE
require_once __DIR__ . '/../../components/header.php';
require_once __DIR__ . '/../layouts/template.php';
