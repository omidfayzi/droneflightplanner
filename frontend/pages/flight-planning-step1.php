<?php
session_start();
include __DIR__ . '/../includes/header.php';
?>
<div class="container mx-auto p-6">
  <h2 class="text-2xl font-bold mb-4">Stap 1: Basisgegevens</h2>
  <form action="flight-planning-step2.php" method="post" class="space-y-4">
    <div>
      <label class="block text-sm font-medium mb-1">Vluchttype</label>
      <select name="flight_type" class="w-full p-3 border rounded-lg" required>
        <option value="">Selecteer vluchttype</option>
        <option value="bvlos">BVLOS Routevlucht</option>
        <option value="objectinspectie">Objectinspectie</option>
        <option value="thermische">Thermische Scanning</option>
      </select>
    </div>
    <div>
      <label class="block text-sm font-medium mb-1">Startlocatie</label>
      <input type="text" name="start_location" class="w-full p-3 border rounded-lg" placeholder="Voer startlocatie in" required>
    </div>
    <div>
      <label class="block text-sm font-medium mb-1">Bestemming</label>
      <input type="text" name="destination" class="w-full p-3 border rounded-lg" placeholder="Voer bestemming in" required>
    </div>
    <div>
      <label class="block text-sm font-medium mb-1">Datum en Tijd</label>
      <input type="datetime-local" name="flight_datetime" class="w-full p-3 border rounded-lg" required>
    </div>
    <div class="flex justify-end">
      <button type="submit" class="bg-black text-white px-6 py-3 rounded-lg hover:bg-gray-700 transition-colors">
        Volgende <i class="fa-solid fa-arrow-right ml-2"></i>
      </button>
    </div>
  </form>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
