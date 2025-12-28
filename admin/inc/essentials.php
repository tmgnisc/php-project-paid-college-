<?php 

//frontend purpose data

// Build SITE_URL dynamically so the host matches how the user accessed the site
// (prevents session cookies from being treated as cross-domain during redirects e.g. Stripe)
$appHost = $_SERVER['HTTP_HOST'] ?? '127.0.0.1';
$isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || $_SERVER['SERVER_PORT'] == 443;
$appScheme = $isHttps ? 'https' : 'http';
// Keep the explicit /Hotel/ path (case sensitive on Linux)
define('SITE_URL', $appScheme . '://' . $appHost . '/Hotel/');
define('ABOUT_IMG_PATH',SITE_URL.'images/about/');
define('CAROUSEL_IMG_PATH',SITE_URL.'images/carousel/');
define('FACILITIES_IMG_PATH',SITE_URL.'images/facilities/');
define('ROOMS_IMG_PATH',SITE_URL.'images/rooms/');
define('USERS_IMG_PATH',SITE_URL.'images/users/');


//backend upload process needs this data

// Match the actual "Hotel" directory name under the web root
define('UPLOAD_IMAGE_PATH', $_SERVER['DOCUMENT_ROOT']. '/Hotel/images/');//path return garxa
define('ABOUT_FOLDER','about/');
define('CAROUSEL_FOLDER','carousel/');
define('FACILITIES_FOLDER','facilities/');
define('ROOMS_FOLDER','rooms/');
define('USERS_FOLDER','users/');

//sendgrid api
define('SENDGRID_API_KEY',"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");

function adminLogin()
{
    session_start();
    if(!(isset($_SESSION['adminLogin']) && $_SESSION['adminLogin']==true)){
    echo"
    <script>
    window.location.href='index.php';
    </script>
    ";
    exit;
    }
}

function redirect($url){
    echo"
    <script>
    window.location.href='$url';
    </script>
    ";
    exit;
}


function alert($type, $msg){
    $bs_class =($type=="success") ? "alert-success" : "alert-danger";

       echo <<<alert
             <div class="alert $bs_class alert-dismissible fade show custom-alert" role="alert">
               <strong class="me-3">$msg</strong>
               <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
             </div>
        alert;
}

function uploadImage($image, $folder){
   $valid_mime = ['image/jpg','image/jpeg','image/png','image/webp'];
    $img_mime = $image['type'];

    if(!in_array($img_mime,$valid_mime)){
        return 'inv_img'; //invalid image or format
    }
    elseif(($image['size']/(1024*1024))>2){
        return 'inv_size'; //invalid size greater than 2mb
    }
    else{
        $ext = pathinfo($image['name'],PATHINFO_EXTENSION);
        $rname = 'IMG_'.random_int(11111,99999).".$ext";
        $img_path = UPLOAD_IMAGE_PATH.$folder.$rname;
        if(move_uploaded_file($image['tmp_name'],$img_path)){
            return $rname;
        }
        else{
            return 'upd_failed';
        }
    }
}

function deleteImage($image,$folder){
    if(unlink(UPLOAD_IMAGE_PATH.$folder.$image)){
        return true;
    }
    else{
        return  false;
    }
}


function uploadSVGImage($image, $folder){
   $valid_mime = ['image/svg+xml'];
    $img_mime = $image['type'];

    if(!in_array($img_mime,$valid_mime)){
        return 'inv_img'; //invalid image or format
    }
    elseif(($image['size']/(1024*1024))>1){
        return 'inv_size'; //invalid size greater than 1mb
    }
    else{
        $ext = pathinfo($image['name'],PATHINFO_EXTENSION);
        $rname = 'IMG_'.random_int(11111,99999).".$ext";
        $img_path = UPLOAD_IMAGE_PATH.$folder.$rname;
        if(move_uploaded_file($image['tmp_name'],$img_path)){
            return $rname;
        }
        else{
            return 'upd_failed';
        }
    }
}

function uploadUserImage($image)
{
 $valid_mime = ['image/jpg','image/jpeg','image/png','image/webp'];
    
    // Check if file was uploaded
    if (!isset($image['tmp_name']) || !is_uploaded_file($image['tmp_name'])) {
        $error_code = isset($image['error']) ? $image['error'] : 'unknown';
        error_log("uploadUserImage: No file uploaded or invalid upload. Error code: $error_code");
        return 'upd_failed';
    }
    
    $img_mime = $image['type'];
    error_log("uploadUserImage: Processing file - Name: " . ($image['name'] ?? 'unknown') . ", Type: $img_mime, Size: " . ($image['size'] ?? 0));

    if(!in_array($img_mime,$valid_mime)){
        error_log("uploadUserImage: Invalid MIME type: $img_mime");
        return 'inv_img'; //invalid image or format
    }
    else{
        $ext = pathinfo($image['name'],PATHINFO_EXTENSION);
        $rname = 'IMG_'.random_int(11111,99999).".jpeg";

        $img_path = UPLOAD_IMAGE_PATH.USERS_FOLDER.$rname;
        
        // Ensure directory exists
        $upload_dir = UPLOAD_IMAGE_PATH.USERS_FOLDER;
        error_log("uploadUserImage: Upload directory: $upload_dir");
        
        if (!file_exists($upload_dir)) {
            error_log("uploadUserImage: Directory does not exist, creating...");
            if (!mkdir($upload_dir, 0755, true)) {
                error_log("uploadUserImage: Failed to create directory: " . $upload_dir);
                return 'upd_failed';
            }
            error_log("uploadUserImage: Directory created successfully");
        }
        
        // Check if directory is writable
        if (!is_writable($upload_dir)) {
            error_log("uploadUserImage: Directory is not writable: $upload_dir");
            // Try to fix permissions
            if (!chmod($upload_dir, 0755)) {
                error_log("uploadUserImage: Failed to set directory permissions");
                return 'upd_failed';
            }
        }

        // Try to create image resource
        $img = false;
        if($ext == 'png' || $ext =='PNG'){
            $img = @imagecreatefrompng($image['tmp_name']);
        }
        else if($ext == 'webp' || $ext =='WEBP'){
            $img = @imagecreatefromwebp($image['tmp_name']);
        }
        else{
            $img = @imagecreatefromjpeg($image['tmp_name']);
        }
        
        // Check if image creation failed
        if($img === false){
            error_log("uploadUserImage: Failed to create image resource from: " . $image['tmp_name']);
            error_log("uploadUserImage: File extension: $ext");
            return 'upd_failed';
        }
        
        error_log("uploadUserImage: Image resource created, saving to: $img_path");

        // Save image
        $result = @imagejpeg($img, $img_path, 75);
        imagedestroy($img); // Free memory
        
        // Verify file was actually written
        if($result && file_exists($img_path) && filesize($img_path) > 0){
            error_log("uploadUserImage: Image saved successfully - File: $rname, Size: " . filesize($img_path) . " bytes");
            return $rname;
        }
        else{
            error_log("uploadUserImage: Failed to save image to: " . $img_path);
            error_log("uploadUserImage: imagejpeg result: " . var_export($result, true));
            error_log("uploadUserImage: File exists: " . (file_exists($img_path) ? 'yes' : 'no'));
            if (file_exists($img_path)) {
                error_log("uploadUserImage: File size: " . filesize($img_path));
            }
            error_log("uploadUserImage: Directory writable: " . (is_writable($upload_dir) ? 'yes' : 'no'));
            return 'upd_failed';
        }
    }
}

?>