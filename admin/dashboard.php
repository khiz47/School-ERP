<div class="container-fluid dashboard-container">
    <!-- 📊 Stats Row -->
    <div class="row mb-4">
        <div class="col-md-3 col-sm-6 mb-3">
            <div class="dash-card shadow-sm">
                <h6>Total Students</h6>
                <h4>1200</h4>
            </div>
        </div>
        <div class="col-md-3 col-sm-6 mb-3">
            <div class="dash-card shadow-sm">
                <h6>Total Teachers</h6>
                <h4>85</h4>
            </div>
        </div>
        <div class="col-md-3 col-sm-6 mb-3">
            <div class="dash-card shadow-sm">
                <h6>Parents Linked</h6>
                <h4>1100</h4>
            </div>
        </div>
        <div class="col-md-3 col-sm-6 mb-3">
            <div class="dash-card shadow-sm">
                <h6>Total Accounts</h6>
                <h4>280</h4>
            </div>
        </div>
        <div class="col-md-3 col-sm-6 mb-3">
            <div class="dash-card shadow-sm">
                <h6>Classes</h6>
                <h4>10</h4>
            </div>
        </div>
        <div class="col-md-3 col-sm-6 mb-3">
            <div class="dash-card shadow-sm">
                <h6>Sections</h6>
                <h4>20</h4>
            </div>
        </div>
        <div class="col-md-3 col-sm-6 mb-3">
            <div class="dash-card shadow-sm">
                <h6>Subjects</h6>
                <h4>30</h4>
            </div>
        </div>
        <div class="col-md-3 col-sm-6 mb-3">
            <div class="dash-card shadow-sm">
                <h6>Branches</h6>
                <h4>4</h4>
            </div>
        </div>
    </div>

    <!-- 🚀 Quick Actions -->
    <div class="quick-actions mb-4">
        <a href="#" class="btn btn-primary action-btn side-item" data-page="users/add">
            <i class="fa fa-user-plus mr-1"></i> Add User
        </a>
        <a href="#" class="btn btn-outline-primary action-btn side-item" data-page="users/manage">
            <i class="fa fa-users mr-1"></i> Manage Users
        </a>
        <a href="#" class="btn btn-outline-primary action-btn side-item" data-page="classes/add">
            <i class="fa fa-chalkboard mr-1"></i> Add Class/Section
        </a>
        <a href="#" class="btn btn-outline-primary action-btn side-item" data-page="subjects/add">
            <i class="fa fa-book mr-1"></i> Add Subject
        </a>
    </div>


    <!-- 🆕 Recent Admissions -->
    <div class="card shadow-sm mb-4">
        <div class="card-header">
            <h4 class="card-title">Recent Admissions</h4>
            <!-- <div id="#studentsTable_filter"></div> -->
        </div>
        <div class="card-body p-0">
            <div class="table-responsive fixed-header-table">
                <table id="studentsTable" class="table table-hover mb-0">
                    <thead class="thead-light">
                        <tr>
                            <th>Name</th>
                            <th>Class</th>
                            <th>Section</th>
                            <th>Admission Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>Ali Khan</td>
                            <td>10</td>
                            <td>A</td>
                            <td>2025-09-01</td>
                        </tr>
                        <tr>
                            <td>Sarah Ahmed</td>
                            <td>9</td>
                            <td>B</td>
                            <td>2025-09-02</td>
                        </tr>
                        <tr>
                            <td>Mohammed Yusuf</td>
                            <td>8</td>
                            <td>A</td>
                            <td>2025-09-03</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>