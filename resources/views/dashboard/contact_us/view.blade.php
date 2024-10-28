@extends('dashboard.layout.app')
@section('title', 'Dashboard - view contact us')
@section('content')	
    <div class="content-wrapper">
        <div class="container-fluid">

            <div class="row mt-3">
                <div class="col-lg-12">
                    <div class="card">
                      <div class="card-body">
                      <div class="card-title">Contact Us</div>
                      <hr>
                       <form method="post" action="{{route('update.contact_us',['id' => $contact_us->id])}}" enctype="multipart/form-data">
                        @csrf
                        <div class="form-group">
                            <label>Name</label>
                            <input type="text" disabled class="form-control"  value="{{ $contact_us->name }}">

                        </div>
                        <div class="form-group">
                            <label>Email</label>
                            <input type="text" disabled class="form-control"  value="{{ $contact_us->email }}">

                        </div>
                        <div class="form-group">
                            <label>Phone Number</label>
                            <input type="text" disabled class="form-control"  value="{{ $contact_us->phone }}">

                        </div>
                        <div class="form-group">
                            <label>Subject</label>
                            <input type="text" disabled class="form-control"  value="{{ $contact_us->subject }}">

                        </div>
                        <div class="form-group">
                            <label>Massage</label>
                            <textarea disabled class="form-control" rows="5">{{ $contact_us->message }}</textarea>
                        </div>
                        <div class="form-group">
                            <label>Reply</label>
                            <textarea name="reply" class="form-control" rows="5" placeholder="Reply On Message"></textarea>
                        </div>
                      
                      
                      <div class="form-group">
                       <button type="submit" class="btn btn-light px-5"><i class="icon-lock"></i> Save</button>
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

@endpush
