<?php
include '../functions/functions.php';
login();

//define the user for sidebar
$user = $_SESSION["user"];
//Define the user name
$userName = $user['first_name'];
//Define save attributes, this also un-hides it
$saveAttributes = "setPref()";
// set right atrributes value to hide unhide specific conponments
$rightAttributes = 0;
//define if you want to include the componment setPrefName
$includeSetPrefName = 1;
//define if you want to include set plot name conponment
$includeSetPlotName = 0;
//define if you want to include check with idin conponment
$includeCheckWithIdin = 0;
//define if you want an go back url
$gobackUrl = 1;
//define the head title
$headTitle = fetchPropPrefTxt(5);
//define if you want to show the header at all or not
$showHeader = 1;

// Define the body content
$bodyContent = "
    <div id='preferencesContainer'>
        <!-- Dynamic categories and checkboxes will be injected here -->
    </div>
";

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

let toggleAlert = 0;

document.addEventListener("DOMContentLoaded", function () {
    const apiUrl = getAllPreferences; // API endpoint

    fetch(apiUrl, {
        method: 'GET',
        headers: {
            'Content-Type': 'application/json',
        },
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`Error fetching data: ${response.statusText}`);
        }
        return response.json();
    })
    .then(data => {
        if (data && data.pref) {
            const preferencesContainer = document.getElementById('preferencesContainer');
            const categories = {};

            // Organize preferences by category (shortname)
            data.pref.forEach(preference => {
                const { PropPrefPrefs_Shortname: shortname, PropPrefPrefs_Longname: longname, PropPrefPrefs_Id: id } = preference;
                if (!categories[shortname]) {
                    categories[shortname] = [];
                }
                categories[shortname].push({ id: id, longname: longname });
            });

            // Generate HTML for each category and its preferences
            Object.keys(categories).forEach(shortname => {
                const categoryHTML = `
                    <div class='bg-black m-4 p-3 min-h-72 rounded-xl' style='background-color: rgba(0, 0, 0, 0.13);'>
                        <div class='flex justify-between items-center'>
                            <h1 class='mb-2 text-white'>${shortname}</h1>
                        </div>
                        <div id="${shortname}" class='grid sm:grid-cols-3 gap-4'></div>
                    </div>
                `;

                preferencesContainer.innerHTML += categoryHTML;

                const categoryContainer = document.getElementById(shortname);
                categories[shortname].forEach((value, index) => {
                    // Get the array of values
                    const values = Object.values(value);

                    // Access the second value
                    const preferenceHTML = `
                        <div class='rounded-xl p-4' style='background-color: #D9D9D9;'>
                            <div class='flex justify-between items-center h-full'>
                                <input type='checkbox' id='${values[0]}' value='${values[1]}' class='mr-5 h-full'>
                                <label for='checkbox-${shortname}-${index}'>${values[1]}</label>
                            </div>
                        </div>
                    `;
                    categoryContainer.innerHTML += preferenceHTML;
                });
            });
            attachCheckboxListeners(); // Call the function to add listeners to checkboxes
        } else {
            console.log("No preferences found.");
        }
    })
    .catch(error => {
        console.error("Error:", error);
    });
});

    // Event listener for the button
    function setPref() {
        const preferencesContainer = document.getElementById('setPrefName');
        preferencesContainer.classList.remove("hidden");
    }

    async function confirmPrefName() {
        if (toggleAlert > 0) {
            const checkboxes = document.querySelectorAll('#preferencesContainer input[type="checkbox"]');
            var prefListName = document.getElementById("prefNameValue");
            let idLatest = 0;

            // Define the URL of your GET endpoint
            const url = getHighestIdPrefList + idForInsert;

            try {
                // Use fetch() to make a GET request to the server and wait for the response
                const response = await fetch(url);

                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }

                const data = await response.json();

                // Handle the response data (the latest ID)
                idLatest = data.highestId + 1;
            } catch (error) {
                showPopup("Probleem met het ophalen van de laatste ID, probeer later opnieuw", "error");
                idLatest = 0;
                //return; // Exit if there's an error fetching the ID
            }

            // Loop over the checkboxes and process them
            checkboxes.forEach((checkbox) => {
                if (checkbox.checked) {
                    // Data to be sent to the endpoint
                    const data = {
                        PropPrefPrefSets_Id: idLatest, // Updated ID
                        PropPrefUsrSets_Name: prefListName.value,
                        PropPrefUsrSets_UserOrg_Id: idForInsert,
                        PropPrefUsrSets_Pref_Id: checkbox.id,
                        PropPrefUsrSets_Pref_Entry_Id: 1,
                    };

                    // Call the insert endpoint with fetch
                    fetch(
                        `${insertIntoPrefList}${data.PropPrefPrefSets_Id}/${data.PropPrefUsrSets_Name}/${data.PropPrefUsrSets_UserOrg_Id}/${data.PropPrefUsrSets_Pref_Id}/${data.PropPrefUsrSets_Pref_Entry_Id}`,
                        {
                            method: "GET",
                        }
                    )
                        .then((response) => {
                            if (!response.ok) {
                                throw new Error("Network response was not OK");
                                preferencesContainer.classList.add("hidden");
                            }
                            return response.json();
                        })
                        .then((result) => {
                            showPopup("Succesvol toegevoegd!", "succes");
                            preferencesContainer.classList.add("hidden");
                        })
                        .catch((error) => {
                            showPopup("Probleem met het toevoegen van de data, probeer later opnieuw", "error");
                            preferencesContainer.classList.add("hidden");
                        });
                }
            });
        }
    }

    // Function to change button color based on checkbox selection
    function toggleButtonColor() {
        const checkboxes = document.querySelectorAll('#preferencesContainer input[type="checkbox"]');
        const button = document.getElementById('setPref()');

        const isAnyChecked = Array.from(checkboxes).some(checkbox => checkbox.checked);

        if (isAnyChecked) {
            //button.classList.remove("text-white");
            button.classList.remove("bg-red-800");
            button.classList.add("bg-green-500");
            toggleAlert = 1;
        } else {
            button.classList.remove("bg-green-500");
            button.classList.add("bg-red-800");
            //button.classList.add("text-white");
            toggleAlert = 0;
        }
    }

    // Attach change event listeners to checkboxes
    function attachCheckboxListeners() {
        const checkboxes = document.querySelectorAll('#preferencesContainer input[type="checkbox"]');
        checkboxes.forEach(checkbox => {
            checkbox.addEventListener('change', toggleButtonColor);
        });
    }
</script>
