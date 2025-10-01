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
        let map, marker, directionsService, directionsRenderer;

        const token = "{{ $token }}";

        function initMap(lat = 30.0444, lng = 31.2357) {
            map = new google.maps.Map(document.getElementById("map"), {
                center: {
                    lat,
                    lng
                },
                zoom: 13,
            });

            marker = new google.maps.Marker({
                position: {
                    lat,
                    lng
                },
                map: map
            });

            directionsService = new google.maps.DirectionsService();
            directionsRenderer = new google.maps.DirectionsRenderer({
                suppressMarkers: true,
                polylineOptions: {
                    strokeColor: "#0000FF",
                    strokeOpacity: 0.8,
                    strokeWeight: 6
                }
            });
            directionsRenderer.setMap(map);
        }

        async function fetchLocation() {
            const res = await fetch(`/api/live-location/data/${token}`);
            if (!res.ok) return;
            const data = await res.json();

            if (data.lat && data.lng) {
                const pos = {
                    lat: parseFloat(data.lat),
                    lng: parseFloat(data.lng)
                };
                marker.setPosition(pos);
                map.setCenter(pos);
            }

            if (data.trip && data.trip.final_destination.length > 0) {
                drawRoute(data);
            }
        }

        function drawRoute(data) {
            const start = {
                lat: parseFloat(data.trip.start_lat),
                lng: parseFloat(data.trip.start_lng)
            };

            const destinations = data.trip.final_destination.map(d => ({
                location: {
                    lat: parseFloat(d.lat),
                    lng: parseFloat(d.lng)
                },
                stopover: true
            }));

            directionsService.route({
                origin: start,
                destination: destinations[destinations.length - 1].location,
                waypoints: destinations.slice(0, -1), // كل الوجهات ما عدا الأخيرة كـ waypoints
                travelMode: google.maps.TravelMode.DRIVING,
            }, (result, status) => {
                if (status === google.maps.DirectionsStatus.OK) {
                    directionsRenderer.setDirections(result);

                    // markers للـ destinations
                    destinations.forEach((wp, i) => {
                        new google.maps.Marker({
                            position: wp.location,
                            map,
                            label: `${i+1}`
                        });
                    });
                } else {
                    console.error("Directions request failed due to " + status);
                }
            });
        }

        initMap();
        fetchLocation();
        setInterval(fetchLocation, 5000);
    </script>


</body>

</html>
