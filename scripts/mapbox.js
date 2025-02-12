async function getHouseNumbersAndPostcodesInPolygon(lineCoordinates) {
    // Convert lineCoordinates to OpenStreetMap's polygon format
    const polygonString = lineCoordinates.map(([lng, lat]) => `${lat} ${lng}`).join(" ");

    // Overpass API query to get house numbers and postal codes in the polygon
    const overpassQuery = `
        [out:json];
        (
            node["addr:housenumber"](poly:"${polygonString}");
            way["addr:housenumber"](poly:"${polygonString}");
            relation["addr:housenumber"](poly:"${polygonString}");
            node["addr:postcode"](poly:"${polygonString}");
            way["addr:postcode"](poly:"${polygonString}");
            relation["addr:postcode"](poly:"${polygonString}");
        );
        out body;
        >;
        out skel qt;
    `;

    const apiUrl = overpassApiUrl;

    try {
        const response = await fetch(apiUrl, {
            method: "POST",
            body: overpassQuery,
            headers: {
                "Content-Type": "application/x-www-form-urlencoded",
            },
        });

        if (!response.ok) {
            throw new Error(`HTTP error! Status: ${response.status}`);
        }

        const data = await response.json();

        // Extract house numbers and postal codes from the response
        const results = [];
        data.elements
            .filter(element => element.tags)
            .forEach(element => {
                const houseNumber = element.tags["addr:housenumber"];
                const postcode = element.tags["addr:postcode"];
                if (houseNumber && postcode) {
                    results.push({
                        houseNumber,
                        postcode,
                    });
                }
            });

        return results;
    } catch (error) {
        console.error("Error fetching house numbers and postal codes in polygon:", error);
        throw error;
    }
}

function calculateCentroid(coordinatesString) {
    // Parse the coordinates into an array of [lng, lat] pairs
    const coordinates = coordinatesString.split(',').map(Number).reduce((acc, val, index, arr) => {
        if (index % 2 === 0) {
            acc.push([arr[index], arr[index + 1]]);
        }
        return acc;
    }, []);

    // Ensure the polygon is closed by repeating the first point at the end
    if (coordinates[0][0] !== coordinates[coordinates.length - 1][0] || 
        coordinates[0][1] !== coordinates[coordinates.length - 1][1]) {
        coordinates.push(coordinates[0]);
    }

    let highestX = -Infinity;
    let lowestX = Infinity;
    let highestY = -Infinity;
    let lowestY = Infinity;

    // Loop through all coordinates to find the extremes
    for (let i = 0; i < coordinates.length; i++) {
        const [x, y] = coordinates[i];

        if (x > highestX) highestX = x;
        if (x < lowestX) lowestX = x;
        if (y > highestY) highestY = y;
        if (y < lowestY) lowestY = y;
    }

    // Calculate the middle point (centroid) from the highest and lowest values
    const centroidX = (highestX + lowestX) / 2;
    const centroidY = (highestY + lowestY) / 2;

    console.log(`Middle Point (Centroid): (${centroidX}, ${centroidY})`);

    return { lng: centroidX, lat: centroidY };
}