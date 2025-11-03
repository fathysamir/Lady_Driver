<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Opening App...</title>
  <script>
    document.addEventListener("DOMContentLoaded", function() {
      window.location = "{{ $appLink }}";
      setTimeout(function() {
        window.location = "{{ $webLink }}";
      }, 2000);
    });
  </script>
</head>
<body style="font-family:sans-serif;text-align:center;margin-top:50px;">
  <p>Trying to open the app...</p>
  <p>If nothing happens, <a href="{{ $webLink }}">click here</a>.</p>
</body>
</html>
