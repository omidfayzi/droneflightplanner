<?php
// Dit bestand heeft geen complexe PHP-logica nodig, dus we houden het simpel.
// We starten de sessie om eventueel later de gebruikersnaam te kunnen tonen.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="nl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Juridische Informatie - Drone Flight Planner</title>

    <!-- Externe stylesheets voor een professionele uitstraling -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">

    <!-- Link naar het aparte CSS-bestand voor deze pagina -->
    <link rel="stylesheet" href="/app/assets/styles/legal-styling.css">
</head>

<body>
    <div class="container-wrapper">
        <div class="text-center mb-6">
            <h1 class="text-3xl font-extrabold text-gray-900">Privacy & AVG</h1>
            <p class="text-gray-600 mt-2">Hoe wij omgaan met jouw gegevens</p>
        </div>

        <!-- Navigatietabs om te wisselen tussen Privacy en AVG -->
        <ul class="nav nav-tabs" id="legalTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="privacy-tab" data-bs-toggle="tab" data-bs-target="#privacy" type="button" role="tab">Privacyverklaring</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="avg-tab" data-bs-toggle="tab" data-bs-target="#avg" type="button" role="tab">AVG-verantwoording</button>
            </li>
        </ul>

        <div class="tab-content" id="legalContent">
            <!-- Tab 1: Privacyverklaring (Simpele uitleg voor de gebruiker) -->
            <div class="tab-pane fade show active" id="privacy" role="tabpanel">
                <div class="content-section">
                    <h2>Onze Belofte aan Jouw Privacy</h2>
                    <p>
                        Bij Drone Flight Planner nemen we jouw privacy serieus. We hebben onze applicatie zo gebouwd dat we zo min mogelijk gegevens van je nodig hebben. Hieronder leggen we in duidelijke taal uit wat dit voor jou betekent.
                    </p>

                    <div class="highlight">
                        <p><strong>De kern:</strong> We verzamelen alleen de data die strikt noodzakelijk is om de app te laten werken: je naam, e-mail en gebruikers-ID.</p>
                    </div>

                    <h3>Jouw Wachtwoord is Veilig (want we hebben het niet)</h3>
                    <p>
                        Je logt in via Keycloak, een externe en zwaar beveiligde partij. Wij slaan jouw wachtwoord **nooit** op. Dit is de veiligste aanpak.
                    </p>

                    <h3>Geen Tracking, Geen Advertenties</h3>
                    <p>
                        Wij gebruiken geen tracking cookies en delen jouw gegevens niet met adverteerders of andere derde partijen. Jouw data wordt alleen gebruikt binnen de Drone Flight Planner applicatie.
                    </p>
                </div>
            </div>

            <!-- Tab 2: AVG-verantwoording (Uitleg hoe we aan de wet voldoen) -->
            <div class="tab-pane fade" id="avg" role="tabpanel">
                <div class="content-section">
                    <h2>Hoe wij voldoen aan de AVG</h2>
                    <p>
                        De Drone Flight Planner is ontwikkeld volgens de principes van 'Privacy by Design'. Hieronder lichten we toe hoe we voldoen aan de belangrijkste eisen van de AVG-wetgeving.
                    </p>

                    <ol class="mt-4">
                        <li>
                            <strong>Dataminimalisatie:</strong> We verwerken alleen persoonsgegevens die essentieel zijn voor de functionaliteit: gebruikers-ID, naam en e-mail. Er worden geen gevoelige persoonsgegevens verwerkt.
                        </li>
                        <li>
                            <strong>Beveiliging:</strong> Wachtwoorden worden extern beheerd door Keycloak. Gevoelige configuratie (zoals database-wachtwoorden) staat in een `.env` bestand, gescheiden van de code.
                        </li>
                        <li>
                            <strong>Transparantie en Rechten:</strong> Gebruikers kunnen hun eigen basisgegevens inzien op de `profile.php` pagina. Deze pagina legt ook uit welke gegevens worden verwerkt.
                        </li>
                        <li>
                            <strong>Incidentenbeheer:</strong> We hebben een intern proces om eventuele datalekken te detecteren en, indien nodig, binnen 72 uur te melden bij de Autoriteit Persoonsgegevens.
                        </li>
                    </ol>
                </div>
            </div>
        </div>

        <footer>
            <a href="/dashboard" class="btn-action btn-primary">
                <i class="fas fa-arrow-left"></i> Terug naar het dashboard
            </a>
            <p class="mt-4">&copy; <?= date("Y") ?> Drone Flight Planner. Alle rechten voorbehouden.</p>
        </footer>
    </div>

    <!-- JavaScript voor de tabs -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Activeer de Bootstrap tabs functionaliteit
        const triggerTabList = document.querySelectorAll('#legalTabs button');
        triggerTabList.forEach(triggerEl => {
            const tabTrigger = new bootstrap.Tab(triggerEl);
            triggerEl.addEventListener('click', event => {
                event.preventDefault();
                tabTrigger.show();
            });
        });
    </script>
</body>

</html>