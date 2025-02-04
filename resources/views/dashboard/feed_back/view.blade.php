@extends('dashboard.layout.app')
@section('title', 'Dashboard - view feed back')
@section('content')
    <div class="content-wrapper">
        <div class="container-fluid">

            <div class="row mt-3">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-body">
                            <div class="card-title">Feed Back</div>
                            <hr>

                            <div class="form-group">
                                <label>Name</label>
                                <input type="text" disabled class="form-control" value="{{ $feed_back->user->name }}">

                            </div>
                            <div class="form-group">
                                <label>Email</label>
                                <input type="text" disabled class="form-control" value="{{ $feed_back->user->email }}">

                            </div>
                            <div class="form-group">
                                <label>Phone Number</label>
                                <input type="text" disabled class="form-control" value="{{ $feed_back->user->phone }}">

                            </div>

                            <div class="form-group">
                                <label>Feed Back</label>
                                <textarea disabled class="form-control" rows="5">{{ $feed_back->feed_back }}</textarea>
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
