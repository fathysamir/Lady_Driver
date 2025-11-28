@extends('dashboard.layout.app')
@section('title')
    Dashboard - archived applications
@endsection
@section('content')
    <style>
        .pagination {
            display: inline-flex;
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
                                <form id="searchForm" class="search-bar" style="margin-bottom:1%;margin-right:20px;margin-left:0px;" method="post" action="{{ route('archived_careers') }}" enctype="multipart/form-data">
                                    @csrf
                                    <div style="display:flex;">
                                        <h5 class="card-title" style="width: 80%;">{{ "Archived Applications" }} - {{ $count }}</h5>
                                        <div style="display:flex;margin-bottom:1%;margin-left:0px;">
                                            <input type="text" class="form-control" placeholder="Enter keywords" name="search" style="width: 300px;" value="{{ $search }}">
                                            <a href="javascript:void(0);" id="submitForm"><i class="icon-magnifier"></i></a>
                                        </div>
                                    </div>
                                </form>
                            </div>

                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>Name</th>
                                            <th>Email</th>
                                            <th>Phone</th>
                                            <th>Position</th>
                                            <th>Status</th>
                                            <th>CV</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @if (!empty($all_users) && $all_users->count())
                                            @php
                                                $counter = ($all_users->currentPage() - 1) * $all_users->perPage() + 1;
                                            @endphp
                                            @foreach ($all_users as $career)
                                                <tr>
                                                    <td>{{ $counter++ }}</td>
                                                    <td>{{ $career->first_name }} {{ $career->last_name }}</td>
                                                    <td>{{ $career->email }}</td>
                                                    <td>{{ $career->country_code }}{{ $career->phone }}</td>
                                                    <td>{{ $career->position }}</td>
                                                    <td>
                                                        @if ($career->status == 'banned')
                                                            <span class="badge badge-secondary" style="background-color:rgb(61, 27, 255);width:100%;">Banned</span>
                                                        @elseif($career->status == 'confirmed')
                                                            <span class="badge badge-secondary" style="background-color:rgb(50, 134, 50);width:100%;">Confirmed</span>
                                                        @elseif($career->status == 'blocked')
                                                            <span class="badge badge-secondary" style="background-color:rgb(255,0,0);width:100%;">Blocked</span>
                                                        @else
                                                            <span class="badge badge-secondary" style="background-color:gray;width:100%;">Pending</span>
                                                        @endif
                                                    </td>
                                                    <td>
                                                        @if ($career->cv)
                                                            <a href="{{ $career->cv }}" target="_blank" class="btn btn-light px-3">View CV</a>
                                                        @else
                                                            <span>Not Found</span>
                                                        @endif
                                                    </td>
                                                    <td>
                                                        <a onclick='event.stopPropagation(); showConfirmationPopup("{{ url("/admin-dashboard/career/restore/".$career->id) }}","{{ $career->first_name }} {{ $career->last_name }}","{{ asset("dashboard/logo.png") }}")'>
                                                        <span class="bi bi-eye"
                                                        style="font-size: 1rem; color: rgb(255,255,255);"></span>
                                                        </a>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        @else
                                            <tr>
                                                <td colspan="8">No Deleted Applications found.</td>
                                            </tr>
                                        @endif
                                    </tbody>
                                </table>

                                <div style="text-align: center;">
                                    {!! $all_users->appends(['search' => $search])->links('pagination::bootstrap-4') !!}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="overlay toggle-menu"></div>
        </div>
    </div>

    <div class="modal fade" id="confirmationPopup" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" style="color:black;">Are you sure you want to restore this application?</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body" style="color:black;">
                    <div class="form-group">
                        <div style="width: 100%; display: flex; justify-content: center;">
                            <img id="deletedUserAvatar" src="{{ asset('dashboard/logo.png') }}" class="logo-icon" alt="logo icon" style="width:100px;height:100px; border-radius: 50%;">
                        </div>
                        <div style="width: 100%; display: flex; justify-content: center;">
                            <h5 class="logo-text" style="color:black;font-weight: bold;" id="deletedNameInput"></h5>
                        </div>
                    </div>
                    <button onclick="hideConfirmationPopup()" style="background-color: #5f6360; color: white; padding: 10px 20px; border: none; cursor: pointer;width:48%;border-radius:10px;">
                        <span class="bi bi-x" style="font-size: 1rem; color: rgb(255,255,255);"></span> Cancel
                    </button>
                    <button onclick="restoreCareer()" style="background-color: rgb(193, 183, 41); color: white; padding: 10px 20px; border: none; cursor: pointer; margin-right: 10px; width:48%; border-radius:10px;">
                        <span class="bi bi-arrow-clockwise" style="font-size: 1rem; color: rgb(255,255,255);"></span> Restore
                    </button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
    function showConfirmationPopup(restoreUrl, name, imgSrc) {
        document.getElementById('deletedNameInput').textContent = name;
        document.getElementById('deletedUserAvatar').src = imgSrc;
        const myModal = new bootstrap.Modal(document.getElementById('confirmationPopup'), {});
        myModal.show();
        document.getElementById('confirmationPopup').setAttribute('data-restore-url', restoreUrl);
    }

    function hideConfirmationPopup() {
        var modal = document.getElementById('confirmationPopup');
        modal.classList.remove('show');
        modal.style.display = 'none';
        document.body.classList.remove('modal-open');
        document.getElementsByClassName('modal-backdrop')[0].remove();
    }

    function restoreCareer() {
        const restoreUrl = document.getElementById('confirmationPopup').getAttribute('data-restore-url');
        window.location.href = restoreUrl;
    }

    $(document).ready(function() {
        $('#submitForm').on('click', function() {
            $('#searchForm').submit();
        });
    });
</script>
@endpush
