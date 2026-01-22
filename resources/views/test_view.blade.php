<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Upload File</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f4f6f9;
            padding: 40px;
        }

        .upload-card {
            background: #fff;
            max-width: 500px;
            margin: auto;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }

        h3 {
            text-align: center;
            margin-bottom: 20px;
        }

        input[type="file"] {
            width: 100%;
            padding: 10px;
            border: 2px dashed #ccc;
            border-radius: 6px;
            cursor: pointer;
        }

        button {
            width: 100%;
            padding: 12px;
            margin-top: 15px;
            background: #007bff;
            border: none;
            color: #fff;
            font-size: 16px;
            border-radius: 6px;
            cursor: pointer;
        }

        button:disabled {
            background: #999;
        }

        .progress-container {
            width: 100%;
            background: #eee;
            border-radius: 6px;
            margin-top: 15px;
            overflow: hidden;
            display: none;
        }

        .progress-bar {
            height: 20px;
            width: 0%;
            background: linear-gradient(90deg, #28a745, #5cd65c);
            text-align: center;
            color: #fff;
            font-size: 12px;
            line-height: 20px;
            transition: width 0.2s;
        }

        .status-text {
            text-align: center;
            margin-top: 10px;
            font-size: 14px;
        }
    </style>
</head>
<body>

<div class="upload-card">
    <h3>Upload File</h3>

    <form id="uploadForm">
        @csrf

        <input type="file" name="file" id="fileInput" required>

        <button type="submit" id="uploadBtn">Upload</button>

        <div class="progress-container" id="progressContainer">
            <div class="progress-bar" id="progressBar">0%</div>
        </div>

        <div class="status-text" id="statusText"></div>
    </form>
</div>

<script>
document.getElementById('uploadForm').addEventListener('submit', function(e) {
    e.preventDefault();

    const fileInput = document.getElementById('fileInput');
    const progressBar = document.getElementById('progressBar');
    const progressContainer = document.getElementById('progressContainer');
    const statusText = document.getElementById('statusText');
    const uploadBtn = document.getElementById('uploadBtn');

    if (!fileInput.files.length) {
        alert("Please select a file");
        return;
    }

    const formData = new FormData();
    formData.append('file', fileInput.files[0]);

    progressContainer.style.display = 'block';
    progressBar.style.width = '0%';
    progressBar.innerText = '0%';
    statusText.innerText = 'Uploading...';
    uploadBtn.disabled = true;

    const xhr = new XMLHttpRequest();
    xhr.open('POST', '{{ url("/test-view") }}', true);
    xhr.setRequestHeader('X-CSRF-TOKEN', document.querySelector('meta[name="csrf-token"]').content);

    xhr.upload.onprogress = function(e) {
        if (e.lengthComputable) {
            const percent = Math.round((e.loaded / e.total) * 100);
            progressBar.style.width = percent + '%';
            progressBar.innerText = percent + '%';
        }
    };

    xhr.onload = function() {
        uploadBtn.disabled = false;

        if (xhr.status === 200) {
            progressBar.style.width = '100%';
            progressBar.innerText = '100%';
            statusText.innerText = 'Upload completed successfully ✅';
        } else {
            statusText.innerText = 'Upload failed ❌';
        }
    };

    xhr.onerror = function() {
        uploadBtn.disabled = false;
        statusText.innerText = 'Upload error ❌';
    };

    xhr.send(formData);
});
</script>

</body>
</html>
