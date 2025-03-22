<?php
    include '../functions.php';
    login();

    $user = $_SESSION["user"];
    // Define the user name
    $userName = $user['first_name'];
    //define if you want to iclude the componment setPrefName
    $includeSetPrefName = 0;
    //select the right attributes for the page
    //define if you want to iclude the componment setPlotName
    $includeSetPlotName = 0;
    //define if you want to iclude the the componkent include check
    $includeCheckWithIdin = 0;
    // define the go back url
    $gobackUrl = 1;
    // Define the title of the head
    $headTitle = fetchPropPrefTxt(19);
    // Define if the head should be shown or not
    $showHeader = 1;

    // this is the body of the webpage
    $bodyContent = "
    <div id='verification-status' style='background-color:rgba(0, 0, 0, 0.13);' class='bg-black m-4 p-3 min-h-72 rounded-xl'>
        <h1 class='mb-2 text-white'>iDIN</h1>
        <div class='flex justify-center'>
            <div class='justify-center text-center'>
                <!-- Content will be updated dynamically -->
            </div>
        </div>
    </div>
    <div style='background-color:rgba(0, 0, 0, 0.13);' class='bg-black m-4 p-3 rounded-xl'>
        <h1 class='mb-2 text-white'>Percelen</h1>
        <div class='sm:grid-cols-4 grid gap-4'>
            <select id='languageSelect' class='rounded-xl' style='padding: 10px; background-color: #D9D9D9;'>
                <option value='disabled selected'>Selecteer een taal</option>
                <option value='PropPrefTxt_Nl'>".fetchPropPrefTxt(20)."</option>
                <option value='PropPrefTxt_En'>".fetchPropPrefTxt(21)."</option>
            </select>
            <select id='mySelect' class='rounded-xl' style='padding: 10px; background-color: #D9D9D9;'>
                <option value='' disabled selected>".fetchPropPrefTxt(22)."</option>
            </select>
            <a href='./logout.php' style='text-decoration: none;'><div style='background-color: #D9D9D9;' class='overflow-hidden rounded-xl p-4'>".fetchPropPrefTxt(13)."</div></a></div>
    </div>
    ";

    // Include the base template
    include '../includes/header.php';
?>
<script>
    // Automatically call the function on page load
//sendStatusRequest()


const keycloakId = "<?php echo $user['id'] ?>"; // Replace with the actual ProfPrefKeycloak_id
const url = getUsersWithKeycloak + keycloakId;
let isVerified2 = 1;

fetch(url, {
    method: 'GET',
    headers: {
        'Content-Type': 'application/json',
    },
})
.then((response) => {
    if (!response.ok) {
        throw new Error(`Error fetching users: ${response.statusText}`);
    }
    return response.json();
})
.then((data) => {
    if (data.users && data.users.length > 0) {
        // Get the last user from the array
        const lastUser = data.users[data.users.length - 1];
        // Extract PropPrefUser_IdinCheck from the last user
        const propPrefUserIdInCheck = lastUser.PropPrefUser_IdinCheck;
        if(propPrefUserIdInCheck == 1) {
            isVerified2 = 1;
        } else {
            isVerified2 = 0;
        }

        const container = document.querySelector('#verification-status .justify-center.text-center');

        if (isVerified2 == 0) {
            container.innerHTML = `
                <h1 class='text-white text-lg mb-3'><?php echo fetchPropPrefTxt(24); ?></h1>
                <img src='/images/idin-logo.svg' alt='Holding the Drones Logo' class='max-w-full max-h-40 object-contain'>
                <button class='mt-6 px-4 py-2 bg-blue-500 text-white rounded-3xl' onclick='hello()'><?php echo fetchPropPrefTxt(23); ?></button>
            `;
        } else {
            container.innerHTML = `
                <h1 class='text-white text-lg mb-3'><?php echo fetchPropPrefTxt(10); ?></h1>
                <img src='/images/idin-logo.svg' alt='Holding the Drones Logo' class='max-w-full max-h-40 object-contain'>
            `;
        }
        // Handle the users array when at least one user is fetched
    } else {
        console.log("No users found for the provided Keycloak ID.");
    }
})
.catch((error) => {
    console.error("Error:", error);
});

function hello() {
    // Example usage
    (async () => {
            await processTransactionRequest();
        })();
}

document.getElementById('languageSelect').addEventListener('change', function () {
    const selectedLanguage = this.value;

    // Set the language cookie with a very long expiration time (100 years)
    document.cookie = `language_id=${selectedLanguage}; path=/; max-age=${100 * 365 * 24 * 60 * 60};`;
    showPopup("<?php echo fetchPropPrefTxt(25) ?>", "success");
});

function setSelectedLanguage() {
    const currentLanguage = getCookie('language_id');
    if (currentLanguage) {
        const languageSelect = document.getElementById('languageSelect');
        languageSelect.value = currentLanguage; // Set the value to match the cookie
    }
}

setSelectedLanguage();

    const orgArray = [];

    async function fetchAllPrefLists() {
    try {
        // Define the proxy URL
        const proxyUrl = cors;
        
        // Define the URL of your GET endpoint
        const url = userOrgDatabaseUser; 
        
        // Use the proxy to forward the request over HTTPS
        const proxyUrlWithTarget = proxyUrl + url;

        // Replace this with your actual Bearer token
        const token = userOrgDatabaseBearerToken;

        // Make the fetch request to retrieve all preference lists with Authorization header
        const response = await fetch(proxyUrlWithTarget, {
            method: 'GET',
            headers: {
                'Authorization': `Bearer ${token}`,
                'Content-Type': 'application/json'  // You may want to include this depending on your API's expectations
            }
        });

        if (!response.ok) {
            throw new Error('Network response was not ok');
        }

        // Parse the JSON response
        const data = await response.json();

        // Handle the response data (all preference lists)
        console.log('All Preference Lists:', data);

        // Make sure the data is in the correct format
        if (Array.isArray(data)) {
            const prefListContainer = document.getElementById("mySelect");
            
            // Loop through each org
            data.forEach(org => {
                var keycloakId = "<?php echo $user['id'] ?>";
                alert(org.USR_Keycloak_ID);
                alert("alert" + keycloakId);
                if(org.USR_Keycloak_ID === keycloakId) {
                    alert(org.USR_Keycloak_ID);
                    // Check if the USR_ID is 1
                    // Create the option element
                    //const option = document.createElement('option');
                    orgArray.push(org.ORG_ID);  // Set the Org ID as the value

                    // Example usage of the function
                }
            });
            fetchOrganisationData();
        } else {
            console.error("The response is not an array", data);
        }

    } catch (error) {
        console.error('Error fetching all preference lists:', error);
    }
}

fetchAllPrefLists();

async function fetchOrganisationData() {
    // Define the proxy URL
    const proxyUrl = cors;
    
    // Define the URL of your GET endpoint
    const url = userOrgDatabaseOrg; // Your original HTTP URL
    
    // Use the proxy to forward the request over HTTPS
    const proxyUrlWithTarget = proxyUrl + url;

    // Define the Bearer token inside the function
    const token = userOrgDatabaseBearerToken; // Replace with your actual Bearer token

    try {
        // Fetch data from the API with the Authorization header
        const response = await fetch(proxyUrlWithTarget, {
            method: 'GET',
            headers: {
                'Authorization': `Bearer ${token}`,
                'Content-Type': 'application/json'  // You may want to include this depending on your API's expectations
            }
        });

        // Check if the response is okay
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }

        // Parse the JSON response
        const data = await response.json();

        // Handle the response data (all preference lists)
        console.log('All Preference Lists:', data);

        // Make sure the data is in the correct format
        if (Array.isArray(data)) {
            const prefListContainer = document.getElementById("mySelect");
            
            // Loop through each org
            data.forEach(org => {
                // Check if the org_id matches
                if (orgArray.includes(org.ORG_ID)) {
                    // Create the option element
                    const option = document.createElement('option');
                    option.value = org.ORG_ID;  // Set the Org ID as the value
                    option.textContent = org.ORG_FullName;  // Display the Full Name of the organization
                    prefListContainer.appendChild(option);
                }
            });
        } else {
            console.error("The response is not an array", data);
        }
    } catch (error) {
        console.error('Error fetching organization data:', error);
    }
}

function confirmOrg() {
    // Get the select element by its ID
    const selectBox = document.getElementById("mySelect");

    if (selectBox.value === "gebruiker") {
        window.location.href = "./usr-dashboard.php";
    } else {
        // Retrieve the text of the selected option
        const selectedText = selectBox.options[selectBox.selectedIndex].textContent;
        //alert(selectedText); // Output the selected text
        document.cookie = `org_id=${selectedText}; path=/; max-age=${100 * 365 * 24 * 60 * 60};`;
        window.location.href = "./org-dashboard.php";
    }
}

</script>