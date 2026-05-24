@extends('dashboard.layout.app')
@section('title', 'Dashboard - view trip')
@section('content')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
    .star-rating { color: #fff; }
    .star        { font-size: 20px; }
    .filled      { color: gold; }
    .car-link:hover { color: #30638a; }

    .info-label {
        font-weight: 600;
        color: #aaa;
        font-size: 12px;
        text-transform: uppercase;
        letter-spacing: .5px;
        margin-bottom: 2px;
    }
    .info-value {
        font-size: 15px;
        color: #ddd;
        margin-bottom: 12px;
    }
    .price-row {
        display: flex;
        justify-content: space-between;
        padding: 6px 0;
        border-bottom: 1px solid rgba(255,255,255,.07);
        font-size: 14px;
    }
    .price-row:last-child { border-bottom: none; }
    .price-row .label  { color: #aaa; }
    .price-row .amount { color: #e0e0e0; font-weight: 600; }
    .price-row.total   { margin-top: 4px; }
    .price-row.total .label  { color: #fff; font-size: 15px; font-weight: 700; }
    .price-row.total .amount { color: #95c408; font-size: 16px; font-weight: 700; }
    .price-row.highlight .amount { color: #4fc3f7; }
    .price-row.discount  .amount { color: #ef9a9a; }

    .section-title {
        display: flex;
        align-items: center;
        margin: 20px 0 12px;
    }
    .section-title h4 {
        margin: 0 12px 0 0;
        white-space: nowrap;
        font-size: 15px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 1px;
        color: #95c408;
    }
    .section-title hr {
        flex: 1;
        margin: 0;
        border-color: rgba(255,255,255,.1);
    }

    .status-badge {
        display: inline-block;
        padding: 3px 12px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 700;
        letter-spacing: .5px;
    }

    #map { height: 520px; margin: 16px 0; border-radius: 8px; overflow: hidden; }

    .two-col { display: flex; gap: 32px; }
    .two-col > div { flex: 1; min-width: 0; }

    @media(max-width:768px) {
        .two-col { flex-direction: column; gap: 0; }
    }
</style>

<div class="content-wrapper">
<div class="container-fluid">
<div class="row mt-3">
<div class="col-lg-12">
<div class="card">
<div class="card-body">

@php
    $typeLabels = [
        'car'         => 'Standard Trip',
        'comfort_car' => 'Comfort Trip',
        'scooter'     => 'Scooter Trip',
    ];

    /* ── Prices ── */
    $totalPrice  = (float) $trip->total_price;
    $delayCost   = (float) ($trip->delay_cost ?? 0);
    $discount    = (float) ($trip->discount   ?? 0);
    $tip         = (float) ($trip->tip        ?? 0);

    /* Derive VAT, commission, income tax using same logic as TripByID API */
    $category = match($trip->type) {
        'comfort_car' => 'Comfort Trips',
        'scooter'     => 'Scooter Trips',
        default       => 'Car Trips',
    };
    $driverLevel = 1;
    if ($trip->type === 'scooter' && $trip->scooter?->owner) {
        $driverLevel = $trip->scooter->owner->level ?? 1;
    } elseif ($trip->car?->owner) {
        $driverLevel = $trip->car->owner->level ?? 1;
    }

    $taxes = getTripSettings($category, $driverLevel);

    $vatRate          = ($taxes['vat_percentage'] ?? 14) / 100;
    $basePrice        = round($totalPrice / (1 + $vatRate), 2);
    $vatAmount        = round($basePrice * $vatRate, 2);
    $priceWithoutVat  = round($totalPrice - $vatAmount, 2);
    $commissionRate   = $taxes['application_commission'] ?? 25;
    $commissionAmount = round($priceWithoutVat * ($commissionRate / 100), 2);
    $driverBeforeTax  = round($priceWithoutVat - $commissionAmount, 2);
    $incomeTaxRate    = $taxes['income_tax_percentage'] ?? 5;
    $incomeTaxAmount  = round($basePrice * ($incomeTaxRate / 100), 2);
    $driverRemaining  = round(($driverBeforeTax - $incomeTaxAmount) + $delayCost, 2);

    $statusColors = [
        'pending'     => '#8f7609',
        'scheduled'   => '#e8a000',
        'in_progress' => '#3428df',
        'completed'   => '#2e7d32',
        'cancelled'   => '#c62828',
        'expired'     => '#555',
        'created'     => '#1565c0',
    ];
    $statusColor = $statusColors[$trip->status] ?? '#555';
@endphp

{{-- ── Header ── --}}
<div class="card-title" style="font-size:17px;">
    Trip Code: <strong>{{ $trip->code }}</strong>
    &nbsp;
    <span class="status-badge" style="background:{{ $statusColor }}">
        {{ ucfirst(str_replace('_',' ',$trip->status)) }}
    </span>
    &nbsp;
    <span style="color:#aaa;font-size:13px;">{{ $typeLabels[$trip->type] ?? $trip->type }}</span>
    @if(($trip->type === 'car' && $trip->air_conditioned == '1') || $trip->type === 'comfort_car')
        &nbsp;<i class="fa fa-snowflake" style="color:#00d5ff;" title="Air Conditioned"></i>
    @endif
</div>
<hr>

{{-- ── Map ── --}}
<div id="map"></div>

{{-- ── Trip Info ── --}}
<div class="two-col">
    {{-- Left --}}
    <div>
        <div class="section-title"><h4>People</h4><hr></div>

        <div class="info-label">Client</div>
        <div class="info-value">
            <a href="{{ url('/admin-dashboard/user/edit/'.$trip->user->id) }}">
                <img @if(getFirstMediaUrl($trip->user,$trip->user->avatarCollection)!=null)
                         src="{{ getFirstMediaUrl($trip->user,$trip->user->avatarCollection) }}"
                     @else src="{{ asset('dashboard/user_avatar.png') }}" @endif
                     class="img-circle" style="width:22px;height:22px;margin-right:5px;" alt="">
                {{ ucwords($trip->user->name) }}
            </a>
            @if($trip->student_trip == '1')
                <span style="color:#ffd710;font-size:12px;">(Student)</span>
            @endif
        </div>

        <div class="info-label">Driver</div>
        <div class="info-value">
            @if($trip->type === 'scooter' && $trip->scooter)
                <a href="{{ url('/admin-dashboard/user/edit/'.$trip->scooter->owner->id) }}">
                    <img @if(getFirstMediaUrl($trip->scooter->owner,$trip->scooter->owner->avatarCollection)!=null)
                             src="{{ getFirstMediaUrl($trip->scooter->owner,$trip->scooter->owner->avatarCollection) }}"
                         @else src="{{ asset('dashboard/user_avatar.png') }}" @endif
                         class="img-circle" style="width:22px;height:22px;margin-right:5px;" alt="">
                    {{ ucwords($trip->scooter->owner->name) }}
                </a>
            @elseif($trip->car)
                <a href="{{ url('/admin-dashboard/user/edit/'.$trip->car->owner->id) }}">
                    <img @if(getFirstMediaUrl($trip->car->owner,$trip->car->owner->avatarCollection)!=null)
                             src="{{ getFirstMediaUrl($trip->car->owner,$trip->car->owner->avatarCollection) }}"
                         @else src="{{ asset('dashboard/user_avatar.png') }}" @endif
                         class="img-circle" style="width:22px;height:22px;margin-right:5px;" alt="">
                    {{ ucwords($trip->car->owner->name) }}
                </a>
            @else
                <span style="color:#aaa;">N/A (no vehicle assigned)</span>
            @endif
        </div>

        <div class="section-title" style="margin-top:16px;"><h4>Timeline</h4><hr></div>

        <div class="info-label">Created At</div>
        <div class="info-value">
            {{ date('d M Y  h:i a', strtotime($trip->created_at)) }}
            @if($trip->scheduled == '1')
                <span class="status-badge" style="background:#1c8c22;margin-left:6px;">Scheduled</span>
            @endif
        </div>

        @if($trip->driver_arrived)
        <div class="info-label">Driver Arrived At</div>
        <div class="info-value">{{ date('d M Y  h:i a', strtotime($trip->driver_arrived)) }}</div>
        @endif

        @if($trip->start_date)
        <div class="info-label">Trip Started At</div>
        <div class="info-value">
            {{ date('d M Y', strtotime($trip->start_date)) }}
            {{ date('h:i a', strtotime($trip->start_time)) }}
        </div>
        @endif

        @if($trip->end_date)
        <div class="info-label">Trip Ended At</div>
        <div class="info-value">
            {{ date('d M Y', strtotime($trip->end_date)) }}
            {{ date('h:i a', strtotime($trip->end_time)) }}
        </div>
        @endif
    </div>

    {{-- Right --}}
    <div>
        <div class="section-title"><h4>Route</h4><hr></div>

        <div class="info-label"><i class="fa fa-map-marker-alt" style="color:#2e7d32;"></i> From</div>
        <div class="info-value" style="background:rgba(255,255,255,.05);padding:8px 12px;border-radius:6px;">
            {{ $trip->address1 ?? 'N/A' }}
        </div>

        <div class="info-label" style="margin-top:8px;"><i class="fa fa-flag-checkered" style="color:#c62828;"></i> To</div>
        <div class="info-value" style="background:rgba(255,255,255,.05);padding:8px 12px;border-radius:6px;">
            @foreach($trip->finalDestination as $i => $dest)
                <div style="display:flex;align-items:center;margin-bottom:{{ !$loop->last ? '6px' : '0' }};">
                    <span class="status-badge" style="background:#c62828;margin-right:8px;min-width:22px;text-align:center;">{{ $i+1 }}</span>
                    {{ $dest->address }}
                </div>
            @endforeach
        </div>

        <div class="info-label" style="margin-top:12px;">Distance</div>
        <div class="info-value">{{ $trip->distance ?? '—' }} km</div>

        <div class="section-title" style="margin-top:8px;"><h4>Change Status</h4><hr></div>
        <form action="{{ route('update.trip.status', $trip->id) }}" method="POST" style="display:flex;gap:8px;align-items:flex-end;">
            @csrf
            <select class="form-control" name="status" style="max-width:200px;">
                @foreach(['created','scheduled','pending','in_progress','completed','cancelled','expired'] as $s)
                    <option value="{{ $s }}" @if($trip->status==$s) selected @endif>{{ ucfirst(str_replace('_',' ',$s)) }}</option>
                @endforeach
            </select>
            <button type="submit" class="btn btn-light px-4">
                <i class="fa fa-save"></i> Save
            </button>
        </form>
    </div>
</div>

{{-- ── Vehicle ── --}}
<div class="section-title"><h4>{{ $trip->type === 'scooter' ? 'Scooter' : 'Car' }}</h4><hr></div>
<div style="display:flex;align-items:center;gap:24px;flex-wrap:wrap;">
    @if($trip->type === 'scooter' && $trip->scooter)
        <img style="border-radius:6px;width:220px;height:140px;object-fit:cover;"
             src="{{ $trip->scooter->image ?? asset('dashboard/scooter_avatar.png') }}" alt="scooter">
        <div>
            <a href="{{ url('/admin-dashboard/scooter/edit/'.$trip->scooter->id) }}" class="car-link">
                <h4 style="margin:0 0 4px;">
                    {{ $trip->scooter->motorcycleMark->en_name ?? 'N/A' }} &mdash;
                    {{ $trip->scooter->motorcycleMark->ar_name ?? '' }}
                </h4>
                <h5 style="margin:0;color:#aaa;">
                    {{ $trip->scooter->motorcycleModel->en_name ?? 'N/A' }} &mdash;
                    {{ $trip->scooter->motorcycleModel->ar_name ?? '' }}
                    ({{ $trip->scooter->year }})
                </h5>
            </a>
        </div>
    @elseif($trip->car)
        <img style="border-radius:6px;width:220px;height:140px;object-fit:cover;"
             src="{{ $trip->car->image ?? asset('dashboard/car_avatar.png') }}" alt="car">
        <div>
            <a href="{{ url('/admin-dashboard/car/edit/'.$trip->car->id) }}" class="car-link">
                <h4 style="margin:0 0 4px;">
                    {{ $trip->car->mark->en_name ?? 'N/A' }} &mdash;
                    {{ $trip->car->mark->ar_name ?? '' }}
                </h4>
                <h5 style="margin:0;color:#aaa;">
                    {{ $trip->car->model->en_name ?? 'N/A' }} &mdash;
                    {{ $trip->car->model->ar_name ?? '' }}
                    ({{ $trip->car->year }})
                </h5>
            </a>
        </div>
    @endif
</div>

{{-- ── Payment ── --}}
<div class="section-title"><h4>Payment</h4><hr></div>
<div style="max-width:480px;">

    <div class="price-row">
        <span class="label">Base Price (excl. VAT)</span>
        <span class="amount">{{ number_format($basePrice, 2) }} LE</span>
    </div>
    @if($delayCost > 0)
    <div class="price-row">
        <span class="label">Delay Cost</span>
        <span class="amount">{{ number_format($delayCost, 2) }} LE</span>
    </div>
    @endif
    <div class="price-row">
        <span class="label">VAT ({{ $taxes['vat_percentage'] ?? 14 }}%)</span>
        <span class="amount">{{ number_format($vatAmount, 2) }} LE</span>
    </div>
    @if($discount > 0)
    <div class="price-row discount">
        <span class="label">Discount</span>
        <span class="amount">&minus; {{ number_format($discount, 2) }} LE</span>
    </div>
    @endif
    <div class="price-row total">
        <span class="label">Total Price</span>
        <span class="amount">{{ number_format($totalPrice, 2) }} LE</span>
    </div>
    @if($tip > 0)
    <div class="price-row highlight">
        <span class="label">Tip</span>
        <span class="amount">{{ number_format($tip, 2) }} LE</span>
    </div>
    @endif

    <div style="height:12px;"></div>

    {{-- Commission Breakdown --}}
    <div class="price-row">
        <span class="label">App Commission ({{ $commissionRate }}%)</span>
        <span class="amount">{{ number_format($commissionAmount, 2) }} LE</span>
    </div>
    <div class="price-row">
        <span class="label">Income Tax ({{ $incomeTaxRate }}%)</span>
        <span class="amount">{{ number_format($incomeTaxAmount, 2) }} LE</span>
    </div>
    <div class="price-row highlight">
        <span class="label">Driver Earnings</span>
        <span class="amount">{{ number_format($driverRemaining, 2) }} LE</span>
    </div>

    <div style="height:12px;"></div>

    <div class="price-row">
        <span class="label">Payment Status</span>
        <span class="amount" style="color:{{ $trip->payment_status === 'cash paid' || $trip->payment_status === 'online paid' ? '#95c408' : '#ef9a9a' }};">
            {{ ucwords($trip->payment_status ?? 'N/A') }}
        </span>
    </div>
    <div class="price-row">
        <span class="label">Payment Method</span>
        <span class="amount">{{ ucwords($trip->payment_method ?? 'N/A') }}</span>
    </div>
</div>

{{-- ── Cancellation ── --}}
@if($trip->cancelled_by_id != null)
<div class="section-title"><h4>Cancellation</h4><hr></div>
<div class="info-label">Cancelled By</div>
<div class="info-value">
    {{ $trip->cancelled_by_id == $trip->user_id ? 'Client' : 'Driver' }}
</div>
<div class="info-label">Reason</div>
<div class="info-value">
    @if($trip->trip_cancelling_reason_id)
        {{ $trip->cancelling_reason->reason ?? 'N/A' }}
    @else
        {{ $trip->trip_cancelling_reason_text ?? 'N/A' }}
    @endif
</div>
@endif

{{-- ── Ratings ── --}}
<div class="section-title"><h4>Trip Evaluation</h4><hr></div>
<div class="two-col" style="max-width:600px;">
    <div>
        <div class="info-label">Client Rating</div>
        <div class="star-rating" style="margin-bottom:4px;">
            @for($i = 1; $i <= 5; $i++)
                <span class="star {{ $i <= $trip->client_stare_rate ? 'filled' : '' }}">&#9733;</span>
            @endfor
        </div>
        <div class="info-value" style="font-size:13px;">{{ $trip->client_comment ?? 'No comment' }}</div>
    </div>
    <div>
        <div class="info-label">Driver Rating</div>
        <div class="star-rating" style="margin-bottom:4px;">
            @for($i = 1; $i <= 5; $i++)
                <span class="star {{ $i <= $trip->driver_stare_rate ? 'filled' : '' }}">&#9733;</span>
            @endfor
        </div>
        <div class="info-value" style="font-size:13px;">{{ $trip->driver_comment ?? 'No comment' }}</div>
    </div>
</div>

</div>{{-- card-body --}}
</div>{{-- card --}}
</div>{{-- col --}}
</div>{{-- row --}}
<div class="overlay toggle-menu"></div>
</div>{{-- container --}}
</div>{{-- content-wrapper --}}

@endsection

@push('scripts')
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyCWDitjrboDO2zHDtZHzLlgRLduXi7-3Es&libraries=geometry"></script>
<script>
/* ─────────────────────────────────────────────────────────
   Data from PHP
───────────────────────────────────────────────────────── */
var TRIP_STATUS       = "{{ $trip->status }}";
var TRIP_TYPE         = "{{ $trip->type }}";
var START_LAT         = {{ $trip->start_lat }};
var START_LNG         = {{ $trip->start_lng }};
var DESTINATIONS      = @json($destinations->map(fn($d) => ['lat'=>$d->lat,'lng'=>$d->lng,'address'=>$d->address]));

@php
    $vehicleType = $trip->type === 'scooter' ? 'scooter' : 'car';
    $vehicle     = $vehicleType === 'scooter' ? $trip->scooter : $trip->car;
    $vLat        = $vehicle ? (float)$vehicle->lat : (float)$trip->start_lat;
    $vLng        = $vehicle ? (float)$vehicle->lng : (float)$trip->start_lng;
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
        zoom: 13,
        center: { lat: START_LAT, lng: START_LNG },
        mapTypeControl: false,
        streetViewControl: false,
    });

    directionsService = new google.maps.DirectionsService();

    placeStaticMarkers();
    drawRoutes(previousLocation);

    // Live vehicle marker for active trips
    if (TRIP_STATUS === 'in_progress' || TRIP_STATUS === 'pending') {
        movingMarker = new RotatingMarker({ lat: VEHICLE_LAT, lng: VEHICLE_LNG }, map, VEHICLE_ICON);
        setInterval(pollVehicleLocation, 4000);
    }
}

/* ─────────────────────────────────────────────────────────
   Static start / destination markers
───────────────────────────────────────────────────────── */
function placeStaticMarkers() {
    new google.maps.Marker({
        position: { lat: START_LAT, lng: START_LNG },
        map: map,
        icon: 'https://maps.google.com/mapfiles/ms/icons/green-dot.png',
        title: 'Pick-up point',
        label: { text: 'S', color: '#fff', fontWeight: 'bold' }
    });

    DESTINATIONS.forEach(function(dest, idx) {
        new google.maps.Marker({
            position: { lat: dest.lat, lng: dest.lng },
            map: map,
            icon: 'https://maps.google.com/mapfiles/ms/icons/red-dot.png',
            title: dest.address,
            label: { text: String(idx + 1), color: '#fff', fontWeight: 'bold' }
        });
    });
}

/* ─────────────────────────────────────────────────────────
   Route drawing
   – completed / scheduled : full route in one colour
   – in_progress            : passed (pink) + remaining (blue)
   – pending                : full route blue (driver en-route to client)
   – cancelled              : full route red
───────────────────────────────────────────────────────── */
function drawRoutes(vehiclePos) {
    // Clear old renderers
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
        var nearestIdx   = findNearestDestinationIndex(vehiclePos);
        var vLatLng      = new google.maps.LatLng(vehiclePos.lat, vehiclePos.lng);

        var passedWP = DESTINATIONS.slice(0, nearestIdx).map(function(d) {
            return { location: new google.maps.LatLng(d.lat, d.lng), stopover: true };
        });
        var remainingWP = DESTINATIONS.slice(nearestIdx + 1, -1).map(function(d) {
            return { location: new google.maps.LatLng(d.lat, d.lng), stopover: true };
        });

        // Passed segment — pink/purple
        if (nearestIdx > 0) {
            drawSegment(start, vLatLng, '#e040fb', passedWP, false);
        }
        // Remaining segment — blue
        drawSegment(vLatLng, final, '#1565c0', remainingWP, true);

    } else if (TRIP_STATUS === 'completed') {
        drawSegment(start, final, '#e040fb', allWaypoints, false);

    } else if (TRIP_STATUS === 'pending') {
        // Show vehicle → pickup in orange, then pickup → destination in blue
        var vehicleLatLng = new google.maps.LatLng(vehiclePos.lat, vehiclePos.lng);
        drawSegment(vehicleLatLng, start, '#ff8f00', [], false);   // driver heading to client
        drawSegment(start, final, '#1565c0', allWaypoints, false); // planned route

    } else if (TRIP_STATUS === 'cancelled') {
        drawSegment(start, final, '#c62828', allWaypoints, false);

    } else {
        // created / scheduled
        drawSegment(start, final, '#2e7d32', allWaypoints, true);
    }
}

function drawSegment(origin, destination, color, waypoints, showDistanceLabel) {
    directionsService.route({
        origin: origin,
        destination: destination,
        waypoints: waypoints,
        optimizeWaypoints: false,
        travelMode: google.maps.TravelMode.DRIVING,
    }, function(result, status) {
        if (status !== 'OK') return;

        var renderer = new google.maps.DirectionsRenderer({
            map: map,
            suppressMarkers: true,
            polylineOptions: { strokeColor: color, strokeWeight: 5, strokeOpacity: .85 },
        });
        renderer.setDirections(result);
        routeRenderers.push(renderer);

        // Place distance label on the main/final segment
        if (showDistanceLabel && TRIP_STATUS !== 'cancelled') {
            var path     = result.routes[0].overview_path;
            var midpoint = path[Math.floor(path.length / 2)];
            if (distanceLabel) {
                distanceLabel.setPosition(midpoint);
            } else {
                distanceLabel = new google.maps.InfoWindow({
                    content: '<div style="color:#000;font-weight:bold;font-size:13px;">{{ $trip->distance ?? "" }} km</div>',
                    position: midpoint,
                    pixelOffset: new google.maps.Size(0, -10),
                });
                distanceLabel.open(map);
            }
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
            var newPos = { lat: parseFloat(data.lat), lng: parseFloat(data.lng) };

            if (newPos.lat === previousLocation.lat && newPos.lng === previousLocation.lng) return;

            var bearing = computeBearing(previousLocation, newPos);
            movingMarker.setPosition(newPos);
            movingMarker.setRotation(bearing);
            previousLocation = newPos;

            drawRoutes(newPos);
        })
        .catch(function(e) { console.warn('Vehicle location error:', e); });
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

/* ─────────────────────────────────────────────────────────
   Custom rotating overlay marker
───────────────────────────────────────────────────────── */
function RotatingMarker(position, map, imageUrl) {
    this.pos      = new google.maps.LatLng(position.lat, position.lng);
    this.rotation = 0;
    this.imageUrl = imageUrl;
    this.div      = null;
    this.setMap(map);
}
RotatingMarker.prototype = new google.maps.OverlayView();

RotatingMarker.prototype.onAdd = function() {
    var div = document.createElement('div');
    div.style.cssText = 'position:absolute;width:48px;height:38px;background-size:contain;background-repeat:no-repeat;background-position:center;';
    div.style.backgroundImage = 'url(' + this.imageUrl + ')';
    this.div = div;
    this.getPanes().overlayLayer.appendChild(div);
};

RotatingMarker.prototype.draw = function() {
    if (!this.div) return;
    var proj = this.getProjection();
    var pt   = proj.fromLatLngToDivPixel(this.pos);
    this.div.style.left      = (pt.x - 24) + 'px';
    this.div.style.top       = (pt.y - 19) + 'px';
    this.div.style.transform = 'rotate(' + this.rotation + 'deg)';
};

RotatingMarker.prototype.setPosition = function(pos) {
    this.pos = new google.maps.LatLng(pos.lat, pos.lng);
    this.draw();
};

RotatingMarker.prototype.setRotation = function(deg) {
    this.rotation = deg;
    this.draw();
};

window.onload = initMap;
</script>
@endpush

