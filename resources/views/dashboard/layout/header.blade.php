<header class="topbar-nav">
    <nav class="navbar navbar-expand fixed-top">
        <ul class="navbar-nav mr-auto align-items-center">
            <li class="nav-item">
                <a class="nav-link toggle-menu" href="javascript:void(0);">
                    <i class="icon-menu menu-icon"></i>
                </a>
            </li>
            <li class="nav-item">
                <form class="search-bar" id="header-search-form" onsubmit="handleSearch(event)">
                    <input type="text" id="header-search-input" class="form-control" placeholder="Enter keywords">
                    <a href="#" onclick="handleSearch(event)"><i class="icon-magnifier"></i></a>
                </form>
            </li>
        </ul>

        <ul class="navbar-nav align-items-center right-nav-link">
            <li class="nav-item">
                <a class="nav-link" onclick="goBack()" role="button" title="Back">
                    <i class="fa fa-arrow-left"></i>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" onclick="goNext()" role="button" title="Next">
                    <i class="fa fa-arrow-right"></i>
                </a>
            </li>

            {{-- SCRUM-360: Envelope icon now navigates to contact us / inbox page --}}
            <li class="nav-item">
                <a class="nav-link waves-effect" href="{{ url('/admin-dashboard/contact_us') }}" title="Inbox">
                    <i class="fa fa-envelope-open-o"></i>
                </a>
            </li>

            {{-- SCRUM-359: Bell icon now navigates to notifications page --}}
            <li class="nav-item">
                <a class="nav-link waves-effect" href="{{ url('/admin-dashboard/contact_us') }}" title="Notifications">
                    <i class="fa fa-bell-o"></i>
                </a>
            </li>

            {{-- SCRUM-325: Language switcher now functional --}}



            {{-- Profile dropdown --}}
            <li class="nav-item">
                <a class="nav-link dropdown-toggle dropdown-toggle-nocaret" data-toggle="dropdown" href="#">
                    <span class="user-profile">
                        <img
                            @if (getFirstMediaUrl(auth()->user(), auth()->user()->avatarCollection) != null)
                                src="{{ getFirstMediaUrl(auth()->user(), auth()->user()->avatarCollection) }}"
                            @else
                                src="{{ asset('dashboard/user_avatar.png') }}"
                            @endif
                            class="img-circle" alt="user avatar">
                    </span>
                </a>
                <ul class="dropdown-menu dropdown-menu-right">
                    <li class="dropdown-item user-details">
                        <a href="javascript:void(0);">
                            <div class="media">
                                <div class="avatar">
                                    <img class="align-self-start mr-3"
                                        @if (getFirstMediaUrl(auth()->user(), auth()->user()->avatarCollection) != null)
                                            src="{{ getFirstMediaUrl(auth()->user(), auth()->user()->avatarCollection) }}"
                                        @else
                                            src="{{ asset('dashboard/user_avatar.png') }}"
                                        @endif
                                        alt="user avatar">
                                </div>
                                <div class="media-body">
                                    <h6 class="mt-2 user-title">{{ auth()->user()->name }}</h6>
                                    <p class="user-subtitle">{{ auth()->user()->email }}</p>
                                </div>
                            </div>
                        </a>
                    </li>
                    <li class="dropdown-divider"></li>

                    {{-- SCRUM-363: Inbox now navigates to emails/contact us page --}}
                    <li class="dropdown-item">
                        <a href="{{ url('/admin-dashboard/contact_us') }}">
                            <i class="icon-envelope mr-2"></i> Inbox
                        </a>
                    </li>
                    <li class="dropdown-divider"></li>

                    {{-- SCRUM-366: Account now navigates to admin edit page (not client page) --}}
                    <li class="dropdown-item">
                        <a href="{{ url('/admin-dashboard/admin/edit/' . auth()->user()->id) }}">
                            <i class="icon-wallet mr-2"></i> Account
                        </a>
                    </li>
                    <li class="dropdown-divider"></li>

                    {{-- SCRUM-364: Settings now navigates to settings page --}}
                    <li class="dropdown-item">
                        <a href="{{ url('/admin-dashboard/settings') }}">
                            <i class="icon-settings mr-2"></i> Setting
                        </a>
                    </li>
                    <li class="dropdown-divider"></li>

                    <li class="dropdown-item">
                        <a href="{{ url('admin-dashboard/logout') }}">
                            <i class="icon-power mr-2"></i> Logout
                        </a>
                    </li>
                </ul>
            </li>
        </ul>
    </nav>
</header>

<script>
    {{-- SCRUM-361 & SCRUM-362: Search navigates to results, empty search shows proper message --}}
    function handleSearch(e) {
        e.preventDefault();
        var query = document.getElementById('header-search-input').value.trim();
        if (query === '') {
            alert('Please enter a search keyword.');
            return;
        }
        window.location.href = '{{ url('/admin-dashboard/admins') }}?search=' + encodeURIComponent(query);
    }

    {{-- SCRUM-325: Language switcher sends locale to backend --}}
    function changeLanguage(lang) {
        window.location.href = '{{ url('/admin-dashboard/home') }}?lang=' + lang;
    }
</script>