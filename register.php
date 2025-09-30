<?php
header("Location: login");
session_start();
include 'common/header.php';
include 'includes/conn.php';
?>

<section class="centered-page">
    <div class="background">
    </div>

    <div class="loginContainer">
        <div class="loginbox">
            <div class="row justify-content-center">
                <div class="mx-auto">
                    <h2 class="text-center mb-4">Register</h2>
                    <form id="registerForm" method="POST" onsubmit="return false">
                        <input type="hidden" name="action" value="register_user">
                        <div class="form-group">
                            <label for="fullname">Full Name</label>
                            <input type="text" class="form-control" id="fullname" name="fullname" required
                                placeholder="Enter full name">
                        </div>
                        <div class="form-group">
                            <label for="email">Email Address</label>
                            <input type="email" class="form-control" id="email" name="email" required
                                placeholder="Enter email">
                        </div>
                        <div class="form-group">
                            <label for="username">Username</label>
                            <input type="text" class="form-control" id="username" name="username" required
                                placeholder="Choose a username">
                        </div>
                        <div class="form-group">
                            <label for="phone">Phone Number</label>
                            <input type="text" class="form-control" id="phone" name="phone" required
                                placeholder="Enter phone number" maxlength="10" pattern="[0-9]{10}">
                        </div>

                        <div class="form-group password-wrapper">
                            <label for="password">Password</label>
                            <div class="input-group">
                                <input type="password" class="form-control" id="password" name="password" required
                                    placeholder="Create password">
                                <span class="toggle-password" toggle="#password">
                                    <i class="fa-solid fa-eye"></i>
                                </span>
                            </div>
                        </div>
                        <div class="form-group password-wrapper">
                            <label for="confirm_password">Confirm Password</label>
                            <div class="input-group">
                                <input type="password" class="form-control" id="confirm_password"
                                    name="confirm_password" required placeholder="Repeat password">
                                <span class="toggle-password" toggle="#confirm_password">
                                    <i class="fa-solid   fa-eye"></i>
                                </span>
                            </div>
                        </div>
                        <input type="submit" class="btn btn-primary btn-block" value="Register">
                        <p class="mt-3 text-center">Already have an account? <a href="login">Login</a></p>
                    </form>
                    <div id="registerResponse" class="text-center mt-3"></div>
                </div>
            </div>
        </div>
    </div>
</section>
<?php include 'common/footer.php'; ?>
<script>
$(document).ready(function() {
    $('.toggle-password').on('click', function() {
        const inputSelector = $(this).attr('toggle');
        const input = $(inputSelector);
        const icon = $(this).find('i');

        if (input.attr('type') === 'password') {
            input.attr('type', 'text');
            icon.removeClass('fa-eye').addClass('fa-eye-slash');
        } else {
            input.attr('type', 'password');
            icon.removeClass('fa-eye-slash').addClass('fa-eye');
        }
    });
    $('#registerForm').on('submit', function(e) {
        e.preventDefault();
        let formData = {
            fullname: $('#fullname').val(),
            email: $('#email').val(),
            username: $('#username').val(),
            phone: $('#phone').val(),
            password: $('#password').val(),
            confirm_password: $('#confirm_password').val(),
            action: 'register_user'
        };

        $.ajax({
            url: 'common/functions.php',
            type: 'POST',
            data: formData,
            dataType: 'json',
            success: function(res) {
                let alertBox = '';
                if (res.status === 'success') {
                    alertBox =
                        '<div class="alert alert-success show"><i class="fa-solid fa-check-circle"></i> ' +
                        res.message +
                        '</div>';
                    $('#registerForm')[0].reset();
                    setTimeout(function() {
                        window.location.href = 'student/';

                    }, 1500);
                } else {
                    alertBox =
                        '<div class="alert alert-error show"><i class="fa-solid fa-exclamation-circle"></i> ' +
                        res.message +
                        '</div>';
                }
                $('#registerResponse').html(alertBox);
            },
            error: function(xhr, status, error) {
                console.log("XHR:", xhr.responseText);
                console.log("Status:", status);
                console.log("Error:", error);
                $('#registerResponse').html(
                    '<span style="color:red;">Something went wrong. Please try again.</span>'
                );
            }
        });
    });
});
</script>