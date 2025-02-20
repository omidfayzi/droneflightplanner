<?php
    include '../functions/functions.php';
    login();

    $user = $_SESSION["user"];

    // Define the user name
    $userName = $user['first_name'];
    //define the save attributes for the page for the save button, this also un-hides it
    $saveAttributes = "setPlot()";
    //define if you want to include the componment setPlotName
    $includeSetPlotName = 1;
    //define if you want to include the componment setPlotName
    $includeCheckWithKadaster = 1;
    //define if you want to include the componment setPrefName
    $includeSetPrefName = 0;
    //define if you want to include the the componkent include check
    $includeCheckWithIdin = 0;
    //select the right attributes for the page
    $rightAttributes = 0;
    // define the go back url
    $gobackUrl = 1;
    // Define the title of the head
    $headTitle = fetchPropPrefTxt(4);
    // Define if the head should be shown or not
    $showHeader = 1;

    // this is the body of the webpage
    $bodyContent = "
    <div class='bg-black m-4 sm:h-[calc(100vh-130px)] h-[calc(100%-250px)] rounded-xl overflow-hidden overscroll-none'>
        <div id='map' class='overflow-hidden rounded-xl w-full h-full overscroll-none'></div>
        <img id='screenshot' style='display: none; margin-top: 10px;'/>
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
var setPlotName = document.getElementById("setPlotName");
var checkWithKadaster = document.getElementById("checkWithKadaster");
var plotName = document.getElementById("plotNameValue");
var plotNameName = 0;
let setPlotCounter = 0;
mapboxgl.accessToken = '<?php echo $mapBoxAccessToken; ?>';

let markers = [];
let lineCoordinates = [];
let firstMarkerCoordinates = null; // Variabele om de coördinaten van de eerste marker op te slaan


function confirmPlotName() {
    var plotNameValue = plotName.value;
    if (plotNameValue.trim() !== "") {
        plotNameName = plotNameValue;
        setPlotName.style.display = "none";
        checkWithKadaster.style.display = "flex";
    } else {
        showPopup("<?php echo fetchPropPrefTxt(26) ?>", "error");
    }
}

function confirmKadasterCheck(kadaster) {
    if(kadaster == 0) {
        let houseNumbers; // For storing house numbers
        let postcodes; 

        getHouseNumbersAndPostcodesInPolygon(lineCoordinates)
            .then(results => {
                if (results.length >= 0) {
                    houseNumbers = results.map(r => r.houseNumber);
                    postcodes = results.map(r => r.postcode);

                    const id = 1;
                    const shortName = 'ShortName';
                    const longName = plotNameName;
                    const coordinates = lineCoordinates;
                    const postalCode = postcodes;
                    const perceelNummer = houseNumbers;
                    const userId = idForInsert;

                    const apiUrl = `${insertPlot}${id}/${shortName}/${longName}/${coordinates}/${postalCode}/${perceelNummer}/${userId}`;

                    fetchData(apiUrl)
                        .then(data => {
                            showPopup("<?php echo fetchPropPrefTxt(27) ?>", "success");
                            console.log('Response data from insert into database API call:', data);
                            checkWithKadaster.style.display = "none";
                        })
                        .catch(error => {
                            showPopup(`<?php echo fetchPropPrefTxt(28) ?> ${error}`, "error");
                            checkWithKadaster.style.display = "none";
                        });
                } else {
                    showPopup("<?php echo fetchPropPrefTxt(29) ?>", "error");
                    checkWithKadaster.style.display = "none";
                }
            })
            .catch(error => {
                console.error("Error fetching data:", error);
                showPopup("<?php echo fetchPropPrefTxt(30) ?>", "error");
                checkWithKadaster.style.display = "none";
            });
    }
}

function CalculateCentroidOffset(centroid2lng, centroid2lat, centroidlng, centroidlat, offset) {
    i = 0;
    if(centroid2lng > (centroidlng - offset)) {
        i++;
    }
    if(centroid2lng < (centroidlng + offset)) {
        i++;  
    }
    if(centroid2lat > (centroidlat - offset)) {
        i++;
    }
    if(centroid2lat < (centroidlat + offset)) {
        i++;
    }

    if(i == 4) {
        return true;
    } else {
        return false;
    }
}

async function setPlot() {
    var text = 0;
    if(setPlotCounter == 1) {
                    const offsetValue = 0.00003;
                    var PlotWithSameValue = 0;
                    // Calculate the centroid for the plot
                    const coordinatesString = String(lineCoordinates);
                    const centroid = calculateCentroid(coordinatesString);

                    // Ensure centroid calculation was successful
                    if (!centroid || isNaN(centroid.lat) || isNaN(centroid.lng)) {
                        console.warn('Invalid centroid for plot:', plot);
                    }  

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

                    // Loop through all the plots and generate static images
                    for (const plot of data.data) {
                        const coordinatesString2 = String(plot.ProfPrefProp_Coord);
                        const centroid2 = calculateCentroid(coordinatesString2);
                        console.log(centroid2);
                        true2 = CalculateCentroidOffset(centroid2.lng, centroid2.lat, centroid.lng, centroid.lat, offsetValue);
                        if(true2) {
                            PlotWithSameValue = 1;
                            break;
                        }
                    }

                    if(PlotWithSameValue == 0) {
                        setPlotName.style.display = "flex";
                    } else {
                        setPlotName.style.display = "none";
                        showPopup("Er is al een perceel die ongeveer op hetzelfde afstand zit", "error");
                    }

   
                    //alert(centroid.lat);
                    //alert(centroid.lng);

                    fetch(`${mapBoxReverseGeocoding}${centroid.lng},${centroid.lat}.json?access_token=${mapboxgl.accessToken}`)
                    .then(response => response.json())
                    .then(data => {
                        var address = data.features[0].place_name;
                        //alert("Full Address: " + address);
                        plotName.value = address;
                    })
                    .catch(error => console.error('Error:', error));

                    //if(postcodes.length > 0 && houseNumbers.length > 0) {
                        //text = "Perceel met postcodes " + postcodes + " en huisnummers " + houseNumbers;
                    //} else {
                        //if(postcodes.length > 0) {
                            //text = "Perceel met postcodes " + postcodes + " en huisnummer" + houseNumbers;
                        //} else {
                            //(houseNumbers.length > 0) {
                                //text = "Perceel met postcode " + postcodes + " en huisnummers" + houseNumbers;
                            //} else {
                                //text = "Perceel met postcode " + postcodes + " en huisnummer" + houseNumbers;
                            //}
                        //}
                    //}
                }
}

    var saveAttributes = document.getElementById("setPlot()");

    navigator.geolocation.getCurrentPosition(successLocation, errorLocation, {
        enableHighAccuracy: true
    });

    function successLocation(position) {
        const userCoordinates = [position.coords.longitude, position.coords.latitude];
        setupMap(userCoordinates);
    }

    function errorLocation() {
        const defaultCoordinates = [5.33178975679067, 51.80620168475037];
        setupMap(defaultCoordinates);
    }

    function setupMap(centerCoordinates) {
        var map = new mapboxgl.Map({
            container: 'map',
            style: 'mapbox://styles/mapbox/streets-v12',
            center: centerCoordinates,
            zoom: 10
        });

        map.addControl(new MapboxGeocoder({
            accessToken: mapboxgl.accessToken,
            mapboxgl: mapboxgl
        }));

        map.addControl(new mapboxgl.NavigationControl());
        new mapboxgl.Marker({ color: "#FF0000" }).setLngLat(centerCoordinates).addTo(map);

        map.on('load', function () {
            map.addSource('places', {
                'type': 'geojson',
                'data': {
                    'type': 'FeatureCollection',
                    'features': []
                }
            });

            map.addLayer({
                'id': 'places',
                'type': 'symbol',
                'source': 'places',
                'layout': {
                    'icon-image': '{icon}',
                    'icon-allow-overlap': true
                }
            });

            // Voeg lijnsource toe
            map.addSource('line', {
                'type': 'geojson',
                'data': {
                    'type': 'Feature',
                    'geometry': {
                        'type': 'LineString',
                        'coordinates': []
                    }
                }
            });

            // Voeg lijnlaag toe
            map.addLayer({
                'id': 'line',
                'type': 'line',
                'source': 'line',
                'layout': {
                    'line-join': 'round',
                    'line-cap': 'round'
                },
                'paint': {
                    'line-color': '#FF00FF',
                    'line-width': 3
                }
            });
        });

        map.addControl(new mapboxgl.FullscreenControl());

        const geocoder = new MapboxGeocoder({
            accessToken: mapboxgl.accessToken,
        });

        geocoder.on('result', (e) => {
            const address = e.result.place_name;
            const coordinates = e.result.geometry.coordinates;
            console.log(`Address: ${address}, Coordinates: ${coordinates}`);
        });

        geocoder.on('clear', () => {
            geocoderResult = {};
        });

        // Get the cookie value
        const cookieValue = getCookie("lineCoordinates");

        // Parse the JSON string back into an array
        if (cookieValue) {
            const myArrayFromCookie = JSON.parse(cookieValue);
            lineCoordinates = myArrayFromCookie;

            // Loop through each coordinate in lineCoordinates
            lineCoordinates.forEach((coords, index) => {
                if (lineCoordinates.length >= 2 && index < lineCoordinates.length - 1) {
                    const lngLat = new mapboxgl.LngLat(coords[0], coords[1]); // Create a LngLat object
                    const marker = new mapboxgl.Marker({ color: "#00FF00" }) // Add a marker for visualization
                        .setLngLat(lngLat)
                        .addTo(map);

                    markers.push(marker); // Store the marker
                } else if (lineCoordinates.length <= 3) {
                    const lngLat = new mapboxgl.LngLat(coords[0], coords[1]); // Create a LngLat object
                    const marker = new mapboxgl.Marker({ color: "#00FF00" }) // Add a marker for visualization
                        .setLngLat(lngLat)
                        .addTo(map);

                    markers.push(marker); // Store the marker
                }
            });
            if(lineCoordinates.length > 0) {
                showPopup("<?php echo fetchPropPrefTxt(31) ?>", "success");
                setPlotCounter = 1;
                saveAttributes.classList.remove("bg-red-800");
                saveAttributes.classList.add("bg-green-500");
            } else {
                showPopup("<?php echo fetchPropPrefTxt(32) ?>", "success");
            }
        }
        

        map.on('click', function (e) {
            const clickedCoordinates = e.lngLat;
            let closestSegmentIndex = -1;
            let closestSegmentDistance = Infinity;

            //alert("hello");

            // Function to calculate the distance from a point to a line segment
            const pointToSegmentDistance = (point, start, end) => {
                const A = point.lng - start.lng;
                const B = point.lat - start.lat;
                const C = end.lng - start.lng;
                const D = end.lat - start.lat;

                const dot = A * C + B * D;
                const len_sq = C * C + D * D;
                const param = len_sq !== 0 ? dot / len_sq : -1;

                let xx, yy;

                if (param < 0) {
                    xx = start.lng;
                    yy = start.lat;
                } else if (param > 1) {
                    xx = end.lng;
                    yy = end.lat;
                } else {
                    xx = start.lng + param * C;
                    yy = start.lat + param * D;
                }

                const dx = point.lng - xx;
                const dy = point.lat - yy;
                return Math.sqrt(dx * dx + dy * dy);
            };

            // Clean up the lineCoordinates array to avoid incorrect connections
            if (markers.length > 2) {
                lineCoordinates.pop(); // Remove the loop-back connection temporarily
            }

            // If we have at least 2 markers, find the closest segment
            if (markers.length >= 2) {
                for (let i = 0; i < markers.length - 1; i++) {
                    const start = markers[i].getLngLat();
                    const end = markers[i + 1].getLngLat();
                    const distance = pointToSegmentDistance(clickedCoordinates, start, end);

                    if (distance < closestSegmentDistance) {
                        closestSegmentDistance = distance;
                        closestSegmentIndex = i;
                    }
                }

                // Check the loop-back segment from the last marker to the first
                const start = markers[markers.length - 1].getLngLat();
                const end = markers[0].getLngLat(); // Loop-back to the first marker
                const distanceToLoopBack = pointToSegmentDistance(clickedCoordinates, start, end);

                if (distanceToLoopBack < closestSegmentDistance) {
                    closestSegmentDistance = distanceToLoopBack;
                    closestSegmentIndex = markers.length - 1; // Mark the last segment as closest
                }

                // Insert a new marker at the closest segment
                if (closestSegmentIndex !== -1) {
                    const insertIndex = closestSegmentIndex + 1; // Insert between the two closest markers

                    // Create a new marker at the clicked location
                    const newMarker = new mapboxgl.Marker({ color: "#00FF00" })
                        .setLngLat(clickedCoordinates)
                        .addTo(map);

                    // Insert the new marker and coordinates at the correct position
                    markers.splice(insertIndex, 0, newMarker);
                    lineCoordinates.splice(insertIndex, 0, [clickedCoordinates.lng, clickedCoordinates.lat]);

                    // If markers form a loop, reconnect the first marker to close the loop
                    if (markers.length > 2) {
                        lineCoordinates.push(lineCoordinates[0]); // Reconnect the loop
                    }
                    if (markers.length >= 2) {
                    setArrayCookie("lineCoordinates", lineCoordinates, 3600); // Sets the cookie for 1 hour
                    updateLineSource(); // Update the map with the new line
                    }
                    //alert('Coordinates:\n' + lineCoordinates.map((coord, index) => 
       // `Index ${index}: [Longitude: ${coord[0]}, Latitude: ${coord[1]}]`
    //).join(', '));
                }
            } else {
                setPlotCounter = 1;
                saveAttributes.classList.remove("bg-red-800");
                saveAttributes.classList.add("bg-green-500");
                // Handle adding the first or second marker
                const newMarker = new mapboxgl.Marker({ color: "#00FF00" })
                    .setLngLat(clickedCoordinates)
                    .addTo(map);

                markers.push(newMarker);
                lineCoordinates.push([clickedCoordinates.lng, clickedCoordinates.lat]);

                // Only update the line source if there are at least 2 markers
                if (markers.length >= 2) {
                    updateLineSource(); // Update the map with the new line
                }
                setArrayCookie("lineCoordinates", lineCoordinates, 3600); // Sets the cookie for 1 hour
            }
        });

        map.on('contextmenu', function (e) {
    if (markers.length > 0) {
        const clickedCoordinates = e.lngLat;
        let closestMarker = null;
        let closestDistance = Infinity;
        let closestIndex = -1;

        // Find the closest marker
        markers.forEach((marker, index) => {
            const markerLngLat = marker.getLngLat();
            const distance = Math.sqrt(
                Math.pow(clickedCoordinates.lng - markerLngLat.lng, 2) +
                Math.pow(clickedCoordinates.lat - markerLngLat.lat, 2)
            );

            if (distance < closestDistance) {
                closestDistance = distance;
                closestMarker = marker;
                closestIndex = index;
            }
        });

        // If a closest marker was found, remove it
        if (closestMarker) {
                // Remove the marker from the map and the array
                closestMarker.remove();
                markers.splice(closestIndex, 1); // Remove the marker from the array
                lineCoordinates.splice(closestIndex, 1); // Remove the corresponding coordinate

                if (closestIndex === 0 && markers.length > 2) {
                    const firstCoordinate2 = lineCoordinates[0];
                    lineCoordinates.pop(); // Remove the last loop-back coordinate
                    lineCoordinates.push(firstCoordinate2); // Reconnect the loop
                }

                if (markers.length === 2) {
                   lineCoordinates.pop(); // Remove the last loop-back coordinate
                }

                if (markers.length === 0) {
                   setPlotCounter = 0;
                   saveAttributes.classList.remove("bg-green-500");
                   saveAttributes.classList.add("bg-red-800");
                }
                // Update the line source after removal
                updateLineSource();
                setArrayCookie("lineCoordinates", lineCoordinates, 3600); // Sets the cookie for 1 hour
        }
    }
});

///for smartphones delete

map.on('touchstart', function (e) {
    const longPressDuration = 600; // milliseconds for long press
    let longPressTimer = null;

    // Start the timer on touchstart
    longPressTimer = setTimeout(() => {
        handleLongPressDelete(e); // Call delete function on long press
    }, longPressDuration);

    // Clear the timer if touch ends before duration (no long press)
    map.on('touchend', cancelLongPress);
    map.on('touchcancel', cancelLongPress);

    function cancelLongPress() {
        clearTimeout(longPressTimer);
        map.off('touchend', cancelLongPress);
        map.off('touchcancel', cancelLongPress);
    }
});

// Function to delete marker on long press
function handleLongPressDelete(e) {
    if (markers.length > 0) {
        const clickedCoordinates = e.lngLat;
        let closestMarker = null;
        let closestDistance = Infinity;
        let closestIndex = -1;

        markers.forEach((marker, index) => {
            const markerLngLat = marker.getLngLat();
            const distance = Math.sqrt(
                Math.pow(clickedCoordinates.lng - markerLngLat.lng, 2) +
                Math.pow(clickedCoordinates.lat - markerLngLat.lat, 2)
            );

            if (distance < closestDistance) {
                closestDistance = distance;
                closestMarker = marker;
                closestIndex = index;
            }
        });

        //alert(closestDistance);

        if (closestMarker && closestDistance < 0.003) {
            closestMarker.remove();
            markers.splice(closestIndex, 1);
            lineCoordinates.splice(closestIndex, 1);

            if (closestIndex === 0 && markers.length > 2) {
                const firstCoordinate = lineCoordinates[0];
                lineCoordinates.pop();
                lineCoordinates.push(firstCoordinate);
            }

            if (markers.length === 2) {
                lineCoordinates.pop();
                setPlotCounter = 0;
                saveAttributes.classList.remove("bg-green-500");
                saveAttributes.classList.add("bg-red-800");
            }
            updateLineSource();
            setArrayCookie("lineCoordinates", lineCoordinates, 3600); // Sets the cookie for 1 hour
        }
    }
}

        function updateLineSource() {
            map.getSource('line').setData({
                'type': 'Feature',
                'geometry': {
                    'type': 'LineString',
                    'coordinates': lineCoordinates
                }
            });
        }
        setTimeout(function () {
            updateLineSource(); // Update the map with the new line
            console.log("updateLineSource called after delay");
        }, 800); // Delay in milliseconds (1000ms = 1 second)
        function isLastIndex(array, index) {
            return index === array.length - 1;
        }


       // map.on('click', function (e) {
           // const coordinates = e.lngLat;
           // alert(`Coordinates: \nLongitude: ${coordinates.lng.toFixed(6)} \nLatitude: ${coordinates.lat.toFixed(6)}`);
       // });
}
</script>