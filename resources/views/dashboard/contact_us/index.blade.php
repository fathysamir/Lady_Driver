@extends('dashboard.layout.app')
@section('title', 'Dashboard - contact us')
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
                        <div style="display: flex;">
                            <h5 class="card-title" style="width: 89%;">Contact Us</h5>
                            <form id="searchForm" class="search-bar" style="margin-bottom:1%;margin-left:20px;margin-right:0px;"method="post" action="{{ route('contact_us') }}" enctype="multipart/form-data">
                              @csrf
                              <input type="text" class="form-control" placeholder="Enter keywords" name="search">
                              <a href="javascript:void(0);" id="submitForm"><i class="icon-magnifier"></i></a>
                          </form>
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
                              <td></td>
                              <th scope="col">Name</th>
                              <th scope="col">Email</th>
                              <th scope="col">Phone Number</th>
                              
                              <th scope="col">Subject</th>
                              <th scope="col">Message</th>
                              <th scope="col">Action</th>
                             
                            </tr>
                          </thead>
                          <tbody>
                            @if(!empty($all_contact_us) && $all_contact_us->count())
                            @foreach($all_contact_us as $contact_us)
                              <tr>
                                <td>@if($contact_us->seen=='0') <span class="badge badge-secondary" style="background-color:rgb(50, 134, 50);width:100%;">New</span> @endif</td>
                                <td>{{$contact_us->name}}</td>
                                <td>{{$contact_us->email}}</td>
                                <td>{{$contact_us->phone}}</td>
                                <td>{{$contact_us->subject}}</td>
                                <td>@if(strlen($contact_us->message)>50)
                                  {{substr($contact_us->message,0,50)}}...
                                 @else
                                 {{$contact_us->message}}
                                 @endif</td>
                                
                                 <td>

                                  <a href="{{url('/admin-dashboard/contact_us/view/'.$contact_us->id)}}" style="margin-right: 1rem;">
                                    <span  class="bi bi-eye" style="font-size: 1rem; color: rgb(255,255,255);" title="View"></span>
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
                          {!! $all_contact_us->appends(['search' => request('search')])->links("pagination::bootstrap-4") !!}
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
