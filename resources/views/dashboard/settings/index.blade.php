@extends('dashboard.layout.app')
@section('title', 'Dashboard - Settings')
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

                                <form id="searchForm" class="search-bar" style="margin-bottom:1%;margin-left:0px;"method="post"
                                    action="{{ route('settings') }}" enctype="multipart/form-data">
                                    @csrf
                                    <div style="display:flex;">
                                        <h5 class="card-title" style="width: 60%;text-align: left;">Settings</h5>

                                        <div style="display:flex;margin-bottom:1%;margin-left:0px;text-align: right;">

                                            {{-- <button class="btn btn-light px-5" type="button"
                                                onclick="toggleFilters()"style="margin:0% 1% 1% 1%; ">Filter</button> --}}
                                            <input type="text" class="form-control" placeholder="Enter keywords"
                                                name="search">
                                            <a href="javascript:void(0);" id="submitForm"><i class="icon-magnifier"></i></a>
                                        </div>


                                    </div>


                                    <div id="filterOptions" style="display: none; text-align:center;">
                                        <div style="text-align:center;">
                                            <select class="form-control"style="width: 33%;display:inline" name="category">
                                                <option value="">Select Category</option>
                                                <option value="General">General</option>
                                                <option value="Users">Users</option>
                                                <option value="Trips">Trips</option>
                                                <option value="Car Trips">Car Trips</option>

                                                <option value="Motorcycle Trips">Motorcycle Trips</option>


                                            </select>
                                        </div>


                                        <button class="btn btn-light px-5" style="margin-top:10px" type="submit">Apply
                                            Filters</button>
                                    </div>
                                    <div class="btn-group mb-3" role="group" style="width: 80%;">
                                        <button type="button" class="btn btn-light " onclick="showTab('General')"
                                            style="width: 16.55%">General</button>
                                        <button type="button" class="btn btn-light "
                                            onclick="showTab('Users')"style="width: 16.55%">Users</button>
                                        <button type="button" class="btn btn-light "
                                            onclick="showTab('Trips')"style="width: 16.55%">Trips</button>
                                        <button type="button" class="btn btn-light "
                                            onclick="showTab('Car Trips')"style="width: 16.55%">Car Trips</button>
                                        <button type="button" class="btn btn-light "
                                            onclick="showTab('Scooter Trips')"style="width: 16.55%">Scooter Trips</button>
                                        <button type="button" class="btn btn-light "
                                            onclick="showTab('Comfort Trips')"style="width: 16.55%">Comfort Trips</button>
                                    </div>
                                    @foreach (['General', 'Car Trips', 'Scooter Trips', 'Comfort Trips'] as $category)
                                        <div class="btn-group mb-3 settings-bar" role="group"
                                            style="width: 60%;display:none;" id="bar-{{ Str::slug($category) }}">
                                            <button type="button" class="btn btn-light "
                                                onclick="showTab('{{ $category }} 1','bar')" style="width: 12%">Level
                                                1</button>
                                            <button type="button" class="btn btn-light "
                                                onclick="showTab('{{ $category }} 2','bar')"style="width: 12%">Level
                                                2</button>
                                            <button type="button" class="btn btn-light "
                                                onclick="showTab('{{ $category }} 3','bar')"style="width: 12%">Level
                                                3</button>
                                            <button type="button" class="btn btn-light "
                                                onclick="showTab('{{ $category }} 4','bar')"style="width: 12%">Level
                                                4</button>
                                            <button type="button" class="btn btn-light "
                                                onclick="showTab('{{ $category }} 5','bar')"style="width: 12%">Level
                                                5</button>
                                        </div>
                                    @endforeach
                                </form>
                                {{-- <a  class="btn btn-light px-5" style="margin-bottom:1%; " href="{{route('add.user')}}">create</a> --}}
                            </div>

                            @if (session('error'))
                                <div id="errorAlert" class="alert alert-danger"
                                    style="padding-top:5px;padding-bottom:5px; padding-left: 10px; background-color:brown;border-radius: 20px; color:beige;">
                                    {{ session('error') }}
                                </div>
                            @endif

                            @if (session('success'))
                                <div id="successAlert"
                                    class="alert alert-success"style="padding-top:5px;padding-bottom:5px; padding-left: 10px; background-color:green;border-radius: 20px; color:beige;">
                                    {{ session('success') }}
                                </div>
                            @endif

                            <div class="table-responsive">
                                @foreach (['General', 'Users', 'Trips', 'Car Trips', 'Scooter Trips', 'Comfort Trips'] as $category)
                                    @php
                                        $levels = $all_settings
                                            ->where('category', $category)
                                            ->whereNotNull('level')
                                            ->pluck('level')
                                            ->unique();

                                    @endphp
                                    <div class="settings-tab" id="tab-{{ Str::slug($category) }}" style="display: none;">
                                        <h5>{{ $category }} Settings</h5>
                                        <table class="table table-hover">
                                            <thead>
                                                <tr>
                                                    <th>Label</th>
                                                    <th>Value</th>
                                                    <th>Unit</th>
                                                    <th>Action</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach ($all_settings->where('category', $category)->where('level', null)->sortBy('label') as $setting)
                                                    <tr onclick="window.open('{{ url('/admin-dashboard/setting/edit/' . $setting->id) }}', '_blank');"
                                                        style="cursor: pointer;">
                                                        <td>{!! highlight($setting->label, $search ?? '') !!}</td>
                                                        @php
                                                            $decoded = json_decode($setting->value, true);
                                                        @endphp
                                                        <td>{{ is_array($decoded) || is_object($decoded) ? '_' : $setting->value }}
                                                        </td>
                                                        <td>{{ $setting->unit }}</td>
                                                        <td>
                                                            @can('settings.edit')
                                                                <a href="{{ url('/admin-dashboard/setting/edit/' . $setting->id) }}"
                                                                    target="_blank"onclick="event.stopPropagation();">
                                                                    <span class="bi bi-pen"
                                                                        style="font-size: 1rem; color: #000;"></span>
                                                                </a>
                                                            @endcan
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                    @if ($levels->count())
                                        @foreach ($levels as $i => $level)
                                            <div class="settings-tab"
                                                id="tab-{{ Str::slug($category) }}-{{ $level }}"
                                                style="display: none;">
                                                <h5>{{ $category }} Settings</h5>
                                                <p style="line-height: 22px;margin-bottom: .5rem;">Level
                                                    {{ $level }} Settings</p>
                                                <table class="table table-hover">
                                                    <thead>
                                                        <tr>
                                                            <th>Label</th>
                                                            <th>Value</th>
                                                            <th>Unit</th>
                                                            <th>Action</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        @foreach ($all_settings->where('category', $category)->where('level', $level)->sortBy('label') as $setting)
                                                            <tr onclick="window.open('{{ url('/admin-dashboard/setting/edit/' . $setting->id) }}', '_blank');"
                                                                style="cursor: pointer;">
                                                                <td>{!! highlight($setting->label, $search ?? '') !!}</td>
                                                                @php
                                                                    $decoded = json_decode($setting->value, true);
                                                                @endphp
                                                                <td>{{ is_array($decoded) || is_object($decoded) ? '_' : $setting->value }}
                                                                </td>
                                                                <td>{{ $setting->unit }}</td>
                                                                <td>
                                                                    <a href="{{ url('/admin-dashboard/setting/edit/' . $setting->id) }}"target="_blank"
                                                                        onclick="event.stopPropagation();">
                                                                        <span class="bi bi-pen"
                                                                            style="font-size: 1rem; color: #000;"></span>
                                                                    </a>
                                                                </td>
                                                            </tr>
                                                        @endforeach
                                                    </tbody>
                                                </table>
                                            </div>
                                        @endforeach
                                    @endif
                                @endforeach

                                {{-- <div style="text-align: center;">

                                    {!! $all_settings->appends(['search' => request('search'), 'category' => request('category')])->links('pagination::bootstrap-4') !!}
                                </div> --}}
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
    </script>
    <script>
        function showTab(category, x = null) {
            if (x == null) {
                const bars = document.querySelectorAll('.settings-bar');
                bars.forEach(bar => bar.style.display = 'none');
                const targetId2 = 'bar-' + category.toLowerCase().replace(/\s+/g, '-');
                const activeTab2 = document.getElementById(targetId2);
                if (activeTab2) activeTab2.style.display = 'inline-flex';
            }



            const tabs = document.querySelectorAll('.settings-tab');
            tabs.forEach(tab => tab.style.display = 'none');

            const targetId = 'tab-' + category.toLowerCase().replace(/\s+/g, '-');
            const activeTab = document.getElementById(targetId);
            if (activeTab) activeTab.style.display = 'block';


        }

        // اختياري: عرض أول تاب تلقائيًا عند التحميل
        window.addEventListener('DOMContentLoaded', () => {
            showTab('General');
        });
    </script>

    <script>
        // Set a timeout to hide the error or success message after 5 seconds
        setTimeout(function() {
            $('#errorAlert').fadeOut();
            $('#successAlert').fadeOut();
        }, 4000); // 5000 milliseconds = 5 seconds
    </script>
    <script>
        function toggleFilters() {
            var filterOptions = document.getElementById("filterOptions");
            if (filterOptions.style.display === "none") {
                filterOptions.style.display = "block";
            } else {
                filterOptions.style.display = "none";
            }
        }
    </script>
@endpush
