@extends('dashboard.layout.app')
@section('title', 'Dashboard - Create Rating Trip Settings')
@section('content')
    <div class="content-wrapper">
        <div class="container-fluid">

            <div class="row mt-3">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-body">
                            <div class="card-title">Create Rating Trip Settings</div>
                            <hr>
                            <form method="post" action="{{ route('store.rating') }}">
                                @csrf

                                <div class="form-group">
                                    <label>Label</label>
                                    <input type="text" name="label" class="form-control"
                                        placeholder="Enter Label"
                                        value="{{ old('label') }}"
                                        required>
                                    @if ($errors->has('label'))
                                        <p class="text-error more-info-err" style="color: red;">
                                            {{ $errors->first('label') }}</p>
                                    @endif
                                </div>

                                <div class="form-group">
                                    <label>Star Count</label>
                                    <select class="form-control" name="star_count" required>
                                        <option value="">Select Star Count</option>
                                        @for ($i = 1; $i <= 5; $i++)
                                            <option value="{{ $i }}" @if (old('star_count') == $i) selected @endif>
                                                {{ $i }} Star{{ $i > 1 ? 's' : '' }}
                                            </option>
                                        @endfor
                                    </select>
                                    @if ($errors->has('star_count'))
                                        <p class="text-error more-info-err" style="color: red;">
                                            {{ $errors->first('star_count') }}</p>
                                    @endif
                                </div>

                                <div class="form-group">
                                    <label>Category</label>
                                    <select class="form-control" name="category" required>
                                        <option value="">Select Category</option>
                                        <option value="driver" @if (old('category') == 'driver') selected @endif>
                                            Driver
                                        </option>
                                        <option value="client" @if (old('category') == 'client') selected @endif>
                                            Client
                                        </option>
                                    </select>
                                    @if ($errors->has('category'))
                                        <p class="text-error more-info-err" style="color: red;">
                                            {{ $errors->first('category') }}</p>
                                    @endif
                                </div>

                                <div class="form-group">
                                    <button type="submit" class="btn btn-light px-5">
                                        <i class="icon-lock"></i> Create
                                    </button>
                                    <a href="{{ route('ratingtripsettings') }}" class="btn btn-light px-5">
                                        Cancel
                                    </a>
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
            let isFormDirty = false;

            $('form :input').on('change', function() {
                isFormDirty = true;
            });

            $(document).on('click', 'a', function(e) {
                if (isFormDirty && !$(this).closest('form').length) {
                    e.preventDefault();
                    let url = $(this).attr('href');

                    if (confirm("You have unsaved changes. Do you really want to leave?")) {
                        window.location.href = url;
                    }
                }
            });

            $('form').on('submit', function() {
                isFormDirty = false;
            });
        });
    </script>
@endpush