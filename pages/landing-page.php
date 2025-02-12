<?php
    include '../functions/functions.php';
    login();

    $user = $_SESSION["user"];
    // Define the user name
    $userName = $user['first_name'];
    //define if you want to include the componment setPrefName
    $includeSetPrefName = 0;
    //define if you want to include the componment setPlotName
    $includeSetPlotName = 0;
    //define if you want to include the the componment include check
    $includeCheckWithIdin = 0;
    //select the right attributes for the page
    $rightAttributes = 0;
    // define the go back url
    $gobackUrl = 1;
    // Define the title of the head
    $headTitle = fetchPropPrefTxt(46);
    // Define if the head should be shown or not
    $showHeader = 1;


    // this is the body of the webpage
    $bodyContent = "
        <div class='fixed inset-0 bg-black bg-opacity-90 flex items-center justify-center z-50'>
            <div class='w-[90%] bg-white rounded-xl p-5 max-w-md'>
            <h1 class='pb-2'>Selecteer organisatie</h1>
            <select id='mySelect' class='rounded-xl w-full mb-2' style='padding: 10px; background-color: #D9D9D9;'>
                    <option value='' disabled selected>".fetchPropPrefTxt(22)."</option>
                    <option value='gebruiker'>Als gebruiker inloggen</option>
                    <option value='org1'>Fictieve Organisatie</option>
            <input type='button' value='Bevestigen' onclick='confirmOrg()' class='text-white bg-blue-500 hover:bg-blue-700 rounded-xl w-full' style='padding: 10px; cursor: pointer;'>
            </div>
        </div>
    ";

    // Include the base template
    include '../includes/header.php';
?>
<script>
    const cors = '<?php echo $corsAnywhere; ?>'; // Define the Mapbox API key
    const user = '<?php echo $userOrgDatabaseUser; ?>'; // Define the Mapbox API key
    const org = '<?php echo $userOrgDatabaseOrg; ?>'; // Define the Mapbox API key
    const tokenBearerUserOrg = '<?php echo $userOrgDatabaseBearerToken; ?>'; // Define the Mapbox API key

    var Organisations = 0; //define a default value
    let i = 1; // define a default value

    if (i == 0) {
        // Redirect to another page
        //window.location.href = "./dashboard.php";
    }

    const orgArray = []; //placeholder array for data

    async function set() {
        const elapsedTime = Date.now() - parseInt(sessionStorage.tokenTimestamp, 10); 
        const hoursPassed = elapsedTime / (1000 * 60 * 60); // Convert to hours

        if (hoursPassed > 20) {
            console.log("Session expired! Clearing sessionStorage.");
            sessionStorage.removeItem("accessToken");
            sessionStorage.removeItem("tokenTimestamp");
            const proxyUrl = cors; // Define the proxy URL
            const url = "http://devserv01.holdingthedrones.com:4535/token";  // Define the URL of your GET endpoint
            const proxyUrlWithTarget = proxyUrl + url; // Use the proxy to forward the request over HTTPS
            fetch(proxyUrlWithTarget, {
                method: "POST",
                headers: {
                    "Content-Type": "application/json"
                },
                body: JSON.stringify({
                    name: "Test",
                    token: "bco2lnNTXXMfePSkVmldqKiaeMXyXw5U"
                })
            })
            .then(response => response.json())
            .then(data => {
                const accessToken = data.accessToken; // Extract token
                console.log(accessToken);
                sessionStorage.accesToken = accessToken;
                sessionStorage.tokenTimestamp = Date.now(); // Save timestamp
                alert(sessionStorage.getItem("accesToken"));
            })
            .catch(error => console.error("Error:", error));
        } else {
            console.log(`Session is valid. ${20 - hoursPassed.toFixed(2)} hours remaining.`);
        }
        
    }

    //function for retrieving all pref lists
    async function fetchAllPrefLists() {
    try {
        set();
        const proxyUrl = cors; // Define the proxy URL
        const url = user;  // Define the URL of your GET endpoint
        const proxyUrlWithTarget = proxyUrl + url; // Use the proxy to forward the request over HTTPS
        const token = sessionStorage.getItem("accesToken"); // bearer token

        // Make the fetch request to retrieve all preference lists with Authorization header
        const response = await fetch(proxyUrlWithTarget, {
            method: 'GET',
            headers: {
                'Authorization': `Bearer ${token}`, // add bearer token to authorisation header
                'Content-Type': 'application/json'  // add the right content type to it
            }
        });

        //see if response goes through ok otherwise throw a error
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }

        const data = await response.json(); // Parse the JSON response

        // Handle the response data (all preference lists)
        console.log('All Preference Lists:', data);

        // Make sure the data is in the correct format
        if (Array.isArray(data)) {
            const prefListContainer = document.getElementById("mySelect");
            
            // Loop through each org
            data.forEach(org => {
                var keycloakId = "<?php echo $user['id'] ?>"; // get keycloak id from hackproof php page
                alert(org.USR_Keycloak_ID); // alert for debugging purposes
                alert("alert" + keycloakId); // alert for debugging purposes
                if(org.USR_Keycloak_ID === keycloakId) {
                    alert(org.USR_Keycloak_ID); //alert the keycloak id for debugging purposes
                    orgArray.push(org.ORG_ID);  // Set the Org ID as the value
                }
            });
            fetchOrganisationData(); // call php function after all necessary data has been retrieved
        } else {
            console.error("The response is not an array", data); // show a error log if data can not be retrieved because of a problem with the server
        }
    } catch (error) {
        console.error('Error fetching all preference lists:', error); // show error if all preferernce list can not be retrieved
        //window.location.href = "./dashboard.php"; // return the user back to the usr dashboard (depreceated)
    }
}
// call the function
fetchAllPrefLists();

//function to fetch all organisation data and add it to select box
async function fetchOrganisationData() {
    const proxyUrl = cors; // Define the proxy URL
    const url = org; // Define the URL of your GET endpoint
    const proxyUrlWithTarget = proxyUrl + url; // Use the proxy to forward the request over HTTPS
    const token = sessionStorage.getItem("accesToken");  // Define the Bearer token inside the function

    try {
        // Fetch data from the API with the Authorization header
        const response = await fetch(proxyUrlWithTarget, {
            method: 'GET',
            headers: {
                'Authorization': `Bearer ${token}`,
                'Content-Type': 'application/json'  
            }
        });

        // Check if the response is okay otherwise throw an error
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }

        const data = await response.json(); // Parse the JSON response
        console.log('All Preference Lists:', data); // Handle the response data (all preference lists)

        // Make sure the data is in the correct format
        if (Array.isArray(data)) {
            const prefListContainer = document.getElementById("mySelect"); // get element select box so retrieved data can be added to it
            
            // Loop through each org
            data.forEach(org => {
                if (orgArray.includes(org.ORG_ID)) { // Check if the org_id matches
                    const option = document.createElement('option'); // Create the option element
                    option.value = org.ORG_ID;  // Set the Org ID as the value
                    option.textContent = org.ORG_FullName;  // Display the Full Name of the organization
                    prefListContainer.appendChild(option); // finally apend the new option value to the select box
                }
            });
        } else {
            console.error("The response is not an array", data); // show an error if data could not be retrieved because of server issues
        }
    } catch (error) {
        console.error('Error fetching organization data:', error); // show an error if organisation data could not be retrieved
    }
}

function confirmOrg() {
    const selectBox = document.getElementById("mySelect"); // Get the select element by its ID
        if (selectBox.value === "gebruiker") { // check if user selected is user
            window.location.href = "./dashboard"; // return the user to the user dashboard (depreceated)
            document.cookie = `usr_type=usr; path=/; max-age=${100 * 365 * 24 * 60 * 60};`; // add cookie for selected option by user
        } else { // if not true, so its an organisation then
            const selectedText = selectBox.options[selectBox.selectedIndex].textContent; // Retrieve the text of the selected option
            const selectedId = selectBox.options[selectBox.selectedIndex].value; // Retrieve the text of the selected option
            //alert(selectedText); // Output the selected text
            document.cookie = `org_text=${selectedText}; path=/; max-age=${100 * 365 * 24 * 60 * 60};`; // add cookie for selected option by user
            document.cookie = `org_id=${selectedId}; path=/; max-age=${100 * 365 * 24 * 60 * 60};`; // add cookie for selected option by user
            document.cookie = `usr_type=org; path=/; max-age=${100 * 365 * 24 * 60 * 60};`; // add cookie for selected option by user
            window.location.href = "./dashboard"; // send them back to organisation dashboard
        }
    }

</script>