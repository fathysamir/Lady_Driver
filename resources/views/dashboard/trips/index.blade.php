@extends('dashboard.layout.app')
@section('title', 'Dashboard - trips')
@section('content')
    <style>
        .pagination { display: inline-flex; }
    </style>
    <div class="content-wrapper">
        <div class="container-fluid">
            <div class="row">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-body">
                            <div style="text-align: center;">
                                <form id="searchForm" class="search-bar"
                                      style="margin-bottom:1%;margin-left:0px;"
                                      method="GET"
                                      action="{{ route('trips') }}">

                                    <input type="hidden" name="type"        id="type_input"        value="{{ $type }}">
                                    <input type="hidden" name="time_filter" id="time_filter_input"  value="{{ $time_filter }}">

                                    <div style="display:flex;">
                                        <h5 class="card-title" style="width:60%;text-align:left;">
                                            @if(request('driver') && isset($driverName))
                                                {{ $driverName }} - Trips
                                            @else
                                                Trips
                                            @endif
                                        </h5>
                                        <div style="display:flex;margin-bottom:1%;margin-left:0px;text-align:right;">
                                            <button class="btn btn-light px-5" type="button"
                                                    onclick="toggleFilters()"
                                                    style="margin:0% 1% 1% 1%;">Filter</button>
                                            <input type="text" class="form-control"
                                                   placeholder="Enter keywords"
                                                   name="search"
                                                   value="{{ request('search') }}">
                                            <a href="javascript:void(0);" id="submitForm">
                                                <i class="icon-magnifier"></i>
                                            </a>
                                        </div>
                                    </div>

                                    <div id="filterOptions"
                                    style="display:{{ request()->anyFilled(['user','status','payment_status','mark','model','created_date','air_conditioned','trip_type']) ? 'block' : 'none' }};text-align:center;">

                                        <div style="display:flex;">
                                            <select class="form-control" style="width:23.5%;margin:0% 1% 0% 1%;" name="status">
                                                <option value="">Select Status</option>
                                                <option value="pending"      {{ request('status') == 'pending'      ? 'selected' : '' }}>Pending</option>
                                                <option value="scheduled"    {{ request('status') == 'scheduled'    ? 'selected' : '' }}>Scheduled</option>
                                                <option value="in_progress"  {{ request('status') == 'in_progress'  ? 'selected' : '' }}>In Progress</option>
                                                <option value="completed"    {{ request('status') == 'completed'    ? 'selected' : '' }}>Completed</option>
                                                <option value="cancelled"    {{ request('status') == 'cancelled'    ? 'selected' : '' }}>Cancelled</option>
                                            </select>
                                            <select class="form-control" style="width:23.5%;margin:0% 0% 0% 1%;" name="payment_status">
                                                <option value="">Select Payment Status</option>
                                                <option value="unpaid"       {{ request('payment_status') == 'unpaid'       ? 'selected' : '' }}>Unpaid</option>
                                                <option value="online paid"  {{ request('payment_status') == 'online paid'  ? 'selected' : '' }}>Online Paid</option>
                                                <option value="cash paid"    {{ request('payment_status') == 'cash paid'    ? 'selected' : '' }}>Cash Paid</option>
                                            </select>
                                        </div>
                                        <div style="display:flex;margin-top:10px;">

                                            <select class="form-control" style="width:23.5%;margin:0% 1% 0% 1%;" name="mark" id="markSelect">
                                                <option value="">Select Vehicle Mark</option>
                                                @if ($type === 'scooter')
                                                    @foreach ($motorcycle_marks as $mark)
                                                        <option value="{{ $mark->id }}" {{ request('mark') == $mark->id ? 'selected' : '' }}>
                                                            {{ $mark->en_name }} - {{ $mark->ar_name }}
                                                        </option>
                                                    @endforeach
                                                @else
                                                    @foreach ($car_marks as $mark)
                                                        <option value="{{ $mark->id }}" {{ request('mark') == $mark->id ? 'selected' : '' }}>
                                                            {{ $mark->en_name }} - {{ $mark->ar_name }}
                                                        </option>
                                                    @endforeach
                                                @endif
                                            </select>
                                            <select class="form-control" style="width:23.5%;margin:0% 1% 0% 1%;{{ request('mark') ? '' : 'display:none;' }}" name="model" id="modelSelect">
                                                <option value="">Select Vehicle Model</option>
                                            </select>
                                            <div class="form-group" style="width:23.5%;margin:0% 0% 0% 1%;">
                                                <input type="date" name="created_date" class="form-control"
                                                       style="width:100%;" value="{{ request('created_date') }}">
                                            </div>
                                            <div class="form-group py-2" style="width:23.5%;margin:0%;{{ $type !== 'car' ? 'display:none;' : '' }}"
     id="airConditionedCheckbox">
                                                <div class="icheck-material-white">
                                                    <input type="checkbox" name="air_conditioned" id="user-checkbox2"
                                                           {{ request('air_conditioned') ? 'checked' : '' }} />
                                                    <label for="user-checkbox2">Air conditioned</label>
                                                </div>
                                            </div>
                                        </div>
                                        <button class="btn btn-light px-5" style="margin-top:10px" type="submit">
                                            Apply Filters
                                        </button>
                                    </div>

                                    {{-- ── Main Trip Type Tabs ── --}}
                                    <div class="btn-group mb-3" role="group" style="width:80%;margin-top:20px;">
                                        @can('trips.standard.view')
                                            <button type="button" class="btn btn-light trip-type-btn"
                                                    onclick="switchTab('car')" data-type="car"
                                                    style="width:33.33%;{{ $type === 'car' ? 'background-color:#30638a;color:white;' : '' }}">
                                                Standard Trips
                                            </button>
                                        @endcan
                                        @can('trips.comfort.view')
                                            <button type="button" class="btn btn-light trip-type-btn"
                                                    onclick="switchTab('comfort_car')" data-type="comfort_car"
                                                    style="width:33.33%;{{ $type === 'comfort_car' ? 'background-color:#30638a;color:white;' : '' }}">
                                                Comfort Trips
                                            </button>
                                        @endcan
                                        @can('trips.scooter.view')
                                            <button type="button" class="btn btn-light trip-type-btn"
                                                    onclick="switchTab('scooter')" data-type="scooter"
                                                    style="width:33.33%;{{ $type === 'scooter' ? 'background-color:#30638a;color:white;' : '' }}">
                                                Scooter Trips
                                            </button>
                                        @endcan
                                    </div>

                                    {{-- ── Time Filter Sub Tabs ── --}}
                                    <div class="btn-group mb-3" role="group" style="width:60%;">
                                        <button type="button" class="btn btn-light time-filter-btn"
                                                onclick="switchTimeTab('scheduled')" data-filter="scheduled"
                                                style="width:33.33%;{{ $time_filter === 'scheduled' ? 'background-color:#30638a;color:white;' : '' }}">
                                            Pending Trips
                                        </button>
                                        <button type="button" class="btn btn-light time-filter-btn"
                                                onclick="switchTimeTab('current')" data-filter="current"
                                                style="width:33.33%;{{ $time_filter === 'current' ? 'background-color:#30638a;color:white;' : '' }}">
                                            Current Trips
                                        </button>
                                        <button type="button" class="btn btn-light time-filter-btn"
                                                onclick="switchTimeTab('past')" data-filter="past"
                                                style="width:33.33%;{{ $time_filter === 'past' ? 'background-color:#30638a;color:white;' : '' }}">
                                            Past Trips
                                        </button>
                                    </div>
                                </form>
                            </div>

                            {{-- ── Results Table ── --}}
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Trip Code</th>
                                            <th>Client</th>
                                            <th>Client Phone</th>
                                            <th>Driver</th>
                                            <th>Driver Phone</th>
                                            <th>Vehicle</th>
                                            <th>Created at</th>
                                            <th>Status</th>
                                            <th>Payment Status</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse ($all_trips as $trip)
                                            <tr onclick="window.location='{{ url('/admin-dashboard/trip/view/' . $trip->id) }}';"
                                                style="cursor:pointer;">
                                                <td>{!! highlight($trip->code, $search ?? '') !!}</td>

                                                {{-- Client --}}
                                                <td>
                                                    <span class="user-profile">
                                                        <img src="{{ getFirstMediaUrl($trip->user, $trip->user->avatarCollection) ?? asset('dashboard/user_avatar.png') }}"
                                                             class="img-circle"
                                                             alt="user avatar"
                                                             onerror="this.onerror=null; this.src='{{ asset('dashboard/user_avatar.png') }}';">
                                                    </span>
                                                    {!! highlight($trip->user->name, $search ?? '') !!}
                                                </td>
                                                <td>{!! highlight($trip->user->phone, $search ?? '') !!}</td>

                                                {{-- Driver & Vehicle --}}
                                                @if ($type === 'scooter')
                                                <td>
                                                    <span class="user-profile">
                                                        <img src="{{ ($trip->scooter && $trip->scooter->owner && getFirstMediaUrl($trip->scooter->owner, $trip->scooter->owner->avatarCollection)) ? getFirstMediaUrl($trip->scooter->owner, $trip->scooter->owner->avatarCollection) : asset('dashboard/user_avatar.png') }}"
                                                             class="img-circle"
                                                             alt="user avatar"
                                                             onerror="this.onerror=null; this.src='{{ asset('dashboard/user_avatar.png') }}';">
                                                    </span>
                                                    {!! ($trip->scooter && $trip->scooter->owner) ? highlight($trip->scooter->owner->name, $search ?? '') : 'N/A' !!}
                                                </td>
                                                <td>{!! highlight(optional(optional($trip->scooter)->owner)->phone ?? 'N/A', $search ?? '') !!}</td>
                                                    <td>
                                                        @if ($trip->scooter)
                                                            {{ $trip->scooter->motorcycleMark->en_name ?? 'N/A' }} -
                                                            {{ $trip->scooter->motorcycleModel->en_name ?? 'N/A' }}
                                                            ({{ $trip->scooter->year }})
                                                        @else
                                                            N/A
                                                        @endif
                                                    </td>
                                                @else
                                                <td>
                                                    <span class="user-profile">
                                                        <img src="{{ ($trip->car && $trip->car->owner && getFirstMediaUrl($trip->car->owner, $trip->car->owner->avatarCollection)) ? getFirstMediaUrl($trip->car->owner, $trip->car->owner->avatarCollection) : asset('dashboard/user_avatar.png') }}"
                                                             class="img-circle"
                                                             alt="user avatar"
                                                             onerror="this.onerror=null; this.src='{{ asset('dashboard/user_avatar.png') }}';">
                                                    </span>
                                                    {!! ($trip->car && $trip->car->owner) ? highlight($trip->car->owner->name, $search ?? '') : 'N/A' !!}
                                                </td>
                                                <td>{!! highlight(optional(optional($trip->car)->owner)->phone ?? 'N/A', $search ?? '') !!}</td>
                                                    <td>
                                                        @if ($trip->car)
                                                            {{ $trip->car->mark->name }} -
                                                            {{ $trip->car->model->name }}
                                                            ({{ $trip->car->year }})
                                                        @else
                                                            N/A
                                                        @endif
                                                    </td>
                                                @endif

                                                <td>{{ date('Y-m-d h:i a', strtotime($trip->created_at)) }}</td>

                                                <td>
                                                    @php
                                                        $statusColors = [
                                                            'pending'     => 'rgb(143,118,9)',
                                                            'scheduled'   => 'rgb(255,165,0)',
                                                            'in_progress' => 'rgb(52,40,223)',
                                                            'completed'   => 'rgb(50,134,50)',
                                                            'cancelled'   => 'rgb(255,0,0)',
                                                        ];
                                                    @endphp
                                                    <span class="badge badge-secondary"
                                                          style="background-color:{{ $statusColors[$trip->status] ?? 'gray' }};width:100%;">
                                                        {{ ucfirst(str_replace('_', ' ', $trip->status)) }}
                                                    </span>
                                                </td>

                                                <td>{{ $trip->status === 'completed' ? 'Paid' : ucwords($trip->payment_status ?? 'Unpaid') }}</td>

                                                <td>
                                                    <a href="{{ url('/admin-dashboard/trip/view/' . $trip->id) }}"
                                                       onclick="event.stopPropagation();">
                                                        <span class="bi bi-eye" style="font-size:1rem;color:#fff;"></span>
                                                    </a>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="10" style="text-align:center;">No trips found.</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>

                                <div style="text-align:center;">
                                    {!! $all_trips->links('pagination::bootstrap-4') !!}
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
        $('#submitForm').on('click', function () {
            $('#searchForm').submit();
        });

        // Filter names that should be wiped when switching tabs or time filters
        const FILTER_PARAMS = [
            'user', 'driver', 'status', 'payment_status',
            'mark', 'model', 'created_date', 'air_conditioned', 'trip_type', 'search'
        ];

        function clearFilterInputs() {
            const form = document.getElementById('searchForm');
            FILTER_PARAMS.forEach(function (name) {
                const els = form.querySelectorAll('[name="' + name + '"]');
                els.forEach(function (el) {
                    if (el.type === 'checkbox') {
                        el.checked = false;
                    } else {
                        el.value = '';
                    }
                    // Disable so the field is excluded from the GET URL entirely.
                    // request()->hasAny() then returns false on reload and the
                    // filter panel stays closed.
                    el.disabled = true;
                });
            });
            const modelSelect = document.getElementById('modelSelect');
            modelSelect.innerHTML = '<option value="">Select Vehicle Model</option>';
            modelSelect.style.display = 'none';
        }

        function switchTab(tripType) {
            clearFilterInputs();
            document.getElementById('type_input').value        = tripType;
            document.getElementById('time_filter_input').value = 'current';
             // Hide/show air conditioned checkbox based on trip type
    const airCondDiv = document.getElementById('airConditionedCheckbox');
    if (airCondDiv) {
        airCondDiv.style.display = tripType === 'car' ? '' : 'none';
    }
            submitWithoutPage();
        }

        function switchTimeTab(timeFilter) {
            clearFilterInputs();
            document.getElementById('time_filter_input').value = timeFilter;
            submitWithoutPage();
        }

        function submitWithoutPage() {
            const existing = document.querySelector('#searchForm input[name="page"]');
            if (existing) existing.remove();
            document.getElementById('searchForm').submit();
        }

        function toggleFilters() {
            const el = document.getElementById('filterOptions');
            el.style.display = el.style.display === 'none' ? 'block' : 'none';
        }

        document.getElementById('markSelect').addEventListener('change', function () {
            const markId      = this.value;
            const modelSelect = document.getElementById('modelSelect');
            const currentType = document.getElementById('type_input').value;

            if (!markId) {
                modelSelect.style.display = 'none';
                modelSelect.innerHTML     = '<option value="">Select Vehicle Model</option>';
                return;
            }

            const endpoint = currentType === 'scooter'
                ? '/admin-dashboard/getMotorcycleModels?markId=' + markId
                : '/admin-dashboard/getModels?markId=' + markId;

            fetch(endpoint)
                .then(r => r.json())
                .then(data => {
                    modelSelect.innerHTML = '<option value="">Select Vehicle Model</option>';
                    data.forEach(model => {
                        modelSelect.innerHTML +=
                            `<option value="${model.id}">${model.en_name} - ${model.ar_name}</option>`;
                    });
                    modelSelect.style.display = 'block';
                })
                .catch(err => console.error('Error fetching models:', err));
        });
    </script>
@endpush