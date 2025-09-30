<?php
session_start();
session_destroy(); // ensure no session remains
include 'common/header.php';
?>

<section class="heroLogin">
    <div class="background">
        <div class="noise"></div>
        <div class="gradient-overlay"></div>
    </div>

    <div class="d-flex justify-content-center align-items-center" style="min-height:100vh; padding: 15px;">
        <div class="card shadow-lg p-4 text-center w-100" style="max-width: 500px; border-radius: 20px; 
                    background: rgba(255, 255, 255, 0.05); 
                    backdrop-filter: blur(12px); 
                    border: 1px solid rgba(255, 255, 255, 0.2);">

            <div class="mb-4">
                <i class="fa-solid fa-user-slash fa-3x text-danger"></i>
            </div>

            <h2 class="mb-3 text-white">Account Suspended</h2>
            <p class="text-muted mb-4">
                Your account has been temporarily suspended by the administrator.
                Please contact support if you believe this is a mistake.
            </p>

            <a href="login" class="btn btn-danger px-4 py-2 w-100"
                style="border-radius: 12px; max-width: 250px; margin: 0 auto;">
                <i class="fa-solid fa-right-to-bracket"></i> Back to Login
            </a>
        </div>
    </div>
</section>