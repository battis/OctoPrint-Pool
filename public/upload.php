<?php

function anyOf(array $keys, $default, callable $filter = null): string
{
    foreach ($keys as $key) {
        if (isset($_GET[$key])) {
            if ($filter) {
                return $filter($_GET[$key]);
            }
            return $_GET[$key];
        }
    }
    return $default;
}

$user_id = null;
$tags = [];
foreach (explode('/', $_SERVER['PATH_INFO']) as $token) {
    if (!empty($user_id)) {
        array_push($tags, $token);
    } else {
        if (!empty($token) && empty($user_id)) {
            $user_id = $token;
        }
    }
}
$uploadEndpoint = dirname($_SERVER['SCRIPT_NAME']) . "/api/v1/anonymous/queue/{$user_id}";

$buttonText = anyOf(['button', 'text', 'b', 't'], 'Upload Files');
$comment = anyOf(['comment', 'c'], true, function ($val) {
    return filter_var($val, FILTER_VALIDATE_BOOLEAN);
});

?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Upload</title>
    <style>
        body {
            position: fixed;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            display: grid;
            font-family: Helvetica, Arial, sans-serif;
            background: hsla(0, 0%, 100%, 0.0);
            margin: 0;
            padding: 0;
        }

        #dropzone {
            border: 5px dashed hsla(0, 0%, 50%, 0.25);
            border-radius: 1rem;
            padding: 2rem;
            background: hsla(0, 0%, 100%, 0.75);
            display: grid;
            grid-template-columns: 1fr auto 1fr;
            grid-template-rows: 1fr auto auto 1.618fr;
        }

        #dropzone.highlight {
            border-color: hsla(0, 0%, 15%, 0.25);
            background: hsla(0, 0%, 25%, 0.5);
            color: white;
        }

        .file-input {
            grid-column-start: 2;
            grid-row-start: 2;
            position: relative;
            overflow: hidden;
            display: inline-block;
        }

        .file-input button {
            border: solid 3px hsla(0, 0%, 50%, 0.5);
            border-radius: 1rem;
            background: hsla(0, 0%, 100%, 0.5);
            padding: 1rem 3rem;
            font-size: xx-large;
            font-weight: bolder;
        }

        .file-input input[type="file"] {
            position: absolute;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            opacity: 0;
        }

        #uploaded {
            grid-column-start: 2;
            grid-row-start: 3;
        }

        .collection {
            border: solid 1px hsla(0, 0%, 60%, 0.5);
            border-radius: 0.5rem;
            margin: 0.5rem;
            padding: 0.25rem 0.5rem;
        }

        .collection:hover {
            border: solid 1px hsla(0, 0%, 40%, 0.5);
            background: hsla(0, 0%, 50%, 0.5);
        }

        .collection ul {
            margin: 0;
            padding: 0;
        }

        .collection li {
            list-style: none;
        }

        .collection li::before {
            content: "\002714  ";
            color: green;
        }

        .comment {
            font-style: italic;
        }

        .filename {
            display: inline-block;
        }

        .filename::before {
            content: "\01f4c4 ";
            font-size: x-large;
        }
    </style>
</head>
<body>

<div id="dropzone">
    <div class="file-input">
        <form method="post" action="<?= $uploadEndpoint ?>">
            <button><?= $buttonText ?></button>
            <input type="file" id="file" name="file" multiple onchange="handleFiles(this.files)">
            <button type="submit" id="submit">Upload</button>
        </form>
    </div>
    <div id="uploaded"></div>
</div>

<script>
    const dropzone = document.getElementById('dropzone');
    const file = document.getElementById('file');
    const uploaded = document.getElementById('uploaded');
    const askForComment = <?= $comment ? 'true' : 'false' ?>;
    document.getElementById('submit').style.display = 'none';

    const preventDefaults = event => {
        event.preventDefault();
        event.stopPropagation();
    }

    const highlight = () => {
        dropzone.classList.add('highlight');
    }
    const unhighlight = () => {
        dropzone.classList.remove('highlight');
    }

    const handleDrop = e => {
        handleFiles(e.dataTransfer.files);
    }

    const handleFiles = files => {
        let comment = "";
        if (askForComment) {
            comment = window.prompt(`What notes or instructions do you need to include with ${
                Array.from(files)
                    .map(file => file.name).join(', ')
                    .replace(/, ([^,]+)$/, ' and $1')
            }?`);
        }
        const collection = document.createElement('div');
        collection.classList.add('collection');
        collection.innerHTML = `${comment.length > 0 ? `<span class="comment">${comment}</span>` : ''}<ul></ul>`
        uploaded.appendChild(collection);
        for (const file of files) {
            uploadFile(file, comment, collection.querySelector('ul'));
        }
        file.value = null;
    }

    const uploadFile = async (file, comment, collection) => {
        const formData = new FormData();
        formData.append('file', file);
        formData.append('tags[]', <?= json_encode($tags) ?>)
        formData.append('comment', comment);
        const fileResponses = await (await fetch('<?= $uploadEndpoint ?>', {
            method: 'POST',
            body: formData
        })).json();
        fileResponses.forEach(fileResponse => {
            const fileItem = document.createElement('li');
            fileItem.innerHTML = `<div class="filename">${fileResponse.filename} uploaded.</div>`;
            collection.appendChild(fileItem);
        })
    }

    ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(event => {
        dropzone.addEventListener(event, preventDefaults, false);
    });
    ['dragenter', 'dragover'].forEach(event => {
        dropzone.addEventListener(event, highlight, false);
    });
    ['dragleave', 'drop'].forEach(event => {
        dropzone.addEventListener(event, unhighlight, false);
    });
    dropzone.addEventListener('drop', handleDrop, false);
</script>

</body>
</html>
