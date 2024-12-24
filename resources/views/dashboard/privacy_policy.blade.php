<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() == 'ar' ? 'rtl' : 'ltr' }}">
  <head>
    <meta charset="utf-8"/>
    <meta http-equiv="X-UA-Compatible" content="IE=edge"/>
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no"/>
    <meta name="description" content=""/>
    <meta name="author" content=""/>
    <link rel="icon" type="image/x-icon" href="{{asset('dashboard/lady_driver.jpeg')}}">
    <title>@yield('title', 'Lady Driver - '.$title)</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <!-- loader-->
    <link href="{{asset('dashboard/assets/css/pace.min.css')}}" rel="stylesheet"/>
    <script src="{{asset('dashboard/assets/js/pace.min.js')}}"></script>
    <!--favicon-->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.3/font/bootstrap-icons.css">
    <!-- Vector CSS -->
    <link href="{{asset('dashboard/assets/plugins/vectormap/jquery-jvectormap-2.0.2.css')}}" rel="stylesheet"/>
    <!-- simplebar CSS-->
    <link href="{{asset('dashboard/assets/plugins/simplebar/css/simplebar.css')}}" rel="stylesheet"/>
    <!-- Bootstrap core CSS-->
    <link href="{{asset('dashboard/assets/css/bootstrap.min.css')}}" rel="stylesheet"/>
    <!-- animate CSS-->
    <link href="{{asset('dashboard/assets/css/animate.css')}}" rel="stylesheet" type="text/css"/>
    <!-- Icons CSS-->
    <link href="{{asset('dashboard/assets/css/icons.css')}}" rel="stylesheet" type="text/css"/>
    <!-- Sidebar CSS-->
    <link href="{{asset('dashboard/assets/css/sidebar-menu.css')}}" rel="stylesheet"/>
    <!-- Custom Style-->
    <link href="{{asset('dashboard/assets/css/app-style.css')}}" rel="stylesheet"/>
    @if(app()->getLocale() == 'ar')
    <style>
      body {
        direction: rtl;
        text-align: right;
      }
      .navbar, .card-body, .footer, .content-wrapper {
        direction: rtl;
      }
    </style>
    @endif
  </head>

  <body class="bg-theme bg-theme1">
  
  <!-- Start wrapper-->
  {!! $content !!}
   <!--End wrapper-->

    <!-- Bootstrap core JavaScript-->
    <script src="{{asset('dashboard/assets/js/jquery.min.js')}}"></script>
    <script src="{{asset('dashboard/assets/js/popper.min.js')}}"></script>
    <script src="{{asset('dashboard/assets/js/bootstrap.min.js')}}"></script>
    
  <!-- simplebar js -->
    <script src="{{asset('dashboard/assets/plugins/simplebar/js/simplebar.js')}}"></script>
    <!-- sidebar-menu js -->
    <script src="{{asset('dashboard/assets/js/sidebar-menu.js')}}"></script>
    <!-- loader scripts -->
    <script src="{{asset('dashboard/assets/js/jquery.loading-indicator.js')}}"></script>
    <!-- Custom scripts -->
    <script src="{{asset('dashboard/assets/js/app-script.js')}}"></script>
    <!-- Chart js -->
    
    <script src="{{asset('dashboard/assets/plugins/Chart.js/Chart.min.js')}}"></script>
  
    <!-- Index js -->
    <script src="{{asset('dashboard/assets/js/index.js')}}"></script>

    
  </body>
</html>
