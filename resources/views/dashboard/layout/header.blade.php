<header class="topbar-nav">
    <nav class="navbar navbar-expand fixed-top">
      <ul class="navbar-nav mr-auto align-items-center">
        <li class="nav-item">
          <a class="nav-link toggle-menu" href="javascript:void();">
          <i class="icon-menu menu-icon"></i>
        </a>
        </li>
        <li class="nav-item">
          <form class="search-bar">
            <input type="text" class="form-control" placeholder="Enter keywords">
            <a href="javascript:void();"><i class="icon-magnifier"></i></a>
          </form>
        </li>
      </ul>
        
      <ul class="navbar-nav align-items-center right-nav-link">
        <li class="nav-item">
            <a class="nav-link" onclick="goBack()"  role="button" title="Back">
                <i class="fa fa-arrow-left"></i>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" onclick="goNext()"  role="button" title="Next">
                <i class="fa fa-arrow-right"></i>
            </a>
        </li>
        <li class="nav-item dropdown-lg">
          <a class="nav-link dropdown-toggle dropdown-toggle-nocaret waves-effect" data-toggle="dropdown" href="javascript:void();">
          <i class="fa fa-envelope-open-o"></i></a>
        </li>
        <li class="nav-item dropdown-lg">
          <a class="nav-link dropdown-toggle dropdown-toggle-nocaret waves-effect" data-toggle="dropdown" href="javascript:void();">
          <i class="fa fa-bell-o"></i></a>
        </li>
        <li class="nav-item language">
          <a class="nav-link dropdown-toggle dropdown-toggle-nocaret waves-effect" data-toggle="dropdown" href="javascript:void();"><i class="fa fa-flag"></i></a>
          <ul class="dropdown-menu dropdown-menu-right">
              <li class="dropdown-item"> <i class="flag-icon flag-icon-gb mr-2"></i> English</li>
              <li class="dropdown-item"> <i class="flag-icon flag-icon-fr mr-2"></i> French</li>
              <li class="dropdown-item"> <i class="flag-icon flag-icon-cn mr-2"></i> Chinese</li>
              <li class="dropdown-item"> <i class="flag-icon flag-icon-de mr-2"></i> German</li>
            </ul>
        </li>
        <li class="nav-item">
          <a class="nav-link dropdown-toggle dropdown-toggle-nocaret" data-toggle="dropdown" href="#">
            <span class="user-profile"><img @if(getFirstMediaUrl(auth()->user(),auth()->user()->avatarCollection)!=null) src="{{getFirstMediaUrl(auth()->user(),auth()->user()->avatarCollection)}}" @else src="{{asset('dashboard/user_avatar.png')}}" @endif class="img-circle" alt="user avatar"></span>
          </a>
          <ul class="dropdown-menu dropdown-menu-right">
          <li class="dropdown-item user-details">
            <a href="javaScript:void();">
              <div class="media">
                <div class="avatar"><img class="align-self-start mr-3" @if(getFirstMediaUrl(auth()->user(),auth()->user()->avatarCollection)!=null) src="{{getFirstMediaUrl(auth()->user(),auth()->user()->avatarCollection)}}" @else src="{{asset('dashboard/user_avatar.png')}}" @endif alt="user avatar"></div>
                <div class="media-body">
                <h6 class="mt-2 user-title">{{auth()->user()->name}}</h6>
                <p class="user-subtitle">{{auth()->user()->email}}</p>
                </div>
              </div>
              </a>
            </li>
            <li class="dropdown-divider"></li>
            <li class="dropdown-item"><i class="icon-envelope mr-2"></i> Inbox</li>
            <li class="dropdown-divider"></li>
            <li class="dropdown-item"><a href="{{url('/admin-dashboard/user/edit/'.auth()->user()->id)}}"><i class="icon-wallet mr-2"></i> Account</a></li>
            <li class="dropdown-divider"></li>
            <li class="dropdown-item"><i class="icon-settings mr-2"></i> Setting</li>
            <li class="dropdown-divider"></li>
            <li class="dropdown-item"><a href="{{url('admin-dashboard/logout')}}"><i class="icon-power mr-2"></i> Logout</a></li>
          </ul>
        </li>
      </ul>
    </nav>
  </header>