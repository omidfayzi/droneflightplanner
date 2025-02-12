<?php
    include './functions/functions.php';

    // Define if you want to include the component setPlotName
    $includeSetPlotName = 0;
    // Define if you want to include the component setPlotName
    $includeSetPrefName = 0;

    // Define if the head should be shown or not
    $showHeader = 0;

    // This is the body of the webpage
    $bodyContent = "
    <!-- Section with a background image covering the entire viewport -->
    <div class=\"p-3 min-h-screen bg-[url('/images/phone-background.jpg')] sm:bg-[url('/images/background-htd.jpg')]\" style=\"background-size: cover; background-position: center;\">
        <a href=\"./login\" class=\"absolute right-5 top-5 mb-1 mt-1 p-2 text-white flex items-center rounded-xl bg-blue-700 hover:scale-105 transition-all\">Inloggen</a>
    </div>

    <div class='flex flex-col items-center mb-6 ml-6 mr-6'>
        <div class='w-full max-w-[1200px]'>
            <!-- Section with a background -->
            <div class=\"h-screen min-h-[400px] flex flex-col items-center overflow-hidden mt-14 pt-10\">
                <h1 class=\"text-3xl text-black mb-6\">".fetchPropPrefTxt(37)."</h1>

                <div class=\"flex flex-col items-center overflow-hidden\">

                    <img id='imageContainer' src='/images/perceel.png' alt='Image description' class='sm:p-4 p-2' style='background-color: rgba(0, 0, 0, 0.4); border-radius: 1rem; object-fit: contain; max-height: calc(100% - 60px);'>

                    <div class=\"flex mt-5\">
                        <button id=\"button1\" style=\"background-color: rgba(0, 0, 0, 0.4);\" class=\"rounded-s-2xl text-white py-2 px-4 text-sm hover:bg-blue-600 transition-colors\">".fetchPropPrefTxt(38)."</button>
                        <button id=\"button2\" style=\"background-color: rgba(0, 0, 0, 0.4);\" class=\"text-white py-2 px-4 text-sm hover:bg-blue-600 transition-colors\">".fetchPropPrefTxt(39)."</button>
                        <button id=\"button3\" style=\"background-color: rgba(0, 0, 0, 0.4);\" class=\"rounded-e-2xl text-white py-2 px-4 text-sm hover:bg-blue-600 transition-colors\">".fetchPropPrefTxt(40)."</button>
                    </div>
                </div>
            </div>

            <div class=\"h-[400px] min-h-screen flex flex-col justify-center items-center w-full mt-14 pt-10\">
                <div class=\"flex justify-center items-center\">
                    <h1 class=\"mb-6 text-black text-3xl max-w-[50px];\">".fetchPropPrefTxt(41)."</h1>
                </div>
                
                <div class=\"grid sm:grid-cols-2 gap-4 h-full w-full justify-center\">
                
                    <!-- Left column with centered text -->
                        <div style=\"background-color: #E2E2E2;\" class=\"rounded-xl max-h-full min-w-full flex justify-center items-center\">
                            <h2 class=\"sm:w-full w-[60%] text-center leading-relaxed flex items-center justify-center min-text-size overflow-hidden\" style=\"max-width: 90%; max-height: 90%;\">".fetchPropPrefTxt(42)."</h2>
                        </div>


                    <!-- Right column with image -->
                    <div style=\"background-color: rgba(0, 132, 255, 0.4);\" class=\"max-h-full min-w-full rounded-xl flex justify-center items-center\">
                        <img id=\"imageContainer\" src=\"/images/idin-logo.svg\" alt=\"Image description\" class=\"sm:w-[60%] sm:h-[60%] h-[40%] w-[40%] h-full p-3 rounded-xl\" style=\"object-fit: contain;;\">
                    </div>
                </div>
            </div>

            <div class=\"flex justify-center items-center mt-14 pt-10\">
                <h1 class=\"text-black text-3xl\">".fetchPropPrefTxt(43)."</h1>
            </div>
            <div style=\"background-color: rgba(0, 0, 0, 0.4);\" class=\"bg-gray-500 mt-6 p-3 rounded-xl flex justify-between items-center\">
                <h1 class=\"text-xl text-white\">".fetchPropPrefTxt(44)."</h1>
                <a href=\"./login\" class=\"mb-1 mt-1 p-2 text-white flex items-center rounded-xl bg-blue-700 hover:scale-105 transition-all\">".fetchPropPrefTxt(45)."</a>
            </div>
        </div>
    </div>
    ";

    // Include the base template
    include './includes/header.php';
?>

<script>
    const cookieName = "language_id";
    if (!getCookie(cookieName)) {
        async function detectCountryAndSetLanguage() {
            try {
                // Fetch user's IP-based location
                const response = await fetch('https://ipapi.co/json/');
                const data = await response.json();

                const country = data.country; 

                if (country === 'NL') {
                    setLanguage('PropPrefTxt_Nl');
                } else {
                    setLanguage('PropPrefTxt_En');
                }
            } catch (error) {
                console.error('Error detecting country:', error);
                // Default to English if detection fails
                setLanguage('PropPrefTxt_En');
            }
        }

        function setLanguage(lang) {
            document.cookie = `language_id=${lang}; path=/; max-age=${100 * 365 * 24 * 60 * 60};`;
            location.reload();
        }

        // Call the function on page load
        detectCountryAndSetLanguage();
    }
    // Get the image container and buttons
    const imageContainer = document.getElementById('imageContainer');
    const button1 = document.getElementById('button1');
    const button2 = document.getElementById('button2');
    const button3 = document.getElementById('button3');

    // Define the image URLs for each button
    const images = {
        button1: {
            sm: '/images/perceel-sm.jpg',  // Image for small screens
            default: '/images/perceel.png'  // Default image
        },
        button2: {
            sm: '/images/voorkeur-sm.jpg',  // Image for small screens
            default: '/images/voorkeur.png'  // Default image
        },
        button3: {
            sm: '/images/koppelen-sm.jpg',  // Image for small screens
            default: '/images/koppelen.png'  // Default image
        }
    };

    // Function to get the appropriate image based on screen size
    function getImageForSize(imageKey) {
        const isSmallScreen = window.innerWidth < 640; // Tailwind's 'sm' screen size is 640px
        return isSmallScreen ? images[imageKey].sm : images[imageKey].default;
    }

    // Add event listeners to the buttons
    button1.addEventListener('click', () => {
        imageContainer.src = getImageForSize('button1'); // Change the source of the image
    });

    button2.addEventListener('click', () => {
        imageContainer.src = getImageForSize('button2'); // Change the source of the image
    });

    button3.addEventListener('click', () => {
        imageContainer.src = getImageForSize('button3'); // Change the source of the image
    });

    // Optionally, update the image on page load based on screen size
    window.addEventListener('load', () => {
        imageContainer.src = getImageForSize('button1'); // Set default image based on screen size
    });

    // Optionally, add event listener for resizing to handle screen size changes
    window.addEventListener('resize', () => {
        imageContainer.src = getImageForSize('button1'); // Update the image on resize
    });
</script>
