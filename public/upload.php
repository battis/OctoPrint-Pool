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

function jsBool($val)
{
    return $val ? 'true' : 'false';
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
        :root {
            --dropzone-padding: 2rem;
            --dropzone-border-width: 5px;
        }

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
            border: var(--dropzone-border-width) dashed hsla(0, 0%, 50%, 0.25);
            border-radius: 1rem;
            padding: var(--dropzone-padding);
            background: hsla(0, 0%, 100%, 0.75);
        }

        #dropzone.highlight {
            border-color: hsla(0, 0%, 15%, 0.25);
            background: hsla(0, 0%, 25%, 0.5);
            color: white;
        }

        #dropzone-contents {
            display: flex;
            flex-direction: row;
            align-content: center;
            justify-content: center;
            align-items: center;
        }

        .hidden {
            display: none;
        }

        .golden-center {
            display: grid;
            grid-template-rows: 1fr auto 1.618fr;
            grid-template-columns: 1fr auto 1fr;
        }

        .golden-center .centered {
            grid-row-start: 2;
            grid-column-start: 2;
        }

        .file-input {
            position: relative;
            overflow: hidden;
            display: inline-block;
        }

        .file-input button {
            border: solid 3px hsla(0, 0%, 50%, 0.5);
            border-radius: 1rem;
            background: hsla(0, 0%, 100%, 0.5);
            padding: 1rem 3rem;
            font-size: x-large;
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
            overflow-x: hidden;
            overflow-y: auto;
        }

        .collection {
            border: solid 1px hsla(0, 0%, 60%, 0.5);
            border-radius: 0.5rem;
            margin: 0.5rem;
            padding: 0.25rem 0.5rem;
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

<div id="dropzone" class="golden-center">
    <div class="centered" id="dropzone-contents">
        <div class="file-input">
            <form method="post" action="<?= $uploadEndpoint ?>">
                <button><?= $buttonText ?></button>
                <input type="file" id="file" name="file" multiple onchange="handleFiles(this.files)">
                <button type="submit" id="submit">Upload</button>
            </form>
        </div>
        <div id="uploaded" class="hidden"></div>
    </div>
</div>

<script>
    const dropzone = document.getElementById('dropzone');
    const file = document.getElementById('file');
    const uploaded = document.getElementById('uploaded');

    // FIXME need to double-check actual behavior  as a form...
    document.getElementById('submit').style.display = 'none';

    const resizeToFit = () => {
        // release size to fit new window
        dropzone.style.width = 'calc(100% - 2 * (var(--dropzone-padding) + var(--dropzone-border-width)))';
        dropzone.style.height = 'calc(100% - 2 * (var(--dropzone-padding) + var(--dropzone-border-width)))';
        uploaded.style.maxHeight = '0';

        // clamp size to window to prevent #uploaded from expanding #dropzone
        dropzone.style.width = `calc(${dropzone.clientWidth}px - 2 * var(--dropzone-padding))`;
        dropzone.style.height = `calc(${dropzone.clientHeight}px - 2 * var(--dropzone-padding))`
        uploaded.style.maxHeight = `calc(${dropzone.clientHeight}px - 2 * var(--dropzone-padding))`;
    }

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
        if (<?= jsBool($comment) ?>) {
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
        uploaded.classList.remove('hidden');
        collection.scrollIntoView();
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
            fileItem.scrollIntoView();
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
    window.onresize = resizeToFit;
    resizeToFit();
</script>

</body>
</html>
