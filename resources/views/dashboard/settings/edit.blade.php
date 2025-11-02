@extends('dashboard.layout.app')
@section('title', 'Dashboard - edit setting')
@section('content')
    <style>
        fieldset {
            border: 2px solid #ccc;
            border-radius: 10px;
            padding: 1rem 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
        }

        legend {
            width: auto;
            font-weight: bold;
            font-size: 1.1rem;
            padding: 0 10px;
            margin: 0px;
        }
    </style>
    <div class="content-wrapper">
        <div class="container-fluid">

            <div class="row mt-3">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-body">
                            <div class="card-title">Edit Setting</div>
                            <hr>
                            <form method="post" action="{{ route('update.setting', $setting->id) }}"
                                enctype="multipart/form-data">
                                @csrf
                                <div class="form-group">
                                    <label>Label</label>
                                    <input type="text" name="label" class="form-control"
                                        placeholder="Enter Label"value="{{ old('label', $setting->label) }}">
                                    @if ($errors->has('label'))
                                        <p class="text-error more-info-err" style="color: red;">
                                            {{ $errors->first('label') }}</p>
                                    @endif
                                </div>
                                <div class="form-group">
                                    <label>Key</label>
                                    <input type="text" disabled class="form-control"
                                        placeholder="Enter Label"value="{{ $setting->key }}">
                                </div>
                                <div class="form-group">
                                    <label>Category</label>

                                    <select class="form-control" disabled>
                                        <option value="General" {!! $setting->category == 'General' ? 'selected' : '' !!}>General</option>
                                        <option value="Users" {!! $setting->category == 'Users' ? 'selected' : '' !!}>Users</option>
                                        <option value="Trips" {!! $setting->category == 'Trips' ? 'selected' : '' !!}>Trips</option>
                                        <option value="Car Trips" {!! $setting->category == 'Car Trips' ? 'selected' : '' !!}>Car Trips</option>
                                        <option value="Scooter Trips" {!! $setting->category == 'Scooter Trips' ? 'selected' : '' !!}>Motorcycle Trips</option>
                                        <option value="Comfort Trips" {!! $setting->category == 'Comfort Trips' ? 'selected' : '' !!}>Comfort Trips</option>

                                    </select>

                                </div>
                                @if ($setting->type == 'number')
                                    <div class="form-row">
                                        <div class="form-group col-md-10">
                                            <div class="form-group">
                                                <label>Value</label>
                                                <input type="number" name="value" class="form-control"
                                                    placeholder="Enter Value"value="{{ old('value', $setting->value) }}"
                                                    step="0.01">
                                                @if ($errors->has('value'))
                                                    <p class="text-error more-info-err" style="color: red;">
                                                        {{ $errors->first('value') }}</p>
                                                @endif
                                            </div>
                                        </div>
                                        <div class="form-group col-md-2">
                                            <div class="form-group">
                                                <label>Unit</label>
                                                <input type="text" disabled class="form-control"
                                                    value="{{ $setting->unit }}">

                                            </div>
                                        </div>
                                    </div>
                                @elseif($setting->type == 'boolean')
                                    <div class="form-row">
                                        <div class="form-group col-md-12">
                                            <div class="custom-control custom-switch mt-2">
                                                <input type="checkbox" class="custom-control-input"
                                                    id="boolean_value_{{ $setting->id }}" name="value" value="1"
                                                    {{ $setting->value=='On' ? 'checked' : '' }}>
                                                <label class="custom-control-label" for="boolean_value_{{ $setting->id }}">
                                                    {{ $setting->label ?? 'Enabled' }}
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                @elseif ($setting->type == 'string')
                                    <div class="form-row">
                                        <div class="form-group col-md-10">
                                            <div class="form-group">
                                                <label>Value</label>
                                                <input type="text" name="value" class="form-control"
                                                    placeholder="Enter Value"value="{{ old('value', $setting->value) }}"
                                                    step="0.01">
                                                @if ($errors->has('value'))
                                                    <p class="text-error more-info-err" style="color: red;">
                                                        {{ $errors->first('value') }}</p>
                                                @endif
                                            </div>
                                        </div>
                                        <div class="form-group col-md-2">
                                            <div class="form-group">
                                                <label>Unit</label>
                                                <input type="text" disabled class="form-control"
                                                    value="{{ $setting->unit }}">

                                            </div>
                                        </div>
                                    </div>
                                @elseif($setting->type == 'options')
                                    @php
                                        if ($setting->value) {
                                            $val = json_decode($setting->value, true);
                                        } else {
                                            $val = null;
                                        }
                                    @endphp
                                    <div class="form-group">
                                        <label>Value</label>
                                        <fieldset>
                                            <legend>Saturday :</legend>
                                            <div class="mb-2" style="margin-top: -10px; text-align:center;">
                                                <button data-day="Saturday" type="button"
                                                    class="btn btn-light px-5 add-peak-time">
                                                    Add Peak Time</button>
                                            </div>
                                            <div class="day-container" data-day="Saturday">
                                                @if ($val != null && array_key_exists('Saturday', $val))
                                                    @foreach ($val['Saturday'] as $key => $value)
                                                        <div class="form-row mb-2">
                                                            <input type="time"
                                                                name="value[Saturday][{{ $key }}][from]"
                                                                class="form-control mr-1" placeholder="From"
                                                                style="width: 48.2%;" required
                                                                value="{{ $value['from'] }}">
                                                            <input type="time"
                                                                name="value[Saturday][{{ $key }}][to]"
                                                                class="form-control mr-1" placeholder="To"
                                                                style="width: 48.2%;" required value="{{ $value['to'] }}">
                                                            <i class="fa fa-times remove-row"
                                                                style="font-size: 35px; color: indianred; cursor: pointer;"></i>
                                                        </div>
                                                    @endforeach
                                                @endif
                                                <!-- Time rows will be added here -->
                                            </div>


                                        </fieldset>
                                        <fieldset>
                                            <legend>Sunday :</legend>
                                            <div class="mb-2" style="margin-top: -10px; text-align:center;">
                                                <button data-day="Sunday" type="button"
                                                    class="btn btn-light px-5 add-peak-time">
                                                    Add Peak Time</button>
                                            </div>
                                            <div class="day-container" data-day="Sunday">
                                                @if ($val != null && array_key_exists('Sunday', $val))
                                                    @foreach ($val['Sunday'] as $key => $value)
                                                        <div class="form-row mb-2">
                                                            <input type="time"
                                                                name="value[Sunday][{{ $key }}][from]"
                                                                class="form-control mr-1" placeholder="From"
                                                                style="width: 48.2%;" required
                                                                value="{{ $value['from'] }}">
                                                            <input type="time"
                                                                name="value[Sunday][{{ $key }}][to]"
                                                                class="form-control mr-1" placeholder="To"
                                                                style="width: 48.2%;" required
                                                                value="{{ $value['to'] }}">
                                                            <i class="fa fa-times remove-row"
                                                                style="font-size: 35px; color: indianred; cursor: pointer;"></i>
                                                        </div>
                                                    @endforeach
                                                @endif
                                            </div>
                                        </fieldset>
                                        <fieldset>
                                            <legend>Monday :</legend>
                                            <div class="mb-2" style="margin-top: -10px; text-align:center;">
                                                <button data-day="Monday" type="button"
                                                    class="btn btn-light px-5 add-peak-time">
                                                    Add Peak Time</button>
                                            </div>
                                            <div class="day-container" data-day="Monday">
                                                @if ($val != null && array_key_exists('Monday', $val))
                                                    @foreach ($val['Monday'] as $key => $value)
                                                        <div class="form-row mb-2">
                                                            <input type="time"
                                                                name="value[Monday][{{ $key }}][from]"
                                                                class="form-control mr-1" placeholder="From"
                                                                style="width: 48.2%;" required
                                                                value="{{ $value['from'] }}">
                                                            <input type="time"
                                                                name="value[Monday][{{ $key }}][to]"
                                                                class="form-control mr-1" placeholder="To"
                                                                style="width: 48.2%;" required
                                                                value="{{ $value['to'] }}">
                                                            <i class="fa fa-times remove-row"
                                                                style="font-size: 35px; color: indianred; cursor: pointer;"></i>
                                                        </div>
                                                    @endforeach
                                                @endif
                                            </div>
                                        </fieldset>
                                        <fieldset>
                                            <legend>Tuesday :</legend>
                                            <div class="mb-2" style="margin-top: -10px; text-align:center;">
                                                <button data-day="Tuesday" type="button"
                                                    class="btn btn-light px-5 add-peak-time">
                                                    Add Peak Time</button>
                                            </div>
                                            <div class="day-container" data-day="Tuesday">
                                                @if ($val != null && array_key_exists('Tuesday', $val))
                                                    @foreach ($val['Tuesday'] as $key => $value)
                                                        <div class="form-row mb-2">
                                                            <input type="time"
                                                                name="value[Tuesday][{{ $key }}][from]"
                                                                class="form-control mr-1" placeholder="From"
                                                                style="width: 48.2%;" required
                                                                value="{{ $value['from'] }}">
                                                            <input type="time"
                                                                name="value[Tuesday][{{ $key }}][to]"
                                                                class="form-control mr-1" placeholder="To"
                                                                style="width: 48.2%;" required
                                                                value="{{ $value['to'] }}">
                                                            <i class="fa fa-times remove-row"
                                                                style="font-size: 35px; color: indianred; cursor: pointer;"></i>
                                                        </div>
                                                    @endforeach
                                                @endif
                                            </div>
                                        </fieldset>
                                        <fieldset>
                                            <legend>Wednesday :</legend>
                                            <div class="mb-2" style="margin-top: -10px; text-align:center;">
                                                <button data-day="Wednesday" type="button"
                                                    class="btn btn-light px-5 add-peak-time">
                                                    Add Peak Time</button>
                                            </div>
                                            <div class="day-container" data-day="Wednesday">
                                                @if ($val != null && array_key_exists('Wednesday', $val))
                                                    @foreach ($val['Wednesday'] as $key => $value)
                                                        <div class="form-row mb-2">
                                                            <input type="time"
                                                                name="value[Wednesday][{{ $key }}][from]"
                                                                class="form-control mr-1" placeholder="From"
                                                                style="width: 48.2%;" required
                                                                value="{{ $value['from'] }}">
                                                            <input type="time"
                                                                name="value[Wednesday][{{ $key }}][to]"
                                                                class="form-control mr-1" placeholder="To"
                                                                style="width: 48.2%;" required
                                                                value="{{ $value['to'] }}">
                                                            <i class="fa fa-times remove-row"
                                                                style="font-size: 35px; color: indianred; cursor: pointer;"></i>
                                                        </div>
                                                    @endforeach
                                                @endif
                                            </div>
                                        </fieldset>
                                        <fieldset>
                                            <legend>Thursday :</legend>
                                            <div class="mb-2" style="margin-top: -10px; text-align:center;">
                                                <button data-day="Thursday" type="button"
                                                    class="btn btn-light px-5 add-peak-time">
                                                    Add Peak Time</button>
                                            </div>
                                            <div class="day-container" data-day="Thursday">
                                                @if ($val != null && array_key_exists('Thursday', $val))
                                                    @foreach ($val['Thursday'] as $key => $value)
                                                        <div class="form-row mb-2">
                                                            <input type="time"
                                                                name="value[Thursday][{{ $key }}][from]"
                                                                class="form-control mr-1" placeholder="From"
                                                                style="width: 48.2%;" required
                                                                value="{{ $value['from'] }}">
                                                            <input type="time"
                                                                name="value[Thursday][{{ $key }}][to]"
                                                                class="form-control mr-1" placeholder="To"
                                                                style="width: 48.2%;" required
                                                                value="{{ $value['to'] }}">
                                                            <i class="fa fa-times remove-row"
                                                                style="font-size: 35px; color: indianred; cursor: pointer;"></i>
                                                        </div>
                                                    @endforeach
                                                @endif
                                            </div>
                                        </fieldset>
                                        <fieldset>
                                            <legend>Friday :</legend>
                                            <div class="mb-2" style="margin-top: -10px; text-align:center;">
                                                <button data-day="Friday" type="button"
                                                    class="btn btn-light px-5 add-peak-time">
                                                    Add Peak Time</button>
                                            </div>
                                            <div class="day-container" data-day="Friday">
                                                @if ($val != null && array_key_exists('Friday', $val))
                                                    @foreach ($val['Friday'] as $key => $value)
                                                        <div class="form-row mb-2">
                                                            <input type="time"
                                                                name="value[Friday][{{ $key }}][from]"
                                                                class="form-control mr-1" placeholder="From"
                                                                style="width: 48.2%;" required
                                                                value="{{ $value['from'] }}">
                                                            <input type="time"
                                                                name="value[Friday][{{ $key }}][to]"
                                                                class="form-control mr-1" placeholder="To"
                                                                style="width: 48.2%;" required
                                                                value="{{ $value['to'] }}">
                                                            <i class="fa fa-times remove-row"
                                                                style="font-size: 35px; color: indianred; cursor: pointer;"></i>
                                                        </div>
                                                    @endforeach
                                                @endif
                                            </div>
                                        </fieldset>
                                    </div>
                                @endif



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
            $('.add-peak-time').on('click', function() {
                const day = $(this).data('day');
                const container = $(`.day-container[data-day="${day}"]`);
                const index = container.children('.form-row').length;

                const row = `
                    <div class="form-row mb-2">
                        <input type="time" name="value[${day}][${index}][from]" class="form-control mr-1 from-time"
                               placeholder="From" style="width: 48.2%;" required>
                        <input type="time" name="value[${day}][${index}][to]" class="form-control mr-1 to-time"
                               placeholder="To" style="width: 48.2%;" disabled required>
                        <i class="fa fa-times remove-row" style="font-size: 35px; color: indianred; cursor: pointer;"></i>
                    </div>
                `;
                container.append(row);
            });
            $(document).on('change', '.from-time', function() {
                const fromTime = $(this).val();
                const toInput = $(this).closest('.form-row').find('.to-time');
                toInput.prop('disabled', false);
                toInput.attr('min', fromTime);
            });

            // Remove row on click of close icon
            $(document).on('click', '.remove-row', function() {
                $(this).closest('.form-row').remove();
            });
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
