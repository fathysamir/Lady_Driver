@extends('dashboard.layout.app')
@section('title', 'Dashboard - drivers')
@section('content')
<style>
    #exportCitySelect option {
    background-color: #ffffff !important;
    color: #111111 !important;
}
    .pagination { display: inline-flex; }

    .user-status {
        position: absolute;
        display: inline-block;
        width: 10px; height: 10px;
        border-radius: 50%;
        margin-left: -25%;
        margin-bottom: 8%;
    }
    .online  { background-color: green; }
    .offline { background-color: gray; }
    .filled  { color: gold; }

    .user-profile { position: relative; }
    .user-avatar  { width: 40px; height: 40px; cursor: pointer; position: relative; }

    .avatar-preview {
        display: none;
        position: fixed;
        justify-content: center;
        align-items: center;
        top: 50%; left: 50%;
        height: 600px; width: 800px;
        transform: translate(-50%, -50%);
        z-index: 1000;
        background-color: rgba(0,0,0,0.8);
        padding: 10px;
        border-radius: 8px;
        box-shadow: 0 4px 8px rgba(0,0,0,0.2);
    }
    .avatar-preview img { width: 100%; height: 100%; border-radius: 5px; }
    .user-profile:hover .avatar-preview { display: block; }
    .export-dropdown { position: relative; display: inline-block; }


    .export-dropdown-menu {
        display: none;
        position: absolute;
        top: 100%; left: 0;
        z-index: 999;
        min-width: 200px;
        border-radius: 4px;
        overflow: hidden;
        box-shadow: 0 4px 8px rgba(0,0,0,0.15);
    }
    .export-dropdown-menu a {
        display: block; padding: 8px 16px;
        background-color: #ffffff !important; color: #212529 !important;
        text-decoration: none; font-size: 0.875rem;
        white-space: nowrap; border: 1px solid rgba(0,0,0,.125);
        border-top: none;
    }
    .export-dropdown-menu a:first-child { border-top: 1px solid rgba(0,0,0,.125); }
    .export-dropdown-menu a:hover { background-color: #f0f0f0 !important; color: #212529 !important; text-decoration: none; }
    .export-dropdown:hover .export-dropdown-menu { display: block; }

    /* Bulk-action bar */
    #bulkActionBar {
        display: none;
        align-items: center;
        gap: 12px;
        padding: 10px 16px;
        margin-bottom: 10px;
        background: #fff3cd;
        border: 1px solid #ffc107;
        border-radius: 8px;
        font-size: 14px;
        color: #333;
    }
    #bulkActionBar.visible { display: flex; }
    #bulkDeleteBtn {
        padding: 6px 16px;
        background: #f44336; color: #fff;
        border: none; border-radius: 6px;
        cursor: pointer; font-size: 14px;
    }
    #bulkDeleteBtn:hover { background: #d32f2f; }
    #deselectAllBtn {
        padding: 6px 16px;
        background: #6c757d; color: #fff;
        border: none; border-radius: 6px;
        cursor: pointer; font-size: 14px;
    }
    #deselectAllBtn:hover { background: #5a6268; }

    tr.row-selected { background-color: rgba(244, 67, 54, 0.07) !important; }
    th.col-check, td.col-check { width: 40px; text-align: center; }
</style>

    <div class="content-wrapper">
        <div class="container-fluid">
            <div class="row">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-body">

                            @php
                                $authUser            = auth()->user();
                                $isSuperAdmin        = $authUser->hasRole('Super Admin');
                                $isSupervisor        = $authUser->hasRole('Supervisor');
                                $isModeratorStandard = $authUser->hasRole('Moderator Standard');
                                $isModeratorComfort  = $authUser->hasRole('Moderator Comfort');
                                $isModeratorScooter  = $authUser->hasRole('Moderator Scooter');
                                $isModeratorClient   = $authUser->hasRole('Moderator Client');
                                $isAccountant        = $authUser->hasRole('Accountant');
                            @endphp

                            <div>
                                <form id="searchForm" class="search-bar"
                                    style="margin-bottom:1%;margin-right:20px;margin-left:0px;" method="post"
                                    action="{{ route('drivers') }}" enctype="multipart/form-data">
                                    @csrf
                                    <input type="hidden" name="type" value="{{ $type }}">

                                    <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:10px;">
                                        <h5 class="card-title" style="margin:0;">
                                            @if($type == 'comfort_cars')
                                                Comfort Drivers
                                            @elseif($type == 'scooters')
                                                Scooters
                                            @elseif($type == 'cars')
                                                Standard Drivers
                                            @else
                                                ALL Drivers
                                            @endif
                                            - {{ $count }}
                                        </h5>
                                    </div>

                                {{-- Action buttons row --}}
<div style="display:flex; align-items:center; gap:8px; margin-bottom:10px; flex-wrap:wrap;">

    @if(!$isModeratorClient && !$isAccountant)
        <a class="btn btn-light px-3"
            href="{{ url('/admin-dashboard/archived-drivers?type=' . $type) }}">
            Deleted Accounts</a>
    @endif

    @if($isSuperAdmin)
    <div class="export-dropdown">
        <button class="btn btn-light px-3" type="button">Export CSV</button>
        <div class="export-dropdown-menu">
            <a href="{{ route('drivers.export', array_merge(
                    ['type' => $type, 'export_scope' => 'all'],
                    array_filter(['search'=>request('search'),'status'=>request('status'),'city'=>request('city'),'online'=>request('online')], fn($v)=>$v!==null&&$v!=='')
                )) }}">Export All Drivers</a>

            <a href="javascript:void(0);" onclick="openCityExportModal(); event.stopPropagation();">
                Export by City</a>
            <a href="javascript:void(0);" onclick="openDateRangeModal(); event.stopPropagation();">
                Export by Date Range</a>
        </div>
    </div>
@elseif($isSupervisor)
    <a class="btn btn-light px-3" href="javascript:void(0);"
        onclick="openDateRangeModal()">Export by Date Range</a>
@endif

@if($isSuperAdmin || $isSupervisor || $isModeratorStandard || $isModeratorComfort || $isModeratorScooter)
<a class="btn btn-light px-3"
    href="{{ route('drivers.create', request()->query()) }}">
    Create Driver</a>
@endif

</div>

                                   {{-- Search row --}}
<div style="display:flex; align-items:center; gap:8px; margin-bottom:10px;">
    <button class="btn btn-light px-3" type="button" onclick="toggleFilters()">Filter</button>
    <input type="text" class="form-control" placeholder="Enter keywords"
        name="search" value="{{ request('search') }}" style="flex:1;">
    <button class="btn btn-light px-3" type="submit">
        <i class="bi bi-search"></i>
    </button>
</div>

                                    <div id="filterOptions" style="display: none; text-align:center;">
                                        <div style="display: flex;">
                                            <select class="form-control" style="width: 32%;margin: 0% 2% 0% 0%;" name="status">
                                                <option value="">Select Status</option>
                                                <option value="pending"   {{ request('status') == 'pending'   ? 'selected' : '' }}>Pending</option>
                                                <option value="confirmed" {{ request('status') == 'confirmed' ? 'selected' : '' }}>Confirmed</option>
                                                <option value="banned"    {{ request('status') == 'banned'    ? 'selected' : '' }}>Banned</option>
                                                <option value="blocked"   {{ request('status') == 'blocked'   ? 'selected' : '' }}>Blocked</option>
                                            </select>

                                            {{-- City filter: hidden for Supervisor (locked to Alexandria) --}}
                                            @if(!$isSupervisor)
                                                <select class="form-control" style="width: 32%;margin: 0% 2% 0% 0%;" name="city">
                                                    <option value="">Select City</option>
                                                    @foreach ($cities as $city)
                                                        <option value="{{ $city->id }}" {{ request('city') == $city->id ? 'selected' : '' }}>
                                                            {{ $city->name }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            @else
                                                {{-- Show locked city for Supervisor --}}
                                                <div class="form-control" style="width: 32%;margin: 0% 2% 0% 0%;background:#2a2e35;color:#aaa;cursor:not-allowed;">
                                                    <i class="bi bi-lock-fill"></i> Alexandria (locked)
                                                </div>
                                            @endif

                                            <select class="form-control" style="width: 32%;margin: 0% 2% 0% 0%;" name="online">
                                                <option value="">Select Online Status</option>
                                                <option value="1" {{ request('online') === '1' ? 'selected' : '' }}>Online</option>
                                                <option value="0" {{ request('online') === '0' ? 'selected' : '' }}>Offline</option>
                                            </select>
                                        </div>
                                        <button class="btn btn-light px-5" style="margin-top:10px" type="submit">Apply Filters</button>
                                    </div>
                                </form>
                            </div>

                            {{-- ── Bulk-action bar (only for roles that can delete) ── --}}
                            @if(!$isModeratorClient && !$isAccountant)
                                <div id="bulkActionBar">
                                    <span id="selectedCount">0</span> driver(s) selected
                                    <button id="bulkDeleteBtn" onclick="showBulkConfirmationPopup()">
                                        <span class="bi bi-trash"></span> Delete Selected
                                    </button>
                                    <button id="deselectAllBtn" onclick="deselectAll()">
                                        Deselect All
                                    </button>
                                </div>
                            @endif

                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            @if(!$isModeratorClient && !$isAccountant)
                                                <th class="col-check">
                                                    <input type="checkbox" id="selectAllCheckbox"
                                                        title="Select all on this page"
                                                        onclick="toggleSelectAll(this)">
                                                </th>
                                            @else
                                                <th class="col-check"></th>
                                            @endif
                                            <th scope="col">#</th>
                                            <th scope="col">Name</th>
                                            <th scope="col">Email</th>
                                            <th scope="col">Phone Number</th>
                                            <th scope="col">Status</th>
                                            <th scope="col">Level</th>
                                            <th scope="col">Join Date</th>
                                            <th scope="col">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @if (!empty($all_users) && $all_users->count())
                                            @php
                                                $counter = ($all_users->currentPage() - 1) * $all_users->perPage() + 1;
                                            @endphp
                                            @foreach ($all_users as $user)
                                                @php
                                                    $avatarUrl = getFirstMediaUrl($user, $user->avatarCollection) ?? asset('dashboard/user_avatar.png');
                                                @endphp
                                                <tr id="row-{{ $user->id }}"
                                                    onclick="handleRowClick(event, {{ $user->id }}, '{{ route('edit.driver', ['id' => $user->id] + request()->query()) }}')"
                                                    style="cursor: pointer;">

                                                    <td class="col-check" onclick="event.stopPropagation()">
                                                        @if(!$isModeratorClient && !$isAccountant)
                                                            <input type="checkbox"
                                                                class="row-checkbox"
                                                                value="{{ $user->id }}"
                                                                onchange="onRowCheckChange(this)">
                                                        @endif
                                                    </td>

                                                    <td>{{ $counter++ }}</td>
                                                    <td>
                                                        <span class="user-profile">
                                                            <img src="{{ $avatarUrl }}"
                                                                class="img-circle user-avatar"
                                                                alt="user avatar"
                                                                onerror="this.src='{{ asset('dashboard/user_avatar.png') }}'; this.onerror=null;">
                                                            <span class="user-status {{ $user->is_online ? 'online' : 'offline' }}"></span>
                                                            <div class="avatar-preview">
                                                                <img src="{{ $avatarUrl }}"
                                                                    alt="Avatar Preview"
                                                                    onerror="this.src='{{ asset('dashboard/user_avatar.png') }}'; this.onerror=null;">
                                                            </div>
                                                        </span>
                                                        {!! highlight($user->name, $search ?? '') !!}
                                                    </td>
                                                    <td>{!! highlight($user->email, $search ?? '') !!}</td>
                                                    <td>{!! highlight($user->country_code . $user->phone, $search ?? '') !!}</td>
                                                    <td>
                                                        @if ($user->status == 'pending')
                                                            <span class="badge badge-secondary" style="background-color:rgb(143,118,9);width:100%;">Pending</span>
                                                        @elseif($user->status == 'confirmed')
                                                            <span class="badge badge-secondary" style="background-color:rgb(50,134,50);width:100%;">Confirmed</span>
                                                        @elseif($user->status == 'banned')
                                                            <span class="badge badge-secondary" style="background-color:rgb(61,27,255);width:100%;">Banned</span>
                                                        @else
                                                            <span class="badge badge-secondary" style="background-color:rgb(255,0,0);width:100%;">Blocked</span>
                                                        @endif
                                                    </td>

                                                    <td>LV {{ $user->level }}</td>
                                                    <td>{{ $user->created_at->format('d.M.Y h:i a') }}</td>
                                                    <td>
                                                        @php
                                                            $edit_per = [
                                                                'cars'         => 'drivers.standard.car.edit',
                                                                'comfort_cars' => 'drivers.comfort.car.edit',
                                                                'scooters'     => 'drivers.scooter.edit',
                                                            ];
                                                            $delete_per = [
                                                                'cars'         => 'drivers.standard.car.delete',
                                                                'comfort_cars' => 'drivers.comfort.car.delete',
                                                                'scooters'     => 'drivers.scooter.delete',
                                                            ];
                                                        @endphp
                                                        @can($edit_per[$type??'cars'])
                                                            <a href="{{ route('edit.driver', ['id' => $user->id] + request()->query()) }}"
                                                                style="margin-right: 1rem;" onclick="event.stopPropagation()">
                                                                <span class="bi bi-pen" style="font-size:1rem;color:rgb(255,255,255);"></span>
                                                            </a>
                                                        @endcan
                                                        @can($delete_per[$type??'cars'])
                                                            <a onclick='event.stopPropagation(); showConfirmationPopup("{{ url('/admin-dashboard/driver/delete/' . $user->id) . '?' . http_build_query(request()->query()) }}","{{ $user->name }}","{{ $avatarUrl }}")'>
                                                                <span class="bi bi-trash" style="font-size:1rem;color:rgb(255,255,255);"></span>
                                                            </a>
                                                        @endcan
                                                    </td>
                                                </tr>
                                            @endforeach
                                        @else
                                            <tr>
                                                <td colspan="9">There are no Drivers.</td>
                                            </tr>
                                        @endif
                                    </tbody>
                                </table>
                                <div style="text-align: center;">
                                    {!! $all_users->appends([
                                        'search' => request('search'),
                                        'status' => request('status'),
                                        'city'   => request('city'),
                                        'online' => request('online'),
                                        'type'   => request('type'),
                                    ])->links('pagination::bootstrap-4') !!}
                                </div>
                            </div>

                        </div>
                    </div>
                </div>
            </div>
            <div class="overlay toggle-menu"></div>
        </div>
    </div>

    {{-- ── Single Delete Confirmation Modal ── --}}
    <div class="modal fade" id="confirmationPopup" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" style="color:black;">Are you sure you want to delete this driver?</h5>
                    <button type="button" class="close" onclick="hideConfirmationPopup()" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body" style="color:black;">
                    <div class="form-group">
                        <div style="width:100%;display:flex;justify-content:center;">
                            <img id="deletedUserAvatar" src="{{ asset('dashboard/logo.png') }}"
                                class="logo-icon" alt="logo icon"
                                style="width:100px;height:100px;border-radius:50%;"
                                onerror="this.src='{{ asset('dashboard/user_avatar.png') }}'; this.onerror=null;">
                        </div>
                        <div style="width:100%;display:flex;justify-content:center;">
                            <h5 class="logo-text" style="color:black;font-weight:bold;" id="deletedNameInput"></h5>
                        </div>
                    </div>
                    <button onclick="hideConfirmationPopup()"
                        style="background-color:#5f6360;color:white;padding:10px 20px;border:none;cursor:pointer;width:48%;border-radius:10px;">
                        <span class="bi bi-x"></span> Cancel
                    </button>
                    <button onclick="deleteUser()"
                        style="background-color:#f44336;color:white;padding:10px 20px;border:none;cursor:pointer;margin-right:10px;width:48%;border-radius:10px;">
                        <span class="bi bi-trash"></span> Delete
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- ── Bulk Delete Confirmation Modal ── --}}
    <div class="modal fade" id="bulkConfirmationPopup" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" style="color:black;">
                        Delete <span id="bulkDeleteCount">0</span> selected driver(s)?
                    </h5>
                    <button type="button" class="close" onclick="hideBulkConfirmationPopup()" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body" style="color:black;">
                    <p style="margin-bottom:20px;">
                        This action cannot be undone. All selected drivers will be moved to deleted accounts.
                    </p>
                    <button onclick="hideBulkConfirmationPopup()"
                        style="background-color:#5f6360;color:white;padding:10px 20px;border:none;cursor:pointer;width:48%;border-radius:10px;">
                        <span class="bi bi-x"></span> Cancel
                    </button>
                    <button onclick="executeBulkDelete()"
                        style="background-color:#f44336;color:white;padding:10px 20px;border:none;cursor:pointer;margin-right:10px;width:48%;border-radius:10px;">
                        <span class="bi bi-trash"></span> Delete All
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- ── Date Range Export Modal (Super Admin + Supervisor) ── --}}
    @if($isSuperAdmin || $isSupervisor)
    <div class="modal fade" id="dateRangeModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content" style="border-radius:12px;border:none;overflow:hidden;">
                <div style="display:flex;align-items:center;justify-content:space-between;
                            padding:16px 20px;border-bottom:1px solid #e5e7eb;background:#fff;">
                    <div>
                        <span style="font-size:15px;font-weight:500;color:#111;">Export by date range</span>
                        @if($isSupervisor)
                            <div style="font-size:12px;color:#e65100;margin-top:3px;">
                                <i class="bi bi-exclamation-circle"></i> Maximum range: 2 months
                            </div>
                        @endif
                    </div>
                    <button type="button" onclick="closeDateRangeModal()" aria-label="Close"
                        style="background:none;border:none;cursor:pointer;font-size:20px;color:#6b7280;line-height:1;padding:0;">
                        &times;
                    </button>
                </div>
                <div style="padding:20px;background:#fff;">
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:20px;">
                        <div>
                            <label for="exportDateFrom" style="display:block;font-size:12px;color:#6b7280;margin-bottom:6px;">From</label>
                            <input type="date" id="exportDateFrom"
                                style="width:100%;box-sizing:border-box;padding:8px 10px;font-size:14px;
                                       color:#111;background:#fff;border:1px solid #d1d5db;border-radius:8px;outline:none;">
                        </div>
                        <div>
                            <label for="exportDateTo" style="display:block;font-size:12px;color:#6b7280;margin-bottom:6px;">To</label>
                            <input type="date" id="exportDateTo"
                                style="width:100%;box-sizing:border-box;padding:8px 10px;font-size:14px;
                                       color:#111;background:#fff;border:1px solid #d1d5db;border-radius:8px;outline:none;">
                        </div>
                    </div>
                    <p id="dateRangeError"       style="display:none;font-size:12px;color:#dc2626;margin:0 0 14px;">Please select both dates.</p>
                    <p id="dateRangeOrderError"  style="display:none;font-size:12px;color:#dc2626;margin:0 0 14px;">"From" date must be before or equal to "To" date.</p>
                    <p id="dateRangeMonthError"  style="display:none;font-size:12px;color:#dc2626;margin:0 0 14px;">Date range cannot exceed 2 months.</p>
                    <div style="display:flex;gap:8px;justify-content:flex-end;">
                        <button type="button" onclick="closeDateRangeModal()"
                            style="padding:8px 18px;border-radius:8px;border:1px solid #d1d5db;background:#fff;color:#374151;font-size:14px;cursor:pointer;">
                            Cancel
                        </button>
                        <button type="button" onclick="submitDateRangeExport()"
                            style="padding:8px 18px;border-radius:8px;border:1px solid #d1d5db;background:#fff;color:#374151;font-size:14px;cursor:pointer;">
                            Export CSV
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- ── City Export Modal (Super Admin only) ── --}}
@if($isSuperAdmin)
<div class="modal fade" id="cityExportModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content" style="border-radius:12px;border:none;overflow:hidden;">
            <div style="display:flex;align-items:center;justify-content:space-between;
                        padding:16px 20px;border-bottom:1px solid #e5e7eb;background:#fff;">
                <span style="font-size:15px;font-weight:500;color:#111;">Export by City</span>
                <button type="button" onclick="closeCityExportModal()" aria-label="Close"
                    style="background:none;border:none;cursor:pointer;font-size:20px;color:#6b7280;line-height:1;padding:0;">
                    &times;
                </button>
            </div>
            <div style="padding:20px;background:#fff;">
                <label for="exportCitySelect" style="display:block;font-size:12px;color:#6b7280;margin-bottom:6px;">
                    Select City
                </label>
                <select id="exportCitySelect"
                    style="width:100%;box-sizing:border-box;padding:8px 10px;font-size:14px;
                           color:#111;background:#fff;border:1px solid #d1d5db;border-radius:8px;outline:none;margin-bottom:16px;">
                    <option value="" style="background:#fff;color:#111;">-- Select a city --</option>
                    @foreach($cities as $cityItem)
                        <option value="{{ $cityItem->id }}" style="background:#fff;color:#111;">
                            {{ $cityItem->name }}
                        </option>
                    @endforeach
                </select>
                <p id="cityExportError" style="display:none;font-size:12px;color:#dc2626;margin:0 0 14px;">
                    Please select a city.
                </p>
                <div style="display:flex;gap:8px;justify-content:flex-end;">
                    <button type="button" onclick="closeCityExportModal()"
                        style="padding:8px 18px;border-radius:8px;border:1px solid #d1d5db;background:#fff;color:#374151;font-size:14px;cursor:pointer;">
                        Cancel
                    </button>
                    <button type="button" onclick="submitCityExport()"
                        style="padding:8px 18px;border-radius:8px;border:1px solid #d1d5db;background:#fff;color:#374151;font-size:14px;cursor:pointer;">
                        Export CSV
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
@endif

@endsection

@push('scripts')
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
    // ── City export modal ──────────────────────────────────────────────────────
function openCityExportModal() {
    const el = document.getElementById('cityExportModal');
    if (!el) return;
    document.getElementById('exportCitySelect').value = '';
    document.getElementById('cityExportError').style.display = 'none';
    const modal = new bootstrap.Modal(el, {});
    modal.show();
}

function closeCityExportModal() {
    const modalEl = document.getElementById('cityExportModal');
    if (!modalEl) return;
    const modal = bootstrap.Modal.getInstance(modalEl);
    if (modal) modal.hide();
}

function submitCityExport() {
    const cityId = document.getElementById('exportCitySelect').value;
    document.getElementById('cityExportError').style.display = 'none';

    if (!cityId) {
        document.getElementById('cityExportError').style.display = 'block';
        return;
    }

    const params = new URLSearchParams({
        type:         '{{ $type }}',
        export_scope: 'all',
    city:         cityId,
    });
    @if(request('search')) params.append('search', '{{ request('search') }}'); @endif
    @if(request('status')) params.append('status', '{{ request('status') }}'); @endif
    @if(request()->filled('online')) params.append('online', '{{ request('online') }}'); @endif

    closeCityExportModal();
    window.location.href = '{{ route('drivers.export') }}?' + params.toString();
}
    // ── Single-row delete ──────────────────────────────────────────────────────
    function showConfirmationPopup(deleteUrl, name, imgSrc) {
        document.getElementById('deletedNameInput').textContent = name;
        document.getElementById('deletedUserAvatar').src = imgSrc;
        const myModal = new bootstrap.Modal(document.getElementById('confirmationPopup'), {});
        myModal.show();
        document.getElementById('confirmationPopup').setAttribute('data-delete-url', deleteUrl);
    }

    function hideConfirmationPopup() {
        var modal = document.getElementById('confirmationPopup');
        modal.classList.remove('show');
        modal.style.display = 'none';
        document.body.classList.remove('modal-open');
        var backdrop = document.getElementsByClassName('modal-backdrop')[0];
        if (backdrop) backdrop.remove();
    }

    function deleteUser() {
        const deleteUrl = document.getElementById('confirmationPopup').getAttribute('data-delete-url');
        window.location.href = deleteUrl;
    }

    // ── Bulk-select (persists across pages via localStorage) ───────────────────
    const STORAGE_KEY = 'bulk_selected_driver_ids_{{ $type }}';

    function getSavedIds() {
        try {
            const raw = localStorage.getItem(STORAGE_KEY);
            return raw ? JSON.parse(raw) : [];
        } catch(e) { return []; }
    }

    function saveIds(ids) {
        localStorage.setItem(STORAGE_KEY, JSON.stringify(ids));
    }

    function getSelectedIds() {
        return getSavedIds();
    }

    function onRowCheckChange(checkbox) {
        let ids = getSavedIds();
        const id  = checkbox.value;
        const row = document.getElementById('row-' + id);
        if (checkbox.checked) {
            if (!ids.includes(id)) ids.push(id);
            row.classList.add('row-selected');
        } else {
            ids = ids.filter(i => i !== id);
            row.classList.remove('row-selected');
        }
        saveIds(ids);
        updateBulkBar();
    }

    function toggleSelectAll(masterCheckbox) {
        let ids = getSavedIds();
        document.querySelectorAll('.row-checkbox').forEach(cb => {
            const row = document.getElementById('row-' + cb.value);
            cb.checked = masterCheckbox.checked;
            if (masterCheckbox.checked) {
                if (!ids.includes(cb.value)) ids.push(cb.value);
                row.classList.add('row-selected');
            } else {
                ids = ids.filter(i => i !== cb.value);
                row.classList.remove('row-selected');
            }
        });
        saveIds(ids);
        updateBulkBar();
    }

    function deselectAll() {
        saveIds([]);
        document.querySelectorAll('.row-checkbox').forEach(cb => {
            cb.checked = false;
            document.getElementById('row-' + cb.value).classList.remove('row-selected');
        });
        const selectAll = document.getElementById('selectAllCheckbox');
        if (selectAll) selectAll.checked = false;
        updateBulkBar();
    }

    function updateBulkBar() {
        const ids     = getSavedIds();
        const bar     = document.getElementById('bulkActionBar');
        if (!bar) return;
        const countEl = document.getElementById('selectedCount');
        countEl.textContent = ids.length;
        ids.length > 0 ? bar.classList.add('visible') : bar.classList.remove('visible');

        const pageBoxes     = document.querySelectorAll('.row-checkbox');
        const checkedOnPage = Array.from(pageBoxes).filter(cb => ids.includes(cb.value));
        const selectAll     = document.getElementById('selectAllCheckbox');
        if (selectAll) {
            selectAll.indeterminate = checkedOnPage.length > 0 && checkedOnPage.length < pageBoxes.length;
            selectAll.checked       = pageBoxes.length > 0 && checkedOnPage.length === pageBoxes.length;
        }
    }

    // Restore checkmarks on every page load
    document.addEventListener('DOMContentLoaded', function () {
        const ids = getSavedIds();
        document.querySelectorAll('.row-checkbox').forEach(cb => {
            if (ids.includes(cb.value)) {
                cb.checked = true;
                document.getElementById('row-' + cb.value).classList.add('row-selected');
            }
        });
        updateBulkBar();
    });

    function handleRowClick(event, id, url) {
        if (event.target.type === 'checkbox') return;
        window.location.href = url;
    }

    // ── Bulk delete modal ──────────────────────────────────────────────────────
    function showBulkConfirmationPopup() {
        const ids = getSelectedIds();
        if (ids.length === 0) return;
        document.getElementById('bulkDeleteCount').textContent = ids.length;
        const modal = new bootstrap.Modal(document.getElementById('bulkConfirmationPopup'), {});
        modal.show();
    }

    function hideBulkConfirmationPopup() {
        const modalEl = document.getElementById('bulkConfirmationPopup');
        const modal   = bootstrap.Modal.getInstance(modalEl);
        if (modal) modal.hide();
    }

    function executeBulkDelete() {
        const ids = getSelectedIds();
        if (ids.length === 0) return;

        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '{{ route('drivers.bulk_delete') }}';

        const csrf  = document.createElement('input');
        csrf.type   = 'hidden';
        csrf.name   = '_token';
        csrf.value  = '{{ csrf_token() }}';
        form.appendChild(csrf);

        const typeInput = document.createElement('input');
        typeInput.type  = 'hidden';
        typeInput.name  = 'type';
        typeInput.value = '{{ $type }}';
        form.appendChild(typeInput);

        ids.forEach(id => {
            const input = document.createElement('input');
            input.type  = 'hidden';
            input.name  = 'ids[]';
            input.value = id;
            form.appendChild(input);
        });

        localStorage.removeItem(STORAGE_KEY);
        document.body.appendChild(form);
        form.submit();
    }

    // ── Search ─────────────────────────────────────────────────────────────────
    $(document).ready(function() {
        $('#submitForm').on('click', function() {
            $('#searchForm').submit();
        });
    });

    // ── Filters toggle ─────────────────────────────────────────────────────────
    function toggleFilters() {
        var filterOptions = document.getElementById("filterOptions");
        filterOptions.style.display = filterOptions.style.display === "none" ? "block" : "none";
    }

    // ── Date range export ──────────────────────────────────────────────────────
    @if($isSuperAdmin || $isSupervisor)
    const IS_SUPERVISOR = {{ $isSupervisor ? 'true' : 'false' }};

    function openDateRangeModal() {
        document.getElementById('exportDateFrom').value = '';
        document.getElementById('exportDateTo').value   = '';
        document.getElementById('dateRangeError').style.display      = 'none';
        document.getElementById('dateRangeOrderError').style.display = 'none';
        document.getElementById('dateRangeMonthError').style.display = 'none';
        const modal = new bootstrap.Modal(document.getElementById('dateRangeModal'), {});
        modal.show();
    }

    function closeDateRangeModal() {
        const modalEl = document.getElementById('dateRangeModal');
        const modal   = bootstrap.Modal.getInstance(modalEl);
        if (modal) modal.hide();
    }

    function submitDateRangeExport() {
        const from = document.getElementById('exportDateFrom').value;
        const to   = document.getElementById('exportDateTo').value;

        document.getElementById('dateRangeError').style.display      = 'none';
        document.getElementById('dateRangeOrderError').style.display = 'none';
        document.getElementById('dateRangeMonthError').style.display = 'none';

        if (!from || !to) {
            document.getElementById('dateRangeError').style.display = 'block';
            return;
        }

        const fromDate = new Date(from);
        const toDate   = new Date(to);

        if (fromDate > toDate) {
            document.getElementById('dateRangeOrderError').style.display = 'block';
            return;
        }

        // Supervisor: enforce max 2-month range
        if (IS_SUPERVISOR) {
            const maxTo = new Date(fromDate);
            maxTo.setMonth(maxTo.getMonth() + 2);
            if (toDate > maxTo) {
                document.getElementById('dateRangeMonthError').style.display = 'block';
                return;
            }
        }

        const params = new URLSearchParams({
            type: '{{ $type }}', export_scope: 'date_range', date_from: from, date_to: to,
        });
        @if(request('search')) params.append('search', '{{ request('search') }}'); @endif
        @if(request('status')) params.append('status', '{{ request('status') }}'); @endif
        @if(request('city'))   params.append('city',   '{{ request('city') }}');   @endif
        @if(request()->filled('online')) params.append('online', '{{ request('online') }}'); @endif

        closeDateRangeModal();
        window.location.href = '{{ route('drivers.export') }}?' + params.toString();
    }
    @endif
</script>
@endpush