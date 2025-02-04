<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8"/>
    <meta http-equiv="X-UA-Compatible" content="IE=edge"/>
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no"/>
    <meta name="description" content=""/>
    <meta name="author" content=""/>
    <link rel="icon" type="image/x-icon" href="{{asset('dashboard/logo.png')}}">
    <title>@yield('title', 'Lady Driver - Terms and Conditions')</title>
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
    
  </head>

  <body class="bg-theme bg-theme1">
  
  <!-- Start wrapper-->
    <div id="wrapper">
  
      <!--Start sidebar-wrapper-->
    
      <!--End sidebar-wrapper-->

        <!--Start topbar header-->
        <header class="topbar-nav">
            <nav class="navbar navbar-expand" >
              <h4>LADY DRIVER - CONTACT US</h4>
            </nav>
          </header>
        <!--End topbar header-->

      <div class="clearfix"></div>
      
      <div class="content-wrapper" style="margin-right: 250px;">
        <div class="container-fluid">

            <div class="row mt-3">
                <div class="col-lg-12">
                    <div class="card">
                      <div class="card-body">
                      <div class="card-title">Contact Us</div>
                      <hr>
                       <form method="post" action="{{route('save_contact_us')}}" enctype="multipart/form-data">
                        @csrf
                      <div class="form-group">
                        <label>Subject</label>
                        <input type="text" name="subject" class="form-control"  placeholder="Enter Subject"value="{{ old('subject') }}">
                        @if ($errors->has('subject'))
                            <p class="text-error more-info-err" style="color: red;">{{ $errors->first('subject') }}</p>
                        @endif
                      </div>
                      <div class="form-group">
                        <label>Name</label>
                        <input type="text" name="name" class="form-control"  placeholder="Enter Name"value="{{ old('name') }}">
                        @if ($errors->has('name'))
                            <p class="text-error more-info-err" style="color: red;">{{ $errors->first('name') }}</p>
                        @endif
                      </div>
                      <div class="form-group">
                        <label>Email</label>
                        <input type="text" name="email" class="form-control"  placeholder="Enter Email"value="{{ old('email') }}">
                        @if ($errors->has('email'))
                            <p class="text-error more-info-err" style="color: red;">{{ $errors->first('email') }}</p>
                        @endif
                      </div>
                      <div class="form-group">
                        <label>Phone Number</label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <select name="country_code" class="form-control">
                                    <option value="+1">USA (+1)</option>
                                    <option value="+44">UK (+44)</option>
                                    <option value="+971">UAE (+971)</option>
                                    <option value="+91">India (+91)</option>
                                    <option value="+61">Australia (+61)</option>
                                    <option value="+93">Afghanistan (+93)</option>
                                    <option value="+355">Albania (+355)</option>
                                    <option value="+213">Algeria (+213)</option>
                                    <option value="+376">Andorra (+376)</option>
                                    <option value="+244">Angola (+244)</option>
                                    <option value="+54">Argentina (+54)</option>
                                    <option value="+374">Armenia (+374)</option>
                                    <option value="+61">Australia (+61)</option>
                                    <option value="+43">Austria (+43)</option>
                                    <option value="+994">Azerbaijan (+994)</option>
                                    <option value="+973">Bahrain (+973)</option>
                                    <option value="+880">Bangladesh (+880)</option>
                                    <option value="+375">Belarus (+375)</option>
                                    <option value="+32">Belgium (+32)</option>
                                    <option value="+501">Belize (+501)</option>
                                    <option value="+229">Benin (+229)</option>
                                    <option value="+975">Bhutan (+975)</option>
                                    <option value="+591">Bolivia (+591)</option>
                                    <option value="+387">Bosnia and Herzegovina (+387)</option>
                                    <option value="+267">Botswana (+267)</option>
                                    <option value="+55">Brazil (+55)</option>
                                    <option value="+673">Brunei (+673)</option>
                                    <option value="+359">Bulgaria (+359)</option>
                                    <option value="+226">Burkina Faso (+226)</option>
                                    <option value="+257">Burundi (+257)</option>
                                    <option value="+855">Cambodia (+855)</option>
                                    <option value="+237">Cameroon (+237)</option>
                                    <option value="+1">Canada (+1)</option>
                                    <option value="+238">Cape Verde (+238)</option>
                                    <option value="+236">Central African Republic (+236)</option>
                                    <option value="+235">Chad (+235)</option>
                                    <option value="+56">Chile (+56)</option>
                                    <option value="+86">China (+86)</option>
                                    <option value="+57">Colombia (+57)</option>
                                    <option value="+269">Comoros (+269)</option>
                                    <option value="+243">Congo (+243)</option>
                                    <option value="+682">Cook Islands (+682)</option>
                                    <option value="+506">Costa Rica (+506)</option>
                                    <option value="+385">Croatia (+385)</option>
                                    <option value="+53">Cuba (+53)</option>
                                    <option value="+357">Cyprus (+357)</option>
                                    <option value="+420">Czech Republic (+420)</option>
                                    <option value="+45">Denmark (+45)</option>
                                    <option value="+253">Djibouti (+253)</option>
                                    <option value="+593">Ecuador (+593)</option>
                                    <option value="+20"selected>Egypt (+20)</option>
                                    <option value="+503">El Salvador (+503)</option>
                                    <option value="+240">Equatorial Guinea (+240)</option>
                                    <option value="+291">Eritrea (+291)</option>
                                    <option value="+372">Estonia (+372)</option>
                                    <option value="+251">Ethiopia (+251)</option>
                                    <option value="+679">Fiji (+679)</option>
                                    <option value="+358">Finland (+358)</option>
                                    <option value="+33">France (+33)</option>
                                    <option value="+241">Gabon (+241)</option>
                                    <option value="+220">Gambia (+220)</option>
                                    <option value="+995">Georgia (+995)</option>
                                    <option value="+49">Germany (+49)</option>
                                    <option value="+233">Ghana (+233)</option>
                                    <option value="+30">Greece (+30)</option>
                                    <option value="+502">Guatemala (+502)</option>
                                    <option value="+224">Guinea (+224)</option>
                                    <option value="+592">Guyana (+592)</option>
                                    <option value="+509">Haiti (+509)</option>
                                    <option value="+504">Honduras (+504)</option>
                                    <option value="+36">Hungary (+36)</option>
                                    <option value="+354">Iceland (+354)</option>
                                    <option value="+62">Indonesia (+62)</option>
                                    <option value="+98">Iran (+98)</option>
                                    <option value="+964">Iraq (+964)</option>
                                    <option value="+353">Ireland (+353)</option>
                                    <option value="+39">Italy (+39)</option>
                                    <option value="+81">Japan (+81)</option>
                                    <option value="+962">Jordan (+962)</option>
                                    <option value="+7">Kazakhstan (+7)</option>
                                    <option value="+254">Kenya (+254)</option>
                                    <option value="+965">Kuwait (+965)</option>
                                    <option value="+996">Kyrgyzstan (+996)</option>
                                    <option value="+856">Laos (+856)</option>
                                    <option value="+371">Latvia (+371)</option>
                                    <option value="+961">Lebanon (+961)</option>
                                    <option value="+266">Lesotho (+266)</option>
                                    <option value="+231">Liberia (+231)</option>
                                    <option value="+370">Lithuania (+370)</option>
                                    <option value="+352">Luxembourg (+352)</option>
                                    <option value="+389">Macedonia (+389)</option>
                                    <option value="+261">Madagascar (+261)</option>
                                    <option value="+265">Malawi (+265)</option>
                                    <option value="+60">Malaysia (+60)</option>
                                    <option value="+960">Maldives (+960)</option>
                                    <option value="+223">Mali (+223)</option>
                                    <option value="+356">Malta (+356)</option>
                                    <option value="+692">Marshall Islands (+692)</option>
                                    <option value="+222">Mauritania (+222)</option>
                                    <option value="+230">Mauritius (+230)</option>
                                    <option value="+52">Mexico (+52)</option>
                                    <option value="+373">Moldova (+373)</option>
                                    <option value="+377">Monaco (+377)</option>
                                    <option value="+976">Mongolia (+976)</option>
                                    <option value="+382">Montenegro (+382)</option>
                                    <option value="+212">Morocco (+212)</option>
                                    <option value="+258">Mozambique (+258)</option>
                                    <option value="+95">Myanmar (+95)</option>
                                    <option value="+264">Namibia (+264)</option>
                                    <option value="+977">Nepal (+977)</option>
                                    <option value="+31">Netherlands (+31)</option>
                                    <option value="+64">New Zealand (+64)</option>
                                    <option value="+505">Nicaragua (+505)</option>
                                    <option value="+227">Niger (+227)</option>
                                    <option value="+234">Nigeria (+234)</option>
                                    <option value="+47">Norway (+47)</option>
                                    <option value="+968">Oman (+968)</option>
                                    <option value="+92">Pakistan (+92)</option>
                                    <option value="+507">Panama (+507)</option>
                                    <option value="+675">Papua New Guinea (+675)</option>
                                    <option value="+595">Paraguay (+595)</option>
                                    <option value="+51">Peru (+51)</option>
                                    <option value="+63">Philippines (+63)</option>
                                    <option value="+48">Poland (+48)</option>
                                    <option value="+351">Portugal (+351)</option>
                                    <option value="+974">Qatar (+974)</option>
                                    <option value="+40">Romania (+40)</option>
                                    <option value="+7">Russia (+7)</option>
                                    <option value="+250">Rwanda (+250)</option>
                                    <option value="+966">Saudi Arabia (+966)</option>
                                    <option value="+221">Senegal (+221)</option>
                                    <option value="+381">Serbia (+381)</option>
                                    <option value="+248">Seychelles (+248)</option>
                                    <option value="+232">Sierra Leone (+232)</option>
                                    <option value="+65">Singapore (+65)</option>
                                    <option value="+421">Slovakia (+421)</option>
                                    <option value="+386">Slovenia (+386)</option>
                                    <option value="+27">South Africa (+27)</option>
                                    <option value="+82">South Korea (+82)</option>
                                    <option value="+34">Spain (+34)</option>
                                    <option value="+94">Sri Lanka (+94)</option>
                                    <option value="+249">Sudan (+249)</option>
                                    <option value="+597">Suriname (+597)</option>
                                    <option value="+268">Swaziland (+268)</option>
                                    <option value="+46">Sweden (+46)</option>
                                    <option value="+41">Switzerland (+41)</option>
                                    <option value="+963">Syria (+963)</option>
                                    <option value="+886">Taiwan (+886)</option>
                                    <option value="+992">Tajikistan (+992)</option>
                                    <option value="+255">Tanzania (+255)</option>
                                    <option value="+66">Thailand (+66)</option>
                                    <option value="+228">Togo (+228)</option>
                                    <option value="+676">Tonga (+676)</option>
                                    <option value="+216">Tunisia (+216)</option>
                                    <option value="+90">Turkey (+90)</option>
                                    <option value="+993">Turkmenistan (+993)</option>
                                    <option value="+256">Uganda (+256)</option>
                                    <option value="+380">Ukraine (+380)</option>
                                    <option value="+971">United Arab Emirates (+971)</option>
                                    <option value="+44">United Kingdom (+44)</option>
                                    <option value="+1">United States (+1)</option>
                                    <option value="+598">Uruguay (+598)</option>
                                    <option value="+998">Uzbekistan (+998)</option>
                                    <option value="+58">Venezuela (+58)</option>
                                    <option value="+84">Vietnam (+84)</option>
                                    <option value="+967">Yemen (+967)</option>
                                    <option value="+260">Zambia (+260)</option>
                                    <option value="+263">Zimbabwe (+263)</option>
                                </select>
                                    <!-- Add more country codes as needed -->
                                </select>
                            </div>
                            <input type="number" name="phone" class="form-control" placeholder="Enter Phone Number" value="{{ old('phone') }}">
                        </div>
                        @if ($errors->has('phone'))
                            <p class="text-error more-info-err" style="color: red;">{{ $errors->first('phone') }}</p>
                        @endif
                      </div>
                      <div class="form-group">
                        <label>Comment</label>
                        <textarea name="message" class="form-control" placeholder="Enter Your Comment" style="height: 120px;">{{ old('message') }}</textarea>
                        @if ($errors->has('message'))
                            <p class="text-error more-info-err" style="color: red;">{{ $errors->first('message') }}</p>
                        @endif
                      </div>
                      
                      
                      <div class="form-group">
                       <button type="submit" class="btn btn-light px-5"><i class="icon-lock"></i> Save</button>
                     </div>
                     </form>
                    </div>
                    </div>
                 </div>
            </div>
            <div class="overlay toggle-menu"></div>
        </div>
    </div>
      <!--End content-wrapper-->
      <!--Start Back To Top Button-->
      <a href="javaScript:void();" class="back-to-top"><i class="fa fa-angle-double-up"></i> </a>
        <!--End Back To Top Button-->
      
      <!--Start footer-->
      <footer class="footer" style="position: unset;">
        <div class="container">
          <div class="text-center">
            Copyright © 2024 Dashboard Admin
          </div>
        </div>
    </footer>
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
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <script>
      $(document).ready(function () {
          let isFormDirty = false; // Track if the form has been modified
    
          // Detect changes in any input field inside a form
          $('form :input').on('change', function () {
              isFormDirty = true;
          });
    
          // Warn user before leaving the page if form is changed
          $(document).on('click', 'a', function (e) {
              if (isFormDirty) {
                  e.preventDefault(); // Prevent link navigation
                  let url = $(this).attr('href'); // Get the link URL
                  
                  if (confirm("You have unsaved changes. Do you really want to leave?")) {
                      window.location.href = url; // Navigate if confirmed
                  }
              }
          });
    
          // Allow form submission without warning
          $('form').on('submit', function () {
              isFormDirty = false;
          });
      });
    </script>
  </body>
</html>
