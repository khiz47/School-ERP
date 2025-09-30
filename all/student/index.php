<?php
require_once __DIR__ . '/../common/functions.php';
check_login('Student');
include '../common/header.php';
?>

<section class="hero">
    <h1 class="hero-title">Dashboard</h1>
    <p class="hero-subtitle">Welcome to APNA SCHOOL ERP</p>
    <div class="hero-actions">
        <button class="btn btn-primary">Add Student</button>
        <button class="btn btn-secondary">View Reports</button>
    </div>
</section>

<!-- Or with a card inside hero -->
<section class="hero">
    <div class="hero-card">
        <h2>Quick Stats</h2>
        <p>Some summary information here...</p>
    </div>
</section>

<?php include '../common/footer.php'; ?>