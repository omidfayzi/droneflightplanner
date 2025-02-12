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
    $headTitle = "Percelen overzicht";
    // Define if the head should be shown or not
    $showHeader = 1;
    //define organisation name to be shown in sidebar
    //$org = $_COOKIE['org_id'];
    $org = "placeholder";

    // this is the body of the webpage
    $bodyContent = "
    <div style='background-color: #AEAEAE;' class='bg-black m-4 p-10 rounded-xl'>
        <div class='flex flex-col justify-center items-center w-full ml-2 mr-2'>
            <svg xmlns='http://www.w3.org/2000/svg' width='80' height='80' fill='#db4f4f' class='mb-3 bi bi-geo-alt-fill' viewBox='0 0 16 16'>
            <path d='M8 16s6-5.686 6-10A6 6 0 0 0 2 6c0 4.314 6 10 6 10m0-7a3 3 0 1 1 0-6 3 3 0 0 1 0 6'/>
            </svg>
            <h1 class='text-black text-2xl'>Jouw percelen</h1>
            <h2 class='pt-5 text-stone-800 text-md text-center'>Op deze pagina kun je jouw percelen bekijken en instellen. Dit doe je door een perceel te selecteren op de kaart of een adres in te voeren. Je kunt per perceel specifieke eigenschappen instellen, zoals de naam en bijbehorende voorkeuren. Nadat je jouw instellingen hebt opgeslagen, kun je ze op elk moment wijzigen of opnieuw bekijken.</h2>
            <a href='/set-plot'>
                <button id='set-plot' class='text-white hover:scale-105 transition-all bg-blue-800 mt-7 pl-2 pr-2 p-1 rounded-xl'>
                    Perceel instellen
                </button>
            </a>
        </div>
    </div>
    <div style='background-color:rgba(0, 0, 0, 0.13);' class='bg-black m-4 p-3 rounded-xl'>
        <div id='mapImageContainer' class='grid sm:grid-cols-3 grid-cols-1 gap-4'>
            
        </div>
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

// fetch all plots and show them in the dashboard
async function fetchAllPlots() {
    try {
        const url = getAllPlots + idForInsert; // Define the URL of your GET endpoint
        const response = await fetch(url); // Make the fetch request to retrieve all plots

        // check if reponse went through otherwise throw an error which includes server side information
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }
        const data = await response.json(); // Parse the JSON response

        const apiKey = '<?php echo $mapBoxAccessToken; ?>'; // Define the Mapbox API key

        // Loop through all the plots and generate static images
        for (const [index, plot] of data.data.entries()) {
            if (index === 0) {
                document.getElementById("mapImageContainer").innerHTML = '';
            }
            const apiUrlLinkedPrefs = getAllLinkedPreferences + idForInsert; // Step 1
            const apiUrlPrefLists = getAllPrefList + idForInsert; // Step 2
            const apiUrlPreferences = getAllPreferences; // Step 3
            let arr = []; // placeholder array for data 
            let PreferncesForPlot = []; // placeholder array for data from preferences
            var prefListName = ""; // placeholder variable for data

            const targetPlotId = plot.ProfPrefPropId; // defined the target plot id
            // fetch data
            const fetchJson = (url) => fetch(url, { method: 'GET', headers: { 'Content-Type': 'application/json' } })
                .then(response => {
                    if (!response.ok) throw new Error(`Error fetching data: ${response.statusText}`); // check if reponse went through otherwise throw an error which includes server side information
                    return response.json(); // return data
                });

                    // if coordinates are missing
                    if (!plot.ProfPrefProp_Coord) {
                        console.warn('Missing coordinates for plot:', plot);
                        return;
                    }

                    const centroid = calculateCentroid(plot.ProfPrefProp_Coord); // Calculate the centroid for the plot
                    // Ensure centroid calculation was successful
                    if (!centroid || isNaN(centroid.lat) || isNaN(centroid.lng)) {
                        console.warn('Invalid centroid for plot:', plot);
                    }
                    const { lat, lng } = centroid; // add lat, lng variables to centroid
                    const zoomLevel = 15; // define zoomlevel
                    const width = 1200; // define image width pixels
                    const height = 800; // define image height pixels

                    // Construct the URL for Mapbox Static Image API
                    const url = `${mapboxStaticImageApi}(${lng},${lat})/${lng},${lat},${zoomLevel},0,0/${width}x${height}?access_token=${apiKey}`;

                    if (plot.ProfPrefProp_Id == 1) {
                            // Example usage:
                        const result = await hasMatchingPreferenceList(plot, idForInsert);
                        console.log(result); // true or false

                        if (!result && plot.ProfPrefProp_Id == 1) {
                            createPlotElement(plot.ProfPrefPropId, plot.ProfPrefProp_Short, plot.ProfPrefProp_Long, plot.ProfPrefProp_PcNr, plot.ProfPrefProp_PostalCode, index, url) // function to create new element in container
                        }
                    }
                }
    } catch (error) {
        console.error('Error fetching all plots:', error); // show error if something went wrong into console for debugging purposes
    }
}
// Call this function to fetch and display all plots
fetchAllPlots();

//handle plot click for map
async function handlePlotClick(id) {
    alert(id);
    let Id = 0;
    const deleteUrl = `${deleteFromPlots}${id}`; // define the url to be fetched
    const deleteUrl2 = `${deleteFromLinkedPreferencesWithPropId}${id}`; // define the url to be fetched


    const apiUrl = `${insertPlot}${id}/0/0/0/0/0/${idForInsert}`;

    fetchData(apiUrl)
        .then(data => {
            showPopup("Succesvol verwijderd", "success");
            console.log('Response data from insert into database API call:', data);
            fetchAllPlots();
        })
        .catch(error => {
            showPopup(`<?php echo fetchPropPrefTxt(28) ?> ${error}`, "error");
    });

    const preferencesResponse = await fetch(getAllLinkedPreferences + idForInsert);
        if (!preferencesResponse.ok) {
            throw new Error('Preferences network response was not ok');
        }

    const preferencesData = await preferencesResponse.json();
        // Check if the plot is linked to preferences
        if (preferencesData.data && Array.isArray(preferencesData.data)) {
            const preferencesData2 = preferencesData?.data?.slice().reverse().find(item => item.PropPrefProp_Id == id);
            if (!preferencesData2) return Promise.resolve([]);  // Return empty array if no matching data
            Id = preferencesData2.PropPrefOrgPropPrefId; // set preflist id for futher use
            alert(Id);
        }

         // Define the endpoint URL and the parameters
         const baseURL = insertLinkedPreferences;
            const params = {
                PropPrefOrgPropPref_Id: "1",
                PropPrefOrg_Id: Id,
                PropPrefSets_Id: "0",
                PropPrefProp_Id: "0",
                PropPref_EntryID: "0"
            };

            // Construct the full URL with parameters
            const url = `${baseURL}/${params.PropPrefOrgPropPref_Id}/${params.PropPrefOrg_Id}/${params.PropPrefSets_Id}/${params.PropPrefProp_Id}/${params.PropPref_EntryID}`;

            // Make the GET request
            const insertResponse = await fetch(url);
            if (!insertResponse.ok) {
                throw new Error(`HTTP error! status: ${insertResponse.status}`);
            }
            const insertData = await insertResponse.json();
            console.log("Response from server:", insertData);
}

// function for creating plot element that can later be added to the dashboard specific container
function createPlotElement(plot_id, plot_short, plot_long, plot_nr, plot_pc, index, url) { 
    // Define the HTML structure
    const container2HTML = `
        <div class="relative w-full h-full">
            <div class="rounded-xl p-0" data-index="${index}">
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
             <div class="absolute top-0 right-0 p-1.5 bg-black m-3 bg-opacity-80 rounded-2xl">
             <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="white" class="bi bi-trash" viewBox="0 0 16 16" onclick="handlePlotClick('${plot_id}', ${index})">
                <path d="M5.5 5.5A.5.5 0 0 1 6 6v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5m2.5 0a.5.5 0 0 1 .5.5v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5m3 .5a.5.5 0 0 0-1 0v6a.5.5 0 0 0 1 0z"/>
                <path d="M14.5 3a1 1 0 0 1-1 1H13v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V4h-.5a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1H6a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1h3.5a1 1 0 0 1 1 1zM4.118 4 4 4.059V13a1 1 0 0 0 1 1h6a1 1 0 0 0 1-1V4.059L11.882 4zM2.5 3h11V2h-11z"/>
             </svg>
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