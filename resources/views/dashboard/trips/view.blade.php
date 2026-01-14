@extends('dashboard.layout.app')
@section('title', 'Dashboard - view trip')
@section('content')
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        .star-rating {
            color: #FFf;
            /* Default star color */
        }

        .star {
            font-size: 24px;
            /* Adjust the size of the stars */
        }

        .filled {
            color: gold;
            /* Filled star color */
        }

        .car-link:hover {
            color: blue;
            /* Text color on hover */
        }
    </style>
    <div class="content-wrapper">
        <div class="container-fluid">

            <div class="row mt-3">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-body">
                            @php
                                $type['car'] = 'Standard Trip';
                                $type['comfort_car'] = 'Comfort Trip';
                                $type['scooter'] = 'Scooter Trip';

                            @endphp
                            <div class="card-title">Trip Code : {{ $trip->code }} ({{ $type[$trip->type] }})  @if ($trip->type === 'car' && $trip->air_conditioned=='1')<i class="fa fa-snowflake" style="color: rgb(0, 213, 255);"></i> @endif</div>
                            <hr>

                            <div id="map" style="height: 800px; margin: 20px 0px 20px 0px;"></div>
                            <div class="form-group" style="display: flex;margin-bottom: 0rem;">
                                <div style="width:50%">
                                    <div class="form-group"style="margin-bottom: 0.75rem;">
                                        <label>Client : <a
                                                href="{{ url('/admin-dashboard/user/edit/' . $trip->user->id) }}">
                                                <span class="user-profile"><img
                                                        @if (getFirstMediaUrl($trip->user, $trip->user->avatarCollection) != null) src="{{ getFirstMediaUrl($trip->user, $trip->user->avatarCollection) }}" @else src="{{ asset('dashboard/user_avatar.png') }}" @endif
                                                        class="img-circle" alt="user avatar"
                                                        style="width: 22px;height: 22px;"></span>
                                                {{ ucwords($trip->user->name) }}</a> @if($trip->student_trip=='1') (<span style="color:rgb(255, 215, 16)">Student</span>) @endif</label>
                                    </div>
                                    <div class="form-group"style="margin-bottom: 0.75rem;">
                                        <label>Driver :
                                            @if ($trip->type === 'scooter' && $trip->scooter)
                                                <a
                                                    href="{{ url('/admin-dashboard/user/edit/' . $trip->scooter->owner->id) }}">
                                                    <span class="user-profile"><img
                                                            @if (getFirstMediaUrl($trip->scooter->owner, $trip->scooter->owner->avatarCollection) != null) src="{{ getFirstMediaUrl($trip->scooter->owner, $trip->scooter->owner->avatarCollection) }}"
                                                        @else
                                                            src="{{ asset('dashboard/user_avatar.png') }}" @endif
                                                            class="img-circle" alt="user avatar"
                                                            style="width: 22px;height: 22px;"></span>
                                                    {{ ucwords($trip->scooter->owner->name) }}
                                                </a>
                                            @elseif ($trip->car)
                                                <a href="{{ url('/admin-dashboard/user/edit/' . $trip->car->owner->id) }}">
                                                    <span class="user-profile"><img
                                                            @if (getFirstMediaUrl($trip->car->owner, $trip->car->owner->avatarCollection) != null) src="{{ getFirstMediaUrl($trip->car->owner, $trip->car->owner->avatarCollection) }}"
                                                        @else
                                                            src="{{ asset('dashboard/user_avatar.png') }}" @endif
                                                            class="img-circle" alt="user avatar"
                                                            style="width: 22px;height: 22px;"></span>
                                                    {{ ucwords($trip->car->owner->name) }}
                                                </a>
                                            @else
                                                N/A
                                            @endif
                                        </label>
                                    </div>
                                    <div class="form-group"style="margin-bottom: 0.75rem;">
                                        <label>Created at : <span style="color: #95c408">{{ date('d M.Y h:i a', strtotime($trip->created_at)) }}</span>
                                            @if ($trip->scheduled == '1')
                                                <span class="badge badge-secondary"
                                                    style="background-color:rgb(28, 161, 34);">Scheduled</span>
                                            @endif
                                        </label>
                                    </div>
                                    <div class="form-group"style="margin-bottom: 0.75rem;">
                                        <label>Driver Arrived at : <span style="color: #95c408">{{ date('d M.Y h:i a', strtotime($trip->driver_arrived)) }}</span>
                                            </label>
                                    </div>
                                    <div class="form-group"style="margin-bottom: 0.75rem;">
                                        <label>Start At : <span style="color: #95c408">{{ date('d M.Y', strtotime($trip->start_date)) }}
                                            {{ date('h:i a', strtotime($trip->start_time)) }}</span></label>
                                    </div>
                                    <div class="form-group"style="margin-bottom: 0rem;">
                                        <label>End At : <span style="color: #95c408">{{ date('d M.Y', strtotime($trip->end_date)) }}
                                            {{ date('h:i a', strtotime($trip->end_time)) }}</span></label>
                                    </div>
                                </div>
                                <div style="width:50% ;">
                                    <div class="form-group"style="margin-bottom: 0.75rem;">
                                        <label class="fw-bold text-success">
                                            <i class="fa fa-map-marker-alt"></i>
                                            From :
                                        </label>
                                        <div class="border rounded p-2 bg-light">
                                            {{ $trip->address1 }}
                                        </div>
                                    </div>

                                    <div class="form-group"style="margin-bottom: 0.75rem;">
                                        <label class="fw-bold text-danger">
                                            <i class="fa fa-flag-checkered"></i>
                                            To :
                                        </label>

                                        <div class="border rounded p-2 bg-light">
                                            @foreach ($trip->finalDestination as $i => $dest)
                                                <div class="d-flex align-items-center mb-1">
                                                    <span class="badge bg-danger me-2">{{ $i + 1 }}</span>
                                                    <span>{{ $dest->address }}</span>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                    <div class="form-group"style="margin-bottom: 0rem;">
                                        <label>Trip Status : @if ($trip->status == 'pending')
                                                <span class="badge badge-secondary"
                                                    style="background-color:rgb(143, 118, 9);">Pending</span>
                                            @elseif($trip->status == 'scheduled')
                                                <span class="badge badge-secondary"
                                                    style="background-color:rgb(112, 137, 4);">Scheduled</span>
                                            @elseif($trip->status == 'completed')
                                                <span class="badge badge-secondary"
                                                    style="background-color:rgb(50, 134, 50);">Completed</span>
                                            @elseif($trip->status == 'in_progress')
                                                <span class="badge badge-secondary"
                                                    style="background-color:rgb(52, 40, 223);">In Progress</span>
                                            @else
                                                <span class="badge badge-secondary"
                                                    style="background-color:rgb(255,0,0);">Cancelled</span>
                                            @endif
                                        </label>
                                    </div>

                                </div>
                            </div>

                            <div class="form-group" style="display: flex; align-items: center;">
                                <h4 style="margin-right: 10px;">{{ $trip->type === 'scooter' ? 'Scooter' : 'Car' }}</h4>
                                <hr style="flex: 1; margin: 0;">
                            </div>
                            <div class="form-group" style="text-align: center;">
                                @if ($trip->type === 'scooter' && $trip->scooter)
                                    <div>
                                        <img style="border-radius: 2%;width:60%;"
                                            @if ($trip->scooter->image != null) src="{{ $trip->scooter->image }}"
                                            @else
                                                src="{{ asset('dashboard/scooter_avatar.png') }}" @endif
                                            class="img-circle" alt="scooter image">
                                    </div>
                                    <div style="width: 50%;margin-left:25%">
                                        <a href="{{ url('/admin-dashboard/scooter/edit/' . $trip->scooter->id) }}"
                                            style="text-align:center;">
                                            <h3 style="margin-top:10px;" class="car-link">
                                                {{ $trip->scooter->motorcycleMark->en_name ?? 'N/A' }} -
                                                {{ $trip->scooter->motorcycleMark->ar_name ?? 'N/A' }}
                                            </h3>
                                            <h3 style="margin-top:10px;" class="car-link">
                                                {{ $trip->scooter->motorcycleModel->en_name ?? 'N/A' }} -
                                                {{ $trip->scooter->motorcycleModel->ar_name ?? 'N/A' }}
                                                ({{ $trip->scooter->year }})
                                            </h3>
                                        </a>
                                    </div>
                                @elseif ($trip->car)
                                    <div>
                                        <img style="border-radius: 2%;width:60%;"
                                            @if ($trip->car->image != null) src="{{ $trip->car->image }}"
                                            @else
                                                src="{{ asset('dashboard/car_avatar.png') }}" @endif
                                            class="img-circle" alt="car image">
                                    </div>
                                    <div style="width: 50%;margin-left:25%">
                                        <a
                                            href="{{ url('/admin-dashboard/car/edit/' . $trip->car->id) }}"style="text-align:center;">
                                            <h3 style="margin-top:10px;"class="car-link">{{ $trip->car->mark->en_name }} -
                                                {{ $trip->car->mark->ar_name }}</h3>
                                            <h3 style="margin-top:10px;"class="car-link">{{ $trip->car->model->en_name }} -
                                                {{ $trip->car->model->ar_name }} ({{ $trip->car->year }})</h3>
                                        </a>
                                    </div>
                                @endif
                            </div>

                            <div class="form-group" style="display: flex; align-items: center;">
                                <h4 style="margin-right: 10px;">Payment</h4>
                                <hr style="flex: 1; margin: 0;">
                            </div>
                            <div class="form-group">
                                <label>Payment Status : {{ $trip->payment_status }}</label>
                            </div>
                            <div class="form-group">
                                <label>Distance : {{ $trip->distance }} KM</label>
                            </div>
                            <div class="form-group">
                                <label>Driver ratio : {{ $trip->driver_rate }} LE</label>
                            </div>
                            <div class="form-group">
                                <label>Application ratio : {{ $trip->app_rate }} LE</label>
                            </div>
                            <div class="form-group">
                                <label>Total Price : {{ $trip->total_price }} LE</label>
                            </div>

                            @if ($trip->cancelled_by_id != null)
                                <div class="form-group" style="display: flex; align-items: center;">
                                    <h4 style="margin-right: 10px;">Cancellation</h4>
                                    <hr style="flex: 1; margin: 0;">
                                </div>
                                <div class="form-group">
                                    <label>Cancelled By : @if ($trip->cancelled_by_id == $trip->user_id)
                                            Client
                                        @else
                                            Driver
                                        @endif
                                    </label>
                                </div>
                                <div class="form-group">
                                    <label>Cancelled Reason : <span
                                            style="text-transform: none;">{{ $trip->cancellation_reason ?? 'N/A' }}</span></label>
                                </div>
                            @endif

                            <div class="form-group" style="display: flex; align-items: center;">
                                <h4 style="margin-right: 10px;">Trip evaluation</h4>
                                <hr style="flex: 1; margin: 0;">
                            </div>
                            <div class="form-group" style="display: flex;align-items: center;">
                                <label>Client evaluation : </label>
                                <div class="star-rating" style="margin-bottom: 10px;">
                                    <?php
                                    $clientEvaluation = $trip->client_stare_rate; // Assuming $trip->client_evaluation holds the evaluation score (1 to 5)
                                    
                                    // Loop to generate stars based on the client evaluation score
                                    for ($i = 1; $i <= 5; $i++) {
                                        $starClass = $i <= $clientEvaluation ? 'filled' : 'empty';
                                        echo '<span class="star ' . $starClass . '">&#9733;</span>'; // Unicode character for a star
                                    }
                                    ?>
                                </div>
                            </div>
                            <div class="form-group">
                                <label>Client Comment : <span
                                        style="text-transform: none;">{{ $trip->client_comment ?? 'N/A' }}</span></label>
                            </div>
                            <div class="form-group" style="display: flex;align-items: center;">
                                <label>Driver evaluation : </label>
                                <div class="star-rating" style="margin-bottom: 10px;">
                                    <?php
                                    $driverEvaluation = $trip->driver_stare_rate;
                                    
                                    // Loop to generate stars based on the client evaluation score
                                    for ($i = 1; $i <= 5; $i++) {
                                        $starClass2 = $i <= $driverEvaluation ? 'filled' : 'empty';
                                        echo '<span class="star ' . $starClass2 . '">&#9733;</span>';
                                    }
                                    ?>
                                </div>
                            </div>
                            <div class="form-group">
                                <label>Driver Comment : <span
                                        style="text-transform: none;">{{ $trip->driver_comment ?? 'N/A' }}</span></label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="overlay toggle-menu"></div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyBXsZZzdgnddljlDCbtlOFJumsoktvSOBE&libraries=places">
    </script>

    <script>
        var map, directionsService, segmentRenderer1, segmentRenderer2, marker, previousLocation, distanceLabel;
        var tripDestinations = @json(
            $destinations->map(function ($d) {
                return ['lat' => $d->lat, 'lng' => $d->lng, 'address' => $d->address];
            }));
        var routeRenderers = [];

        function initMap() {
            @php
                $vehicleType = $trip->type === 'scooter' ? 'scooter' : 'car';
                $vehicle = $vehicleType === 'scooter' ? $trip->scooter : $trip->car;
                $vehicleLat = $vehicle ? $vehicle->lat : $trip->start_lat;
                $vehicleLng = $vehicle ? $vehicle->lng : $trip->start_lng;
            @endphp

            var vehicleLocation = {
                lat: {{ $vehicleLat }},
                lng: {{ $vehicleLng }}
            };
            previousLocation = vehicleLocation;

            map = new google.maps.Map(document.getElementById('map'), {
                zoom: 12,
                center: {
                    lat: {{ $trip->start_lat }},
                    lng: {{ $trip->start_lng }}
                }
            });

            // Initialize Directions Service and Renderer
            directionsService = new google.maps.DirectionsService();


            // Calculate and display the route
            calculateRoute();

            var startLocation = new google.maps.LatLng({{ $trip->start_lat }}, {{ $trip->start_lng }});
            placeMarkers(startLocation, tripDestinations);

            if ("{{ $trip->status }}" === "in_progress" || "{{ $trip->status }}" === "pending") {
                var vehicleIcon =
                    "{{ $trip->type === 'scooter' ? asset('dashboard/scooter-icon.png') : asset('dashboard/Travel-car-topview.svg.png') }}";
                marker = new RotatingMarker(vehicleLocation, map, vehicleIcon);

                // Start updating the car's location every 3 seconds
                setInterval(updateVehicleLocation, 3000);
            }

            if ("{{ $trip->status }}" !== "cancelled") {
                addDistanceLabel(map, '{{ $trip->distance }} km');
            }
        }

        function calculateRoute() {

            var startLocation = new google.maps.LatLng({{ $trip->start_lat }}, {{ $trip->start_lng }});
            var vehicleLocation = new google.maps.LatLng(previousLocation.lat, previousLocation.lng);

            var tripStatus = "{{ $trip->status }}";

            // آخر Destination هو النهاية
            var finalDest = tripDestinations[tripDestinations.length - 1];

            var finalLocation = new google.maps.LatLng(finalDest.lat, finalDest.lng);

            var waypoints = tripDestinations.slice(0, -1).map(d => ({
                location: new google.maps.LatLng(d.lat, d.lng),
                stopover: true
            }));

            routeRenderers.forEach(r => r.setMap(null));
            routeRenderers = [];

            if (tripStatus === "in_progress") {
                drawRoute(startLocation, vehicleLocation, '#00FF00', waypoints);
                drawRoute(vehicleLocation, finalLocation, '#0000FF', waypoints);
            } else if (tripStatus === "completed") {
                drawRoute(startLocation, finalLocation, '#00FF00', waypoints);
            } else if (tripStatus === "pending") {
                drawRoute(startLocation, finalLocation, '#0000FF', waypoints);
            } else if (tripStatus === "cancelled") {
                drawRoute(startLocation, finalLocation, '#FF0000', waypoints);
            }
        }


        function placeMarkers(startLocation, destinations) {

            // Start marker
            new google.maps.Marker({
                position: startLocation,
                map: map,
                label: "S",
                icon: "https://maps.google.com/mapfiles/ms/icons/green-dot.png",
                title: "Start Point"
            });

            // Destination markers
            destinations.forEach((dest, index) => {

                new google.maps.Marker({
                    position: new google.maps.LatLng(dest.lat, dest.lng),
                    map: map,
                    label: (index + 1).toString(),
                    icon: "https://maps.google.com/mapfiles/ms/icons/red-dot.png",
                    title: dest.address
                });

            });
        }


        function drawRoute(origin, destination, color, waypoints = []) {

            directionsService.route({
                origin: origin,
                destination: destination,
                waypoints: waypoints,
                optimizeWaypoints: false,
                travelMode: google.maps.TravelMode.DRIVING
            }, function(result, status) {

                if (status === "OK") {

                    var renderer = new google.maps.DirectionsRenderer({
                        map: map,
                        suppressMarkers: true,
                        polylineOptions: {
                            strokeColor: color,
                            strokeWeight: 4
                        }
                    });

                    renderer.setDirections(result);
                    routeRenderers.push(renderer);

                    var path = result.routes[0].overview_path;
                    var midpoint = path[Math.floor(path.length / 2)];
                    if (distanceLabel) distanceLabel.setPosition(midpoint);
                }
            });
        }

        function updateVehicleLocation() {
            var vehicleType = "{{ $trip->type }}";
            var vehicleId = "{{ $trip->type === 'scooter' ? $trip->scooter->id ?? 0 : $trip->car->id ?? 0 }}";
            var endpoint = vehicleType === 'scooter' ?
                '/admin-dashboard/scooter-location/' + vehicleId :
                '/admin-dashboard/car-location/' + vehicleId;

            fetch(endpoint)
                .then(response => response.json())
                .then(data => {
                    var newLocation = {
                        lat: data.lat,
                        lng: data.lng
                    };

                    // Check if the new location is different from the previous location
                    if (newLocation.lat === previousLocation.lat && newLocation.lng === previousLocation.lng) {
                        console.log('Location is unchanged. No update needed.');
                        return;
                    }

                    // Calculate the bearing (rotation angle)
                    var rotationAngle = calculateBearing(previousLocation, newLocation);

                    // Move the marker to the new location and rotate it
                    marker.setPosition(newLocation);
                    marker.setRotation(rotationAngle);

                    // Update previous location to the new location
                    previousLocation = newLocation;

                    // Optionally, center the map on the new location
                    map.setCenter(newLocation);

                    // Recalculate and update the route segments for in-progress trips
                    calculateRoute();
                })
                .catch(error => console.error('Error fetching car location:', error));
        }

        // Function to calculate the bearing (angle) between two locations
        function calculateBearing(start, end) {
            var startLat = degreesToRadians(start.lat);
            var startLng = degreesToRadians(start.lng);
            var endLat = degreesToRadians(end.lat);
            var endLng = degreesToRadians(end.lng);

            var dLng = endLng - startLng;

            var y = Math.sin(dLng) * Math.cos(endLat);
            var x = Math.cos(startLat) * Math.sin(endLat) - Math.sin(startLat) * Math.cos(endLat) * Math.cos(dLng);
            var bearing = Math.atan2(y, x);

            return radiansToDegrees(bearing); // Convert from radians to degrees
        }

        // Custom RotatingMarker class to handle rotation and position update
        function RotatingMarker(position, map, imageUrl) {
            this.position = position;
            this.rotation = 250;

            // Create the marker div
            this.div = document.createElement('div');
            this.div.style.position = 'absolute';
            this.div.style.width = '50px';
            this.div.style.height = '38px';
            this.div.style.backgroundImage = `url(${imageUrl})`;
            this.div.style.backgroundSize = 'contain';
            this.div.style.backgroundRepeat = 'no-repeat';

            // Append the marker div to the map
            this.setMap(map);
        }

        RotatingMarker.prototype = new google.maps.OverlayView();

        RotatingMarker.prototype.onAdd = function() {
            this.getPanes().overlayLayer.appendChild(this.div);
        };

        RotatingMarker.prototype.draw = function() {
            var overlayProjection = this.getProjection();
            var position = overlayProjection.fromLatLngToDivPixel(this.position);

            // Position the div
            this.div.style.left = (position.x - 25) + 'px'; // Center the icon
            this.div.style.top = (position.y - 25) + 'px';

            // Apply the rotation
            this.div.style.transform = `rotate(${this.rotation}deg)`;
        };

        RotatingMarker.prototype.setPosition = function(position) {
            this.position = position;
            this.draw();
        };

        RotatingMarker.prototype.setRotation = function(rotation) {
            this.rotation = rotation;
            this.draw();
        };

        // Helper functions to convert between degrees and radians
        function degreesToRadians(degrees) {
            return degrees * (Math.PI / 180);
        }

        function radiansToDegrees(radians) {
            return radians * (180 / Math.PI);
        }

        function addDistanceLabel(map, distanceText) {
            distanceLabel = new google.maps.InfoWindow({
                content: `<div  style="color:#000;  border-radius: 5px; font-weight: bold;">${distanceText}</div>`,
                position: map
                    .getCenter(), // Default to map center; can be updated later to the midpoint of the route
                pixelOffset: new google.maps.Size(0, -10)
            });

            distanceLabel.open(map);
        }
        // Initialize the map on page load
        window.onload = initMap;
    </script>
@endpush
