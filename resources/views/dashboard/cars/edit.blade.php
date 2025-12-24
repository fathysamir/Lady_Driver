@extends('dashboard.layout.app')
@section('title', 'Dashboard - edit car')
@section('content')
<style>
    .zoomable-image {
        cursor: pointer;
        transition: transform 0.3s ease, box-shadow 0.3s ease;
    }

    .zoomable-image:hover {
        transform: scale(1.05);
        box-shadow: 0 8px 20px rgba(0, 0, 0, 0.3);
    }

    .image-preview {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.95);
        z-index: 9999;
        justify-content: center;
        align-items: center;
        opacity: 0;
        transition: opacity 0.3s ease;
    }

    .image-preview.show {
        display: flex;
        opacity: 1;
    }

    .preview-container {
        position: relative;
        max-width: 90vw;
        max-height: 90vh;
        display: flex;
        justify-content: center;
        align-items: center;
    }

    .image-preview img {
        max-width: 90vw;
        max-height: 90vh;
        border-radius: 8px;
        cursor: grab;
        transition: transform 0.1s ease;
        transform-origin: center center;
        user-select: none;
    }

    .image-preview img:active {
        cursor: grabbing;
    }

    .preview-controls {
        position: fixed;
        top: 20px;
        right: 20px;
        display: flex;
        gap: 10px;
        z-index: 10001;
    }

    .control-btn {
        background: rgba(255, 255, 255, 0.9);
        border: none;
        width: 45px;
        height: 45px;
        border-radius: 50%;
        cursor: pointer;
        font-size: 20px;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.2s ease;
        color: #333;
        font-weight: bold;
    }

    .control-btn:hover {
        background: white;
        transform: scale(1.1);
    }

    .close-btn {
        background: rgba(255, 59, 48, 0.9) !important;
        color: white !important;
    }

    .close-btn:hover {
        background: rgb(255, 59, 48) !important;
    }

    .zoom-indicator {
        position: fixed;
        bottom: 30px;
        left: 50%;
        transform: translateX(-50%);
        background: rgba(255, 255, 255, 0.9);
        padding: 10px 20px;
        border-radius: 25px;
        font-size: 16px;
        color: #333;
        font-weight: bold;
        pointer-events: none;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
        z-index: 10001;
    }
</style>

    <div class="content-wrapper">
        <div class="container-fluid">

            <div class="row mt-3">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-body">
                            <div class="card-title">Client Car</div>
                            <hr>

                                <div class="form-group"style="text-align: center;">
                                    <div>
                                        <img style="border-radius: 2%;width:60%;"
                                            @if ($car->image != null) src="{{ $car->image }}" @else src="{{ asset('dashboard/car_avatar.png') }}" @endif
                                            class="img-circle zoomable-image" alt="user avatar">
                                    </div>
                                    <h3 style="margin-top:10px;">{{ $car->mark->en_name }} - {{ $car->mark->ar_name }}</h3>
                                    <h3 style="margin-top:10px;">{{ $car->model->en_name }} - {{ $car->model->ar_name }}
                                        ({{ $car->year }})</h3>
                                </div>

                                <div class="form-group">
                                    <label>Driver : <a
                                            href="{{ url('/admin-dashboard/driver/edit/' . $car->owner->id) }}">{{ ucwords($car->owner->name) }}</a></label>
                                </div>
                                <div class="form-group">
                                    <label>Car Plate : {{ $car->car_plate }}</label>
                                </div>
                                <div class="form-group">
                                    <label>Car Color : {{ $car->color }}</label>
                                </div>
                                <div class="form-group">
                                    <label>License Expire Date : {{ $car->license_expire_date }} </label>
                                    @if ($car->license_expire_date < date('Y-m-d'))
                                        <span class="badge badge-secondary"
                                            style="background-color:rgb(255,0,0);width:10%; margin-left:1%;">Expired</span>
                                    @endif
                                </div>
                                <div id="map" style="height: 800px; margin: 20px 0px 20px 0px;"></div>
                                <div class="form-group" style="display: flex; align-items: center;">
                                    <h4 style="margin-right: 10px;">Images</h4>
                                    <hr style="flex: 1; margin: 0;">
                                </div>

                                <div class="form-group"style="display: flex;">
                                    <label style="width: 20%">Image : </label> <img
                                        style="margin: 0px 10px 0px 10px; border-radius:10px;width:30%"
                                        src="{{ $car->image }}" class="zoomable-image">
                                </div>
                                <div class="form-group"style="display: flex;">
                                    <label style="width: 20%">Plate Image : </label> <img
                                        style="margin: 0px 10px 0px 10px; border-radius:10px;width:30%;"
                                        src="{{ $car->plate_image }}" class="zoomable-image">
                                </div>
                                <div class="form-group"style="display: flex;">
                                    <label style="width: 20%">License Front Image : </label> <img
                                        style="margin: 0px 10px 0px 10px; border-radius:10px;width:30%;"
                                        src="{{ $car->license_front_image }}" class="zoomable-image">
                                </div>
                                <div class="form-group"style="display: flex;">
                                    <label style="width: 20%">License Back Image : </label> <img
                                        style="margin: 0px 10px 0px 10px; border-radius:10px;width:30%;"
                                        src="{{ $car->license_back_image }}" class="zoomable-image">
                                </div>
                                <div class="form-group">
                                    <label>Status</label>

                                    <select disabled class="form-control" name="status">
                                        <option value="pending" @if ($car->status == 'pending') selected @endif>Pending
                                        </option>
                                        <option value="confirmed" @if ($car->status == 'confirmed') selected @endif>
                                            Confirmed</option>
                                        <option value="blocked" @if ($car->status == 'blocked') selected @endif>Blocked
                                        </option>
                                    </select>
                                </div>


                        </div>
                    </div>
                </div>
            </div>

            <!-- Image Preview Popup -->
            <div class="image-preview">
                <div class="preview-container">
                    <div class="preview-controls">
                        <button type="button" class="btn btn-light px-3 zoom-in-btn" title="Zoom In">+</button>
                        <button type="button" class="btn btn-light px-3 zoom-out-btn" title="Zoom Out">-</button>
                        <button type="button" class="btn btn-light px-3 close-btn" title="Close">✕</button>
                    </div>
                    <img src="" alt="Image Preview">
                    <div class="zoom-indicator">100%</div>
                </div>
            </div>

            <div class="overlay toggle-menu"></div>
        </div>
    </div>
@endsection
@push('scripts')
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyATC_r7Y-U6Th1RQLHWJv2JcufJb-x2VJ0"></script>
    {{-- <script>
  var map, marker,carIcon,previousLocation;

  function initMap() {
      // Initial location of the car
      var userLocation = { lat: {{ $car->lat }}, lng: {{ $car->lng }} };
      previousLocation = userLocation;
      map = new google.maps.Map(document.getElementById('map'), {
          zoom: 12,
          center: userLocation
      });
       carIcon = {
          url: '{{ asset("dashboard/Travel-car-topview.svg.png") }}', // Path to your custom car image
          scaledSize: new google.maps.Size(35, 20), // Scale the image to a desired size (optional)
          origin: new google.maps.Point(0, 0), // Origin of the image (optional)
          anchor: new google.maps.Point(25, 25), // Anchor point of the image (optional)
      };
      // Create a marker at the car's initial position
      marker = new google.maps.Marker({
          position: userLocation,
          map: map,
          icon: carIcon,
          rotation: 0
      });
      // google.maps.event.addListener(map, 'zoom_changed', function() {
      //     var zoomLevel = map.getZoom();
      //     updateIconSize(zoomLevel);
      // });
      // Start updating the car's location every 5 seconds
      setInterval(updateCarLocation, 3000);
  }
  // function updateIconSize(zoomLevel) {
  //     var newSize;

  //     // Adjust the size based on the zoom level
  //     if (zoomLevel > 15) {
  //         newSize = new google.maps.Size(50, 30);  // Medium size
  //     } else {
  //         newSize = new google.maps.Size(35, 20);  // Smaller size for distant zoom
  //     }

  //     // Update the marker icon size dynamically
  //     marker.setIcon({
  //         url: carIcon.url,   // Keep the same icon URL
  //         scaledSize: newSize, // Update the size
  //         origin: carIcon.origin,  // Keep the same origin
  //         anchor: carIcon.anchor   // Keep the same anchor
  //     });
  // }
  function updateCarLocation() {

      fetch('/admin-dashboard/car-location/{{ $car->id }}')
        .then(response => response.json())
        .then(data => {
            var newLocation = { lat: data.lat, lng: data.lng };
            var rotationAngle = calculateBearing(previousLocation, newLocation);
            // Move the marker to the new location
            marker.setPosition(newLocation);
            rotateMarker(rotationAngle);
            previousLocation = newLocation;
            // Optionally, center the map on the new location
            //map.setCenter(newLocation);
        })
        .catch(error => console.error('Error fetching car location:', error));
  }
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

  // Function to rotate the marker to face the direction of movement
  function rotateMarker(angle) {
    console.log(angle);

      marker.setIcon({
          url: carIcon.url,
          scaledSize: carIcon.scaledSize,
          origin: carIcon.origin,
          anchor: carIcon.anchor,
          rotation: angle // Apply the rotation angle
      });
  }

  // Helper functions to convert between degrees and radians
  function degreesToRadians(degrees) {
      return degrees * (Math.PI / 180);
  }

  function radiansToDegrees(radians) {
      return radians * (180 / Math.PI);
  }
  // Initialize the map
  window.onload = initMap;
</script> --}}
    <script>
        var map, marker, previousLocation;

        function initMap() {
            // Initial location of the car
            var userLocation = {
                lat: {{ $car->lat }},
                lng: {{ $car->lng }}
            };
            previousLocation = userLocation; // Store the initial location

            map = new google.maps.Map(document.getElementById('map'), {
                zoom: 12,
                center: userLocation
            });

            // Create a custom overlay for the car icon
            marker = new RotatingMarker(userLocation, map, '{{ asset('dashboard/Travel-car-topview.svg.png') }}');

            // Start updating the car's location every 3 seconds
            setInterval(updateCarLocation, 3000);
        }

        function updateCarLocation() {
            // Fetch the updated car location using AJAX
            fetch('/admin-dashboard/car-location/{{ $car->id }}')
                .then(response => response.json())
                .then(data => {
                    var newLocation = {
                        lat: data.lat,
                        lng: data.lng
                    };

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
            this.div.style.width = '50px';
            this.div.style.height = '50px';
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

        // Initialize the map
        window.onload = initMap;
    </script>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            // Image zoom functionality
            const zoomableImages = document.querySelectorAll('.zoomable-image');
            const imagePreview = document.querySelector('.image-preview');
            const previewImg = imagePreview.querySelector('img');
            const zoomInBtn = imagePreview.querySelector('.zoom-in-btn');
            const zoomOutBtn = imagePreview.querySelector('.zoom-out-btn');
            const closeBtn = imagePreview.querySelector('.close-btn');
            const zoomIndicator = imagePreview.querySelector('.zoom-indicator');

            let scale = 1;
            let posX = 0;
            let posY = 0;
            let isDragging = false;
            let startX, startY;

            function updateTransform() {
                previewImg.style.transform = `translate(${posX}px, ${posY}px) scale(${scale})`;
                zoomIndicator.textContent = `${Math.round(scale * 100)}%`;
            }

            function resetPosition() {
                posX = 0;
                posY = 0;
            }

            function openPreview(imageSrc) {
                previewImg.src = imageSrc;
                imagePreview.classList.add('show');
                document.body.style.overflow = 'hidden';
            }

            function closePreview() {
                imagePreview.classList.remove('show');
                document.body.style.overflow = '';
                scale = 1;
                resetPosition();
                updateTransform();
            }

            zoomableImages.forEach(img => {
                img.addEventListener('click', function(e) {
                    e.preventDefault();
                    openPreview(this.src);
                });
            });

            closeBtn.addEventListener('click', function(e) {
                e.stopPropagation();
                closePreview();
            });

            zoomInBtn.addEventListener('click', function(e) {
                e.stopPropagation();
                scale += 0.2;
                if (scale > 5) scale = 5;
                updateTransform();
            });

            zoomOutBtn.addEventListener('click', function(e) {
                e.stopPropagation();
                scale -= 0.2;
                if (scale < 1) {
                    scale = 1;
                    resetPosition();
                }
                updateTransform();
            });

            previewImg.addEventListener('wheel', function(e) {
                e.preventDefault();
                scale += e.deltaY < 0 ? 0.1 : -0.1;
                if (scale < 1) {
                    scale = 1;
                    resetPosition();
                } else if (scale > 5) {
                    scale = 5;
                }
                updateTransform();
            });

            previewImg.addEventListener('mousedown', function(e) {
                if (scale <= 1) return;
                isDragging = true;
                startX = e.clientX - posX;
                startY = e.clientY - posY;
                previewImg.style.cursor = 'grabbing';
                e.preventDefault();
            });

            document.addEventListener('mousemove', function(e) {
                if (!isDragging || scale <= 1) return;
                posX = e.clientX - startX;
                posY = e.clientY - startY;
                updateTransform();
            });

            document.addEventListener('mouseup', function() {
                if (isDragging) {
                    isDragging = false;
                    previewImg.style.cursor = 'grab';
                }
            });

            previewImg.addEventListener('dragstart', function(e) {
                e.preventDefault();
            });

            imagePreview.addEventListener('click', function(e) {
                if (e.target === imagePreview) {
                    closePreview();
                }
            });

            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape' && imagePreview.classList.contains('show')) {
                    closePreview();
                }
            });
        });
    </script>
@endpush