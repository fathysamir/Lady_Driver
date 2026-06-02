@extends('dashboard.layout.app')
@section('title', 'Dashboard - create driver')
@section('content')
    <style>
        .section-divider {
            display: flex;
            align-items: center;
            margin: 24px 0 16px;
        }
        .section-divider h4 {
            margin-right: 10px;
            white-space: nowrap;
        }
        .section-divider hr {
            flex: 1;
            margin: 0;
        }

        #id-type-selector {
            display: flex;
            gap: 12px;
            margin-bottom: 16px;
        }
        .id-type-btn {
            flex: 1;
            padding: 10px;
            border: 1px solid rgba(255,255,255,0.2);
            border-radius: 8px;
            background: transparent;
            color: #fff;
            cursor: pointer;
            text-align: center;
            font-size: 14px;
            transition: background 0.2s, border-color 0.2s;
        }
        .id-type-btn.active {
            background-color: rgba(255, 230, 0, 0.15);
            border-color: rgb(255, 230, 0);
            color: rgb(255, 230, 0);
        }

        #vehicle-type-selector {
            display: flex;
            gap: 12px;
            margin-bottom: 16px;
        }
        .vehicle-type-btn {
            flex: 1;
            padding: 10px;
            border: 1px solid rgba(255,255,255,0.2);
            border-radius: 8px;
            background: transparent;
            color: #fff;
            cursor: pointer;
            text-align: center;
            font-size: 14px;
            transition: background 0.2s, border-color 0.2s;
        }
        .vehicle-type-btn.active {
            background-color: rgba(255, 230, 0, 0.15);
            border-color: rgb(255, 230, 0);
            color: rgb(255, 230, 0);
        }

        .ar {
            font-size: 12px;
            color: rgba(255,255,255,0.45);
            margin-right: 4px;
        }
    </style>

    <div class="content-wrapper">
        <div class="container-fluid">
            <div class="row mt-3">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-body">

                            <h5 class="card-title" style="margin-bottom: 20px;">
                                Create New Driver Account
                            </h5>

                            @if ($errors->any())
                                <div class="alert alert-danger" style="margin-bottom: 16px;">
                                    <ul style="margin:0; padding-left: 18px;">
                                        @foreach ($errors->all() as $error)
                                            <li>{{ $error }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif

                            <form method="POST" action="{{ route('drivers.store') }}" enctype="multipart/form-data">
                                @csrf

                                {{-- ── ACCOUNT CREDENTIALS ─────────────────────────────── --}}
                                <div class="section-divider">
                                    <h4>Account Credentials</h4><hr>
                                </div>

                                <div class="form-group">
                                    <label>Full Name <span class="ar">(الاسم بالكامل)</span><span style="color:red">*</span></label>
                                    <input type="text" class="form-control" name="name"
                                        placeholder="Enter full name" value="{{ old('name') }}" required>
                                    @error('name')
                                        <p style="color:red; margin-top:4px;">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div class="form-group">
                                    <label>Email <span class="ar">(البريد الإلكتروني)</span><span style="color:red">*</span></label>
                                    <input type="email" class="form-control" name="email"
                                        placeholder="Enter email address" value="{{ old('email') }}" required>
                                    @error('email')
                                        <p style="color:red; margin-top:4px;">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div class="form-group">
                                    <label>Temporary Password <span class="ar">(كلمة المرور المؤقتة)</span><span style="color:red">*</span></label>
                                    <input type="text" class="form-control" name="password"
                                        placeholder="Set a temporary password (min 8 chars)"
                                        value="{{ old('password') }}" required minlength="8"
                                        autocomplete="new-password">
                                    <small style="color: rgba(255,255,255,0.5);">
                                        Share this password with the driver so they can log in and change it
                                        شارك هذا الرقم السري مع السائق حتى يتمكن من تسجيل الدخول وتغييره
                                    </small>
                                    @error('password')
                                        <p style="color:red; margin-top:4px;">{{ $message }}</p>
                                    @enderror
                                </div>

                                {{-- ── PERSONAL INFO ───────────────────────────────────── --}}
                                <div class="section-divider">
                                    <h4>Personal Information</h4><hr>
                                </div>

                                <div class="form-group">
                                    <label>Profile Image <span class="ar">(الصورة الشخصية)</span><span style="color:red">*</span></label>
                                    @if(session('temp_upload_image'))
                                        <div style="margin-bottom:8px;">
                                            <img src="{{ asset('storage/' . session('temp_upload_image')) }}"
                                                style="height:80px; border-radius:6px;">
                                            <input type="hidden" name="temp_image" value="{{ session('temp_upload_image') }}">
                                            <small style="color:rgba(255,255,255,0.5); display:block;">Previously uploaded — upload again to replace.</small>
                                        </div>
                                    @endif
                                    <input type="file" class="form-control" name="image"
                                        accept="image/jpg,image/jpeg,image/png"
                                        {{ session('temp_upload_image') ? '' : 'required' }}>
                                    @error('image')
                                        <p style="color:red; margin-top:4px;">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div class="form-group">
                                    <label>Phone Number <span class="ar">(رقم الهاتف)</span><span style="color:red">*</span></label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <select name="country_code" class="form-control">
                                                @php $cc = old('country_code', '+20'); @endphp
                                                <option value="+20"  {{ $cc=='+20'  ? 'selected':'' }}>Egypt (+20)</option>
                                                <option value="+1"   {{ $cc=='+1'   ? 'selected':'' }}>USA / Canada (+1)</option>
                                                <option value="+44"  {{ $cc=='+44'  ? 'selected':'' }}>UK (+44)</option>
                                                <option value="+971" {{ $cc=='+971' ? 'selected':'' }}>UAE (+971)</option>
                                                <option value="+966" {{ $cc=='+966' ? 'selected':'' }}>Saudi Arabia (+966)</option>
                                                <option value="+965" {{ $cc=='+965' ? 'selected':'' }}>Kuwait (+965)</option>
                                                <option value="+974" {{ $cc=='+974' ? 'selected':'' }}>Qatar (+974)</option>
                                                <option value="+968" {{ $cc=='+968' ? 'selected':'' }}>Oman (+968)</option>
                                                <option value="+962" {{ $cc=='+962' ? 'selected':'' }}>Jordan (+962)</option>
                                                <option value="+961" {{ $cc=='+961' ? 'selected':'' }}>Lebanon (+961)</option>
                                                <option value="+249" {{ $cc=='+249' ? 'selected':'' }}>Sudan (+249)</option>
                                                <option value="+218" {{ $cc=='+218' ? 'selected':'' }}>Libya (+218)</option>
                                                <option value="+212" {{ $cc=='+212' ? 'selected':'' }}>Morocco (+212)</option>
                                                <option value="+216" {{ $cc=='+216' ? 'selected':'' }}>Tunisia (+216)</option>
                                                <option value="+213" {{ $cc=='+213' ? 'selected':'' }}>Algeria (+213)</option>
                                                <option value="+91"  {{ $cc=='+91'  ? 'selected':'' }}>India (+91)</option>
                                                <option value="+92"  {{ $cc=='+92'  ? 'selected':'' }}>Pakistan (+92)</option>
                                                <option value="+880" {{ $cc=='+880' ? 'selected':'' }}>Bangladesh (+880)</option>
                                                <option value="+33"  {{ $cc=='+33'  ? 'selected':'' }}>France (+33)</option>
                                                <option value="+49"  {{ $cc=='+49'  ? 'selected':'' }}>Germany (+49)</option>
                                                <option value="+39"  {{ $cc=='+39'  ? 'selected':'' }}>Italy (+39)</option>
                                                <option value="+34"  {{ $cc=='+34'  ? 'selected':'' }}>Spain (+34)</option>
                                                <option value="+7"   {{ $cc=='+7'   ? 'selected':'' }}>Russia (+7)</option>
                                                <option value="+86"  {{ $cc=='+86'  ? 'selected':'' }}>China (+86)</option>
                                                <option value="+81"  {{ $cc=='+81'  ? 'selected':'' }}>Japan (+81)</option>
                                                <option value="+82"  {{ $cc=='+82'  ? 'selected':'' }}>South Korea (+82)</option>
                                                <option value="+55"  {{ $cc=='+55'  ? 'selected':'' }}>Brazil (+55)</option>
                                                <option value="+27"  {{ $cc=='+27'  ? 'selected':'' }}>South Africa (+27)</option>
                                                <option value="+234" {{ $cc=='+234' ? 'selected':'' }}>Nigeria (+234)</option>
                                                <option value="+254" {{ $cc=='+254' ? 'selected':'' }}>Kenya (+254)</option>
                                                <option value="+90"  {{ $cc=='+90'  ? 'selected':'' }}>Turkey (+90)</option>
                                                <option value="+98"  {{ $cc=='+98'  ? 'selected':'' }}>Iran (+98)</option>
                                                <option value="+964" {{ $cc=='+964' ? 'selected':'' }}>Iraq (+964)</option>
                                                <option value="+963" {{ $cc=='+963' ? 'selected':'' }}>Syria (+963)</option>
                                                <option value="+967" {{ $cc=='+967' ? 'selected':'' }}>Yemen (+967)</option>
                                                <option value="+251" {{ $cc=='+251' ? 'selected':'' }}>Ethiopia (+251)</option>
                                                <option value="+255" {{ $cc=='+255' ? 'selected':'' }}>Tanzania (+255)</option>
                                                <option value="+256" {{ $cc=='+256' ? 'selected':'' }}>Uganda (+256)</option>
                                                <option value="+233" {{ $cc=='+233' ? 'selected':'' }}>Ghana (+233)</option>
                                                <option value="+225" {{ $cc=='+225' ? 'selected':'' }}>Ivory Coast (+225)</option>
                                                <option value="+237" {{ $cc=='+237' ? 'selected':'' }}>Cameroon (+237)</option>
                                                <option value="+260" {{ $cc=='+260' ? 'selected':'' }}>Zambia (+260)</option>
                                                <option value="+263" {{ $cc=='+263' ? 'selected':'' }}>Zimbabwe (+263)</option>
                                                <option value="+61"  {{ $cc=='+61'  ? 'selected':'' }}>Australia (+61)</option>
                                                <option value="+64"  {{ $cc=='+64'  ? 'selected':'' }}>New Zealand (+64)</option>
                                                <option value="+65"  {{ $cc=='+65'  ? 'selected':'' }}>Singapore (+65)</option>
                                                <option value="+60"  {{ $cc=='+60'  ? 'selected':'' }}>Malaysia (+60)</option>
                                                <option value="+66"  {{ $cc=='+66'  ? 'selected':'' }}>Thailand (+66)</option>
                                                <option value="+62"  {{ $cc=='+62'  ? 'selected':'' }}>Indonesia (+62)</option>
                                                <option value="+63"  {{ $cc=='+63'  ? 'selected':'' }}>Philippines (+63)</option>
                                                <option value="+84"  {{ $cc=='+84'  ? 'selected':'' }}>Vietnam (+84)</option>
                                            </select>
                                        </div>
                                        <input type="number" name="phone" class="form-control"
                                            placeholder="Phone number" value="{{ old('phone') }}" required>
                                    </div>
                                    @error('phone')
                                        <p style="color:red; margin-top:4px;">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div class="form-group">
                                    <label>City <span class="ar">(المدينة)</span><span style="color:red">*</span></label>
                                    <select class="form-control" name="city_id" required>
                                        <option value="">Select City</option>
                                        @foreach ($cities as $city)
                                            <option value="{{ $city->id }}"
                                                {{ old('city_id') == $city->id ? 'selected' : '' }}>
                                                {{ $city->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('city_id')
                                        <p style="color:red; margin-top:4px;">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div class="form-group">
                                    <label>Birth Date <span class="ar">(تاريخ الميلاد)</span><span style="color:red">*</span></label>
                                    <input type="date" class="form-control" name="birth_date"
                                        style="background-color: rgba(255,255,255,0.2);"
                                        value="{{ old('birth_date') }}"
                                        max="{{ now()->subYears(16)->format('Y-m-d') }}" required>
                                    <small style="color: rgba(255,255,255,0.5);">Driver must be at least 16 years old.</small>
                                    @error('birth_date')
                                        <p style="color:red; margin-top:4px;">{{ $message }}</p>
                                    @enderror
                                </div>

                                {{-- ── IDENTITY DOCUMENT ───────────────────────────────── --}}
                                <div class="section-divider">
                                    <h4>Identity Document <span class="ar">(وثيقة الهوية)</span></h4><hr>
                                </div>

                                <div id="id-type-selector">
                                    <button type="button" class="id-type-btn active" onclick="switchIdType('national')">
                                        National ID <span class="ar">(بطاقة وطنية)</span>
                                    </button>
                                    <button type="button" class="id-type-btn" onclick="switchIdType('passport')">
                                        Passport <span class="ar">(جواز السفر)</span>
                                    </button>
                                </div>
                                <input type="hidden" name="id_type" id="id_type_hidden" value="{{ old('id_type', 'national') }}">

                                {{-- National ID --}}
                                <div id="national-id-section">
                                    <div class="form-group">
                                        <label>National ID Number <span class="ar">(رقم البطاقة الوطنية)</span></label>
                                        <input type="number" class="form-control" name="national_id"
                                            placeholder="14-digit national ID" value="{{ old('national_id') }}">
                                        @error('national_id')
                                            <p style="color:red; margin-top:4px;">{{ $message }}</p>
                                        @enderror
                                    </div>
                                    <div class="form-group">
                                        <label>National ID Expiration Date <span class="ar">(تاريخ انتهاء البطاقة)</span></label>
                                        <input type="date" class="form-control" name="national_id_expire_date"
                                            style="background-color: rgba(255,255,255,0.2);"
                                            value="{{ old('national_id_expire_date') }}">
                                    </div>
                                    <div class="form-group">
                                        <label>ID Front Image <span class="ar">(صورة البطاقة - أمامية)</span></label>
                                        @if(session('temp_upload_ID_front_image'))
                                            <div style="margin-bottom:8px;">
                                                <img src="{{ asset('storage/' . session('temp_upload_ID_front_image')) }}"
                                                    style="height:80px; border-radius:6px;">
                                                <input type="hidden" name="temp_ID_front_image" value="{{ session('temp_upload_ID_front_image') }}">
                                                <small style="color:rgba(255,255,255,0.5); display:block;">Previously uploaded — upload again to replace.</small>
                                            </div>
                                        @endif
                                        <input type="file" class="form-control" name="ID_front_image"
                                            accept="image/jpg,image/jpeg,image/png">
                                        @error('ID_front_image')
                                            <p style="color:red; margin-top:4px;">{{ $message }}</p>
                                        @enderror
                                    </div>
                                    <div class="form-group">
                                        <label>ID Back Image <span class="ar">(صورة البطاقة - خلفية)</span></label>
                                        @if(session('temp_upload_ID_back_image'))
                                            <div style="margin-bottom:8px;">
                                                <img src="{{ asset('storage/' . session('temp_upload_ID_back_image')) }}"
                                                    style="height:80px; border-radius:6px;">
                                                <input type="hidden" name="temp_ID_back_image" value="{{ session('temp_upload_ID_back_image') }}">
                                                <small style="color:rgba(255,255,255,0.5); display:block;">Previously uploaded — upload again to replace.</small>
                                            </div>
                                        @endif
                                        <input type="file" class="form-control" name="ID_back_image"
                                            accept="image/jpg,image/jpeg,image/png">
                                        @error('ID_back_image')
                                            <p style="color:red; margin-top:4px;">{{ $message }}</p>
                                        @enderror
                                    </div>
                                </div>

                                {{-- Passport --}}
                                <div id="passport-section" style="display:none;">
                                    <div class="form-group">
                                        <label>Passport Number <span class="ar">(رقم جواز السفر)</span></label>
                                        <input type="text" class="form-control" name="passport_id"
                                            placeholder="Passport number" value="{{ old('passport_id') }}">
                                        @error('passport_id')
                                            <p style="color:red; margin-top:4px;">{{ $message }}</p>
                                        @enderror
                                    </div>
                                    <div class="form-group">
                                        <label>Passport Expiration Date <span class="ar">(تاريخ انتهاء جواز السفر)</span></label>
                                        <input type="date" class="form-control" name="passport_expire_date"
                                            style="background-color: rgba(255,255,255,0.2);"
                                            value="{{ old('passport_expire_date') }}">
                                    </div>
                                    <div class="form-group">
                                        <label>Passport Image <span class="ar">(صورة جواز السفر)</span></label>
                                        @if(session('temp_upload_passport_image'))
                                            <div style="margin-bottom:8px;">
                                                <img src="{{ asset('storage/' . session('temp_upload_passport_image')) }}"
                                                    style="height:80px; border-radius:6px;">
                                                <input type="hidden" name="temp_passport_image" value="{{ session('temp_upload_passport_image') }}">
                                                <small style="color:rgba(255,255,255,0.5); display:block;">Previously uploaded — upload again to replace.</small>
                                            </div>
                                        @endif
                                        <input type="file" class="form-control" name="passport_image"
                                            accept="image/jpg,image/jpeg,image/png">
                                        @error('passport_image')
                                            <p style="color:red; margin-top:4px;">{{ $message }}</p>
                                        @enderror
                                    </div>
                                </div>

                                {{-- ── DRIVING LICENSE ─────────────────────────────────── --}}
                                <div class="section-divider">
                                    <h4>Driving License <span class="ar">(رخصة القيادة)</span></h4><hr>
                                </div>

                                <div class="form-group">
                                    <label>License Number <span class="ar">(رقم الرخصة)</span><span style="color:red">*</span></label>
                                    <input type="text" class="form-control" name="driving_license_number"
                                        placeholder="License number" value="{{ old('driving_license_number') }}" required>
                                    @error('driving_license_number')
                                        <p style="color:red; margin-top:4px;">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div class="form-group">
                                    <label>License Expiration Date <span class="ar">(تاريخ انتهاء الرخصة)</span><span style="color:red">*</span></label>
                                    <input type="date" class="form-control" name="license_expire_date"
                                        style="background-color: rgba(255,255,255,0.2);"
                                        value="{{ old('license_expire_date') }}"
                                        min="{{ now()->format('Y-m-d') }}" required>
                                    @error('license_expire_date')
                                        <p style="color:red; margin-top:4px;">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div class="form-group">
                                    <label>License Front Image <span class="ar">(صورة الرخصة - أمامية)</span><span style="color:red">*</span></label>
                                    @if(session('temp_upload_license_front_image'))
                                        <div style="margin-bottom:8px;">
                                            <img src="{{ asset('storage/' . session('temp_upload_license_front_image')) }}"
                                                style="height:80px; border-radius:6px;">
                                            <input type="hidden" name="temp_license_front_image" value="{{ session('temp_upload_license_front_image') }}">
                                            <small style="color:rgba(255,255,255,0.5); display:block;">Previously uploaded — upload again to replace.</small>
                                        </div>
                                    @endif
                                    <input type="file" class="form-control" name="license_front_image"
                                        accept="image/jpg,image/jpeg,image/png"
                                        {{ session('temp_upload_license_front_image') ? '' : 'required' }}>
                                    @error('license_front_image')
                                        <p style="color:red; margin-top:4px;">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div class="form-group">
                                    <label>License Back Image <span class="ar">(صورة الرخصة - خلفية)</span><span style="color:red">*</span></label>
                                    @if(session('temp_upload_license_back_image'))
                                        <div style="margin-bottom:8px;">
                                            <img src="{{ asset('storage/' . session('temp_upload_license_back_image')) }}"
                                                style="height:80px; border-radius:6px;">
                                            <input type="hidden" name="temp_license_back_image" value="{{ session('temp_upload_license_back_image') }}">
                                            <small style="color:rgba(255,255,255,0.5); display:block;">Previously uploaded — upload again to replace.</small>
                                        </div>
                                    @endif
                                    <input type="file" class="form-control" name="license_back_image"
                                        accept="image/jpg,image/jpeg,image/png"
                                        {{ session('temp_upload_license_back_image') ? '' : 'required' }}>
                                    @error('license_back_image')
                                        <p style="color:red; margin-top:4px;">{{ $message }}</p>
                                    @enderror
                                </div>

                                {{-- ── VEHICLE ─────────────────────────────────────────── --}}
                                <div class="section-divider">
                                    <h4>Vehicle <span class="ar">(المركبة)</span></h4><hr>
                                </div>

                                <div id="vehicle-type-selector">
                                    <button type="button" class="vehicle-type-btn active" onclick="switchVehicle('car')">
                                        Car <span class="ar">(سيارة)</span>
                                    </button>
                                    <button type="button" class="vehicle-type-btn" onclick="switchVehicle('scooter')">
                                        Scooter <span class="ar">(دراجة)</span>
                                    </button>
                                </div>
                                <input type="hidden" name="vehicle_type" id="vehicle_type_hidden" value="{{ old('vehicle_type', 'car') }}">

                                <div class="form-group">
                                    <label>Color <span class="ar">(اللون)</span><span style="color:red">*</span></label>
                                    <input type="text" class="form-control" name="color"
                                        placeholder="e.g. White" value="{{ old('color') }}" required>
                                </div>

                                <div class="form-group">
                                    <label>Year <span class="ar">(سنة الصنع)</span><span style="color:red">*</span></label>
                                    <input type="number" class="form-control" name="year"
                                        placeholder="e.g. 2020" value="{{ old('year') }}"
                                        min="1990" max="{{ date('Y') }}" required>
                                    <small style="color: rgba(255,255,255,0.5);">
                                        Cars from {{ $comfort_year ?? '2020' }} onward are registered as Comfort.
                                    </small>
                                </div>

                                <div class="form-group">
                                    <label>Plate Number <span class="ar">(رقم اللوحة)</span><span style="color:red">*</span></label>
                                    <input type="text" class="form-control" name="plate_num"
                                        placeholder="Vehicle plate number" value="{{ old('plate_num') }}" required>
                                </div>

                                {{-- Car fields --}}
                                <div id="car-fields">
                                    <div class="form-group">
                                        <label>Car Mark <span class="ar">(ماركة السيارة)</span></label>
                                        <select class="form-control" name="car_mark_id">
                                            <option value="">Select Car Mark</option>
                                            @foreach ($carMarks ?? [] as $mark)
                                                <option value="{{ $mark->id }}"
                                                    {{ old('car_mark_id') == $mark->id ? 'selected' : '' }}>
                                                    {{ $mark->en_name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label>Car Model <span class="ar">(موديل السيارة)</span></label>
                                        <select class="form-control" name="car_model_id">
                                            <option value="">Select Car Model</option>
                                            @foreach ($carModels ?? [] as $model)
                                                <option value="{{ $model->id }}"
                                                    {{ old('car_model_id') == $model->id ? 'selected' : '' }}>
                                                    {{ $model->en_name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>

                                {{-- Scooter fields --}}
                                <div id="scooter-fields" style="display:none;">
                                    <div class="form-group">
                                        <label>Scooter Mark <span class="ar">(ماركة الدراجة)</span></label>
                                        <select class="form-control" name="scooter_mark_id">
                                            <option value="">Select Scooter Mark</option>
                                            @foreach ($scooterMarks ?? [] as $mark)
                                                <option value="{{ $mark->id }}"
                                                    {{ old('scooter_mark_id') == $mark->id ? 'selected' : '' }}>
                                                    {{ $mark->en_name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label>Scooter Model <span class="ar">(موديل الدراجة)</span></label>
                                        <select class="form-control" name="scooter_model_id">
                                            <option value="">Select Scooter Model</option>
                                            @foreach ($scooterModels ?? [] as $model)
                                                <option value="{{ $model->id }}"
                                                    {{ old('scooter_model_id') == $model->id ? 'selected' : '' }}>
                                                    {{ $model->en_name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label>Vehicle Image <span class="ar">(صورة المركبة)</span><span style="color:red">*</span></label>
                                    @if(session('temp_upload_vehicle_image'))
                                        <div style="margin-bottom:8px;">
                                            <img src="{{ asset('storage/' . session('temp_upload_vehicle_image')) }}"
                                                style="height:80px; border-radius:6px;">
                                            <input type="hidden" name="temp_vehicle_image" value="{{ session('temp_upload_vehicle_image') }}">
                                            <small style="color:rgba(255,255,255,0.5); display:block;">Previously uploaded — upload again to replace.</small>
                                        </div>
                                    @endif
                                    <input type="file" class="form-control" name="vehicle_image"
                                        accept="image/jpg,image/jpeg,image/png"
                                        {{ session('temp_upload_vehicle_image') ? '' : 'required' }}>
                                    @error('vehicle_image')
                                        <p style="color:red; margin-top:4px;">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div class="form-group">
                                    <label>Plate Image <span class="ar">(صورة اللوحة)</span><span style="color:red">*</span></label>
                                    @if(session('temp_upload_plate_image'))
                                        <div style="margin-bottom:8px;">
                                            <img src="{{ asset('storage/' . session('temp_upload_plate_image')) }}"
                                                style="height:80px; border-radius:6px;">
                                            <input type="hidden" name="temp_plate_image" value="{{ session('temp_upload_plate_image') }}">
                                            <small style="color:rgba(255,255,255,0.5); display:block;">Previously uploaded — upload again to replace.</small>
                                        </div>
                                    @endif
                                    <input type="file" class="form-control" name="plate_image"
                                        accept="image/jpg,image/jpeg,image/png"
                                        {{ session('temp_upload_plate_image') ? '' : 'required' }}>
                                    @error('plate_image')
                                        <p style="color:red; margin-top:4px;">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div class="form-group">
                                    <label>Vehicle License Expiration Date <span class="ar">(تاريخ انتهاء رخصة المركبة)</span><span style="color:red">*</span></label>
                                    <input type="date" class="form-control" name="vehicle_license_expire_date"
                                        style="background-color: rgba(255,255,255,0.2);"
                                        value="{{ old('vehicle_license_expire_date') }}"
                                        min="{{ now()->format('Y-m-d') }}" required>
                                    @error('vehicle_license_expire_date')
                                        <p style="color:red; margin-top:4px;">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div class="form-group">
                                    <label>Vehicle License Front Image <span class="ar">(صورة رخصة المركبة - أمامية)</span><span style="color:red">*</span></label>
                                    @if(session('temp_upload_vehicle_license_front_image'))
                                        <div style="margin-bottom:8px;">
                                            <img src="{{ asset('storage/' . session('temp_upload_vehicle_license_front_image')) }}"
                                                style="height:80px; border-radius:6px;">
                                            <input type="hidden" name="temp_vehicle_license_front_image" value="{{ session('temp_upload_vehicle_license_front_image') }}">
                                            <small style="color:rgba(255,255,255,0.5); display:block;">Previously uploaded — upload again to replace.</small>
                                        </div>
                                    @endif
                                    <input type="file" class="form-control" name="vehicle_license_front_image"
                                        accept="image/jpg,image/jpeg,image/png"
                                        {{ session('temp_upload_vehicle_license_front_image') ? '' : 'required' }}>
                                    @error('vehicle_license_front_image')
                                        <p style="color:red; margin-top:4px;">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div class="form-group">
                                    <label>Vehicle License Back Image <span class="ar">(صورة رخصة المركبة - خلفية)</span><span style="color:red">*</span></label>
                                    @if(session('temp_upload_vehicle_license_back_image'))
                                        <div style="margin-bottom:8px;">
                                            <img src="{{ asset('storage/' . session('temp_upload_vehicle_license_back_image')) }}"
                                                style="height:80px; border-radius:6px;">
                                            <input type="hidden" name="temp_vehicle_license_back_image" value="{{ session('temp_upload_vehicle_license_back_image') }}">
                                            <small style="color:rgba(255,255,255,0.5); display:block;">Previously uploaded — upload again to replace.</small>
                                        </div>
                                    @endif
                                    <input type="file" class="form-control" name="vehicle_license_back_image"
                                        accept="image/jpg,image/jpeg,image/png"
                                        {{ session('temp_upload_vehicle_license_back_image') ? '' : 'required' }}>
                                    @error('vehicle_license_back_image')
                                        <p style="color:red; margin-top:4px;">{{ $message }}</p>
                                    @enderror
                                </div>

                                {{-- ── ACCOUNT STATUS ──────────────────────────────────── --}}
                                <div class="section-divider">
                                    <h4>Account Status <span class="ar">(حالة الحساب)</span></h4><hr>
                                </div>

                                <div class="form-group">
                                    <label>Status <span class="ar">(الحالة)</span><span style="color:red">*</span></label>
                                    <select class="form-control" name="status" required>
                                        <option value="pending"   {{ old('status','pending')=='pending'   ? 'selected':'' }}>Pending (قيد الانتظار)</option>
                                        <option value="confirmed" {{ old('status')=='confirmed' ? 'selected':'' }}>Confirmed (مفعّل)</option>
                                        <option value="banned"    {{ old('status')=='banned'    ? 'selected':'' }}>Banned (محظور)</option>
                                        <option value="blocked"   {{ old('status')=='blocked'   ? 'selected':'' }}>Blocked (موقوف)</option>
                                    </select>
                                </div>

                                {{-- ── ACTIONS ─────────────────────────────────────────── --}}
                                <div class="form-group" style="display:flex; gap:12px; margin-top:24px;">
                                    <button type="submit" class="btn btn-light px-5">
                                        <i class="icon-user-follow"></i> Create Driver
                                    </button>
                                    <a href="{{ route('drivers', request()->query()) }}"
                                        class="btn btn-secondary px-5">
                                        Cancel
                                    </a>
                                </div>

                            </form>
                        </div>
                    </div>
                </div>
            </div>
            <div class="overlay toggle-menu"></div>
        </div>
    </div>

@endsection
@push('scripts')
<script>
    function switchIdType(type) {
        document.getElementById('id_type_hidden').value = type;
        document.getElementById('national-id-section').style.display = type === 'national' ? 'block' : 'none';
        document.getElementById('passport-section').style.display    = type === 'passport' ? 'block' : 'none';
        document.querySelectorAll('.id-type-btn').forEach(btn => btn.classList.remove('active'));
        event.currentTarget.classList.add('active');
    }

    function switchVehicle(type) {
        document.getElementById('vehicle_type_hidden').value = type;
        document.getElementById('car-fields').style.display     = type === 'car'     ? 'block' : 'none';
        document.getElementById('scooter-fields').style.display = type === 'scooter' ? 'block' : 'none';
        document.querySelectorAll('.vehicle-type-btn').forEach(btn => btn.classList.remove('active'));
        event.currentTarget.classList.add('active');
    }

    document.addEventListener('DOMContentLoaded', function () {
        const idType      = document.getElementById('id_type_hidden').value;
        const vehicleType = document.getElementById('vehicle_type_hidden').value;

        document.getElementById('national-id-section').style.display = idType === 'national' ? 'block' : 'none';
        document.getElementById('passport-section').style.display    = idType === 'passport' ? 'block' : 'none';
        document.querySelectorAll('.id-type-btn').forEach(btn => {
            const btnType = btn.getAttribute('onclick').includes('national') ? 'national' : 'passport';
            btn.classList.toggle('active', btnType === idType);
        });

        document.getElementById('car-fields').style.display     = vehicleType === 'car'     ? 'block' : 'none';
        document.getElementById('scooter-fields').style.display = vehicleType === 'scooter' ? 'block' : 'none';
        document.querySelectorAll('.vehicle-type-btn').forEach(btn => {
            const btnType = btn.getAttribute('onclick').includes('scooter') ? 'scooter' : 'car';
            btn.classList.toggle('active', btnType === vehicleType);
        });
    });
</script>
@endpush