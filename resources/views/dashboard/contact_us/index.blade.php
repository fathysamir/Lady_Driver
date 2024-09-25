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
                            
                            <a  class="btn btn-light px-5" style="margin-bottom:1%; " href="{{url('/admin-dashboard/contact_us?export=excel')}}">Export  <i class="bi bi-download"></i> </a>
                        </div>
                       
                        <div class="table-responsive">
                        <table class="table table-hover">
                          <thead>
                            <tr>
                              
                              <th scope="col">Name</th>
                              
                              <th scope="col">Phone Number</th>
                              <th scope="col">Governorate</th>
                              <th scope="col">Delegation</th>
                              <th scope="col">City</th>
                             
                            </tr>
                          </thead>
                          <tbody>
                            @if(!empty($contact_us) && $contact_us->count())
                            @foreach($contact_us as $contact_us1)
                              <tr>
                                <td>{{$contact_us1->name}}</td>
                               
                                <td>{{$contact_us1->phone}}</td>
                                <td>{{$contact_us1->state}}</td>
                                <td>{{$contact_us1->area}}</td>
                                <td>{{$contact_us1->city}}</td>
                                
                                
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
                        {!! $contact_us->links("pagination::bootstrap-4") !!}
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
@endpush
