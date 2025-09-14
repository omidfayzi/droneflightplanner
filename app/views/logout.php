<?php
// =============== LOGOUT ===============
// Doel: gebruiker uitloggen in DroneFlightPlanner

session_start();
session_unset();    // alle sessie-variabelen leeg
session_destroy();  // sessie sluiten

// Optioneel: ook bij Keycloak zelf uitloggen via hun logout-URL
// Nu redirect alleen terug naar de applicatie
header('Location: https://app.droneperceelvoorkeuren.nl');
exit();
