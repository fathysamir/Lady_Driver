<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <meta name="description" content="" />
    <meta name="author" content="" />
    <meta name="robots" content="noindex, nofollow">
    <link rel="icon" type="image/x-icon" href="{{ asset('dashboard/logo.png') }}">
    <title>@yield('title', 'Lady Driver - Admin Home')</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <!-- loader-->
    <link href="{{ asset('dashboard/assets/css/pace.min.css') }}" rel="stylesheet" />
    <script src="{{ asset('dashboard/assets/js/pace.min.js') }}"></script>
    <!--favicon-->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.3/font/bootstrap-icons.css">
    <!-- Vector CSS -->
    <link href="{{ asset('dashboard/assets/plugins/vectormap/jquery-jvectormap-2.0.2.css') }}" rel="stylesheet" />
    <!-- simplebar CSS-->
    <link href="{{ asset('dashboard/assets/plugins/simplebar/css/simplebar.css') }}" rel="stylesheet" />
    <!-- Bootstrap core CSS-->
    <link href="{{ asset('dashboard/assets/css/bootstrap.min.css') }}" rel="stylesheet" />
    <!-- animate CSS-->
    <link href="{{ asset('dashboard/assets/css/animate.css') }}" rel="stylesheet" type="text/css" />
    <!-- Icons CSS-->
    <link href="{{ asset('dashboard/assets/css/icons.css') }}" rel="stylesheet" type="text/css" />
    <!-- Sidebar CSS-->
    <link href="{{ asset('dashboard/assets/css/sidebar-menu.css') }}" rel="stylesheet" />
    <!-- Custom Style-->
    <link href="{{ asset('dashboard/assets/css/app-style.css') }}" rel="stylesheet" />

</head>

<body class="bg-theme bg-{{ app('theme') }}">

    <!-- Start wrapper-->
    <div id="wrapper">

        <!--Start sidebar-wrapper-->
        @include('dashboard.layout.side_menu')
        <!--End sidebar-wrapper-->

        <!--Start topbar header-->
        @include('dashboard.layout.header')
        <!--End topbar header-->

        <div class="clearfix"></div>

        @yield('content')
        <!--End content-wrapper-->
        <!--Start Back To Top Button-->
        <a href="javaScript:void();" class="back-to-top"><i class="fa fa-angle-double-up"></i> </a>
        <!--End Back To Top Button-->

        <!--Start footer-->
        @include('dashboard.layout.footer')
        <!--End footer-->

        <!--start color switcher-->
        <div class="right-sidebar">
            <div class="switcher-icon">
                <i class="zmdi zmdi-settings zmdi-hc-spin"></i>
            </div>
            <div class="right-sidebar-content">

                <p class="mb-0">Gaussion Texture</p>
                <hr>

                <ul class="switcher">
                    <li onclick="change_theme('theme1');" id="theme1"></li>
                    <li onclick="change_theme('theme2');" id="theme2"></li>
                    <li onclick="change_theme('theme3');" id="theme3"></li>
                    <li onclick="change_theme('theme4');" id="theme4"></li>
                    <li onclick="change_theme('theme5');" id="theme5"></li>
                    <li onclick="change_theme('theme6');" id="theme6"></li>
                </ul>

                <p class="mb-0">Gradient Background</p>
                <hr>

                <ul class="switcher">
                    <li onclick="change_theme('theme7');" id="theme7"></li>
                    <li onclick="change_theme('theme8');" id="theme8"></li>
                    <li onclick="change_theme('theme9');" id="theme9"></li>
                    <li onclick="change_theme('theme10');" id="theme10"></li>
                    <li onclick="change_theme('theme11');" id="theme11"></li>
                    <li onclick="change_theme('theme12');" id="theme12"></li>
                    <li onclick="change_theme('theme13');" id="theme13"></li>
                    <li onclick="change_theme('theme14');" id="theme14"></li>
                    <li onclick="change_theme('theme15');" id="theme15"></li>
                </ul>

            </div>
        </div>
        <!--end color switcher-->

    </div><!--End wrapper-->

    <!-- Bootstrap core JavaScript-->
    <script src="{{ asset('dashboard/assets/js/jquery.min.js') }}"></script>
    <script src="{{ asset('dashboard/assets/js/popper.min.js') }}"></script>
    <script src="{{ asset('dashboard/assets/js/bootstrap.min.js') }}"></script>

    <!-- simplebar js -->
    <script src="{{ asset('dashboard/assets/plugins/simplebar/js/simplebar.js') }}"></script>
    <!-- sidebar-menu js -->
    <script src="{{ asset('dashboard/assets/js/sidebar-menu.js') }}"></script>
    <!-- loader scripts -->
    <script src="{{ asset('dashboard/assets/js/jquery.loading-indicator.js') }}"></script>
    <!-- Custom scripts -->
    <script src="{{ asset('dashboard/assets/js/app-script.js') }}"></script>
    <!-- Chart js -->

    <script src="{{ asset('dashboard/assets/plugins/Chart.js/Chart.min.js') }}"></script>

    <!-- Index js -->
    <script src="{{ asset('dashboard/assets/js/index.js') }}"></script>
    <script>
        function goBack() {
            history.go(-1);
        }

        function goNext() {
            history.go(+1);
        }

        function change_theme(x) {
            var csrfToken = $('meta[name="csrf-token"]').attr('content');
            var formData = {
                _token: csrfToken,
                theme: x

            };

            // Submit form data via AJAX
            $.ajax({
                url: '/admin-dashboard/change_theme', // Replace with your actual controller route
                type: 'POST',
                data: formData,
                success: function(response) {

                },
                error: function(xhr, status, error) {
                    // Handle the error response here
                    console.error(error);
                }
            });
        }
    </script>
    @stack('scripts')

</body>

</html>
