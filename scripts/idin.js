//js file for IDIN Functionality

    // Function to generate a unique entrance code
    function generateUniqueEntranceCode(responseType, merchantId, creditorId) {
        // Get the current date and time in UTC
        const now = new Date();
        
        // Generate a timestamp with milliseconds in the format YYYYMMDDHHMMSSFFF
        const year = now.getUTCFullYear();
        const month = String(now.getUTCMonth() + 1).padStart(2, '0'); // Months are 0-indexed
        const day = String(now.getUTCDate()).padStart(2, '0');
        const hours = String(now.getUTCHours()).padStart(2, '0');
        const minutes = String(now.getUTCMinutes()).padStart(2, '0');
        const seconds = String(now.getUTCSeconds()).padStart(2, '0');
        const milliseconds = String(now.getUTCMilliseconds()).padStart(3, '0');
        
        const timestamp = `${year}${month}${day}${hours}${minutes}${seconds}${milliseconds}`;
        
        // Generate a short UUID (8 characters from a random hex string)
        const uniqueId = Math.random().toString(16).substring(2, 10);
        
        // Combine responseType, merchantId, creditorId, timestamp, and UUID
        const uniqueCode = `${responseType}${merchantId}-${creditorId}-${timestamp}-${uniqueId}`;
        
        return uniqueCode;
    }

    // Main function to generate and log all required variables
    function generateVariables() {
        const now = new Date();

        // Format the date as per the required RFC 7231 format
        const utcDate = now.toUTCString();

        // Generate the filename in the format `ITX-S2336-BSP1-YYYYMMDDHHMMSSFFF.xml`
        const year = now.getUTCFullYear();
        const month = String(now.getUTCMonth() + 1).padStart(2, '0'); // Months are 0-indexed
        const day = String(now.getUTCDate()).padStart(2, '0');
        const hours = String(now.getUTCHours()).padStart(2, '0');
        const minutes = String(now.getUTCMinutes()).padStart(2, '0');
        const seconds = String(now.getUTCSeconds()).padStart(2, '0');
        const milliseconds = String(now.getUTCMilliseconds()).padStart(3, '0');

        const filename = `ITX-S2336-BSP1-${year}${month}${day}${hours}${minutes}${seconds}${milliseconds}.xml`;
        const date = `${year}${month}${day}${hours}${minutes}${seconds}${milliseconds}`;

        // Generate the dynamic ISO 8601 timestamp
        const createDateTime = now.toISOString();

        // Generate a unique entrance code
        const responseType = "succesHIO100OIHtest";
        const merchantId = "0020000387";
        const creditorId = "NL12ZZZ123456780000";
        const entranceCode = generateUniqueEntranceCode(responseType, merchantId, creditorId);

        // Log the generated values
        console.log("Generated x-ttrs-date:", utcDate);
        console.log("Generated x-ttrs-filename:", filename);
        console.log("Generated createDateTime:", createDateTime);
        console.log("Generated uniqueEntranceCode:", entranceCode);

        // Return the generated values as an object (optional)
        return {
            xTtrsDate: utcDate,
            xTtrsFilename: filename,
            createDateTime: createDateTime,
            uniqueEntranceCode: entranceCode,
            date: date
        };
    }

    // Function to make a POST request to create a transaction using a proxy
    async function sendTransactionRequest() {
        // Proxy URL (CORS Anywhere)
        const proxyUrl = corsAnywhere;
        // Target URL
        const targetUrl = `${bluemCreateTransaction}?token=${blueMToken}`;
        // Full URL (proxy + target)
        const url = proxyUrl + targetUrl;

        // Define headers
        const headers = {
            "Origin": mainUrl, // Add the Origin header
            "X-Requested-With": "XMLHttpRequest", // Add the X-Requested-With header
            "x-ttrs-date": new Date().toUTCString(),
            "x-ttrs-files-count": "1",
            "x-ttrs-filename": "ITX-S2336-BSP1-" + new Date().toISOString().replace(/[-:.TZ]/g, "") + ".xml",
            "Content-Type": "application/xml;type=ITX;charset=utf-8",
            "Request-Type": "IDIN_IDENTITY_CHECK"
        };

        const xmlBody = `<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
            <IdentityInterface xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" type="TransactionRequest" mode="direct" 
            senderID="${bluemSenderId}" version="1.0" createDateTime="${new Date().toISOString()}" messageCount="1" 
            xsi:noNamespaceSchemaLocation="../IdentityInterface.xsd">
                <IdentityTransactionRequest entranceCode="succesHIO100OIHtest${new Date().toISOString().replace(/[-:.TZ]/g, "")}" language="nl" brandID="${bluemIdinBrandId}" sendOption="none">
                    <RequestCategory>
                        <CustomerIDRequest action="request"/>
                        <NameRequest action="request"/>
                        <AddressRequest action="request"/>
                        <BirthDateRequest action="request"/>
                        <AgeCheckRequest ageOrOlder="18" action="skip"/>
                        <GenderRequest action="request"/>
                        <TelephoneRequest action="skip"/> 
                        <EmailRequest action="request"/>
                    </RequestCategory>
                    <Description>Identificatie voor demo</Description><!--description is shown to customer-->
                    <DebtorReturnURL automaticRedirect="1">${mainUrl}/pages/close-page.php</DebtorReturnURL>
                </IdentityTransactionRequest>
            </IdentityInterface>`;

        try {
            // Make the POST request
            const response = await fetch(url, {
                method: "POST",
                headers: headers,
                body: xmlBody,
            });

            if (response.ok) {
                // Return the parsed response as text
                const result = await response.text();
                return result;
            } else {
                // Handle HTTP errors
                throw new Error(`Error: ${response.status} ${response.statusText}`);
            }
        } catch (error) {
            console.error("Fetch error:", error);
        }
    }

    async function sendStatusRequest(entranceCode, transactionID) {
        const proxyUrl = cors;
        const targetUrl = `${bluemRequestTransaction}?token=${blueMToken}`;
        const url = proxyUrl + targetUrl;
    
        const headers = {
            "Origin": mainUrl,
            "X-Requested-With": "XMLHttpRequest",
            "x-ttrs-date": new Date().toUTCString(),
            "x-ttrs-files-count": "1",
            "x-ttrs-filename": "ISX-S2336-BSP1-" + new Date().toISOString().replace(/[-:.TZ]/g, "") + ".xml",
            "Content-Type": "application/xml;type=ISX;charset=utf-8",
        };
    
        const xmlBody = `
            <?xml version="1.0" encoding="UTF-8" standalone="yes"?>
            <IdentityInterface xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" type="StatusRequest" mode="direct"
            senderID="${bluemSenderId}" version="1.0" createDateTime="2019-05-02T08:30:15.628Z" messageCount="1">
                <IdentityStatusRequest entranceCode="${entranceCode}">
                    <TransactionID>${transactionID}</TransactionID>
                </IdentityStatusRequest>
            </IdentityInterface> 
        `;
    
        try {
            const response = await fetch(url, {
                method: "POST",
                headers: headers,
                body: xmlBody,
            });
    
            if (response.ok) {
                const result = await response.text();
                return result;
            } else {
                const errorText = `Error: ${response.status} - ${response.statusText}`;
                alert(errorText);
                throw new Error(errorText);
            }
        } catch (error) {
            alert("Fetch error occurred:\n" + error.message);
            console.error("Fetch error:", error);
        }
    }    

    async function processTransactionRequest() {
        try {
            // Call the function and get the result
            const xmlResponse = await sendTransactionRequest();

            // Parse the XML response
            const parser = new DOMParser();
            const xmlDoc = parser.parseFromString(xmlResponse, "application/xml");

            // Extract Entrance Code
            const entranceCodeNode = xmlDoc.querySelector("IdentityTransactionResponse[entranceCode]");
            const entranceCode = entranceCodeNode?.getAttribute("entranceCode");

            // Extract Transaction ID
            const transactionIDNode = xmlDoc.querySelector("TransactionID");
            const transactionID = transactionIDNode?.textContent;

            // Extract ShortTransactionURL or TransactionURL
            const shortTransactionURLNode = xmlDoc.querySelector("ShortTransactionURL");
            const shortTransactionURL = shortTransactionURLNode?.textContent;

            // Validate and process the extracted values
            if (entranceCode && transactionID && shortTransactionURL) {
                // Open the Short Transaction URL in a new tab
                const bankWindow = window.open(shortTransactionURL, "_blank");

                // Wait for the bank page to be closed
                const checkWindowClosed = setInterval(() => {
                    if (bankWindow.closed) {
                        clearInterval(checkWindowClosed);
                        (async () => {
                            const result = await afterBankPageClosed(entranceCode, transactionID);
                        
                            if (result) {
                                insertIntoUsers({
                                    PropPrefUser_id: "1",
                                    PropPrefKeycloak_id: keycloakId,
                                    PropPrefUser_IdinCheck: "1",
                                    PropPrefUser_Streetname: result.street,
                                    PropPrefUser_Nr: result.houseNumber,
                                    PropPrefUser_PC: result.postalCode,
                                    PropPrefUser_City: result.city,
                                    PropPrefUser_Country: result.country,
                                    PropPrefUser_Entry_ID: "1"
                                }).then((result) => {
                                    console.log("Insertion successful:", result);
                                    showPopup("U bent succesvol geverifieerd met IDIN.", "success");
                                }).catch((error) => {
                                    console.error("Failed to insert data:", error);
                                    showPopup("Fout bij toevoegen van IDIN gegevens, probeer later opnieuw!", "error");
                            });
                            checkWithIdin.style.display = "none";
                        
                            } else {
                                console.log("Failed to retrieve user details.");
                                showPopup("Fout bij toevoegen van IDIN gegevens, probeer later opnieuw!", "error");
                            }
                        })();
                    }
                }, 1000); // Check every 1 second
            } else {
                showPopup("Fout bij het inlezen van de XML data!", "error");
                return null;
            }
        } catch (error) {
            showPopup("Fout bij het inlezen van de XML data!", "error");
            return null;
        }
    }


    // Function to execute after the bank page is closed
    async function afterBankPageClosed(entranceCode, transactionID) {
        try {
            // Call sendStatusRequest and await its result
            const xmlResponse = await sendStatusRequest(entranceCode, transactionID);

            // Parse the XML response
            const parser = new DOMParser();
            const xmlDoc = parser.parseFromString(xmlResponse, "application/xml");

            // Extract Legal Last Name
            const legalLastNameNode = xmlDoc.querySelector("LegalLastName");
            const legalLastName = legalLastNameNode?.textContent;

            // Extract Address Fields
            const streetNode = xmlDoc.querySelector("Street");
            const street = streetNode?.textContent;

            const houseNumberNode = xmlDoc.querySelector("HouseNumber");
            const houseNumber = houseNumberNode?.textContent;

            const postalCodeNode = xmlDoc.querySelector("PostalCode");
            const postalCode = postalCodeNode?.textContent;

            const cityNode = xmlDoc.querySelector("City");
            const city = cityNode?.textContent;

            const countryNode = xmlDoc.querySelector("CountryCode");
            const country = countryNode?.textContent;

            // Return the extracted data
            return {
                legalLastName,
                street,
                houseNumber,
                postalCode,
                city,
                country
            };
        } catch (error) {
            showPopup("Error bij het inlezen van de status!", "error");
            console.error("Error handling status request:", error);
            return null;
        }
    }

    const updateIdinCheckAddress = async ({
        PropPrefKeycloak_id,
        newIdinCheck,
        streetname,
        nr,
        postalCode,
        city,
        country,
    }) => {
        // Construct the URL dynamically
        const url = `${updateIdinCheckAddress2}${PropPrefKeycloak_id}/${newIdinCheck}/${streetname}/${nr}/${postalCode}/${city}/${country}`;
    
        try {
        // Make the PUT request
        const response = await fetch(url, {
            method: 'PUT',
            headers: {
            'Content-Type': 'application/json',
            },
        });
    
        // Check if the response is successful
        if (!response.ok) {
            throw new Error(`Failed to update address: ${response.statusText}`);
        }
    
        // Parse and return the JSON response
        const data = await response.json();
        console.log('Update successful:', data);
        return data;
        } catch (error) {
        console.error('Error updating address:', error);
        throw error; // Re-throw the error for the caller to handle
        }
    };