 /**
     * Function to perform a GET request to an API
     * @param {string} url - The API endpoint to fetch data from
     * @returns {Promise} - Resolves with the JSON response, or rejects with an error
     */
    function fetchData(url) {
        return fetch(url)
        .then(response => {
            if (!response.ok) {
            throw new Error(`HTTP error! Status: ${response.status}`);
            }
            return response.json(); // Parse the JSON response
        })
        .catch(error => {
            console.error('Error fetching data:', error);
            throw error; // Re-throw the error for further handling
        });
    }

    function showPopup(message, type = "error") {
        const popup = document.getElementById("popup");
        const popupContent = document.getElementById("popup-content");
        const popupMessage = document.getElementById("popup-message");

        // Set the message
        popupMessage.textContent = message;

        // Set the styling based on type (error or success)
        if (type === "success") {
            popupContent.classList.remove("bg-red-100", "text-red-600");
            popupContent.classList.add("bg-green-100", "text-green-600");
        } else {
            popupContent.classList.remove("bg-green-100", "text-green-600");
            popupContent.classList.add("bg-red-100", "text-red-600");
        }

        // Show the popup
        popup.classList.remove("hidden");
        popupContent.classList.remove("opacity-0");

        // Auto-hide after 4 seconds
        setTimeout(() => {
            popupContent.classList.add("opacity-0");
            setTimeout(() => popup.classList.add("hidden"), 500); // Hide completely after fade-out
        }, 4000);
    }

    // Function to get a cookie by name
    function getCookie(name) {
        const value = `; ${document.cookie}`;
        const parts = value.split(`; ${name}=`);
        return parts.pop().split(';').shift();
    }

    function setArrayCookie(name, array, maxAge) {
        // Convert the array to a JSON string
        const arrayString = JSON.stringify(array);
        // Set the cookie with the given name, array string, and max-age
        document.cookie = `${name}=${arrayString}; path=/; max-age=${maxAge}`;
    }

    async function hasMatchingPreferenceList(plot, idForInsert) {
        try {
            const plotsResponse = await fetch(getAllPlots + idForInsert);
            if (!plotsResponse.ok) {
                throw new Error('Plots network response was not ok');
            }
    
            const plotsData = await plotsResponse.json();
    
            // Zoek naar een overeenkomstige voorkeurenlijst
            const matchingPlot = plotsData.data.slice().reverse().find(item => item.ProfPrefProp_Id == plot.ProfPrefPropId);
    
            return matchingPlot !== undefined; // Returns true if found, false otherwise
        } catch (error) {
            console.error("Error fetching plots:", error);
            return false; // Return false in case of an error
        }
    }

    

    