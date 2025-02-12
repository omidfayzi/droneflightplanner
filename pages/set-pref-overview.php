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
    $headTitle = "Voorkeuren overzicht";
    // Define if the head should be shown or not
    $showHeader = 1;
    //define organisation name to be shown in sidebar
    //$org = $_COOKIE['org_id'];
    $org = "placeholder";

    // this is the body of the webpage
    $bodyContent = "
    <div style='background-color: #AEAEAE;' class='bg-black m-4 p-10 rounded-xl'>
        <div class='flex flex-col justify-center items-center w-full ml-2 mr-2'>
            <svg xmlns='http://www.w3.org/2000/svg' width='80' height='80' fill='#71706e' class='mb-3 bi bi-gear-fill' viewBox='0 0 16 16'>
            <path d='M9.405 1.05c-.413-1.4-2.397-1.4-2.81 0l-.1.34a1.464 1.464 0 0 1-2.105.872l-.31-.17c-1.283-.698-2.686.705-1.987 1.987l.169.311c.446.82.023 1.841-.872 2.105l-.34.1c-1.4.413-1.4 2.397 0 2.81l.34.1a1.464 1.464 0 0 1 .872 2.105l-.17.31c-.698 1.283.705 2.686 1.987 1.987l.311-.169a1.464 1.464 0 0 1 2.105.872l.1.34c.413 1.4 2.397 1.4 2.81 0l.1-.34a1.464 1.464 0 0 1 2.105-.872l.31.17c1.283.698 2.686-.705 1.987-1.987l-.169-.311a1.464 1.464 0 0 1 .872-2.105l.34-.1c1.4-.413 1.4-2.397 0-2.81l-.34-.1a1.464 1.464 0 0 1-.872-2.105l.17-.31c.698-1.283-.705-2.686-1.987-1.987l-.311.169a1.464 1.464 0 0 1-2.105-.872zM8 10.93a2.929 2.929 0 1 1 0-5.86 2.929 2.929 0 0 1 0 5.858z'/>
            </svg>
            <h1 class='text-black text-2xl'>Jouw voorkeuren</h1>
            <h2 class='pt-5 text-stone-800 text-md text-center'>Op deze pagina kun je jouw voorkeuren bekijken en instellen. Dit doe je door de beschikbare opties te selecteren en aan te passen naar jouw wensen. Je kunt bijvoorbeeld voorkeuren instellen voor vluchtijden, hoogtes en andere relevante voorwaarden. Nadat je jouw instellingen hebt opgeslagen, kun je ze op elk moment wijzigen of opnieuw bekijken.</h2>
            <a href='/set-pref'>
                <button id='set-pref' class='text-white hover:scale-105 transition-all bg-blue-800 mt-7 pl-2 pr-2 p-1 rounded-xl'>
                    Voorkeur instellen
                </button>
            </a>
        </div>
    </div>
    <div id='prefContainer' class='m-4 grid sm:grid-cols-3 grid-cols-1 gap-4'>
            
    </div>
    ";

    // Include the base template
    include '../includes/header.php';
?>
<script>
    const keycloakId = "<?php echo $user['id'] ?>"; // get keycloak id
    function getIdForInsertOfUpdate(type, id) {
    if(type == "usr") {
        return keycloakId;
    } else {
        return id;
    }
}

const idForInsert = getIdForInsertOfUpdate(getCookie('usr_type'), getCookie('org_id'));
var checkWithIdin = document.getElementById("checkWithIdin"); // get element by id so the specific element in conponments folder can be shown or hidden dynamically
const idPlots = []; // placeholder array for idPlots data

// Fetch all plots and show them in the dashboard
async function fetchAllPref() {
    const apiUrlPreferences = getAllPreferences; // API URL for fetching preferences
    const url = getAllPrefList + idForInsert; // Define the URL of your GET endpoint

    // Fetch preferences data
    const fetchJson = async (url) => {
        const response = await fetch(url, { method: 'GET', headers: { 'Content-Type': 'application/json' } });
        if (!response.ok) throw new Error(`Error fetching data: ${response.statusText}`); // Check if response was successful
        return await response.json(); // Return parsed JSON
    };

    try {
        // First, fetch the preflist data to get unique names and ids
        const prefListData = await fetchJson(url); // Fetch preflist data from the specified endpoint

        // Filter and extract PrefList IDs and long names
        const prefListNames = prefListData?.data?.filter(prefList => prefList?.PropPrefUsrSets_Name)
            .map(prefList => ({ id: prefList?.PropPrefUsrSets_Pref_Id, longName: prefList?.PropPrefUsrSets_Name })) || [];

        // Remove duplicates based on the longName
        const uniquePrefListNames = Array.from(new Set(prefListNames.map(a => a.longName)))
            .map(longName => prefListNames.find(a => a.longName === longName));

        //console.log('Unique PrefList Names:', uniquePrefListNames);

        // Now retrieve preferences data based on the filtered IDs
        const preferencesData = await fetchJson(apiUrlPreferences); // Retrieve preferences data

        // For each unique longName, create elements and fetch the associated preferences
        for (let index = 0; index < uniquePrefListNames.length; index++) {
            // Create a container element for the plot and preferences
            const prefList = uniquePrefListNames[index];

            const div = `
                <div style="background-color: rgba(0, 0, 0, 0.13);" class="p-3 rounded-xl w-full h-full">
                   <h3 class="text-white">${prefList.longName}</h3>
                    <div id="sub-element-${index}" class="h-52 overflow-scroll sub-element-${index}">
                    </div>
                </div>
            `;

            const container = document.getElementById('prefContainer');
            container.innerHTML += div;

            // Iterate through the prefListNames to fetch associated preferences
            for (const prefListNot of prefListNames) {
                if (prefListNot.longName === prefList.longName) {
                    // Fetch preferences based on the ID match
                    const preferencesForCurrentPlot = preferencesData?.pref?.filter(pref =>
                        pref.PropPrefPrefs_Id === prefListNot.id // Match by ID
                    ).map(pref => pref.PropPrefPrefs_Longname) || [];

                    //console.log("preferencesForCurrentPlot", preferencesForCurrentPlot);

                    // Create and append the preferences associated with this PrefUsrSets_Name
                    preferencesForCurrentPlot.forEach(pref => {
                        const subDiv = `
                        <div style="background-color: #D9D9D9;" class="rounded-xl p-3 m-2">
                            ${pref}
                        </div>
                    `;
                    const subContainer = document.getElementById(`sub-element-${index}`);
                    subContainer.innerHTML += subDiv;
                    });
                }
            }
    }
    } catch (error) {
        console.error('Error fetching preflist or preferences:', error); // Log error if something went wrong
    }
}

// Call this function to fetch and display all plots
fetchAllPref();






//handle plot click for map
async function handlePlotClick(id3) {
    let id4 = id3; // set id3 into new variable for debugging purposes
    const apiKey = '<?php echo $mapBoxAccessToken; ?>'; // retrieve api key from secure php page
    const apiUrlLinkedPrefs = getAllLinkedPreferences + idForInsert; // Step 1
    const apiUrlPrefLists = getAllPrefList + idForInsert; // Step 2
    const apiUrlPreferences = getAllPreferences; // Step 3
    var prefListName = ""; // placeholder variable for pref list name
    let arr = []; // placeholder array for arr
    let preferencesForPlot = []; // placeholder array for prefernces in plot

    // Helper function to fetch JSON data
    const fetchJson = (url) => fetch(url, { method: 'GET', headers: { 'Content-Type': 'application/json' } })
        .then(response => { // get response
            if (!response.ok) throw new Error(`Error fetching data: ${response.statusText}`); // if something goes wrong return error
            return response.json(); // return data retrieved from server
        });

    try {
        const linkedPrefsData = await fetchJson(apiUrlLinkedPrefs); // fetch data from server and await response
        const linkedPref = linkedPrefsData?.data?.slice().reverse().find(item => item.PropPrefProp_Id == id4); // find linked data that belongs to selected plot
        if (!linkedPref) { // if nothing is found
            console.log('No linked preference found for this ID'); // show error for debugging purposes into console log
        } else { // else ...
            const prefListId = linkedPref.PropPreffSets_Id; // set preflist id into new variable
            const prefListsData = await fetchJson(apiUrlPrefLists); // set preflist values into new variable
            const matchingList = prefListsData?.data?.find(item => item.PropPrefPrefSetsId === prefListId); // retrieve matchingList and set in new variable
            if (matchingList) { 
                prefListName = matchingList.PropPrefUsrSets_Name; // set preflist name based on rertrieved data
                arr = prefListsData?.data
                    ?.filter(item => item.PropPrefUsrSets_Name === prefListName)
                    ?.map(item => item.PropPrefUsrSets_Pref_Id) || []; // if propprefusrsetsname is the same as preflistname add array with all pref ids
            }
        }
    } catch (error) {
        console.error('Error occurred:', error); // show specific error for debugging purposes
    }
}

// function for creating plot element that can later be added to the dashboard specific container
function createPlotElement(plot_id, plot_short, plot_long, plot_nr, plot_pc, index, url) { 
    // Define the HTML structure
    const container2HTML = `
        <div class="relative w-full h-full">
            <div class="rounded-xl p-0" onclick="handlePlotClick('${plot_id}', ${index})" data-index="${index}">
                <img src="${url}" alt="Plot ${index}" class="w-full h-auto rounded-sm">
            </div>
            <div class="absolute bottom-0 left-0 p-6 bg-black bg-opacity-0 rounded-xl">
                <h2 style="text-shadow: 0 0 4px rgba(0, 0, 0, 0.5);" class="text-white sm:text-2xl text-sm font-bold drop-shadow-4xl">
                    ${plot_long}
                </h2>
                <h2 style="text-shadow: 0 0 4px rgba(0, 0, 0, 0.5);" class="text-white sm:text-md text-xs drop-shadow-4xl">
                    ${plot_nr} ${plot_pc}
                </h2>
            </div>
        </div>
    `;

    // Append the HTML to the container
    const container2 = document.getElementById('mapImageContainer');
    container2.innerHTML += container2HTML;

    // Push the plot ID to the idPlots array
    idPlots.push(plot_id);
}

</script>