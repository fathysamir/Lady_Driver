@extends('dashboard.layout.app')
@section('title', 'Dashboard - edit car mark')
@section('content')
    <div class="content-wrapper">
        <div class="container-fluid">

            <div class="row mt-3">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-body">
                            <div class="card-title">Update Car Mark</div>
                            <hr>
                            <form method="post" action="{{ route('update.car.mark', ['id' => $mark->id]) }}"
                                enctype="multipart/form-data">
                                @csrf
                                <div class="form-group">
                                    <label><span style="cursor: pointer;" onclick="change_input('en')">English Name</span> |
                                        <span style="cursor: pointer;"onclick="change_input('ar')">Arabic
                                            Name</span></label>
                                    <input id="en_name_input" type="text" name="en_name" class="form-control"
                                        placeholder="Enter English Name"value="{{ old('en_name', $mark->en_name) }}">
                                    <input id="ar_name_input"type="text" name="ar_name" class="form-control"
                                        placeholder="Enter Arabic Name"value="{{ old('ar_name', $mark->ar_name) }}"
                                        style="display:none;">
                                    @if ($errors->has('en_name'))
                                        <p class="text-error more-info-err" style="color: red;">
                                            {{ $errors->first('en_name') }}</p>
                                    @endif
                                    @if ($errors->has('ar_name'))
                                        <p class="text-error more-info-err" style="color: red;">
                                            {{ $errors->first('ar_name') }}</p>
                                    @endif
                                </div>
                                <div class="form-group" style="display: flex; align-items: center;">
                                    <h4 style="margin-right: 10px;">Models</h4>
                                    <hr style="flex: 1; margin: 0;">
                                </div>
                                <div class="form-group text-center">
                                    <button type="button" class="btn btn-light px-5" id="addModel">
                                        Add Model
                                    </button>
                                </div>

                                <div class="col-12 form-group">
                                    <div class="row" id="modelsContainer"> <!-- Dynamic models will be added here -->
                                        <!-- Existing fields -->
                                        @foreach($models as $model)
                                        <div class="col-md-3 form-group position-relative">
                                            <div class="input-group">
                                                <input type="text" name="old_models[{{$model->id}}]"required class="form-control" value="{{$model->en_name}}">
                                                <div class="input-group-append">
                                                    <button type="button"
                                                        class="btn btn-outline-danger removeModel">✖</button>
                                                </div>
                                            </div>
                                        </div>
                                        @endforeach
                                       
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


            $("#addModel").click(function() {
                let modelField = `
                <div class="col-md-3 form-group position-relative">
                    <div class="input-group">
                        <input type="text" required name="new_models[]" class="form-control">
                        <div class="input-group-append">
                            <button type="button" class="btn btn-outline-danger removeModel">✖</button>
                        </div>
                    </div>
                </div>
            `;
                $("#modelsContainer").append(modelField);
            });

            // Remove a model input field
            $(document).on("click", ".removeModel", function() {
                $(this).closest(".form-group").remove();
            });
        });
    </script>
@endpush
