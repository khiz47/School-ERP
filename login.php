<?php
session_start();
include 'common/header.php';
include 'includes/conn.php';

?>

<section class="centered-page">
    <div class="background"></div>

    <div class="loginContainer">
        <div class="loginbox">
            <div class="row justify-content-center">
                <div class="mx-auto">
                    <h2 class="text-center mb-4">Login</h2>
                    <form id="loginForm" method="POST" onsubmit="return false">
                        <input type="hidden" name="action" value="login_user">
                        <div class="form-group">
                            <label for="email">Email address</label>
                            <input type="email" class="form-control" id="email" name="email" required
                                placeholder="Enter email">
                        </div>
                        <div class="form-group password-wrapper">
                            <label for="password">Password</label>
                            <div class="input-group">
                                <input type="password" class="form-control" id="password" name="password" required
                                    placeholder="Enter password">
                                <span class="toggle-password" toggle="#password">
                                    <i class="fa-solid fa-eye"></i>
                                </span>
                            </div>
                        </div>
                        <input type="submit" class="btn btn-primary btn-block" value="Login">
                        <p class="mt-3 text-center" id="resetPassword" style="display:none;">Forgot your password? <a
                                href="reset-password">Reset</a></p>
                        <!-- <p class=" mt-3 text-center">Don't have an account? <a href="register">Register</a></p> -->
                    </form>
                    <div id="loginResponse" class="text-center mt-3"></div>
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

        $('#loginForm').on('submit', function(e) {
            e.preventDefault();
            let formData = {
                email: $('#email').val(),
                password: $('#password').val(),
                action: 'login_user'
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
                        $('#loginForm')[0].reset(); // Reset the form
                        $('#resetPassword').hide();
                        setTimeout(function() {
                            window.location.href = res.data.redirect;;
                        }, 1500);
                    } else {
                        alertBox =
                            '<div class="alert alert-error show"><i class="fa-solid fa-exclamation-circle"></i> ' +
                            res.message +
                            '</div>';
                        if (res.data && res.data.show_reset) {
                            $('#resetPassword').show();
                        }
                    }
                    $('#loginResponse').html(alertBox);
                },
                error: function(xhr, status, error) {
                    console.log("XHR:", xhr.responseText);
                    console.log("Status:", status);
                    console.log("Error:", error);
                    $('#loginResponse').html(
                        '<span style="color:red;">Something went wrong. Please try again.</span>'
                    );
                }
            });
        });
    });
</script>