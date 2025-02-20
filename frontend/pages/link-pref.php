<?php
    include '../functions/functions.php';
    login();

    //define if you want to include the componment setPlotName
    $includeSetPlotName = 0;
    //define if you want to include the componment setPrefName
    $includeSetPrefName = 0;
    //define the user
    $user = $_SESSION["user"];
    //define if you want to include the the componkent include check with idin
    $includeCheckWithIdin = 0;
    // Define the user name
    $userName = $user['first_name'];
    //select the right attributes for the page
    $rightAttributes = 0;
    // define the go back url
    $gobackUrl = 1;
    // Define the title of the head
    $headTitle = fetchPropPrefTxt(16);
    // Define if the head should be shown or not
    $showHeader = 1;

    // this is the body of the webpage
    $bodyContent = "
    <div style='background-color:rgba(0, 0, 0, 0.13);' class='bg-black m-4 p-3 rounded-xl'>
        <div class='flex justify-between items-center'>
            <h1 class='mb-2 text-white'>".fetchPropPrefTxt(15)."</h1>
        </div>
        <select id='mySelect' class='rounded-xl w-full' style='padding: 10px; background-color: #D9D9D9;'>
                <option value='' disabled selected>".fetchPropPrefTxt(17)."</option>
        </select>
    </div>
    <div style='background-color:rgba(0, 0, 0, 0.13);' class='bg-black m-4 p-3 min-h-80 rounded-xl'>
        <div class='flex justify-between items-center'>
            <h1 class='mb-2 text-white'>".fetchPropPrefTxt(18)."</h1>
        </div>
        <div id='mapImageContainer' class='grid sm:grid-cols-3 gap-4'>
            
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

async function fetchAllPrefLists() {
    try {
        // Define the URL of your GET endpoint
        const url = getAllPrefList + idForInsert;

        // Make the fetch request to retrieve all preference lists
        const response = await fetch(url);

        if (!response.ok) {
            throw new Error('Network response was not ok');
        }

        // Parse the JSON response
        const data = await response.json();

        // Handle the response data (all preference lists)
        console.log('All Preference Lists:', data);
        const uniqueNamesArray = [];

        // Optionally, you can process the data (for example, display it on the page)
        // Example: populate a dropdown with preference list names
        const prefListContainer = document.getElementById("mySelect");
        data.data.forEach(pref => {
            if (!uniqueNamesArray.includes(pref.PropPrefUsrSets_Name)) {
                uniqueNamesArray.push(pref.PropPrefUsrSets_Name);
                const option = document.createElement('option');
                option.value = pref.PropPrefPrefSetsId;  // Assuming `PropPrefUsrSets_Id` is the unique identifie
                option.textContent = pref.PropPrefUsrSets_Name;  // Assuming `PropPrefUsrSets_Name` is the name of the preference list
                prefListContainer.appendChild(option);
            }
        });

    } catch (error) {
        console.error('Error fetching all preference lists:', error);
    }
}
fetchAllPrefLists();

async function fetchAllPlots(id) {
    try {
        const container = document.getElementById('mapImageContainer');
        container.innerHTML = '';  // Clear the content of the container

        // Define the URL of your GET endpoint
        const url = getAllPlots + idForInsert;

        // Fetch data using an async function
        const fetchData = async (url) => {
            const response = await fetch(url);
            if (!response.ok) {
                throw new Error(`Failed to fetch plots: ${response.statusText}`);
            }
            return response.json();
        };

        // Get data from the API
        const data = await fetchData(url);

        // Define the Mapbox API key
        const apiKey = '<?php echo $mapBoxAccessToken; ?>';

        // Loop through all the plots and generate static images
        for (const plot of data.data) {
            const result = await hasMatchingPreferenceList(plot, idForInsert);
            console.log(result); // true or false

            if(!result) {
            // Fetch preferences linked to the plot
            let isColor = false; // Reset isColor for each plot
            let isColorOrange = false; // Reset isColor for each plot
            let color;
            try {
                const preferencesResponse = await fetch(getAllLinkedPreferences + idForInsert);
                if (!preferencesResponse.ok) {
                    throw new Error('Preferences network response was not ok');
                }

                const preferencesData = await preferencesResponse.json();

                // Check if the plot is linked to preferences
                if (preferencesData.data && Array.isArray(preferencesData.data)) {
                    isColor = preferencesData.data.some(item => item.PropPrefProp_Id === plot.ProfPrefPropId);
                    if(!id == 0) {
                        // Iterate through the data and check the condition
                        for (let i = preferencesData.data.length - 1; i >= 0; i--) {
                            const item = preferencesData.data[i];
                            if (item.PropPrefProp_Id == plot.ProfPrefPropId) {
                                if(item.PropPreffSets_Id == id) {
                                    isColorOrange = true;
                                }
                                break; // Exit the loop once the last match is found
                            }
                        }
                    }
                } else {
                    console.error('Preferences data format is not as expected');
                }
            } catch (preferencesError) {
                console.error('Error fetching preferences:', preferencesError);
            }
            if(isColorOrange && id !== 0) {
                color = 'green';
            } else {
                color = isColor ? 'orange' : 'red';
            }

            // Ensure coordinates are properly formatted
            if (!plot.ProfPrefProp_Coord) {
                console.warn('Missing coordinates for plot:', plot);
                continue;
            }

            // Calculate the centroid for the plot
            const centroid = calculateCentroid(plot.ProfPrefProp_Coord);

            // Ensure centroid calculation was successful
            if (!centroid || isNaN(centroid.lat) || isNaN(centroid.lng)) {
                console.warn('Invalid centroid for plot:', plot);
                continue;
            }

            const { lat, lng } = centroid;

            const zoomLevel = 15;
            const width = 600;
            const height = 400;

            // Construct the URL for Mapbox Static Image API
            const staticImageUrl = `${mapboxStaticImageApi}(${lng},${lat})/${lng},${lat},${zoomLevel},0,0/${width}x${height}?access_token=${apiKey}`;

            // Create a container for the image
            const containerInner = `
                <div style="background-color: ${color};" class="rounded-xl p-2 relative w-full h-full">
                    <div class="rounded-xl p-0" onclick="handlePlotClick('${plot.ProfPrefPropId}')">
                        <img src="${staticImageUrl}" alt="Plot" class="w-full h-auto rounded-sm">
                    </div>
                    <div class="absolute bottom-0 left-0 p-6 bg-black bg-opacity-0 rounded-xl">
                        <h2 style="text-shadow: 0 0 4px rgba(0, 0, 0, 0.5);" class="text-white sm:text-2xl text-sm font-bold drop-shadow-4xl">
                            ${plot.ProfPrefProp_Long}
                        </h2>
                        <h2 style="text-shadow: 0 0 4px rgba(0, 0, 0, 0.5);" class="text-white sm:text-md text-xs drop-shadow-4xl">
                            ${plot.ProfPrefProp_PcNr} ${plot.ProfPrefProp_PostalCode}
                        </h2>
                    </div>
                </div>
            `;

            // Append the container to the main container
            const container = document.getElementById('mapImageContainer');
            if (container) {
                container.innerHTML += containerInner;
            } else {
                console.error('Container with ID "mapImageContainer" not found.');
            }
        }
        }
    } catch (error) {
        console.error('Error fetching all plots:', error);
    }
}

// Call this function to fetch and display all plots
fetchAllPlots(0);

async function handlePlotClick(id) {
    var selectedValue = document.getElementById("mySelect").value;
    let isLinked = false;

    try {
        // Fetching data from the endpoint
        const response = await fetch(getAllLinkedPreferences + idForInsert);
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }
        const data = await response.json();

        // Assuming 'data.data' contains an array of preference lists
        if (data.data && Array.isArray(data.data)) {
            // Use forEach to process each item in the data array
            for (let i = data.data.length - 1; i >= 0; i--) {
                const item = data.data[i];
                if (item.PropPrefProp_Id == id) {
                    if(item.PropPreffSets_Id == selectedValue) {
                        isLinked = true;
                    }
                    break; // Exit the loop once the last match is found
                }
            }
        } else {
            console.error('Data format is not as expected');
        }

        if (!isLinked) {
            console.log("The value of isLinked is false");
            // Define the endpoint URL and the parameters
            const baseURL = insertLinkedPreferences;
            const params = {
                PropPrefOrgPropPref_Id: "1",
                PropPrefOrg_Id: idForInsert,
                PropPrefSets_Id: selectedValue,
                PropPrefProp_Id: id,
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
            fetchAllPlots(selectedValue);
        }

    } catch (error) {
        console.error('There was a problem with the fetch operation:', error);
        fetchAllPlots(selectedValue);
    }
}


// Select the dropdown and output element
const selectBox = document.getElementById('mySelect');

// Add an event listener for the change event
selectBox.addEventListener('change', function() {
  // Get the selected value
  const selectedValue = selectBox.value;
  fetchAllPlots(selectBox.value);

});



</script>