<?php
include '../functions/functions.php';
login();

// Stel parameters in voor de dashboardpagina
$includeSetPlotName = 0;
$includeSetPrefName = 0;
$includeCheckWithIdin = 1;
$user = $_SESSION["user"];
$userName = $user['first_name'];
$gobackUrl = 0;
$rightAttributes = 0;
$headTitle = "Dashboard";
$showHeader = 1;

// Body content: Dashboard layout
$bodyContent = "
<div class='container mx-auto p-6 border-solid border-2 border-gray-200 rounded-xl'>
  <!-- KPI Grid -->
  <div class='grid grid-cols-1 md:grid-cols-3 gap-6 mb-8'>
    <div class='bg-white p-6 rounded-xl shadow hover:shadow-lg transition'>
      <div class='flex justify-between items-center'>
        <div>
          <p class='text-sm text-gray-500 mb-1'>Actieve Vluchten</p>
          <p class='text-3xl font-bold text-gray-800'>3</p>
        </div>
        <div class='w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center'>
          <i class='fa-solid fa-rocket text-blue-700'></i>
        </div>
      </div>
    </div>
    <div class='bg-white p-6 rounded-xl shadow hover:shadow-lg transition'>
      <div class='flex justify-between items-center'>
        <div>
          <p class='text-sm text-gray-500 mb-1'>Wachtend op Goedkeuring</p>
          <p class='text-3xl font-bold text-gray-800'>2</p>
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
          <p class='text-3xl font-bold text-gray-800'>127</p>
        </div>
        <div class='w-12 h-12 bg-green-100 rounded-full flex items-center justify-center'>
          <i class='fa-solid fa-chart-line text-green-700'></i>
        </div>
      </div>
    </div>
  </div>

  <!-- Recente Vluchten Tabel -->
  <div class='bg-white rounded-xl shadow overflow-hidden'>
    <div class='p-6 border-b border-gray-200 flex justify-between items-center'>
      <h3 class='text-xl font-semibold text-gray-800'>Recente Operaties</h3>
      <button class='flex items-center text-blue-600 hover:text-blue-800 transition'>
        <i class='fa-solid fa-plus mr-2'></i> Nieuwe Vlucht
      </button>
    </div>
    <div class='overflow-x-auto'>
      <table class='w-full'>
        <thead class='bg-gray-100 text-sm'>
          <tr>
            <th class='p-4 text-left text-gray-600'>Vlucht ID</th>
            <th class='p-4 text-left text-gray-600'>Type</th>
            <th class='p-4 text-left text-gray-600'>Locatie</th>
            <th class='p-4 text-left text-gray-600'>Uitgevoerd door</th>
            <th class='p-4 text-left text-gray-600'>Status</th>
            <th class='p-4'></th>
          </tr>
        </thead>
        <tbody class='divide-y divide-gray-200 text-sm'>
          <template x-for='flight in recentFlights' :key='flight.id'>
            <tr class='hover:bg-gray-50 transition'>
              <td class='p-4 font-medium text-gray-800' x-text='flight.id'></td>
              <td class='p-4 text-gray-600' x-text='flight.type'></td>
              <td class='p-4 text-gray-600' x-text='flight.location'></td>
              <td class='p-4 text-gray-600' x-text='flight.operator'></td>
              <td class='p-4'>
                <span :class='flight.statusClass + \" px-3 py-1 rounded-full text-sm font-medium\"' x-text='flight.status'></span>
              </td>
              <td class='p-4 text-right'>
                <button class='text-gray-600 hover:text-gray-800 transition'>
                  <i class='fa-solid fa-ellipsis-vertical'></i>
                </button>
              </td>
            </tr>
          </template>
        </tbody>
      </table>
    </div>
  </div>
</div>
";

// Include de header die de volledige HTML-opbouw verzorgt
include '../includes/header.php';
?>

<script>
// Bestaande Alpine.js data en navigatie kunnen hier worden geïnitialiseerd
function app() {
  return {
    currentPage: 'dashboard',
    currentPageLabel: 'Dashboard',
    recentFlights: [
      { id: '#FL-2309', type: 'Objectinspectie', location: 'Windmolenpark Eemmeerdijk', operator: 'J. van den Berg', status: 'Voltooid', statusClass: 'bg-green-100 text-green-800' },
      { id: '#FL-2310', type: 'BVLOS Route', location: 'A12 Corridor', operator: 'M. de Vries', status: 'In behandeling', statusClass: 'bg-yellow-100 text-yellow-800' },
      { id: '#FL-2311', type: 'Thermische Scan', location: 'Industrieterrein Twente', operator: 'A. Bakker', status: 'Gepland', statusClass: 'bg-blue-100 text-blue-800' },
      { id: '#FL-2312', type: 'Noodinspectie', location: 'Haven Rotterdam', operator: 'P. Jansen', status: 'Mislukt', statusClass: 'bg-red-100 text-red-800' }
    ]
  }
}
</script>
