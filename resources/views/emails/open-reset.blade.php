<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Opening App...</title>
  <script>
    document.addEventListener("DOMContentLoaded", function() {
      window.location = "{{ $appLink }}";
    });
  </script>
</head>
<body>
  <p>If you are not redirected automatically, <a href="{{ $appLink }}">click here to open the app</a>.</p>