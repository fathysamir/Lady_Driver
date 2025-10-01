<!DOCTYPE html>
<html>

<head>
    <title>Live Location</title>
    <script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyATC_r7Y-U6Th1RQLHWJv2JcufJb-x2VJ0"></script>
    <script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyATC_r7Y-U6Th1RQLHWJv2JcufJb-x2VJ0&libraries=geometry">
    </script>

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
        let map, marker, directionsService;
        let finishedPolyline, nextPolyline;

        const token = "{{ $token }}";

        function initMap(lat = 30.0444, lng = 31.2357) {
            map = new google.maps.Map(document.getElementById("map"), {
                center: {
                    lat,
                    lng
                },
                zoom: 17,
            });

            marker = new google.maps.Marker({
                position: {
                    lat,
                    lng
                },
                map: map
            });

            directionsService = new google.maps.DirectionsService();
        }
        let x = false;
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
                if (x == false) {
                    map.setCenter(pos);
                    x = true;
                }

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

            const waypoints = data.trip.final_destination.map(d => ({
                location: {
                    lat: parseFloat(d.lat),
                    lng: parseFloat(d.lng)
                },
                stopover: true
            }));

            directionsService.route({
                origin: start,
                destination: waypoints[waypoints.length - 1].location,
                waypoints: waypoints.slice(0, -1),
                travelMode: google.maps.TravelMode.DRIVING,
            }, (result, status) => {
                if (status === google.maps.DirectionsStatus.OK) {
                    const route = result.routes[0].overview_path;

                    if (finishedPolyline) finishedPolyline.setMap(null);
                    if (nextPolyline) nextPolyline.setMap(null);

                    const currentPos = {
                        lat: parseFloat(data.lat),
                        lng: parseFloat(data.lng)
                    };

                    let nearestIndex = 0;
                    let nearestDist = Infinity;
                    route.forEach((p, i) => {
                        const d = google.maps.geometry.spherical.computeDistanceBetween(
                            new google.maps.LatLng(currentPos.lat, currentPos.lng),
                            p
                        );
                        if (d < nearestDist) {
                            nearestDist = d;
                            nearestIndex = i;
                        }
                    });

                    finishedPolyline = new google.maps.Polyline({
                        path: route.slice(0, nearestIndex + 1),
                        strokeColor: "#808080",
                        strokeOpacity: 1.0,
                        strokeWeight: 6,
                        map
                    });

                    nextPolyline = new google.maps.Polyline({
                        path: route.slice(nearestIndex),
                        strokeColor: "#0000FF",
                        strokeOpacity: 1.0,
                        strokeWeight: 6,
                        map
                    });

                    // ✅ حط ماركر صغير أخضر عند نقطة البداية
                    new google.maps.Marker({
                        position: start,
                        map: map,
                        icon: {
                            path: google.maps.SymbolPath.CIRCLE,
                            scale: 6,
                            fillColor: "gold",
                            fillOpacity: 1,
                            strokeColor: "white",
                            strokeWeight: 2
                        },
                        title: "Start"
                    });

                    // ✅ وحط ماركر صغير أخضر عند كل Destination
                    data.trip.final_destination.forEach((d, i) => {
                        new google.maps.Marker({
                            position: {
                                lat: parseFloat(d.lat),
                                lng: parseFloat(d.lng)
                            },
                            map: map,
                            icon: {
                                path: google.maps.SymbolPath.CIRCLE,
                                scale: 6,
                                fillColor: "gold",
                                fillOpacity: 1,
                                strokeColor: "white",
                                strokeWeight: 2
                            },
                            title: "Destination " + (i + 1)
                        });
                    });
                } else {
                    console.error("Directions request failed: " + status);
                }
            });
        }


        initMap();
        fetchLocation();
        setInterval(fetchLocation, 5000);
    </script>


</body>

</html>
