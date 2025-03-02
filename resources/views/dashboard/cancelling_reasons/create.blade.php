@extends('dashboard.layout.app')
@section('title', 'Dashboard - create trip cancellation reason')
@section('content')
    <div class="content-wrapper">
        <div class="container-fluid">

            <div class="row mt-3">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-body">
                            <div class="card-title">Create New Trip Cancellation Reason</div>
                            <hr>
                            <form method="post" action="{{ route('create.reason') }}" enctype="multipart/form-data">
                                @csrf
                                <div class="form-group">
                                    <label><span style="cursor: pointer;" onclick="change_input('en')">English Reason</span>
                                        | <span style="cursor: pointer;"onclick="change_input('ar')">Arabic
                                            Reason</span></label>
                                    <input id="en_name_input" type="text" name="en_reason" class="form-control"
                                        placeholder="Enter English Reason"value="{{ old('en_reason') }}">
                                    <input id="ar_name_input"type="text" name="ar_reason" class="form-control"
                                        placeholder="Enter Arabic Reason"value="{{ old('ar_reason') }}"
                                        style="display:none;">
                                    @if ($errors->has('en_reason'))
                                        <p class="text-error more-info-err" style="color: red;">
                                            {{ $errors->first('en_reason') }}</p>
                                    @endif
                                    @if ($errors->has('ar_reason'))
                                        <p class="text-error more-info-err" style="color: red;">
                                            {{ $errors->first('ar_reason') }}</p>
                                    @endif
                                </div>
                                <div class="form-group">
                                    <label>Category</label>

                                    <select class="form-control" name="category">
                                        <option value="">Select Category</option>
                                        <option value="client"@if (old('category') == 'client') selected @endif>Client
                                        </option>
                                        <option value="driver"@if (old('category') == 'driver') selected @endif>Driver
                                        </option>

                                    </select>
                                    @if ($errors->has('category'))
                                        <p class="text-error more-info-err" style="color: red;">
                                            {{ $errors->first('category') }}</p>
                                    @endif
                                </div>

                                <div class="form-group">
                                    <label>Value Type</label>

                                    <select class="form-control" name="value_type"id="value_type_select">
                                        <option value="">Select Value Type</option>
                                        <option value="fixed"@if (old('value_type') == 'fixed') selected @endif>Fixed
                                        </option>
                                        <option value="ratio"@if (old('value_type') == 'ratio') selected @endif>Ratio
                                        </option>

                                    </select>
                                    @if ($errors->has('value_type'))
                                        <p class="text-error more-info-err" style="color: red;">
                                            {{ $errors->first('value_type') }}</p>
                                    @endif
                                </div>
                                <div class="form-group">
                                    <label>Value</label>
                                    <input type="number" name="value" class="form-control" id="value_input"
                                        placeholder="Enter Value"value="{{ old('value') }}" step="0.01">
                                    @if ($errors->has('value'))
                                        <p class="text-error more-info-err" style="color: red;">
                                            {{ $errors->first('value') }}</p>
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
        function change_input(lang) {
            if (lang === 'en') {
                document.getElementById('en_name_input').style.display = 'block';
                document.getElementById('ar_name_input').style.display = 'none';
            } else if (lang === 'ar') {
                document.getElementById('en_name_input').style.display = 'none';
                document.getElementById('ar_name_input').style.display = 'block';
            }
        }
    </script>
    <script>
        document.getElementById('value_type_select').addEventListener('change', function() {
            // Clear the input value when the value_type select changes
            document.getElementById('value_input').value = "";
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
