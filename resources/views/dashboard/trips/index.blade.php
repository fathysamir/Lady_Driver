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


                            <form id="searchForm"
                                  class="search-bar"
                                  method="GET"
                                  action="{{ route('trips') }}">

                                {{-- Hidden inputs --}}
                                <input type="hidden" name="type" id="type_input"
                                       value="{{ request('type', 'car') }}">

                                <input type="hidden" name="time_filter" id="time_filter_input"
                                       value="{{ request('time_filter', 'current') }}">

                                <div style="display:flex;">
                                    <h5 style="width:60%; text-align:left;">Trips</h5>

                                    <div style="display:flex;">
                                        <button class="btn btn-light" type="button" onclick="toggleFilters()">Filter</button>

                                        <input type="text"
                                               class="form-control"
                                               name="search"
                                               placeholder="Enter keywords"
                                               value="{{ request('search') }}">

                                        <a href="javascript:void(0);" id="submitForm">
                                            <i class="icon-magnifier"></i>
                                        </a>
                                    </div>
                                </div>

                                {{-- FILTERS --}}
                                <div id="filterOptions" style="display:none; margin-top:10px;">

                                    <select class="form-control" name="user">
                                        <option value="">Select Client</option>
                                        @foreach ($users as $user)
                                            <option value="{{ $user->id }}"
                                                {{ request('user') == $user->id ? 'selected' : '' }}>
                                                {{ $user->name }}
                                            </option>
                                        @endforeach
                                    </select>

                                    <select class="form-control" name="driver">
                                        <option value="">Select Driver</option>
                                        @foreach ($drivers as $driver)
                                            <option value="{{ $driver->id }}"
                                                {{ request('driver') == $driver->id ? 'selected' : '' }}>
                                                {{ $driver->name }}
                                            </option>
                                        @endforeach
                                    </select>

                                    <select class="form-control" name="status">
                                        <option value="">Status</option>
                                        <option value="pending" {{ request('status')=='pending'?'selected':'' }}>Pending</option>
                                        <option value="scheduled" {{ request('status')=='scheduled'?'selected':'' }}>Scheduled</option>
                                        <option value="in_progress" {{ request('status')=='in_progress'?'selected':'' }}>In Progress</option>
                                        <option value="completed" {{ request('status')=='completed'?'selected':'' }}>Completed</option>
                                        <option value="cancelled" {{ request('status')=='cancelled'?'selected':'' }}>Cancelled</option>
                                    </select>

                                    <button class="btn btn-light mt-2" type="submit">Apply Filters</button>
                                </div>

                                {{-- TABS --}}
                                <div class="btn-group mb-3" style="width:80%; margin-top:20px;">
                                    <button type="button"
                                            class="btn btn-light trip-type-btn"
                                            data-type="car"
                                            onclick="showTab('car')">
                                        Standard
                                    </button>

                                    <button type="button"
                                            class="btn btn-light trip-type-btn"
                                            data-type="comfort_car"
                                            onclick="showTab('comfort_car')">
                                        Comfort
                                    </button>

                                    <button type="button"
                                            class="btn btn-light trip-type-btn"
                                            data-type="scooter"
                                            onclick="showTab('scooter')">
                                        Scooter
                                    </button>
                                </div>

                            </form>
                        </div>

                        {{-- TABLE --}}
                        <div class="table-responsive">

                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Trip Code</th>
                                        <th>User</th>
                                        <th>Status</th>
                                        <th>Payment</th>
                                    </tr>
                                </thead>

                                <tbody>
                                    @forelse ($all_trips as $trip)
                                        <tr>
                                            <td>{{ $trip->code }}</td>
                                            <td>{{ $trip->user->name ?? '-' }}</td>
                                            <td>{{ $trip->status }}</td>
                                            <td>{{ $trip->payment_status }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="4" style="text-align:center;">No trips found</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>

                        </div>

                        <div style="text-align:center;">
                            {!! $all_trips->appends(request()->query())->links('pagination::bootstrap-4') !!}
                        </div>

                    </div>
                </div>

            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function () {
    $('#submitForm').on('click', function () {
        $('#searchForm').submit();
    });
});

function showTab(type) {
    document.getElementById('type_input').value = type;
    document.getElementById('searchForm').submit();
}

function toggleFilters() {
    let f = document.getElementById("filterOptions");
    f.style.display = (f.style.display === "none") ? "block" : "none";
}

window.addEventListener('DOMContentLoaded', () => {
    const type = new URLSearchParams(window.location.search).get('type') || 'car';

    document.getElementById('type_input').value = type;

    document.querySelectorAll('.trip-type-btn').forEach(btn => {
        if (btn.dataset.type === type) {
            btn.style.background = '#30638a';
            btn.style.color = '#fff';
        }
    });
});
</script>
@endpush