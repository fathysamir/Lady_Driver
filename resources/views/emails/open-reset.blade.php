<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Opening App...</title>

<script>
window.onload = function() {

  var appLink = "{{ $appLink }}";
  var webFallback = "{{ $webLink }}";

  // Try to open the app
  window.location.href = appLink;

  // If user leaves the page (app opened), cancel the fallback
  var fallbackTimer = setTimeout(function() {
    window.location.href = webFallback;
  }, 2500);

  // Cancel fallback if page becomes hidden (app launched)
  document.addEventListener("visibilitychange", function() {
    if (document.hidden) {
      clearTimeout(fallbackTimer);
    }
  });

  window.addEventListener("blur", function() {
    clearTimeout(fallbackTimer);
  });

};
</script>

</head>
<body style="font-family: sans-serif; text-align: center; padding-top: 60px;">

  <p>Opening Lady Drive App...</p>

  <p>
    If nothing happens,
    <a href="{{ $appLink }}">tap here to open the app</a>
    or
    <a href="{{ $webLink }}">continue in browser</a>
  </p>

</body>
</html>