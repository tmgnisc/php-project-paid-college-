<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <?php require('inc/links.php'); ?>
  <title><?php echo $settings_r['site_title'] ?> -ROOMS</title>

</head>

<body class="bg-light">

  <?php require('inc/header.php'); ?>

  <div class="my-5 px-4">
    <h2 class="fw-bold h-font text-center">Our Rooms</h2>
    <div class="h-line bg-dark"></div>

  </div>

  <?php
    // Helper to safely get GET params
    $get_val = function($key, $default = '') {
      return isset($_GET[$key]) ? trim($_GET[$key]) : $default;
    };

    $search        = $get_val('search');
    $min_price     = $get_val('min_price');
    $max_price     = $get_val('max_price');
    $adults        = $get_val('adults');
    $children      = $get_val('children');
    $sort          = $get_val('sort', 'recommended');
    $facility_ids  = isset($_GET['facility']) && is_array($_GET['facility']) ? array_filter($_GET['facility'], 'is_numeric') : [];

    // Build dynamic room query
    $conditions = ["r.status = 1", "r.removed = 0"];
    $params = [];
    $types  = '';

    if ($search !== '') {
      $conditions[] = "(r.name LIKE ? OR r.description LIKE ?)";
      $like = "%{$search}%";
      $params[] = $like;
      $params[] = $like;
      $types .= 'ss';
    }

    if ($min_price !== '' && is_numeric($min_price)) {
      $conditions[] = "r.price >= ?";
      $params[] = floatval($min_price);
      $types .= 'd';
    }

    if ($max_price !== '' && is_numeric($max_price)) {
      $conditions[] = "r.price <= ?";
      $params[] = floatval($max_price);
      $types .= 'd';
    }

    if ($adults !== '' && is_numeric($adults)) {
      $conditions[] = "r.adult >= ?";
      $params[] = intval($adults);
      $types .= 'i';
    }

    if ($children !== '' && is_numeric($children)) {
      $conditions[] = "r.children >= ?";
      $params[] = intval($children);
      $types .= 'i';
    }

    // Facilities filter: require each selected facility via EXISTS
    $facility_conditions = '';
    if (!empty($facility_ids)) {
      foreach ($facility_ids as $fid) {
        $conditions[] = "EXISTS (SELECT 1 FROM room_facilities rf WHERE rf.room_id = r.id AND rf.facilities_id = ?)";
        $params[] = intval($fid);
        $types .= 'i';
      }
    }

    $where_sql = implode(' AND ', $conditions);

    // Sorting / recommendation
    $order_sql = '';
    switch ($sort) {
      case 'price_asc':
        $order_sql = 'ORDER BY r.price ASC';
        break;
      case 'price_desc':
        $order_sql = 'ORDER BY r.price DESC';
        break;
      case 'newest':
        $order_sql = 'ORDER BY r.id DESC';
        break;
      default: // recommended: closest to average price, then lower price first
        $order_sql = 'ORDER BY ABS(r.price - (SELECT AVG(price) FROM rooms WHERE status=1 AND removed=0)), r.price ASC';
        break;
    }

    $sql = "SELECT r.* FROM rooms r WHERE {$where_sql} {$order_sql}";

    // Prepare and execute
    $room_res = null;
    if ($stmt = mysqli_prepare($con, $sql)) {
      if (!empty($params)) {
        mysqli_stmt_bind_param($stmt, $types, ...$params);
      }
      mysqli_stmt_execute($stmt);
      $room_res = mysqli_stmt_get_result($stmt);
      mysqli_stmt_close($stmt);
    }

    // Fetch facilities for filters
    $facilities = [];
    $fac_res = mysqli_query($con, "SELECT id, name FROM facilities ORDER BY name ASC");
    if ($fac_res) {
      while ($row = mysqli_fetch_assoc($fac_res)) {
        $facilities[] = $row;
      }
    }
  ?>

  <div class="container-fluid">
    <div class="row">

      <div class="col-lg-3 col-md-12 mb-lg-0 mb-4 ps-4">
        <nav class="navbar navbar-expand-lg navbar-light bg-white rounded shadow">
          <div class="container-fluid flex-lg-column align-items-stretch">
            <h4 class="mt-2">FILTERS</h4>
            <button class="navbar-toggler shadow-none" type="button" data-bs-toggle="collapse" data-bs-target="#filterDropdown" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
              <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse flex-column align-items-stretch mt-2" id="filterDropdown">
              <form method="GET" class="w-100">
                <div class="border bg-light p-3 rounded mb-3">
                  <h5 class="mb-3" style="font-size:18px;">Search & Price</h5>
                  <input type="text" name="search" placeholder="Search rooms..." value="<?php echo htmlspecialchars($search); ?>" class="form-control shadow-none mb-2">
                  <div class="row g-2">
                    <div class="col-6">
                      <label class="form-label">Min Price</label>
                      <input type="number" name="min_price" min="0" step="1" value="<?php echo htmlspecialchars($min_price); ?>" class="form-control shadow-none">
                    </div>
                    <div class="col-6">
                      <label class="form-label">Max Price</label>
                      <input type="number" name="max_price" min="0" step="1" value="<?php echo htmlspecialchars($max_price); ?>" class="form-control shadow-none">
                    </div>
                  </div>
                </div>

                <div class="border bg-light p-3 rounded mb-3">
                  <h5 class="mb-3" style="font-size: 18px;">FACILITIES</h5>
                  <?php if (!empty($facilities)) { foreach ($facilities as $fac): ?>
                    <div class="mb-2">
                      <input type="checkbox" name="facility[]" value="<?php echo $fac['id']; ?>" id="fac_<?php echo $fac['id']; ?>" class="form-check-input shadow-none me-1"
                        <?php echo in_array($fac['id'], $facility_ids) ? 'checked' : ''; ?>>
                      <label class="form-check-label" for="fac_<?php echo $fac['id']; ?>"><?php echo htmlspecialchars($fac['name']); ?></label>
                    </div>
                  <?php endforeach; } else { ?>
                    <div class="text-muted small">No facilities found</div>
                  <?php } ?>
                </div>

                <div class="border bg-light p-3 rounded mb-3">
                  <h5 class="mb-3" style="font-size: 18px;">GUESTS</h5>
                  <div class="d-flex">
                    <div class="me-3">
                      <label class="form-label">Adults</label>
                      <input type="number" name="adults" min="0" value="<?php echo htmlspecialchars($adults); ?>" class="form-control shadow-none">
                    </div>

                    <div>
                      <label class="form-label">Children</label>
                      <input type="number" name="children" min="0" value="<?php echo htmlspecialchars($children); ?>" class="form-control shadow-none">
                    </div>
                  </div>
                </div>

                <div class="border bg-light p-3 rounded mb-3">
                  <h5 class="mb-3" style="font-size: 18px;">Sort By</h5>
                  <select name="sort" class="form-select shadow-none">
                    <option value="recommended" <?php echo $sort === 'recommended' ? 'selected' : ''; ?>>Recommended</option>
                    <option value="price_asc" <?php echo $sort === 'price_asc' ? 'selected' : ''; ?>>Price: Low to High</option>
                    <option value="price_desc" <?php echo $sort === 'price_desc' ? 'selected' : ''; ?>>Price: High to Low</option>
                    <option value="newest" <?php echo $sort === 'newest' ? 'selected' : ''; ?>>Newest</option>
                  </select>
                </div>

                <div class="d-grid gap-2 mb-3">
                  <button type="submit" class="btn btn-dark shadow-none">Apply Filters</button>
                  <a href="rooms.php" class="btn btn-outline-secondary shadow-none">Clear</a>
                </div>
              </form>
            </div>

          </div>
        </nav>
      </div>


      <div class="col-lg-9 col-md-12 px-4">

        <div class="d-flex justify-content-between align-items-center mb-3">
          <h4 class="fw-bold mb-0">Browse Rooms</h4>
          <span class="text-muted"><?php echo $room_res ? mysqli_num_rows($room_res) : 0; ?> found</span>
        </div>

        <?php
        if ($room_res && mysqli_num_rows($room_res) > 0) {
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
          if (!$settings_r['shutdown']) {
             $login=0;
            if (isset($_SESSION['login']) && $_SESSION['login'] == true) {
              $login=1;
            }
            $book_btn = "<button onclick='checkLoginToBook($login,$room_data[id])' class='btn btn-sm w-100 text-white custom-bg shadow-none mb-2'>Book Now</button>";
          }

          //print room card

          echo <<<data
<div class="card mb-4 border-0 shadow-sm room-card">
  <div class="row g-0 p-3 align-items-center">
    <div class="col-md-5 mb-lg-0 mb-md-0 mb-3">
      <img src="$room_thumb" class="img-fluid rounded">
    </div>
    <div class="col-md-5 px-lg-3 px-md-3 px-0">
      <div class="d-flex justify-content-between align-items-start mb-2">
        <h5 class="mb-0">$room_data[name]</h5>
        <span class="room-price">Rs.$room_data[price] / night</span>
      </div>
      <div class="features mb-2">
        <small class="text-muted">Features</small><br>
        $features_data
      </div>
      <div class="facilities mb-2">
        <small class="text-muted">Facilities</small><br>
        $facilities_data
      </div>
      <div class="guests">
        <small class="text-muted">Guests</small><br>
        <span class="pill-badge">$room_data[adult] Adults</span>
        <span class="pill-badge">$room_data[children] Children</span>
      </div>
    </div>
    <div class="col-md-2 mt-lg-0 mt-md-0 mt-4 text-center d-flex flex-column gap-2">
      $book_btn
      <a href="room_details.php?id=$room_data[id]" class="btn btn-sm w-100 btn-outline-dark shadow-none">More details</a>
    </div>
  </div>
</div>

data;
        }
        } else {
          echo "<div class='alert alert-info'>No rooms found for selected filters.</div>";
        }
        ?>



      </div>
    </div>
  </div>


  <?php require('inc/footer.php'); ?>


</body>

</html>