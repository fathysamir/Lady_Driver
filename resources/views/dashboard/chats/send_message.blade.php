@extends('dashboard.layout.app')
@section('title', 'Dashboard - Send Message')
@section('content')
    <style>
        .image-preview {
            max-width: 200px;
            max-height: 200px;
            margin-top: 10px;
            border-radius: 8px;
            border: 1px solid #ddd;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            object-fit: contain;
        }

        .video-preview {
            max-width: 300px;
            max-height: 200px;
            margin-top: 10px;
            border-radius: 8px;
            border: 1px solid #ddd;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            background-color: #f5f5f5;
        }

        .video-preview video {
            border-radius: 8px;
            width: 100%;
            height: auto;
        }
    </style>
    <div class="content-wrapper">
        <div class="container-fluid">

            <div class="row mt-3">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-body">
                            <div class="card-title">Send Message To Client Or Drivers</div>
                            <hr>
                            <form method="post" action="{{ route('send.messages') }}" enctype="multipart/form-data">
                                @csrf
                                <div class="form-group">
                                    <label>Message</label>
                                    <textarea class="form-control" name="message" rows="5" placeholder="Write your message"></textarea>
                                </div>
                                <div class="form-group">
                                    <label>Attached Image</label>
                                    <input type="file" class="form-control" name="image" accept="image/*"
                                        id="imageUpload">
                                    <div id="imagePreview" style="margin-top: 10px;"></div>
                                </div>

                                <div class="form-group">
                                    <label>Attached Video</label>
                                    <input type="file" class="form-control" name="video" accept="video/*"
                                        id="videoUpload">
                                    <div id="videoPreview" style="margin-top: 10px;"></div>
                                </div>
                                <div class="form-group">
                                    <label>Sending Date <span style="font-size:.5rem">(optional)</span></label>
                                    <input type="date" class="form-control" name="date">
                                </div>
                                <div class="form-group">
                                    <label>receivers</label>
                                    <select class="form-control" name="receivers_type" required>
                                        <option value="">Select Receivers</option>
                                        <option value="clients">Clients</option>
                                        <option value="drivers">Drivers</option>
                                        <option value="all">All</option>
                                    </select>
                                </div>

                                <div class="form-group">
                                    <button type="submit" class="btn btn-light px-5"><i class="icon-lock"></i>
                                        Send</button>
                                </div>
                            </form>
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
    <script>
        document.getElementById('imageUpload').addEventListener('change', function(event) {
            const preview = document.getElementById('imagePreview');
            preview.innerHTML = ''; // Clear previous preview

            const file = event.target.files[0];

            if (file && file.type.match('image.*')) {
                const reader = new FileReader();

                reader.onload = function(e) {
                    const img = document.createElement('img');
                    img.src = e.target.result;
                    img.className = 'image-preview'; // Use CSS class instead of inline styles
                    preview.appendChild(img);
                }

                reader.readAsDataURL(file);
            } else {
                alert('Please select an image file (JPEG, PNG, GIF, etc.)');
                event.target.value = ''; // Clear the input
            }
        });
    </script>
    <script>
        document.getElementById('videoUpload').addEventListener('change', function(event) {
            const preview = document.getElementById('videoPreview');
            preview.innerHTML = ''; // Clear previous preview

            const file = event.target.files[0];

            if (file && file.type.match('video.*')) {
                const video = document.createElement('video');
                video.controls = true; // Add video controls
                video.className = 'video-preview';

                const source = document.createElement('source');
                source.src = URL.createObjectURL(file);
                source.type = file.type;

                video.appendChild(source);
                preview.appendChild(video);

                // Optional: Add file name display
                const fileName = document.createElement('div');
                fileName.textContent = file.name;
                fileName.style.marginTop = '5px';
                fileName.style.fontSize = '0.8em';
                preview.appendChild(fileName);
            } else {
                alert('Please select a video file (MP4, WebM, etc.)');
                event.target.value = ''; // Clear the input
            }
        });
    </script>
    <script>
        $(document).ready(function() {
            let isFormDirty = false; // Track if the form has been modified

            // Detect changes in any input field inside a form
            $('form :input').on('change', function() {
                isFormDirty = true;
            });

            // Warn user before leaving the page if form is changed
            $(document).on('click', 'a', function(e) {
                if (isFormDirty) {
                    e.preventDefault(); // Prevent link navigation
                    let url = $(this).attr('href'); // Get the link URL

                    if (confirm("You have unsaved changes. Do you really want to leave?")) {
                        window.location.href = url; // Navigate if confirmed
                    }
                }
            });

            // Allow form submission without warning
            $('form').on('submit', function() {
                isFormDirty = false;
            });
        });
    </script>
@endpush
