<!DOCTYPE html>
<html>

<head>
    <title>Live Location</title>
    <script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyATC_r7Y-U6Th1RQLHWJv2JcufJb-x2VJ0"></script>
    <style>
        #map {
            height: 100vh;
            width: 100%;
        }
    </style>
</head>

<body>
    <div id="map"></div>

    <script>
        let map, marker;
        const token = "{{ $token }}";

        function initMap(lat = 30.0444, lng = 31.2357) {
            map = new google.maps.Map(document.getElementById('map'), {
                center: {
                    lat,
                    lng
                },
                zoom: 14
            });
            marker = new google.maps.Marker({
                position: {
                    lat,
                    lng
                },
                map: map
            });
        }

        async function fetchLocation() {
            const res = await fetch(`/api/live-location/data/${token}`);
            if (!res.ok) return;
            let data = await res.json();
            console.log(data);
            if (data.lat && data.lng) {
                const pos = {
                    lat: parseFloat(data.lat),
                    lng: parseFloat(data.lng)
                };
                marker.setPosition(pos);
                map.setCenter(pos);
            }
        }

        initMap();
        setInterval(fetchLocation, 5000); // كل 10 ثواني
    </script>
</body>

</html>
