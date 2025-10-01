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
        let finishedPolyline, nextPolyline, destinationMarkers = [];
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

        function clearPolylines() {
            if (finishedPolyline) finishedPolyline.setMap(null);
            if (nextPolyline) nextPolyline.setMap(null);
            destinationMarkers.forEach(m => m.setMap(null));
            destinationMarkers = [];
        }

        function getDistance(a, b) {
            const R = 6371e3; // meters
            const φ1 = a.lat * Math.PI / 180;
            const φ2 = b.lat * Math.PI / 180;
            const Δφ = (b.lat - a.lat) * Math.PI / 180;
            const Δλ = (b.lng - a.lng) * Math.PI / 180;

            const x = Math.sin(Δφ / 2) * Math.sin(Δφ / 2) +
                Math.cos(φ1) * Math.cos(φ2) *
                Math.sin(Δλ / 2) * Math.sin(Δλ / 2);
            const d = 2 * Math.atan2(Math.sqrt(x), Math.sqrt(1 - x));
            return R * d;
        }

        function drawTrip(data) {
            if (!data.trip || !data.trip.final_destination) return;

            const start = {
                lat: parseFloat(data.trip.start_lat),
                lng: parseFloat(data.trip.start_lng)
            };

            const destinations = data.trip.final_destination.map(d => ({
                lat: parseFloat(d.lat),
                lng: parseFloat(d.lng)
            }));

            if (destinations.length === 0) return;

            const currentPos = {
                lat: parseFloat(data.lat),
                lng: parseFloat(data.lng)
            };

            clearPolylines();

            // كامل المسار (start → dest1 → dest2 → ...)
            const fullPath = [start, ...destinations];

            // نحدد أقرب نقطة لسه ماوصلهاش
            let nearestIndex = -1;
            let nearestDist = Infinity;
            destinations.forEach((p, i) => {
                let dist = getDistance(currentPos, p);
                if (dist < nearestDist) {
                    nearestDist = dist;
                    nearestIndex = i;
                }
            });

            // finished path = من start → لحد النقطة اللي قبل الحالية
            const finishedPath = fullPath.slice(0, nearestIndex + 1);
            finishedPath.push(currentPos); // لحد موقعه

            // next path = من موقعه → باقي النقاط
            const nextPath = [currentPos, ...fullPath.slice(nearestIndex + 1)];

            // رسم الرمادي
            finishedPolyline = new google.maps.Polyline({
                path: finishedPath,
                geodesic: true,
                strokeColor: "#808080",
                strokeOpacity: 1.0,
                strokeWeight: 5,
                map
            });

            // رسم الأزرق
            nextPolyline = new google.maps.Polyline({
                path: nextPath,
                geodesic: true,
                strokeColor: "#0000FF",
                strokeOpacity: 1.0,
                strokeWeight: 5,
                map
            });

            // pin على كل destination
            destinations.forEach((pos, i) => {
                const m = new google.maps.Marker({
                    position: pos,
                    map: map,
                    label: `${i+1}`,
                    icon: {
                        path: google.maps.SymbolPath.CIRCLE,
                        scale: 6,
                        fillColor: "#0000FF",
                        fillOpacity: 1,
                        strokeColor: "#fff",
                        strokeWeight: 2
                    }
                });
                destinationMarkers.push(m);
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

            if (data.trip) {
                drawTrip(data);
            }
        }

        initMap();
        fetchLocation();
        setInterval(fetchLocation, 5000);
    </script>


</body>

</html>
