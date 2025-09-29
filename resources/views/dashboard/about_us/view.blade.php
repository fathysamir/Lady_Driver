@extends('dashboard.layout.app')
@section('title', 'Dashboard - view about us')
@section('content')
    <div class="content-wrapper">
        <div class="container-fluid">

            <div class="row mt-3">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-body">
                            <div class="card-title">About Us</div>
                            <hr>
                            @if (session('error'))
                                <div id="errorAlert" class="alert alert-danger"
                                    style="padding-top:5px;padding-bottom:5px; padding-left: 10px; background-color:brown;border-radius: 20px; color:beige;">
                                    {{ session('error') }}
                                </div>
                            @endif

                            @if (session('success'))
                                <div id="successAlert"
                                    class="alert alert-success"style="padding-top:5px;padding-bottom:5px; padding-left: 10px; background-color:green;border-radius: 20px; color:beige;">
                                    {{ session('success') }}
                                </div>
                            @endif
                            <form method="post" action="{{ route('update.about_us') }}" enctype="multipart/form-data">
                                @csrf
                                <div class="form-group">
                                    <label>Description</label>
                                    <textarea name="description" class="form-control" rows="5">{{ $description }}</textarea>

                                </div>
                                <div class="form-group">
                                    <label>Email No.1</label>
                                    <input type="text" class="form-control" name="email1" value="{{ $email1 }}">

                                </div>
                                 <div class="form-group">
                                    <label>Email No.2</label>
                                    <input type="text" class="form-control" name="email2" value="{{ $email2 }}">

                                </div>
                                 <div class="form-group">
                                    <label>Email No.3</label>
                                    <input type="text" class="form-control" name="email3" value="{{ $email3 }}">

                                </div>
                                 <div class="form-group">
                                    <label>Email No.4</label>
                                    <input type="text" class="form-control" name="email4" value="{{ $email4 }}">

                                </div>
                                <div class="form-group">
                                    <label>Phone Number No.1</label>
                                    <input type="text" class="form-control" name="phone1" value="{{ $phone1 }}">

                                </div>
                                <div class="form-group">
                                    <label>Phone Number No.2</label>
                                    <input type="text" class="form-control" name="phone2" value="{{ $phone2 }}">

                                </div>
                                <div class="form-group">
                                    <label>Phone Number No.3</label>
                                    <input type="text" class="form-control" name="phone3" value="{{ $phone3 }}">

                                </div>
                                <div class="form-group">
                                    <label>Phone Number No.4</label>
                                    <input type="text" class="form-control" name="phone4" value="{{ $phone4 }}">

                                </div>
                                <div class="form-group">
                                    <label>Facebook Link</label>
                                    <input type="text" class="form-control" name="facebook" value="{{ $facebook }}">

                                </div>
                                <div class="form-group">
                                    <label>Instagram Link</label>
                                    <input type="text" class="form-control" name="instagram" value="{{ $instagram }}">
                                </div>
                                <div class="form-group">
                                    <label>Twitter Link</label>
                                    <input type="text" class="form-control" name="twitter" value="{{ $twitter }}">
                                </div>
                                <div class="form-group">
                                    <label>TikTok Link</label>
                                    <input type="text" class="form-control" name="tiktok" value="{{ $tiktok }}">
                                </div>
                                <div class="form-group">
                                    <label>Linked-In Link</label>
                                    <input type="text" class="form-control" name="linked_in" value="{{ $linked_in }}">
                                </div>
                                <div class="form-group">
                                    <label>Application Link (Android)</label>
                                    <input type="text" class="form-control" name="app_link_android" value="{{ $app_link_android }}">
                                </div>
                                 <div class="form-group">
                                    <label>Application Link (IOS)</label>
                                    <input type="text" class="form-control" name="app_link_IOS" value="{{ $app_link_IOS }}">
                                </div>


                                <div class="form-group">
                                    <button type="submit" class="btn btn-light px-5"><i class="icon-lock"></i>
                                        Save</button>
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
        // Set a timeout to hide the error or success message after 5 seconds
        setTimeout(function() {
            $('#errorAlert').fadeOut();
            $('#successAlert').fadeOut();
        }, 4000); // 5000 milliseconds = 5 seconds
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
