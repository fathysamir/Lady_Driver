@extends('dashboard.layout.app')
@section('title', 'Dashboard - users')
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
                            <h5 class="card-title" style="width: 80%;">Users</h5>
                            <form id="searchForm" class="search-bar" style="margin-bottom:1%;margin-right:20px;margin-left:0px;"method="post" action="{{ route('users') }}" enctype="multipart/form-data">
                                @csrf
                                <input type="text" class="form-control" placeholder="Enter keywords" name="search">
                                <a href="javascript:void(0);" id="submitForm"><i class="icon-magnifier"></i></a>
                            </form>
                            {{-- <a  class="btn btn-light px-5" style="margin-bottom:1%; " href="{{route('add.user')}}">create</a> --}}
                        </div>
                       
                        <div class="table-responsive">
                        <table class="table table-hover">
                          <thead>
                            <tr>
                              
                              <th scope="col">Name</th>
                              <th scope="col">Email</th>
                              <th scope="col">Phone Number</th>
                              <th scope="col">Mode</th>
                              <th scope="col">Role</th>
                              <th scope="col">status</th>
                              <th scope="col">Action</th>
                            </tr>
                          </thead>
                          <tbody>
                            @if(!empty($all_users) && $all_users->count())
                            @foreach($all_users as $user)
                              <tr>
                                <td><span class="user-profile"><img @if(getFirstMediaUrl($user,$user->avatarCollection)!=null) src="{{getFirstMediaUrl($user,$user->avatarCollection)}}" @else src="{{asset('dashboard/user_avatar.png')}}" @endif class="img-circle" alt="user avatar"></span> {{$user->name}}</td>
                                <td>{{$user->email}}</td>
                                
                                <td>{{$user->phone}}</td>
                                <td>{{ucwords($user->mode)}}</td>
                                <td>{{$user->roles->first()->name}}</td>
                                <td>@if($user->status=='pending') <span class="badge badge-secondary" style="background-color:rgb(143, 118, 9); width:100%;">Pending</span> @elseif($user->status=='confirmed') <span class="badge badge-secondary" style="background-color:rgb(50, 134, 50);width:100%;">Confirmed</span> @else <span class="badge badge-secondary" style="background-color:rgb(255,0,0);width:100%;">Blocked</span> @endif</td>
                                
                                <td>
                                  
                                  
                                 
                                  <a href="{{url('/admin-dashboard/user/edit/'.$user->id)}}" style="margin-right: 1rem;">
                                    <span  class="bi bi-pen" style="font-size: 1rem; color: rgb(255,255,255);"></span>
                                  </a>
                                 
                                  <a href="{{url('/admin-dashboard/user/delete/'.$user->id)}}">
                                    <span class="bi bi-trash" style="font-size: 1rem; color: rgb(255,255,255);"></span>
                                  </a>
                                 
                                  
                                </td>
                              </tr>
                            @endforeach
                          @else
                              <tr>
                                <td>There are no Clients.</td>
                              </tr>
                          @endif
                          </tbody>
                        </table>
                        <div style="text-align: center;">
                        {!! $all_users->links("pagination::bootstrap-4") !!}
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
@endpush
