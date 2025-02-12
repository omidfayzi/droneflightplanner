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
    $headTitle = "Dashboard";
    // Define if the head should be shown or not
    $showHeader = 1;
    //define organisation name to be shown in sidebar
    //$org = $_COOKIE['org_id'];
    $org = "placeholder";

    // this is the body of the webpage
    $bodyContent = "
    <div style='background-color:rgba(0, 0, 0, 0.13);' class='bg-black m-4 p-3 rounded-xl'>
        <div id='mapContainer' class='grid sm:grid-cols-2 gap-4'>
            <div class='relative w-full h-full'>
            <img src='../images/new-york2.png' alt='Holding the Drones Logo' class='w-full h-full object-cover rounded-xl sm:rounded-s-xl' />
            <div class='absolute inset-0 rounded-xl sm:rounded-s-xl' style='background: linear-gradient(to right, transparent 70%, rgba(189, 189, 189));'></div>
            <div class='absolute bottom-0 left-0 p-6 bg-black bg-opacity-0 rounded-xl'>
                <h2 style='text-shadow: 0 0 4px rgba(0, 0, 0, 0.5);' class='text-white sm:text-2xl text-sm font-bold drop-shadow-2xl'>
                    Voorbeeldperceel
                </h2>
                <h2 style='text-shadow: 0 0 4px rgba(0, 0, 0, 0.5);' class='text-white sm:text-md text-xs drop-shadow-2xl'>
                    80 1234AB
                </h2>
            </div>
        </div>
        <div>
            <div class='justify-center flex flex-col items-center'>
                <div class='grid grid-cols-2 sm:grid-cols-1 gap-4 sm:w-5/6 w-full pt-5 max-h-40 sm:max-h-96 overflow-scroll scrollbar-hidden'>
                    <div style='background-color: #AEAEAE;' class='rounded-xl text-white p-4'>
                        <strong>sample</strong> voorkeuren:
                        </div>
                        <div style='background-color: #D9D9D9;' class='rounded-xl p-4'>sample1</div>
                        <div style='background-color: #D9D9D9;' class='rounded-xl p-4'>sample2</div>
                        <div style='background-color: #D9D9D9;' class='rounded-xl p-4'>sample3</div>
                </div>
            </div>
        </div>
        </div>
    </div>
    <div style='background-color:rgba(0, 0, 0, 0.13);' class='bg-black m-4 p-3 rounded-xl'>
        <div id='mapImageContainer' class='grid sm:grid-cols-3 grid-cols-2 gap-4'>
            <div class='relative w-full h-full rounded-xl p-0' data-index='index'>
                <img src='../images/new-york2.png' alt='Plot index' class='w-full h-auto rounded-sm'>
                <div class='absolute inset-0 w-full h-full'>
                    <div class='flex justify-center items-center h-full w-full ml-2 mr-2'>
                        <h1 class='text-white text-2xl'>Jouw percelen</h1>
                    </div>
                </div>
            </div>
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


const url = `${getUsersWithKeycloak}${keycloakId}`; // define the url to be fetched

// fetch the data
fetchData(url).then(data => {
    if (data.users && data.users.length > 0) { // check if the users contain more than zero rows
    } else { // if not
        console.log("No users found for the provided Keycloak ID."); // console log for debugging purposes
        checkWithIdin.style.display = "flex"; // check with idin is getting displayed because this is a new user if row is not in database
        insertIntoUsers({ // add some default values into database for this new user
            PropPrefUser_id: "1",
            PropPrefKeycloak_id: keycloakId,
            PropPrefUser_IdinCheck: "0",
            PropPrefUser_Streetname: "unknown",
            PropPrefUser_Nr: 0,
            PropPrefUser_PC: "unknown",
            PropPrefUser_City: "unknown",
            PropPrefUser_Country: "unknown",
            PropPrefUser_Entry_ID: "1"
        }).then((result) => {
            console.log("Insertion successful:", result); // displaying a message into console log if insert succeed for debugging purposes
        }).catch((error) => {
            console.error("Failed to insert data:", error); // displaying a message into console log if insert fails for debugging purposes
    });
    }
}).catch(error => {
    checkWithIdin.style.display = "flex"; // if it fails display check with idin anyway
    console.error('Error:', error); // display error into console log for debugging purposes
});

// function to either hide the message or go through with the idin check
function confirmIdinCheck(idin) {
    if(idin == 1) {  // check if user either pressed yes or no
        (async () => {
            await processTransactionRequest(); // initalize bank check with idin
        })();
    } else {
        checkWithIdin.style.display = "none"; // hide message
    }
}

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

            // Step 1: Fetch linked preferences and find the matching preference list ID
            fetchJson(apiUrlLinkedPrefs) 
                .then(linkedPrefsData => { // if json is fetched then process the data
                    const linkedPref = linkedPrefsData?.data?.slice().reverse().find(item => item.PropPrefProp_Id === targetPlotId);
                    if (!linkedPref) return Promise.resolve([]);  // Return empty array if no matching data
                    const prefListId = linkedPref.PropPreffSets_Id; // set preflist id for futher use
                    // Step 2: Fetch preference lists and collect preference IDs
                    return fetchJson(apiUrlPrefLists) // fetching step 2 pref lists
                        .then(prefListsData => { // after response process data
                            const matchingList = prefListsData?.data?.find(item => item.PropPrefPrefSetsId === prefListId); // find matching array row where item.PropPrefPrefSetsId matches prefListId
                            if (!matchingList) return Promise.resolve([]);  // Return empty array if no matching list
                            prefListName = matchingList.PropPrefUsrSets_Name; // set prefListName to the matching list name
                            const arr = prefListsData?.data
                                ?.filter(item => item.PropPrefUsrSets_Name === prefListName)
                                ?.map(item => item.PropPrefUsrSets_Pref_Id) || []; // map data to certain variable

                            // Step 3: Fetch all preferences and collect matching long names
                            return fetchJson(apiUrlPreferences) // step3 fetch prefernces data for prefererence list
                                .then(preferencesData => { // if data is retrieved process data
                                    preferencesData?.pref?.forEach(pref => { // for each value in database
                                        if (arr.includes(pref.PropPrefPrefsId)) { // check if id belongs to an id matching the id in the link table from pref list and pref
                                            PreferncesForPlot.push(pref.PropPrefPrefs_Longname); // then push this in array
                                        }
                                    });

                                    return PreferncesForPlot; // Return the collected preferences
                                })
                                .catch(error => {
                                    console.error('Error fetching preferences:', error); // show console error for debugging purposes
                                    return []; // Return empty array in case of error
                                });
                        })
                        .catch(error => {
                            console.error('Error fetching preference lists:', error); // show console error for debugging purposes
                            return []; // Return empty array in case of error
                        });
                })
                .then(async (preferences) => { 

                    // if coordinates are missing
                    if (!plot.ProfPrefProp_Coord) {
                        console.warn('Missing coordinates for plot:', plot);
                        return;
                    }

                    const centroid = calculateCentroid(plot.ProfPrefProp_Coord); // Calculate the centroid for the plot
                    // Ensure centroid calculation was successful
                    if (!centroid || isNaN(centroid.lat) || isNaN(centroid.lng)) {
                        console.warn('Invalid centroid for plot:', plot);
                        return;
                    }
                    const { lat, lng } = centroid; // add lat, lng variables to centroid
                    const zoomLevel = 15; // define zoomlevel
                    const width = 1200; // define image width pixels
                    const height = 800; // define image height pixels

                    // Construct the URL for Mapbox Static Image API
                    const url = `${mapboxStaticImageApi}(${lng},${lat})/${lng},${lat},${zoomLevel},0,0/${width}x${height}?access_token=${apiKey}`;

                    const result = await hasMatchingPreferenceList(plot, idForInsert);
                    console.log(result); // true or false

                    // Append the container to the main container
                    if (!result && index == 0) { // if index is 0
                        const container = document.getElementById('mapContainer'); // get element so content can be added to it
                        renderPlotDetails(container, url, plot.ProfPrefProp_Short, plot.ProfPrefProp_Long, plot.ProfPrefProp_PcNr, plot.ProfPrefProp_PostalCode, prefListName, preferences) // function to render content to the container
                    }
                    if(!result) {
                    createPlotElement(plot.ProfPrefPropId, plot.ProfPrefProp_Short, plot.ProfPrefProp_Long, plot.ProfPrefProp_PcNr, plot.ProfPrefProp_PostalCode, index, url) // function to create new element in container
                    }
                })
                .catch(error => { 
                    console.error('Error fetching linked preferences:', error); // show error if something went wrong for debugging purposes into console
                });
        };
    } catch (error) {
        console.error('Error fetching all plots:', error); // show error if something went wrong into console for debugging purposes
    }
}
// Call this function to fetch and display all plots
fetchAllPlots();

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
        const preferencesData = await fetchJson(apiUrlPreferences); // then retrieve latest data the preferences
        preferencesForPlot = preferencesData?.pref?.filter(pref => arr.includes(pref.PropPrefPrefsId)) // if array includes the pref id
            .map(pref => pref.PropPrefPrefs_Longname) || []; // map the name data
        // Define the URL of your GET endpoint for all plots
        console.log('Fetching plots...'); // put fetching plots into console for debugging purposes
        const url = getAllPlots + idForInsert; // define new fetch url
        const response = await fetch(url); // fetch this url
        if (!response.ok) throw new Error(`Network response was not ok: ${response.status}`); // if gotten no reponse from server show error

        const data = await response.json(); // await for json reponse before continue of code
        if (!data || !data.data) { // if no data is found
            console.error('Invalid response structure: missing data field'); // show an error for debugging purposes
            return; // If no plot data, we return early but continue the rest of the flow
        }
        // Loop through all the plots and generate static images
        let plotFound = false; // Flag to track if we found the matching plot
        data.data.forEach((plot) => { // for each in data data
            if (id4 == plot.ProfPrefPropId) { // if selefcted id is the same as the retrieved plot id
                plotFound = true; // Mark as found

                const centroid = calculateCentroid(plot.ProfPrefProp_Coord); // call the calculate centroid function and put returned data into variable
                if (!centroid || isNaN(centroid.lat) || isNaN(centroid.lng)) { // if no centroid returned
                    console.warn('Invalid centroid for plot:', plot); // show error for debugging purposes
                    return; // exit function by returning nothing
                }

                const { lat, lng } = centroid; // put centroid in lat lng variables
                const zoomLevel = 15; // define zoomlevel
                const width = 600; // define width pixels
                const height = 400; // define height pixels

                // Construct the URL for Mapbox Static Image API
                const mapboxUrl = `${mapboxStaticImageApi}(${lng},${lat})/${lng},${lat},${zoomLevel},0,0/${width}x${height}?access_token=${apiKey}`;

                // Append the container to the main container
                const container = document.getElementById('mapContainer');
                if (container) {
                    // reder the plot into container
                    renderPlotDetails(container, mapboxUrl, plot.ProfPrefProp_Short, plot.ProfPrefProp_Long, plot.ProfPrefProp_PcNr, plot.ProfPrefProp_PostalCode, prefListName, preferencesForPlot)
                }
            }
        });

        // If no matching plot is found
        if (!plotFound) {
            console.log('No matching plot found for the given ID'); // add console log for debugging purposes
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

// function to render plot details in the main screen of the dashboard
function renderPlotDetails(container, url, plot_short, plot_long, plot_nr, plot_pc, prefListName, preferences) {
    container.innerHTML = `
        <div class="relative w-full h-full">
            <img src="${url}" alt="Holding the Drones Logo" class="w-full h-full object-cover rounded-xl sm:rounded-s-xl" />
            <div class="absolute inset-0 rounded-xl sm:rounded-s-xl" style="background: linear-gradient(to right, transparent 70%, rgba(189, 189, 189));"></div>
            <div class="absolute bottom-0 left-0 p-6 bg-black bg-opacity-0 rounded-xl">
                <h2 style="text-shadow: 0 0 4px rgba(0, 0, 0, 0.5);" class="text-white sm:text-2xl text-sm font-bold drop-shadow-2xl">
                    ${plot_long}
                </h2>
                <h2 style="text-shadow: 0 0 4px rgba(0, 0, 0, 0.5);" class="text-white sm:text-md text-xs drop-shadow-2xl">
                    ${plot_nr} ${plot_pc}
                </h2>
            </div>
        </div>
        <div>
            <div class="justify-center flex flex-col items-center">
                <div class='grid grid-cols-2 sm:grid-cols-1 gap-4 sm:w-5/6 w-full pt-5 max-h-40 sm:max-h-96 overflow-scroll scrollbar-hidden'>
                    ${prefListName 
                        ? `<div style='background-color: #AEAEAE;' class='rounded-xl text-white p-4'>
                            <strong>${prefListName}</strong> voorkeuren:
                            </div>` 
                        : ''
                    }
                    ${preferences.length > 0 
                        ? preferences.map(item => ` 
                            <div style='background-color: #D9D9D9;' class='rounded-xl p-4'>${item}</div>
                            `).join('') 
                        : `<div style='background-color: #D9D9D9;' class='rounded-xl p-4'>Geen voorkeuren beschikbaar voor dit perceel</div>`
                    }
                </div>
            </div>
        </div>
    `;
}

function callFunctionInOrder() {
    let index = 0; // Start at the first element of the array

    // Define an interval that runs every 5 seconds
    const interval = setInterval(() => {
        if (index < idPlots.length) {
            handlePlotClick(idPlots[index]);
            index++; // Move to the next element
        } else {
            clearInterval(interval); // Stop the interval when the array is exhausted
            index++;
            callFunctionInOrder();
        }
    }, 20000); // 5000ms = 5 seconds
}

// Start the process
callFunctionInOrder();

</script>