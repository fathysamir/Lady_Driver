@extends('dashboard.layout.app')
@section('title', 'Dashboard - edit driver')
@section('content')
    <style>
        .user-status {
            display: inline-block;
            width: 30px;
            height: 30px;
            border-radius: 50%;
            margin-left: -4%;
            margin-bottom: 4.65%;
        }

        .circle-wrapper {
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .circle-label {
            width: 40px;
            height: 40px;
            border: 2px solid rgb(255, 230, 0);
            border-radius: 50%;
            color: rgb(255, 255, 255);
            display: flex;
            justify-content: center;
            align-items: center;
            font-weight: bold;
            font-size: 18px;
        }

        .online { background-color: green; }
        .filled { color: gold; }
        .offline { background-color: gray; }

        .zoomable-image {
            cursor: pointer;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .zoomable-image:hover {
            transform: scale(1.05);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.3);
        }

        /* ── Image Preview Popup ── */
        .image-preview {
            display: none;
            position: fixed;
            top: 0; left: 0;
            width: 100%; height: 100%;
            background-color: rgba(0, 0, 0, 0.95);
            z-index: 9999;
            justify-content: center;
            align-items: center;
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        .image-preview.show { display: flex; opacity: 1; }

        .preview-container {
            position: relative;
            max-width: 90vw; max-height: 90vh;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .image-preview img {
            max-width: 90vw; max-height: 90vh;
            border-radius: 8px;
            cursor: grab;
            transition: transform 0.1s ease;
            transform-origin: center center;
            user-select: none;
        }
        .image-preview img:active { cursor: grabbing; }

        .preview-controls {
            position: fixed;
            top: 20px; right: 20px;
            display: flex; gap: 10px;
            z-index: 10001;
        }

        .control-btn {
            background: rgba(255,255,255,0.9);
            border: none;
            width: 45px; height: 45px;
            border-radius: 50%;
            cursor: pointer;
            font-size: 20px;
            display: flex; align-items: center; justify-content: center;
            transition: all 0.2s ease;
            color: #333; font-weight: bold;
        }
        .control-btn:hover { background: white; transform: scale(1.1); }
        .close-btn { background: rgba(255,59,48,0.9) !important; color: white !important; }
        .close-btn:hover { background: rgb(255,59,48) !important; }

        .zoom-indicator {
            position: fixed;
            bottom: 30px; left: 50%;
            transform: translateX(-50%);
            background: rgba(255,255,255,0.9);
            padding: 10px 20px;
            border-radius: 25px;
            font-size: 16px; color: #333; font-weight: bold;
            pointer-events: none;
            box-shadow: 0 4px 12px rgba(0,0,0,0.3);
            z-index: 10001;
        }

        /* ── Photo Edit Wrapper ── */
        .photo-edit-wrapper {
            position: relative;
            display: inline-block;
        }

        .photo-edit-wrapper .edit-photo-btn {
            position: absolute;
            bottom: 6px; right: 6px;
            background: rgba(0,0,0,0.65);
            color: #fff;
            border: none;
            border-radius: 50%;
            width: 32px; height: 32px;
            font-size: 15px;
            cursor: pointer;
            display: flex; align-items: center; justify-content: center;
            transition: background 0.2s;
            z-index: 2;
        }
        .photo-edit-wrapper .edit-photo-btn:hover { background: rgba(255,200,0,0.85); }

        /* Avatar edit btn slightly bigger */
        .avatar-edit-wrapper .edit-photo-btn {
            width: 38px; height: 38px;
            font-size: 17px;
            bottom: 10px; right: 10px;
        }

        /* New-image preview badge */
        .new-img-badge {
            display: none;
            position: absolute;
            top: 6px; left: 6px;
            background: #28a745;
            color: #fff;
            font-size: 11px;
            padding: 2px 7px;
            border-radius: 10px;
            font-weight: bold;
            z-index: 3;
        }
        .photo-edit-wrapper.has-new .new-img-badge { display: block; }
        .photo-edit-wrapper.has-new img { box-shadow: 0 0 0 3px #28a745; border-radius: 8px; }
        .avatar-edit-wrapper.has-new img { box-shadow: 0 0 0 4px #28a745; }
    </style>

    <div class="content-wrapper">
        <div class="container-fluid">
            <div class="row mt-3">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-body">
                            @php
                                $arr = [
                                    'car'          => 'Standard',
                                    'comfort_car'  => 'Comfort',
                                    'scooter'      => 'Scooter',
                                ];
                                $avatarFallback = asset('dashboard/user_avatar.png');
                                $imageFallback  = asset('dashboard/image_placeholder.png');
                            @endphp

                            {{-- Trip quick-links --}}
                            <div style="display:flex;gap:10px;margin-bottom:16px;justify-content:center;">
                                <a href="{{ url('/admin-dashboard/trips?type=' . ($user->driver_type === 'scooter' ? 'scooter' : ($user->driver_type === 'comfort_car' ? 'comfort_car' : 'car')) . '&time_filter=current&driver=' . $user->id) }}"
                                   class="btn btn-light px-4">Current Trip</a>
                                <a href="{{ url('/admin-dashboard/trips?type=' . ($user->driver_type === 'scooter' ? 'scooter' : ($user->driver_type === 'comfort_car' ? 'comfort_car' : 'car')) . '&time_filter=past&driver=' . $user->id) }}"
                                   class="btn btn-light px-4">Trips History</a>
                            </div>
                            <hr>

                            {{-- ══════════════════════════════════════════
                                 MAIN EDIT FORM  (text fields + status)
                                 ══════════════════════════════════════════ --}}
                            <form method="post"
                                  action="{{ route('update.driver', ['id' => $user->id] + $queryString) }}"
                                  enctype="multipart/form-data">
                                @csrf
                                @method('PUT')
                                <input type="hidden" name="page" value="{{ request()->input('page', 1) }}">

                                {{-- Avatar --}}
                                <div class="form-group" style="text-align:center;">
                                    <div class="photo-edit-wrapper avatar-edit-wrapper" id="wrapper-avatar" style="display:inline-block;">
                                        <img id="preview-avatar"
                                             style="border-radius:50%;width:200px;height:200px;"
                                             src="{{ ($user->image ?? $avatarFallback) . '?v=' . $cacheBust }}"
                                             onerror="this.onerror=null;this.src='{{ $avatarFallback }}';"
                                             class="img-circle zoomable-image" alt="user avatar">
                                        <span class="user-status {{ $user->is_online ? 'online' : 'offline' }}"></span>
                                        <span class="new-img-badge">NEW</span>
                                        <button type="button" class="edit-photo-btn" title="Change photo"
                                                onclick="document.getElementById('input-avatar').click()">✎</button>
                                        <input type="file" id="input-avatar" name="avatar" accept="image/*"
                                               style="display:none" data-wrapper="wrapper-avatar" data-preview="preview-avatar">
                                    </div>

                                    <div class="star-rating" style="margin-bottom:-5px;">
                                        @php
                                            $driverEvaluation = $user->rate;
                                            for ($i = 1; $i <= 5; $i++) {
                                                $starClass2 = $i <= $driverEvaluation ? 'filled' : 'empty';
                                                echo '<span style="font-size:30px;" class="star '.$starClass2.'">&#9733;</span>';
                                            }
                                        @endphp
                                    </div>
                                    <h3>{{ $user->name }}</h3>
                                    <div class="circle-wrapper">
                                        <div class="circle-label">{{ $user->level }}</div>
                                    </div>
                                </div>

                                {{-- Email --}}
                                <div class="form-group">
                                    <label>Email</label>
                                    <input type="email" class="form-control" name="email"
                                           placeholder="Enter Email" value="{{ old('email', $user->email) }}">
                                    @if($errors->has('email'))
                                        <p class="text-error" style="color:red;">{{ $errors->first('email') }}</p>
                                    @endif
                                </div>

                                {{-- Phone --}}
                                @php $country_code = $user->country_code; @endphp
                                <div class="form-group">
                                    <label>Phone Number</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <select name="country_code" class="form-control">
                                                <option value="+1"   {{ $country_code=='+1'   ?'selected':'' }}>USA (+1)</option>
                                                <option value="+44"  {{ $country_code=='+44'  ?'selected':'' }}>UK (+44)</option>
                                                <option value="+971" {{ $country_code=='+971' ?'selected':'' }}>UAE (+971)</option>
                                                <option value="+91"  {{ $country_code=='+91'  ?'selected':'' }}>India (+91)</option>
                                                <option value="+61"  {{ $country_code=='+61'  ?'selected':'' }}>Australia (+61)</option>
                                                <option value="+93"  {{ $country_code=='+93'  ?'selected':'' }}>Afghanistan (+93)</option>
                                                <option value="+355" {{ $country_code=='+355' ?'selected':'' }}>Albania (+355)</option>
                                                <option value="+213" {{ $country_code=='+213' ?'selected':'' }}>Algeria (+213)</option>
                                                <option value="+376" {{ $country_code=='+376' ?'selected':'' }}>Andorra (+376)</option>
                                                <option value="+244" {{ $country_code=='+244' ?'selected':'' }}>Angola (+244)</option>
                                                <option value="+54"  {{ $country_code=='+54'  ?'selected':'' }}>Argentina (+54)</option>
                                                <option value="+374" {{ $country_code=='+374' ?'selected':'' }}>Armenia (+374)</option>
                                                <option value="+43"  {{ $country_code=='+43'  ?'selected':'' }}>Austria (+43)</option>
                                                <option value="+994" {{ $country_code=='+994' ?'selected':'' }}>Azerbaijan (+994)</option>
                                                <option value="+973" {{ $country_code=='+973' ?'selected':'' }}>Bahrain (+973)</option>
                                                <option value="+880" {{ $country_code=='+880' ?'selected':'' }}>Bangladesh (+880)</option>
                                                <option value="+375" {{ $country_code=='+375' ?'selected':'' }}>Belarus (+375)</option>
                                                <option value="+32"  {{ $country_code=='+32'  ?'selected':'' }}>Belgium (+32)</option>
                                                <option value="+501" {{ $country_code=='+501' ?'selected':'' }}>Belize (+501)</option>
                                                <option value="+229" {{ $country_code=='+229' ?'selected':'' }}>Benin (+229)</option>
                                                <option value="+975" {{ $country_code=='+975' ?'selected':'' }}>Bhutan (+975)</option>
                                                <option value="+591" {{ $country_code=='+591' ?'selected':'' }}>Bolivia (+591)</option>
                                                <option value="+387" {{ $country_code=='+387' ?'selected':'' }}>Bosnia and Herzegovina (+387)</option>
                                                <option value="+267" {{ $country_code=='+267' ?'selected':'' }}>Botswana (+267)</option>
                                                <option value="+55"  {{ $country_code=='+55'  ?'selected':'' }}>Brazil (+55)</option>
                                                <option value="+673" {{ $country_code=='+673' ?'selected':'' }}>Brunei (+673)</option>
                                                <option value="+359" {{ $country_code=='+359' ?'selected':'' }}>Bulgaria (+359)</option>
                                                <option value="+226" {{ $country_code=='+226' ?'selected':'' }}>Burkina Faso (+226)</option>
                                                <option value="+257" {{ $country_code=='+257' ?'selected':'' }}>Burundi (+257)</option>
                                                <option value="+855" {{ $country_code=='+855' ?'selected':'' }}>Cambodia (+855)</option>
                                                <option value="+237" {{ $country_code=='+237' ?'selected':'' }}>Cameroon (+237)</option>
                                                <option value="+1"   {{ $country_code=='+1'   ?'selected':'' }}>Canada (+1)</option>
                                                <option value="+238" {{ $country_code=='+238' ?'selected':'' }}>Cape Verde (+238)</option>
                                                <option value="+236" {{ $country_code=='+236' ?'selected':'' }}>Central African Republic (+236)</option>
                                                <option value="+235" {{ $country_code=='+235' ?'selected':'' }}>Chad (+235)</option>
                                                <option value="+56"  {{ $country_code=='+56'  ?'selected':'' }}>Chile (+56)</option>
                                                <option value="+86"  {{ $country_code=='+86'  ?'selected':'' }}>China (+86)</option>
                                                <option value="+57"  {{ $country_code=='+57'  ?'selected':'' }}>Colombia (+57)</option>
                                                <option value="+269" {{ $country_code=='+269' ?'selected':'' }}>Comoros (+269)</option>
                                                <option value="+243" {{ $country_code=='+243' ?'selected':'' }}>Congo (+243)</option>
                                                <option value="+682" {{ $country_code=='+682' ?'selected':'' }}>Cook Islands (+682)</option>
                                                <option value="+506" {{ $country_code=='+506' ?'selected':'' }}>Costa Rica (+506)</option>
                                                <option value="+385" {{ $country_code=='+385' ?'selected':'' }}>Croatia (+385)</option>
                                                <option value="+53"  {{ $country_code=='+53'  ?'selected':'' }}>Cuba (+53)</option>
                                                <option value="+357" {{ $country_code=='+357' ?'selected':'' }}>Cyprus (+357)</option>
                                                <option value="+420" {{ $country_code=='+420' ?'selected':'' }}>Czech Republic (+420)</option>
                                                <option value="+45"  {{ $country_code=='+45'  ?'selected':'' }}>Denmark (+45)</option>
                                                <option value="+253" {{ $country_code=='+253' ?'selected':'' }}>Djibouti (+253)</option>
                                                <option value="+593" {{ $country_code=='+593' ?'selected':'' }}>Ecuador (+593)</option>
                                                <option value="+20"  {{ $country_code=='+20'||$country_code==null?'selected':'' }}>Egypt (+20)</option>
                                                <option value="+503" {{ $country_code=='+503' ?'selected':'' }}>El Salvador (+503)</option>
                                                <option value="+240" {{ $country_code=='+240' ?'selected':'' }}>Equatorial Guinea (+240)</option>
                                                <option value="+291" {{ $country_code=='+291' ?'selected':'' }}>Eritrea (+291)</option>
                                                <option value="+372" {{ $country_code=='+372' ?'selected':'' }}>Estonia (+372)</option>
                                                <option value="+251" {{ $country_code=='+251' ?'selected':'' }}>Ethiopia (+251)</option>
                                                <option value="+679" {{ $country_code=='+679' ?'selected':'' }}>Fiji (+679)</option>
                                                <option value="+358" {{ $country_code=='+358' ?'selected':'' }}>Finland (+358)</option>
                                                <option value="+33"  {{ $country_code=='+33'  ?'selected':'' }}>France (+33)</option>
                                                <option value="+241" {{ $country_code=='+241' ?'selected':'' }}>Gabon (+241)</option>
                                                <option value="+220" {{ $country_code=='+220' ?'selected':'' }}>Gambia (+220)</option>
                                                <option value="+995" {{ $country_code=='+995' ?'selected':'' }}>Georgia (+995)</option>
                                                <option value="+49"  {{ $country_code=='+49'  ?'selected':'' }}>Germany (+49)</option>
                                                <option value="+233" {{ $country_code=='+233' ?'selected':'' }}>Ghana (+233)</option>
                                                <option value="+30"  {{ $country_code=='+30'  ?'selected':'' }}>Greece (+30)</option>
                                                <option value="+502" {{ $country_code=='+502' ?'selected':'' }}>Guatemala (+502)</option>
                                                <option value="+224" {{ $country_code=='+224' ?'selected':'' }}>Guinea (+224)</option>
                                                <option value="+592" {{ $country_code=='+592' ?'selected':'' }}>Guyana (+592)</option>
                                                <option value="+509" {{ $country_code=='+509' ?'selected':'' }}>Haiti (+509)</option>
                                                <option value="+504" {{ $country_code=='+504' ?'selected':'' }}>Honduras (+504)</option>
                                                <option value="+36"  {{ $country_code=='+36'  ?'selected':'' }}>Hungary (+36)</option>
                                                <option value="+354" {{ $country_code=='+354' ?'selected':'' }}>Iceland (+354)</option>
                                                <option value="+62"  {{ $country_code=='+62'  ?'selected':'' }}>Indonesia (+62)</option>
                                                <option value="+98"  {{ $country_code=='+98'  ?'selected':'' }}>Iran (+98)</option>
                                                <option value="+964" {{ $country_code=='+964' ?'selected':'' }}>Iraq (+964)</option>
                                                <option value="+353" {{ $country_code=='+353' ?'selected':'' }}>Ireland (+353)</option>
                                                <option value="+39"  {{ $country_code=='+39'  ?'selected':'' }}>Italy (+39)</option>
                                                <option value="+81"  {{ $country_code=='+81'  ?'selected':'' }}>Japan (+81)</option>
                                                <option value="+962" {{ $country_code=='+962' ?'selected':'' }}>Jordan (+962)</option>
                                                <option value="+7"   {{ $country_code=='+7'   ?'selected':'' }}>Kazakhstan (+7)</option>
                                                <option value="+254" {{ $country_code=='+254' ?'selected':'' }}>Kenya (+254)</option>
                                                <option value="+965" {{ $country_code=='+965' ?'selected':'' }}>Kuwait (+965)</option>
                                                <option value="+996" {{ $country_code=='+996' ?'selected':'' }}>Kyrgyzstan (+996)</option>
                                                <option value="+856" {{ $country_code=='+856' ?'selected':'' }}>Laos (+856)</option>
                                                <option value="+371" {{ $country_code=='+371' ?'selected':'' }}>Latvia (+371)</option>
                                                <option value="+961" {{ $country_code=='+961' ?'selected':'' }}>Lebanon (+961)</option>
                                                <option value="+266" {{ $country_code=='+266' ?'selected':'' }}>Lesotho (+266)</option>
                                                <option value="+231" {{ $country_code=='+231' ?'selected':'' }}>Liberia (+231)</option>
                                                <option value="+370" {{ $country_code=='+370' ?'selected':'' }}>Lithuania (+370)</option>
                                                <option value="+352" {{ $country_code=='+352' ?'selected':'' }}>Luxembourg (+352)</option>
                                                <option value="+389" {{ $country_code=='+389' ?'selected':'' }}>Macedonia (+389)</option>
                                                <option value="+261" {{ $country_code=='+261' ?'selected':'' }}>Madagascar (+261)</option>
                                                <option value="+265" {{ $country_code=='+265' ?'selected':'' }}>Malawi (+265)</option>
                                                <option value="+60"  {{ $country_code=='+60'  ?'selected':'' }}>Malaysia (+60)</option>
                                                <option value="+960" {{ $country_code=='+960' ?'selected':'' }}>Maldives (+960)</option>
                                                <option value="+223" {{ $country_code=='+223' ?'selected':'' }}>Mali (+223)</option>
                                                <option value="+356" {{ $country_code=='+356' ?'selected':'' }}>Malta (+356)</option>
                                                <option value="+692" {{ $country_code=='+692' ?'selected':'' }}>Marshall Islands (+692)</option>
                                                <option value="+222" {{ $country_code=='+222' ?'selected':'' }}>Mauritania (+222)</option>
                                                <option value="+230" {{ $country_code=='+230' ?'selected':'' }}>Mauritius (+230)</option>
                                                <option value="+52"  {{ $country_code=='+52'  ?'selected':'' }}>Mexico (+52)</option>
                                                <option value="+373" {{ $country_code=='+373' ?'selected':'' }}>Moldova (+373)</option>
                                                <option value="+377" {{ $country_code=='+377' ?'selected':'' }}>Monaco (+377)</option>
                                                <option value="+976" {{ $country_code=='+976' ?'selected':'' }}>Mongolia (+976)</option>
                                                <option value="+382" {{ $country_code=='+382' ?'selected':'' }}>Montenegro (+382)</option>
                                                <option value="+212" {{ $country_code=='+212' ?'selected':'' }}>Morocco (+212)</option>
                                                <option value="+258" {{ $country_code=='+258' ?'selected':'' }}>Mozambique (+258)</option>
                                                <option value="+95"  {{ $country_code=='+95'  ?'selected':'' }}>Myanmar (+95)</option>
                                                <option value="+264" {{ $country_code=='+264' ?'selected':'' }}>Namibia (+264)</option>
                                                <option value="+977" {{ $country_code=='+977' ?'selected':'' }}>Nepal (+977)</option>
                                                <option value="+31"  {{ $country_code=='+31'  ?'selected':'' }}>Netherlands (+31)</option>
                                                <option value="+64"  {{ $country_code=='+64'  ?'selected':'' }}>New Zealand (+64)</option>
                                                <option value="+505" {{ $country_code=='+505' ?'selected':'' }}>Nicaragua (+505)</option>
                                                <option value="+227" {{ $country_code=='+227' ?'selected':'' }}>Niger (+227)</option>
                                                <option value="+234" {{ $country_code=='+234' ?'selected':'' }}>Nigeria (+234)</option>
                                                <option value="+47"  {{ $country_code=='+47'  ?'selected':'' }}>Norway (+47)</option>
                                                <option value="+968" {{ $country_code=='+968' ?'selected':'' }}>Oman (+968)</option>
                                                <option value="+92"  {{ $country_code=='+92'  ?'selected':'' }}>Pakistan (+92)</option>
                                                <option value="+507" {{ $country_code=='+507' ?'selected':'' }}>Panama (+507)</option>
                                                <option value="+675" {{ $country_code=='+675' ?'selected':'' }}>Papua New Guinea (+675)</option>
                                                <option value="+595" {{ $country_code=='+595' ?'selected':'' }}>Paraguay (+595)</option>
                                                <option value="+51"  {{ $country_code=='+51'  ?'selected':'' }}>Peru (+51)</option>
                                                <option value="+63"  {{ $country_code=='+63'  ?'selected':'' }}>Philippines (+63)</option>
                                                <option value="+48"  {{ $country_code=='+48'  ?'selected':'' }}>Poland (+48)</option>
                                                <option value="+351" {{ $country_code=='+351' ?'selected':'' }}>Portugal (+351)</option>
                                                <option value="+974" {{ $country_code=='+974' ?'selected':'' }}>Qatar (+974)</option>
                                                <option value="+40"  {{ $country_code=='+40'  ?'selected':'' }}>Romania (+40)</option>
                                                <option value="+7"   {{ $country_code=='+7'   ?'selected':'' }}>Russia (+7)</option>
                                                <option value="+250" {{ $country_code=='+250' ?'selected':'' }}>Rwanda (+250)</option>
                                                <option value="+966" {{ $country_code=='+966' ?'selected':'' }}>Saudi Arabia (+966)</option>
                                                <option value="+221" {{ $country_code=='+221' ?'selected':'' }}>Senegal (+221)</option>
                                                <option value="+381" {{ $country_code=='+381' ?'selected':'' }}>Serbia (+381)</option>
                                                <option value="+248" {{ $country_code=='+248' ?'selected':'' }}>Seychelles (+248)</option>
                                                <option value="+232" {{ $country_code=='+232' ?'selected':'' }}>Sierra Leone (+232)</option>
                                                <option value="+65"  {{ $country_code=='+65'  ?'selected':'' }}>Singapore (+65)</option>
                                                <option value="+421" {{ $country_code=='+421' ?'selected':'' }}>Slovakia (+421)</option>
                                                <option value="+386" {{ $country_code=='+386' ?'selected':'' }}>Slovenia (+386)</option>
                                                <option value="+27"  {{ $country_code=='+27'  ?'selected':'' }}>South Africa (+27)</option>
                                                <option value="+82"  {{ $country_code=='+82'  ?'selected':'' }}>South Korea (+82)</option>
                                                <option value="+34"  {{ $country_code=='+34'  ?'selected':'' }}>Spain (+34)</option>
                                                <option value="+94"  {{ $country_code=='+94'  ?'selected':'' }}>Sri Lanka (+94)</option>
                                                <option value="+249" {{ $country_code=='+249' ?'selected':'' }}>Sudan (+249)</option>
                                                <option value="+597" {{ $country_code=='+597' ?'selected':'' }}>Suriname (+597)</option>
                                                <option value="+268" {{ $country_code=='+268' ?'selected':'' }}>Swaziland (+268)</option>
                                                <option value="+46"  {{ $country_code=='+46'  ?'selected':'' }}>Sweden (+46)</option>
                                                <option value="+41"  {{ $country_code=='+41'  ?'selected':'' }}>Switzerland (+41)</option>
                                                <option value="+963" {{ $country_code=='+963' ?'selected':'' }}>Syria (+963)</option>
                                                <option value="+886" {{ $country_code=='+886' ?'selected':'' }}>Taiwan (+886)</option>
                                                <option value="+992" {{ $country_code=='+992' ?'selected':'' }}>Tajikistan (+992)</option>
                                                <option value="+255" {{ $country_code=='+255' ?'selected':'' }}>Tanzania (+255)</option>
                                                <option value="+66"  {{ $country_code=='+66'  ?'selected':'' }}>Thailand (+66)</option>
                                                <option value="+228" {{ $country_code=='+228' ?'selected':'' }}>Togo (+228)</option>
                                                <option value="+676" {{ $country_code=='+676' ?'selected':'' }}>Tonga (+676)</option>
                                                <option value="+216" {{ $country_code=='+216' ?'selected':'' }}>Tunisia (+216)</option>
                                                <option value="+90"  {{ $country_code=='+90'  ?'selected':'' }}>Turkey (+90)</option>
                                                <option value="+993" {{ $country_code=='+993' ?'selected':'' }}>Turkmenistan (+993)</option>
                                                <option value="+256" {{ $country_code=='+256' ?'selected':'' }}>Uganda (+256)</option>
                                                <option value="+380" {{ $country_code=='+380' ?'selected':'' }}>Ukraine (+380)</option>
                                                <option value="+971" {{ $country_code=='+971' ?'selected':'' }}>United Arab Emirates (+971)</option>
                                                <option value="+44"  {{ $country_code=='+44'  ?'selected':'' }}>United Kingdom (+44)</option>
                                                <option value="+1"   {{ $country_code=='+1'   ?'selected':'' }}>United States (+1)</option>
                                                <option value="+598" {{ $country_code=='+598' ?'selected':'' }}>Uruguay (+598)</option>
                                                <option value="+998" {{ $country_code=='+998' ?'selected':'' }}>Uzbekistan (+998)</option>
                                                <option value="+58"  {{ $country_code=='+58'  ?'selected':'' }}>Venezuela (+58)</option>
                                                <option value="+84"  {{ $country_code=='+84'  ?'selected':'' }}>Vietnam (+84)</option>
                                                <option value="+967" {{ $country_code=='+967' ?'selected':'' }}>Yemen (+967)</option>
                                                <option value="+260" {{ $country_code=='+260' ?'selected':'' }}>Zambia (+260)</option>
                                                <option value="+263" {{ $country_code=='+263' ?'selected':'' }}>Zimbabwe (+263)</option>
                                            </select>
                                        </div>
                                        <input type="number" name="phone" class="form-control"
                                               placeholder="Enter Phone Number" value="{{ $user->phone }}">
                                    </div>
                                    @if($errors->has('phone'))
                                        <p class="text-error" style="color:red;">{{ $errors->first('phone') }}</p>
                                    @endif
                                </div>

                                {{-- City --}}
                                <div class="form-group">
                                    <label>City</label>
                                    <select class="form-control" required name="city">
                                        <option value="">Select City</option>
                                        @foreach($cities as $city)
                                            <option value="{{ $city->id }}" {{ $user->city_id==$city->id?'selected':'' }}>
                                                {{ $city->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                {{-- Birth Date --}}
                                <div class="form-group">
                                    <label>Birth Date</label>
                                    <input type="date" class="form-control date" name="birth_date"
                                           style="background-color:rgba(255,255,255,0.2);"
                                           value="{{ old('birth_date', $user->birth_date) }}">
                                </div>

                                {{-- ── Identity Document ── --}}
                                @if($user->national_id || $user->IDfrontImage)
                                    <div class="form-group">
                                        <label>National ID</label>
                                        <input type="number" class="form-control" name="national_id"
                                               placeholder="National ID" value="{{ old('national_id', $user->national_id) }}">
                                    </div>

                                    @if($user->national_id_expire_date)
                                        <div class="form-group" style="display:flex;">
                                            <label>National ID Expiration Date :
                                                <span style="color:{{ \Carbon\Carbon::parse($user->national_id_expire_date)->isFuture() ? '#56ec60' : '#ff5f5f' }}">
                                                    {{ \Carbon\Carbon::parse($user->national_id_expire_date)->format('d M Y') }}
                                                </span>
                                            </label>
                                        </div>
                                    @endif

                                    <div class="form-group" style="display:flex; align-items:flex-start; flex-wrap:wrap; gap:10px;">
                                        <label style="margin-top:6px;">Id Images :</label>
                                        @if($user->IDfrontImage)
                                            {{-- Front --}}
                                            <div class="photo-edit-wrapper" id="wrapper-id-front">
                                                <img id="preview-id-front" width="400" height="250"
                                                     style="border-radius:10px;"
                                                     src="{{ $user->IDfrontImage . '?v=' . $cacheBust }}"
                                                     onerror="this.onerror=null;this.src='{{ $imageFallback }}';"
                                                     class="zoomable-image">
                                                <span class="new-img-badge">NEW</span>
                                                <button type="button" class="edit-photo-btn" title="Replace image"
                                                        onclick="document.getElementById('input-id-front').click()">✎</button>
                                                <input type="file" id="input-id-front" name="id_front_image" accept="image/*"
                                                       style="display:none" data-wrapper="wrapper-id-front" data-preview="preview-id-front">
                                            </div>
                                            {{-- Back --}}
                                            <div class="photo-edit-wrapper" id="wrapper-id-back">
                                                <img id="preview-id-back" width="400" height="250"
                                                     style="border-radius:10px;"
                                                     src="{{ $user->IDbackImage . '?v=' . $cacheBust }}"

                                                     onerror="this.onerror=null;this.src='{{ $imageFallback }}';"
                                                     class="zoomable-image">
                                                <span class="new-img-badge">NEW</span>
                                                <button type="button" class="edit-photo-btn" title="Replace image"
                                                        onclick="document.getElementById('input-id-back').click()">✎</button>
                                                <input type="file" id="input-id-back" name="id_back_image" accept="image/*"
                                                       style="display:none" data-wrapper="wrapper-id-back" data-preview="preview-id-back">
                                            </div>
                                        @else
                                            <label style="color:#ff7272">There is no National ID image.</label>
                                        @endif
                                    </div>

                                @elseif($user->passport_id || $user->PassportImage)
                                    <div class="form-group">
                                        <label>Passport ID</label>
                                        <input type="text" class="form-control" name="passport_id"
                                               placeholder="Passport ID" value="{{ old('passport_id', $user->passport_id) }}">
                                    </div>

                                    @if($user->passport_expire_date)
                                        <div class="form-group" style="display:flex;">
                                            <label>Passport Expiration Date :
                                                <span style="color:{{ \Carbon\Carbon::parse($user->passport_expire_date)->isFuture() ? '#56ec60' : '#ff5f5f' }}">
                                                    {{ \Carbon\Carbon::parse($user->passport_expire_date)->format('d M Y') }}
                                                </span>
                                            </label>
                                        </div>
                                    @endif

                                    <div class="form-group" style="display:flex; align-items:flex-start; flex-wrap:wrap; gap:10px;">
                                        <label style="margin-top:6px;">Passport Image :</label>
                                        @if($user->PassportImage)
                                            <div class="photo-edit-wrapper" id="wrapper-passport">
                                                <img id="preview-passport" width="400" height="250"
                                                     style="border-radius:10px;"
                                                     src="{{ $user->PassportImage . '?v=' . $cacheBust }}"

                                                     onerror="this.onerror=null;this.src='{{ $imageFallback }}';"
                                                     class="zoomable-image">
                                                <span class="new-img-badge">NEW</span>
                                                <button type="button" class="edit-photo-btn" title="Replace image"
                                                        onclick="document.getElementById('input-passport').click()">✎</button>
                                                <input type="file" id="input-passport" name="passport_image" accept="image/*"
                                                       style="display:none" data-wrapper="wrapper-passport" data-preview="preview-passport">
                                            </div>
                                        @else
                                            <label style="color:#ff7272">There is no passport image.</label>
                                        @endif
                                    </div>

                                @else
                                    <div class="form-group">
                                        <label style="color:#ff7272">No identity document has been provided.</label>
                                    </div>
                                @endif

                                {{-- Medical Examination --}}
                                <div class="form-group" style="display:flex; align-items:flex-start; flex-wrap:wrap; gap:10px;">
                                    <label style="margin-top:6px;">Medical Examination Image :</label>
                                    @if($user->medicalExaminationImage)
                                        <div class="photo-edit-wrapper" id="wrapper-medical">
                                            <img id="preview-medical" width="400" height="250"
                                                 style="border-radius:10px;"
                                                 src="{{ $user->medicalExaminationImage . '?v=' . $cacheBust }}"

                                                 onerror="this.onerror=null;this.src='{{ $imageFallback }}';"
                                                 class="zoomable-image">
                                            <span class="new-img-badge">NEW</span>
                                            <button type="button" class="edit-photo-btn" title="Replace image"
                                                    onclick="document.getElementById('input-medical').click()">✎</button>
                                            <input type="file" id="input-medical" name="medical_examination_image" accept="image/*"
                                                   style="display:none" data-wrapper="wrapper-medical" data-preview="preview-medical">
                                        </div>
                                    @else
                                        <label style="color:#ff7272">There is no medical examination.</label>
                                    @endif
                                </div>

                                @if($user->medical_examination_date)
                                    <div class="form-group" style="display:flex;">
                                        <label>Medical Examination Date :
                                            <span style="color:#56ec60">
                                                {{ \Carbon\Carbon::parse($user->medical_examination_date)->format('d M Y') }}
                                            </span>
                                        </label>
                                    </div>
                                @endif

                                {{-- Criminal Record --}}
                                <div class="form-group" style="display:flex; align-items:flex-start; flex-wrap:wrap; gap:10px;">
                                    <label style="margin-top:6px;">Criminal Record Image :</label>
                                    @if($user->criminalRecordImage)
                                        <div class="photo-edit-wrapper" id="wrapper-criminal">
                                            <img id="preview-criminal" width="400" height="250"
                                                 style="border-radius:10px;"
                                                 src="{{ $user->criminalRecordImage . '?v=' . $cacheBust }}"

                                                 onerror="this.onerror=null;this.src='{{ $imageFallback }}';"
                                                 class="zoomable-image">
                                            <span class="new-img-badge">NEW</span>
                                            <button type="button" class="edit-photo-btn" title="Replace image"
                                                    onclick="document.getElementById('input-criminal').click()">✎</button>
                                            <input type="file" id="input-criminal" name="criminal_record_image" accept="image/*"
                                                   style="display:none" data-wrapper="wrapper-criminal" data-preview="preview-criminal">
                                        </div>
                                    @else
                                        <label style="color:#ff7272">There is no criminal record.</label>
                                    @endif
                                </div>

                                @if($user->criminal_record_date)
                                    <div class="form-group" style="display:flex;">
                                        <label>Criminal Record Date :
                                            <span style="color:#56ec60">
                                                {{ \Carbon\Carbon::parse($user->criminal_record_date)->format('d M Y') }}
                                            </span>
                                        </label>
                                    </div>
                                @endif

                                {{-- Address / Map --}}
                                <div class="form-group">
                                    <label>Address : {{ $user->address }}</label>
                                </div>

                                <div id="map" style="height:800px;margin:20px 0;"></div>

                                {{-- ── Driving License ── --}}
                                <div class="form-group" style="display:flex;align-items:center;">
                                    <h4 style="margin-right:10px;">Driving License</h4>
                                    <hr style="flex:1;margin:0;">
                                </div>

                                @if($user->driving_license)
                                    <div class="form-group">
                                        <label>License Number : {{ $user->driving_license->license_num }}</label>
                                    </div>
                                    <div class="form-group">
                                        <label>Expire Date :
                                            <span style="color:{{ \Carbon\Carbon::parse($user->driving_license->expire_date)->isFuture() ? '#56ec60' : '#ff5f5f' }}">
                                                {{ \Carbon\Carbon::parse($user->driving_license->expire_date)->format('d M Y') }}
                                            </span>
                                        </label>
                                    </div>
                                    <div class="form-group" style="display:flex; align-items:flex-start; flex-wrap:wrap; gap:10px;">
                                        <label style="margin-top:6px;">Driver License Images :</label>
                                        {{-- License Front --}}
                                        <div class="photo-edit-wrapper" id="wrapper-lic-front">
                                            <img id="preview-lic-front" width="300"
                                                 style="border-radius:10px;"
                                                 src="{{ $user->driving_license->front_image . '?v=' . $cacheBust }}"

                                                 onerror="this.onerror=null;this.src='{{ $imageFallback }}';"
                                                 class="zoomable-image">
                                            <span class="new-img-badge">NEW</span>
                                            <button type="button" class="edit-photo-btn" title="Replace image"
                                                    onclick="document.getElementById('input-lic-front').click()">✎</button>
                                            <input type="file" id="input-lic-front" name="license_front_image" accept="image/*"
                                                   style="display:none" data-wrapper="wrapper-lic-front" data-preview="preview-lic-front">
                                        </div>
                                        {{-- License Back --}}
                                        <div class="photo-edit-wrapper" id="wrapper-lic-back">
                                            <img id="preview-lic-back" width="300"
                                                 style="border-radius:10px;"
                                                 src="{{ $user->driving_license->back_image . '?v=' . $cacheBust }}"
                                                 onerror="this.onerror=null;this.src='{{ $imageFallback }}';"
                                                 class="zoomable-image">
                                            <span class="new-img-badge">NEW</span>
                                            <button type="button" class="edit-photo-btn" title="Replace image"
                                                    onclick="document.getElementById('input-lic-back').click()">✎</button>
                                            <input type="file" id="input-lic-back" name="license_back_image" accept="image/*"
                                                   style="display:none" data-wrapper="wrapper-lic-back" data-preview="preview-lic-back">
                                        </div>
                                    </div>
                                @else
                                    <div class="form-group">
                                        <label>No data was entered for the driver's license</label>
                                    </div>
                                @endif

                                {{-- Vehicle link --}}
                                @if($user->car)
                                    <div class="form-group" style="text-align:center;margin:40px 0;">
                                        <span style="font-size:22px;">Car Details : You can view car details from </span>
                                        <a style="color:yellow;font-size:36px;font-weight:bold;"
                                           href="{{ url('/admin-dashboard/car/edit/'.$user->car->id) }}">Here</a>
                                    </div>
                                @elseif($user->scooter)
                                    <div class="form-group" style="text-align:center;margin:40px 0;">
                                        <span style="font-size:22px;">Scooter Details : You can view scooter details from </span>
                                        <a style="color:yellow;font-size:36px;font-weight:bold;"
                                           href="{{ url('/admin-dashboard/scooter/edit/'.$user->scooter->id) }}">Here</a>
                                    </div>
                                @else
                                    <div class="form-group">
                                        <label>No vehicle data has been entered</label>
                                    </div>
                                @endif

                                {{-- Activities --}}
                                <div class="form-group" style="display:flex;align-items:center;">
                                    <h4 style="margin-right:10px;">Activities</h4>
                                    <hr style="flex:1;margin:0;">
                                </div>

                                <div class="form-group" style="display:flex;align-items:center;">
                                    <label>Rate : </label>
                                    <div class="star-rating" style="margin-bottom:10px;">
                                        @php
                                            for ($i = 1; $i <= 5; $i++) {
                                                $sc = $i <= $user->rate ? 'filled' : 'empty';
                                                echo '<span class="star '.$sc.'">&#9733;</span>';
                                            }
                                        @endphp
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label>Trips Count : {{ $user->trips_count }}</label>
                                </div>
                                <div class="form-group">
                                    <label>Wallet : {{ round($user->wallet, 2) }}</label>
                                </div>

                                {{-- Status --}}
                                <div class="form-group">
                                    <label>Status</label>
                                    <select class="form-control" name="status">
                                        <option value="pending"   @if($user->status=='pending')   selected @endif>Pending</option>
                                        <option value="confirmed" @if($user->status=='confirmed') selected @endif>Confirmed</option>
                                        <option value="banned"    @if($user->status=='banned')    selected @endif>Banned</option>
                                        <option value="blocked"   @if($user->status=='blocked')   selected @endif>Blocked</option>
                                    </select>
                                </div>

                                <div class="form-group">
                                    <button type="submit" class="btn btn-light px-5">
                                        <i class="icon-lock"></i>Save
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            <div class="overlay toggle-menu"></div>
        </div>
    </div>

    {{-- ── Image Preview Popup ── --}}
    <div class="image-preview">
        <div class="preview-container">
            <div class="preview-controls">
                <button type="button" class="control-btn zoom-in-btn"  title="Zoom In">+</button>
                <button type="button" class="control-btn zoom-out-btn" title="Zoom Out">-</button>
                <button type="button" class="control-btn close-btn"    title="Close">✕</button>
            </div>
            <img src="" alt="Image Preview"
                 onerror="this.onerror=null;this.src='{{ asset('dashboard/image_placeholder.png') }}';">
            <div class="zoom-indicator">100%</div>
        </div>
    </div>

@endsection

@push('scripts')
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyCWDitjrboDO2zHDtZHzLlgRLduXi7-3Es"></script>
    <script>
        function initMap() {
            var userLocation = { lat: {{ $user->lat }}, lng: {{ $user->lng }} };
            var map = new google.maps.Map(document.getElementById('map'), { zoom: 12, center: userLocation });
            new google.maps.Marker({ position: userLocation, map: map });
        }
        google.maps.event.addDomListener(window, 'load', initMap);
    </script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>

    <script>
    document.addEventListener('DOMContentLoaded', function () {

        /* ─────────────────────────────────────────
           1.  LIVE PHOTO PREVIEW on file-input change
           ───────────────────────────────────────── */
        document.querySelectorAll('input[type="file"][data-preview]').forEach(function (input) {
            input.addEventListener('change', function () {
                if (!this.files || !this.files[0]) return;

                var wrapperId  = this.dataset.wrapper;
                var previewId  = this.dataset.preview;
                var wrapper    = document.getElementById(wrapperId);
                var previewImg = document.getElementById(previewId);

                var reader = new FileReader();
                reader.onload = function (e) {
                    previewImg.src = e.target.result;
                    wrapper.classList.add('has-new');
                };
                reader.readAsDataURL(this.files[0]);
            });
        });

        /* ─────────────────────────────────────────
           2.  ZOOMABLE IMAGE PREVIEW POPUP
           ───────────────────────────────────────── */
        var imagePreview  = document.querySelector('.image-preview');
        var previewImg    = imagePreview.querySelector('img');
        var zoomInBtn     = imagePreview.querySelector('.zoom-in-btn');
        var zoomOutBtn    = imagePreview.querySelector('.zoom-out-btn');
        var closeBtn      = imagePreview.querySelector('.close-btn');
        var zoomIndicator = imagePreview.querySelector('.zoom-indicator');

        var scale = 1, posX = 0, posY = 0, isDragging = false, startX, startY;

        function updateTransform() {
            previewImg.style.transform = 'translate(' + posX + 'px,' + posY + 'px) scale(' + scale + ')';
            zoomIndicator.textContent  = Math.round(scale * 100) + '%';
        }
        function resetPosition() { posX = 0; posY = 0; }

        function openPreview(src) {
            previewImg.src = src;
            imagePreview.classList.add('show');
            document.body.style.overflow = 'hidden';
        }
        function closePreview() {
            imagePreview.classList.remove('show');
            document.body.style.overflow = '';
            scale = 1; resetPosition(); updateTransform();
        }

        // Attach click to all .zoomable-image elements (including dynamically previewed ones)
        document.addEventListener('click', function (e) {
            if (e.target.classList.contains('zoomable-image')) {
                e.preventDefault();
                openPreview(e.target.src);
            }
        });

        closeBtn.addEventListener('click',   function (e) { e.stopPropagation(); closePreview(); });
        zoomInBtn.addEventListener('click',  function (e) {
            e.stopPropagation();
            scale = Math.min(scale + 0.2, 5);
            updateTransform();
        });
        zoomOutBtn.addEventListener('click', function (e) {
            e.stopPropagation();
            scale -= 0.2;
            if (scale < 1) { scale = 1; resetPosition(); }
            updateTransform();
        });

        previewImg.addEventListener('wheel', function (e) {
            e.preventDefault();
            scale += e.deltaY < 0 ? 0.1 : -0.1;
            if (scale < 1) { scale = 1; resetPosition(); }
            else if (scale > 5) scale = 5;
            updateTransform();
        });

        previewImg.addEventListener('mousedown', function (e) {
            if (scale <= 1) return;
            isDragging = true;
            startX = e.clientX - posX; startY = e.clientY - posY;
            previewImg.style.cursor = 'grabbing';
            e.preventDefault();
        });
        document.addEventListener('mousemove', function (e) {
            if (!isDragging || scale <= 1) return;
            posX = e.clientX - startX; posY = e.clientY - startY;
            updateTransform();
        });
        document.addEventListener('mouseup', function () {
            if (isDragging) { isDragging = false; previewImg.style.cursor = 'grab'; }
        });
        previewImg.addEventListener('dragstart', function (e) { e.preventDefault(); });

        imagePreview.addEventListener('click', function (e) {
            if (e.target === imagePreview) closePreview();
        });
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && imagePreview.classList.contains('show')) closePreview();
        });
    });
    </script>
@endpush