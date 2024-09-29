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
                        <div class="form-group"style="text-align: center;">
                          <div>
                            <img style="border-radius: 50%;width:200px;height:200px;" @if($user->image!=null) src="{{$user->image}}" @else src="{{asset('dashboard/user_avatar.png')}}" @endif class="img-circle" alt="user avatar">
                          </div>
                          <h3>{{$user->name}}</h3>
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
                      @if($user->mode == 'driver')
                      <div class="form-group" style="display: flex; align-items: center;">
                        <h4 style="margin-right: 10px;">Driving License</h4>
                        <hr style="flex: 1; margin: 0;">
                      </div>
                      
                      <div class="form-group">
                        <label>License Number : {{$user->driving_license->license_num}}</label>
                      </div>
                      <div class="form-group">
                        <label>Expire Date : {{$user->driving_license->expire_date}}</label>
                      </div>
                      <div class="form-group"style="display: flex;">
                        <label>Front Image : </label>  <img style="margin: 0px 10px 0px 10px; border-radius:10px;" src="{{$user->driving_license->front_image}}">
                      </div>
                      <div class="form-group"style="display: flex;">
                        <label>Back Image : </label>  <img style="margin: 0px 10px 0px 10px; border-radius:10px;" src="{{$user->driving_license->back_image}}">
                      </div>
                      @endif
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
