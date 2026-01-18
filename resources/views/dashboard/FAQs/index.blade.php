@extends('dashboard.layout.app')
@section('title', 'Dashboard - FAQs')
@section('content')
    <style>
        .pagination {
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
                                <h5 class="card-title" style="width: 55%;">FAQs</h5>
                                @can('FAQs.create')
                                    <a class="btn btn-light px-5" style="margin-bottom:1%; "
                                        href="{{ route('add.FAQ') }}">create</a>
                                @endcan
                                <form id="searchForm" class="search-bar"
                                    style="margin-bottom:1%;margin-left:20px;margin-right:0px;"method="post"
                                    action="{{ route('FAQs') }}" enctype="multipart/form-data">
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
                                            <th scope="col">Question</th>
                                            <th scope="col">Answer</th>
                                            <th scope="col">Category</th>
                                            <th scope="col">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @if (!empty($FAQs) && $FAQs->count())
                                            @php
                                                $counter = ($FAQs->currentPage() - 1) * $FAQs->perPage() + 1;
                                            @endphp
                                            @foreach ($FAQs as $FAQ)
                                                <tr onclick="window.location='{{ route('edit.FAQ', ['id' => $FAQ->id] + request()->query()) }}';"
                                                    style="cursor: pointer;">
                                                    <td>
                                                        <div class="form-row">
                                                            <div class="form-group col-md-12">
                                                                <div class="custom-control custom-switch mt-2">
                                                                    <input type="checkbox" disabled
                                                                        class="custom-control-input" id="is_active"
                                                                        name="is_active"{{ $FAQ->is_active == '1' ? 'checked' : '' }}>
                                                                    <label class="custom-control-label" for="is_active">
                                                                        {{ $counter++ }}
                                                                    </label>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <span class="short-text">
                                                            {!! highlight(\Illuminate\Support\Str::limit($FAQ->question, 50), $search ?? '') !!}
                                                        </span>

                                                        @if (strlen($FAQ->question) > 50)
                                                            <span class="full-text d-none">
                                                                {!! highlight($FAQ->question, $search ?? '') !!}
                                                            </span>

                                                            <a href="javascript:void(0)" class="read-more"
                                                                onclick="event.stopPropagation(); toggleText(this);">Read
                                                                more</a>
                                                        @endif
                                                    </td>
                                                    <td>
                                                        <span class="short-text">
                                                            {!! highlight(\Illuminate\Support\Str::limit($FAQ->answer, 50), $search ?? '') !!}
                                                        </span>

                                                        @if (strlen($FAQ->answer) > 50)
                                                            <span class="full-text d-none">
                                                                {!! highlight($FAQ->answer, $search ?? '') !!}
                                                            </span>

                                                            <a href="javascript:void(0)" class="read-more"
                                                                onclick="event.stopPropagation(); toggleText(this);">Read
                                                                more</a>
                                                        @endif
                                                    </td>

                                                    <td>{{ ucwords($FAQ->type) }}</td>



                                                    <td>

                                                        @can('FAQs.edit')
                                                            <a href="{{ route('edit.FAQ', ['id' => $FAQ->id] + request()->query()) }}"
                                                                style="margin-right: 1rem;">
                                                                <span class="bi bi-pen"
                                                                    style="font-size: 1rem; color: rgb(255,255,255);"></span>
                                                            </a>
                                                        @endcan

                                                        @can('FAQs.delete')
                                                            <a
                                                                onclick='event.stopPropagation(); showConfirmationPopup("{{ url('/admin-dashboard/FAQs/delete/' . $FAQ->id) }}","{{ $FAQ->name }}")'>
                                                                <span class="bi bi-trash"
                                                                    style="font-size: 1rem; color: rgb(255,255,255);"></span>
                                                            </a>
                                                        @endcan
                                                    </td>
                                                </tr>
                                            @endforeach
                                        @else
                                            <tr>
                                                <td>There are no FAQs.</td>
                                            </tr>
                                        @endif
                                    </tbody>
                                </table>
                                <div style="text-align: center;">
                                    {!! $FAQs->appends([
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
                        delete this FAQ?</h5>
                    <button type="button" class="close" onclick="hideConfirmationPopup()" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body" style="color:black;">
                    <div class="form-group">

                        <div style="width: 100%;  display: flex;justify-content: center;">
                            <h5 class="logo-text"style="color:black;font-weight: bold;" id="deletedNameInput"></h5>

                        </div>
                    </div>
                    <button
                        onclick="hideConfirmationPopup()"style="background-color: #5f6360; color: white; padding: 10px 20px; border: none; cursor: pointer;width:48%;border-radius:10px;"><span
                            class="bi bi-x" style="font-size: 1rem; color: rgb(255,255,255);"></span> Cancel</button>
                    <button
                        onclick="deleteCity()"style="background-color: #f44336; color: white; padding: 10px 20px; border: none; cursor: pointer; margin-right: 10px; width:48%; border-radius:10px;"><span
                            class="bi bi-trash" style="font-size: 1rem; color: rgb(255,255,255);"></span> Delete</button>

                </div>
            </div>
        </div>
    </div>
@endsection
@push('scripts')
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        function showConfirmationPopup(deleteUrl, name) {

            document.getElementById('deletedNameInput').textContent = name;

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

        function deleteCity() {
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
        function toggleText(el) {
            let td = el.closest('td');
            td.querySelector('.short-text').classList.toggle('d-none');
            td.querySelector('.full-text').classList.toggle('d-none');
            el.textContent = el.textContent === 'Read more' ? 'Read less' : 'Read more';
        }
    </script>

    <script>
        // Set a timeout to hide the error or success message after 5 seconds
        setTimeout(function() {
            $('#errorAlert').fadeOut();
            $('#successAlert').fadeOut();
        }, 4000); // 5000 milliseconds = 5 seconds
    </script>
@endpush
