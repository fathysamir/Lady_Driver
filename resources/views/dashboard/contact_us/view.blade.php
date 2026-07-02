@extends('dashboard.layout.app')
@section('title', 'Dashboard - view contact us')
@section('content')
    <div class="content-wrapper">
        <div class="container-fluid">

            <div class="row mt-3">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-body">
                            <div class="card-title">Support</div>
                            <hr>
                            <form method="post" action="{{ route('update.contact_us', ['id' => $contact_us->id]) }}"
                                enctype="multipart/form-data">
                                @csrf

                                <div class="form-group">
                                    <label>Name</label>
                                    <input type="text" disabled class="form-control" value="{{ $contact_us->name }}">
                                </div>

                                <div class="form-group">
                                    <label>Email</label>
                                    <input type="text" disabled class="form-control" value="{{ $contact_us->email }}">
                                </div>

                                <div class="form-group">
                                    <label>Phone</label>
                                    <input type="text" disabled class="form-control" value="{{ $contact_us->phone ?? '-' }}">
                                </div>

                                <div class="form-group">
                                    <label>Subject</label>
                                    <input type="text" disabled class="form-control" value="{{ $contact_us->subject }}">
                                </div>

                                <div class="form-group">
                                    <label>Message</label>
                                    <textarea disabled class="form-control" rows="5">{{ $contact_us->message }}</textarea>
                                </div>

                                {{-- ─── Attachments (uses $appends accessor) ─── --}}
                                <div class="form-group">
                                    <label>Attachments</label>

                                    @if (!empty($contact_us->attachment_files))
                                        <div class="d-flex flex-wrap gap-2 mt-1">
                                            @foreach ($contact_us->attachment_files as $file)
                                                <div class="border rounded p-2 d-inline-flex flex-column align-items-center"
                                                    style="max-width: 180px;">

                                                    @if (in_array($file['extension'], ['jpg', 'jpeg', 'png']))
                                                        {{-- Image preview --}}
                                                        <a href="{{ $file['url'] }}" target="_blank">
                                                            <img src="{{ $file['url'] }}" alt="{{ $file['file_name'] }}"
                                                                class="img-thumbnail mb-1"
                                                                style="max-height: 100px; object-fit: cover; width: 100%;">
                                                        </a>
                                                        <small class="text-muted text-truncate w-100 text-center">
                                                            {{ $file['file_name'] }}
                                                        </small>
                                                    @else
                                                        {{-- Document download --}}
                                                        <a href="{{ $file['url'] }}" target="_blank"
                                                            class="btn btn-sm btn-outline-secondary w-100">
                                                            <i class="icon-docs me-1"></i>
                                                            {{ $file['file_name'] }}
                                                        </a>
                                                    @endif

                                                </div>
                                            @endforeach
                                        </div>
                                    @else
                                        <p class="text-muted mb-0">No attachments provided.</p>
                                    @endif
                                </div>

                                @can('contact.us.reply')
                                    <div class="form-group">
                                        <label>Reply</label>
                                        <textarea name="reply" class="form-control" rows="5"
                                            placeholder="Reply On Message">{{ $contact_us->reply }}</textarea>
                                    </div>

                                    <div class="form-group">
                                        <button type="submit" class="btn btn-light px-5">
                                            <i class="icon-lock"></i> Save
                                        </button>
                                    </div>
                                @endcan

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
        $(document).ready(function() {
            let isFormDirty = false;

            $('form :input').on('change', function() {
                isFormDirty = true;
            });

            $(document).on('click', 'a', function(e) {
                if (isFormDirty) {
                    e.preventDefault();
                    let url = $(this).attr('href');
                    if (confirm("You have unsaved changes. Do you really want to leave?")) {
                        window.location.href = url;
                    }
                }
            });

            $('form').on('submit', function() {
                isFormDirty = false;
            });
        });
    </script>
@endpush
