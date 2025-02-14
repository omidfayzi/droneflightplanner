<?php
include '../functions/functions.php';
login();

include '../functions/functions.php';
login();
$showHeader = 1;
$headTitle = "Vluchtplanning";
$bodyContent = "<div class='p-6'><h1 class='text-3xl font-bold'>Vluchtplanning</h1><p>Dit is de vluchtplanning pagina.</p></div>";
include '../includes/header.php';

// Stel de benodigde variabelen in voor deze pagina
$includeSetPlotName = 0;
$includeSetPrefName = 0;
$includeCheckWithIdin = 0;
$user = $_SESSION["user"];
$userName = $user['first_name'];
$gobackUrl = 1;
$rightAttributes = 0;
$headTitle = "Vluchtplanning";
$showHeader = 1;

// Definieer de body content van de vluchtplanning-pagina
$bodyContent = "
<div x-data='flightPlanning()' class='container mx-auto p-6'>
  <!-- Stappen Indicator -->
  <div class='mb-8'>
    <div class='flex items-center justify-center'>
      <template x-for='(step, index) in flightSteps' :key='index'>
        <div class='flex items-center'>
          <div class='w-8 h-8 rounded-full flex items-center justify-center transition-colors'
               :class='currentFlightStep > index ? \"bg-gray-800 text-white\" : \"bg-gray-300 text-gray-800\"'>
            <span x-text='index + 1'></span>
          </div>
          <div x-show='index !== flightSteps.length - 1' class='w-16 h-1 bg-gray-300'
               :class='currentFlightStep > index + 1 ? \"bg-gray-800\" : \"bg-gray-300\"'></div>
        </div>
      </template>
    </div>
  </div>

  <!-- Stap 1: Basisgegevens -->
  <div x-show='currentFlightStep === 1' class='bg-white rounded-xl shadow p-6'>
    <h3 class='text-2xl font-bold mb-4'>Basisgegevens</h3>
    <form>
      <div class='mb-4'>
        <label class='block text-gray-700 mb-2'>Vluchttype</label>
        <select class='w-full p-3 border border-gray-300 rounded-lg'>
          <option value='bvlos'>BVLOS Routevlucht</option>
          <option value='object'>Objectinspectie</option>
          <option value='thermal'>Thermische Scan</option>
        </select>
      </div>
      <div class='mb-4'>
        <label class='block text-gray-700 mb-2'>Datum en Tijd</label>
        <input type='datetime-local' class='w-full p-3 border border-gray-300 rounded-lg'>
      </div>
      <div class='flex justify-end'>
        <button type='button' @click='nextStep()' class='bg-blue-700 hover:bg-blue-800 text-white py-2 px-4 rounded-lg transition'>
          Volgende
        </button>
      </div>
    </form>
  </div>

  <!-- Stap 2: Risicoanalyse -->
  <div x-show='currentFlightStep === 2' class='bg-white rounded-xl shadow p-6'>
    <h3 class='text-2xl font-bold mb-4'>Risicoanalyse</h3>
    <p class='mb-4 text-gray-700'>Controleer de SAIL score en de aanbevelingen:</p>
    <div class='bg-gray-100 p-4 rounded-lg mb-4'>
      <p class='text-lg font-semibold'>SAIL Score: 1.7</p>
      <p class='text-sm text-gray-600'>Maximaal toegestaan: 2.0</p>
    </div>
    <div class='flex justify-between'>
      <button type='button' @click='prevStep()' class='text-gray-600 hover:text-gray-800 transition'>
        ← Vorige
      </button>
      <button type='button' @click='nextStep()' class='bg-blue-700 hover:bg-blue-800 text-white py-2 px-4 rounded-lg transition'>
        Volgende →
      </button>
    </div>
  </div>

  <!-- Stap 3: Goedkeuring aanvragen -->
  <div x-show='currentFlightStep === 3' class='bg-white rounded-xl shadow p-6'>
    <h3 class='text-2xl font-bold mb-4'>Goedkeuring aanvragen</h3>
    <div class='mb-4'>
      <label class='block text-gray-700 mb-2'>Selecteer benodigde vergunningen:</label>
      <div class='flex flex-col space-y-2'>
        <label class='flex items-center'>
          <input type='checkbox' class='mr-2'>
          <span>Luchtruimtoestemming</span>
        </label>
        <label class='flex items-center'>
          <input type='checkbox' class='mr-2'>
          <span>Privacyverklaring</span>
        </label>
        <label class='flex items-center'>
          <input type='checkbox' class='mr-2'>
          <span>Risicoacceptatie</span>
        </label>
      </div>
    </div>
    <div class='flex justify-between'>
      <button type='button' @click='prevStep()' class='text-gray-600 hover:text-gray-800 transition'>
        ← Vorige
      </button>
      <button type='button' @click='nextStep()' class='bg-blue-700 hover:bg-blue-800 text-white py-2 px-4 rounded-lg transition'>
        Volgende →
      </button>
    </div>
  </div>

  <!-- Stap 4: Bevestiging -->
  <div x-show='currentFlightStep === 4' class='bg-white rounded-xl shadow p-6 text-center'>
    <div class='w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4'>
      <i class='fa-solid fa-check text-green-700 text-2xl'></i>
    </div>
    <h3 class='text-2xl font-bold mb-4'>Vlucht succesvol ingediend!</h3>
    <p class='text-gray-700 mb-6'>Wacht op akkoord van het UTM.</p>
    <button type='button' @click='finish()' class='bg-blue-700 hover:bg-blue-800 text-white py-2 px-6 rounded-lg transition'>
      Details bekijken
    </button>
  </div>
</div>
";

include '../includes/header.php';
?>

<script>
function flightPlanning() {
  return {
    currentFlightStep: 1,
    flightSteps: [
      { id: 1, label: 'Basisgegevens' },
      { id: 2, label: 'Risicoanalyse' },
      { id: 3, label: 'Goedkeuring aanvragen' },
      { id: 4, label: 'Bevestiging' }
    ],
    nextStep() {
      if(this.currentFlightStep < this.flightSteps.length) {
        this.currentFlightStep++;
      }
    },
    prevStep() {
      if(this.currentFlightStep > 1) {
        this.currentFlightStep--;
      }
    },
    finish() {
      alert('Vluchtplanning voltooid! Redirect naar dashboard...');
      window.location.href = "../dashboard";
    }
  }
}
</script>
