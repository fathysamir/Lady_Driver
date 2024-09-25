@extends('dashboard.layout.app')
@section('title', 'Dashboard - edit user')
@section('content')	
    <div class="content-wrapper">
        <div class="container-fluid">

            <div class="row mt-3">
                <div class="col-lg-12">
                    <div class="card">
                      <div class="card-body">
                      <div class="card-title">Edit Client</div>
                      <hr>
                       <form method="post" action="{{ route('update.user', ['id' => $user->id]) }}" enctype="multipart/form-data">
                        @csrf
                      <div class="form-group">
                       <label>Name</label>
                        <input type="text" disabled class="form-control"  placeholder="Enter Name"value="{{$user->name}}">
                      </div>
                      <div class="form-group">
                        <label>Email</label>
                        <input type="email" disabled class="form-control"  placeholder="Enter Email"value="{{$user->email}}">
                      </div>
                      <div class="form-group">
                        <label>Phone Number</label>
                        <input type="number" disabled class="form-control"  placeholder="Enter Phone Number"value="{{$user->phone}}">
                      </div>
                      <div class="form-group">
                        <label>Mode : {{ucwords($user->mode)}}</label>
                      </div>
                      <div class="form-group">
                        <label>National ID : {{$user->national_id}}</label>
                      </div>
                      <div class="form-group">
                        <label>Address : {{$user->address}}</label>
                      </div>
                      <div class="form-group">
                        <label>Birth Date : {{$user->birth_date}}</label>
                      </div>
                      <div class="form-group">
                        <label>Status</label>
                         
                          <select class="form-control" name="status">
                              <option value="pending" @if($user->status=='pending') selected @endif>Pending</option>
                              <option value="confirmed" @if($user->status=='confirmed') selected @endif>Confirmed</option>
                              <option value="blocked" @if($user->status=='blocked') selected @endif>Blocked</option>
                          </select>
                       </div>
                      
                      <div class="form-group">
                       <button type="submit" class="btn btn-light px-5"><i class="icon-lock"></i>Save</button>
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
   
</script>
@endpush
