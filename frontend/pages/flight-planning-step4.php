<?php
session_start();
include __DIR__ . '/../includes/header.php';

// Sla de gegevens van stap 3 op in de sessie
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $_SESSION['flight_planning']['permits'] = $_POST['permits'] ?? [];
    // Bestandsuploadverwerking wordt hier niet uitgebreid behandeld.
}
?>
<div class="container mx-auto p-6 text-center">
  <div class="max-w-2xl mx-auto">
    <div class="w-16 h-16 bg-black/10 rounded-full flex items-center justify-center mx-auto mb-6">
      <i class="fa-solid fa-check text-2xl text-black"></i>
    </div>
    <h2 class="text-2xl font-bold mb-4">Luchtplanning succesvol ingediend</h2>
    <p class="text-lg text-black mb-8">Uw aanvraag is succesvol ingediend. U ontvangt spoedig een bevestiging.</p>
    <div class="flex justify-center space-x-4">
      <a href="/frontend/pages/dashboard.php" class="bg-black text-white px-6 py-3 rounded-lg hover:bg-gray-700 transition-colors">
        Details bekijken
      </a>
      <a href="/frontend/pages/dashboard.php" class="border border-black px-6 py-3 rounded-lg hover:border-gray-700 transition-colors">
        Sluiten
      </a>
    </div>
  </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
