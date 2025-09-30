<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5>Delete User Confirmation</h5>
        <div>
            <button class="btn btn-danger btn-sm" id="btnDeleteUser">
                <i class="fa fa-trash"></i> Delete
            </button>
            <button class="btn btn-secondary btn-sm" onclick="window.print()">
                <i class="fa fa-print"></i> Print
            </button>
            <button class="btn btn-light btn-sm" onclick="history.back()">
                Cancel
            </button>
        </div>
    </div>
    <div class="card-body">
        <div class="alert alert-warning">
            ⚠️ You are about to delete this user. This action is permanent.
        </div>

        <div class="row">
            <div class="col-md-3 text-center">
                <img id="userPhoto" src="./assets/images/admin-avatar.png" class="img-fluid rounded-circle mb-3"
                    alt="User Photo">
            </div>
            <div class="col-md-9">
                <h4 id="del_fullname">Loading...</h4>
                <p><strong>Role:</strong> <span id="del_role"></span></p>
                <p><strong>Email:</strong> <span id="del_email"></span></p>
                <p><strong>Phone:</strong> <span id="del_phone"></span></p>
                <p><strong>Branch:</strong> <span id="del_branch"></span></p>
                <hr>
                <div id="roleSpecificDetails">
                    <!-- Role-specific details go here -->
                </div>
            </div>
        </div>
    </div>
</div>