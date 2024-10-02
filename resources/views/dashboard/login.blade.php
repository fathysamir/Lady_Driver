<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8"/>
  <meta http-equiv="X-UA-Compatible" content="IE=edge"/>
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no"/>
  <meta name="description" content=""/>
  <meta name="author" content=""/>
  <title>@yield('title', 'Lady Driver - Admin Login')</title>
  <!-- loader-->
  <link href="{{asset('dashboard/assets/css/pace.min.css')}}" rel="stylesheet"/>
  <script src="{{asset('dashboard/assets/js/pace.min.js')}}"></script>
  <!--favicon-->
  <link rel="icon" type="image/x-icon" href="{{asset('dashboard/lady_driver.jpeg')}}">
  <!-- Bootstrap core CSS-->
  <link href="{{asset('dashboard/assets/css/bootstrap.min.css')}}" rel="stylesheet"/>
  <!-- animate CSS-->
  <link href="{{asset('dashboard/assets/css/animate.css')}}" rel="stylesheet" type="text/css"/>
  <!-- Icons CSS-->
  <link href="{{asset('dashboard/assets/css/icons.css')}}" rel="stylesheet" type="text/css"/>
  <!-- Custom Style-->
  <link href="{{asset('dashboard/assets/css/app-style.css')}}" rel="stylesheet"/>
  
</head>

<body class="bg-theme bg-theme1">

<!-- start loader -->
   <div id="pageloader-overlay" class="visible incoming"><div class="loader-wrapper-outer"><div class="loader-wrapper-inner" ><div class="loader"></div></div></div></div>
   <!-- end loader -->

<!-- Start wrapper-->
 <div id="wrapper">

 <div class="loader-wrapper"><div class="lds-ring"><div></div><div></div><div></div><div></div></div></div>
	<div class="card card-authentication1 mx-auto my-5">
		<div class="card-body">
		 <div class="card-content p-2">
		 	<div class="text-center">
		 		<img src="{{asset('dashboard/lady_driver.jpeg')}}" alt="logo icon" style="width:50%; border-radius:50%;">
		 	</div>
		  <div class="card-title text-uppercase text-center py-3">Dashboard Sign In</div>
            @if ($errors->any())
                @if($errors->has('msg'))
                <p class="alert alert-danger"id="alert" role="alert" style="padding-top:5px;padding-bottom:5px; padding-left: 10px; background-color:brown;border-radius: 20px; color:beige;">{{ $errors->first('msg') }}rsgfe</p>
                @endif
            @endif
		    <form action="{{ route('login') }}" method="POST">
                @csrf
			  <div class="form-group">
			  <label for="exampleInputUsername" class="sr-only">Email</label>
			   <div class="position-relative has-icon-right">
				  <input type="text"  name="email" id="exampleInputUsername" class="form-control input-shadow" placeholder="Enter Email" value="{{old('email')}}">
				  <div class="form-control-position">
					  <i class="icon-user"></i>
				  </div>
                  @if ($errors->has('email'))
                    <p class="text-error more-info-err" style="color: red;">
                      {{ $errors->first('email') }}</p>
                  @endif
			   </div>
			  </div>
			  <div class="form-group">
			  <label for="exampleInputPassword" class="sr-only">Password</label>
			   <div class="position-relative has-icon-right">
				  <input type="password" id="exampleInputPassword" class="form-control input-shadow" name="password" placeholder="Enter Password">
				  <div class="form-control-position">
					  <i class="icon-lock"></i>
				  </div>
                  @if ($errors->has('password'))
                            <p class="text-error more-info-err" style="color: red;">
                                {{ $errors->first('password') }}</p>
                  @endif
			   </div>
			  </div>
			
			 <button type="submit" class="btn btn-light btn-block">Sign In</button>
			 
			  
			 
			 
			 </form>
		   </div>
		  </div>
		 
	     </div>
    
     <!--Start Back To Top Button-->
    <a href="javaScript:void();" class="back-to-top"><i class="fa fa-angle-double-up"></i> </a>
    <!--End Back To Top Button-->
	
	<!--start color switcher-->
   <div class="right-sidebar">
    <div class="switcher-icon">
      <i class="zmdi zmdi-settings zmdi-hc-spin"></i>
    </div>
    <div class="right-sidebar-content">

      <p class="mb-0">Gaussion Texture</p>
      <hr>
      
      <ul class="switcher">
        <li id="theme1"></li>
        <li id="theme2"></li>
        <li id="theme3"></li>
        <li id="theme4"></li>
        <li id="theme5"></li>
        <li id="theme6"></li>
      </ul>

      <p class="mb-0">Gradient Background</p>
      <hr>
      
      <ul class="switcher">
        <li id="theme7"></li>
        <li id="theme8"></li>
        <li id="theme9"></li>
        <li id="theme10"></li>
        <li id="theme11"></li>
        <li id="theme12"></li>
		<li id="theme13"></li>
        <li id="theme14"></li>
        <li id="theme15"></li>
      </ul>
      
     </div>
   </div>
  <!--end color switcher-->
	
	</div><!--wrapper-->
	
  <!-- Bootstrap core JavaScript-->
  <script src="{{asset('dashboard/assets/js/jquery.min.js')}}"></script>
  <script src="{{asset('dashboard/assets/js/popper.min.js')}}"></script>
  <script src="{{asset('dashboard/assets/js/bootstrap.min.js')}}"></script>
	
  <!-- sidebar-menu js -->
  <script src="{{asset('dashboard/assets/js/sidebar-menu.js')}}"></script>
  
  <!-- Custom scripts -->
  <script src="{{asset('dashboard/assets/js/app-script.js')}}"></script>
  <script type="text/javascript">
    $(document).ready(function() {
        setTimeout(function() {
    $('#alert').fadeOut('fast');
    }, 5000);
    });
  </script>
</body>
</html>
