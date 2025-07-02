<!DOCTYPE html>
<html lang="nl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Juridische documenten - Drone Flight Planner</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body {
            background-color: #f5f7fa;
            font-family: 'Montserrat', sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
            padding: 20px;
            background-image: url('/app/assets/images/droneBackgroundImage.jpg');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            backdrop-filter: blur(5px) brightness(0.7);
        }

        .container-wrapper {
            background-color: rgba(255, 255, 255, 0.98);
            border-radius: 12px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            padding: 40px;
            width: 100%;
            max-width: 800px;
            display: flex;
            flex-direction: column;
            position: relative;
            overflow: visible;
            margin: 20px 0;
        }

        .drone-visual-element {
            width: 220px;
            height: auto;
            position: absolute;
            top: 5%;
            right: 20%;
            z-index: 100;
            filter: drop-shadow(0 10px 20px rgba(0, 0, 0, 0.3));
            pointer-events: none;
        }

        h1,
        h2 {
            color: #2c3e50;
            font-weight: 700;
        }

        h1 {
            font-size: 2.2rem;
            margin-bottom: 1rem;
            border-bottom: 2px solid #e2e8f0;
            padding-bottom: 0.5rem;
        }

        h2 {
            font-size: 1.5rem;
            margin-top: 2rem;
            margin-bottom: 1rem;
            color: #3b82f6;
        }

        p,
        li {
            color: #4a5568;
            line-height: 1.6;
            font-size: 1rem;
        }

        a {
            color: #3b82f6;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.2s;
        }

        a:hover {
            color: #2563eb;
            text-decoration: underline;
        }

        .nav-tabs {
            margin-bottom: 30px;
            border-bottom: 2px solid #e2e8f0;
        }

        .nav-link {
            font-weight: 600;
            color: #4a5568;
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 8px 8px 0 0;
            margin-right: 5px;
            transition: all 0.2s;
        }

        .nav-link.active,
        .nav-link:hover {
            color: #3b82f6;
            background-color: rgba(59, 130, 246, 0.1);
            border: none;
        }

        .content-section {
            background: #f8fafc;
            border-radius: 10px;
            padding: 25px;
            margin-bottom: 25px;
            border-left: 4px solid #3b82f6;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        }

        ol {
            counter-reset: item;
            padding-left: 0;
        }

        ol li {
            display: block;
            margin-bottom: 1rem;
            padding-left: 2.5rem;
            position: relative;
        }

        ol li:before {
            content: counter(item) ".";
            counter-increment: item;
            position: absolute;
            left: 0;
            top: 0;
            background: #3b82f6;
            color: white;
            width: 26px;
            height: 26px;
            border-radius: 50%;
            text-align: center;
            line-height: 26px;
            font-weight: 700;
            font-size: 0.9rem;
        }

        .btn-action {
            padding: 10px 20px;
            font-size: 0.95rem;
            border-radius: 8px;
            transition: all 0.2s;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-primary {
            background-color: #3b82f6;
            color: white;
            border-color: #3b82f6;
        }

        .btn-primary:hover {
            background-color: #2563eb;
            transform: translateY(-2px);
        }

        footer {
            margin-top: 50px;
            padding-top: 20px;
            color: #718096;
            font-size: 0.9rem;
            border-top: 1px solid #e2e8f0;
            text-align: center;
        }

        .contact-box {
            background: #e6f2ff;
            border-radius: 10px;
            padding: 20px;
            margin-top: 30px;
            text-align: center;
        }

        .highlight {
            background: linear-gradient(120deg, #e0f2fe 0%, #dbeafe 100%);
            border-radius: 8px;
            padding: 15px;
            margin: 15px 0;
            border-left: 3px solid #3b82f6;
        }
    </style>
</head>

<body>
    <img src="/app/assets/images/drone.png" alt="Drone Visual Element" class="drone-visual-element">

    <div class="container-wrapper">
        <div class="text-center mb-6">
            <h1 class="text-3xl font-extrabold text-gray-900">Juridische Documenten</h1>
            <p class="text-gray-600 mt-2">Informatie over privacy en gegevensbescherming</p>
        </div>

        <!-- Navigatietabs -->
        <ul class="nav nav-tabs" id="legalTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="privacy-tab" data-bs-toggle="tab" data-bs-target="#privacy" type="button" role="tab">Privacyverklaring</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="avg-tab" data-bs-toggle="tab" data-bs-target="#avg" type="button" role="tab">AVG-verantwoording</button>
            </li>
        </ul>

        <div class="tab-content" id="legalContent">
            <!-- Privacyverklaring Tab -->
            <div class="tab-pane fade show active" id="privacy" role="tabpanel">
                <div class="content-section">
                    <h2>Privacyverklaring Drone Flight Planner</h2>
                    <p>
                        Drone Flight Planner verwerkt alleen persoonsgegevens die noodzakelijk zijn voor het gebruik van de applicatie.
                        Onze aanpak is gebaseerd op het principe van minimale gegevensverwerking en transparantie.
                    </p>

                    <div class="highlight">
                        <p><i class="fas fa-shield-alt text-blue-500"></i> <strong>Minimale gegevensverwerking:</strong> Wij verwerken alleen naam, e-mailadres en gebruikersrol.</p>
                    </div>
                    <br>
                    <h3>Authenticatie & Beveiliging</h3>
                    <p>
                        Authenticatie vindt plaats via een externe Identity Provider (Keycloak), waardoor wachtwoorden niet binnen deze applicatie worden verwerkt of opgeslagen.
                    </p>
                    <br>
                    <h3>Gegevensopslag & Toegang</h3>
                    <p>
                        Uw gegevens worden alleen opgeslagen tijdens een actieve sessie en zijn alleen zichtbaar voor u en, indien van toepassing, uw organisatiebeheerder.
                    </p>
                    <br>
                    <h3>Tracking & Delen</h3>
                    <p>
                        Drone Flight Planner maakt geen gebruik van:
                    </p>
                    <ul class="mt-2">
                        <li><i class="fas fa-times text-red-500"></i> Tracking cookies of analytics</li>
                        <li><i class="fas fa-times text-red-500"></i> Profiling of advertenties</li>
                        <li><i class="fas fa-times text-red-500"></i> Delen van persoonsgegevens met derden</li>
                    </ul>

                    <div class="contact-box mt-5">
                        <h3 class="text-xl font-semibold mb-3">Vragen over uw privacy?</h3>
                        <p>Neem contact op via:</p>
                        <a href="mailto:beheer@droneflightplanner.nl" class="btn btn-primary mt-3">
                            <i class="fas fa-envelope"></i> beheer@droneflightplanner.nl
                        </a>
                    </div>
                </div>
            </div>

            <!-- AVG Tab -->
            <div class="tab-pane fade" id="avg" role="tabpanel">
                <div class="content-section">
                    <h2>AVG-verantwoording Drone Flight Planner</h2>
                    <p>
                        Deze pagina beschrijft hoe de Drone Flight Planner voldoet aan de eisen van de Algemene Verordening Gegevensbescherming (AVG/GDPR).
                    </p>

                    <h3>Belangrijkste maatregelen</h3>
                    <ol class="mt-4">
                        <li><strong>Minimale gegevensopslag:</strong> Alleen naam, e-mailadres, gebruikers-ID en rol worden server-side bewaard. Geen wachtwoorden, geboortedata of bijzondere persoonsgegevens.</li>
                        <li><strong>Externe authenticatie:</strong> Wachtwoorden worden niet in de applicatie verwerkt of opgeslagen. Het beheer ligt bij de externe Identity Provider.</li>
                        <li><strong>Server-side opslag:</strong> Persoonsgegevens en tokens worden uitsluitend server-side opgeslagen, niet in cookies of in de browser.</li>
                        <li><strong>Toegangsbeheer:</strong> Toegang tot data is beperkt tot de eigen organisatie en rol.</li>
                        <li><strong>Beveiliging:</strong> CSRF-bescherming en sessiebeveiliging zijn geïmplementeerd.</li>
                        <li><strong>Geen tracking:</strong> Geen analytics, advertentietracking of ongewenste koppelingen met derden.</li>
                        <li><strong>Foutafhandeling:</strong> Geen gevoelige gegevens in foutmeldingen of logs.</li>
                        <li><strong>Data portabiliteit:</strong> Gebruikersgegevens zijn zichtbaar en te beheren via het profielscherm.</li>
                        <li><strong>Bewaartermijnen:</strong> Gegevens worden alleen tijdens actieve sessies bewaard en verwijderd na uitloggen of sessieverloop.</li>
                        <li><strong>Verwerkersovereenkomst:</strong> Indien van toepassing, wordt een verwerkersovereenkomst afgesloten met technische beheerders of hostingpartijen.</li>
                    </ol>

                    <div class="highlight mt-5">
                        <p><i class="fas fa-info-circle text-blue-500"></i> <strong>Transparantie:</strong> Alle verwerkingen zijn gedocumenteerd en inzichtelijk voor gebruikers.</p>
                    </div>

                    <div class="contact-box mt-5">
                        <h3 class="text-xl font-semibold mb-3">Vragen of verzoeken?</h3>
                        <p>Neem gerust contact op via:</p>
                        <a href="mailto:beheer@droneflightplanner.nl" class="btn btn-primary mt-3">
                            <i class="fas fa-envelope"></i> beheer@droneflightplanner.nl
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <footer>
            <div class="flex justify-center items-center gap-4 mb-3">
                <a href="/app/views/dashboard.php" class="btn-action btn-primary">
                    <i class="fas fa-arrow-left"></i> Terug naar dashboard
                </a>
            </div>
            <p>&copy; <?php echo date("Y"); ?> Drone Flight Planner. Alle rechten voorbehouden.</p>
        </footer>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Activeer de Bootstrap tabs
        const triggerTabList = [].slice.call(document.querySelectorAll('#legalTabs button'))
        triggerTabList.forEach(triggerEl => {
            const tabTrigger = new bootstrap.Tab(triggerEl)

            triggerEl.addEventListener('click', event => {
                event.preventDefault()
                tabTrigger.show()
            })
        })

        // Toon standaard de privacyverklaring
        document.getElementById('privacy-tab').click();
    </script>
</body>

</html>