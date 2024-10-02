@extends('dashboard.layout.app')
@section('title', 'Dashboard - edit car')
@section('content')	
    <div class="content-wrapper">
        <div class="container-fluid">

            <div class="row mt-3">
                <div class="col-lg-12">
                    <div class="card">
                      <div class="card-body">
                      <div class="card-title">Edit Client Car</div>
                      <hr>
                       <form method="post" action="{{ route('update.car', ['id' => $car->id]) }}" enctype="multipart/form-data">
                        @csrf
                        <div class="form-group"style="text-align: center;">
                          <div>
                            <img style="border-radius: 5%;width:60%;" @if($car->image!=null) src="{{$car->image}}" @else src="{{asset('dashboard/car_avatar.png')}}" @endif class="img-circle" alt="user avatar">
                          </div>
                          <h3 style="margin-top:10px;">{{$car->mark->en_name}} - {{$car->mark->ar_name}}</h3>
                          <h3 style="margin-top:10px;">{{$car->model->en_name}} - {{$car->model->ar_name}} ({{$car->year}})</h3>
                        </div>
                      
                      <div class="form-group">
                        <label>Driver : <a href="{{url('/admin-dashboard/user/edit/'.$car->owner->id)}}">{{ucwords($car->owner->name)}}</a></label>
                      </div>
                      <div class="form-group">
                        <label>Car Plate : {{$car->car_plate}}</label>
                      </div>
                      <div class="form-group">
                        <label>Car Color : {{$car->color}}</label>
                      </div>
                      <div class="form-group">
                        <label>License Expire Date : {{$car->license_expire_date}}   </label>@if($car->license_expire_date<date('Y-m-d'))<span class="badge badge-secondary" style="background-color:rgb(255,0,0);width:10%; margin-left:1%;">Expired</span>@endif
                      </div>
                      <div id="map" style="height: 800px; margin: 20px 0px 20px 0px;"></div>
                      <div class="form-group" style="display: flex; align-items: center;">
                        <h4 style="margin-right: 10px;">Images</h4>
                        <hr style="flex: 1; margin: 0;">
                      </div>
                      
                      <div class="form-group"style="display: flex;">
                        <label style="width: 20%">Image : </label>  <img style="margin: 0px 10px 0px 10px; border-radius:10px;width:30%" src="{{$car->image}}">
                      </div>
                      <div class="form-group"style="display: flex;">
                        <label style="width: 20%">Plate Image : </label>  <img style="margin: 0px 10px 0px 10px; border-radius:10px;width:30%;" src="{{$car->plate_image}}">
                      </div>
                      <div class="form-group"style="display: flex;">
                        <label style="width: 20%">License Front Image : </label>  <img style="margin: 0px 10px 0px 10px; border-radius:10px;width:30%;" src="{{$car->license_front_image}}">
                      </div>
                      <div class="form-group"style="display: flex;">
                        <label style="width: 20%">License Back Image : </label>  <img style="margin: 0px 10px 0px 10px; border-radius:10px;width:30%;" src="{{$car->license_back_image}}">
                      </div>
                      <div class="form-group">
                        <label>Status</label>
                         
                          <select class="form-control" name="status">
                              <option value="pending" @if($car->status=='pending') selected @endif>Pending</option>
                              <option value="confirmed" @if($car->status=='confirmed') selected @endif>Confirmed</option>
                              <option value="blocked" @if($car->status=='blocked') selected @endif>Blocked</option>
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
<script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyATC_r7Y-U6Th1RQLHWJv2JcufJb-x2VJ0"></script>
<script>
  var map, marker;

  function initMap() {
      // Initial location of the car
      var userLocation = { lat: {{ $car->lat }}, lng: {{ $car->lng }} };
      map = new google.maps.Map(document.getElementById('map'), {
          zoom: 12,
          center: userLocation
      });

      // Create a marker at the car's initial position
      marker = new google.maps.Marker({
          position: userLocation,
          map: map
      });

      // Start updating the car's location every 5 seconds
      setInterval(updateCarLocation, 3000);
  }

  function updateCarLocation() {
      // Fetch the updated car location using AJAX
      // This assumes you have an endpoint like '/car-location/{{ $car->id }}' that returns the updated lat/lng
      fetch('/admin-dashboard/car-location/{{ $car->id }}')
        .then(response => response.json())
        .then(data => {
            var newLocation = { lat: data.lat, lng: data.lng };

            // Move the marker to the new location
            marker.setPosition(newLocation);

            // Optionally, center the map on the new location
            map.setCenter(newLocation);
        })
        .catch(error => console.error('Error fetching car location:', error));
  }

  // Initialize the map
  window.onload = initMap;
</script>
@endpush
