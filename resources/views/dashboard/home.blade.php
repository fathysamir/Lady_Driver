@extends('dashboard.layout.app')
@section('title', 'Lady Driver - Admin Home')

@section('content')
<div class="content-wrapper">
  <div class="container-fluid mt-4">

    <!-- ═══════════════════════════════════════════════════════
         PAGE HEADING
    ═══════════════════════════════════════════════════════ -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
      <h1 class="h3 mb-0">
        <i class="fa fa-tachometer" style="color:#4e73df;margin-left:8px;"></i>
        Dashboard
        <small style="font-size:14px;color:#aaa;margin-right:12px;">
          {{ now()->format('l, d M Y') }}
        </small>
      </h1>
    </div>

    <!-- ═══════════════════════════════════════════════════════
         STATS CARDS
    ═══════════════════════════════════════════════════════ -->
    <div class="row">

      {{-- Total Users --}}
      <div class="col-xl-3 col-md-6 mb-4">
        <div class="card shadow h-100 py-2" style="border-left:4px solid #4e73df;border-radius:10px;">
          <div class="card-body">
            <div class="row no-gutters align-items-center">
              <div class="col mr-2">
                <div style="font-size:11px;font-weight:700;color:#4e73df;text-transform:uppercase;letter-spacing:.5px;margin-bottom:6px;">
                  Total Clients
                  <span style="font-size:12px;font-weight:400;color:#aaa;">(عدد المستخدمين)</span>
                </div>
                <div class="h4 mb-0 font-weight-bold">
                  <span class="counter" data-target="{{ $totalUsers }}">0</span>
                </div>
              </div>
              <div class="col-auto">
                <i class="fa fa-users fa-2x" style="color:#dddfeb;"></i>
              </div>
            </div>
          </div>
        </div>
      </div>

      {{-- Total Drivers --}}
      <div class="col-xl-3 col-md-6 mb-4">
        <div class="card shadow h-100 py-2" style="border-left:4px solid #1cc88a;border-radius:10px;">
          <div class="card-body">
            <div class="row no-gutters align-items-center">
              <div class="col mr-2">
                <div style="font-size:11px;font-weight:700;color:#1cc88a;text-transform:uppercase;letter-spacing:.5px;margin-bottom:6px;">
                  Total Drivers
                  <span style="font-size:12px;font-weight:400;color:#aaa;">(عدد السائقين)</span>
                </div>
                <div class="h4 mb-0 font-weight-bold">
                  <span class="counter" data-target="{{ $totalDrivers }}">0</span>
                </div>
              </div>
              <div class="col-auto">
                <i class="fa fa-car fa-2x" style="color:#dddfeb;"></i>
              </div>
            </div>
          </div>
        </div>
      </div>

      {{-- Total Trips --}}
      <div class="col-xl-3 col-md-6 mb-4">
        <div class="card shadow h-100 py-2" style="border-left:4px solid #36b9cc;border-radius:10px;">
          <div class="card-body">
            <div class="row no-gutters align-items-center">
              <div class="col mr-2">
                <div style="font-size:11px;font-weight:700;color:#36b9cc;text-transform:uppercase;letter-spacing:.5px;margin-bottom:6px;">
                  Total Trips
                  <span style="font-size:12px;font-weight:400;color:#aaa;">(عدد الرحلات)</span>
                </div>
                <div class="h4 mb-0 font-weight-bold">
                  <span class="counter" data-target="{{ $totalTrips }}">0</span>
                </div>
              </div>
              <div class="col-auto">
                <i class="fa fa-road fa-2x" style="color:#dddfeb;"></i>
              </div>
            </div>
          </div>
        </div>
      </div>

      {{-- Total Revenue --}}
      <div class="col-xl-3 col-md-6 mb-4">
        <div class="card shadow h-100 py-2" style="border-left:4px solid #f6c23e;border-radius:10px;">
          <div class="card-body">
            <div class="row no-gutters align-items-center">
              <div class="col mr-2">
                <div style="font-size:11px;font-weight:700;color:#f6c23e;text-transform:uppercase;letter-spacing:.5px;margin-bottom:6px;">
                  Total Revenue
                  <span style="font-size:12px;font-weight:400;color:#aaa;">(إجمالي الإيرادات)</span>
                </div>
                <div class="h4 mb-0 font-weight-bold">
                  EGP <span class="counter" data-target="{{ $totalRevenue }}" data-decimals="2">0.00</span>
                </div>
              </div>
              <div class="col-auto">
                <i class="fa fa-money fa-2x" style="color:#dddfeb;"></i>
              </div>
            </div>
          </div>
        </div>
      </div>

    </div>
    <!-- END STATS CARDS -->

    <!-- ═══════════════════════════════════════════════════════
         CHARTS ROW
    ═══════════════════════════════════════════════════════ -->
    <div class="row">

      {{-- Bar Chart: Last 7 Days --}}
      <div class="col-xl-8 col-lg-7 mb-4">
        <div class="card shadow" style="border-radius:10px;">
          <div class="card-header py-3 d-flex justify-content-between align-items-center"
               style="border-radius:10px 10px 0 0;">
            <h6 class="m-0 font-weight-bold" style="color:#4e73df;">
              <i class="fa fa-bar-chart" style="margin-left:6px;"></i>
              Trips – Last 7 Days
              <span style="font-size:12px;font-weight:400;color:#aaa;">(الرحلات - آخر 7 أيام)</span>
            </h6>
          </div>
          <div class="card-body">
            <canvas id="tripsChart" height="110"></canvas>
          </div>
        </div>
      </div>

      {{-- Doughnut Chart: Trip Status --}}
      <div class="col-xl-4 col-lg-5 mb-4">
        <div class="card shadow" style="border-radius:10px;">
          <div class="card-header py-3" style="border-radius:10px 10px 0 0;">
            <h6 class="m-0 font-weight-bold" style="color:#4e73df;">
              <i class="fa fa-pie-chart" style="margin-left:6px;"></i>
              Trips by Status
              <span style="font-size:12px;font-weight:400;color:#aaa;">(الرحلات حسب الحالة)</span>
            </h6>
          </div>
          <div class="card-body d-flex justify-content-center align-items-center">
            <canvas id="statusChart" height="220"></canvas>
          </div>
        </div>
      </div>

    </div>
    <!-- END CHARTS ROW -->

    <!-- ═══════════════════════════════════════════════════════
         RECENT TRIPS TABLE
    ═══════════════════════════════════════════════════════ -->
    <div class="row">
      <div class="col-12 mb-4">
        <div class="card shadow" style="border-radius:10px;">
          <div class="card-header py-3 d-flex justify-content-between align-items-center"
               style="border-radius:10px 10px 0 0;">
            <h6 class="m-0 font-weight-bold" style="color:#4e73df;">
              <i class="fa fa-clock-o" style="margin-left:6px;"></i>
              Recent Trips
              <span style="font-size:12px;font-weight:400;color:#aaa;">(أحدث الرحلات)</span>
            </h6>
            <a href="{{ route('trips') }}" class="btn btn-sm btn-primary" style="border-radius:6px;">
              View All <i class="fa fa-arrow-left" style="margin-right:4px;"></i>
            </a>
          </div>
          <div class="card-body p-0">
            <div class="table-responsive">
              <table class="table table-bordered table-hover mb-0 text-center">
                <thead style="background:#2c2f3a;color:#a0a4b8;">
                  <tr>
                    <th style="padding:12px 8px;"># (الرقم)</th>
                    <th style="padding:12px 8px;">Code (الكود)</th>
                    <th style="padding:12px 8px;">Client (العميل)</th>
                    <th style="padding:12px 8px;">Car (السيارة)</th>
                    <th style="padding:12px 8px;">Type (النوع)</th>
                    <th style="padding:12px 8px;">Status (الحالة)</th>
                    <th style="padding:12px 8px;">Price (السعر)</th>
                    <th style="padding:12px 8px;">Payment (الدفع)</th>
                    <th style="padding:12px 8px;">Date (التاريخ)</th>
                    <th style="padding:12px 8px;">Action (الإجراء)</th>
                  </tr>
                </thead>
                <tbody>
                  @forelse($recentTrips as $trip)
                  <tr onclick="window.location='{{ route('view.trip', $trip->id) }}';"
                      style="cursor:pointer;">
                    <td>{{ $trip->id }}</td>
                    <td>{{ $trip->code ?? '—' }}</td>
                    <td>{{ $trip->user->name ?? 'N/A' }}</td>
                    <td>
                      @if($trip->type === 'scooter')
                        {{ $trip->scooter->motorcycleMark->en_name ?? 'N/A' }}
                      @else
                        {{ $trip->car->mark->name ?? 'N/A' }}
                      @endif
                    </td>
                    <td>{{ ucfirst(str_replace('_', ' ', $trip->type ?? '—')) }}</td>
                    <td>
                      @php
                        $statusColors = [
                          'pending'     => 'rgb(143,118,9)',
                          'scheduled'   => 'rgb(255,165,0)',
                          'in_progress' => 'rgb(52,40,223)',
                          'completed'   => 'rgb(50,134,50)',
                          'cancelled'   => 'rgb(255,0,0)',
                        ];
                        $color = $statusColors[$trip->status] ?? 'gray';
                      @endphp
                      <span class="badge badge-secondary"
                            style="background-color:{{ $color }};width:100%;padding:5px;">
                        {{ ucfirst(str_replace('_', ' ', $trip->status ?? 'unknown')) }}
                      </span>
                    </td>
                    <td>EGP {{ number_format($trip->total_price ?? 0, 2) }}</td>
                    <td>
                      @php
                        $isPaid   = $trip->status === 'completed';
                        $pbadge   = $isPaid ? 'rgb(50,134,50)' : 'rgb(143,118,9)';
                        $plabel   = $isPaid ? 'Paid' : ucwords($trip->payment_status ?? 'Unpaid');
                      @endphp
                      <span class="badge badge-secondary"
                            style="background-color:{{ $pbadge }};width:100%;padding:5px;">
                        {{ $plabel }}
                      </span>
                    </td>
                    <td>{{ $trip->created_at->format('Y-m-d') }}</td>
                    <td>
                      <a href="{{ route('view.trip', $trip->id) }}"
                         onclick="event.stopPropagation();"
                         class="btn btn-sm btn-info" style="border-radius:6px;">
                        <i class="fa fa-eye"></i>
                      </a>
                    </td>
                  </tr>
                  @empty
                  <tr>
                    <td colspan="10" class="text-muted py-4">
                      <i class="fa fa-inbox fa-2x mb-2 d-block"></i>
                      No trips found. (لا توجد رحلات)
                    </td>
                  </tr>
                  @endforelse
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    </div>
    <!-- END RECENT TRIPS TABLE -->

  </div>
</div>

<div class="overlay toggle-menu"></div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// ═══════════════════════════════════════════════════════════
// ANIMATED COUNTER
// ═══════════════════════════════════════════════════════════
document.addEventListener('DOMContentLoaded', function () {
    const counters = document.querySelectorAll('.counter');
    const duration = 1800; // ms

    function easeOutQuad(t) {
        return t * (2 - t);
    }

    counters.forEach(function(counter) {
        const target   = parseFloat(counter.getAttribute('data-target')) || 0;
        const decimals = parseInt(counter.getAttribute('data-decimals'))  || 0;
        let startTime  = null;

        function animate(timestamp) {
            if (!startTime) startTime = timestamp;
            const elapsed  = timestamp - startTime;
            const progress = Math.min(elapsed / duration, 1);
            const eased    = easeOutQuad(progress);
            const value    = eased * target;

            counter.textContent = decimals > 0
                ? value.toLocaleString('en-US', {
                    minimumFractionDigits: decimals,
                    maximumFractionDigits: decimals
                  })
                : Math.floor(value).toLocaleString('en-US');

            if (progress < 1) {
                requestAnimationFrame(animate);
            } else {
                // Lock to exact final value
                counter.textContent = decimals > 0
                    ? target.toLocaleString('en-US', {
                        minimumFractionDigits: decimals,
                        maximumFractionDigits: decimals
                      })
                    : target.toLocaleString('en-US');
            }
        }

        requestAnimationFrame(animate);
    });
});

// ═══════════════════════════════════════════════════════════
// BAR CHART — Last 7 Days
// ═══════════════════════════════════════════════════════════
new Chart(document.getElementById('tripsChart').getContext('2d'), {
    type: 'bar',
    data: {
        labels: @json($chartLabels),
        datasets: [{
            label: 'Trips',
            data:  @json($chartData),
            backgroundColor: 'rgba(78,115,223,0.5)',
            borderColor:     'rgba(78,115,223,1)',
            borderWidth: 2,
            borderRadius: 5,
        }]
    },
    options: {
        responsive: true,
        animation: {
            duration: 1200,
            easing: 'easeOutQuart',
        },
        plugins: {
            legend: { display: false },
            tooltip: {
                callbacks: {
                    label: ctx => ' ' + ctx.parsed.y + ' trips'
                }
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: { stepSize: 1, color: '#a0a4b8' },
                grid:  { color: 'rgba(255,255,255,0.05)' }
            },
            x: {
                ticks: { color: '#a0a4b8' },
                grid:  { display: false }
            }
        }
    }
});

// ═══════════════════════════════════════════════════════════
// DOUGHNUT CHART — Trip Status
// ═══════════════════════════════════════════════════════════
new Chart(document.getElementById('statusChart').getContext('2d'), {
    type: 'doughnut',
    data: {
        labels: ['Completed', 'Cancelled', 'In Progress', 'Pending'],
        datasets: [{
            data: @json($statusData),
            backgroundColor: [
                'rgb(50,134,50)',   // completed
                'rgb(255,0,0)',     // cancelled
                'rgb(52,40,223)',   // in_progress
                'rgb(143,118,9)',   // pending
            ],
            hoverOffset: 6,
        }]
    },
    options: {
        responsive: true,
        animation: {
            duration: 1400,
            easing: 'easeOutBounce',
        },
        plugins: {
            legend: {
                position: 'bottom',
                labels: { color: '#a0a4b8', padding: 16 }
            }
        }
    }
});
</script>
@endpush