@extends('dashboard.layout.app')
@section('title', 'Dashboard - edit car')
@section('content')	
<style>
    .star-rating {
        color: #FFf; /* Default star color */
    }

    .star {
        font-size: 24px; /* Adjust the size of the stars */
    }

    .filled {
        color: gold; /* Filled star color */
    }
    .car-link:hover {
        color: blue; /* Text color on hover */
    }
</style>
    <div class="content-wrapper">
        <div class="container-fluid">

            <div class="row mt-3">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-body">
                            <div class="card-title">Trip Code : {{$trip->code}}</div>
                            <hr>
                            
                            <div id="map" style="height: 800px; margin: 20px 0px 20px 0px;"></div>
                            <div class="form-group" style="display: flex">
                                <div style="width:50%">
                                    <div class="form-group">
                                        <label>Client : <a href="{{url('/admin-dashboard/user/edit/'.$trip->user->id)}}"> <span class="user-profile"><img @if(getFirstMediaUrl($trip->user,$trip->user->avatarCollection)!=null) src="{{getFirstMediaUrl($trip->user,$trip->user->avatarCollection)}}" @else src="{{asset('dashboard/user_avatar.png')}}" @endif class="img-circle" alt="user avatar" style="width: 22px;height: 22px;"></span> {{ucwords($trip->user->name)}}</a></label>
                                    </div>
                                    <div class="form-group">
                                        <label>Driver : <a href="{{url('/admin-dashboard/user/edit/'.$trip->car->owner->id)}}"> <span class="user-profile"><img @if(getFirstMediaUrl($trip->car->owner,$trip->car->owner->avatarCollection)!=null) src="{{getFirstMediaUrl($trip->car->owner,$trip->car->owner->avatarCollection)}}" @else src="{{asset('dashboard/user_avatar.png')}}" @endif class="img-circle" alt="user avatar"style="width: 22px;height: 22px;"></span> {{ucwords($trip->car->owner->name)}}</a></label>
                                    </div>
                                    <div class="form-group">
                                        <label>Created at : {{date('Y/m/d h:i a',strtotime($trip->created_at))}}</label>
                                    </div>
                                    <div class="form-group">
                                        <label>Type : {{$trip->type}}</label>
                                    </div>
                                    <div class="form-group">
                                        <label>Air Conditioned Status : {!! $trip->air_conditioned?'<span class="badge badge-secondary" style="background-color:rgb(28, 161, 34);">Air conditioned</span>':'<span class="badge badge-secondary" style="background-color:rgb(255,0,0);">Not air conditioned</span>' !!}</label>
                                    </div>
                                </div>
                                <div style="width:50% ;">
                                    <div class="form-group">
                                        <label>From : {{$trip->address1}}</label>
                                    </div>
                                    <div class="form-group">
                                        <label>To : {{$trip->address2}}</label>
                                    </div>
                                    <div class="form-group">
                                        <label>Start At : {{$trip->start_date}} {{date('h:i a',strtotime($trip->start_time))}}</label>
                                    </div>
                                    <div class="form-group">
                                        <label>End At : {{$trip->end_date}} {{date('h:i a',strtotime($trip->end_time))}}</label>
                                    </div>
                                    <div class="form-group">
                                        <label>Trip Status : @if($trip->status=='pending') <span class="badge badge-secondary" style="background-color:rgb(143, 118, 9);">Pending</span> @elseif($trip->status=='completed') <span class="badge badge-secondary" style="background-color:rgb(50, 134, 50);">Completed</span> @elseif($trip->status=='in_progress') <span class="badge badge-secondary" style="background-color:rgb(52, 40, 223);">In Progress</span> @else<span class="badge badge-secondary" style="background-color:rgb(255,0,0);">Cancelled</span> @endif</label>
                                    </div>
                                </div>
                                
                            </div>
                            <div class="form-group" style="display: flex; align-items: center;">
                                <h4 style="margin-right: 10px;">Car</h4>
                                <hr style="flex: 1; margin: 0;">
                            </div>
                            <div class="form-group"style="text-align: center;">
                                <div>
                                  <img style="border-radius: 2%;width:60%;" @if($trip->car->image!=null) src="{{$trip->car->image}}" @else src="{{asset('dashboard/car_avatar.png')}}" @endif class="img-circle" alt="user avatar">
                                </div>
                                <div style="width: 50%;margin-left:25%">
                                    <a href="{{url('/admin-dashboard/car/edit/'.$trip->car->id)}}"style="text-align:center;">
                                        <h3 style="margin-top:10px;"class="car-link">{{$trip->car->mark->en_name}} - {{$trip->car->mark->ar_name}}</h3>
                                        <h3 style="margin-top:10px;"class="car-link">{{$trip->car->model->en_name}} - {{$trip->car->model->ar_name}} ({{$trip->car->year}})</h3>
                                    </a>
                                </div>
                                
                               
                            </div>
                            <div class="form-group" style="display: flex; align-items: center;">
                                <h4 style="margin-right: 10px;">Payment</h4>
                                <hr style="flex: 1; margin: 0;">
                            </div>
                            <div class="form-group">
                                <label>Payment Status : {{$trip->payment_status}}</label>
                            </div>
                            <div class="form-group">
                                <label>Distance : {{$trip->distance}} KM</label>
                            </div>
                            <div class="form-group">
                                <label>Driver ratio : {{$trip->driver_rate}} LE</label>
                            </div>
                            <div class="form-group">
                                <label>Application ratio : {{$trip->app_rate}} LE</label>
                            </div>
                            <div class="form-group">
                                <label>Total Price : {{$trip->total_price}} LE</label>
                            </div>
                            @if($trip->cancelled_by_id!=null)
                            <div class="form-group" style="display: flex; align-items: center;">
                                <h4 style="margin-right: 10px;">Cancellation</h4>
                                <hr style="flex: 1; margin: 0;">
                            </div>
                            <div class="form-group">
                                <label>Cancelled By : @if($trip->cancelled_by_id==$trip->user_id) Client @else Driver @endif</label>
                            </div>
                            <div class="form-group">
                                <label>Cancelled Resion : <span style="text-transform: none;">bla bla bla</span></label>
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
                                        $starClass = ($i <= $clientEvaluation) ? "filled" : "empty";
                                        echo '<span class="star ' . $starClass . '">&#9733;</span>'; // Unicode character for a star
                                    }
                                    ?>
                                </div>
                            </div>
                            <div class="form-group">
                                <label>Client Comment : <span style="text-transform: none;">{{$trip->client_comment}}</span></label>
                            </div>
                            <div class="form-group" style="display: flex;align-items: center;">
                                <label>Drivel evaluation : </label>
                                <div class="star-rating" style="margin-bottom: 10px;">
                                    <?php
                                    $driverEvaluation = $trip->driver_stare_rate; // Assuming $trip->client_evaluation holds the evaluation score (1 to 5)
                                    
                                    // Loop to generate stars based on the client evaluation score
                                    for ($i = 1; $i <= 5; $i++) {
                                        $starClass2 = ($i <= $driverEvaluation) ? "filled" : "empty";
                                        echo '<span class="star ' . $starClass2 . '">&#9733;</span>'; // Unicode character for a star
                                    }
                                    ?>
                                </div>
                            </div>
                            <div class="form-group">
                                <label>Driver Comment : <span style="text-transform: none;">{{$trip->driver_comment}}</span></label>
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
<script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyATC_r7Y-U6Th1RQLHWJv2JcufJb-x2VJ0&libraries=places"></script>

{{-- <script>
  var map, directionsService, directionsRenderer, marker, previousLocation;

  function initMap() {
    var carLocation = { lat: {{ $trip->car->lat }}, lng: {{ $trip->car->lng }} };
      previousLocation = carLocation;
      // Initialize the map centered at the start location
      map = new google.maps.Map(document.getElementById('map'), {
          zoom: 12,
          center: { lat: {{ $trip->start_lat }}, lng: {{ $trip->start_lng }} }
      });

      // Initialize Directions Service and Renderer
      directionsService = new google.maps.DirectionsService();
      directionsRenderer = new google.maps.DirectionsRenderer({
          map: map,
          suppressMarkers: true, // Suppress default markers if you want custom ones
          polylineOptions: {
              strokeColor: '#0000FF', // Blue color for the path
              strokeOpacity: 1.0,
              strokeWeight: 4
          }
      });

      // Calculate and display the route
      calculateRoute();
      marker = new RotatingMarker(carLocation, map, '{{ asset("dashboard/Travel-car-topview.svg.png") }}');
      
      // Start updating the car's location every 3 seconds
      setInterval(updateCarLocation, 20000);
  }

  function calculateRoute() {
      var startLocation = new google.maps.LatLng({{ $trip->start_lat }}, {{ $trip->start_lng }});
      var endLocation = new google.maps.LatLng({{ $trip->end_lat }}, {{ $trip->end_lng }});

      var request = {
          origin: startLocation,
          destination: endLocation,
          travelMode: google.maps.TravelMode.DRIVING // Change travel mode if needed
      };

      directionsService.route(request, function(result, status) {
          if (status == google.maps.DirectionsStatus.OK) {
              directionsRenderer.setDirections(result);
              // Optionally, place custom markers at the start and end points
              placeMarkers(startLocation, endLocation);
          } else {
              console.error('Directions request failed due to ' + status);
          }
      });
  }

  function placeMarkers(start, end) {
      // Custom markers for start and end locations (optional)
      new google.maps.Marker({
          position: start,
          map: map,
          title: 'Start Location'
      });

      new google.maps.Marker({
          position: end,
          map: map,
          title: 'End Location'
      });
  }
  function updateCarLocation() {
      // Fetch the updated car location using AJAX
      fetch('/admin-dashboard/car-location/{{ $trip->car->id }}')
        .then(response => response.json())
        .then(data => {
            var newLocation = { lat: data.lat, lng: data.lng };

            // Check if the new location is different from the previous location
            
            if (newLocation.lat === previousLocation.lat && newLocation.lng === previousLocation.lng) {
                // If locations are the same, do nothing
                
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
      this.div.style.width = '40px';
      this.div.style.height = '35px';
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
  // Initialize the map on page load
  window.onload = initMap;
</script> --}}

<script>
    var map, directionsService, segmentRenderer1, segmentRenderer2, marker, previousLocation,distanceLabel;
  
    function initMap() {
      var carLocation = { lat: {{ $trip->car->lat }}, lng: {{ $trip->car->lng }} };
        previousLocation = carLocation;
        // Initialize the map centered at the start location
        map = new google.maps.Map(document.getElementById('map'), {
            zoom: 12,
            center: { lat: {{ $trip->start_lat }}, lng: {{ $trip->start_lng }} }
        });
  
        // Initialize Directions Service and Renderer
        directionsService = new google.maps.DirectionsService();
        
  
        // Calculate and display the route
        calculateRoute();
        var startLocation = new google.maps.LatLng({{ $trip->start_lat }}, {{ $trip->start_lng }});
        var endLocation = new google.maps.LatLng({{ $trip->end_lat }}, {{ $trip->end_lng }});
        placeMarkers(startLocation, endLocation);
        if ("{{ $trip->status }}" === "in_progress" || "{{ $trip->status }}" === "pending") {
            marker = new RotatingMarker(carLocation, map, '{{ asset("dashboard/Travel-car-topview.svg.png") }}');
            
            // Start updating the car's location every 3 seconds
            setInterval(updateCarLocation, 3000);
        }
        if ("{{ $trip->status }}" !== "cancelled") {
            addDistanceLabel(map, '{{ $trip->distance }} km');
        }
    }
    
    function calculateRoute() {
      var startLocation = new google.maps.LatLng({{ $trip->start_lat }}, {{ $trip->start_lng }});
      var carLocation = new google.maps.LatLng({{ $trip->car->lat }}, {{ $trip->car->lng }});
      var endLocation = new google.maps.LatLng({{ $trip->end_lat }}, {{ $trip->end_lng }});
      
      var tripStatus = "{{ $trip->status }}";

      if (tripStatus === "in_progress") {
          // Segment 1: Start to Car (Green)
          drawRoute(startLocation, carLocation, '#00FF00'); // Green for start to car
          // Segment 2: Car to End (Blue)
          drawRoute(carLocation, endLocation, '#0000FF'); // Blue for car to end
      } else if (tripStatus === "completed") {
          // Completed: All Green from Start to End
          drawRoute(startLocation, endLocation, '#00FF00'); // Green for completed
      } else if (tripStatus === "pending") {
          // Pending: All Blue from Start to End
          drawRoute(startLocation, endLocation, '#0000FF'); // Blue for pending
      } else if (tripStatus === "cancelled") {
          // Cancelled: All Red from Start to End
          drawRoute(startLocation, endLocation, '#FF0000'); // Red for cancelled
      }
    }
    function placeMarkers(start, end) {
      // Custom markers for start and end locations (optional)
      new google.maps.Marker({
          position: start,
          map: map,
          title: 'Start Location'
      });

      new google.maps.Marker({
          position: end,
          map: map,
          title: 'End Location'
      });
    }
    // Helper function to draw a specific route segment with a specified color
    function drawRoute(origin, destination, color) {
      var request = {
          origin: origin,
          destination: destination,
          travelMode: google.maps.TravelMode.DRIVING
      };

      directionsService.route(request, function(result, status) {
          if (status == google.maps.DirectionsStatus.OK) {
              var directionsRenderer = new google.maps.DirectionsRenderer({
                  map: map,
                  suppressMarkers: true,
                  polylineOptions: {
                      strokeColor: color, // Use the passed color
                      strokeOpacity: 1.0,
                      strokeWeight: 4
                  }
              });
              directionsRenderer.setDirections(result);
              var path = result.routes[0].overview_path;
                var midpoint = path[Math.floor(path.length / 2)];
                if (distanceLabel) {
                    distanceLabel.setPosition(midpoint);
                }
          } else {
              console.error('Directions request failed due to ' + status);
          }
      });
    }

    function updateCarLocation() {
      // Fetch the updated car location using AJAX
      fetch('/admin-dashboard/car-location/{{ $trip->car->id }}')
        .then(response => response.json())
        .then(data => {
            var newLocation = { lat: data.lat, lng: data.lng };

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
                position: map.getCenter(), // Default to map center; can be updated later to the midpoint of the route
                pixelOffset: new google.maps.Size(0, -10)
            });

        distanceLabel.open(map);
    }
    // Initialize the map on page load
    window.onload = initMap;
</script>
@endpush
