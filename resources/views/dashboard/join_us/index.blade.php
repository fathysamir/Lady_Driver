@extends('dashboard.layout.app')
@section('title', 'Dashboard - join us')
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
                                <h5 class="card-title" style="width: 89%;">Join Us</h5>

                                <a class="btn btn-light px-5" style="margin-bottom:1%; "
                                    href="{{ url('/admin-dashboard/join_us?export=excel') }}">Export <i
                                        class="bi bi-download"></i> </a>
                            </div>

                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>

                                            <th scope="col">Emails</th>



                                        </tr>
                                    </thead>
                                    <tbody>
                                        @if (!empty($emails) && $emails->count())
                                            @foreach ($emails as $email)
                                                <tr>
                                                    <td>{{ $email->email }}</td>




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
                                    {!! $emails->links('pagination::bootstrap-4') !!}
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
@endpush
