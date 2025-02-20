<?php
session_start();
include __DIR__ . '/../includes/header.php';

// Sla de gegevens van stap 2 op in de sessie
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $_SESSION['flight_planning']['risk_score'] = $_POST['risk_score'] ?? '';
    $_SESSION['flight_planning']['risk_comments'] = $_POST['risk_comments'] ?? '';
}
?>
<div class="container mx-auto p-6">
  <h2 class="text-2xl font-bold mb-4">Stap 3: Goedkeuring aanvragen</h2>
  <form action="flight-planning-step4.php" method="post" enctype="multipart/form-data" class="space-y-4">
    <div>
      <label class="block text-sm font-medium mb-1">Vereiste Vergunningen</label>
      <div class="space-y-2">
        <div class="flex items-center">
          <input type="checkbox" name="permits[]" value="luchtruim" class="mr-2">
          <span>Luchtruimtoestemming</span>
        </div>
        <div class="flex items-center">
          <input type="checkbox" name="permits[]" value="privacy" class="mr-2">
          <span>Privacyverklaring</span>
        </div>
        <div class="flex items-center">
          <input type="checkbox" name="permits[]" value="risico" class="mr-2">
          <span>Risicoacceptatie</span>
        </div>
      </div>
    </div>
    <div>
      <label class="block text-sm font-medium mb-1">Documentupload</label>
      <input type="file" name="documents[]" multiple class="w-full p-3 border rounded-lg">
    </div>
    <div class="flex justify-between">
      <a href="flight-planning-step2.php" class="text-black hover:underline">← Vorige stap</a>
      <button type="submit" class="bg-black text-white px-6 py-3 rounded-lg hover:bg-gray-700 transition-colors">
        Aanvraag indienen <i class="fa-solid fa-arrow-right ml-2"></i>
      </button>
    </div>
  </form>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
