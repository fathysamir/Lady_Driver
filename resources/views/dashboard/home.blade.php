@extends('dashboard.layout.app')
@section('title', 'Lady Driver - Admin Home')
@section('content')
<div class="content-wrapper">
    <div class="container-fluid">

      <!--Start Dashboard Content-->

      <!--<div class="card mt-3">
        <div class="card-content">
            <div class="row row-group m-0">
                <div class="col-12 col-lg-6 col-xl-3 border-light">
                    <div class="card-body">
                      <h5 class="text-white mb-0">9526 <span class="float-right"><i class="fa fa-shopping-cart"></i></span></h5>
                        <div class="progress my-3" style="height:3px;">
                          <div class="progress-bar" style="width:55%"></div>
                        </div>
                      <p class="mb-0 text-white small-font">Total Orders <span class="float-right">+4.2% <i class="zmdi zmdi-long-arrow-up"></i></span></p>
                    </div>
                </div>
                <div class="col-12 col-lg-6 col-xl-3 border-light">
                    <div class="card-body">
                      <h5 class="text-white mb-0">8323 <span class="float-right"><i class="fa fa-usd"></i></span></h5>
                        <div class="progress my-3" style="height:3px;">
                          <div class="progress-bar" style="width:55%"></div>
                        </div>
                      <p class="mb-0 text-white small-font">Total Revenue <span class="float-right">+1.2% <i class="zmdi zmdi-long-arrow-up"></i></span></p>
                    </div>
                </div>
                <div class="col-12 col-lg-6 col-xl-3 border-light">
                    <div class="card-body">
                      <h5 class="text-white mb-0">6200 <span class="float-right"><i class="fa fa-eye"></i></span></h5>
                        <div class="progress my-3" style="height:3px;">
                          <div class="progress-bar" style="width:55%"></div>
                        </div>
                      <p class="mb-0 text-white small-font">Visitors <span class="float-right">+5.2% <i class="zmdi zmdi-long-arrow-up"></i></span></p>
                    </div>
                </div>
                <div class="col-12 col-lg-6 col-xl-3 border-light">
                    <div class="card-body">
                      <h5 class="text-white mb-0">5630 <span class="float-right"><i class="fa fa-envira"></i></span></h5>
                        <div class="progress my-3" style="height:3px;">
                          <div class="progress-bar" style="width:55%"></div>
                        </div>
                      <p class="mb-0 text-white small-font">Messages <span class="float-right">+2.2% <i class="zmdi zmdi-long-arrow-up"></i></span></p>
                    </div>
                </div>
            </div>
        </div>
      </div>  -->
      <div style="color:#fff;text-align:center;">
        <canvas id="canvas" width="900" height="800" style="color:#fff;text-align:center;"></canvas>
      </div>
        
      <!--End Row-->
      
     

          <!--End Dashboard Content-->
        
      <!--start overlay-->
      <div class="overlay toggle-menu"></div>
        <!--end overlay-->
    
    </div>
    <!-- End container-fluid-->
    
  </div>
@endsection
@push('scripts')
<script>

    const canvas = document.getElementById("canvas");
    const ctx = canvas.getContext("2d");

    ctx.font = "75px sans-serif"; // Set the font family to sans-serif
ctx.strokeStyle = "white"; // Set the stroke color to white
ctx.lineWidth = 3; // Set the stroke width if needed
ctx.strokeText("Welcome To Dashboard", 50, 90);
  
</script>
@endpush
