<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Close Page</title>
</head>
<body>
    <script>
        // Close the window
        window.close();

        // If the window doesn't close, provide a fallback message
        if (!window.closed) {
            document.body.innerHTML = '<p>The payment page could not be closed automatically. Please close this page manually.</p>';
        }
    </script>
</body>
</html>