<?php
session_start();
include __DIR__ . '/../includes/header.php';

// Sla de gegevens van stap 1 op in de sessie
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $_SESSION['flight_planning']['flight_type'] = $_POST['flight_type'] ?? '';
    $_SESSION['flight_planning']['start_location'] = $_POST['start_location'] ?? '';
    $_SESSION['flight_planning']['destination'] = $_POST['destination'] ?? '';
    $_SESSION['flight_planning']['flight_datetime'] = $_POST['flight_datetime'] ?? '';
}
?>
<div class="container mx-auto p-6">
  <h2 class="text-2xl font-bold mb-4">Stap 2: Risicoanalyse</h2>
  <form action="flight-planning-step3.php" method="post" class="space-y-4">
    <div>
      <label class="block text-sm font-medium mb-1">Risico Score</label>
      <input type="number" name="risk_score" class="w-full p-3 border rounded-lg" placeholder="Voer de risico score in" min="0" max="10" required>
    </div>
    <div>
      <label class="block text-sm font-medium mb-1">Risico Opmerkingen</label>
      <textarea name="risk_comments" class="w-full p-3 border rounded-lg" placeholder="Voer eventuele opmerkingen in"></textarea>
    </div>
    <div class="flex justify-between">
      <a href="flight-planning-step1.php" class="text-black hover:underline">← Vorige stap</a>
      <button type="submit" class="bg-black text-white px-6 py-3 rounded-lg hover:bg-gray-700 transition-colors">
        Volgende stap <i class="fa-solid fa-arrow-right ml-2"></i>
      </button>
    </div>
  </form>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
