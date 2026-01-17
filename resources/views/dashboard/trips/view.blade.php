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
                            <div class="card-title">Trip Code : {{ $trip->code }} ({{ $type[$trip->type] }}) @if (($trip->type === 'car' && $trip->air_conditioned == '1') || $trip->type === 'comfort_car')
                                    <i class="fa fa-snowflake" style="color: rgb(0, 213, 255);"></i>
                                @endif
                            </div>
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
                                                {{ ucwords($trip->user->name) }}</a>
                                            @if ($trip->student_trip == '1')
                                                (<span style="color:rgb(255, 215, 16)">Student</span>)
                                            @endif
                                        </label>
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
                                        <label>Created at : <span
                                                style="color: #95c408">{{ date('d M.Y h:i a', strtotime($trip->created_at)) }}</span>
                                            @if ($trip->scheduled == '1')
                                                <span class="badge badge-secondary"
                                                    style="background-color:rgb(28, 161, 34);">Scheduled</span>
                                            @endif
                                        </label>
                                    </div>
                                    <div class="form-group"style="margin-bottom: 0.75rem;">
                                        <label>Driver Arrived at : <span
                                                style="color: #95c408">{{ date('d M.Y h:i a', strtotime($trip->driver_arrived)) }}</span>
                                        </label>
                                    </div>
                                    <div class="form-group"style="margin-bottom: 0.75rem;">
                                        <label>Start At : <span
                                                style="color: #95c408">{{ date('d M.Y', strtotime($trip->start_date)) }}
                                                {{ date('h:i a', strtotime($trip->start_time)) }}</span></label>
                                    </div>
                                    <div class="form-group"style="margin-bottom: 0rem;">
                                        <label>End At : <span
                                                style="color: #95c408">{{ date('d M.Y', strtotime($trip->end_date)) }}
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
                                <label>Distance : {{ $trip->distance }} KM</label>
                            </div>


                            <div class="form-group">
                                <label>Driver commission : {{ $trip->driver_rate }} LE</label>
                            </div>
                            <div class="form-group">
                                <label>Application commission : {{ $trip->app_rate }} LE</label>
                            </div>
                            <div class="form-group">
                                <label>Delay Cost : {{ $trip->delay_cost }} LE</label>
                            </div>

                            <div class="form-group">
                                <label>Discount : {{ $trip->discount }} LE</label>
                            </div>
                            <div class="form-group">
                                <label>Total Price : {{ $trip->total_price }} LE</label>
                            </div>
                            <div class="form-group">
                                <label>Tip : {{ $trip->tip }} LE</label>
                            </div>
                            <div class="form-group">
                                <label>Payment Status : {{ $trip->payment_status }}</label>
                            </div>
                            <div class="form-group">
                                <label>Payment Method : {{ ucwords($trip->payment_method) }}</label>
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
                                    <label>Cancelled Reason : @if ($trip->trip_cancelling_reason_id)
                                            <span
                                                style="text-transform: none;">{{ $trip->cancelling_reason->reason ?? 'N/A' }}</span>
                                        @else
                                            <span
                                                style="text-transform: none;">{{ $trip->trip_cancelling_reason_text ?? 'N/A' }}</span>
                                        @endif
                                    </label>
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
        var map,
            directionsService,
            routeRenderer,
            marker,
            previousLocation,
            travelledPath = [],
            travelledPolyline,
            distanceLabel;

        var tripDestinations = @json(
            $destinations->map(fn($d) => [
                    'lat' => $d->lat,
                    'lng' => $d->lng,
                    'address' => $d->address,
                ]));

        function initMap() {

            @php
                $vehicle = $trip->type === 'scooter' ? $trip->scooter : $trip->car;
                $vehicleLat = $vehicle ? $vehicle->lat : $trip->start_lat;
                $vehicleLng = $vehicle ? $vehicle->lng : $trip->start_lng;
            @endphp

            previousLocation = {
                lat: {{ $vehicleLat }},
                lng: {{ $vehicleLng }}
            };

            map = new google.maps.Map(document.getElementById('map'), {
                zoom: 13,
                center: previousLocation
            });

            directionsService = new google.maps.DirectionsService();

            routeRenderer = new google.maps.DirectionsRenderer({
                map: map,
                suppressMarkers: true,
                polylineOptions: {
                    strokeColor: '#0000FF',
                    strokeWeight: 4
                }
            });

            placeMarkers();

            if ("{{ $trip->status }}" === "in_progress" || "{{ $trip->status }}" === "pending") {
                marker = new RotatingMarker(
                    previousLocation,
                    map,
                    "{{ $trip->type === 'scooter'
                        ? asset('dashboard/scooter-icon.png')
                        : asset('dashboard/Travel-car-topview.svg.png') }}"
                );

                travelledPath.push(new google.maps.LatLng(previousLocation.lat, previousLocation.lng));

                setInterval(updateVehicleLocation, 3000);
            }

            if ("{{ $trip->status }}" !== "cancelled") {
                addDistanceLabel('{{ $trip->distance }} km');
            }

            drawRemainingRoute(previousLocation);
        }

        /* ===================== ROUTES ===================== */

        function drawRemainingRoute(vehicleLocation) {

            var finalDest = tripDestinations[tripDestinations.length - 1];

            var waypoints = tripDestinations.slice(0, -1).map(d => ({
                location: new google.maps.LatLng(d.lat, d.lng),
                stopover: true
            }));

            directionsService.route({
                origin: vehicleLocation,
                destination: new google.maps.LatLng(finalDest.lat, finalDest.lng),
                waypoints: waypoints,
                travelMode: google.maps.TravelMode.DRIVING
            }, function(result, status) {
                if (status === "OK") {
                    routeRenderer.setDirections(result);

                    var path = result.routes[0].overview_path;
                    var midpoint = path[Math.floor(path.length / 2)];
                    if (distanceLabel) distanceLabel.setPosition(midpoint);
                }
            });
        }

        /* ===================== MARKERS ===================== */

        function placeMarkers() {

            new google.maps.Marker({
                position: new google.maps.LatLng({{ $trip->start_lat }}, {{ $trip->start_lng }}),
                map: map,
                label: "S",
                icon: "https://maps.google.com/mapfiles/ms/icons/green-dot.png"
            });

            tripDestinations.forEach((dest, i) => {
                new google.maps.Marker({
                    position: new google.maps.LatLng(dest.lat, dest.lng),
                    map: map,
                    label: (i + 1).toString(),
                    icon: "https://maps.google.com/mapfiles/ms/icons/red-dot.png",
                    title: dest.address
                });
            });
        }

        /* ===================== LIVE UPDATE ===================== */

        function updateVehicleLocation() {

            var endpoint =
                "{{ $trip->type === 'scooter'
                    ? '/admin-dashboard/scooter-location/' . ($trip->scooter->id ?? 0)
                    : '/admin-dashboard/car-location/' . ($trip->car->id ?? 0) }}";

            fetch(endpoint)
                .then(r => r.json())
                .then(data => {

                    var newLocation = {
                        lat: data.lat,
                        lng: data.lng
                    };

                    if (
                        newLocation.lat === previousLocation.lat &&
                        newLocation.lng === previousLocation.lng
                    ) return;

                    var rotation = calculateBearing(previousLocation, newLocation);

                    marker.setPosition(newLocation);
                    marker.setRotation(rotation);

                    travelledPath.push(new google.maps.LatLng(newLocation.lat, newLocation.lng));

                    if (!travelledPolyline) {
                        travelledPolyline = new google.maps.Polyline({
                            path: travelledPath,
                            strokeColor: '#00FF00',
                            strokeOpacity: 1,
                            strokeWeight: 4,
                            map: map
                        });
                    } else {
                        travelledPolyline.setPath(travelledPath);
                    }

                    previousLocation = newLocation;
                    map.panTo(newLocation);

                    drawRemainingRoute(newLocation);
                });
        }

        /* ===================== ROTATING MARKER ===================== */

        function RotatingMarker(position, map, image) {
            this.position = position;
            this.rotation = 0;

            this.div = document.createElement('div');
            this.div.style.width = '50px';
            this.div.style.height = '40px';
            this.div.style.backgroundImage = `url(${image})`;
            this.div.style.backgroundSize = 'contain';
            this.div.style.backgroundRepeat = 'no-repeat';

            this.setMap(map);
        }

        RotatingMarker.prototype = new google.maps.OverlayView();

        RotatingMarker.prototype.onAdd = function() {
            this.getPanes().overlayLayer.appendChild(this.div);
        };

        RotatingMarker.prototype.draw = function() {
            var p = this.getProjection().fromLatLngToDivPixel(this.position);
            this.div.style.left = (p.x - 25) + 'px';
            this.div.style.top = (p.y - 20) + 'px';
            this.div.style.transform = `rotate(${this.rotation}deg)`;
        };

        RotatingMarker.prototype.setPosition = function(pos) {
            this.position = pos;
            this.draw();
        };

        RotatingMarker.prototype.setRotation = function(rot) {
            this.rotation = rot;
            this.draw();
        };

        /* ===================== HELPERS ===================== */

        function calculateBearing(start, end) {
            var y = Math.sin((end.lng - start.lng) * Math.PI / 180) *
                Math.cos(end.lat * Math.PI / 180);
            var x = Math.cos(start.lat * Math.PI / 180) *
                Math.sin(end.lat * Math.PI / 180) -
                Math.sin(start.lat * Math.PI / 180) *
                Math.cos(end.lat * Math.PI / 180) *
                Math.cos((end.lng - start.lng) * Math.PI / 180);

            return (Math.atan2(y, x) * 180 / Math.PI + 360) % 360;
        }

        function addDistanceLabel(text) {
            distanceLabel = new google.maps.InfoWindow({
                content: `<strong>${text}</strong>`
            });
            distanceLabel.open(map);
        }

        window.onload = initMap;
    </script>
@endpush
