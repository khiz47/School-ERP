<?php
// Role IDs — if not defined, define them
if (!defined('TEACHER_ROLE_ID')) {
    define('TEACHER_ROLE_ID', 2);
    define('STUDENT_ROLE_ID', 3);
    define('PARENT_ROLE_ID', 4);
    define('ACCOUNTANT_ROLE_ID', 5);
}

session_start();

// connection DB
include '../../includes/conn.php';
include '../../includes/helpers.php';


if (!isset($_SESSION['login_attempts'])) {
    $_SESSION['login_attempts'] = 0;
}

// if ($_SERVER['REQUEST_METHOD'] === 'POST') {
//     file_put_contents(__DIR__ . "/debug.log", print_r($_POST, true), FILE_APPEND);
// }

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    switch ($_POST['action']) {
        case 'add_user':
            addUser($conn);
            break;

        case 'get_branches':
            getBranches($conn);
            break;

        case 'get_roles':
            getRoles($conn);
            break;

        case 'get_classes':
            getClasses($conn, $_POST['branch_id'] ?? null);
            break;

        case 'get_sections':
            getSections($conn, $_POST['class_id'] ?? null);
            break;

        case 'get_subjects':
            getSubjects($conn, $_POST['class_id'] ?? null);
            break;

        case 'get_parents':
            getParents($conn);
            break;

        case 'get_parent_by_id':
            getParentById($conn, $_POST['parent_id'] ?? null);
            break;

        // case 'search_parents':
        //     searchParents($conn);
        //     break;

        case 'get_users':
            getUsers($conn);
            break;

        case 'get_user':
            getUser($conn, $_POST['user_id'] ?? 0);
            break;

        case 'get_role_constants':
            $roles = [];
            $res = $conn->query("SELECT role_id, role_name FROM roles");
            while ($row = $res->fetch_assoc()) {
                $roles[strtoupper($row['role_name'])] = (int)$row['role_id'];
            }
            sendResponse('success', $roles, 'Roles loaded');
            break;

        case 'update_user':
            update_user($conn);
            break;

        case 'delete_user':
            delete_user($conn);
            break;

        case 'get_recent_admissions':
            getRecentAdmissions($conn);
            break;
        default:
            sendResponse('error', null, 'Invalid action.');
    }
}


/** ========== POST FUNCTIONS ========== **/
function addUser($conn)
{
    try {
        // 1. Collect Common Inputs
        $fullname   = trim($_POST['fullname']);
        $username   = trim($_POST['username']);
        $email      = trim($_POST['email']);
        $phone      = trim($_POST['phone']);
        $password   = password_hash($_POST['password'], PASSWORD_BCRYPT);
        $branch_id  = intval($_POST['branch_id']);
        $role_id    = intval($_POST['role_id']);

        if (empty($fullname) || empty($username) || empty($_POST['password']) || !$role_id) {
            sendResponse('error', null, 'Missing required fields.');
        }

        // 1. Common validations
        if (!preg_match("/^[A-Za-z\s]+$/", $fullname)) {
            sendResponse('error', null, 'Full name must contain only letters.');
        }

        if (!preg_match("/^[A-Za-z0-9]+$/", $username)) {
            sendResponse('error', null, 'Username must contain only letters and numbers.');
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            sendResponse('error', null, 'Invalid email address.');
        }

        if (!preg_match("/^\d{10}$/", $phone)) {
            sendResponse('error', null, 'Phone number must be exactly 10 digits.');
        }

        if (!preg_match("/^(?=.*[A-Za-z])(?=.*\d)(?=.*[@$!%*#?&]).{8,}$/", $_POST['password'])) {
            sendResponse('error', null, 'Password must be at least 8 characters, include a letter, number, and special character.');
        }
        // 2. Insert into Users
        $stmt = $conn->prepare("INSERT INTO users (fullname, username, email, phone, password, role_id, branch_id) 
                                VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssssi", $fullname, $username, $email, $phone, $password, $role_id, $branch_id);
        if (!$stmt->execute()) {
            sendResponse('error', null, "User insert failed: " . $stmt->error);
        }
        $user_id = $stmt->insert_id;
        $stmt->close();

        // 3. Role Specific Inserts
        switch ($role_id) {
            case 1: // Admin
                // Optional: also put into staff
                $join_date = date("Y-m-d");
                $stmt = $conn->prepare("INSERT INTO staff (user_id, designation, join_date) VALUES (?, 'admin', ?)");
                $stmt->bind_param("is", $user_id, $join_date);
                $stmt->execute();
                $stmt->close();
                break;

            case 2: // Teacher
                $qualification  = $_POST['qualification'] ?? null;
                $join_date      = date("Y-m-d");

                // staff
                $stmt = $conn->prepare("INSERT INTO staff (user_id, designation, qualification, join_date) 
                                        VALUES (?, 'teacher', ?, ?)");
                $stmt->bind_param("iss", $user_id, $qualification, $join_date);
                if (!$stmt->execute()) {
                    sendResponse('error', null, "Staff insert failed: " . $stmt->error);
                }
                $staff_id = $stmt->insert_id;
                $stmt->close();

                // teacher_details
                // $subject_specialization = $_POST['subject_specialization'] ?? null;
                // $stmt = $conn->prepare("INSERT INTO teacher_details (staff_id, subject_specialization) VALUES (?, ?)");
                // $stmt->bind_param("is", $staff_id, $subject_specialization);
                // $stmt->execute();
                // $stmt->close();

                // // teacher_classes
                // if (!empty($_POST['assigned_classes'])) {
                //     foreach ($_POST['assigned_classes'] as $class_id) {
                //         $class_id = intval($class_id);
                //         $section_id = $_POST['section_id'] ?? 0; // you may extend UI for per-class section
                //         $stmt = $conn->prepare("INSERT INTO teacher_classes (staff_id, class_id, section_id) VALUES (?, ?, ?)");
                //         $stmt->bind_param("iii", $staff_id, $class_id, $section_id);
                //         $stmt->execute();
                //         $stmt->close();
                //     }
                // }
                // // teacher_subjects
                // if (!empty($_POST['assigned_subjects'])) {
                //     foreach ($_POST['assigned_subjects'] as $subject_id) {
                //         $subject_id = intval($subject_id);
                //         $stmt = $conn->prepare("INSERT INTO teacher_subjects (staff_id, subject_id) VALUES (?, ?)");
                //         $stmt->bind_param("ii", $staff_id, $subject_id);
                //         $stmt->execute();
                //         $stmt->close();
                //     }
                // }
                // Teacher Assignments (Class + Section + Subject)
                if (!empty($_POST['teacher_class'])) {
                    $count = count($_POST['teacher_class']);
                    for ($i = 0; $i < $count; $i++) {
                        $class_id   = intval($_POST['teacher_class'][$i]);
                        $section_id = intval($_POST['teacher_section'][$i]);
                        $subject_id = intval($_POST['teacher_subject'][$i]);

                        if ($class_id && $section_id && $subject_id) {
                            $stmt = $conn->prepare("INSERT INTO teacher_assignments (staff_id, class_id, section_id, subject_id) VALUES (?, ?, ?, ?)");
                            $stmt->bind_param("iiii", $staff_id, $class_id, $section_id, $subject_id);
                            $stmt->execute();
                            $stmt->close();
                        }
                    }
                }

                break;

            case 3: // Student
                $class_id       = intval($_POST['class_id']);
                $section_id     = intval($_POST['section_id']);
                $dob            = $_POST['dob'] ?: null;
                $gender         = $_POST['gender'] ?: null;
                $admission_date = $_POST['admission_date'] ?: null;
                $parent_id      = !empty($_POST['parent_id']) ? intval($_POST['parent_id']) : null;

                // Extra fields
                $aadhar_no      = $_POST['aadhar_no'] ?? null;
                $blood_group    = $_POST['blood_group'] ?? null;
                $address        = $_POST['student_address'] ?? null;
                $transport_req  = $_POST['transport_required'] ?? 'no';
                $hostel_req     = $_POST['hostel_required'] ?? 'no';

                // === Student Validations ===
                if (!$class_id) {
                    sendResponse('error', null, "Class is required.");
                }
                if (!$section_id) {
                    sendResponse('error', null, "Section is required.");
                }
                if (!$dob || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $dob) || strtotime($dob) > time()) {
                    sendResponse('error', null, "Invalid Date of Birth.");
                }
                if (!$gender || !in_array($gender, ['male', 'female', 'other'])) {
                    sendResponse('error', null, "Gender is required.");
                }
                if (!$admission_date || strtotime($admission_date) > time()) {
                    sendResponse('error', null, "Admission Date cannot be in future.");
                }
                if (!empty($aadhar_no) && !preg_match('/^[0-9]{12}$/', $aadhar_no)) {
                    sendResponse('error', null, "Aadhar number must be 12 digits.");
                }
                // check aadhar uniqueness
                if (!empty($aadhar_no)) {
                    $chk = $conn->prepare("SELECT student_id FROM students WHERE aadhar_no=? LIMIT 1");
                    $chk->bind_param("s", $aadhar_no);
                    $chk->execute();
                    $chk->store_result();
                    if ($chk->num_rows > 0) {
                        sendResponse('error', null, "Aadhar number already exists.");
                    }
                    $chk->close();
                }
                if (!empty($blood_group) && !preg_match('/^(A|B|AB|O)[+-]$/', $blood_group)) {
                    sendResponse('error', null, "Invalid blood group format.");
                }
                if (!in_array($transport_req, ['yes', 'no'])) {
                    $transport_req = 'no';
                }
                if (!in_array($hostel_req, ['yes', 'no'])) {
                    $hostel_req = 'no';
                }
                // Photo check and Handle photo upload (if provided)
                $photo_path = null;
                if (!empty($_FILES['photo']['name'])) {
                    $allowed_ext = ['jpg', 'jpeg', 'png'];
                    $ext = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));
                    if (!in_array($ext, $allowed_ext)) {
                        sendResponse('error', null, "Only JPG, JPEG, PNG photos allowed.");
                    }
                    if ($_FILES['photo']['size'] > 2 * 1024 * 1024) {
                        sendResponse('error', null, "Photo must be less than 2MB.");
                    }
                    $upload_dir = "../../uploads/students/";
                    if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
                    $photo_path = $upload_dir . "stu_" . $user_id . "." . $ext;
                    move_uploaded_file($_FILES['photo']['tmp_name'], $photo_path);
                }

                // === Logical Validations ===
                $age = (int)((time() - strtotime($dob)) / (365 * 24 * 60 * 60));
                if ($age < 3 || $age > 25) {
                    sendResponse('error', null, "Invalid age. Must be between 3 and 25 years.");
                }
                if (strtotime($admission_date) < strtotime($dob)) {
                    sendResponse('error', null, "Admission date cannot be before Date of Birth.");
                }

                // Generate admission number (YEAR-UID)
                $admission_no = date("Y") . "-" . str_pad($user_id, 4, "0", STR_PAD_LEFT);

                $stmt = $conn->prepare("INSERT INTO students 
        (user_id, admission_no, class_id, section_id, dob, gender, admission_date, parent_id,
         photo, aadhar_no, blood_group, address, transport_required, hostel_required) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param(
                    "isiisssissssss",
                    $user_id,
                    $admission_no,
                    $class_id,
                    $section_id,
                    $dob,
                    $gender,
                    $admission_date,
                    $parent_id,
                    $photo_path,
                    $aadhar_no,
                    $blood_group,
                    $address,
                    $transport_req,
                    $hostel_req
                );
                if (!$stmt->execute()) {
                    sendResponse('error', null, "Student insert failed: " . $stmt->error);
                }
                $stmt->close();
                break;

            case 4: // Parent
                $father_name   = $_POST['father_name'] ?? null;
                $mother_name   = $_POST['mother_name'] ?? null;
                $relation      = $_POST['relation'] ?? 'father';
                $occupation    = $_POST['occupation'] ?? null;
                $parent_salary = $_POST['parent_salary'] ?? 0.00;
                $alt_phone     = $_POST['alt_phone'] ?? null;
                $alt_email     = $_POST['alt_email'] ?? null;
                $address       = $_POST['parent_address'] ?? null;

                // === Validations ===
                if ($father_name && !preg_match("/^[A-Za-z\s]+$/", $father_name)) {
                    sendResponse('error', null, 'Father name must contain only letters.');
                }
                if ($mother_name && !preg_match("/^[A-Za-z\s]+$/", $mother_name)) {
                    sendResponse('error', null, 'Mother name must contain only letters.');
                }
                if (!in_array($relation, ['father', 'mother', 'guardian'])) {
                    sendResponse('error', null, 'Invalid relation selected.');
                }
                if ($parent_salary !== null && !is_numeric($parent_salary)) {
                    sendResponse('error', null, 'Parent salary must be a number.');
                }
                if ($alt_email && !filter_var($alt_email, FILTER_VALIDATE_EMAIL)) {
                    sendResponse('error', null, 'Invalid email address.');
                }
                if ($alt_phone && !preg_match('/^[0-9]{10}$/', $alt_phone)) {
                    sendResponse('error', null, 'Phone number must be exactly 10 digits.');
                }

                $stmt = $conn->prepare("INSERT INTO parents 
                    (user_id, father_name, mother_name, relation, occupation, parent_salary, alt_phone, alt_email, address) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("issssdsss", $user_id, $father_name, $mother_name, $relation, $occupation, $parent_salary, $alt_phone, $alt_email, $address);
                if (!$stmt->execute()) {
                    sendResponse('error', null, "Parent insert failed: " . $stmt->error);
                }
                $stmt->close();
                break;

            case 5: // Accountant
                $qualification = null;
                $join_date = date("Y-m-d");

                // staff
                $stmt = $conn->prepare("INSERT INTO staff (user_id, designation, join_date) VALUES (?, 'accountant', ?)");
                $stmt->bind_param("is", $user_id, $join_date);
                $stmt->execute();
                $staff_id = $stmt->insert_id;
                $stmt->close();

                // accountant_details
                // $salary       = $_POST['salary'] ?? 0.00;
                // $bank_account = $_POST['bank_account'] ?? null;

                // accountant_details
                $salary = isset($_POST['salary']) && is_numeric($_POST['salary']) ? floatval($_POST['salary']) : null;
                $bank_account = isset($_POST['bank_account']) ? preg_replace('/\s+/', '', $_POST['bank_account']) : null;

                if ($salary === null || $salary < 0) {
                    sendResponse('error', null, 'Salary is required and must be a valid number.');
                }

                if (empty($bank_account) || !preg_match('/^\d{12}$/', $bank_account)) {
                    sendResponse('error', null, 'Bank account must be exactly 12 digits.');
                }


                $stmt = $conn->prepare("INSERT INTO accountant_details (staff_id, salary, bank_account) VALUES (?, ?, ?)");
                $stmt->bind_param("ids", $staff_id, $salary, $bank_account);
                if (!$stmt->execute()) {
                    sendResponse('error', null, "Accountant insert failed: " . $stmt->error);
                }
                $stmt->close();
                break;
        }

        sendResponse('success', ['user_id' => $user_id], 'User added successfully!');
    } catch (Exception $e) {
        sendResponse('error', null, "Exception: " . $e->getMessage());
    }
}

// function update_user($conn)
// {
//     $user_id = $_POST['user_id'] ?? 0;
//     $fullname = $_POST['fullname'] ?? '';
//     $username = $_POST['username'] ?? '';
//     $email = $_POST['email'] ?? '';
//     $phone = $_POST['phone'] ?? '';
//     $role_id = $_POST['role_id'] ?? '';
//     $branch_id = $_POST['branch_id'] ?? '';
//     $status = $_POST['status'] ?? 'active';
//     $class_id = $_POST['class_id'] ?? null;
//     $section_id = $_POST['section_id'] ?? null;

//     if (!$user_id) {
//         sendResponse("error", null, "Invalid user ID.");
//     }

//     // 1️⃣ Backup old data
//     $stmt = $conn->prepare("SELECT * FROM users WHERE user_id=?");
//     $stmt->bind_param("i", $user_id);
//     $stmt->execute();
//     $oldUser = $stmt->get_result()->fetch_assoc();
//     $stmt->close();

//     if ($oldUser) {
//         $stmt = $conn->prepare("INSERT INTO old_user_data (user_id, old_data, changed_by, change_type) VALUES (?, ?, ?, 'edit')");
//         $oldDataJson = json_encode($oldUser);
//         $changedBy = $_SESSION['user_id'] ?? 0;
//         $stmt->bind_param("isi", $user_id, $oldDataJson, $changedBy);
//         $stmt->execute();
//         $stmt->close();
//     }

//     // 2️⃣ Update user
//     $stmt = $conn->prepare("UPDATE users SET fullname=?, username=?, email=?, phone=?, role_id=?, branch_id=?, status=? WHERE user_id=?");
//     $stmt->bind_param("sssiisii", $fullname, $username, $email, $phone, $role_id, $branch_id, $status, $user_id);
//     if ($stmt->execute()) {
//         $stmt->close();
//         sendResponse("success", null, "User updated successfully.");
//     } else {
//         $stmt->close();
//         sendResponse("error", null, "Failed to update user.");
//     }
// }

// function delete_user($conn)
// {
//     $user_id = $_POST['user_id'] ?? 0;
//     if (!$user_id) sendResponse("error", null, "Invalid user ID.");

//     // Backup old data
//     $stmt = $conn->prepare("SELECT * FROM users WHERE user_id=?");
//     $stmt->bind_param("i", $user_id);
//     $stmt->execute();
//     $oldUser = $stmt->get_result()->fetch_assoc();
//     $stmt->close();

//     if ($oldUser) {
//         $stmt = $conn->prepare("INSERT INTO old_user_data (user_id, old_data, changed_by, change_type) VALUES (?, ?, ?, 'delete')");
//         $oldDataJson = json_encode($oldUser);
//         $changedBy = $_SESSION['user_id'] ?? 0;
//         $stmt->bind_param("isi", $user_id, $oldDataJson, $changedBy);
//         $stmt->execute();
//         $stmt->close();
//     }

//     // Soft delete
//     $stmt = $conn->prepare("UPDATE users SET status='deleted' WHERE user_id=?");
//     $stmt->bind_param("i", $user_id);
//     if ($stmt->execute()) {
//         $stmt->close();
//         sendResponse("success", null, "User deleted (soft delete) successfully.");
//     } else {
//         $error = $stmt->error;
//         $stmt->close();
//         sendResponse("error", null, "Failed to delete user: $error");
//     }
// }

function delete_user($conn)
{
    $user_id = $_POST['user_id'] ?? 0;
    if (!$user_id) sendResponse("error", null, "Invalid user ID.");

    // 1️⃣ Fetch user
    $stmt = $conn->prepare("SELECT * FROM users WHERE user_id=?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$user) {
        sendResponse("error", null, "User not found.");
    }

    // 2️⃣ Check if already inactive (deleted)
    if ($user['status'] === 'inactive') {
        sendResponse("error", null, "User already deleted.");
    }


    // 4️⃣ Soft delete
    $stmt = $conn->prepare("UPDATE users SET status='inactive' WHERE user_id=?");
    $stmt->bind_param("i", $user_id);
    if ($stmt->execute()) {
        $stmt->close();
        sendResponse("success", null, "User deleted (soft delete) successfully.");
    } else {
        $error = $stmt->error;
        $stmt->close();
        sendResponse("error", null, "Failed to delete user: $error");
    }
    // 3️⃣ Backup old data
    $stmt = $conn->prepare("INSERT INTO old_user_data (user_id, old_data, changed_by, change_type) VALUES (?, ?, ?, 'delete')");
    $oldDataJson = json_encode($user);
    $changedBy = $_SESSION['user_id'] ?? 0;
    $stmt->bind_param("isi", $user_id, $oldDataJson, $changedBy);
    $stmt->execute();
    $stmt->close();
}




// function delete_user($conn)
// {
//     $user_id = $_POST['user_id'] ?? 0;
//     if (!$user_id) sendResponse("error", null, "Invalid user ID.");

//     // Backup
//     $stmt = $conn->prepare("SELECT * FROM users WHERE user_id=?");
//     $stmt->bind_param("i", $user_id);
//     $stmt->execute();
//     $oldUser = $stmt->get_result()->fetch_assoc();
//     $stmt->close();

//     if ($oldUser) {
//         $stmt = $conn->prepare("INSERT INTO old_user_data (user_id, old_data, changed_by, change_type) VALUES (?, ?, ?, 'delete')");
//         $oldDataJson = json_encode($oldUser);
//         $changedBy = $_SESSION['user_id'] ?? 0;
//         $stmt->bind_param("isi", $user_id, $oldDataJson, $changedBy);
//         $stmt->execute();
//         $stmt->close();
//     }

//     // Clean up role-specific tables
//     $role_id = $oldUser['role_id'] ?? null;
//     if ($role_id) {
//         switch ($role_id) {
//             case STUDENT_ROLE_ID:
//                 $conn->query("DELETE FROM students WHERE user_id=$user_id");
//                 break;
//             case TEACHER_ROLE_ID:
//                 $conn->query("DELETE FROM teacher_assignments WHERE teacher_id=$user_id");
//                 $conn->query("DELETE FROM teachers WHERE user_id=$user_id");
//                 break;
//             case ACCOUNTANT_ROLE_ID:
//                 $conn->query("DELETE FROM accountants WHERE user_id=$user_id");
//                 break;
//             case PARENT_ROLE_ID:
//                 $conn->query("DELETE FROM parents WHERE user_id=$user_id");
//                 break;
//         }
//     }

//     // Finally delete user
//     $stmt = $conn->prepare("DELETE FROM users WHERE user_id=?");
//     $stmt->bind_param("i", $user_id);
//     if ($stmt->execute()) {
//         $stmt->close();
//         sendResponse("success", null, "User deleted successfully.");
//     } else {
//         $stmt->close();
//         sendResponse("error", null, "Failed to delete user.");
//     }
// }

/** ========== GET FUNCTIONS ========== **/



function getRecentAdmissions($conn)
{
    // Use defined constant if available, fallback to 3
    $STUDENT_ROLE_ID = defined('STUDENT_ROLE_ID') ? STUDENT_ROLE_ID : 3;

    $sql = "
        SELECT
            u.fullname,
            c.class_name,
            sec.section_name,
            st.admission_date
        FROM students st
        INNER JOIN users u ON st.user_id = u.user_id
        LEFT JOIN classes c ON st.class_id = c.class_id
        LEFT JOIN sections sec ON st.section_id = sec.section_id
        WHERE u.role_id = {$STUDENT_ROLE_ID}
          AND u.status = 'active'
          AND st.status = 'active'
        ORDER BY st.admission_date DESC
        LIMIT 10
    ";

    $result = $conn->query($sql);
    if (!$result) {
        // return the DB error to the client for quick debugging
        sendResponse('error', null, 'Query failed: ' . $conn->error);
    }

    $students = [];
    while ($row = $result->fetch_assoc()) {
        $students[] = $row;
    }

    sendResponse('success', $students, 'Recent admissions loaded');
}


function getBranches($conn)
{
    $sql = "SELECT branch_id, branch_name FROM branches ORDER BY branch_name";
    $result = $conn->query($sql);
    $branches = $result->fetch_all(MYSQLI_ASSOC);
    sendResponse('success', $branches, 'Branches loaded');
}

function getRoles($conn)
{
    $sql = "SELECT role_id, role_name FROM roles ORDER BY role_id";
    $result = $conn->query($sql);
    $roles = $result->fetch_all(MYSQLI_ASSOC);
    sendResponse('success', $roles, 'Roles loaded');
}

function getClasses($conn, $branch_id)
{
    if (!$branch_id) sendResponse('error', null, 'Branch ID required');
    $stmt = $conn->prepare("SELECT class_id, class_name FROM classes WHERE branch_id=? ORDER BY class_name");
    $stmt->bind_param("i", $branch_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $classes = $result->fetch_all(MYSQLI_ASSOC);
    sendResponse('success', $classes, 'Classes loaded');
}

function getSections($conn, $class_id)
{
    if (!$class_id) sendResponse('error', null, 'Class ID required');
    $stmt = $conn->prepare("SELECT section_id, section_name FROM sections WHERE class_id=? ORDER BY section_name");
    $stmt->bind_param("i", $class_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $sections = $result->fetch_all(MYSQLI_ASSOC);
    sendResponse('success', $sections, 'Sections loaded');
}

// function getSubjects($conn, $branch_id)
// {
//     if (!$branch_id) sendResponse('error', null, 'Branch ID required');
//     $stmt = $conn->prepare("SELECT s.subject_id, s.subject_name FROM subjects s JOIN classes c ON s.class_id = c.class_id WHERE c.branch_id=? ORDER BY s.subject_name");
//     $stmt->bind_param("i", $branch_id);
//     $stmt->execute();
//     $result = $stmt->get_result();
//     $subjects = $result->fetch_all(MYSQLI_ASSOC);
//     sendResponse('success', $subjects, 'Subjects loaded');
// }

// function getParents($conn)
// {
//     $sql = "SELECT p.parent_id, u.user_id, u.fullname, u.phone, u.email,
//                    p.father_name, p.mother_name, p.occupation, p.alt_phone
//             FROM parents p
//             JOIN users u ON p.user_id = u.user_id
//             ORDER BY u.fullname";
//     $result = $conn->query($sql);
//     $parents = $result->fetch_all(MYSQLI_ASSOC);
//     sendResponse('success', $parents, 'Parents loaded');
// }

function getSubjects($conn, $class_id)
{
    if (!$class_id) sendResponse('error', null, 'Class ID required');
    $stmt = $conn->prepare("SELECT subject_id, subject_name 
                            FROM subjects 
                            WHERE class_id = ? 
                            ORDER BY subject_name");
    $stmt->bind_param("i", $class_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $subjects = $result->fetch_all(MYSQLI_ASSOC);
    sendResponse('success', $subjects, 'Subjects loaded');
}

function getParents($conn)
{
    $sql = "SELECT p.parent_id, u.fullname, u.phone
            FROM parents p
            JOIN users u ON p.user_id = u.user_id
            ORDER BY u.fullname";
    $result = $conn->query($sql);
    $parents = $result->fetch_all(MYSQLI_ASSOC);
    sendResponse('success', $parents, 'Parents loaded');
}

function getParentById($conn, $parent_id)
{
    if (!$parent_id) {
        sendResponse('error', null, 'Parent ID required');
    }

    $sql = "SELECT user_id AS parent_id, fullname, phone 
            FROM users 
            WHERE user_id=? AND role_id=(SELECT role_id FROM roles WHERE role_name='PARENT' LIMIT 1)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $parent_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();

    if ($result) {
        sendResponse('success', $result, 'Parent found');
    } else {
        sendResponse('error', null, 'Parent not found');
    }
}


function getUsers($conn)
{
    $role_id   = $_POST['role_id'] ?? '';
    $branch_id = $_POST['branch_id'] ?? '';
    $search    = $_POST['search'] ?? '';
    $page      = $_POST['page'] ?? 1;
    $limit     = 10;
    $offset    = ($page - 1) * $limit;

    $sql = "SELECT u.user_id, u.fullname, u.username, u.email, u.phone, 
                   u.status, r.role_name, b.branch_name,
                   c.class_name, s.section_name
            FROM users u
            LEFT JOIN roles r ON u.role_id = r.role_id
            LEFT JOIN branches b ON u.branch_id = b.branch_id
            LEFT JOIN students st ON u.user_id = st.user_id
            LEFT JOIN classes c ON st.class_id = c.class_id
            LEFT JOIN sections s ON st.section_id = s.section_id
            WHERE 1=1 And u.status = 'active'";

    $params = [];
    $types  = "";

    if ($role_id !== "") {
        $sql .= " AND u.role_id = ?";
        $params[] = $role_id;
        $types   .= "i";
    }
    if ($branch_id !== "") {
        $sql .= " AND u.branch_id = ?";
        $params[] = $branch_id;
        $types   .= "i";
    }
    if ($search !== "") {
        $sql .= " AND (u.fullname LIKE ? OR u.email LIKE ? OR u.phone LIKE ? 
                       OR b.branch_name LIKE ? OR c.class_name LIKE ? OR s.section_name LIKE ?)";
        $searchTerm = "%$search%";
        $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm]);
        $types  .= str_repeat("s", 6);
    }

    // ---- Count total ----
    $countSql = "SELECT COUNT(*) as total FROM ($sql) as t";
    $stmt = $conn->prepare($countSql);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $totalRows = $row['total'] ?? 0;
    $totalPages = ceil($totalRows / $limit);
    $stmt->close();

    // ---- Main query with LIMIT ----
    $sql .= " ORDER BY u.user_id DESC LIMIT ? OFFSET ?";
    $paramsWithLimit = $params;
    $paramsWithLimit[] = $limit;
    $paramsWithLimit[] = $offset;
    $typesWithLimit = $types . "ii";

    $stmt = $conn->prepare($sql);
    if (!empty($paramsWithLimit)) {
        $stmt->bind_param($typesWithLimit, ...$paramsWithLimit);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    $users = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    sendResponse("success", [
        "users" => $users,
        "totalPages" => $totalPages,
        "currentPage" => $page
    ], "Users fetched successfully");
}

// Full getUser implementation

function getUser($conn, $user_id)
{
    if (!$user_id) sendResponse('error', null, 'Invalid user ID.');

    // Base user + role + branch name
    $sql = "SELECT u.*, r.role_name, b.branch_name
            FROM users u
            LEFT JOIN roles r ON u.role_id = r.role_id
            LEFT JOIN branches b ON u.branch_id = b.branch_id
            WHERE u.user_id = ? LIMIT 1";
    $stmt = $conn->prepare($sql);
    if (!$stmt) sendResponse('error', null, 'SQL prepare error: ' . $conn->error);
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $base = $stmt->get_result()->fetch_assoc();
    if (!$base) sendResponse('error', null, 'User not found.');

    // Always include branch name even when blank
    $base['branch_name'] = $base['branch_name'] ?? null;

    switch ((int)$base['role_id']) {
        case STUDENT_ROLE_ID:
            $sql = "SELECT s.*, c.class_name, sec.section_name, p.father_name, p.mother_name
                    FROM students s
                    LEFT JOIN classes c ON s.class_id = c.class_id
                    LEFT JOIN sections sec ON s.section_id = sec.section_id
                    LEFT JOIN parents p ON s.parent_id = p.parent_id
                    WHERE s.user_id = ? LIMIT 1";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('i', $user_id);
            $stmt->execute();
            $extra = $stmt->get_result()->fetch_assoc() ?: [];
            // rename address
            if (isset($extra['address'])) $extra['student_address'] = $extra['address'];
            unset($extra['address']);
            $base = array_merge($base, $extra);
            break;

        case PARENT_ROLE_ID:
            $sql = "SELECT p.* FROM parents p WHERE p.user_id = ? LIMIT 1";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('i', $user_id);
            $stmt->execute();
            $extra = $stmt->get_result()->fetch_assoc() ?: [];
            if (isset($extra['address'])) $extra['parent_address'] = $extra['address'];
            unset($extra['address']);
            $base = array_merge($base, $extra);
            break;

        case TEACHER_ROLE_ID:
            // staff row
            $sql = "SELECT st.* FROM staff st WHERE st.user_id = ? LIMIT 1";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('i', $user_id);
            $stmt->execute();
            $staff = $stmt->get_result()->fetch_assoc() ?: [];
            $base = array_merge($base, $staff);

            if (!empty($staff['staff_id'])) {
                $sql = "SELECT ta.class_id, c.class_name, ta.section_id, sec.section_name, ta.subject_id, sub.subject_name
                        FROM teacher_assignments ta
                        LEFT JOIN classes c ON ta.class_id = c.class_id
                        LEFT JOIN sections sec ON ta.section_id = sec.section_id
                        LEFT JOIN subjects sub ON ta.subject_id = sub.subject_id
                        WHERE ta.staff_id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param('i', $staff['staff_id']);
                $stmt->execute();
                $res = $stmt->get_result();
                $assignments = [];
                while ($r = $res->fetch_assoc()) $assignments[] = $r;
                $base['teacher_assignments'] = $assignments;
            } else {
                $base['teacher_assignments'] = [];
            }
            break;

        case ACCOUNTANT_ROLE_ID:
            $sql = "SELECT st.* FROM staff st WHERE st.user_id = ? LIMIT 1";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('i', $user_id);
            $stmt->execute();
            $staff = $stmt->get_result()->fetch_assoc() ?: [];
            $base = array_merge($base, $staff);

            if (!empty($staff['staff_id'])) {
                $sql = "SELECT ad.accountant_id, ad.salary, ad.bank_account FROM accountant_details ad WHERE ad.staff_id = ? LIMIT 1";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param('i', $staff['staff_id']);
                $stmt->execute();
                $extra = $stmt->get_result()->fetch_assoc() ?: [];
                $base = array_merge($base, $extra);
            }
            break;
    }

    sendResponse('success', $base, 'User details loaded.');
}

// Simple updateUser handler (expand validation as required)
// function updateUserHandler($conn, $post)
// {
//     $user_id = intval($post['user_id'] ?? 0);
//     if (!$user_id) sendResponse('error', null, 'Invalid user id.');

//     // Update common user fields (do not update password if blank)
//     $fullname = $conn->real_escape_string($post['fullname'] ?? '');
//     $username = $conn->real_escape_string($post['username'] ?? '');
//     $email = $conn->real_escape_string($post['email'] ?? '');
//     $phone = $conn->real_escape_string($post['phone'] ?? '');
//     $branch_id = intval($post['branch_id'] ?? 0);

//     $pwdSql = '';
//     if (!empty($post['password'])) {
//         // hash password (use same method as rest of app)
//         $hashed = password_hash($post['password'], PASSWORD_BCRYPT);
//         $pwdSql = ", password = '" . $conn->real_escape_string($hashed) . "'";
//     }

//     $sql = "UPDATE users SET fullname = ?, username = ?, email = ?, phone = ?, branch_id = ? $pwdSql WHERE user_id = ?";
//     $stmt = $conn->prepare($sql);
//     if ($stmt === false) {
//         // fallback simple update without password part
//         $sql2 = "UPDATE users SET fullname = ?, username = ?, email = ?, phone = ?, branch_id = ? WHERE user_id = ?";
//         $stmt = $conn->prepare($sql2);
//         if (!$stmt) sendResponse('error', null, 'SQL error: ' . $conn->error);
//     }
//     $stmt->bind_param('sssdii', $fullname, $username, $email, $phone, $branch_id, $user_id);
//     // NOTE: above binding may vary based on types; adjust if needed.
//     if (!$stmt->execute()) sendResponse('error', null, 'Failed to update user: ' . $stmt->error);

//     // Role-specific updates: expand as per your app (example for student & parent & accountant & teacher)
//     $resData = ['user_updated' => true];

//     // STUDENT
//     if (intval($post['role_id'] ?? 0) === STUDENT_ROLE_ID) {
//         $class_id = intval($post['class_id'] ?? 0);
//         $section_id = intval($post['section_id'] ?? 0);
//         $dob = $post['dob'] ?? null;
//         $gender = $post['gender'] ?? null;
//         $admission_date = $post['admission_date'] ?? null;
//         $parent_id = intval($post['parent_id'] ?? 0);
//         $aadhar_no = $conn->real_escape_string($post['aadhar_no'] ?? null);
//         $blood_group = $conn->real_escape_string($post['blood_group'] ?? null);
//         $student_address = $conn->real_escape_string($post['student_address'] ?? null);
//         $transport_required = $conn->real_escape_string($post['transport_required'] ?? 'no');
//         $hostel_required = $conn->real_escape_string($post['hostel_required'] ?? 'no');

//         // Upsert student row (simple approach)
//         $stmt = $conn->prepare("SELECT student_id FROM students WHERE user_id = ? LIMIT 1");
//         $stmt->bind_param('i', $user_id);
//         $stmt->execute();
//         $exists = $stmt->get_result()->fetch_assoc();
//         if ($exists) {
//             $stmt = $conn->prepare("UPDATE students SET class_id=?, section_id=?, dob=?, gender=?, admission_date=?, parent_id=?, aadhar_no=?, blood_group=?, address=?, transport_required=?, hostel_required=? WHERE user_id=?");
//             $stmt->bind_param('iisssssssisi', $class_id, $section_id, $dob, $gender, $admission_date, $parent_id, $aadhar_no, $blood_group, $student_address, $transport_required, $hostel_required, $user_id);
//             $stmt->execute();
//         } else {
//             $stmt = $conn->prepare("INSERT INTO students (user_id, class_id, section_id, dob, gender, admission_date, parent_id, aadhar_no, blood_group, address, transport_required, hostel_required) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
//             $stmt->bind_param('iiisssisssss', $user_id, $class_id, $section_id, $dob, $gender, $admission_date, $parent_id, $aadhar_no, $blood_group, $student_address, $transport_required, $hostel_required);
//             $stmt->execute();
//         }
//     }

//     // PARENT
//     if (intval($post['role_id'] ?? 0) === PARENT_ROLE_ID) {
//         $father_name = $conn->real_escape_string($post['father_name'] ?? null);
//         $mother_name = $conn->real_escape_string($post['mother_name'] ?? null);
//         $relation = $conn->real_escape_string($post['relation'] ?? 'father');
//         $occupation = $conn->real_escape_string($post['occupation'] ?? null);
//         $parent_salary = floatval($post['parent_salary'] ?? 0);
//         $alt_phone = $conn->real_escape_string($post['alt_phone'] ?? null);
//         $alt_email = $conn->real_escape_string($post['alt_email'] ?? null);
//         $parent_address = $conn->real_escape_string($post['parent_address'] ?? null);

//         $stmt = $conn->prepare("SELECT parent_id FROM parents WHERE user_id = ? LIMIT 1");
//         $stmt->bind_param('i', $user_id);
//         $stmt->execute();
//         $exists = $stmt->get_result()->fetch_assoc();
//         if ($exists) {
//             $stmt = $conn->prepare("UPDATE parents SET father_name=?, mother_name=?, relation=?, occupation=?, parent_salary=?, alt_phone=?, alt_email=?, address=? WHERE user_id=?");
//             $stmt->bind_param('ssssdssss', $father_name, $mother_name, $relation, $occupation, $parent_salary, $alt_phone, $alt_email, $parent_address, $user_id);
//             $stmt->execute();
//         } else {
//             $stmt = $conn->prepare("INSERT INTO parents (user_id, father_name, mother_name, relation, occupation, parent_salary, alt_phone, alt_email, address) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
//             $stmt->bind_param('isssssdss', $user_id, $father_name, $mother_name, $relation, $occupation, $parent_salary, $alt_phone, $alt_email, $parent_address);
//             $stmt->execute();
//         }
//     }

//     // ACCOUNTANT: ensure staff exists then accountant_details
//     if (intval($post['role_id'] ?? 0) === ACCOUNTANT_ROLE_ID) {
//         $salary = floatval($post['salary'] ?? 0);
//         $bank_account = $conn->real_escape_string($post['bank_account'] ?? null);

//         // staff row
//         $stmt = $conn->prepare("SELECT staff_id FROM staff WHERE user_id = ? LIMIT 1");
//         $stmt->bind_param('i', $user_id);
//         $stmt->execute();
//         $st = $stmt->get_result()->fetch_assoc();

//         if (!$st) {
//             $stmt = $conn->prepare("INSERT INTO staff (user_id, designation) VALUES (?, 'accountant')");
//             $stmt->bind_param('i', $user_id);
//             $stmt->execute();
//             $staff_id = $conn->insert_id;
//         } else {
//             $staff_id = $st['staff_id'];
//         }

//         $stmt = $conn->prepare("SELECT accountant_id FROM accountant_details WHERE staff_id = ? LIMIT 1");
//         $stmt->bind_param('i', $staff_id);
//         $stmt->execute();
//         $exists = $stmt->get_result()->fetch_assoc();

//         if ($exists) {
//             $stmt = $conn->prepare("UPDATE accountant_details SET salary=?, bank_account=? WHERE staff_id = ?");
//             $stmt->bind_param('dsi', $salary, $bank_account, $staff_id);
//             $stmt->execute();
//         } else {
//             $stmt = $conn->prepare("INSERT INTO accountant_details (staff_id, salary, bank_account) VALUES (?, ?, ?)");
//             $stmt->bind_param('ids', $staff_id, $salary, $bank_account);
//             $stmt->execute();
//         }
//     }

//     // TEACHER: update staff & teacher_assignments (we'll remove existing assignments and re-insert simple approach)
//     if (intval($post['role_id'] ?? 0) === TEACHER_ROLE_ID) {
//         $stmt = $conn->prepare("SELECT staff_id FROM staff WHERE user_id = ? LIMIT 1");
//         $stmt->bind_param('i', $user_id);
//         $stmt->execute();
//         $st = $stmt->get_result()->fetch_assoc();
//         if (!$st) {
//             $stmt = $conn->prepare("INSERT INTO staff (user_id, designation) VALUES (?, 'teacher')");
//             $stmt->bind_param('i', $user_id);
//             $stmt->execute();
//             $staff_id = $conn->insert_id;
//         } else {
//             $staff_id = $st['staff_id'];
//         }

//         // remove assignments then re-insert from posted arrays
//         $stmt = $conn->prepare("DELETE FROM teacher_assignments WHERE staff_id = ?");
//         $stmt->bind_param('i', $staff_id);
//         $stmt->execute();

//         // expect teacher_class[], teacher_section[], teacher_subject[]
//         $classArr = $_POST['teacher_class'] ?? [];
//         $sectionArr = $_POST['teacher_section'] ?? [];
//         $subjectArr = $_POST['teacher_subject'] ?? [];

//         for ($i = 0; $i < count($classArr); $i++) {
//             $c = intval($classArr[$i] ?? 0);
//             $s = intval($sectionArr[$i] ?? 0);
//             $sub = intval($subjectArr[$i] ?? 0);
//             if ($c && $s && $sub) {
//                 $stmt = $conn->prepare("INSERT INTO teacher_assignments (staff_id, class_id, section_id, subject_id) VALUES (?, ?, ?, ?)");
//                 $stmt->bind_param('iiii', $staff_id, $c, $s, $sub);
//                 $stmt->execute();
//             }
//         }
//     }

//     sendResponse('success', $resData, 'User updated.');
// }


function update_user($conn)
{
    try {
        $user_id   = intval($_POST['user_id']);
        $fullname  = trim($_POST['fullname']);
        $username  = trim($_POST['username']);
        $email     = trim($_POST['email']);
        $phone     = trim($_POST['phone']);
        $branch_id = intval($_POST['branch_id']);
        $role_id   = intval($_POST['role_id']);

        if (!$user_id || empty($fullname) || empty($username) || !$role_id) {
            sendResponse('error', null, 'Missing required fields.');
        }

        // === Common Validations ===
        if (!preg_match("/^[A-Za-z\s]+$/", $fullname)) {
            sendResponse('error', null, 'Full name must contain only letters.');
        }
        if (!preg_match("/^[A-Za-z0-9_]+$/", $username)) {
            sendResponse('error', null, 'Username must contain only letters, numbers, or _.');
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            sendResponse('error', null, 'Invalid email address.');
        }
        if (!preg_match("/^\d{10}$/", $phone)) {
            sendResponse('error', null, 'Phone must be exactly 10 digits.');
        }

        // === Update Users table (password optional) ===
        if (!empty($_POST['password'])) {
            if (!preg_match("/^(?=.*[A-Za-z])(?=.*\d)(?=.*[@$!%*#?&]).{8,}$/", $_POST['password'])) {
                sendResponse('error', null, 'Password must be at least 8 chars, with letter, number & special char.');
            }
            $password = password_hash($_POST['password'], PASSWORD_BCRYPT);
            $stmt = $conn->prepare("UPDATE users SET fullname=?, username=?, email=?, phone=?, password=?, branch_id=? WHERE user_id=?");
            $stmt->bind_param("sssssii", $fullname, $username, $email, $phone, $password, $branch_id, $user_id);
        } else {
            $stmt = $conn->prepare("UPDATE users SET fullname=?, username=?, email=?, phone=?, branch_id=? WHERE user_id=?");
            $stmt->bind_param("ssssii", $fullname, $username, $email, $phone, $branch_id, $user_id);
        }
        if (!$stmt->execute()) {
            sendResponse('error', null, "User update failed: " . $stmt->error);
        }
        $stmt->close();

        // === Role Specific Updates ===
        switch ($role_id) {
            case 2: // Teacher
                $qualification = $_POST['qualification'] ?? null;

                // Update staff table
                $stmt = $conn->prepare("UPDATE staff SET qualification=? WHERE user_id=? AND designation='teacher'");
                $stmt->bind_param("si", $qualification, $user_id);
                $stmt->execute();
                $stmt->close();

                // Get staff_id
                $staff_id = null;
                $rs = $conn->query("SELECT staff_id FROM staff WHERE user_id=$user_id AND designation='teacher' LIMIT 1");
                if ($rs && $rs->num_rows > 0) {
                    $staff_id = $rs->fetch_assoc()['staff_id'];
                }
                if (!$staff_id) {
                    sendResponse('error', null, 'Staff record not found for teacher.');
                }

                // Clear old assignments
                $conn->query("DELETE FROM teacher_assignments WHERE staff_id=$staff_id");

                // Insert new assignments
                if (!empty($_POST['teacher_class_edit'])) {
                    $count = count($_POST['teacher_class_edit']);
                    for ($i = 0; $i < $count; $i++) {
                        $class_id   = intval($_POST['teacher_class_edit'][$i]);
                        $section_id = intval($_POST['teacher_section_edit'][$i]);
                        $subject_id = intval($_POST['teacher_subject_edit'][$i]);

                        if ($class_id && $section_id && $subject_id) {
                            $stmt = $conn->prepare("INSERT INTO teacher_assignments (staff_id, class_id, section_id, subject_id) VALUES (?, ?, ?, ?)");
                            $stmt->bind_param("iiii", $staff_id, $class_id, $section_id, $subject_id);
                            $stmt->execute();
                            $stmt->close();
                        }
                    }
                }
                break;

            case 3: // Student
                $class_id       = intval($_POST['class_id']);
                $section_id     = intval($_POST['section_id']);
                $dob            = $_POST['dob'] ?: null;
                $gender         = $_POST['gender'] ?: null;
                $admission_date = $_POST['admission_date'] ?: null;
                $parent_id      = !empty($_POST['parent_id']) ? intval($_POST['parent_id']) : null;
                $aadhar_no      = $_POST['aadhar_no'] ?? null;
                $blood_group    = $_POST['blood_group'] ?? null;
                $address        = $_POST['student_address'] ?? null;
                $transport_req  = $_POST['transport_required'] ?? 'no';
                $hostel_req     = $_POST['hostel_required'] ?? 'no';

                // Photo (optional update)
                $photo_path = null;
                if (!empty($_FILES['photo']['name'])) {
                    $ext = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));
                    if (!in_array($ext, ['jpg', 'jpeg', 'png'])) {
                        sendResponse('error', null, "Only JPG, JPEG, PNG allowed.");
                    }
                    if ($_FILES['photo']['size'] > 2 * 1024 * 1024) {
                        sendResponse('error', null, "Photo must be less than 2MB.");
                    }
                    $upload_dir = "../../uploads/students/";
                    if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
                    $photo_path = $upload_dir . "stu_" . $user_id . "." . $ext;
                    move_uploaded_file($_FILES['photo']['tmp_name'], $photo_path);
                }

                $stmt = $conn->prepare("UPDATE students 
                    SET class_id=?, section_id=?, dob=?, gender=?, admission_date=?, parent_id=?, 
                        aadhar_no=?, blood_group=?, address=?, transport_required=?, hostel_required=?, photo=? 
                    WHERE user_id=?");
                $stmt->bind_param(
                    "iisssissssssi",
                    $class_id,
                    $section_id,
                    $dob,
                    $gender,
                    $admission_date,
                    $parent_id,
                    $aadhar_no,
                    $blood_group,
                    $address,
                    $transport_req,
                    $hostel_req,
                    $photo_path,
                    $user_id
                );
                if (!$stmt->execute()) {
                    sendResponse('error', null, "Student update failed: " . $stmt->error);
                }
                $stmt->close();
                break;

            case 4: // Parent
                $father_name   = $_POST['father_name'] ?? null;
                $mother_name   = $_POST['mother_name'] ?? null;
                $relation      = $_POST['relation'] ?? 'father';
                $occupation    = $_POST['occupation'] ?? null;
                $parent_salary = $_POST['parent_salary'] ?? 0.00;
                $alt_phone     = $_POST['alt_phone'] ?? null;
                $alt_email     = $_POST['alt_email'] ?? null;
                $address       = $_POST['parent_address'] ?? null;

                $stmt = $conn->prepare("UPDATE parents 
                    SET father_name=?, mother_name=?, relation=?, occupation=?, parent_salary=?, alt_phone=?, alt_email=?, address=? 
                    WHERE user_id=?");
                $stmt->bind_param("ssssssssi", $father_name, $mother_name, $relation, $occupation, $parent_salary, $alt_phone, $alt_email, $address, $user_id);
                if (!$stmt->execute()) {
                    sendResponse('error', null, "Parent update failed: " . $stmt->error);
                }
                $stmt->close();
                break;

            case 5: // Accountant
                $salary       = isset($_POST['salary']) ? floatval($_POST['salary']) : null;
                $bank_account = isset($_POST['bank_account']) ? preg_replace('/\s+/', '', $_POST['bank_account']) : null;

                $stmt = $conn->prepare("UPDATE accountant_details 
                    SET salary=?, bank_account=? 
                    WHERE staff_id=(SELECT staff_id FROM staff WHERE user_id=? AND designation='accountant')");
                $stmt->bind_param("dsi", $salary, $bank_account, $user_id);
                if (!$stmt->execute()) {
                    sendResponse('error', null, "Accountant update failed: " . $stmt->error);
                }
                $stmt->close();
                break;
        }

        sendResponse('success', ['user_id' => $user_id], 'User updated successfully!');
    } catch (Exception $e) {
        sendResponse('error', null, "Exception: " . $e->getMessage());
    }
}
