<?php
// Base settings
define("BASE_URL", "https://localhost/");
define("SITE_NAME", "APNA School ERP");

// DB settings (instead of scattering in multiple places)
define("DB_HOST", "localhost");
define("DB_USER", "root");
define("DB_PASS", "");
define("DB_NAME", "eduqure");

// For file uploads
define("UPLOAD_PATH", __DIR__ . "/../uploads/");
define("STUDENT_UPLOAD_PATH", UPLOAD_PATH . "students/");

// Timezone
date_default_timezone_set("Asia/Kolkata");
