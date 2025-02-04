@extends('dashboard.layout.app')
@section('title', 'Dashboard - Feed Back')
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
                            <div style="display: flex;">
                                <h5 class="card-title" style="width: 89%;">Feed Back</h5>
                            </div>


                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>

                                            <th scope="col">Client Name</th>
                                            <th scope="col">Email</th>
                                            <th scope="col">Phone Number</th>

                                            <th scope="col">Feed Back</th>

                                            <th scope="col">Action</th>

                                        </tr>
                                    </thead>
                                    <tbody>
                                        @if (!empty($all_feed_back) && $all_feed_back->count())
                                            @foreach ($all_feed_back as $feed_back)
                                                <tr onclick="window.location='{{ url('/admin-dashboard/feed_back/view/' . $feed_back->id) }}';"
                                                    style="cursor: pointer;">
                                                    <td>{{ $feed_back->user->name }}</td>
                                                    <td>{{ $feed_back->user->email }}</td>
                                                    <td>{{ $feed_back->user->phone }}</td>

                                                    <td>
                                                        @if (strlen($feed_back->feed_back) > 50)
                                                            {{ substr($feed_back->feed_back, 0, 50) }}...
                                                        @else
                                                            {{ $feed_back->feed_back }}
                                                        @endif
                                                    </td>

                                                    <td>

                                                        <a href="{{ url('/admin-dashboard/feed_back/view/' . $feed_back->id) }}"
                                                            style="margin-right: 1rem;">
                                                            <span class="bi bi-eye"
                                                                style="font-size: 1rem; color: rgb(255,255,255);"
                                                                title="View"></span>
                                                        </a>

                                                    </td>
                                                </tr>
                                            @endforeach
                                        @else
                                            <tr>
                                                <td>There are nothing.</td>
                                            </tr>
                                        @endif
                                    </tbody>
                                </table>
                                <div style="text-align: center;">
                                    {!! $all_feed_back->links('pagination::bootstrap-4') !!}
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
    </script>
    <script>
        // Set a timeout to hide the error or success message after 5 seconds
        setTimeout(function() {
            $('#errorAlert').fadeOut();
            $('#successAlert').fadeOut();
        }, 4000); // 5000 milliseconds = 5 seconds
    </script>
@endpush
