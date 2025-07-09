<div id="sidebar-wrapper" data-simplebar="" data-simplebar-auto-hide="true">
    <div class="brand-logo">
        <a href="{{ url('/admin-dashboard') }}">
            <img src="{{ asset('dashboard/logo.png') }}" class="logo-icon" alt="logo icon"
                style="width:60px; border-radius: 50%;">
            <h5 class="logo-text">Dashboard Admin</h5>
        </a>
    </div>
    <ul class="sidebar-menu do-nicescrol">
        <li class="sidebar-header">MAIN NAVIGATION</li>
        <li>
            <a href="{{ url('/admin-dashboard') }}">
                <i class="zmdi zmdi-view-dashboard"></i> <span>Dashboard</span>
            </a>
        </li>
        <li>
            <a href="{{ url('/admin-dashboard/admins') }}">
                <i class="zmdi zmdi-view-dashboard"></i> <span>Admins</span>
            </a>
        </li>
        <li>
            <a href="{{ url('/admin-dashboard/clients') }}">
                <i class="zmdi zmdi-view-dashboard"></i> <span>Clients</span>
                @if (app('new_clients_count') > 0)
                    <span
                        style="background-color:rgb(143, 118, 9); float:right; margin-right:10px; display:inline-block;  line-height: 20px; text-align: center; border-radius: 50%; padding: 0px 5px 0px 5px;">{{ app('new_clients_count') }}</span>
                @endif
            </a>
        </li>
        {{-- <li>
            <a href="{{ url('/admin-dashboard/drivers') }}">
                <i class="zmdi zmdi-view-dashboard"></i> <span>Drivers</span>
                @if (app('new_drivers_count') > 0)
                    <span
                        style="background-color:rgb(143, 118, 9); float:right; margin-right:10px; display:inline-block;  line-height: 20px; text-align: center; border-radius: 50%; padding: 0px 5px 0px 5px;">{{ app('new_drivers_count') }}</span>
                @endif
            </a>
        </li> --}}
         <li class="sidebar-item">
            <a href="#" class="dropdown-toggle" onclick="toggleSubmenu(event, this)">
                <i class="zmdi zmdi-view-dashboard"></i> <span>Drivers</span>
                @if (app('new_drivers_count') > 0)
                    <span
                        style="background-color:rgb(143, 118, 9); float:right; margin-right:10px; display:inline-block;  line-height: 20px; text-align: center; border-radius: 50%; padding: 0px 5px 0px 5px;">{{ app('new_drivers_count') }}</span>
                @endif
            </a>
            <ul class="sidebar-submenu" style="display: none; padding-left: 40px;">
                <li><a href="{{ url('/admin-dashboard/drivers') }}">Original Cars</a></li>
                <li><a href="#">Comfort Cars</a></li>
                <li><a href="#">Scooters</a></li>
            </ul>
        </li>
        <li>
            <a href="{{ url('/admin-dashboard/cities') }}">
                <i class="zmdi zmdi-view-dashboard"></i> <span>Cities</span> 
            </a>
        </li>
        {{-- <li>
            <a href="{{ url('/admin-dashboard/users') }}">
                <i class="zmdi zmdi-view-dashboard"></i> <span>Users</span>
                @if (app('new_clients_count') > 0)
                    <span
                        style="background-color:rgb(143, 118, 9); float:right; margin-right:10px; display:inline-block;  line-height: 20px; text-align: center; border-radius: 50%; padding: 0px 5px 0px 5px;">{{ app('new_clients_count') }}</span>
                @endif
            </a>
        </li> --}}
        <li>
            <a href="{{ url('/admin-dashboard/car-marks') }}">
                <i class="zmdi zmdi-view-dashboard"></i> <span>Car Marks & Models</span>
            </a>
        </li>
        <li>
            <a href="{{ url('/admin-dashboard/motorcycles') }}">
                <i class="zmdi zmdi-view-dashboard"></i> <span>Motorcycle Marks & Models</span>
            </a>
        </li>
        {{-- <li>
            <a href="{{ url('/admin-dashboard/car-models') }}">
                <i class="zmdi zmdi-view-dashboard"></i> <span>Car Models</span>
            </a>
        </li> --}}
        <li>
            <a href="{{ url('/admin-dashboard/cars') }}">
                <i class="zmdi zmdi-view-dashboard"></i> <span>Drivers Cars</span>
                @if (app('new_cars_count') > 0)
                    <span
                        style="background-color:rgb(143, 118, 9); float:right; margin-right:10px; display:inline-block;  line-height: 20px; text-align: center; border-radius: 50%; padding: 0px 5px 0px 5px;">{{ app('new_cars_count') }}</span>
                @endif
            </a>
        </li>
        <li>
            <a href="{{ url('/admin-dashboard/trips') }}">
                <i class="zmdi zmdi-view-dashboard"></i> <span>Trips</span>
            </a>
        </li>
        <li>
            <a href="{{ url('/admin-dashboard/settings') }}">
                <i class="zmdi zmdi-view-dashboard"></i> <span>Settings</span>
            </a>
        </li>
        <li>
            <a href="{{ url('/admin-dashboard/reasons-cancelling-trips') }}">
                <i class="zmdi zmdi-view-dashboard"></i> <span>Trip Cancellation Reason</span>
            </a>
        </li>
        <li class="sidebar-item">
            <a href="#" class="dropdown-toggle" onclick="toggleSubmenu(event, this)">
                <i class="zmdi zmdi-view-dashboard"></i> <span>Chats</span>
            </a>
            <ul class="sidebar-submenu" style="display: none; padding-left: 40px;">
                <li><a href="{{ route('send_message_view') }}">Send Message</a></li>
                <li><a href="#">Occasion messages</a></li>
                <li><a href="#">All Chats</a></li>
            </ul>
        </li>
        <li>
            <a href="{{ url('/admin-dashboard/contact_us') }}">
                <i class="zmdi zmdi-view-dashboard"></i> <span>Contact Us</span>
                @if (app('new_contact_us_count') > 0)
                    <span
                        style="background-color:rgb(143, 118, 9); float:right; margin-right:10px; display:inline-block;  line-height: 20px; text-align: center; border-radius: 50%; padding: 0px 5px 0px 5px;">{{ app('new_contact_us_count') }}</span>
                @endif
            </a>
        </li>
        <li>
            <a href="{{ url('/admin-dashboard/about_us/view') }}">
                <i class="zmdi zmdi-view-dashboard"></i> <span>About Us</span>
            </a>
        </li>
        <li>
            <a href="{{ url('/admin-dashboard/feed_back') }}">
                <i class="zmdi zmdi-view-dashboard"></i> <span>Feed Back</span>
            </a>
        </li>
        

    </ul>

</div>
