@extends('dashboard.layout.app')
@section('title', 'Dashboard - edit setting')
@section('content')	
    <div class="content-wrapper">
        <div class="container-fluid">

            <div class="row mt-3">
                <div class="col-lg-12">
                    <div class="card">
                      <div class="card-body">
                      <div class="card-title">Edit Setting</div>
                      <hr>
                       <form method="post" action="{{route('update.setting',$setting->id)}}" enctype="multipart/form-data">
                        @csrf
                      <div class="form-group">
                        <label>Label</label>
                        <input  type="text" name="label" class="form-control"  placeholder="Enter Label"value="{{ old('label',$setting->label) }}">
                        @if ($errors->has('label'))
                            <p class="text-error more-info-err" style="color: red;">{{ $errors->first('label') }}</p>
                        @endif
                      </div>
                      <div class="form-group">
                        <label>Key</label>
                        <input  type="text" disabled class="form-control"  placeholder="Enter Label"value="{{$setting->key}}">
                      </div>
                      <div class="form-group">
                        <label>Category</label>
                        
                        <select class="form-control" disabled>
                            <option value="General" {!! $setting->category == 'General' ? 'selected' : '' !!}>General</option>
                            <option value="Clients" {!! $setting->category == 'Clients' ? 'selected' : '' !!}>Clients</option>
                            <option value="Trips" {!! $setting->category == 'Trips' ? 'selected' : '' !!}>Trips</option>
                        </select>
                        
                      </div>
                      @if($setting->type=='number')
                      <div class="form-group">
                        <label>Value</label>
                        <input  type="number" name="value" class="form-control"  placeholder="Enter Value"value="{{old('value',$setting->value)}}"step="0.01">
                        @if ($errors->has('value'))
                            <p class="text-error more-info-err" style="color: red;">{{ $errors->first('value') }}</p>
                        @endif
                      </div>
                      @endif
                     
                      
                      
                      <div class="form-group">
                       <button type="submit" class="btn btn-light px-5"><i class="icon-lock"></i> Save</button>
                     </div>
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

@endpush
