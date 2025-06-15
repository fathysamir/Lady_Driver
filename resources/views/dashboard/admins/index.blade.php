@extends('dashboard.layout.app')
@section('title', 'Dashboard - admins')
@section('content')
    <style>
        .pagination {
            display: inline-flex;
        }

        .user-status {
            position: absolute;
            display: inline-block;
            width: 10px;
            height: 10px;
            border-radius: 50%;
            margin-left: -1%;
            margin-bottom: 8%;
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
            position: relative;

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
                            <div style="display: flex;">
                                <h5 class="card-title" style="width: 46%;">Admins</h5>
                                <a class="btn btn-light px-5" style="margin-bottom:1%; "
                                    href="{{ route('add.admin') }}">create New Admin</a>
                                <form id="searchForm" class="search-bar"
                                    style="margin-bottom:1%;margin-left:20px;margin-right:0px;"method="post"
                                    action="{{ route('admins') }}" enctype="multipart/form-data">
                                    @csrf
                                    <input type="text" class="form-control" placeholder="Enter keywords" name="search">
                                    <a href="javascript:void(0);" id="submitForm"><i class="icon-magnifier"></i></a>
                                </form>



                            </div>
                            @if (session('error'))
                                <div id="errorAlert" class="alert alert-danger"
                                    style="padding-top:5px;padding-bottom:5px; padding-left: 10px; background-color:brown;border-radius: 20px; color:beige;">
                                    {{ session('error') }}
                                </div>
                            @endif

                            @if (session('success'))
                                <div id="successAlert"
                                    class="alert alert-success"style="padding-top:5px;padding-bottom:5px; padding-left: 10px; background-color:green;border-radius: 20px; color:beige;">
                                    {{ session('success') }}
                                </div>
                            @endif
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th scope="col">#</th>
                                            <th scope="col">Name</th>
                                            <th scope="col">Email</th>
                                            <th scope="col">Phone Number</th>

                                            <th scope="col">Join Date</th>

                                            <th scope="col">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @if (!empty($all_admins) && $all_admins->count())
                                            @php
                                                $counter =
                                                    ($all_admins->currentPage() - 1) * $all_admins->perPage() + 1;
                                            @endphp
                                            @foreach ($all_admins as $admin)
                                                <tr onclick="window.location='{{ url('/admin-dashboard/admin/edit/' . $admin->id) }}';"
                                                    style="cursor: pointer;">
                                                    <td>{{ $counter++ }}</td>
                                                    <td>
                                                        <span class="user-profile">
                                                            <img @if (getFirstMediaUrl($admin, $admin->avatarCollection) != null) src="{{ getFirstMediaUrl($admin, $admin->avatarCollection) }}" 
                                          @else 
                                              src="{{ asset('dashboard/user_avatar.png') }}" @endif
                                                                class="img-circle user-avatar" alt="user avatar">
                                                            <div class="avatar-preview">
                                                                <img @if (getFirstMediaUrl($admin, $admin->avatarCollection) != null) src="{{ getFirstMediaUrl($admin, $admin->avatarCollection) }}" 
                                              @else 
                                                  src="{{ asset('dashboard/user_avatar.png') }}" @endif
                                                                    alt="Avatar Preview">
                                                            </div>
                                                        </span>

                                                        <span
                                                            class="user-status {{ $admin->is_online ? 'online' : 'offline' }}"></span>

                                                        {!! highlight($admin->name, $search ?? '') !!}
                                                    </td>
                                                    <td>{!! highlight($admin->email, $search ?? '') !!}</td>

                                                    <td>{!! highlight($admin->country_code . $admin->phone, $search ?? '') !!}</td>


                                                    <td>{{ $admin->created_at->format('d.M.Y') }}</td>

                                                    <td>



                                                        <a href="{{ url('/admin-dashboard/admin/edit/' . $admin->id) }}"
                                                            style="margin-right: 1rem;">
                                                            <span class="bi bi-pen"
                                                                style="font-size: 1rem; color: rgb(255,255,255);"></span>
                                                        </a>

                                                        {{-- <a href="{{url('/admin-dashboard/user/delete/'.$user->id)}}">
                                            <span class="bi bi-trash" style="font-size: 1rem; color: rgb(255,255,255);"></span>
                                        </a> --}}
                                                        <a
                                                            onclick='event.stopPropagation(); showConfirmationPopup("{{ url('/admin-dashboard/admin/delete/' . $admin->id) }}","{{ $admin->name }}","{{ getFirstMediaUrl($admin, $admin->avatarCollection) ?? asset('dashboard/user_avatar.png') }}")'>
                                                            <span class="bi bi-trash"
                                                                style="font-size: 1rem; color: rgb(255,255,255);"></span>
                                                        </a>

                                                    </td>
                                                </tr>
                                            @endforeach
                                        @else
                                            <tr>
                                                <td>There are no Admins.</td>
                                            </tr>
                                        @endif
                                    </tbody>
                                </table>
                                <div style="text-align: center;">
                                    {!! $all_admins->appends([
                                            'search' => request('search'),
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
                        delete this admin?</h5>
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
@endpush
