<?php
require_once __DIR__ . '/../common/functions.php';
check_login(['Admin']);
include 'common/header.php';
?>

<section class="hero">
    <div class="background"></div>
    <div class="container-fluid">
        <div class="row" style="min-height: 100vh;">

            <div id="sidebar" class="sidebar text-white p-3">

                <input type="text" class="form-control mb-3" placeholder="Search..." id="navSearch">
                <div id="searchResults"></div>
                <ul class="list-unstyled">

                    <!-- Dashboard -->
                    <li>
                        <div class="side-item p-2 d-flex align-items-center justify-content-between"
                            data-page="dashboard">
                            <div class="d-flex align-items-center">
                                <i class="fa-solid fa-chart-line mr-2"></i>
                                <span>Dashboard</span>
                            </div>
                        </div>
                    </li>

                    <!-- Users -->
                    <li>
                        <div class="side-item p-2 d-flex align-items-center justify-content-between"
                            data-toggle="collapse" data-target="#usersMenu" aria-expanded="false">
                            <div class="d-flex align-items-center">
                                <i class="fa-solid fa-users mr-2"></i>
                                <span>Users</span>
                            </div>
                            <i class="fa-solid fa-chevron-down"></i>
                        </div>
                        <ul class="collapse list-unstyled pl-3" id="usersMenu" data-parent="#sidebar">
                            <li><a href="#" class="side-item" data-page="admins">Admins</a></li>
                            <li><a href="#" class="side-item" data-page="teachers">Teachers</a></li>
                            <li><a href="#" class="side-item" data-page="students">Students</a></li>
                            <li><a href="#" class="side-item" data-page="parents">Parents</a></li>
                        </ul>
                    </li>

                    <!-- Academics -->
                    <li>
                        <div class="side-item p-2 d-flex align-items-center justify-content-between"
                            data-toggle="collapse" data-target="#academicsMenu" aria-expanded="false">
                            <div class="d-flex align-items-center">
                                <i class="fa-solid fa-book mr-2"></i>
                                <span>Academics</span>
                            </div>
                            <i class="fa-solid fa-chevron-down"></i>
                        </div>
                        <ul class="collapse list-unstyled pl-3" id="academicsMenu" data-parent="#sidebar">
                            <li><a href="#" class="side-item" data-page="classes">Classes</a></li>
                            <li><a href="#" class="side-item" data-page="sections">Sections</a></li>
                            <li><a href="#" class="side-item" data-page="subjects">Subjects</a></li>
                        </ul>
                    </li>

                    <!-- Fees -->
                    <li>
                        <div class="side-item p-2 d-flex align-items-center justify-content-between" data-page="fees">
                            <div class="d-flex align-items-center">
                                <i class="fa-solid fa-coins mr-2"></i>
                                <span>Fees</span>
                            </div>
                        </div>
                    </li>
                </ul>
            </div>

            <!-- 🧭 Topbar -->
            <div class="col p-0 d-flex flex-column">
                <div class="topbar d-flex justify-content-between align-items-center px-3 py-2">
                    <div class="d-flex align-items-center">
                        <!-- Sidebar -->
                        <button id="sidebarToggle" class="sidebar-toggle-btn d-md-none mr-3">
                            <span class="line line1"></span>
                            <span class="line line2"></span>
                            <span class="line line3"></span>
                        </button>
                        <h5 id="pageTitle" class="m-0">Apna School</h5>
                    </div>
                    <div class="topbar-actions d-flex align-items-center gap-3">
                        <i class="fa-solid fa-bell"></i>
                        <!-- Profile Dropdown -->
                        <div class="dropdown">
                            <img src="assets/images/noFilter.png" alt="Admin" class="rounded-circle dropdown-toggle"
                                width="35" id="profileDropdown" data-toggle="dropdown" aria-haspopup="true"
                                aria-expanded="false" style="cursor:pointer;">
                            <div class="dropdown-menu dropdown-menu-right" aria-labelledby="profileDropdown">
                                <span class="dropdown-item-text"><?php echo $_SESSION['email'] ?? ''; ?></span>
                                <div class="dropdown-divider"></div>
                                <a class="dropdown-item" href="profile.php">Profile</a>
                                <a class="dropdown-item" href="logout">Logout</a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 💬 Main dashboard area -->
                <div class="p-0 dash-area">

                    <!--Default show dashboard.php here when user logged in -->
                </div>
            </div>


        </div>
    </div>
</section>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<?php include 'common/footer.php'; ?>