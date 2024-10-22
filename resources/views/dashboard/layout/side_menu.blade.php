<div id="sidebar-wrapper" data-simplebar="" data-simplebar-auto-hide="true">
    <div class="brand-logo">
      <a href="{{url('/admin-dashboard')}}">
        <img src="{{asset('dashboard/lady_driver.jpeg')}}" class="logo-icon" alt="logo icon" style="width:60px; border-radius: 50%;">
        <h5 class="logo-text">Dashboard Admin</h5>
      </a>
    </div>
    <ul class="sidebar-menu do-nicescrol">
      <li class="sidebar-header">MAIN NAVIGATION</li>
      <li>
        <a href="{{url('/admin-dashboard')}}">
          <i class="zmdi zmdi-view-dashboard"></i> <span>Dashboard</span>
        </a>
      </li>
      <li>
        <a href="{{url('/admin-dashboard/users')}}">
          <i class="zmdi zmdi-view-dashboard"></i> <span>Users</span>
        </a>
      </li>
      <li>
        <a href="{{url('/admin-dashboard/car-marks')}}">
          <i class="zmdi zmdi-view-dashboard"></i> <span>Car Marks</span>
        </a>
      </li>
      <li>
        <a href="{{url('/admin-dashboard/car-models')}}">
          <i class="zmdi zmdi-view-dashboard"></i> <span>Car Models</span>
        </a>
      </li>
      <li>
        <a href="{{url('/admin-dashboard/cars')}}">
          <i class="zmdi zmdi-view-dashboard"></i> <span>Drivers Cars</span>
        </a>
      </li>
      <li>
        <a href="{{url('/admin-dashboard/trips')}}">
          <i class="zmdi zmdi-view-dashboard"></i> <span>Trips</span>
        </a>
      </li>
      <li>
        <a href="{{url('/admin-dashboard/settings')}}">
          <i class="zmdi zmdi-view-dashboard"></i> <span>Settings</span>
        </a>
      </li>
      <li>
        <a href="{{url('/admin-dashboard/reasons-cancelling-trips')}}">
          <i class="zmdi zmdi-view-dashboard"></i> <span>Trip Cancellation Reason</span>
        </a>
      </li>

    </ul>

</div>