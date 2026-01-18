@extends('dashboard.layout.app')
@section('title', 'Dashboard - update FAQ')
@section('content')
    <div class="content-wrapper">
        <div class="container-fluid">

            <div class="row mt-3">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-body">
                            <div class="card-title">Update FAQ</div>
                            <hr>
                            <form method="post" action="{{ route('update.FAQ',['id' => $FAQ->id] +$queryString) }}" enctype="multipart/form-data">
                                @csrf
                               
                                <input type="hidden" name="page" value="{{ request()->input('page', 1) }}">
                                <div class="form-group">
                                    <label>Question</label>
                                    <textarea class="form-control" name="question">{{ old('question',$FAQ->question) }}</textarea>


                                    @if ($errors->has('question'))
                                        <p class="text-error more-info-err" style="color: red;">
                                            {{ $errors->first('question') }}</p>
                                    @endif

                                </div>
                                <div class="form-group">
                                    <label>Answer</label>
                                    <textarea class="form-control" name="answer">{{ old('answer',$FAQ->answer) }}</textarea>

                                    @if ($errors->has('answer'))
                                        <p class="text-error more-info-err" style="color: red;">
                                            {{ $errors->first('question') }}</p>
                                    @endif

                                </div>
                                <div class="form-group">
                                    <label>Category</label>

                                    <select class="form-control" name="category">
                                        <option value="">Select Category</option>
                                        <option value="client"@if ($reason->type == 'client') selected @endif>Client
                                        </option>
                                        <option value="driver"@if ($reason->type == 'driver') selected @endif>Driver
                                        </option>


                                    </select>
                                    @if ($errors->has('category'))
                                        <p class="text-error more-info-err" style="color: red;">
                                            {{ $errors->first('category') }}</p>
                                    @endif
                                </div>
                                <div class="form-row">
                                    <div class="form-group col-md-12">
                                        <div class="custom-control custom-switch mt-2">
                                            <input type="checkbox" class="custom-control-input" id="is_active"
                                                name="is_active"{{ $FAQ->is_active=='1' ? 'checked' : '' }}>
                                            <label class="custom-control-label" for="is_active">
                                                Activation
                                            </label>
                                        </div>
                                    </div>
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
