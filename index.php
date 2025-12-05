<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@12/swiper-bundle.min.css" />
  <?php require('inc/links.php'); ?>
  <title><?php echo $settings_r['site_title'] ?> -Home</title>


  <style>
    .availability-form {
      margin-top: -50px;
      z-index: 2;
      position: relative;
    }

    @media screen and (max-width: 575px) {
      .availability-form {
        margin-top: 25px;
        padding: 0 35px;
      }
    }
  </style>
</head>

<body class="bg-light">

  <?php require('inc/header.php'); ?>

  <!-- Hero + availability -->
  <div class="container px-lg-4 mt-4">
    <div class="hero p-4 p-lg-5">
      <div class="row align-items-center gy-4">
        <div class="col-lg-6">
          <p class="soft-badge d-inline-block mb-3">Your next stay awaits</p>
          <h1 class="fw-bold h-font mb-3">Find the perfect room for your next trip</h1>
          <p class="lead mb-4">Browse curated rooms, filter by price and amenities, and book in minutes.</p>
          <div class="d-flex gap-2">
            <a href="rooms.php" class="btn btn-light text-dark shadow-none">Browse Rooms</a>
            <a href="#availability" class="btn btn-outline-light shadow-none">Check Availability</a>
          </div>
        </div>
        <div class="col-lg-6">
          <div id="availability" class="bg-white text-dark p-4 floating-card">
            <h5 class="mb-3 fw-bold">Check Booking Availability</h5>
            <form action="rooms.php" method="GET">
              <div class="row g-3 align-items-end">
                <div class="col-md-6">
                  <label class="form-label fw-semibold">Search</label>
                  <input type="text" name="search" class="form-control shadow-none" placeholder="Room, facility...">
                </div>
                <div class="col-md-6">
                  <label class="form-label fw-semibold">Check-in</label>
                  <input type="date" name="checkin" class="form-control shadow-none">
                </div>
                <div class="col-md-6">
                  <label class="form-label fw-semibold">Check-out</label>
                  <input type="date" name="checkout" class="form-control shadow-none">
                </div>
                <div class="col-md-3">
                  <label class="form-label fw-semibold">Adults</label>
                  <select class="form-select shadow-none" name="adults">
                    <option value="">Any</option>
                    <option value="1">1</option>
                    <option value="2">2</option>
                    <option value="3">3</option>
                    <option value="4">4+</option>
                  </select>
                </div>
                <div class="col-md-3">
                  <label class="form-label fw-semibold">Children</label>
                  <select class="form-select shadow-none" name="children">
                    <option value="">Any</option>
                    <option value="0">0</option>
                    <option value="1">1</option>
                    <option value="2">2</option>
                    <option value="3">3+</option>
                  </select>
                </div>
                <div class="col-12 text-end">
                  <button type="submit" class="btn text-white shadow-none custom-bg px-4">Search</button>
                </div>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- our rooms -->
  <h2 class="mt-5 pt-4 mb-4 text-center fw-bold h-font section-title">Featured Rooms</h2>

  <div class="container">
    <div class="row">

      <?php
        $room_res = select("SELECT * FROM `rooms` WHERE `status`=? AND `removed`=? ORDER BY `id` DESC LIMIT 3", [1, 0], 'ii');

        while ($room_data = mysqli_fetch_assoc($room_res)) {
          // get features of room
          $fea_q = mysqli_query($con, "SELECT f.name FROM `features` f 
        INNER JOIN `room_features` rfea ON f.id = rfea.features_id 
        WHERE rfea.room_id = '$room_data[id]'");

          $features_data = "";
          while ($fea_row = mysqli_fetch_assoc($fea_q)) {
            $features_data .= "<span class='badge rounded-pill bg-light text-dark text-wrap me-1 mb-1'>
                  $fea_row[name]
                </span>";
          }

          //get facilities of room

          $fac_q = mysqli_query($con, "SELECT f.name FROM `facilities` f 
        INNER JOIN `room_facilities` rfac ON f.id = rfac.facilities_id 
        WHERE rfac.room_id = '$room_data[id]'");

          $facilities_data = "";
          while ($fac_row = mysqli_fetch_assoc($fac_q)) {
            $facilities_data .= "<span class='badge rounded-pill bg-light text-dark text-wrap me-1 mb-1'>
                  $fac_row[name]
                </span>";
          }

          //get thumbnail of image

          $room_thumb = ROOMS_IMG_PATH . "thumbnail.jpg";
          $thumb_q = mysqli_query($con, "SELECT * FROM `room_images` 
          WHERE `room_id`='$room_data[id]' 
          AND `thumb` = '1'");

          if (mysqli_num_rows($thumb_q) > 0) {
            $thumb_res = mysqli_fetch_assoc($thumb_q);
            $room_thumb = ROOMS_IMG_PATH . $thumb_res['image'];
          }

          $book_btn = "";
          if(!$settings_r['shutdown']){
            $login=0;
            if (isset($_SESSION['login']) && $_SESSION['login'] == true) {
              $login=1;
            }
            $book_btn="<button onclick='checkLoginToBook($login,$room_data[id])' class='btn btn-sm text-white custom-bg shadow-none'>Book Now</button>";
          }

          //print room card

          echo <<<data
           <div class="col-lg-4 col-md-6 my-3">
        <div class="card border-0 shadow-sm room-card h-100">
          <img src="$room_thumb" class="card-img-top">
          <div class="card-body d-flex flex-column">
            <div class="d-flex justify-content-between align-items-start mb-2">
              <h5 class="mb-0">$room_data[name]</h5>
              <span class="room-price">Rs.$room_data[price]</span>
            </div>
            <div class="features mb-2">
              <small class="text-muted">Features</small><br>
              $features_data;
            </div>
            <div class="facilities mb-2">
              <small class="text-muted">Facilities</small><br>
              $facilities_data;
            </div>
            <div class="guests mb-3">
              <small class="text-muted">Guests</small><br>
              <span class="pill-badge">$room_data[adult] Adults</span>
              <span class="pill-badge">$room_data[children] Children</span>
            </div>
            <div class="d-flex gap-2 mt-auto">
              $book_btn
              <a href="room_details.php?id=$room_data[id]" class="btn btn-sm btn-outline-dark shadow-none flex-grow-1">More details</a>
            </div>
          </div>
        </div>
      </div>

data;
        }
        ?>
     

    


      <div class="col-lg-12 text-center mt-5">
        <a href="rooms.php" class="btn btn-sm btn-outline-dark rounded-0 fw-bold shadow-none">More Rooms >>></a>
      </div>
    </div>
  </div>

  <!-- facilities -->

  <h2 class="mt-5 pt-4 mb-4 text-center fw-bold h-font">OUR FACILITIES</h2>

  <div class="container">
    <div class="row justify-content-evenly px-lg-0 px-md-0 px-5">
      <?php
$res = mysqli_query($con,"SELECT * FROM `facilities` ORDER BY `id` DESC LIMIT 5");
$path = FACILITIES_IMG_PATH;

while ($row = mysqli_fetch_assoc($res)) {
echo <<<data
 <div class="col-lg-2 col-md-2 text-center bg-white rounded shadow py-4 my-3">
        <img src="$path$row[icon]" width="60px">
        <h5 class="mt-3">$row[name]</h5>
      </div>
data;
}
?>

      <div class="col-lg-12 text-center mt-5">
        <a href="facilities.php" class="btn btn-sm btn-outline-dark rounded-0 fw-bold shadow-none">More Facilities >>></a>
      </div>
    </div>
  </div>

  <!-- testimonials -->
  <h2 class="mt-5 pt-4 mb-4 text-center fw-bold h-font">TESTIMONIALS</h2>

  <div class="container mt-5">
    <div class="swiper swiper-testimonials">
      <div class="swiper-wrapper mb-5">

        <div class="swiper-slide bg-white p-4">
          <div class="profle d-flex align-items-center mb-3">
            <img src="images/features/star.svg" width="30px">
            <h6 class="m-0 ms-2">Random user1</h6>
          </div>
          <p>
            Lorem ipsum dolor, sit amet consectetur adipisicing elit.
            Deleniti doloremque porro asperiores ipsa, quaerat distinctio expedita
            amet omnis sapiente laborum!
          </p>

          <div class="rating">
            <i class="bi bi-star-fill text-warning"></i>
            <i class="bi bi-star-fill text-warning"></i>
            <i class="bi bi-star-fill text-warning"></i>
            <i class="bi bi-star-fill text-warning"></i>
          </div>
        </div>

        <div class="swiper-slide bg-white p-4">
          <div class="profle d-flex align-items-center mb-3">
            <img src="images/features/star.svg" width="30px">
            <h6 class="m-0 ms-2">Random user1</h6>
          </div>
          <p>
            Lorem ipsum dolor, sit amet consectetur adipisicing elit.
            Deleniti doloremque porro asperiores ipsa, quaerat distinctio expedita
            amet omnis sapiente laborum!
          </p>

          <div class="rating">
            <i class="bi bi-star-fill text-warning"></i>
            <i class="bi bi-star-fill text-warning"></i>
            <i class="bi bi-star-fill text-warning"></i>
            <i class="bi bi-star-fill text-warning"></i>
          </div>
        </div>

        <div class="swiper-slide bg-white p-4">
          <div class="profle d-flex align-items-center mb-3">
            <img src="images/features/star.svg" width="30px">
            <h6 class="m-0 ms-2">Random user1</h6>
          </div>
          <p>
            Lorem ipsum dolor, sit amet consectetur adipisicing elit.
            Deleniti doloremque porro asperiores ipsa, quaerat distinctio expedita
            amet omnis sapiente laborum!
          </p>

          <div class="rating">
            <i class="bi bi-star-fill text-warning"></i>
            <i class="bi bi-star-fill text-warning"></i>
            <i class="bi bi-star-fill text-warning"></i>
            <i class="bi bi-star-fill text-warning"></i>
          </div>
        </div>

      </div>
      <div class="swiper-pagination"></div>
    </div>
    <div class="col-lg-12 text-center mt-5">
      <a href="about.php" class="btn btn-sm btn-outline-dark rounded-0 fw-bold shadow-none">Know More >>></a>
    </div>
  </div>

  <!-- Reach us -->


  <h2 class="mt-5 pt-4 mb-4 text-center fw-bold h-font">REACH US</h2>
  <div class="container">
    <div class="row">
      <div class="col-lg-8 col-md-8 p-4 mb-lg-0 mb-3 bg-white rounded">
        <iframe class="w-100 rounded" height="320px" src="<?php echo $contact_r['iframe'] ?>" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
      </div>
      <div class="col-lg-4 col-md-4">
        <div class="bg-white p-4 rounded mb-4">
          <h5>Call us</h5>
          <a href="tel: +<?php echo $contact_r['pn1'] ?>" class="d-inline-block mb-2 text-decoration-none text-dark">
            <i class="bi bi-telephone-fill"></i> +<?php echo $contact_r['pn1'] ?>
          </a>
          <br>
          <?php
          if ($contact_r['pn2'] != '') {
            echo <<<data
            <a href="tel: +$contact_r[pn2]" class="d-inline-block text-decoration-none text-dark">
            <i class="bi bi-telephone-fill"></i> +$contact_r[pn2]
          </a>
          data;
          }
          ?>

        </div>

        <div class="bg-white p-4 rounded mb-4">
          <h5>Follow us</h5>
  <?php
if ($contact_r['tw'] != '') {
    echo <<<data
<a href="$contact_r[tw]" class="d-inline-block mb-3">
    <span class="badge bg-light text-dark fs-6 p-2">
        <i class="bi bi-twitter me-1"></i>Twitter
    </span>
</a>
<br>
data;
}
?>



          <a href="<?php echo $contact_r['fb'] ?>" class="d-inline-block mb-3">
            <span class="badge bg-light text-dark fs-6 p-2">
              <i class="bi bi-facebook me-1"></i>Facebook
            </span>
          </a>

          <br>
          <a href="<?php echo $contact_r['insta'] ?>" class="d-inline-block mb-3">
            <span class="badge bg-light text-dark fs-6 p-2">
              <i class="bi bi-instagram me-1"></i>Instagram
            </span>
          </a>
        </div>
      </div>
    </div>
  </div>


  <?php require('inc/footer.php'); ?>



  <script src="https://cdn.jsdelivr.net/npm/swiper@12/swiper-bundle.min.js"></script>
  <script>
    var swiper = new Swiper(".swiper-container", {
      spaceBetween: 30,
      effect: "fade",
      loop: true,

      autoplay: {
        delay: 3500,
        disableOnInteraction: false,
      },
    });
    var swiper = new Swiper(".swiper-testimonials", {
      effect: "coverflow",
      grabCursor: true,
      centeredSlides: true,
      slidesPerView: "auto",
      slidesPerView: "3",
      loop: true,
      coverflowEffect: {
        rotate: 50,
        stretch: 0,
        depth: 100,
        modifier: 1,
        slideShadows: false,
      },
      pagination: {
        el: ".swiper-pagination",
      },
      breakpoints: {
        320: {
          slidesPerView: 1,
        },
        640: {
          slidesPerView: 1,
        },
        768: {
          slidesPerView: 2,
        },
        1024: {
          slidesPerView: 3,
        },
      }
    });
  </script>

  </script>
</body>

</html>