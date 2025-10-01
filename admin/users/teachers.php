<div class="card">
    <div class="card-header">
        <h5>Teachers</h5>
    </div>

    <div class="card-body">
        <!-- Tabs + Branch Filter -->
        <div class="d-flex flex-wrap align-items-center justify-content-between mb-3">

            <div class="d-flex align-items-center gap-2">
                <a href="#" class="btn btn-primary add-teacher-btn" data-page="teachers/add">
                    <i class="fa fa-user-plus mr-1"></i> Add Teacher
                </a>
            </div>

            <!-- Right: Filters -->
            <div class="d-flex flex-wrap align-items-center gap-2">
                <select id="branchFilter" class="form-control form-control-sm" style="min-width:160px;">
                    <option value="">All Branches</option>
                </select>
                <div class="input-group input-group-sm" style="width:200px;">
                    <div class="input-group-prepend">
                        <span class="input-group-text"><i class="fa fa-search"></i></span>
                    </div>
                    <input type="text" id="teacherSearch" class="form-control" placeholder="Search teachers...">
                </div>
            </div>
        </div>


        <!-- teachers Table -->
        <div class="table-responsive">
            <table class="table table-striped table-bordered" id="teachersTable">
                <thead>
                    <tr>
                        <th>Sr No.</th>
                        <th>Full Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Branch</th>
                        <th>Class</th>
                        <th>Section</th>
                        <th>subjects</th>
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
        <nav aria-label="teachers pagination">
            <ul class="pagination justify-content-end" id="teachersPagination">
                <!-- Pagination items will be injected via JS -->
            </ul>
        </nav>
    </div>
</div>