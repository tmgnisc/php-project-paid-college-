  <footer class="mt-5 border-top pt-5 pb-3 bg-white">
    <div class="container">
      <div class="row g-4">
        <div class="col-lg-4">
          <h3 class="h-font fw-bold fs-3 mb-2"><?php echo $settings_r['site_title'] ?></h3>
          <p class="text-muted mb-0"><?php echo $settings_r['site_about'] ?></p>
        </div>
        <div class="col-lg-4">
          <h6 class="fw-bold mb-3">Links</h6>
          <div class="d-flex flex-column gap-2">
            <a href="index.php" class="text-decoration-none text-muted"><i class="bi bi-chevron-right"></i> Home</a>
            <a href="rooms.php" class="text-decoration-none text-muted"><i class="bi bi-chevron-right"></i> Rooms</a>
            <a href="facilities.php" class="text-decoration-none text-muted"><i class="bi bi-chevron-right"></i> Facilities</a>
            <a href="contact.php" class="text-decoration-none text-muted"><i class="bi bi-chevron-right"></i> Contact us</a>
            <a href="about.php" class="text-decoration-none text-muted"><i class="bi bi-chevron-right"></i> About</a>
          </div>
        </div>
        <div class="col-lg-4">
          <h6 class="fw-bold mb-3">Follow us</h6>
          <div class="d-flex flex-column gap-2">
            <?php
            if ($contact_r['tw'] != '') {
              echo <<<data
              <a href="$contact_r[tw]" class="text-decoration-none text-muted"><i class="bi bi-twitter me-1"></i> Twitter</a>
              data;
            }
            ?>
            <a href="<?php echo $contact_r['fb'] ?>" class="text-decoration-none text-muted"><i class="bi bi-facebook me-1"></i> Facebook</a>
            <a href="<?php echo $contact_r['insta'] ?>" class="text-decoration-none text-muted"><i class="bi bi-instagram me-1"></i> Instagram</a>
          </div>
        </div>
      </div>
      <div class="text-center text-muted mt-4">
        <small>Designed and Developed by Anshika</small>
      </div>
    </div>
  </footer>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-MrcW6ZMFYlzcLA8Nl+NtUVF0sA7MsXsP1UyJoMp4YLEuNSfAP+JcXn/tWtIaxVXM" crossorigin="anonymous"></script>
  <script>
    function alert(type, msg, position = 'body') {
      let bs_class = (type == 'success') ? 'alert-success' : 'alert-danger';
      let element = document.createElement('div');

      element.innerHTML = `
             <div class="alert ${bs_class} alert-dismissible fade show" role="alert">
               <strong class="me-3">${msg}</strong>
               <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
             </div>
        `;

      if (position == 'body') {
        document.body.append(element);
        element.classList.add('custom-alert');
      } else {
        document.getElementById(position).appendChild(element);
      }

      setTimeout(remAlert, 3000);


    }

    function remAlert() {
      document.getElementsByClassName('alert')[0].remove();
    }

    function setActive() {
      navbar = document.getElementById('nav-bar');
      let a_tags = navbar.getElementsByTagName('a');

      for (i = 0; i < a_tags.length; i++) {
        let file = a_tags[i].href.split('/').pop();
        let file_name = file.split('.')[0];

        if (document.location.href.indexOf(file_name) >= 0) {
          a_tags[i].classList.add('active');
        }
      }
    }


    let register_form = document.getElementById('register-form');
    register_form.addEventListener('submit', (e) => {
      e.preventDefault();

      let data = new FormData();

      data.append('name', register_form.elements['name'].value);
      data.append('email', register_form.elements['email'].value);
      data.append('phonenum', register_form.elements['phonenum'].value);
      data.append('address', register_form.elements['address'].value);
      data.append('pincode', register_form.elements['pincode'].value);
      data.append('dob', register_form.elements['dob'].value);
      data.append('pass', register_form.elements['pass'].value);
      data.append('cpass', register_form.elements['cpass'].value);
      data.append('profile', register_form.elements['profile'].files[0]);
      data.append('register', '');

      var myModal = document.getElementById('registerModal');
      var modal = bootstrap.Modal.getInstance(myModal);
      modal.hide();

      let xhr = new XMLHttpRequest();
      xhr.open("POST", "ajax/login_register.php", true);

      xhr.onload = function() {
        if (this.responseText == 'pass_mismatch') {
          alert('error', "Password Mismatched!");
        } else if (this.responseText == 'email_already') {
          alert('error', "Email is already registered!");
        } else if (this.responseText == 'phone_already') {
          alert('error', "Phone is already registered!");
        } else if (this.responseText == 'inv_img') {
          alert('error', "Only JPG, WEBP & PNG images are allowed!");
        } else if (this.responseText == 'upd_failed') {
          alert('error', "Image upload failed!");
        } else if (this.responseText == 'mail_failed') {
          alert('error', "Cannot send confirmation email .Server down!");
        } else if (this.responseText == 'ins_failed') {
          alert('error', "Registration failed!");
        } else {
          alert('success', "Registration successful.");
          register_form.reset();
        }
      }
      xhr.send(data);
    });

    let login_form = document.getElementById('login-form');
    login_form.addEventListener('submit', (e) => {
      e.preventDefault();

      let data = new FormData();

      data.append('email_name_mob', login_form.elements['email_name_mob'].value);
      data.append('pass', login_form.elements['pass'].value);



      data.append('login', '');

      var myModal = document.getElementById('loginModal');
      var modal = bootstrap.Modal.getInstance(myModal);
      modal.hide();

      let xhr = new XMLHttpRequest();
      xhr.open("POST", "ajax/login_register.php", true);

      xhr.onload = function() {
        if (this.responseText == 'inv_email_name_mob') {
          alert('error', "Inavlid Cred!");
        }
        // else if(this.responseText == 'email_already'){
        //   alert('error',"Email is already registered!");
        // }
        else if (this.responseText == 'inactive') {
          alert('error', "Account Band!");
        } else if (this.responseText == 'invalid_pass') {
          alert('error', "Incorrect password!");
        } else {
          let fileurl=window.location.href.split('/').pop().split('?').shift();
          if(fileurl=='room_details.php'){
            window.location = window.location.href;
          }
          else{
            window.location = window.location.pathname;
          }
          
        }
      }
      xhr.send(data);
    });

    let forgot_form = document.getElementById('forgot-form');

    forgot_form.addEventListener('submit', (e) => {
      e.preventDefault();

      let data = new FormData();
      data.append('name_mob', forgot_form.elements['name_mob'].value);
      data.append('pass', forgot_form.elements['pass'].value);

      // Correct flag for forgot password
      data.append('forgot_pass', '');

      var myModal = document.getElementById('forgotModal');
      var modal = bootstrap.Modal.getInstance(myModal);
      modal.hide();

      let xhr = new XMLHttpRequest();
      xhr.open("POST", "ajax/login_register.php", true);

      xhr.onload = function() {
        // Correct conditions for forgot password
        if (this.responseText == 'inv_name_mob') {
          alert('error', "Invalid name or mobile!");
        } else if (this.responseText == 'not_found') {
          alert('error', "No user found!");
        } else if (this.responseText == 'success') {
          alert('success', "Password updated successfully!");
        } else {
          alert('error', "Something went wrong!");
        }
      }

      xhr.send(data);
    });

    function checkLoginToBook(status,room_id){
      if(status){
        window.location.href='confirm_booking.php?id='+room_id;
      }
      else{
        alert('error','Please login to book room!');
      }
    }



    setActive();
  </script>