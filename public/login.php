<?php

$client_id = $_GET['client_id'];

// TODO look up scopes, display details
// TODO real login before authorization

?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login</title>
    <style>
        body {
            position: fixed;
            left: 0;
            top: 0;
            width: 95%; /* stupid scroll bar */
            height: 100%;
            display: grid;
            grid-template-columns: 1fr auto 1fr;
            grid-template-rows: 1fr auto 1.618fr;
            font-family: Helvetica, Arial, sans-serif;
            background: darkgray;
        }

        form {
            display: grid;
            grid-template-columns: 1fr 1fr;
            grid-column-start: 2;
            grid-row-start: 2;
            gap: 0.5rem;
            padding: 1rem 2rem 2rem 2rem;
            border: solid 1px gray;
            border-radius: 1rem;
            background: white;
            box-shadow: 0rem 0rem 1rem 1rem hsla(0, 0%, 50%, 0.5);
        }

        .span {
            grid-column: span 2;
        }

        h1 {
            margin: 0;
            padding: 0;
        }

        label {
            padding: 0.25rem 0;
        }

        input {
            height: 1rem;
            padding: 0.25rem 0.51rem;
            border: solid 0.25px darkgray;
            border-radius: 0.25rem;
        }

        input {
            grid-column: span 2;
        }

        input:focus, button:focus {
            outline: none;
            box-shadow: 0rem 0rem 0.25rem 0.25rem hsla(210, 100%, 75%, 0.5);
        }

        button {
            background: hsla(210, 100%, 45%, 1);
            color: white;
            font-weight: bolder;
            padding: 0.5rem 1rem;
            border-radius: 0.75rem;
            border: solid 0.25px hsla(210, 100%, 25%, 1);
            margin-top: 1rem;
        }

        button:active {
            outline: none;
            background: hsla(210, 100%, 75%, 1);
        }

        button:focus {
            background: hsla(210, 100%, 35%, 1);
        }
    </style>
</head>
<body>


<form method="post" action="api/v1/oauth2/authorize">
    <?php

    foreach ($_GET as $key => $value) {
        echo <<<EOT
    <input type="hidden" name="{$key}" value="{$value}">
EOT;
    }

    ?>
    <div class="span">
        <h1>Authorize <?= $client_id ?></h1>
        <p>Do you authorize <?php echo htmlentities($client_id); ?>?</p>
    </div>
    <input type="text" name="username" placeholder="Username">
    <input type="password" name="password" placeholder="Password">
    <button type="submit" name="authorized" value="yes">Yes</button>
    <button type="submit" name="authorized" value="no">No</button>
</form>

</body>
</html>
