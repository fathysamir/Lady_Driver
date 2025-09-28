<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Open Live Location</title>
<style>
  body{font-family:system-ui,-apple-system,Segoe UI,Roboto,Arial;display:flex;align-items:center;justify-content:center;height:100vh;margin:0}
  .box{max-width:420px;text-align:center}
  button{padding:10px 14px;border-radius:8px;border:none;background:#0b74ff;color:#fff;cursor:pointer}
</style>
</head>
<body>
<div class="box">
    <h2>Opening app…</h2>
    <p id="status">Attempting to open app. If nothing happens, tap the button below.</p>
    <div style="margin-top:16px">
        <button id="openBrowser">Open in browser</button>
    </div>
</div>

<script>
(function(){
    const token = "{{ $link->token }}";
    const fallback = "{{ $fallbackUrl }}";

    // configure your app details (set these in .env and use config())
    const appScheme = "{{ config('app.deep_link_scheme', 'myapp') }}"; // e.g. myapp
    const androidPackage = "{{ config('app.android_package', 'com.example.myapp') }}"; // Android package
    const iosTeamAppId = "{{ config('app.ios_team_appid', '') }}"; // optional

    // Build deep link urls
    const schemeURL = `${appScheme}://live?token=${token}`;
    const universalURL = window.location.href; // this is your https://example.com/l/{token}
    // Android intent URL (Chrome supports)
    const androidIntent = `intent://live-location/${token}#Intent;scheme=${appScheme};package=${androidPackage};S.browser_fallback_url=${encodeURIComponent(fallback)};end`;

    // detect platform
    const ua = navigator.userAgent || navigator.vendor || window.opera;
    const isAndroid = /Android/i.test(ua);
    const isIOS = /iPhone|iPad|iPod/i.test(ua);

    // use visibility API to detect if app opened
    let didHide = false;
    document.addEventListener('visibilitychange', () => {
        if (document.visibilityState === 'hidden') {
            didHide = true;
        }
    });

    function fallbackToWeb() {
        document.getElementById('status').textContent = "Redirecting to browser…";
        setTimeout(() => { window.location.href = 'https://api.lady-driver.com/live/'+token; }, 200);
    }

    function tryOpen() {
        // If Universal/App Links configured correctly, the OS will open the app BEFORE loading this page.
        // If we are here, app probably not installed or universal links not configured -> try other methods:

        if (isAndroid) {
            // Try intent first (works in Chrome)
            window.location = androidIntent;
            // fallback after short delay if not opened
            setTimeout(() => {
                if (!didHide) fallbackToWeb();
            }, 1200);
            return;
        }

        if (isIOS) {
            // Try custom scheme
            window.location = schemeURL;
            setTimeout(() => {
                if (!didHide) fallbackToWeb();
            }, 1200);
            return;
        }

        // Desktop / unknown
        fallbackToWeb();
    }

    // Open on load
    tryOpen();

    // open button
    document.getElementById('openBrowser').addEventListener('click', function(){
        window.location.href = fallback;
        window.location.href = 'https://api.lady-driver.com/live/'+token;
    });

})();
</script>
</body>
</html>
