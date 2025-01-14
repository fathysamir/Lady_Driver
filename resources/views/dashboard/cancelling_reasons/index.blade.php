@extends('dashboard.layout.app')
@section('title', 'Dashboard - Trip Cancellation Reasons')
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
                            
                            <form id="searchForm" class="search-bar" style="margin-bottom:1%;margin-right:20px;margin-left:0px;"method="post" action="{{ route('reasons-cancelling-trips') }}" enctype="multipart/form-data">
                                @csrf
                                <div style="display:flex;">
                                  <h5 class="card-title" style="width: 60%;">Trip Cancellation Reasons</h5>
                                  
                                  <div style="display:flex;margin-bottom:1%;margin-left:0px;">
                                    <a  class="btn btn-light px-5" style="margin-bottom:1%; " href="{{route('add.reason')}}">create</a>
                                    <button class="btn btn-light px-5" type="button" onclick="toggleFilters()"style="margin:0% 1% 1% 1%; ">Filter</button>
                                    <input type="text" class="form-control" placeholder="Enter keywords" name="search">
                                    <a href="javascript:void(0);" id="submitForm"><i class="icon-magnifier"></i></a>
                                  </div>
                                  
                                  
                                </div>
                                
                                
                                <div id="filterOptions" style="display: none; text-align:center;">
                                    <div style="display: flex; justify-content: center; align-items: center;">
                                        <select class="form-control" style="width: 33%; margin: 0% 1% 0% 0;" name="type">
                                            <option value="">Select Category</option>
                                            <option value="client">Client</option>
                                            <option value="driver">Driver</option>
                                            <!-- Add more options as needed -->
                                        </select>
                                        <select class="form-control" style="width: 33%; margin: 0% 0% 0% 1;" name="value_type">
                                            <option value="">Select Value Type</option>
                                            <option value="fixed">Fixed</option>
                                            <option value="ratio">Ratio</option>
                                            <!-- Add more options as needed -->
                                        </select>
                                    </div>
                                    
                                    
                                    <button class="btn btn-light px-5" style="margin-top:10px" type="submit">Apply Filters</button>
                                </div>
                            </form>
                            {{-- <a  class="btn btn-light px-5" style="margin-bottom:1%; " href="{{route('add.user')}}">create</a> --}}
                        </div>
                       
                        @if(session('error'))
                        <div id="errorAlert" class="alert alert-danger" style="padding-top:5px;padding-bottom:5px; padding-left: 10px; background-color:brown;border-radius: 20px; color:beige;">
                            {{ session('error') }}
                        </div>
                    @endif
                    
                    @if(session('success'))
                        <div id="successAlert" class="alert alert-success"style="padding-top:5px;padding-bottom:5px; padding-left: 10px; background-color:green;border-radius: 20px; color:beige;">
                            {{ session('success') }}
                        </div>
                    @endif

                        <div class="table-responsive">
                        <table class="table table-hover">
                          <thead>
                            <tr>
                              
                              <th scope="col">Reason</th>
                             
                              <th scope="col">Category</th>
                              <th scope="col">Value Type</th>
                              <th scope="col">Value</th>

                              <th scope="col">Action</th>
                            </tr>
                          </thead>
                          <tbody>
                            @if(!empty($all_reasons) && $all_reasons->count())
                            @foreach($all_reasons as $reason)
                            <tr onclick="window.location='{{url('/admin-dashboard/reason-cancelling-trip/edit/'.$reason->id)}}';" style="cursor: pointer;">
                              <td>{!! highlight($reason->en_reason, $search ?? '') !!}<br> {!! highlight($reason->ar_reason, $search ?? '') !!}</td>
                                <td>{{ucwords($reason->type)}}</td>
                                <td>{{ucwords($reason->value_type)}}</td>
                                <td>{{$reason->value}}</td>
                                <td>
                                  
                                  
                                  
                                  <a href="{{url('/admin-dashboard/reason-cancelling-trip/edit/'.$reason->id)}}" style="margin-right: 1rem;">
                                    <span  class="bi bi-pen" style="font-size: 1rem; color: rgb(255,255,255);"></span>
                                  </a>
                                 
                                  <a href="{{url('/admin-dashboard/reason-cancelling-trip/delete/'.$reason->id)}}">
                                    <span class="bi bi-trash" style="font-size: 1rem; color: rgb(255,255,255);"></span>
                                  </a>
                                 
                                 
                                  
                                </td>
                              </tr>
                            @endforeach
                          @else
                              <tr>
                                <td>There are no Trip Cancelled Reason.</td>
                              </tr>
                          @endif
                          </tbody>
                        </table>
                        <div style="text-align: center;">
                        
                        {!! $all_reasons->appends(['search' => request('search'),'type' => request('type'),'value_type'=> request('value_type')])->links("pagination::bootstrap-4") !!}
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
