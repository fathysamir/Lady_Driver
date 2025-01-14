@extends('dashboard.layout.app')
@section('title', 'Dashboard - trips')
@section('content')	
<style>
    .pagination{
        display: inline-flex;
    }
</style>
    <div class="content-wrapper">
        <div class="container-fluid">
            <div class="row">
                <div class="col-lg-12">
                    <div class="card">
                      <div class="card-body">
                        <div>
                            
                            <form id="searchForm" class="search-bar" style="margin-bottom:1%;margin-right:20px;margin-left:0px;"method="post" action="{{ route('trips') }}" enctype="multipart/form-data">
                                @csrf
                                <div style="display:flex;">
                                  <h5 class="card-title" style="width: 60%;">Trips</h5>
                                  <div style="display:flex;margin-bottom:1%;margin-left:0px;">
                                    <button class="btn btn-light px-5" type="button" onclick="toggleFilters()"style="margin:0% 1% 1% 1%; ">Filter</button>
                                    <input type="text" class="form-control" placeholder="Enter keywords" name="search">
                                    <a href="javascript:void(0);" id="submitForm"><i class="icon-magnifier"></i></a>
                                  </div>
                                  
                                </div>
                                
                                
                                <div id="filterOptions" style="display: none; text-align:center;">
                                  <div style="display: flex;">
                                    <select class="form-control" style="width: 23.5%; margin: 0% 1% 0% 0%;" name="user">
                                        <option value="">Select Client</option>
                                        @foreach($users as $user)
                                            <option value="{{$user->id}}">{{$user->name}}</option>
                                        @endforeach
                                        <!-- Add more options as needed -->
                                    </select>
                                    <select class="form-control" style="width: 23.5%; margin: 0% 1% 0% 1%;" name="driver">
                                      <option value="">Select Driver</option>
                                      @foreach($drivers as $driver)
                                          <option value="{{$driver->id}}">{{$driver->name}}</option>
                                      @endforeach
                                      <!-- Add more options as needed -->
                                  </select>
                                    
                                    
                                    
                                    <select class="form-control"style="width: 23.5%;margin: 0% 1% 0% 1%;" name="status">
                                        <option value="">Select Status</option>
                                        <option value="pending">Pending</option>
                                        <option value="in_progress">In Progress</option>
                                        <option value="completed">Completed</option>
                                        <option value="cancelled">Cancelled</option>
                                        <!-- Add more options as needed -->
                                    </select>
                                    <select class="form-control"style="width: 23.5%;margin: 0% 0% 0% 1%;" name="payment_status">
                                      <option value="">Select Payment Status</option>
                                      <option value="unpaid">Unpaid</option>
                                      <option value="online paid">Online Paid</option>
                                      <option value="cash paid">Cash Paid</option>
                                      
                                      <!-- Add more options as needed -->
                                  </select>
                                  </div>
                                  <div style="display: flex; margin-top:10px;">
                                  <select class="form-control"style="width: 23.5%;margin: 0% 1% 0% 0%;" name="type">
                                    <option value="">Select Type</option>
                                    <option value="individual">Individual</option>
                                    <option value="couple">Couple</option>
                                    <option value="group">Group</option>
                                    
                                    <!-- Add more options as needed -->
                                </select>
                                  
                                    <select class="form-control" style="width: 23.5%; margin: 0% 1% 0% 1%;" name="mark"id="markSelect">
                                        <option value="">Select Car Mark</option>
                                        @foreach($car_marks as $mark)
                                            <option value="{{$mark->id}}">{{$mark->en_name}} - {{$mark->ar_name}}</option>
                                        @endforeach
                                        <!-- Add more options as needed -->
                                    </select>
                                    <select class="form-control" style="width: 23.5%; margin: 0% 1% 0% 1%; display:none;" name="model"id="modelSelect">
                                        <option value="">Select Car Model</option>
                                        
                                        <!-- Add more options as needed -->
                                    </select>
                                    <div class="form-group"style="width: 23.5%; margin: 0% 0% 0% 1%;">
                                      <input type="date" name="created_date" class="form-control"  placeholder="Enter Created Date" style="width:100%;">
                                      
                                    </div>
                                    <div class="form-group py-2"style="width: 23.5%; margin: 0% 0% 0% 0%;">
                                        <div class="icheck-material-white">
                                            <input type="checkbox"name="air_conditioned" id="user-checkbox2"/>
                                            <label for="user-checkbox2">Air conditioned</label>
                                        </div>
                                      </div>
                                    
                                  </div>
                                    
                                    <button class="btn btn-light px-5" style="margin-top:10px" type="submit">Apply Filters</button>
                                </div>
                            </form>
                            {{-- <a  class="btn btn-light px-5" style="margin-bottom:1%; " href="{{route('add.user')}}">create</a> --}}
                        </div>
                       
                        <div class="table-responsive">
                        <table class="table table-hover">
                          <thead>
                            <tr>
                              
                              <th scope="col">Trip Code</th>
                              <th scope="col">Client</th>
                              <th scope="col">Driver</th>
                              <th scope="col">Car</th>
                              <th scope="col">Created at</th>
                              <th scope="col">status</th>
                              <th scope="col">Payment status</th>
                              <th scope="col">Action</th>
                            </tr>
                          </thead>
                          <tbody>

                            @if(!empty($all_trips) && $all_trips->count())
                            @foreach($all_trips as $trip)
                            <tr onclick="window.location='{{url('/admin-dashboard/trip/view/'.$trip->id)}}';" style="cursor: pointer;">
                              <td>{!! highlight($trip->code, $search ?? '') !!}</td>
                                <td><span class="user-profile"><img @if(getFirstMediaUrl($trip->user,$trip->user->avatarCollection)!=null) src="{{getFirstMediaUrl($trip->user,$trip->user->avatarCollection)}}" @else src="{{asset('dashboard/user_avatar.png')}}" @endif class="img-circle" alt="user avatar"></span> {{$trip->user->name}}</td>
                                
                                <td><span class="user-profile"><img @if(getFirstMediaUrl($trip->car->owner,$trip->car->owner->avatarCollection)!=null) src="{{getFirstMediaUrl($trip->car->owner,$trip->car->owner->avatarCollection)}}" @else src="{{asset('dashboard/user_avatar.png')}}" @endif class="img-circle" alt="user avatar"></span> {{$trip->car->owner->name}}</td>
                                <td>{{$trip->car->mark->name}} - {{$trip->car->model->name}} ({{$trip->car->year}})</td>
                                <td>{{date('Y-m-d h:i a',strtotime($trip->created_at))}}</td>
                               
                                <td>@if($trip->status=='pending') <span class="badge badge-secondary" style="background-color:rgb(143, 118, 9); width:100%;">Pending</span> @elseif($trip->status=='completed') <span class="badge badge-secondary" style="background-color:rgb(50, 134, 50);width:100%;">Completed</span> @elseif($trip->status=='in_progress') <span class="badge badge-secondary" style="background-color:rgb(52, 40, 223);width:100%;">In Progress</span> @else<span class="badge badge-secondary" style="background-color:rgb(255,0,0);width:100%;">Cancelled</span> @endif</td>
                                <td>{{$trip->payment_status}}</td>
                                <td>
                                  
                                  
                                 
                                  <a href="{{url('/admin-dashboard/trip/view/'.$trip->id)}}" style="margin-right: 1rem;">
                                    <span  class="bi bi-eye" style="font-size: 1rem; color: rgb(255,255,255);"></span>
                                  </a>
                                 
                                  
                                 
                                  
                                </td>
                              </tr>
                            @endforeach
                          @else
                              <tr>
                                <td>There are no Trips.</td>
                              </tr>
                          @endif
                          </tbody>
                        </table>
                        <div style="text-align: center;">
                          {!! $all_trips->appends(['search' => request('search'),'driver'=>request('driver'),'user'=>request('user'),'type'=>request('type'),'status'=>request('status'),'payment_status'=>request('payment_status'),'mark'=>request('mark'),'model'=>request('model'),'created_date'=>request('created_date'),'air_conditioned' => request('air_conditioned'),])->links("pagination::bootstrap-4") !!}
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
      function toggleFilters() {
          var filterOptions = document.getElementById("filterOptions");
          if (filterOptions.style.display === "none") {
              filterOptions.style.display = "block";
          } else {
              filterOptions.style.display = "none";
          }
      }
  
      
  </script>
  <script>
    document.getElementById('markSelect').addEventListener('change', function() {
        var markId = this.value;
        var modelSelect = document.getElementById('modelSelect');

        // Make an AJAX request to fetch car models based on the selected car make
        // Adjust the URL and parameters according to your backend implementation
        fetch('/admin-dashboard/getModels?markId=' + markId)
            .then(response => response.json())
            .then(data => {
                modelSelect.innerHTML = '<option value="">Select Car Model</option>';
                data.forEach(model => {
                    modelSelect.innerHTML += `<option value="${model.id}">${model.en_name} - ${model.ar_name}</option>`;
                });
                modelSelect.style.display = 'block'; // Show the model select
            })
            .catch(error => console.error('Error:', error));
    });
</script>
@endpush
