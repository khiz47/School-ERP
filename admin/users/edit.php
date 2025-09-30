<?php
// users/edit.php
// this file outputs only the form fragment (no <html> etc.) — same pattern you use
?>
<div class="card shadow-sm">
    <div class="card-header text-white">
        <h5 class="mb-0"><i class="fa fa-user-edit mr-2"></i> Edit</h5>
    </div>

    <div class="card-body">
        <form id="editUserForm" enctype="multipart/form-data" autocomplete="off">
            <!-- Hidden -->
            <input type="hidden" name="user_id" id="form_user_id" value="">

            <!-- Common Fields -->
            <fieldset class="border p-3 mb-3 rounded">
                <legend class="w-auto px-2 text-primary small">Common Information</legend>
                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label for="fullname">Full Name</label>
                        <input type="text" name="fullname" id="fullname" class="form-control" required>
                        <div class="invalid-feedback fullname-error"></div>
                    </div>
                    <div class="form-group col-md-6">
                        <label for="username">Username</label>
                        <input type="text" name="username" id="username" class="form-control" required>
                        <div class="invalid-feedback username-error"></div>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label for="email">Email</label>
                        <input type="email" name="email" id="email" class="form-control" required>
                        <div class="invalid-feedback email-error"></div>
                    </div>
                    <div class="form-group col-md-6">
                        <label for="phone">Phone</label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text">+91</span>
                            </div>
                            <input type="text" name="phone" id="phone" class="form-control" pattern="\d{10}">
                        </div>
                        <div class="invalid-feedback phone-error"></div>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label for="password">Password</label>
                        <div class="input-group">
                            <input type="password" name="password" id="password" class="form-control"
                                style="border-radius: var(--radius)" placeholder="Leave blank to keep unchanged">
                            <span class="toggle-password" toggle="#password">
                                <i class="fa-solid fa-eye"></i>
                            </span>
                        </div>
                        <div class="invalid-feedback password-error"></div>
                    </div>

                    <div class="form-group col-md-3">
                        <label for="branch_id">Branch</label>
                        <select name="branch_id" id="branch_id" class="form-control" required>
                            <option value="">Select Branch</option>
                        </select>
                        <div class="invalid-feedback branch-error"></div>
                    </div>

                    <div class="form-group col-md-3">
                        <label for="roleSelect">Role</label>
                        <select name="role_id" id="roleSelect" class="form-control" readonly>
                            <option value="">Select Role</option>
                        </select>
                        <div class="invalid-feedback role-error"></div>
                    </div>
                </div>
            </fieldset>

            <!-- Student Fields (unique IDs) -->
            <fieldset id="studentFields" class="role-fields border p-3 mb-3 rounded d-none">
                <legend class="w-auto px-2 text-primary small">Student Information</legend>

                <div class="form-row">
                    <div class="form-group col-md-4">
                        <label for="student_class_id">Class</label>
                        <select name="class_id" id="student_class_id" class="form-control">
                            <option value="">Select Class</option>
                        </select>
                    </div>

                    <div class="form-group col-md-4">
                        <label for="student_section_id">Section</label>
                        <select name="section_id" id="student_section_id" class="form-control">
                            <option value="">Select Section</option>
                        </select>
                    </div>

                    <div class="form-group col-md-4">
                        <label for="dob">Date of Birth</label>
                        <input type="date" name="dob" id="dob" class="form-control">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group col-md-4">
                        <label for="gender">Gender</label>
                        <select name="gender" id="gender" class="form-control">
                            <option value="">Select</option>
                            <option value="male">Male</option>
                            <option value="female">Female</option>
                            <option value="other">Other</option>
                        </select>
                    </div>

                    <div class="form-group col-md-4">
                        <label for="admission_date">Admission Date</label>
                        <input type="date" name="admission_date" id="admission_date" class="form-control">
                    </div>

                    <div class="form-group col-md-4">
                        <label for="student_parent_id">Parent</label>
                        <select name="parent_id" id="student_parent_id" class="form-control">
                            <option value="">Select Parent</option>
                        </select>
                    </div>
                </div>

                <hr>

                <div class="form-row">
                    <div class="form-group col-md-4">
                        <label for="photo">Photo</label>
                        <input type="file" name="photo" id="photo" class="form-control-file">
                        <div class="position-relative d-inline-block mt-2">
                            <img id="photoPreview" src="./assets/images/admin-avatar.png" alt="Photo Preview"
                                class="img-thumbnail" style="max-width: 120px;">
                            <button type="button" id="removePhoto"
                                class="btn btn-sm btn-danger position-absolute d-none"
                                style="top: -10px; right: -10px; border-radius: 50%;">
                                <i class="fa fa-times"></i>
                            </button>
                        </div>
                        <div class="invalid-feedback photo-error"></div>
                    </div>

                    <div class="form-group col-md-4">
                        <label for="aadhar_no">Aadhar / ID No</label>
                        <input type="text" name="aadhar_no" id="aadhar_no" class="form-control">
                        <div class="invalid-feedback aadhar_no-error"></div>
                    </div>

                    <div class="form-group col-md-4">
                        <label for="blood_group">Blood Group</label>
                        <input type="text" name="blood_group" id="blood_group" class="form-control"
                            placeholder="O+, A- etc">
                        <div class="invalid-feedback blood_group-error"></div>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label for="student_address">Address</label>
                        <textarea name="student_address" id="student_address" class="form-control"></textarea>
                        <div class="invalid-feedback address-error"></div>
                    </div>

                    <div class="form-group col-md-3">
                        <label for="transport_required">Transport Required?</label>
                        <select name="transport_required" id="transport_required" class="form-control">
                            <option value="no" selected>No</option>
                            <option value="yes">Yes</option>
                        </select>
                    </div>

                    <div class="form-group col-md-3">
                        <label for="hostel_required">Hostel Required?</label>
                        <select name="hostel_required" id="hostel_required" class="form-control">
                            <option value="no" selected>No</option>
                            <option value="yes">Yes</option>
                        </select>
                    </div>
                </div>
            </fieldset>

            <!-- Parent Fields (unique IDs) -->
            <fieldset id="parentFields" class="role-fields border p-3 mb-3 rounded d-none">
                <legend class="w-auto px-2 text-primary small">Parent Information</legend>

                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label for="father_name">Father Name</label>
                        <input type="text" name="father_name" id="father_name" class="form-control">
                        <div class="invalid-feedback father_name-error"></div>
                    </div>

                    <div class="form-group col-md-6">
                        <label for="mother_name">Mother Name</label>
                        <input type="text" name="mother_name" id="mother_name" class="form-control">
                        <div class="invalid-feedback mother_name-error"></div>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label for="relation">Relation</label>
                        <select name="relation" id="relation" class="form-control">
                            <option value="father">Father</option>
                            <option value="mother">Mother</option>
                            <option value="guardian">Guardian</option>
                        </select>
                    </div>

                    <div class="form-group col-md-6">
                        <label for="occupation">Occupation</label>
                        <input type="text" name="occupation" id="occupation" class="form-control">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label for="parent_salary">Salary</label>
                        <input type="number" step="0.01" name="parent_salary" id="parent_salary" class="form-control">
                        <div class="invalid-feedback parent_salary-error"></div>
                    </div>

                    <div class="form-group col-md-6">
                        <label for="alt_phone">Alternate Phone</label>
                        <input type="text" name="alt_phone" id="alt_phone" class="form-control">
                        <div class="invalid-feedback alt_phone-error"></div>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label for="alt_email">Alternate Email</label>
                        <input type="email" name="alt_email" id="alt_email" class="form-control">
                        <div class="invalid-feedback alt_email-error"></div>
                    </div>

                    <div class="form-group col-md-6">
                        <label for="parent_address">Address</label>
                        <textarea name="parent_address" id="parent_address" class="form-control"></textarea>
                    </div>
                </div>
            </fieldset>

            <!-- Teacher Fields -->
            <fieldset id="teacherFieldsEdit" class="role-fields border p-3 mb-3 rounded d-none">
                <legend class="w-auto px-2 text-primary small">Teacher Information</legend>

                <div id="teacherAssignmentsContainerEdit">
                    <!-- <div class="teacher-mapping-row form-row align-items-end mb-2">
                        <div class="form-group col-md-4">
                            <label>Class</label>
                            <select name="teacher_class_edit[]" class="form-control teacher-class-edit">
                                <option value="">Select Class</option>
                            </select>
                        </div>
                        <div class="form-group col-md-4">
                            <label>Section</label>
                            <select name="teacher_section_edit[]" class="form-control teacher-section-edit">
                                <option value="">Select Section</option>
                            </select>
                        </div>
                        <div class="form-group col-md-4">
                            <label>Subject</label>
                            <select name="teacher_subject_edit[]" class="form-control teacher-subject-edit">
                                <option value="">Select Subject</option>
                            </select>
                        </div>
                        <div class="form-group col-md-3 d-flex align-items-end">
                            <button type="button" class="btn btn-danger btn-sm removeTeacherMapping">Remove</button>
                        </div>
                    </div> -->
                </div>

                <button type="button" id="addTeacherMappingEdit" class="btn btn-sm btn-secondary mb-3">+ Add Another
                    Mapping</button>
            </fieldset>


            <!-- Accountant Fields -->
            <fieldset id="accountantFields" class="role-fields border p-3 mb-3 rounded d-none">
                <legend class="w-auto px-2 text-primary small">Accountant Information</legend>
                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label for="salary">Salary</label>
                        <input type="number" step="0.01" name="salary" id="salary" class="form-control" min="0"
                            placeholder="0.00">
                        <div class="invalid-feedback salary-error"></div>
                    </div>

                    <div class="form-group col-md-6">
                        <label for="bank_account">Bank Account</label>
                        <input type="text" name="bank_account" id="bank_account" class="form-control"
                            placeholder="1234 1234 1234">
                        <div class="invalid-feedback bank_account-error"></div>
                    </div>
                </div>
            </fieldset>

            <!-- Action Buttons -->
            <div class="text-right d-flex justify-content-between align-items-center w-100" style="gap: 10px;">
                <button type="reset" class="btn btn-secondary w-50"><i class="fa fa-undo mr-1"></i>Reset</button>
                <button type="submit" class="btn btn-primary w-50"><i class="fa fa-save mr-1"></i> Save</button>
            </div>
        </form>
    </div>
</div>

<script>
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
</script>