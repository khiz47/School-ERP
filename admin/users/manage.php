<div class="card">
    <div class="card-header">
        <h5>Manage Users</h5>
    </div>

    <div class="card-body">
        <!-- Tabs + Branch Filter -->
        <div class="d-flex flex-wrap align-items-center justify-content-between mb-3 gap-2">
            <div class="user-tabs d-flex flex-wrap align-items-center gap-2">
                <button class="btn btn-outline-primary tab-btn active" data-role="">All</button>
                <button class="btn btn-outline-primary tab-btn" data-role="1">Admin</button>
                <button class="btn btn-outline-primary tab-btn" data-role="2">Teacher</button>
                <button class="btn btn-outline-primary tab-btn" data-role="3">Student</button>
                <button class="btn btn-outline-primary tab-btn" data-role="4">Parent</button>
                <button class="btn btn-outline-primary tab-btn" data-role="5">Accountant</button>
            </div>

            <div class="filters d-flex flex-wrap align-items-center" style="gap:5px;">
                <!-- Branch Dropdown -->
                <select id="branchFilter" class="form-control">
                    <option value="">All Branches</option>
                </select>

                <!-- Search Box -->
                <input type="text" id="userSearch" class="form-control" placeholder="Search users...">
            </div>
        </div>

        <!-- Users Table -->
        <div class="table-responsive">
            <table class="table table-striped table-bordered" id="usersTable">
                <thead>
                    <tr>
                        <th>Sr No.</th>
                        <th>Full Name</th>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Branch</th>
                        <th>Class</th>
                        <th>Section</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <tr id="tableLoader">
                        <td colspan="10" class="text-center text-muted">Loading...</td>
                    </tr>
                    <!-- Rows will be populated via AJAX -->
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <nav aria-label="Users pagination">
            <ul class="pagination justify-content-end" id="usersPagination">
                <!-- Pagination items will be injected via JS -->
            </ul>
        </nav>
    </div>
</div>