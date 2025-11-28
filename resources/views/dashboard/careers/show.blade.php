@extends('dashboard.layout.app')
@section('title', 'Dashboard - Application Details')
@section('content')
<style>
    .user-status {
        display: inline-block;
        width: 30px;
        height: 30px;
        border-radius: 50%;
        margin-left: -4%;
        margin-bottom: 4.65%;
    }

    .circle-wrapper {
        display: flex;
        justify-content: center;
        align-items: center;
    }

    .circle-label {
        width: 40px;
        height: 40px;
        border: 2px solid rgb(255, 230, 0);
        border-radius: 50%;
        color: rgb(255, 255, 255);
        display: flex;
        justify-content: center;
        align-items: center;
        font-weight: bold;
        font-size: 18px;
    }

    .online {
        background-color: green;
    }

    .filled {
        color: gold;
    }

    .offline {
        background-color: gray;
    }
</style>

<div class="content-wrapper">
    <div class="container-fluid">

        <div class="row mt-3">
            <div class="col-lg-12">
                <div class="card">
                    <div class="card-body">
                        <div class="card-title">Application Details</div>
                        <hr>

                        <form action="{{ route('view.career', $career->id) }}" method="POST" enctype="multipart/form-data">
                            @csrf
                            @method('PUT')

                            <div class="form-group" style="text-align: center;">
                                <div>
                                    <img style="border-radius: 50%;width:200px;height:200px;"
                                         src="{{ asset('dashboard/user_avatar.png') }}"
                                         class="img-circle" alt="user avatar">
                                </div>
                                <h3 style="margin-top: 10px;">{{ $career->first_name }} {{ $career->last_name }}</h3>
                            </div>

                            <div class="form-group">
                                <label>Email:</label>
                                <input type="email" class="form-control" name="email"
                                    placeholder="Enter Email" value="{{ old('email', $career->email) }}" readonly>
                                @if ($errors->has('email'))
                                    <p class="text-error more-info-err" style="color: red;">
                                        {{ $errors->first('email') }}</p>
                                @endif
                            </div>

                            <div class="form-group">
                                <label>Phone Number:</label>
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        @php
                                        $country_code = $career->country_code ?? '+20'; // Default to Egypt if null
                                        @endphp
                                        <select name="country_code" class="form-control" readonly>
                                            <option value="+1" {{ $country_code == '+1' ? 'selected' : '' }}>USA (+1)</option>
                                            <option value="+44" {{ $country_code == '+44' ? 'selected' : '' }}>UK (+44)</option>
                                            <option value="+20"{{ $country_code == '+20' || $country_code == null ? 'selected' : '' }}>Egypt (+20)</option>
                                        </select>
                                    </div>
                                    <input type="number" name="phone" class="form-control"
                                        placeholder="Enter Phone Number" value="{{ $career->phone }}" readonly>
                                </div>
                                @if ($errors->has('phone'))
                                    <p class="text-error more-info-err" style="color: red;">
                                        {{ $errors->first('phone') }}</p>
                                @endif
                            </div>

                            <div class="form-group">
                                <label>Position Applied:</label>
                                <input type="text" class="form-control" name="position"
                                    placeholder="Enter Position" value="{{ old('position', $career->position) }}" readonly>
                            </div>

                            <div class="form-group">
                                <label>Status:</label>
                                <select class="form-control" name="status">
                                    <option value="pending" @if ($career->status == 'pending') selected @endif>Pending</option>
                                    <option value="confirmed" @if ($career->status == 'confirmed') selected @endif>Confirmed</option>
                                    <option value="banned" @if ($career->status == 'banned') selected @endif>Banned</option>
                                    <option value="blocked" @if ($career->status == 'blocked') selected @endif>Blocked</option>
                                </select>
                            </div>

                            <div class="form-group" style="display: flex; flex-direction: column; align-items: flex-start;">
                                <label>CV:</label>
                                @if($career->cv)
                                    @php
                                        $cvUrl = $career->cv;
                                        $extension = pathinfo($cvUrl, PATHINFO_EXTENSION);
                                    @endphp

                                    @if(in_array(strtolower($extension), ['jpg','jpeg','png','gif']))
                                        <!-- Show image -->
                                        <img src="{{ $cvUrl }}" alt="CV Image" style="max-width: 400px; max-height: 500px; margin: 10px 0;">
                                    @elseif(strtolower($extension) === 'pdf')
                                        <!-- Show PDF -->
                                        <iframe src="{{ $cvUrl }}" style="width: 100%; height: 500px; margin: 10px 0;"></iframe>
                                        @elseif(in_array(strtolower($extension), ['doc','docx']))
                                       <a href="{{ $cvUrl }}" target="_blank">Download_CV</a> <p>Note: Preview not available for DOC/DOCX files</p>


                                    @else
                                        <p>CV format not supported for preview</p>
                                    @endif

                                @else
                                    <p>NO CV UPLOADED</p>
                                @endif
                            </div>

                            <div class="form-group">
                                <button type="submit" class="btn btn-light px-5"><i class="icon-lock"></i> Save</button>
                            </div>

                        </form>


                    </div>
                </div>
            </div>
        </div>

    </div>
</div>
@endsection
