const insertIntoUsers = async ({
    PropPrefUser_id,
    PropPrefKeycloak_id,
    PropPrefUser_IdinCheck,
    PropPrefUser_Streetname,
    PropPrefUser_Nr,
    PropPrefUser_PC,
    PropPrefUser_City,
    PropPrefUser_Country,
    PropPrefUser_Entry_ID
}) => {
    // Construct the endpoint URL with the parameters
    const url = `${insertIntoUsers2}${PropPrefUser_id}/${PropPrefKeycloak_id}/${PropPrefUser_IdinCheck}/${PropPrefUser_Streetname}/${PropPrefUser_Nr}/${PropPrefUser_PC}/${PropPrefUser_City}/${PropPrefUser_Country}/${PropPrefUser_Entry_ID}`;
    
    try {
        // Make the request to the server
        const response = await fetch(url, {
            method: 'GET', // Replace with 'POST' if the backend expects a POST request
            headers: {
                'Content-Type': 'application/json'
            }
        });

        // Check if the response is successful
        if (!response.ok) {
            throw new Error(`Failed to insert data: ${response.statusText}`);
        }

        // Parse the JSON response
        const data = await response.json();
        console.log("Insertion result:", data);
        return data; // Return the result for further processing
    } catch (error) {
        console.error("Error inserting data:", error);
        throw error; // Re-throw the error for the caller to handle
    }
};

function getIdForInsertOfUpdate(type, id) {
    alert(type);
    alert(id);
    if(type == "usr") {

    } else {

    }
}