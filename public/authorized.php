<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Authorized</title>
    <style>
        body {
            font-family: Helvetica, Arial, sans-serif;
            position: fixed;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            display: grid;
            grid-template-columns: 1fr auto 1fr;
            grid-template-rows: 1fr auto 1.618fr;
            background: darkgray;
        }

        #message {
            grid-column-start: 2;
            grid-row-start: 2;
            gap: 0.5rem;
            padding: 1rem 2rem 2rem 2rem;
            border: solid 1px gray;
            border-radius: 1rem;
            background: white;
            box-shadow: 0 0 1rem 1rem hsla(0, 0%, 50%, 0.5);
        }
    </style>
</head>
<body>

<div id="message">
    <p>Thank you for authorizing this app.</p>
    <p>You can close this page.</p>
</div>

</body>
</html>
