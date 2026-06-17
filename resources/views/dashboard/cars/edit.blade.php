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

    /* ── Photo Edit Wrapper ── */
    .photo-edit-wrapper {
        position: relative;
        display: inline-block;
    }

    .photo-edit-wrapper .edit-photo-btn {
        position: absolute;
        bottom: 6px; right: 6px;
        background: rgba(0,0,0,0.65);
        color: #fff;
        border: none;
        border-radius: 50%;
        width: 32px; height: 32px;
        font-size: 15px;
        cursor: pointer;
        display: flex; align-items: center; justify-content: center;
        transition: background 0.2s;
        z-index: 2;
    }
    .photo-edit-wrapper .edit-photo-btn:hover { background: rgba(255,200,0,0.85); }

    .new-img-badge {
        display: none;
        position: absolute;
        top: 6px; left: 6px;
        background: #28a745;
        color: #fff;
        font-size: 11px;
        padding: 2px 7px;
        border-radius: 10px;
        font-weight: bold;
        z-index: 3;
    }
    .photo-edit-wrapper.has-new .new-img-badge { display: block; }
    .photo-edit-wrapper.has-new img { box-shadow: 0 0 0 3px #28a745; border-radius: 8px; }
</style>

    <div class="content-wrapper">
        <div class="container-fluid">

            <div class="row mt-3">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-body">
                            <div class="card-title">Driver Car</div>
                            <hr>

                            <form method="post" action="{{ route('update.car', ['id' => $car->id]) }}" enctype="multipart/form-data">
                                @csrf
                                @method('PUT')

                                <div class="form-group" style="text-align: center;">
                                    <div style="display:inline-block;">
                                        <img id="preview-car-image"
                                             style="border-radius:2%; width:60%;"
                                             src="{{ $car->image ?? asset('dashboard/car_avatar.png') }}"
                                             onerror="this.onerror=null;this.src='{{ asset('dashboard/car_avatar.png') }}';"
                                             class="img-circle zoomable-image" alt="car image">
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
                                    <label style="font-size: 1.4rem;">Car Plate : {{ $car->car_plate }}</label>
                                </div>
                                <div class="form-group">
                                    <label>Car Color : {{ $car->color }}</label>
                                </div>
                                <div class="form-group">
                                    <label>License Expire Date :
                                        <span style="color: {{ \Carbon\Carbon::parse($car->license_expire_date)->isFuture() ? '#56ec60' : '#ff5f5f' }}">
                                            {{ \Carbon\Carbon::parse($car->license_expire_date)->format('d M Y') }}
                                        </span>
                                    </label>
                                </div>

                                <div id="map" style="height: 800px; margin: 20px 0px 20px 0px;"></div>

                                <div class="form-group" style="display: flex; align-items: center;">
                                    <h4 style="margin-right: 10px;">Images</h4>
                                    <hr style="flex: 1; margin: 0;">
                                </div>

                                {{-- Car Image --}}
                                <div class="form-group" style="display:flex; align-items:flex-start; flex-wrap:wrap; gap:10px;">
                                    <label style="width:20%; margin-top:6px;">Image :</label>
                                    <div class="photo-edit-wrapper" id="wrapper-car-image2">
                                        <img id="preview-car-image2"
                                             style="margin:0 10px; border-radius:10px; width:300px;"
                                             src="{{ $car->image }}"
                                             onerror="this.onerror=null;this.src='{{ asset('dashboard/image_placeholder.png') }}';"
                                             class="zoomable-image">
                                             @if ($car->image)
                                             <span class="new-img-badge">NEW</span>
                                             <button type="button" class="edit-photo-btn" title="Replace image"
                                                     onclick="document.getElementById('input-car-image2').click()">✎</button>
                                             <input type="file" id="input-car-image2" name="car_image" accept="image/*"
                                                    style="display:none" data-wrapper="wrapper-car-image2" data-preview="preview-car-image2">
                                             @endif
                                    </div>
                                </div>

                                {{-- Plate Image --}}
                                <div class="form-group" style="display:flex; align-items:flex-start; flex-wrap:wrap; gap:10px;">
                                    <label style="width:20%; margin-top:6px;">Plate Image :</label>
                                    <div class="photo-edit-wrapper" id="wrapper-plate-image">
                                        <img id="preview-plate-image"
                                             style="margin:0 10px; border-radius:10px; width:300px;"
                                             src="{{ $car->plate_image }}"
                                             onerror="this.onerror=null;this.src='{{ asset('dashboard/image_placeholder.png') }}';"
                                             class="zoomable-image">
                                             @if ($car->plate_image)
                                             <span class="new-img-badge">NEW</span>
                                             <button type="button" class="edit-photo-btn" title="Replace image"
                                                     onclick="document.getElementById('input-plate-image').click()">✎</button>
                                             <input type="file" id="input-plate-image" name="plate_image" accept="image/*"
                                                    style="display:none" data-wrapper="wrapper-plate-image" data-preview="preview-plate-image">
                                             @endif
                                    </div>
                                </div>

                                {{-- License Front Image --}}
                                <div class="form-group" style="display:flex; align-items:flex-start; flex-wrap:wrap; gap:10px;">
                                    <label style="width:20%; margin-top:6px;">License Front Image :</label>
                                    <div class="photo-edit-wrapper" id="wrapper-lic-front">
                                        <img id="preview-lic-front"
                                             style="margin:0 10px; border-radius:10px; width:300px;"
                                             src="{{ $car->license_front_image }}"
                                             onerror="this.onerror=null;this.src='{{ asset('dashboard/image_placeholder.png') }}';"
                                             class="zoomable-image">
                                             @if ($car->license_front_image)
                                             <span class="new-img-badge">NEW</span>
                                             <button type="button" class="edit-photo-btn" title="Replace image"
                                                     onclick="document.getElementById('input-lic-front').click()">✎</button>
                                             <input type="file" id="input-lic-front" name="license_front_image" accept="image/*"
                                                    style="display:none" data-wrapper="wrapper-lic-front" data-preview="preview-lic-front">
                                             @endif
                                    </div>
                                </div>

                                {{-- License Back Image --}}
                                <div class="form-group" style="display:flex; align-items:flex-start; flex-wrap:wrap; gap:10px;">
                                    <label style="width:20%; margin-top:6px;">License Back Image :</label>
                                    <div class="photo-edit-wrapper" id="wrapper-lic-back">
                                        <img id="preview-lic-back"
                                             style="margin:0 10px; border-radius:10px; width:300px;"
                                             src="{{ $car->license_back_image }}"
                                             onerror="this.onerror=null;this.src='{{ asset('dashboard/image_placeholder.png') }}';"
                                             class="zoomable-image">
                                             @if ($car->license_back_image)
                                             <span class="new-img-badge">NEW</span>
                                             <button type="button" class="edit-photo-btn" title="Replace image"
                                                     onclick="document.getElementById('input-lic-back').click()">✎</button>
                                             <input type="file" id="input-lic-back" name="license_back_image" accept="image/*"
                                                    style="display:none" data-wrapper="wrapper-lic-back" data-preview="preview-lic-back">
                                             @endif
                                    </div>
                                </div>

                                {{-- Inspection Image --}}
                                <div class="form-group" style="display:flex; align-items:flex-start; flex-wrap:wrap; gap:10px;">
                                    <label style="width:20%; margin-top:6px;">Inspection Image :</label>
                                    @if ($car->CarInspectionImage)
                                        <div class="photo-edit-wrapper" id="wrapper-inspection">
                                            <img id="preview-inspection" width="400" height="250"
                                                 style="margin:0 10px; border-radius:10px;"
                                                 src="{{ $car->CarInspectionImage }}"
                                                 onerror="this.onerror=null;this.src='{{ asset('dashboard/image_placeholder.png') }}';"
                                                 class="zoomable-image">
                                            <span class="new-img-badge">NEW</span>
                                            <button type="button" class="edit-photo-btn" title="Replace image"
                                                    onclick="document.getElementById('input-inspection').click()">✎</button>
                                            <input type="file" id="input-inspection" name="car_inspection_image" accept="image/*"
                                                   style="display:none" data-wrapper="wrapper-inspection" data-preview="preview-inspection">
                                        </div>
                                    @else
                                        <label style="color:#ff7272">There is no inspection.</label>
                                    @endif
                                </div>

                                @if ($car->car_inspection_date)
                                    <div class="form-group" style="display: flex;">
                                        <label>
                                            Car Inspection Date :
                                            <span style="color: #56ec60">
                                                {{ \Carbon\Carbon::parse($car->car_inspection_date)->format('d M Y') }}
                                            </span>
                                        </label>
                                    </div>
                                @endif

                                <div class="form-group">
                                    <label>Status</label>
                                    <select class="form-control" name="status">
                                        <option value="pending"   @if ($car->status == 'pending')   selected @endif>Pending</option>
                                        <option value="confirmed" @if ($car->status == 'confirmed') selected @endif>Confirmed</option>
                                        <option value="blocked"   @if ($car->status == 'blocked')   selected @endif>Blocked</option>
                                    </select>
                                </div>

                                <div class="form-group">
                                    <button type="submit" class="btn btn-light px-5">
                                        <i class="icon-lock"></i>Save
                                    </button>
                                </div>

                            </form>

                        </div>
                    </div>
                </div>
            </div>

            <!-- Image Preview Popup -->
            <div class="image-preview">
                <div class="preview-container">
                    <div class="preview-controls">
                        <button type="button" class="control-btn zoom-in-btn"  title="Zoom In">+</button>
                        <button type="button" class="control-btn zoom-out-btn" title="Zoom Out">-</button>
                        <button type="button" class="control-btn close-btn"    title="Close">✕</button>
                    </div>
                    <img src="" alt="Image Preview"
                         onerror="this.onerror=null;this.src='{{ asset('dashboard/image_placeholder.png') }}';">
                    <div class="zoom-indicator">100%</div>
                </div>
            </div>

            <div class="overlay toggle-menu"></div>
        </div>
    </div>
@endsection
@push('scripts')
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyCWDitjrboDO2zHDtZHzLlgRLduXi7-3Es"></script>
    <script>
        var map, marker, previousLocation;

        function initMap() {
            var userLocation = {
                lat: {{ $car->lat }},
                lng: {{ $car->lng }}
            };
            previousLocation = userLocation;

            map = new google.maps.Map(document.getElementById('map'), {
                zoom: 12,
                center: userLocation
            });

            marker = new RotatingMarker(userLocation, map, '{{ asset('dashboard/Travel-car-topview.svg.png') }}');

            setInterval(updateCarLocation, 3000);
        }

        function updateCarLocation() {
            fetch('/admin-dashboard/car-location/{{ $car->id }}')
                .then(response => response.json())
                .then(data => {
                    var newLocation = { lat: data.lat, lng: data.lng };

                    if (newLocation.lat === previousLocation.lat && newLocation.lng === previousLocation.lng) {
                        return;
                    }

                    var rotationAngle = calculateBearing(previousLocation, newLocation);
                    marker.setPosition(newLocation);
                    marker.setRotation(rotationAngle);
                    previousLocation = newLocation;
                    map.setCenter(newLocation);
                })
                .catch(error => console.error('Error fetching car location:', error));
        }

        function calculateBearing(start, end) {
            var startLat = degreesToRadians(start.lat);
            var startLng = degreesToRadians(start.lng);
            var endLat   = degreesToRadians(end.lat);
            var endLng   = degreesToRadians(end.lng);
            var dLng     = endLng - startLng;
            var y        = Math.sin(dLng) * Math.cos(endLat);
            var x        = Math.cos(startLat) * Math.sin(endLat) - Math.sin(startLat) * Math.cos(endLat) * Math.cos(dLng);
            return radiansToDegrees(Math.atan2(y, x));
        }

        function RotatingMarker(position, map, imageUrl) {
            this.position = position;
            this.rotation = 250;
            this.div = document.createElement('div');
            this.div.style.position         = 'absolute';
            this.div.style.width            = '50px';
            this.div.style.height           = '50px';
            this.div.style.backgroundImage  = `url(${imageUrl})`;
            this.div.style.backgroundSize   = 'contain';
            this.div.style.backgroundRepeat = 'no-repeat';
            this.setMap(map);
        }

        RotatingMarker.prototype = new google.maps.OverlayView();

        RotatingMarker.prototype.onAdd = function () {
            this.getPanes().overlayLayer.appendChild(this.div);
        };

        RotatingMarker.prototype.draw = function () {
            var proj     = this.getProjection();
            var position = proj.fromLatLngToDivPixel(this.position);
            this.div.style.left      = (position.x - 25) + 'px';
            this.div.style.top       = (position.y - 25) + 'px';
            this.div.style.transform = `rotate(${this.rotation}deg)`;
        };

        RotatingMarker.prototype.setPosition = function (position) {
            this.position = position;
            this.draw();
        };

        RotatingMarker.prototype.setRotation = function (rotation) {
            this.rotation = rotation;
            this.draw();
        };

        function degreesToRadians(degrees) { return degrees * (Math.PI / 180); }
        function radiansToDegrees(radians) { return radians * (180 / Math.PI); }

        window.onload = initMap;
    </script>

    <script>
        document.addEventListener('DOMContentLoaded', function () {

            /* ── 1. Live photo preview on file-input change ── */
            document.querySelectorAll('input[type="file"][data-preview]').forEach(function (input) {
                input.addEventListener('change', function () {
                    if (!this.files || !this.files[0]) return;
                    var wrapper    = document.getElementById(this.dataset.wrapper);
                    var previewImg = document.getElementById(this.dataset.preview);
                    var reader = new FileReader();
                    reader.onload = function (e) {
                        previewImg.src = e.target.result;
                        wrapper.classList.add('has-new');
                        if (input.name === 'car_image') {
                            var topImg = document.getElementById('preview-car-image');
                            if (topImg) topImg.src = e.target.result;
                        }
                    };
                    reader.readAsDataURL(this.files[0]);
                });
            });

            /* ── 2. Zoomable image preview popup ── */
            var imagePreview  = document.querySelector('.image-preview');
            var previewImg    = imagePreview.querySelector('img');
            var zoomInBtn     = imagePreview.querySelector('.zoom-in-btn');
            var zoomOutBtn    = imagePreview.querySelector('.zoom-out-btn');
            var closeBtn      = imagePreview.querySelector('.close-btn');
            var zoomIndicator = imagePreview.querySelector('.zoom-indicator');

            var scale = 1, posX = 0, posY = 0, isDragging = false, startX, startY;

            function updateTransform() {
                previewImg.style.transform = `translate(${posX}px, ${posY}px) scale(${scale})`;
                zoomIndicator.textContent  = `${Math.round(scale * 100)}%`;
            }
            function resetPosition() { posX = 0; posY = 0; }

            function openPreview(src) {
                previewImg.src = src;
                imagePreview.classList.add('show');
                document.body.style.overflow = 'hidden';
            }
            function closePreview() {
                imagePreview.classList.remove('show');
                document.body.style.overflow = '';
                scale = 1; resetPosition(); updateTransform();
            }

            // Attach click to all .zoomable-image including live-previewed ones
            document.addEventListener('click', function (e) {
                if (e.target.classList.contains('zoomable-image')) {
                    e.preventDefault();
                    openPreview(e.target.src);
                }
            });

            closeBtn.addEventListener('click',   function (e) { e.stopPropagation(); closePreview(); });
            zoomInBtn.addEventListener('click',  function (e) {
                e.stopPropagation();
                scale = Math.min(scale + 0.2, 5);
                updateTransform();
            });
            zoomOutBtn.addEventListener('click', function (e) {
                e.stopPropagation();
                scale -= 0.2;
                if (scale < 1) { scale = 1; resetPosition(); }
                updateTransform();
            });

            previewImg.addEventListener('wheel', function (e) {
                e.preventDefault();
                scale += e.deltaY < 0 ? 0.1 : -0.1;
                if (scale < 1) { scale = 1; resetPosition(); }
                else if (scale > 5) scale = 5;
                updateTransform();
            });

            previewImg.addEventListener('mousedown', function (e) {
                if (scale <= 1) return;
                isDragging = true;
                startX = e.clientX - posX; startY = e.clientY - posY;
                previewImg.style.cursor = 'grabbing';
                e.preventDefault();
            });
            document.addEventListener('mousemove', function (e) {
                if (!isDragging || scale <= 1) return;
                posX = e.clientX - startX; posY = e.clientY - startY;
                updateTransform();
            });
            document.addEventListener('mouseup', function () {
                if (isDragging) { isDragging = false; previewImg.style.cursor = 'grab'; }
            });

            previewImg.addEventListener('dragstart', function (e) { e.preventDefault(); });

            imagePreview.addEventListener('click', function (e) {
                if (e.target === imagePreview) closePreview();
            });
            document.addEventListener('keydown', function (e) {
                if (e.key === 'Escape' && imagePreview.classList.contains('show')) closePreview();
            });
        });
    </script>
@endpush