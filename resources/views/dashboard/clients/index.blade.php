@extends('dashboard.layout.app')
@section('title', 'Dashboard - clients')
@section('content')
    <style>
        .pagination {
            display: inline-flex;
        }

        .user-status {
            display: inline-block;
            width: 10px;
            height: 10px;
            border-radius: 50%;
            margin-left: -4%;
            margin-bottom: 4.65%;
        }

        .online {
            background-color: green;
        }

        .offline {
            background-color: gray;
        }


        /* Popup styles */
        .user-profile {
            position: relative;
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            cursor: pointer;
        }

        .avatar-preview {
            display: none;
            position: fixed;
            justify-content: center;
            /* Center horizontally */
            align-items: center;
            top: 50%;
            left: 50%;
            height: 600px;
            width: 800px;
            transform: translate(-50%, -50%);
            z-index: 1000;
            background-color: rgba(0, 0, 0, 0.8);
            padding: 10px;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }

        .avatar-preview img {

            width: 100%;
            height: 100%;
            border-radius: 5px;
        }

        .user-profile:hover .avatar-preview {
            display: block;
        }
    </style>
    <div class="content-wrapper">
        <div class="container-fluid">
            <div class="row">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-body">
                            <div>

                                <form id="searchForm" class="search-bar"
                                    style="margin-bottom:1%;margin-right:20px;margin-left:0px;"method="post"
                                    action="{{ route('clients') }}" enctype="multipart/form-data">
                                    @csrf
                                    <div style="display:flex;">
                                        <h5 class="card-title" style="width: 60%;">Clients</h5>
                                        <div style="display:flex;margin-bottom:1%;margin-left:0px;">
                                            <a class="btn btn-light px-5" type="button"
                                                href="{{ route('archived_clients') }}"
                                                style="margin:0% 0% 1% 1%; ">Archives</a>
                                            <button class="btn btn-light px-5" type="button"
                                                onclick="toggleFilters()"style="margin:0% 1% 1% 1%; ">Filter</button>
                                            <input type="text" class="form-control" placeholder="Enter keywords"
                                                name="search">
                                            <a href="javascript:void(0);" id="submitForm"><i class="icon-magnifier"></i></a>
                                        </div>

                                    </div>


                                    <div id="filterOptions" style="display: none; text-align:center;">
                                        <div style="display: flex;">
                                            <select class="form-control"style="width: 33%;margin: 0% 0% 0% 0%;"
                                                name="status">
                                                <option value="">Select Status</option>
                                                <option value="pending">Pending</option>
                                                <option value="confirmed">Confirmed</option>
                                                <option value="banned">Banned</option>
                                                <option value="blocked">Blocked</option>
                                                <!-- Add more options as needed -->
                                            </select>
                                        </div>


                                        <button class="btn btn-light px-5" style="margin-top:10px" type="submit">Apply
                                            Filters</button>
                                    </div>
                                </form>
                                {{-- <a  class="btn btn-light px-5" style="margin-bottom:1%; " href="{{route('add.user')}}">create</a> --}}
                            </div>

                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th scope="col">#</th>
                                            <th scope="col">Name</th>
                                            <th scope="col">Email</th>
                                            <th scope="col">Phone Number</th>
                                            <th scope="col">status</th>
                                            <th scope="col">Join Date</th>
                                            <th scope="col">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @if (!empty($all_users) && $all_users->count())
                                        @php
                                        $counter =
                                            ($all_users->currentPage() - 1) * $all_users->perPage() + 1;
                                    @endphp
                                            @foreach ($all_users as $user)
                                                <tr onclick="window.location='{{ url('/admin-dashboard/user/edit/' . $user->id) }}';"
                                                    style="cursor: pointer;">
                                                    <td>{{ $counter++ }}</td>
                                                    <td>
                                                        <span class="user-profile">
                                                            <img @if (getFirstMediaUrl($user, $user->avatarCollection) != null) src="{{ getFirstMediaUrl($user, $user->avatarCollection) }}" 
                                          @else 
                                              src="{{ asset('dashboard/user_avatar.png') }}" @endif
                                                                class="img-circle user-avatar" alt="user avatar">
                                                            <div class="avatar-preview">
                                                                <img @if (getFirstMediaUrl($user, $user->avatarCollection) != null) src="{{ getFirstMediaUrl($user, $user->avatarCollection) }}" 
                                              @else 
                                                  src="{{ asset('dashboard/user_avatar.png') }}" @endif
                                                                    alt="Avatar Preview">
                                                            </div>
                                                        </span>

                                                        <span
                                                            class="user-status {{ $user->is_online ? 'online' : 'offline' }}"></span>

                                                        {!! highlight($user->name, $search ?? '') !!}
                                                    </td>
                                                    <td>{!! highlight($user->email, $search ?? '') !!}</td>

                                                    <td>{!! highlight($user->phone, $search ?? '') !!}</td>
                                                   
                                                   
                                                        <td>
                                                            @if ($user->status == 'banned')
                                                                <span class="badge badge-secondary"
                                                                    style="background-color:rgb(61, 27, 255);width:100%;">Banned</span>
                                                            @elseif($user->status == 'confirmed')
                                                                <span class="badge badge-secondary"
                                                                    style="background-color:rgb(50, 134, 50);width:100%;">Confirmed</span>
                                                            @elseif($user->status == 'blocked')
                                                                <span class="badge badge-secondary"
                                                                    style="background-color:rgb(255,0,0);width:100%;">Blocked</span>
                                                            @endif
                                                        </td>
                                                  
                                                    <td>{{ $user->created_at->format('d.M.Y')  }}</td>
                                                    <td>



                                                        <a href="{{ url('/admin-dashboard/client/edit/' . $user->id) }}"
                                                            style="margin-right: 1rem;">
                                                            <span class="bi bi-pen"
                                                                style="font-size: 1rem; color: rgb(255,255,255);"></span>
                                                        </a>

                                                        {{-- <a href="{{url('/admin-dashboard/user/delete/'.$user->id)}}">
                                    <span class="bi bi-trash" style="font-size: 1rem; color: rgb(255,255,255);"></span>
                                  </a> --}}
                                                        <a
                                                            onclick='event.stopPropagation(); showConfirmationPopup("{{ url('/admin-dashboard/client/delete/' . $user->id) }}","{{ $user->name }}","{{ getFirstMediaUrl($user, $user->avatarCollection) ?? asset('dashboard/user_avatar.png') }}")'>
                                                            <span class="bi bi-trash"
                                                                style="font-size: 1rem; color: rgb(255,255,255);"></span>
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
                                    {!! $all_users->appends([
                                            'search' => request('search'),
                                            'status' => request('status'),
                                        ])->links('pagination::bootstrap-4') !!}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="overlay toggle-menu"></div>
        </div>
    </div>
    <div class="modal fade" id="confirmationPopup" tabindex="-1" role="dialog" aria-labelledby="exampleModalCenterTitle"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLongTitle" style="color:black;">Are you sure you want to
                        delete this client?</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body" style="color:black;">
                    <div class="form-group">
                        <div style="width: 100%;  display: flex;justify-content: center;">
                            <img id="deletedUserAvatar" src="{{ asset('dashboard/logo.png') }}" class="logo-icon"
                                alt="logo icon" style="width:100px;height:100px; border-radius: 50%;">
                        </div>
                        <div style="width: 100%;  display: flex;justify-content: center;">
                            <h5 class="logo-text"style="color:black;font-weight: bold;" id="deletedNameInput"></h5>

                        </div>
                    </div>
                    <button
                        onclick="hideConfirmationPopup()"style="background-color: #5f6360; color: white; padding: 10px 20px; border: none; cursor: pointer;width:48%;border-radius:10px;"><span
                            class="bi bi-x" style="font-size: 1rem; color: rgb(255,255,255);"></span> Cancele</button>
                    <button
                        onclick="deleteUser()"style="background-color: #f44336; color: white; padding: 10px 20px; border: none; cursor: pointer; margin-right: 10px; width:48%; border-radius:10px;"><span
                            class="bi bi-trash" style="font-size: 1rem; color: rgb(255,255,255);"></span> Delete</button>

                </div>
            </div>
        </div>
    </div>
@endsection
@push('scripts')
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        function showConfirmationPopup(deleteUrl, name, imgSrc) {

            document.getElementById('deletedNameInput').textContent = name;
            document.getElementById('deletedUserAvatar').src = imgSrc;
            const myModal = new bootstrap.Modal(document.getElementById('confirmationPopup'), {});
            myModal.show();
            // Set the delete URL in a data attribute to access it in the deleteUser function
            document.getElementById('confirmationPopup').setAttribute('data-delete-url', deleteUrl);
        }

        function hideConfirmationPopup() {

            var modal = document.getElementById('confirmationPopup');
            modal.classList.remove('show');
            modal.style.display = 'none';
            document.body.classList.remove('modal-open');
            document.getElementsByClassName('modal-backdrop')[0].remove();
        }

        function deleteUser() {
            const deleteUrl = document.getElementById('confirmationPopup').getAttribute('data-delete-url');
            window.location.href = deleteUrl;
        }
    </script>
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
@endpush
