@extends('dashboard.layout.app')
@section('title', 'Dashboard - create city')
@section('content')
    <div class="content-wrapper">
        <div class="container-fluid">

            <div class="row mt-3">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-body">
                            <div class="card-title">Create New City</div>
                            <hr>
                            <form method="post" action="{{ route('create.city',$queryString) }}" enctype="multipart/form-data">
                                @csrf
                               
                                <input type="hidden" name="page" value="{{ request()->input('page', 1) }}">
                                <div class="form-group">
                                    <label>Name</label>
                                    <input type="text" name="name" class="form-control"
                                        placeholder="Enter Name"value="{{ old('name') }}">
                                    
                                    @if ($errors->has('name'))
                                        <p class="text-error more-info-err" style="color: red;">
                                            {{ $errors->first('name') }}</p>
                                    @endif
                                    
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
            

            // Remove a model input field
           
        });
    </script>
@endpush
