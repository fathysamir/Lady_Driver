@extends('dashboard.layout.app')
@section('title', 'Dashboard - edit car model')
@section('content')	
    <div class="content-wrapper">
        <div class="container-fluid">

            <div class="row mt-3">
                <div class="col-lg-12">
                    <div class="card">
                      <div class="card-body">
                      <div class="card-title">Edit New Car Model</div>
                      <hr>
                       <form method="post" action="{{route('update.car.model',$model->id)}}" enctype="multipart/form-data">
                        @csrf
                      <div class="form-group">
                       <label>Name</label>
                        <input type="text" name="name" class="form-control"  placeholder="Enter Name"value="{{ old('name',$model->name) }}">
                        @if ($errors->has('name'))
                            <p class="text-error more-info-err" style="color: red;">
                                {{ $errors->first('name') }}</p>
                        @endif
                      </div>
                      <div class="form-group">
                        <label>Car Mark</label>
                        
                        <select class="form-control" name="car_mark">
                          <option value="">Select Car Mark</option>
                          @foreach($marks as $mark)
                          <option value="{{$mark->id}}" @if($model->car_mark_id==$mark->id) selected @endif>{{$mark->name}}</option>
                          @endforeach
                        </select>
                        @if ($errors->has('car_mark'))
                            <p class="text-error more-info-err" style="color: red;">
                                {{ $errors->first('car_mark') }}</p>
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
            <div class="overlay toggle-menu"></div>
        </div>
    </div>
@endsection
@push('scripts')
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
   
</script>
@endpush
