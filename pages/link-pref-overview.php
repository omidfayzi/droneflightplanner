<?php
    include '../functions/functions.php';
    login();

    //define if you want to include the componment setPlotName
    $includeSetPlotName = 0;
    //define if you want to include the componment setPrefName
    $includeSetPrefName = 0;
    //define user name for sidebar
    $user = $_SESSION["user"];
    //define if you want to include the the componment include check with idin
    $includeCheckWithIdin = 1;
    // Define the user name
    $userName = $user['first_name'];
    //define the goback 0/1
    $gobackUrl = 0;
    //select the right attributes for the page
    $rightAttributes = 0;
    // Define the title of the head
    $headTitle = "Voorkeuren koppelen overzicht";
    // Define if the head should be shown or not
    $showHeader = 1;
    //define organisation name to be shown in sidebar
    //$org = $_COOKIE['org_id'];
    $org = "placeholder";

    // this is the body of the webpage
    $bodyContent = "
    <div style='background-color: #AEAEAE;' class='bg-black m-4 p-10 rounded-xl'>
        <div class='flex flex-col justify-center items-center w-full ml-2 mr-2'>
            <svg xmlns='http://www.w3.org/2000/svg' width='80' height='80' fill='#6c6c75' class='mb-3 bi bi-link' viewBox='0 0 16 16'>
            <path d='M6.354 5.5H4a3 3 0 0 0 0 6h3a3 3 0 0 0 2.83-4H9q-.13 0-.25.031A2 2 0 0 1 7 10.5H4a2 2 0 1 1 0-4h1.535c.218-.376.495-.714.82-1z'/>
            <path d='M9 5.5a3 3 0 0 0-2.83 4h1.098A2 2 0 0 1 9 6.5h3a2 2 0 1 1 0 4h-1.535a4 4 0 0 1-.82 1H12a3 3 0 1 0 0-6z'/>
            </svg>
            <h1 class='text-black text-2xl'>Voorkeuren koppelen</h1>
            <h2 class='pt-5 text-stone-800 text-md text-center'>Op deze pagina kun je een voorkeurlijst selecteren en deze koppelen aan een of meerdere percelen. Kies een voorkeurlijst met de gewenste instellingen, zoals vluchtijden, hoogtes en andere voorwaarden, en wijs deze toe aan de bijbehorende percelen. Nadat je de koppeling hebt opgeslagen, kun je deze op elk moment wijzigen of opnieuw bekijken. Hieronder staat een video waarin dit proces wordt uitgelegd.</h2>
            <a href='/link-pref'>
                <button id='link-pref' class='text-white hover:scale-105 transition-all bg-blue-800 mt-7 pl-2 pr-2 p-1 rounded-xl'>
                    Voorkeur koppelen
                </button>
            </a>
        </div>
    </div>
    <div id='prefContainer' style='background-color: rgba(0, 0, 0, 0.13);' class='rounded-xl p-0.5 m-4'>
        <video class='rounded-xl w-full h-4/5 object-cover' autoplay loop muted playsinline>
            <source src='../images/koppelen.mp4' type='video/mp4'>
            Your browser does not support the video tag.
        </video>       
    </div>
    ";

    // Include the base template
    include '../includes/header.php';
?>
