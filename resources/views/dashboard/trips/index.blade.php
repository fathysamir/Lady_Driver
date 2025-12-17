@extends('dashboard.layout.app')
@section('title', 'Dashboard - trips')
@section('content')
    <style>
        .pagination {
            display: inline-flex;
        }
    </style>
    <div class="content-wrapper">
        <div class="container-fluid">
            <div class="row">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-body">
                            <div style="text-align: center;">
                                <form id="searchForm" class="search-bar" style="margin-bottom:1%;margin-left:0px;"
                                    method="post" action="{{ route('trips') }}" enctype="multipart/form-data">
                                    @csrf
                                    {{-- Hidden inputs for tab filtering --}}
                                    <input type="hidden" name="type" id="type_input"
                                        value="{{ request('type', 'car') }}">
                                    <input type="hidden" name="time_filter" id="time_filter_input"
                                        value="{{ request('time_filter', 'current') }}">

                                    <div style="display:flex;">
                                        <h5 class="card-title" style="width: 60%;text-align: left;">Trips</h5>
                                        <div style="display:flex;margin-bottom:1%;margin-left:0px;text-align: right;">
                                            <button class="btn btn-light px-5" type="button" onclick="toggleFilters()"
                                                style="margin:0% 1% 1% 1%;">Filter</button>
                                            <input type="text" class="form-control" placeholder="Enter keywords"
                                                name="search" value="{{ request('search') }}">
                                            <a href="javascript:void(0);" id="submitForm"><i class="icon-magnifier"></i></a>
                                        </div>
                                    </div>

                                    <div id="filterOptions" style="display: none; text-align:center;">
                                        <div style="display: flex;">
                                            <select class="form-control" style="width: 23.5%; margin: 0% 1% 0% 0%;"
                                                name="user">
                                                <option value="">Select Client</option>
                                                @foreach ($users as $user)
                                                    <option value="{{ $user->id }}"
                                                        {{ request('user') == $user->id ? 'selected' : '' }}>
                                                        {{ $user->name }}</option>
                                                @endforeach
                                            </select>
                                            <select class="form-control" style="width: 23.5%; margin: 0% 1% 0% 1%;"
                                                name="driver">
                                                <option value="">Select Driver</option>
                                                @foreach ($drivers as $driver)
                                                    <option value="{{ $driver->id }}"
                                                        {{ request('driver') == $driver->id ? 'selected' : '' }}>
                                                        {{ $driver->name }}</option>
                                                @endforeach
                                            </select>
                                            <select class="form-control" style="width: 23.5%; margin: 0% 1% 0% 1%;"
                                                name="status">
                                                <option value="">Select Status</option>
                                                <option value="pending"
                                                    {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                                                <option value="scheduled"
                                                    {{ request('status') == 'scheduled' ? 'selected' : '' }}>Scheduled
                                                </option>
                                                <option value="in_progress"
                                                    {{ request('status') == 'in_progress' ? 'selected' : '' }}>In Progress
                                                </option>
                                                <option value="completed"
                                                    {{ request('status') == 'completed' ? 'selected' : '' }}>Completed
                                                </option>
                                                <option value="cancelled"
                                                    {{ request('status') == 'cancelled' ? 'selected' : '' }}>Cancelled
                                                </option>
                                            </select>
                                            <select class="form-control" style="width: 23.5%; margin: 0% 0% 0% 1%;"
                                                name="payment_status">
                                                <option value="">Select Payment Status</option>
                                                <option value="unpaid"
                                                    {{ request('payment_status') == 'unpaid' ? 'selected' : '' }}>Unpaid
                                                </option>
                                                <option value="online paid"
                                                    {{ request('payment_status') == 'online paid' ? 'selected' : '' }}>
                                                    Online Paid</option>
                                                <option value="cash paid"
                                                    {{ request('payment_status') == 'cash paid' ? 'selected' : '' }}>Cash
                                                    Paid</option>
                                            </select>
                                        </div>
                                        <div style="display: flex; margin-top:10px;">
                                            <select class="form-control" style="width: 23.5%;margin: 0% 1% 0% 0%;"
                                                name="trip_type">
                                                <option value="">Select Trip Type</option>
                                                <option value="individual"
                                                    {{ request('trip_type') == 'individual' ? 'selected' : '' }}>Individual
                                                </option>
                                                <option value="couple"
                                                    {{ request('trip_type') == 'couple' ? 'selected' : '' }}>Couple
                                                </option>
                                                <option value="group"
                                                    {{ request('trip_type') == 'group' ? 'selected' : '' }}>Group</option>
                                            </select>
                                            <select class="form-control" style="width: 23.5%; margin: 0% 1% 0% 1%;"
                                                name="mark" id="markSelect">
                                                <option value="">Select Car Mark</option>
                                                @foreach ($car_marks as $mark)
                                                    <option value="{{ $mark->id }}"
                                                        {{ request('mark') == $mark->id ? 'selected' : '' }}>
                                                        {{ $mark->en_name }} - {{ $mark->ar_name }}</option>
                                                @endforeach
                                            </select>
                                            <select class="form-control"
                                                style="width: 23.5%; margin: 0% 1% 0% 1%; display:none;" name="model"
                                                id="modelSelect">
                                                <option value="">Select Car Model</option>
                                            </select>
                                            <div class="form-group" style="width: 23.5%; margin: 0% 0% 0% 1%;">
                                                <input type="date" name="created_date" class="form-control"
                                                    style="width:100%;" value="{{ request('created_date') }}">
                                            </div>
                                            <div class="form-group py-2" style="width: 23.5%; margin: 0%;">
                                                <div class="icheck-material-white">
                                                    <input type="checkbox" name="air_conditioned" id="user-checkbox2"
                                                        {{ request('air_conditioned') ? 'checked' : '' }} />
                                                    <label for="user-checkbox2">Air conditioned</label>
                                                </div>
                                            </div>
                                        </div>
                                        <button class="btn btn-light px-5" style="margin-top:10px" type="submit">Apply
                                            Filters</button>
                                    </div>

                                    {{-- Main Trip Type Tabs --}}
                                    <div class="btn-group mb-3" role="group" style="width: 80%; margin-top: 20px;">
                                        @can('trips.standard.view')
                                            <button type="button" class="btn btn-light trip-type-btn"
                                                onclick="showTab('car')" data-type="car" style="width: 33.33%">Standard
                                                Trips</button>
                                        @endcan
                                        @can('trips.comfort.view')
                                            <button type="button" class="btn btn-light trip-type-btn"
                                                onclick="showTab('comfort_car')" data-type="comfort_car"
                                                style="width: 33.33%">Comfort Trips</button>
                                        @endcan
                                        @can('trips.scooter.view')
                                            <button type="button" class="btn btn-light trip-type-btn"
                                                onclick="showTab('scooter')" data-type="scooter"
                                                style="width: 33.33%">Scooter Trips</button>
                                        @endcan
                                    </div>

                                    {{-- Sub Tabs for time filtering --}}
                                    @foreach (['car', 'comfort_car', 'scooter'] as $tripType)
                                        <div class="btn-group mb-3 time-filter-bar" role="group"
                                            style="width: 60%; display: none;" id="bar-{{ $tripType }}">
                                            <button type="button" class="btn btn-light time-filter-btn"
                                                onclick="showTimeTab('{{ $tripType }}', 'scheduled')"
                                                data-filter="scheduled" style="width: 33.33%">Scheduled Trips</button>
                                            <button type="button" class="btn btn-light time-filter-btn"
                                                onclick="showTimeTab('{{ $tripType }}', 'current')"
                                                data-filter="current" style="width: 33.33%">Current Trips</button>
                                            <button type="button" class="btn btn-light time-filter-btn"
                                                onclick="showTimeTab('{{ $tripType }}', 'past')" data-filter="past"
                                                style="width: 33.33%">Past Trips</button>
                                        </div>
                                    @endforeach
                                </form>
                            </div>

                            <div class="table-responsive">
                                @foreach (['car', 'comfort_car', 'scooter'] as $tripType)
                                    @foreach (['scheduled', 'current', 'past'] as $timeFilter)
                                        <div class="trip-tab" id="tab-{{ $tripType }}-{{ $timeFilter }}"
                                            style="display: none;">
                                            <h5>{{ ucfirst($tripType) }} - {{ ucfirst($timeFilter) }} Trips</h5>
                                            <table class="table table-hover">
                                                <thead>
                                                    <tr>
                                                        <th>Trip Code</th>
                                                        <th>Client</th>
                                                        <th>Driver</th>
                                                        <th>Car</th>
                                                        <th>Created at</th>
                                                        <th>Status</th>
                                                        <th>Payment Status</th>
                                                        <th>Action</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @php
                                                        $filteredTrips = $all_trips->filter(function ($trip) use (
                                                            $tripType,
                                                            $timeFilter,
                                                        ) {
                                                            $typeMatch = $trip->type === $tripType;
                                                            $timeMatch = false;

                                                            if ($timeFilter === 'scheduled') {
                                                                $timeMatch = in_array($trip->status, [
                                                                    'pending',
                                                                    'scheduled',
                                                                ]);
                                                            } elseif ($timeFilter === 'current') {
                                                                $timeMatch = $trip->status === 'in_progress';
                                                            } elseif ($timeFilter === 'past') {
                                                                $timeMatch = in_array($trip->status, [
                                                                    'completed',
                                                                    'cancelled',
                                                                ]);
                                                            }

                                                            return $typeMatch && $timeMatch;
                                                        });
                                                    @endphp

                                                    @if ($filteredTrips->count())
                                                        @foreach ($filteredTrips as $trip)
                                                            <tr onclick="window.location='{{ url('/admin-dashboard/trip/view/' . $trip->id) }}';"
                                                                style="cursor: pointer;">
                                                                <td>{!! highlight($trip->code, $search ?? '') !!}</td>

                                                                <td><span class="user-profile"><img
                                                                            @if (getFirstMediaUrl($trip->user, $trip->user->avatarCollection) != null) src="{{ getFirstMediaUrl($trip->user, $trip->user->avatarCollection) }}" @else src="{{ asset('dashboard/user_avatar.png') }}" @endif
                                                                            class="img-circle" alt="user avatar"></span>
                                                                    {!! highlight($trip->user->name, $search ?? '') !!}</td>

                                                                <td><span class="user-profile"><img
                                                                            @if (getFirstMediaUrl($trip->car->owner, $trip->car->owner->avatarCollection) != null) src="{{ getFirstMediaUrl($trip->car->owner, $trip->car->owner->avatarCollection) }}" @else src="{{ asset('dashboard/user_avatar.png') }}" @endif
                                                                            class="img-circle" alt="user avatar"></span>
                                                                    {!! highlight($trip->car->owner->name, $search ?? '') !!}</td>

                                                                <td>{{ $trip->car->mark->name }} -
                                                                    {{ $trip->car->model->name }} ({{ $trip->car->year }})
                                                                </td>
                                                                <td>{{ date('Y-m-d h:i a', strtotime($trip->created_at)) }}
                                                                </td>
                                                                <td>
                                                                    @php
                                                                        $statusColors = [
                                                                            'pending' => 'rgb(143, 118, 9)',
                                                                            'scheduled' => 'rgb(255, 165, 0)',
                                                                            'in_progress' => 'rgb(52, 40, 223)',
                                                                            'completed' => 'rgb(50, 134, 50)',
                                                                            'cancelled' => 'rgb(255,0,0)',
                                                                        ];
                                                                    @endphp
                                                                    <span class="badge badge-secondary"
                                                                        style="background-color: {{ $statusColors[$trip->status] ?? 'gray' }}; width:100%;">{{ ucfirst(str_replace('_', ' ', $trip->status)) }}</span>
                                                                </td>
                                                                <td>{{ $trip->payment_status }}</td>
                                                                <td>
                                                                    <a href="{{ url('/admin-dashboard/trip/view/' . $trip->id) }}"
                                                                        onclick="event.stopPropagation();"><span
                                                                            class="bi bi-eye"
                                                                            style="font-size: 1rem; color: #fff;"></span></a>
                                                                </td>
                                                            </tr>
                                                        @endforeach
                                                    @else
                                                        <tr>
                                                            <td colspan="8" style="text-align:center;">No trips found.
                                                            </td>
                                                        </tr>
                                                    @endif
                                                </tbody>
                                            </table>
                                        </div>
                                    @endforeach
                                @endforeach

                                <div style="text-align: center;">
                                    {!! $all_trips->appends(request()->except('page'))->links('pagination::bootstrap-4') !!}
                                </div>
                            </div>
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
            $('#submitForm').on('click', function() {
                $('#searchForm').submit();
            });
        });

        function showTab(tripType) {
            const bars = document.querySelectorAll('.time-filter-bar');
            bars.forEach(bar => bar.style.display = 'none');

            const targetBar = document.getElementById('bar-' + tripType);
            if (targetBar) targetBar.style.display = 'inline-flex';

            document.querySelectorAll('.trip-type-btn').forEach(btn => {
                if (btn.getAttribute('data-type') === tripType) {
                    btn.style.backgroundColor = '#30638a';
                    btn.style.color = 'white';
                } else {
                    btn.style.backgroundColor = '';
                    btn.style.color = '';
                }
            });
            showTimeTab(tripType, 'current');
        }

        function showTimeTab(tripType, timeFilter) {

            const tabs = document.querySelectorAll('.trip-tab');
            tabs.forEach(tab => tab.style.display = 'none');

            const targetTab = document.getElementById('tab-' + tripType + '-' + timeFilter);
            if (targetTab) targetTab.style.display = 'block';

            const currentBar = document.getElementById('bar-' + tripType);
            if (currentBar) {
                currentBar.querySelectorAll('.time-filter-btn').forEach(btn => {
                    if (btn.getAttribute('data-filter') === timeFilter) {
                        btn.style.backgroundColor = '#30638a';
                        btn.style.color = 'white';
                    } else {
                        btn.style.backgroundColor = '';
                        btn.style.color = '';
                    }
                });
            }

            // Update hidden inputs
            document.getElementById('type_input').value = tripType;
            document.getElementById('time_filter_input').value = timeFilter;
        }

        function toggleFilters() {
            const filterOptions = document.getElementById("filterOptions");
            filterOptions.style.display = (filterOptions.style.display === "none") ? "block" : "none";
        }

        document.getElementById('markSelect').addEventListener('change', function() {
            const markId = this.value;
            const modelSelect = document.getElementById('modelSelect');
            fetch('/admin-dashboard/getModels?markId=' + markId)
                .then(response => response.json())
                .then(data => {
                    modelSelect.innerHTML = '<option value="">Select Car Model</option>';
                    data.forEach(model => {
                        modelSelect.innerHTML +=
                            `<option value="${model.id}">${model.en_name} - ${model.ar_name}</option>`;
                    });
                    modelSelect.style.display = 'block';
                })
                .catch(err => console.error('Error:', err));
        });


        window.addEventListener('DOMContentLoaded', () => {
            const currentType = document.getElementById('type_input').value || 'car';
            const currentTime = document.getElementById('time_filter_input').value || 'current';
            showTab(currentType);
            showTimeTab(currentType, currentTime);
        });
    </script>
@endpush
