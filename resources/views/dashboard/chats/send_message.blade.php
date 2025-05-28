@extends('dashboard.layout.app')
@section('title', 'Dashboard - Send Message')
@section('content')
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
                                    
                                </div>

                                <div class="form-group">
                                   
                                </div>
                                <div class="form-group">
                                    

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
