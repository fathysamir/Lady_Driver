@extends('dashboard.layout.app')
@section('title', 'Dashboard - view trip')
@section('content')
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        .star-rating {
            color: #FFF;
        }
        .star {
            font-size: 24px;
        }
        .filled {
            color: gold;
        }
        .car-link:hover {
            color: blue;
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

                                /* ── Payment ── */
                                $totalPrice    = (float) ($trip->total_price  ?? 0);
                                $delayCost     = (float) ($trip->delay_cost   ?? 0);
                                $discount      = (float) ($trip->discount     ?? 0);
                                $tip           = (float) ($trip->tip          ?? 0);
                                $driverRate    = (float) ($trip->driver_rate  ?? 0);
                                $appRate       = (float) ($trip->app_rate     ?? 0);
                                $paymentStatus = $trip->status === 'completed' ? 'Paid' : ucwords($trip->payment_status ?? 'Unpaid');

                                /* ── Status colors (from v2) ── */
                                $statusColors = [
                                    'pending'     => 'rgb(143, 118, 9)',
                                    'scheduled'   => 'rgb(112, 137, 4)',
                                    'in_progress' => 'rgb(52, 40, 223)',
                                    'completed'   => 'rgb(50, 134, 50)',
                                    'cancelled'   => 'rgb(255,0,0)',
                                    'expired'     => '#555',
                                    'created'     => 'rgb(21, 101, 192)',
                                ];
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
                                                        src="{{ getFirstMediaUrl($trip->user, $trip->user->avatarCollection) ?? asset('dashboard/user_avatar.png') }}"
                                                        class="img-circle" alt="user avatar"
                                                        style="width: 22px;height: 22px;"
                                                        onerror="this.src='{{ asset('dashboard/user_avatar.png') }}'; this.onerror=null;"></span>
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
                                                            src="{{ getFirstMediaUrl($trip->scooter->owner, $trip->scooter->owner->avatarCollection) ?? asset('dashboard/user_avatar.png') }}"
                                                            class="img-circle" alt="user avatar"
                                                            style="width: 22px;height: 22px;"
                                                            onerror="this.src='{{ asset('dashboard/user_avatar.png') }}'; this.onerror=null;"></span>
                                                    {{ ucwords($trip->scooter->owner->name) }}
                                                </a>
                                            @elseif ($trip->car)
                                                <a href="{{ url('/admin-dashboard/user/edit/' . $trip->car->owner->id) }}">
                                                    <span class="user-profile"><img
                                                            src="{{ getFirstMediaUrl($trip->car->owner, $trip->car->owner->avatarCollection) ?? asset('dashboard/user_avatar.png') }}"
                                                            class="img-circle" alt="user avatar"
                                                            style="width: 22px;height: 22px;"
                                                            onerror="this.src='{{ asset('dashboard/user_avatar.png') }}'; this.onerror=null;"></span>
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
                                    <form action="{{ route('update.trip.status', $trip->id) }}" method="POST">
                                        @csrf

                                        <div class="form-group">
                                            <label>Change Status :</label>

                                            <select class="form-control" name="status">
                                                <option value="created" @if ($trip->status == 'created') selected @endif>Created</option>
                                                <option value="scheduled" @if ($trip->status == 'scheduled') selected @endif>Scheduled</option>
                                                <option value="pending" @if ($trip->status == 'pending') selected @endif>Pending</option>
                                                <option value="in_progress" @if ($trip->status == 'in_progress') selected @endif>In Progress</option>
                                                <option value="completed" @if ($trip->status == 'completed') selected @endif>Completed</option>
                                                <option value="cancelled" @if ($trip->status == 'cancelled') selected @endif>Cancelled</option>
                                                <option value="expired" @if ($trip->status == 'expired') selected @endif>Expired</option>
                                            </select>
                                        </div>

                                        <button type="submit" class="btn btn-light px-5">
                                            <i class="fa fa-save"></i> Save
                                        </button>
                                    </form>

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
                                <label>Driver Commission : {{ number_format($driverRate, 2) }} LE</label>
                            </div>
                            <div class="form-group">
                                <label>Application Commission : {{ number_format($appRate, 2) }} LE</label>
                            </div>
                            @if($delayCost > 0)
                            <div class="form-group">
                                <label>Delay Cost : {{ number_format($delayCost, 2) }} LE</label>
                            </div>
                            @endif
                            @if($discount > 0)
                            <div class="form-group">
                                <label>Discount : {{ number_format($discount, 2) }} LE</label>
                            </div>
                            @endif
                            <div class="form-group">
                                <label>Total Price : {{ number_format($totalPrice, 2) }} LE</label>
                            </div>
                            @if($tip > 0)
                            <div class="form-group">
                                <label>Tip : {{ number_format($tip, 2) }} LE</label>
                            </div>
                            @endif
                            <div class="form-group">
                                <label>Payment Status : {{ $paymentStatus }}</label>
                            </div>
                            <div class="form-group">
                                <label>Payment Method : {{ ucwords($trip->payment_method ?? 'N/A') }}</label>
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
                                    @for ($i = 1; $i <= 5; $i++)
                                        <span class="star {{ $i <= $trip->client_stare_rate ? 'filled' : '' }}">&#9733;</span>
                                    @endfor
                                </div>
                            </div>
                            <div class="form-group">
                                <label>Client Comment : <span
                                        style="text-transform: none;">{{ $trip->client_comment ?? 'N/A' }}</span></label>
                            </div>
                            <div class="form-group" style="display: flex;align-items: center;">
                                <label>Driver evaluation : </label>
                                <div class="star-rating" style="margin-bottom: 10px;">
                                    @for ($i = 1; $i <= 5; $i++)
                                        <span class="star {{ $i <= $trip->driver_stare_rate ? 'filled' : '' }}">&#9733;</span>
                                    @endfor
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
    <script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyCWDitjrboDO2zHDtZHzLlgRLduXi7-3Es&libraries=geometry"></script>

    <script>
        /* ─────────────────────────────────────────────────────────
           Data from PHP
        ───────────────────────────────────────────────────────── */
        var TRIP_STATUS  = "{{ $trip->status }}";
        var TRIP_TYPE    = "{{ $trip->type }}";
        var START_LAT    = {{ $trip->start_lat }};
        var START_LNG    = {{ $trip->start_lng }};
        var DESTINATIONS = @json($destinations->map(fn($d) => ['lat' => $d->lat, 'lng' => $d->lng, 'address' => $d->address]));

        @php
            $vehicleType = $trip->type === 'scooter' ? 'scooter' : 'car';
            $vehicle     = $vehicleType === 'scooter' ? $trip->scooter : $trip->car;
            $vLat        = $vehicle ? (float) $vehicle->lat : (float) $trip->start_lat;
            $vLng        = $vehicle ? (float) $vehicle->lng : (float) $trip->start_lng;
            $vehicleId   = $vehicle ? $vehicle->id : 0;
        @endphp
        var VEHICLE_LAT  = {{ $vLat }};
        var VEHICLE_LNG  = {{ $vLng }};
        var VEHICLE_ID   = {{ $vehicleId }};
        var VEHICLE_ICON = "{{ $trip->type === 'scooter' ? asset('dashboard/scooter_top_view_312787-removebg-preview.png') : asset('dashboard/Travel-car-topview.svg.png') }}";
        var LOCATION_URL = TRIP_TYPE === 'scooter'
            ? '/admin-dashboard/scooter-location/' + VEHICLE_ID
            : '/admin-dashboard/car-location/'     + VEHICLE_ID;

        /* ─────────────────────────────────────────────────────────
           Globals
        ───────────────────────────────────────────────────────── */
        var map, directionsService;
        var routeRenderers   = [];
        var distanceLabel    = null;
        var movingMarker     = null;
        var previousLocation = { lat: VEHICLE_LAT, lng: VEHICLE_LNG };

        /* ─────────────────────────────────────────────────────────
           Init
        ───────────────────────────────────────────────────────── */
        function initMap() {
            map = new google.maps.Map(document.getElementById('map'), {
                zoom: 12,
                center: { lat: START_LAT, lng: START_LNG }
            });

            directionsService = new google.maps.DirectionsService();

            placeStaticMarkers();
            drawRoutes(previousLocation);

            if (TRIP_STATUS === 'in_progress' || TRIP_STATUS === 'pending') {
                movingMarker = new RotatingMarker({ lat: VEHICLE_LAT, lng: VEHICLE_LNG }, map, VEHICLE_ICON);
                setInterval(pollVehicleLocation, 3000);
            }

            if (TRIP_STATUS !== 'cancelled') {
                addDistanceLabel(map, '{{ $trip->distance }} km');
            }
        }

        /* ─────────────────────────────────────────────────────────
           Static markers
        ───────────────────────────────────────────────────────── */
        function placeStaticMarkers() {
            new google.maps.Marker({
                position: { lat: START_LAT, lng: START_LNG },
                map: map,
                label: 'S',
                icon: 'https://maps.google.com/mapfiles/ms/icons/green-dot.png',
                title: 'Start Point'
            });

            DESTINATIONS.forEach(function(dest, index) {
                new google.maps.Marker({
                    position: { lat: dest.lat, lng: dest.lng },
                    map: map,
                    label: String(index + 1),
                    icon: 'https://maps.google.com/mapfiles/ms/icons/red-dot.png',
                    title: dest.address
                });
            });
        }

        /* ─────────────────────────────────────────────────────────
           Route drawing (improved logic from v2)
        ───────────────────────────────────────────────────────── */
        function drawRoutes(vehiclePos) {
            routeRenderers.forEach(function(r) { r.setMap(null); });
            routeRenderers = [];

            var start = new google.maps.LatLng(START_LAT, START_LNG);
            var final = new google.maps.LatLng(
                DESTINATIONS[DESTINATIONS.length - 1].lat,
                DESTINATIONS[DESTINATIONS.length - 1].lng
            );
            var allWaypoints = DESTINATIONS.slice(0, -1).map(function(d) {
                return { location: new google.maps.LatLng(d.lat, d.lng), stopover: true };
            });

            if (TRIP_STATUS === 'in_progress') {
                var nearestIdx  = findNearestDestinationIndex(vehiclePos);
                var vLatLng     = new google.maps.LatLng(vehiclePos.lat, vehiclePos.lng);

                var passedWP = DESTINATIONS.slice(0, nearestIdx).map(function(d) {
                    return { location: new google.maps.LatLng(d.lat, d.lng), stopover: true };
                });
                var remainingWP = DESTINATIONS.slice(nearestIdx + 1, -1).map(function(d) {
                    return { location: new google.maps.LatLng(d.lat, d.lng), stopover: true };
                });

                if (nearestIdx > 0) {
                    drawSegment(start, vLatLng, '#fc01f8', passedWP, false);
                }
                drawSegment(vLatLng, final, '#0000FF', remainingWP, true);

            } else if (TRIP_STATUS === 'completed') {
                drawSegment(start, final, '#fc01f8', allWaypoints, false);

            } else if (TRIP_STATUS === 'pending') {
                var vehicleLatLng = new google.maps.LatLng(vehiclePos.lat, vehiclePos.lng);
                drawSegment(vehicleLatLng, start, '#ff8f00', [], false);
                drawSegment(start, final, '#0000FF', allWaypoints, false);

            } else if (TRIP_STATUS === 'cancelled') {
                drawSegment(start, final, '#FF0000', allWaypoints, false);

            } else {
                // created / scheduled
                drawSegment(start, final, '#0000FF', allWaypoints, true);
            }
        }

        function drawSegment(origin, destination, color, waypoints, updateDistanceLabel) {
            directionsService.route({
                origin: origin,
                destination: destination,
                waypoints: waypoints,
                optimizeWaypoints: false,
                travelMode: google.maps.TravelMode.DRIVING
            }, function(result, status) {
                if (status !== 'OK') return;

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

                if (updateDistanceLabel && distanceLabel) {
                    var path     = result.routes[0].overview_path;
                    var midpoint = path[Math.floor(path.length / 2)];
                    distanceLabel.setPosition(midpoint);
                }
            });
        }

        /* ─────────────────────────────────────────────────────────
           Live vehicle polling
        ───────────────────────────────────────────────────────── */
        function pollVehicleLocation() {
            fetch(LOCATION_URL)
                .then(function(r) { return r.json(); })
                .then(function(data) {
                    var newLocation = { lat: parseFloat(data.lat), lng: parseFloat(data.lng) };

                    if (newLocation.lat === previousLocation.lat && newLocation.lng === previousLocation.lng) {
                        return;
                    }

                    var bearing = computeBearing(previousLocation, newLocation);
                    movingMarker.setPosition(newLocation);
                    movingMarker.setRotation(bearing);
                    previousLocation = newLocation;

                    map.setCenter(newLocation);
                    drawRoutes(newLocation);
                })
                .catch(function(e) { console.error('Error fetching vehicle location:', e); });
        }

        /* ─────────────────────────────────────────────────────────
           Helpers
        ───────────────────────────────────────────────────────── */
        function findNearestDestinationIndex(pos) {
            var minDist = Infinity, idx = 0;
            DESTINATIONS.forEach(function(d, i) {
                var dist = google.maps.geometry.spherical.computeDistanceBetween(
                    new google.maps.LatLng(pos.lat, pos.lng),
                    new google.maps.LatLng(d.lat, d.lng)
                );
                if (dist < minDist) { minDist = dist; idx = i; }
            });
            return idx;
        }

        function computeBearing(from, to) {
            var lat1 = from.lat * Math.PI / 180, lat2 = to.lat * Math.PI / 180;
            var dLng = (to.lng - from.lng) * Math.PI / 180;
            var y = Math.sin(dLng) * Math.cos(lat2);
            var x = Math.cos(lat1) * Math.sin(lat2) - Math.sin(lat1) * Math.cos(lat2) * Math.cos(dLng);
            return Math.atan2(y, x) * 180 / Math.PI;
        }

        function addDistanceLabel(map, distanceText) {
            distanceLabel = new google.maps.InfoWindow({
                content: '<div style="color:#000; border-radius: 5px; font-weight: bold;">' + distanceText + '</div>',
                position: map.getCenter(),
                pixelOffset: new google.maps.Size(0, -10)
            });
            distanceLabel.open(map);
        }

        /* ─────────────────────────────────────────────────────────
           Custom rotating overlay marker
        ───────────────────────────────────────────────────────── */
        function RotatingMarker(position, map, imageUrl) {
            this.position = new google.maps.LatLng(position.lat, position.lng);
            this.rotation = 250;
            this.div = document.createElement('div');
            this.div.style.position = 'absolute';
            this.div.style.width = '50px';
            this.div.style.height = '38px';
            this.div.style.backgroundImage = 'url(' + imageUrl + ')';
            this.div.style.backgroundSize = 'contain';
            this.div.style.backgroundRepeat = 'no-repeat';
            this.setMap(map);
        }
        RotatingMarker.prototype = new google.maps.OverlayView();

        RotatingMarker.prototype.onAdd = function() {
            this.getPanes().overlayLayer.appendChild(this.div);
        };

        RotatingMarker.prototype.draw = function() {
            var overlayProjection = this.getProjection();
            var pt = overlayProjection.fromLatLngToDivPixel(this.position);
            this.div.style.left      = (pt.x - 25) + 'px';
            this.div.style.top       = (pt.y - 25) + 'px';
            this.div.style.transform = 'rotate(' + this.rotation + 'deg)';
        };

        RotatingMarker.prototype.setPosition = function(pos) {
            this.position = new google.maps.LatLng(pos.lat, pos.lng);
            this.draw();
        };

        RotatingMarker.prototype.setRotation = function(rotation) {
            this.rotation = rotation;
            this.draw();
        };

        window.onload = initMap;
    </script>
@endpush