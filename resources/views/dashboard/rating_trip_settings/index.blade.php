@extends('dashboard.layout.app')
@section('title', 'Dashboard - Rating Trip Settings')
@section('content')
    <style>
        .pagination {
            display: inline-flex;
        }
        .clickable-row {
            cursor: pointer;
        }
        .clickable-row:hover {
            background-color: rgba(255, 255, 255, 0.1);
        }
    </style>
    <div class="content-wrapper">
        <div class="container-fluid">
            <div class="row">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-body">
                            <div>
                                <form id="searchForm" class="search-bar"
                                    style="margin-bottom:1%;margin-right:20px;margin-left:0px;" method="get"
                                    action="{{ route('ratingtripsettings') }}">
                                    <div style="display:flex;">
                                        <h5 class="card-title" style="width: 60%;">Rating Trip Settings</h5>

                                        <div style="display:flex;margin-bottom:1%;margin-left:0px;">
                                            <a class="btn btn-light px-5" style="margin-bottom:1%; "
                                                href="{{ route('create.rating') }}">Create</a>
                                            <button class="btn btn-light px-5" type="button"
                                                onclick="toggleFilters()"style="margin:0% 1% 1% 1%; ">Filter</button>
                                            <input type="text" class="form-control" placeholder="Enter keywords"
                                                name="search" value="{{ request('search') }}">
                                            <a href="javascript:void(0);" id="submitForm"><i class="icon-magnifier"></i></a>
                                        </div>
                                    </div>

                                    <div id="filterOptions" style="display: none; text-align:center;">
                                        <div style="display: flex; justify-content: center; align-items: center;">
                                            <select class="form-control" style="width: 33%; margin: 0% 1% 0% 0;"
                                                name="category">
                                                <option value="">Select Category</option>
                                                <option value="client" @if(request('category') == 'client') selected @endif>Client</option>
                                                <option value="driver" @if(request('category') == 'driver') selected @endif>Driver</option>
                                            </select>
                                            <select class="form-control" style="width: 33%; margin: 0% 1% 0% 0;"
                                                name="star_count">
                                                <option value="">Select Star Count</option>
                                                <option value="1" @if(request('star_count') == '1') selected @endif>1 Star</option>
                                                <option value="2" @if(request('star_count') == '2') selected @endif>2 Stars</option>
                                                <option value="3" @if(request('star_count') == '3') selected @endif>3 Stars</option>
                                                <option value="4" @if(request('star_count') == '4') selected @endif>4 Stars</option>
                                                <option value="5" @if(request('star_count') == '5') selected @endif>5 Stars</option>
                                            </select>
                                        </div>

                                        <button class="btn btn-light px-5" style="margin-top:10px" type="submit">Apply
                                            Filters</button>
                                    </div>
                                </form>
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
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th scope="col">Label</th>
                                            <th scope="col">Star Count</th>
                                            <th scope="col">Category</th>
                                            <th scope="col">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @if (!empty($settings) && $settings->count())
                                            @foreach ($settings as $setting)
                                                <tr class="clickable-row" data-href="{{ route('edit.rating', $setting->id) }}">
                                                    <td>{!! highlight($setting->label, request('search') ?? '') !!}</td>
                                                    <td>{{ $setting->star_count }}</td>
                                                    <td>{!! highlight(ucfirst($setting->category), request('search') ?? '') !!}</td>
                                                    <td>
                                                        <a href="{{ route('edit.rating', $setting->id) }}"
                                                            style="margin-right: 1rem;" onclick="event.stopPropagation();">
                                                            <span class="bi bi-pen"
                                                                style="font-size: 1rem; color: rgb(255,255,255);"></span>
                                                        </a>

                                                        <form action="{{ route('delete.rating', $setting->id) }}"
                                                            method="POST"
                                                            style="display: inline-block;"
                                                            onclick="event.stopPropagation();"
                                                            onsubmit="return confirm('Are you sure you want to delete this setting?');">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" style="background: none; border: none; padding: 0; cursor: pointer;">
                                                                <span class="bi bi-trash"
                                                                    style="font-size: 1rem; color: rgb(255,255,255);"></span>
                                                            </button>
                                                        </form>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        @else
                                            <tr>
                                                <td colspan="4" style="text-align: center;">There are no Rating Trip Settings.</td>
                                            </tr>
                                        @endif
                                    </tbody>
                                </table>
                                <div style="text-align: center;">
                                    {!! $settings->appends([
                                        'search' => request('search'),
                                        'category' => request('category'),
                                        'star_count' => request('star_count'),
                                        'label' => request('label')
                                    ])->links('pagination::bootstrap-4') !!}
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
            // Search submit
            $('#submitForm').on('click', function() {
                $('#searchForm').submit();
            });

            //row clickable
            $('.clickable-row').on('click', function() {
                window.location = $(this).data('href');
            });

            @if(request('category') || request('star_count') || request('label'))
                $('#filterOptions').show();
            @endif
        });
    </script>

    <script>
        setTimeout(function() {
            $('#errorAlert').fadeOut();
            $('#successAlert').fadeOut();
        }, 4000);
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