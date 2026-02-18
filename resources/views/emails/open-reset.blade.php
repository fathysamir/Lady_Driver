<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Opening App...</title>

<script>
window.onload = function() {

  var appLink = "{{ $appLink }}";
  var webFallback = "{{ $webLink }}";


  window.location.href = appLink;

  // fallback after 2 seconds if app not installed
  setTimeout(function() {
    window.location.href = webFallback;
  }, 2000);
};
</script>

</head>
<body>

<p>Opening Lady Drive App...</p>

<p>
If nothing happens,
<a href="{{ $appLink }}">click here</a>
</p>

</body>
</html>
