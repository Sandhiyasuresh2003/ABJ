<!DOCTYPE html>
<html lang="en">
   <head>
      <!-- basic -->
      <meta charset="utf-8">
      <meta http-equiv="X-UA-Compatible" content="IE=edge">
      <!-- mobile metas -->
      <meta name="viewport" content="width=device-width, initial-scale=1">
      <meta name="viewport" content="initial-scale=1, maximum-scale=1">
      <!-- site metas -->
      <title>Agropro</title>
      <meta name="keywords" content="">
      <meta name="description" content="">
      <meta name="author" content="">
      <!-- bootstrap css -->
      <link rel="stylesheet" href="{{ asset('templates/agropro/css/bootstrap.min.css') }}">
      <!-- style css -->
      <link rel="stylesheet" href="{{ asset('templates/agropro/css/style.css') }}">
      <!-- Responsive-->
      <link rel="stylesheet" href="{{ asset('templates/agropro/css/responsive.css') }}">
      <!-- fevicon -->
      <link rel="icon" href="{{ asset('templates/agropro/images/fevicon.png') }}" type="image/gif" />
      <!-- Tweaks for older IEs-->
      <link rel="stylesheet" href="{{ asset('templates/agropro/css/owl.carousel.min.css') }}">
      <link rel="stylesheet" href="https://netdna.bootstrapcdn.com/font-awesome/4.0.3/css/font-awesome.css">
      <link rel="stylesheet" href="{{ asset('templates/agropro/css/bootstrap-datepicker.min.css') }}">
      <!--[if lt IE 9]>
      <script src="https://oss.maxcdn.com/html5shiv/3.7.3/html5shiv.min.js"></script>
      <script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script><![endif]-->
   </head>
   <!-- body -->
   <body class="main-layout">
      <!-- loader  -->
      <div class="loader_bg">
         <div class="loader"><img src="{{ asset('templates/agropro/images/loading.gif') }}" alt="#"/></div>
      </div>
      <!-- end loader -->
      <div class="full_bg">
         <!-- header -->
         <header class="header-area">
            <div class="container-fluid">
               <div class="row d_flex">
                  <div class="col-12">
                     <div class="logo">
                        <a href="{{ route('login') }}"><img src="{{ asset('images/abj-builders-logo.png') }}" alt="ABJ Builders" style="height:78px;width:auto;object-fit:contain"></a>
                     </div>
                  </div>
               </div>
            </div>
         </header>
         <!-- end header inner -->
         <!-- top -->
         <div class="slider_main">
            <!-- carousel code -->
             <div id="banner1" class="carousel slide carousel-fade" data-ride="carousel" data-interval="6000">
                              @if($bannerImages->count() > 1)
                              <ol class="carousel-indicators">
                                 @foreach($bannerImages as $banner)
                                 <li data-target="#banner1" data-slide-to="{{ $loop->index }}" class="{{ $loop->first ? 'active' : '' }}"></li>
                                 @endforeach
                              </ol>
                              @endif
                              <div class="carousel-inner" role="listbox">
                                 @forelse($bannerImages as $banner)
                                    <div class="carousel-item {{ $loop->first ? 'active' : '' }}">
                                       <img src="{{ asset('storage/'.$banner->image_path) }}" alt="ABJ Builders homepage banner {{ $loop->iteration }}" class="d-block img-fluid">
                                    </div>
                                 @empty
                                    <div class="carousel-item active">
                                       <img src="{{ asset('templates/agropro/images/banner.jpg') }}" alt="ABJ Builders" class="d-block img-fluid">
                                    </div>
                                 @endforelse
                              </div>
                              <!-- /.carousel-inner -->
                              <a class="carousel-control-prev" href="#banner1" role="button" data-slide="prev">
                              <i class="fa fa-angle-left" aria-hidden="true"></i>
                              <span class="sr-only">Previous</span>
                              </a>
                              <a class="carousel-control-next" href="#banner1" role="button" data-slide="next">
                              <i class="fa fa-angle-right" aria-hidden="true"></i>
                              <span class="sr-only">Next</span>
                              </a>
                           </div>
                           <div class="container-fluid">
                              <div class="row">
                                 <div class="col-md-12">
                                    <div class="willom">
                                      <h1>ABJ Builders</h1>
                                    </div>
                                 </div>
                              </div>
                           </div>
         </div>
      </div>
      <!-- end banner -->
      <!-- about -->
      <div class="about">
         <div class="container-fluid">
            <div class="row d_flex">
               <div class="col-lg-6 col-md-12">
                  <div class="titlepage text_align_left">
                     <span>{{ $aboutUsSettings?->label ?? 'About Us' }}</span>
                     <h2>{{ $aboutUsSettings?->heading ?: 'AGRICULTURE MARKET' }}</h2>
                     <p>{!! nl2br(e($aboutUsSettings?->description ?: 'fact that a reader will be distracted by the readable content of a page when looking at its layout. The point of using Lorem Ipsum is that itfact that a reader will be distracted by the readable content of a page when looking at its layout. The point of using Lorem Ipsum is that itfact th')) !!}</p>
                  </div>
               </div>
               <div class="col-lg-6 col-md-12">
                  <div class="row d_flex">
                   <div class="col-md-7">
                     <div class="about_img">
                        <figure><img src="{{ $aboutUsSettings?->landing_image_one ? asset('storage/'.$aboutUsSettings->landing_image_one) : asset('templates/agropro/images/about_img.jpg') }}" alt="About Us image 1"/>
                        </figure>
                     </div>
                   </div>
                   <div class="col-md-5">
                     <div class="about_img">
                        <figure><img src="{{ $aboutUsSettings?->landing_image_two ? asset('storage/'.$aboutUsSettings->landing_image_two) : asset('templates/agropro/images/about_img1.jpg') }}" alt="About Us image 2"/>
                        </figure>
                     </div>
                   </div>
                  </div>
               </div>
            </div>
         </div>
      </div>
      <!-- end about -->
      <!-- services -->
      <div class="services">
         <div class="container">
            <div class="row">
               <div class="col-md-12">
                  <div class="titlepage text_align_left">
                     <span>What We Do</span>
                         <h2>SERVICES WE OFFER</h2>
                  </div>
               </div>
            </div>
            <div class="owl-carousel service-carousel">
               @foreach($services as $service)
               <div class="service-slide">
                  <div class="services_box_main">
                     <div  class="services_box text_align_left">
                          <figure><img src="{{ $service->image_url }}" alt="{{ $service->title }}"/></figure>
                        <div class="veget">
                           <h3>{{ $service->title }}</h3>
                           <p>{{ $service->description }}</p>
                        </div>
                     </div>
                     <a class="read_more" href="{{ url('agropro/services.html') }}">Read More</a>
                  </div>
               </div>
               @endforeach
            </div>
         </div>
      </div>
      <!-- end services -->
      <!-- customers -->
      <div class="customers">
         <div class="clients_bg">
            <div class="container">
               <div class="row">
                  <div class="col-sm-12">
                     <div class="titlepage text_align_left">
                         <span>Our Customers</span>
                         <h2>TESTIMONAILS</h2>
                     </div>
                  </div>
               </div>
            </div>
         </div>
         <!-- start slider section -->
         <div id="myCarousel" class="carousel slide clients_banner" data-ride="carousel">
            @if($testimonialSlides->count() > 1)
            <ol class="carousel-indicators">
               @foreach($testimonialSlides as $slide)
               <li data-target="#myCarousel" data-slide-to="{{ $loop->index }}" class="{{ $loop->first ? 'active' : '' }}"></li>
               @endforeach
            </ol>
            @endif
            <div class="carousel-inner">
               @foreach($testimonialSlides as $slide)
               <div class="carousel-item {{ $loop->first ? 'active' : '' }}">
                  <div class="container">
                     <div class="carousel-caption relative">
                        <div class="row d_flex">
                           @foreach($slide as $testimonial)
                           <div class="col-md-6">
                              <div class="custom">
                                 <div class="d_flex">
                                    <i><img src="{{ $testimonial->image_url }}" alt="{{ $testimonial->name }}"/></i>
                                    <div class="clint">
                                      <h4>{{ $testimonial->name }}</h4>
                                      <span>{{ $testimonial->role }}</span>
                                    </div>
                                 </div>
                                  <p>{{ $testimonial->feedback }}</p>
                                  <img src="{{ asset('templates/agropro/images/test.png') }}" alt=""/>
                              </div>
                           </div>
                           @endforeach
                        </div>
                     </div>
                  </div>
               </div>
               @endforeach
            </div>
            @if($testimonialSlides->count() > 1)
            <a class="carousel-control-prev" href="#myCarousel" role="button" data-slide="prev">
            <i class="fa fa-angle-left" aria-hidden="true"></i>
            <span class="sr-only">Previous</span>
            </a>
            <a class="carousel-control-next" href="#myCarousel" role="button" data-slide="next">
            <i class="fa fa-angle-right" aria-hidden="true"></i>
            <span class="sr-only">Next</span>
            </a>
            @endif
         </div>
      </div>
      <!-- end customers -->
      <!-- news -->
      <div class="news">
         <div class="container">
            <div class="row">
               <div class="col-md-12">
                  <div class="titlepage text_align_left">
                     <span>Our</span>
                     <h2>Latest News</h2>
                  </div>
               </div>
            </div>
            <div class="owl-carousel latest-news-carousel">
               @foreach($latestNews as $newsItem)
               <div class="news-slide">
                  <div class="latest {{ $loop->iteration === 2 ? 'box_desho' : '' }}">
                     <figure><img src="{{ $newsItem->image_url }}" alt="{{ $newsItem->title }}"/></figure>
                     <span>{{ $newsItem->published_at->format('d') }}<br>{{ $newsItem->published_at->format('F') }}</span>
                     <div class="nostrud">
                        <h3>{{ $newsItem->title }}</h3>
                        <p>{{ $newsItem->description }}</p>
                        <a class="read_more" href="{{ route('latest-news.show', $newsItem) }}">Read More</a>
                     </div>
                  </div>
               </div>
               @endforeach
            </div>
         </div>
      </div>
      <!-- end news -->
      <!-- contact -->
      <div class="contact">
         <div class="container">
            <div class="row">
               <div class="col-md-12 ">
                  <div class="titlepage text_align_center">
                     <span>Our Contact</span>
                     <h2>Requste A Call Back</h2>
                  </div>
               </div>
               <div class="col-md-8 offset-md-2">
                  <form id="request" class="main_form">
                     <div class="row">
                        <div class="col-md-12 ">
                           <input class="form_control" placeholder="Your Name" type="type" name=" Name"> 
                        </div>
                        <div class="col-md-12">
                           <input class="form_control" placeholder="Phone Number" type="type" name="Phone Number">                          
                        </div>
                       
                        <div class="col-md-12">
                           <input class="textarea" placeholder="Message" type="type" name="message"> 
                        </div>
                        <div class="col-md-12">
                           <div class="group_btn">
                           <button class="send_btn">Send</button>
                            <button class="send_btn">location</button>
                         </div>
                        </div>
                     </div>
                  </form>
               </div>
            </div>
         </div>
          <div class="map-responsive">
            <iframe src="https://www.google.com/maps/embed/v1/place?key=AIzaSyA0s1a7phLN0iaD6-UE7m4qP-z21pH0eSc&amp;q=Eiffel+Tower+Paris+France" width="600" height="430" frameborder="0" style="border:0; width: 100%;" allowfullscreen=""></iframe>
         </div>
      </div>
      <!-- end contact -->
      <!--  footer -->
      <footer>
         <div class="footer">
            <div class="container">
               <div class="row">
                         <div class="col-lg-3 col-md-6">
                           <div class="hedingh3  text_align_left">
                              <h3>Newsletter</h3>
                              <form id="colof" class="form_subscri">
                                 <input class="newsl" placeholder="Enter Email" type="text" name="Email">
                                 <button class="subsci_btn"><img src="{{ asset('templates/agropro/images/new.png') }}" alt="#"/></button>
                              </form>
                              
                           </div>
                        </div>
                        <div class="col-lg-3 col-md-6">
                           <div class="hedingh3 text_align_left">
                              <h3> Explore</h3>
                              <ul class="menu_footer">
                                 <li><a href="{{ route('agropro.home') }}">Home</a></li>
                                 <li><a href="{{ url('agropro/about.html') }}">About</a></li>
                                 <li><a href="{{ url('agropro/service.html') }}">Service</a></li>
                                 <li><a href="Javascript:void(0)">Projects</a></li>
                                 <li><a href="{{ url('agropro/testimonail.html') }}">Testimonail</a></li>
                                 <li><a href="{{ url('agropro/contact.html') }}">Contact us</a></li>
                              </ul>
                           </div>
                        </div>
                        <div class="col-lg-3 col-md-6">
                           <div class="hedingh3 text_align_left">
                              <h3>Recent Posts</h3>
                              <ul class="recent">
                                 <li><img src="{{ asset('templates/agropro/images/resent.jpg') }}" alt="#"/>ea commodo consequat. Duis aute </li>
                                 <li><img src="{{ asset('templates/agropro/images/resent.jpg') }}" alt="#"/>ea commodo consequat. Duis aute </li>
                              </ul>
                           </div>
                        </div>
                         <div class="col-lg-3 col-md-6">
                           <div class="hedingh3  flot_right text_align_left">
                              <h3>ContacT</h3>
                              <ul class="top_infomation">
                                 <li><i class="fa fa-phone" aria-hidden="true"></i>
                                    +01 1234567892
                                 </li>
                                 <li><i class="fa fa-envelope" aria-hidden="true"></i>
                                    <a href="Javascript:void(0)">demo@gmail.com</a>
                                 </li>
                              </ul>
                           </div>
                        </div>
                     </div>
                  </div>
             
            <div class="copyright">
               <div class="container">
                  <div class="row d_flex">
                     <div class="col-md-8">
                        <p>© 2022 All Rights Reserved. Design by <a href="https://html.design/"> Free html Templates</a></p>
                     </div>
                     <div class="col-md-4">
                           <ul class="social_icon ">
                              <li><a href="Javascript:void(0)"><i class="fa fa-facebook" aria-hidden="true"></i></a></li>
                              <li><a href="Javascript:void(0)"><i class="fa fa-twitter" aria-hidden="true"></i></a></li>
                              <li><a href="Javascript:void(0)"><i class="fa fa-linkedin" aria-hidden="true"></i></a></li>
                           </ul>
                     </div>
                  </div>
               </div>
            </div>
         </div>
      </footer>
      <!-- end footer -->
      <!-- Javascript files-->
      <script src="{{ asset('templates/agropro/js/jquery.min.js') }}"></script>
      <script src="{{ asset('templates/agropro/js/bootstrap.bundle.min.js') }}"></script>
      <script src="{{ asset('templates/agropro/js/jquery-3.0.0.min.js') }}"></script>
      <script src="{{ asset('templates/agropro/js/owl.carousel.min.js') }}"></script>
      <script src="{{ asset('templates/agropro/js/bootstrap-datepicker.min.js') }}"></script>
      <script src="{{ asset('templates/agropro/js/custom.js') }}"></script>
   </body>
</html>