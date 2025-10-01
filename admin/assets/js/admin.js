// console.log("main.js loaded");

/* =====================================================
   0. Global Variables
   ===================================================== */

/* =====================================================
   1. Sidebar Animation
   ===================================================== */
$("#sidebarToggle").on("click", function () {
  $(this).toggleClass("open"); // hamburger → X
  $("#sidebar").toggleClass("active"); // sidebar slide
  $("body").toggleClass("no-scroll");
});

$("#closeSidebar").on("click", function () {
  $("#sidebarToggle").removeClass("open");
  $("#sidebar").removeClass("active");
});

/* =====================================================
   2. helpers functions
   ===================================================== */
function escapeHtml(str) {
  if (!str) return "";
  return String(str)
    .replace(/&/g, "&amp;")
    .replace(/</g, "&lt;")
    .replace(/>/g, "&gt;")
    .replace(/"/g, "&quot;");
}

// Utility: Random password generator
function generatePassword(length = 8) {
  let chars =
    "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789@$!%*?&";
  return Array.from(crypto.getRandomValues(new Uint32Array(length)))
    .map((x) => chars[x % chars.length])
    .join("");
}

// Auto-generate password when role is selected (if empty)
$(document).on("change", "#roleSelect", function () {
  if (!$("#password").val()) {
    let randomPass = generatePassword(8);
    $("#password").val(randomPass);
  }
});

function loadDashboardPage() {
  $(".dash-area").html(
    '<div class="text-center p-5 text-muted">Loading dashboard...</div>'
  );

  $.get("dashboard.php")
    .done(function (html) {
      $(".dash-area").html(html);
      // initStudentTable();
      loadRecentAdmissions();
    })
    .fail(function () {
      $(".dash-area").html(
        '<div class="text-center p-5 text-danger">Failed to load Dashboard UI.</div>'
      );
    });
}
// function loadAddUserPage() {
//   $(".dash-area").html(
//     '<div class="text-center p-5 text-muted">Loading Add User...</div>'
//   );
//   $(".dash-area").load("users/add.php", showError("Add User"));
// }

function loadAddUserPage() {
  $(".dash-area").html(
    '<div class="text-center p-5 text-muted">Loading Add User...</div>'
  );

  $(".dash-area").load("users/add.php", function (response, status, xhr) {
    if (status === "error") {
      console.error(
        "Error loading add.php:",
        xhr.status,
        xhr.statusText,
        response
      );
      $(".dash-area").html(
        `<div class="text-center p-5 text-danger">
        Failed to load Add User page. (${xhr.status} ${xhr.statusText})
      </div>`
      );
    } else {
      //  Only init after page loads successfully
      loadBranches();
      loadRoles();
      loadParents();
    }
  });
}

function loadManageUserPage() {
  $(".dash-area").html(
    '<div class="text-center p-5 text-muted">Loading Users List...</div>'
  );
  $(".dash-area").load("users/manage.php", function () {
    // ✅ Init after manage.php loads
    let filters = { role_id: "", branch_id: "", search: "", page: 1 };
    loadBranchesFilter();
    loadUsers(filters);

    // bind filters inside callback (to avoid lost events)
    $(".tab-btn")
      .off("click")
      .on("click", function () {
        $(".tab-btn").removeClass("active");
        $(this).addClass("active");
        filters.role_id = $(this).data("role");
        filters.page = 1;
        loadUsers(filters);
      });

    $("#branchFilter")
      .off("change")
      .on("change", function () {
        filters.branch_id = $(this).val();
        filters.page = 1;
        loadUsers(filters);
      });

    $("#userSearch")
      .off("keyup")
      .on("keyup", function () {
        filters.search = $(this).val();
        filters.page = 1;
        loadUsers(filters);
      });

    $(document)
      .off("click", "#usersPagination a")
      .on("click", "#usersPagination a", function (e) {
        e.preventDefault();
        filters.page = parseInt($(this).data("page"));
        loadUsers(filters);
      });
  });
}

function loadViewUserPage() {
  $(".dash-area").html(
    '<div class="text-center p-5 text-muted">Loading View User...</div>'
  );
  $(".dash-area").load("users/view.php", showError("View User"));
}

// function loadDeleteUserPage() {
//   $(".dash-area").html(
//     '<div class="text-center p-5 text-muted">Loading Delete User...</div>'
//   );
//   $(".dash-area").load("users/delete.php", showError("Delete User"));
// }

function loadDeleteUserPage(params) {
  $(".dash-area").html(
    '<div class="text-center p-5 text-muted">Loading Delete Page...</div>'
  );

  $(".dash-area").load("users/delete.php", function () {
    const userId = params.get("user_id");
    if (!userId) {
      alert("Invalid user ID");
      return;
    }

    apiPost({ action: "get_user", user_id: userId }, function (res) {
      if (res.status !== "success") {
        alert(res.message || "Failed to load user");
        return;
      }

      const u = res.data;
      console.log("DEBUG: Delete page user data", u);

      $("#del_fullname").text(u.fullname || "");
      $("#del_role").text(u.role_name || "");
      $("#del_email").text(u.email || "");
      $("#del_phone").text(u.phone || "");
      $("#del_branch").text(u.branch_name || "");

      if (u.photo) {
        $("#userPhoto").attr("src", u.photo);
      }

      // Role-specific details
      const roleDetails = $("#roleSpecificDetails");
      roleDetails.empty();

      if (u.role_name === "Student") {
        roleDetails.append(`
          <p><strong>Class:</strong> ${u.class_name || ""}</p>
          <p><strong>Section:</strong> ${u.section_name || ""}</p>
          <p id="del_parent"><strong>Parent:</strong> Loading...</p>
          <p><strong>DOB:</strong> ${u.dob || ""}</p>
          <p><strong>Address:</strong> ${u.student_address || ""}</p>
        `);
        if (u.parent_id) {
          apiPost(
            { action: "get_parent_by_id", parent_id: u.parent_id },
            function (pres) {
              if (pres.status === "success" && pres.data) {
                $("#del_parent").html(
                  `<strong>Parent:</strong> ${pres.data.fullname} (${pres.data.phone})`
                );
              } else {
                $("#del_parent").html("<strong>Parent:</strong> Not Found");
              }
            }
          );
        } else {
          $("#del_parent").html("<strong>Parent:</strong> Not Assigned");
        }
      } else if (u.role_name === "Teacher") {
        if (Array.isArray(u.teacher_assignments)) {
          let html = "<strong>Assignments:</strong><ul>";
          u.teacher_assignments.forEach((a) => {
            html += `<li>${a.class_name} - ${a.section_name} (${a.subject_name})</li>`;
          });
          html += "</ul>";
          roleDetails.append(html);
        }
      } else if (u.role_name === "Parent") {
        roleDetails.append(`
          <p><strong>Father:</strong> ${u.father_name || ""}</p>
          <p><strong>Mother:</strong> ${u.mother_name || ""}</p>
          <p><strong>Relation:</strong> ${u.relation || ""}</p>
          <p><strong>Address:</strong> ${u.parent_address || ""}</p>
        `);
      } else if (u.role_name === "Accountant") {
        roleDetails.append(`
          <p><strong>Salary:</strong> ${u.salary || ""}</p>
          <p><strong>Bank Account:</strong> ${u.bank_account || ""}</p>
        `);
      }

      // Attach delete button action
      $("#btnDeleteUser")
        .off("click")
        .on("click", function () {
          if (
            !confirm("Are you sure you want to delete this user permanently?")
          )
            return;

          apiPost(
            { action: "delete_user", user_id: userId },
            function (delRes) {
              if (delRes.status === "success") {
                alert("User deleted successfully.");
                loadManageUserPage();
              } else {
                alert(delRes.message || "Failed to delete user");
              }
            }
          );
        });
    });
  });
}

// Helper
function showError(page) {
  return function (status) {
    if (status === "error") {
      $(".dash-area").html(
        `<div class="text-center p-5 text-danger">Failed to load ${page} page.</div>`
      );
    }
  };
}

function loadUsersPage() {
  // show temporary loader
  $(".dash-area").html(
    '<div class="text-center p-5 text-muted">Loading users...</div>'
  );

  // Load the users.php skeleton (includes table + modal HTML)
  $.get("users.php")
    .done(function (html) {
      $(".dash-area").html(html);
      // After skeleton is placed in DOM, fetch users data and render rows
      fetchUsers();
    })
    .fail(function () {
      $(".dash-area").html(
        '<div class="text-center p-5 text-danger">Failed to load Users UI.</div>'
      );
    });
}
function loadTeachersPage() {
  $(".dash-area").html(
    '<div class="text-center p-5 text-muted">Loading Teachers List...</div>'
  );
  $(".dash-area").load("users/teachers.php", function () {
    // ✅ Init after teachers.php loads
    let filters = { branch_id: "", search: "", page: 1 };
    loadBranchesFilter();
    loadTeachers(filters);

    $("#branchFilter")
      .off("change")
      .on("change", function () {
        filters.branch_id = $(this).val();
        filters.page = 1;
        loadTeachers(filters);
      });

    $("#teacherSearch")
      .off("keyup")
      .on("keyup", function () {
        filters.search = $(this).val();
        filters.page = 1;
        loadTeachers(filters);
      });

    $(document)
      .off("click", "#teachersPagination a")
      .on("click", "#teachersPagination a", function (e) {
        e.preventDefault();
        filters.page = parseInt($(this).data("page"));
        loadTeachers(filters);
      });
  });
}

/* =====================================================
   4. Init functions
   ===================================================== */

function initStudentTable() {
  if ($.fn.DataTable) {
    let pageLength = $(window).width() < 576 ? 5 : 10;
    let scrollY = $(window).width() < 576 ? "200px" : "300px";
    let table = $("#studentsTable").DataTable({
      destroy: true,
      pageLength: pageLength,
      lengthMenu: [5, 10, 25, 50],
      ordering: true,
      searching: true,
      responsive: true,
      autoWidth: false,
      scrollY: scrollY,
      scrollX: true,
      scrollCollapse: true,
      pagingType: "simple_numbers",
      language: {
        search: "",
        searchPlaceholder: "Search...",
        lengthMenu: "_MENU_",
        paginate: { previous: "‹", next: "›" },
      },
      dom: "lfrtip",
    });

    // 🔥 Move search + length into header
    let $search = $("#studentsTable_filter");
    let $length = $("#studentsTable_length");

    if ($(".card-header").length) {
      let $header = $(".card-header");
      $header.find("#studentsTable_filter, #studentsTable_length").remove(); // ✅ remove existing controls
      let $rightControls = $(
        '<div class="d-flex align-items-center gap-3"></div>'
      );
      if ($length.length) $rightControls.append($length);
      if ($search.length) $rightControls.append($search);
      $header.append($rightControls);
    }
  } else {
    console.error("❌ DataTable plugin not loaded");
  }
}
function loadRecentAdmissions() {
  apiPost({ action: "get_recent_admissions" }, function (res) {
    if (res.status !== "success") {
      $("#studentsTable tbody").html(
        `<tr><td colspan="4" class="text-center text-danger">Error loading recent admissions</td></tr>`
      );
      return;
    }

    let rows = "";
    if (res.data.length === 0) {
      rows = `<tr><td colspan="4" class="text-center text-muted">No recent admissions</td></tr>`;
    } else {
      res.data.forEach((stu) => {
        rows += `
          <tr>
            <td>${stu.fullname || "-"}</td>
            <td>${stu.class_name || "-"}</td>
            <td>${stu.section_name || "-"}</td>
            <td>${stu.admission_date || "-"}</td>
          </tr>
        `;
      });
    }

    $("#studentsTable tbody").html(rows);

    // Initialize DataTable after populating
    initStudentTable();
  });
}

// $(window).resize(function () {
//   initStudentTable();
// });

function initSidebarSearch() {
  const $search = $("#navSearch");
  // const $results = $("<div id='searchResults' class='search-results'></div>");
  const $results = $("#searchResults");
  // $search.after($results);

  // Live search
  $search.on("input", function () {
    let query = $(this).val().toLowerCase().trim();
    $results.empty();

    if (!query) return;

    let matches = [];
    $(".side-item[data-page]").each(function () {
      let text = $(this).text().trim().toLowerCase();
      let page = $(this).data("page");
      if (text.includes(query)) {
        matches.push({ text: $(this).text().trim(), page: page });
      }
    });

    if (matches.length) {
      // let $list = $("<ul class='list-unstyled m-0'></ul>");
      let $list = $("<ul></ul>");
      matches.forEach((m) => {
        $list.append(
          $("<li></li>").text(m.text).attr("data-page", m.page)
          // .addClass("p-2 search-item")
        );
      });
      $results.append($list);
    }
  });

  // Click on search result
  $results.on("click", "li", function () {
    let page = $(this).data("page");

    // Close search results
    $results.empty();
    $search.val("");

    // Simulate sidebar click
    let $target = $(".side-item[data-page='" + page + "']");

    if ($target.length) {
      // Expand parent if inside collapse
      let $parentCollapse = $target.closest(".collapse");
      if ($parentCollapse.length) {
        $parentCollapse.addClass("show");
        $parentCollapse.prev(".side-item").attr("aria-expanded", "true");
      }

      $(".side-item").removeClass("active");
      $target.addClass("active");

      history.pushState(
        { page: page },
        "",
        "?page=" + encodeURIComponent(page)
      );
      routePage(page);
    }
  });

  // Hide results if clicked outside
  $(document).on("click", function (e) {
    if (!$(e.target).closest("#navSearch, #searchResults").length) {
      $results.empty();
    }
  });
}
/* =====================================================
   5.  Popolate Data
   ===================================================== */

// Populate Branches
function loadBranches() {
  $.ajax({
    type: "POST",
    url: "common/functions.php",
    data: { action: "get_branches" },
    dataType: "json",
    success: function (res) {
      if (res.status === "success") {
        let options =
          '<option value="" disabled selected>Select Branch</option>';
        res.data.forEach((b) => {
          options += `<option value="${b.branch_id}">${b.branch_name}</option>`;
        });
        $("#branch_id").html(options);
      } else {
        console.error("Error:", res.message);
      }
    },
    error: function (xhr, status, error) {
      console.error("AJAX Error:", status, error);
    },
  });
}

// Populate Roles
function loadRoles() {
  $.ajax({
    type: "POST",
    url: "common/functions.php",
    data: { action: "get_roles" },
    dataType: "json",
    success: function (res) {
      if (res.status === "success") {
        let options = '<option value="" disabled selected>Select Role</option>';
        res.data.forEach((r) => {
          options += `<option value="${r.role_id}">${r.role_name}</option>`;
        });
        $("#roleSelect").html(options);
      } else {
        console.error("Error:", res.message);
      }
    },
    error: function (xhr, status, error) {
      console.error("AJAX Error:", status, error);
    },
  });
}

// Branch → Classes
$(document).on("change", "#branch_id", function () {
  let branch_id = $(this).val();
  if (!branch_id) return;
  $.post(
    "common/functions.php",
    { action: "get_classes", branch_id },
    function (res) {
      if (res.status === "success") {
        let options =
          '<option value="" disabled selected>Select Class</option>';
        res.data.forEach((c) => {
          options += `<option value="${c.class_id}">${c.class_name}</option>`;
        });
        $("#class_id, #assigned_classes").html(options);
      }
    },
    "json"
  );
});

// Branch → subjects
// $(document).on("change", "#branch_id", function () {
//   let branch_id = $(this).val();
//   if (!branch_id) return;
//   $.post(
//     "common/functions.php",
//     { action: "get_subjects", branch_id },
//     function (res) {
//       if (res.status === "success") {
//         let options = "";
//         res.data.forEach((s) => {
//           options += `<option value="${s.subject_id}">${s.subject_name}</option>`;
//         });
//         $("#assigned_subjects").html(options);
//       } else {
//         console.error("Error:", res.message);
//       }
//     },
//     "json"
//   );
// });
//end

// Class → Sections
$(document).on("change", "#class_id", function () {
  let class_id = $(this).val();
  if (!class_id) return;
  $.post(
    "common/functions.php",
    { action: "get_sections", class_id },
    function (res) {
      if (res.status === "success") {
        let options =
          '<option value="" disabled selected>Select Section</option>';
        res.data.forEach((s) => {
          options += `<option value="${s.section_id}">${s.section_name}</option>`;
        });
        $("#section_id").html(options);
      }
    },
    "json"
  );
});

// Populate Parents
function loadParents() {
  $.ajax({
    type: "POST",
    url: "common/functions.php",
    data: { action: "get_parents" },
    dataType: "json",
    success: function (res) {
      if (res.status === "success") {
        let options =
          '<option value="" disabled selected>Select Parent</option>';
        res.data.forEach((p) => {
          options += `<option value="${p.parent_id}">${p.fullname} (${p.phone})</option>`;
        });
        $("#parent_id").html(options);
        $("#parent_id").select2({
          placeholder: "Search Parent...",
          allowClear: true,
          width: "100%",
        });
      } else {
        console.error("Error:", res.message);
      }
    },
    error: function (xhr, status, error) {
      console.error("AJAX Error:", status, error);
    },
  });
}

// manage users
function loadBranchesFilter() {
  $.post(
    "common/functions.php",
    { action: "get_branches" },
    function (res) {
      if (res.status === "success" && res.data) {
        $("#branchFilter").html('<option value="">All Branches</option>');
        res.data.forEach((branch) => {
          $("#branchFilter").append(
            `<option value="${branch.branch_id}">${branch.branch_name}</option>`
          );
        });
      }
    },
    "json"
  );
}

function loadUsers(filters = {}) {
  //show loader
  $("#usersTable tbody").html(
    `<tr><td colspan="10" class="text-center text-muted">Loading...</td></tr>`
  );

  $.post(
    "common/functions.php",
    {
      action: "get_users",
      role_id: filters.role_id || "",
      branch_id: filters.branch_id || "",
      search: filters.search || "",
      page: filters.page || 1,
    },
    function (res) {
      if (res.status === "success") {
        let rows = "";
        if (res.data.users.length === 0) {
          rows = `<tr><td colspan="10" class="text-center text-muted">No users found</td></tr>`;
        } else {
          let startNo = (res.data.currentPage - 1) * 10;
          res.data.users.forEach((user, index) => {
            let serialNo = startNo + index + 1;
            let statusText = user.status ? user.status : "active";
            rows += `
                    <tr>
                        <td>${serialNo}</td>
                        <td>${user.fullname || "-"}</td>
                        <td>${user.username || "-"}</td>
                        <td>${user.email || "-"}</td>
                        <td>${user.phone || "-"}</td>
                        <td>${user.branch_name || "-"}</td>
                        <td>${user.class_name || "-"}</td>
                        <td>${user.section_name || "-"}</td>
                        <td>${user.role_name || "-"}</td>
                        <td>${
                          statusText.charAt(0).toUpperCase() +
                          statusText.slice(1)
                        }</td>
                        <td>
                        <button class="btn btn-sm btn-secondary edit-user" data-id="${
                          user.user_id
                        }" data-page="users/edit">Edit</button>
                        <button class="btn btn-sm btn-danger delete-user" data-id="${
                          user.user_id
                        }" data-page="users/delete">Delete</button>
                        </td>
                        </tr>
                        `;
          });
        }
        $("#usersTable tbody").html(rows);

        // <!--<td>${user.status}</td>-->
        // Pagination
        let pag = "";
        const total = res.data.totalPages;
        const page = res.data.currentPage;
        if (total > 1) {
          if (page > 1)
            pag += `<li class="page-item"><a class="page-link" href="#" data-page="${
              page - 1
            }">&laquo;</a></li>`;
          for (let i = 1; i <= total; i++) {
            if (i === 1 || i === total || (i >= page - 1 && i <= page + 1)) {
              pag += `<li class="page-item ${i === page ? "active" : ""}">
                <a class="page-link" href="#" data-page="${i}">${i}</a>
              </li>`;
            } else if (i === 2 && page > 3) {
              pag += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
            } else if (i === total - 1 && page < total - 2) {
              pag += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
            }
          }
          if (page < total)
            pag += `<li class="page-item"><a class="page-link" href="#" data-page="${
              page + 1
            }">&raquo;</a></li>`;
        }
        $("#usersPagination").html(pag);
      } else {
        $("#usersTable tbody").html(
          `<tr><td colspan="10" class="text-center text-danger">Error loading users</td></tr>`
        );
      }
    },
    "json"
  );
}

function loadTeachers(filters = {}) {
  //show loader
  $("#teachersTable tbody").html(
    `<tr><td colspan="10" class="text-center text-muted">Loading...</td></tr>`
  );
  $.post(
    "common/functions.php",
    {
      action: "get_teachers",
      branch_id: filters.branch_id || "",
      search: filters.search || "",
      page: filters.page || 1,
    },
    function (res) {
      if (res.status === "success") {
        let rows = "";
        if (res.data.users.length === 0) {
          rows = `<tr><td colspan="10" class="text-center text-muted">No teachers found</td></tr>`;
        } else {
          let startNo = (res.data.currentPage - 1) * 10;
          res.data.users.forEach((user, index) => {
            let serialNo = startNo + index + 1;
            let statusText = user.status ? user.status : "active";
            let classBadges = user.class_name
              ? user.class_name
                  .split(",")
                  .map(
                    (c) =>
                      `<span class="badge badge-info mr-1">${c.trim()}</span>`
                  )
                  .join(" ")
              : "-";

            let sectionBadges = user.section_name
              ? user.section_name
                  .split(",")
                  .map(
                    (s) =>
                      `<span class="badge badge-secondary mr-1">${s.trim()}</span>`
                  )
                  .join(" ")
              : "-";

            let subjectBadges = user.subjects
              ? user.subjects
                  .split(",")
                  .map(
                    (sub) =>
                      `<span class="badge badge-primary mr-1">${sub.trim()}</span>`
                  )
                  .join(" ")
              : "-";
            rows += `
                    <tr>
                        <td>${serialNo}</td>
                        <td>${user.fullname || "-"}</td>
                        <td>${user.email || "-"}</td>
                        <td>${user.phone || "-"}</td>
                        <td>${user.branch_name || "-"}</td>
                        <td>${classBadges}</td>
                        <td>${sectionBadges}</td>
                        <td>${subjectBadges}</td>
                        <td>${
                          statusText.charAt(0).toUpperCase() +
                          statusText.slice(1)
                        }</td>
                        <td>
                        <button class="btn btn-sm btn-secondary edit-user" data-id="${
                          user.user_id
                        }" data-page="users/edit">Edit</button>
                        <button class="btn btn-sm btn-danger delete-user" data-id="${
                          user.user_id
                        }" data-page="users/delete">Delete</button>
                        </td>
                        </tr>
                        `;
          });
        }
        $("#teachersTable tbody").html(rows);

        // Pagination
        let pag = "";
        const total = res.data.totalPages;
        const page = res.data.currentPage;
        if (total > 1) {
          if (page > 1)
            pag += `<li class="page-item"><a class="page-link" href="#" data-page="${
              page - 1
            }">&laquo;</a></li>`;
          for (let i = 1; i <= total; i++) {
            if (i === 1 || i === total || (i >= page - 1 && i <= page + 1)) {
              pag += `<li class="page-item ${i === page ? "active" : ""}">
                <a class="page-link" href="#" data-page="${i}">${i}</a>
              </li>`;
            } else if (i === 2 && page > 3) {
              pag += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
            } else if (i === total - 1 && page < total - 2) {
              pag += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
            }
          }
          if (page < total)
            pag += `<li class="page-item"><a class="page-link" href="#" data-page="${
              page + 1
            }">&raquo;</a></li>`;
        }
        $("#teachersPagination").html(pag);
      } else {
        $("#teachersTable tbody").html(
          `<tr><td colspan="10" class="text-center text-danger">Error loading users</td></tr>`
        );
      }
    },
    "json"
  );
}

// //////////////////////////////////////////////////////////////////////////////edit user
// admin-users-edit.js — include after jQuery is loaded
// Helper to call common/functions.php
function apiPost(payload, cb) {
  $.post(
    "common/functions.php",
    payload,
    function (res) {
      if (typeof cb === "function") cb(res);
    },
    "json"
  ).fail(function () {
    alert("Server error");
  });
}

// Loaders with callbacks (populate <select> and call cb after done)
function loadEndBranches(cb) {
  apiPost({ action: "get_branches" }, function (res) {
    if (res.status !== "success") return cb && cb();
    const sel = $("#branch_id");
    sel.empty().append('<option value="">Select Branch</option>');
    res.data.forEach(function (b) {
      sel.append(`<option value="${b.branch_id}">${b.branch_name}</option>`);
    });
    if (cb) cb();
  });
}

function loadClasses(branch_id, cb) {
  apiPost({ action: "get_classes", branch_id: branch_id }, function (res) {
    const sel = $("#student_class_id");
    sel.empty().append('<option value="">Select Class</option>');
    if (res.status === "success") {
      res.data.forEach(function (r) {
        sel.append(`<option value="${r.class_id}">${r.class_name}</option>`);
      });
    }
    // if (cb) cb();
    if (typeof cb === "function") cb(res);
  });
}

function loadSections(class_id, cb, targetSelector = "#student_section_id") {
  apiPost({ action: "get_sections", class_id: class_id }, function (res) {
    const sel = $(targetSelector);
    sel.empty().append('<option value="">Select Section</option>');
    if (res.status === "success") {
      res.data.forEach(function (r) {
        sel.append(
          `<option value="${r.section_id}">${r.section_name}</option>`
        );
      });
    }
    // if (cb) cb();
    if (typeof cb === "function") cb(res);
  });
}

// function loadAllParents(cb) {
//   apiPost({ action: "get_parents" }, function (res) {
//     const sel = $("#student_parent_id");
//     sel.empty().append('<option value="">Select Parent</option>');
//     if (res.status === "success") {
//       res.data.forEach(function (r) {
//         sel.append(
//           `<option value="${r.parent_id}">${
//             r.name || "Parent " + r.parent_id
//           }</option>`
//         );
//       });
//     }
//     // if (cb) cb();
//     if (typeof cb === "function") cb(res);
//   });
// }

function loadAllParents(cb) {
  apiPost({ action: "get_parents" }, function (res) {
    const sel = $("#student_parent_id");
    sel.empty().append('<option value="">Select Parent</option>');
    if (res.status === "success") {
      res.data.forEach(function (p) {
        sel.append(
          `<option value="${p.parent_id}">${p.fullname} (${p.phone})</option>`
        );
      });

      // Initialize Select2
      sel.select2({
        placeholder: "Search Parent...",
        allowClear: true,
        width: "100%",
      });
    }

    if (typeof cb === "function") cb(res);
  });
}

// function loadSubjects(class_id, cb, targetSelector = null) {
//   console.log("DEBUG: Calling get_subjects with class_id=", class_id);
//   apiPost({ action: "get_subjects", class_id: class_id }, function (res) {
//     if (!targetSelector) {
//       if (typeof cb === "function") cb(res);
//       return;
//     }
//     const sel = $(targetSelector);
//     sel.empty().append('<option value="">Select Subject</option>');
//     if (res.status === "success") {
//       res.data.forEach(function (r) {
//         sel.append(
//           `<option value="${r.subject_id}">${r.subject_name}</option>`
//         );
//       });
//     }
//     // if (cb) cb();
//     if (typeof cb === "function") cb(res);
//   });
// }

function loadSubjects(class_id, cb, targetSelector = null) {
  console.log("DEBUG: Calling get_subjects with class_id=", class_id);

  apiPost({ action: "get_subjects", class_id: class_id }, function (res) {
    console.log("DEBUG: get_subjects response", res);

    if (!targetSelector) {
      if (typeof cb === "function") cb(res);
      return;
    }
    const sel =
      targetSelector instanceof jQuery ? targetSelector : $(targetSelector);
    sel.empty().append('<option value="">Select Subject</option>');
    if (res.status === "success") {
      res.data.forEach(function (r) {
        sel.append(
          `<option value="${r.subject_id}">${r.subject_name}</option>`
        );
      });
    }
    if (typeof cb === "function") cb(res);
  });
}

// Template for teacher mapping row
function makeTeacherRow() {
  const row = $(`
  <div class="teacher-mapping-row-edit form-row align-items-end mb-2">
    <div class="form-group col-md-4">
      <label>Class</label>
      <select name="teacher_class_edit[]" class="form-control teacher-class-edit"><option value="">Select Class</option></select>
    </div>
    <div class="form-group col-md-4">
      <label>Section</label>
      <select name="teacher_section_edit[]" class="form-control teacher-section-edit"><option value="">Select Section</option></select>
    </div>
    <div class="form-group col-md-3">
      <label>Subject</label>
      <select name="teacher_subject_edit[]" class="form-control teacher-subject-edit"><option value="">Select Subject</option></select>
    </div>
    <div class="form-group col-md-1 d-flex align-items-end">
      <button type="button" class="btn btn-danger btn-sm removeTeacherMappingEdit">×</button>
    </div>
  </div>`);
  // bind change: when class changes, reload sections & subjects for this row
  row.on("change", ".teacher-class-edit", function () {
    const cls = $(this).val();
    const r = $(this).closest(".teacher-mapping-row-edit");
    loadSections(cls, function () {}, r.find(".teacher-section-edit"));
    loadSubjects(cls, function () {}, r.find(".teacher-subject-edit"));
  });
  return row;
}

// Add mapping button
$(document).on("click", "#addTeacherMappingEdit", function () {
  const row = makeTeacherRow();
  $("#teacherAssignmentsContainerEdit").append(row);
  // populate classes for this row then sections/subjects remain empty until class chosen
  loadClasses($("#branch_id").val(), function () {
    // also populate class select in row with all options (copy from student_class_id)
    const options = $("#student_class_id").html();
    row.find(".teacher-class-edit").html(options);
  });
});

// Remove mapping
$(document).on("click", ".removeTeacherMappingEdit", function () {
  $(this).closest(".teacher-mapping-row-edit").remove();
  if ($(".teacher-mapping-row-edit").length < 1) {
    alert("At least one mapping is required.");
    $("#addTeacherMappingEdit").trigger("click");
  }
});

// Main loader: use the same interface you had but with safe chaining
function loadEditUserPage(params) {
  $(".dash-area").html(
    '<div class="text-center p-5 text-muted">Loading Edit User...</div>'
  );

  apiPost({ action: "get_role_constants" }, function (roleRes) {
    if (roleRes.status !== "success") {
      alert("Failed to load role constants");
      return;
    }
    const STUDENT_ROLE_ID = roleRes.data.STUDENT;
    const TEACHER_ROLE_ID = roleRes.data.TEACHER;
    const PARENT_ROLE_ID = roleRes.data.PARENT;
    const ACCOUNTANT_ROLE_ID = roleRes.data.ACCOUNTANT;

    $(".dash-area").load("users/edit.php", function () {
      const userId = params.get("user_id");
      if (!userId) {
        alert("Invalid user ID");
        return;
      }

      // toggler (keeps required attributes tidy)
      function toggleRoleFields(role) {
        $(
          ".role-fields input, .role-fields select, .role-fields textarea"
        ).prop("required", false);
        $(".role-fields").addClass("d-none");

        if (role == STUDENT_ROLE_ID) {
          $("#studentFields").removeClass("d-none");
          $("#student_class_id, #student_section_id").prop("required", true);
        } else if (role == TEACHER_ROLE_ID) {
          $("#teacherFieldsEdit").removeClass("d-none");
        } else if (role == ACCOUNTANT_ROLE_ID) {
          $("#accountantFields").removeClass("d-none");
          $("#salary, #bank_account").prop("required", true);
        } else if (role == PARENT_ROLE_ID) {
          $("#parentFields").removeClass("d-none");
        }
      }

      // populate role select read-only (you might prefer to prefill all roles but keep disabled)
      apiPost({ action: "get_role_constants" }, function (rres) {
        if (rres.status === "success") {
          // create roleSelect options (but keep disabled)
          $("#roleSelect")
            .empty()
            .append('<option value="">Select Role</option>');
          Object.keys(rres.data).forEach(function (k) {
            const id = rres.data[k];
            $("#roleSelect").append(`<option value="${id}">${k}</option>`);
          });
        }
      });

      // fetch user
      apiPost({ action: "get_user", user_id: userId }, function (res) {
        if (res.status !== "success") {
          alert(res.message);
          return;
        }
        const u = res.data;
        $("#form_user_id").val(u.user_id || "");
        $("#fullname").val(u.fullname || "");
        $("#username").val(u.username || "");
        $("#email").val(u.email || "");
        $("#phone").val(u.phone || "");

        // Role select (show only)
        $("#roleSelect")
          .html(
            `<option value="${u.role_id}" selected>${
              u.role_name || ""
            }</option>`
          )
          .prop("readonly", true);

        // Branch: load branches then set value
        loadEndBranches(function () {
          $("#branch_id").val(u.branch_id || "");
          // .trigger("change");
        });

        // Toggle relevant fieldsets
        toggleRoleFields(u.role_id);

        // STUDENT: chain class->section populate then set values
        // if (parseInt(u.role_id) === STUDENT_ROLE_ID) {
        //   $("#studentFields").removeClass("d-none");

        //   loadClasses(u.branch_id, function () {
        //     // set class
        //     $("#student_class_id").val(u.class_id || "");
        //     // .trigger("change");

        //     // now load sections for that class and set
        //     loadSections(u.class_id, function () {
        //       $("#student_section_id").val(u.section_id || "");
        //     });
        //   });

        //   // parents
        //   loadAllParents(function () {
        //     if (u.parent_id) {
        //       $("#student_parent_id").val(u.parent_id).trigger("change");
        //     }
        //   });

        //   $("#dob").val(u.dob || "");
        //   $("#gender").val(u.gender || "");
        //   $("#admission_date").val(u.admission_date || "");
        //   $("#aadhar_no").val(u.aadhar_no || "");
        //   $("#blood_group").val(u.blood_group || "");
        //   $("#student_address").val(u.student_address || "");
        //   $("#transport_required").val(u.transport_required || "no");
        //   $("#hostel_required").val(u.hostel_required || "no");

        //   if (u.photo) {
        //     $("#photoPreview").attr("src", u.photo).show();
        //     $("#removePhoto").removeClass("d-none");
        //   } else {
        //     $("#photoPreview").attr("src", "./assets/images/admin-avatar.png");
        //     $("#removePhoto").addClass("d-none");
        //   }
        // }
        // STUDENT: chain class->section populate then set values
        if (parseInt(u.role_id) === STUDENT_ROLE_ID) {
          console.log(
            "DEBUG: Student role detected, loading fields for user",
            u
          );

          $("#studentFields").removeClass("d-none");

          loadClasses(u.branch_id, function () {
            const classSel = $("#student_class_id");
            classSel.val(u.class_id || "");
            console.log("DEBUG: Class set to", u.class_id);

            // Load sections for the class
            loadSections(u.class_id, function (res) {
              const sectionSel = $("#student_section_id");
              console.log("DEBUG: Sections loaded", res.data);

              // Show current options in the select
              console.log(
                "DEBUG: Current section options:",
                sectionSel
                  .find("option")
                  .map(function () {
                    return { value: this.value, text: this.text };
                  })
                  .get()
              );

              // Ensure the option exists before setting
              if (u.section_id) {
                const optionExists =
                  sectionSel.find(`option[value="${u.section_id}"]`).length > 0;
                console.log(
                  "DEBUG: Does section exist?",
                  optionExists,
                  "section_id:",
                  u.section_id
                );

                if (optionExists) {
                  sectionSel.val(u.section_id);
                  console.log("DEBUG: Section prefill set to", u.section_id);

                  // If using Select2, trigger change AFTER setting val
                  if (sectionSel.hasClass("select2-hidden-accessible")) {
                    sectionSel.trigger("change");
                    console.log("DEBUG: Triggered Select2 change");
                  }
                } else {
                  console.warn(
                    "DEBUG: Section option not found, cannot prefill"
                  );
                }
              } else {
                console.warn("DEBUG: No section_id to prefill");
              }
            });
          });

          // Load parents
          loadAllParents(function () {
            if (u.parent_id) {
              $("#student_parent_id").val(u.parent_id).trigger("change");
              console.log("DEBUG: Parent prefill set to", u.parent_id);
            }
          });

          // Other fields
          $("#dob").val(u.dob || "");
          $("#gender").val(u.gender || "");
          $("#admission_date").val(u.admission_date || "");
          $("#aadhar_no").val(u.aadhar_no || "");
          $("#blood_group").val(u.blood_group || "");
          $("#student_address").val(u.student_address || "");
          $("#transport_required").val(u.transport_required || "no");
          $("#hostel_required").val(u.hostel_required || "no");

          if (u.photo) {
            $("#photoPreview").attr("src", u.photo).show();
            $("#removePhoto").removeClass("d-none");
          } else {
            $("#photoPreview").attr("src", "./assets/images/admin-avatar.png");
            $("#removePhoto").addClass("d-none");
          }
        }

        // PARENT
        if (parseInt(u.role_id) === PARENT_ROLE_ID) {
          $("#parentFields").removeClass("d-none");
          $("#father_name").val(u.father_name || "");
          $("#mother_name").val(u.mother_name || "");
          $("#relation").val(u.relation || "father");
          $("#occupation").val(u.occupation || "");
          $("#parent_salary").val(u.parent_salary || "");
          $("#alt_phone").val(u.alt_phone || "");
          $("#alt_email").val(u.alt_email || "");
          $("#parent_address").val(u.parent_address || "");
        }

        // TEACHER
        // if (parseInt(u.role_id) === TEACHER_ROLE_ID) {
        //   console.log("DEBUG: Teacher role detected, loading assignments…");
        //   $("#teacherFieldsEdit").removeClass("d-none");
        //   $("#teacherAssignmentsContainerEdit").empty();

        //   const assignments = Array.isArray(u.teacher_assignments)
        //     ? u.teacher_assignments
        //     : [];

        //   if (assignments.length === 0) {
        //     console.log("DEBUG: No assignments found, creating empty row");
        //     const row = makeTeacherRow();
        //     $("#teacherAssignmentsContainerEdit").append(row);

        //     loadClasses(u.branch_id, function () {
        //       const options = $("#student_class_id").html();
        //       row.find(".teacher-class-edit").html(options);
        //     });
        //   } else {
        //     assignments.forEach(function (a, idx) {
        //       console.log(`DEBUG: Processing assignment #${idx}`, a);

        //       const row = makeTeacherRow();
        //       $("#teacherAssignmentsContainerEdit").append(row);

        //       // Load classes
        //       loadClasses(u.branch_id, function () {
        //         const options = $("#student_class_id").html();
        //         row.find(".teacher-class-edit").html(options);
        //         row.find(".teacher-class-edit").val(a.class_id || "");
        //         console.log(`DEBUG: Row ${idx} - class set to`, a.class_id);

        //         // Load sections for that class
        //         loadSections(
        //           a.class_id,
        //           function (resSec) {
        //             row.find(".teacher-section-edit").val(a.section_id || "");
        //             console.log(
        //               `DEBUG: Row ${idx} - section set to`,
        //               a.section_id
        //             );
        //           },
        //           row.find(".teacher-section-edit")
        //         );
        //       });

        //       // Instead of calling get_subjects, use subject from API directly
        //       const subSel = row.find(".teacher-subject-edit");
        //       subSel.empty().append('<option value="">Select Subject</option>');
        //       if (a.subject_id && a.subject_name) {
        //         subSel.append(
        //           `<option value="${a.subject_id}" selected>${a.subject_name}</option>`
        //         );
        //       }
        //       console.log(
        //         `DEBUG: Row ${idx} - subject set directly`,
        //         a.subject_id
        //       );
        //     });
        //   }
        // }

        // TEACHER
        if (parseInt(u.role_id) === TEACHER_ROLE_ID) {
          console.log("DEBUG: Teacher role detected, loading assignments…");
          $("#teacherFieldsEdit").removeClass("d-none");
          $("#teacherAssignmentsContainerEdit").empty();

          const assignments = Array.isArray(u.teacher_assignments)
            ? u.teacher_assignments
            : [];

          if (assignments.length === 0) {
            console.log("DEBUG: No assignments found, creating empty row");
            const row = makeTeacherRow();
            $("#teacherAssignmentsContainerEdit").append(row);

            loadClasses(u.branch_id, function () {
              const options = $("#student_class_id").html();
              row.find(".teacher-class-edit").html(options);
            });
          } else {
            assignments.forEach(function (a, idx) {
              console.log(`DEBUG: Processing assignment #${idx}`, a);

              const row = makeTeacherRow();
              $("#teacherAssignmentsContainerEdit").append(row);

              // Load classes
              loadClasses(u.branch_id, function () {
                const options = $("#student_class_id").html();
                row.find(".teacher-class-edit").html(options);
                row.find(".teacher-class-edit").val(a.class_id || "");
                console.log(`DEBUG: Row ${idx} - class set to`, a.class_id);

                // Load sections for that class
                loadSections(
                  a.class_id,
                  function (resSec) {
                    row.find(".teacher-section-edit").val(a.section_id || "");
                    console.log(
                      `DEBUG: Row ${idx} - section set to`,
                      a.section_id
                    );
                  },
                  row.find(".teacher-section-edit")
                );

                // Load subjects from DB + select the one from assignment
                loadSubjects(
                  a.class_id,
                  function (resSub) {
                    const subSel = row.find(".teacher-subject-edit");
                    subSel
                      .empty()
                      .append('<option value="">Select Subject</option>');

                    if (resSub && resSub.status === "success") {
                      resSub.data.forEach(function (s) {
                        subSel.append(
                          `<option value="${s.subject_id}">${s.subject_name}</option>`
                        );
                      });
                    }
                    // // Ensure assigned subject is in dropdown (even if DB doesn't return it)
                    if (a.subject_id && a.subject_name) {
                      if (
                        subSel.find(`option[value="${a.subject_id}"]`)
                          .length === 0
                      ) {
                        subSel.append(
                          `<option value="${a.subject_id}">${a.subject_name}</option>`
                        );
                      }
                      subSel.val(a.subject_id);
                    }
                    // Select the saved subject if exists
                    if (a.subject_id) {
                      subSel.val(a.subject_id);
                    }
                  },
                  row.find(".teacher-subject-edit")
                );

                console.log(`DEBUG: Row ${idx} - subject set to`, a.subject_id);
                // },
                // row.find(".teacher-subject-edit")
                // );
              });
            });
          }
        }

        // ACCOUNTANT
        if (parseInt(u.role_id) === ACCOUNTANT_ROLE_ID) {
          $("#accountantFields").removeClass("d-none");
          $("#salary").val(u.salary || "");
          $("#bank_account").val(u.bank_account || "");
        }
      });
    });
  });
}

// // Form submit (update_user)
// $(document).on("submit", "#editUserForm", function (e) {
//   e.preventDefault();
//   const form = $(this)[0];
//   const fd = new FormData(form);
//   fd.append("action", "update_user");

//   $.ajax({
//     url: "common/functions.php",
//     data: fd,
//     type: "POST",
//     processData: false,
//     contentType: false,
//     success: function (resp) {
//       if (resp.status === "success") {
//         alert("User updated.");
//         // optionally reload table or redirect
//       } else {
//         alert("Update failed: " + resp.message);
//       }
//     },
//     error: function () {
//       alert("Server error");
//     },
//   });
// });

/* =====================================================
   6. Route Page
   ===================================================== */

function routePage(fullPage) {
  // Split fullPage into page + params
  let [page, queryString] = fullPage.split("?");
  let params = new URLSearchParams(queryString || "");

  // Route to correct loader
  if (page === "dashboard") {
    loadDashboardPage();
  } else if (page === "users") {
    loadUsersPage();
  } else if (page === "users/add") {
    loadAddUserPage();
  } else if (page === "users/manage") {
    loadManageUserPage();
  } else if (page === "users/edit") {
    loadEditUserPage(params);
  } else if (page === "users/view") {
    loadViewUserPage();
  } else if (page === "users/delete") {
    loadDeleteUserPage(params);
  } else if (page === "teachers") {
    loadTeachersPage();
  } else if (page === "teachers/add") {
    loadAddUserPage();
  } else {
    $(".dash-area").load(page + ".php", function (status) {
      if (status === "error") {
        $(".dash-area").html(
          '<div class="text-center p-5 text-danger">Page not found</div>'
        );
      }
    });
  }
}
$(document).on("click", ".add-teacher-btn", function (e) {
  e.preventDefault();
  let page = $(this).data("page");
  history.pushState({ page: page }, "", "?page=" + encodeURIComponent(page));
  routePage(page);
});

/* =====================================================
   7. DOM Ready Initializations
   ===================================================== */

$(document).ready(function () {
  $(document).on("click", ".side-item", function (e) {
    e.preventDefault();

    // Active UI
    $(".side-item").removeClass("active");
    $(this).addClass("active");

    // if clicked item is inside submenu, expand its parent
    let $parentCollapse = $(this).closest(".collapse");
    if ($parentCollapse.length) {
      $parentCollapse.addClass("show");
      $parentCollapse.prev(".side-item").attr("aria-expanded", "true");
    }

    // identify page: prefer data-page attribute, fallback to id text
    let page = ($(this).data("page") || "").toLowerCase();

    history.pushState({ page: page }, "", "?page=" + encodeURIComponent(page));

    // Route
    routePage(page);
  });

  // On initial load restore last active page (default: dashboard)
  (function restoreActive() {
    let urlParams = new URLSearchParams(window.location.search);
    let fullPage = urlParams.get("page") || "dashboard";

    // if no page param in URL, push default dashboard
    if (!urlParams.get("page")) {
      history.replaceState({ page: "dashboard" }, "", "?page=dashboard");
    }

    $(".side-item").removeClass("active");

    let basePage = fullPage.split("?")[0];
    let $target = $(".side-item[data-page='" + basePage + "']");
    $target.addClass("active");

    // if it's inside a collapsed menu, open it
    let $parentCollapse = $target.closest(".collapse");
    if ($parentCollapse.length) {
      $parentCollapse.addClass("show");
      $parentCollapse.prev(".side-item").attr("aria-expanded", "true");
    }

    routePage(fullPage);
  })();

  //Handle browser back/forward
  window.onpopstate = function (event) {
    let fullPage = (event.state && event.state.page) || "dashboard";

    $(".side-item").removeClass("active");
    let basePage = fullPage.split("?")[0];
    let $target = $(".side-item[data-page='" + basePage + "']");
    $target.addClass("active");

    let $parentCollapse = $target.closest(".collapse");
    if ($parentCollapse.length) {
      $parentCollapse.addClass("show");
      $parentCollapse.prev(".side-item").attr("aria-expanded", "true");
    }

    routePage(fullPage);
  };

  // Init search
  initSidebarSearch();

  // Handle Add User form submit
  $(document).on("submit", "#addUserForm", function (e) {
    e.preventDefault();

    $(".invalid-feedback").text("");
    $(".form-control").removeClass("is-invalid");

    let role = $("#roleSelect").val();
    let hasError = false;

    // 🔹 Common validations
    if (!/^[A-Za-z\s]+$/.test($("#fullname").val().trim())) {
      $(".fullname-error").text("Full name must contain only letters");
      $("#fullname").addClass("is-invalid");
      hasError = true;
    }
    if (!/^[A-Za-z0-9_]+$/.test($("#username").val().trim())) {
      $(".username-error").text(
        "Username must contain only letters, numbers, or _"
      );
      $("#username").addClass("is-invalid");
      hasError = true;
    }
    if (
      !$("#email").val().trim() ||
      !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test($("#email").val().trim())
    ) {
      $(".email-error").text("Enter a valid email address");
      $("#email").addClass("is-invalid");
      hasError = true;
    }
    if ($("#phone").val() && !/^\d{10}$/.test($("#phone").val())) {
      $(".phone-error").text("Phone must be 10 digits");
      $("#phone").addClass("is-invalid");
      hasError = true;
    }
    if (!$("#branch_id").val()) {
      $(".branch-error").text("Branch is required");
      $("#branch_id").addClass("is-invalid");
      hasError = true;
    }
    if (!$("#roleSelect").val()) {
      $(".role-error").text("Role is required");
      $("#roleSelect").addClass("is-invalid");
      hasError = true;
    }

    // 🔹 Role-specific validations
    if (role == "3") {
      if (!$("#class_id").val()) {
        $(".class-error").text("Class is required");
        $("#class_id").addClass("is-invalid");
        hasError = true;
      }
      if (!$("#section_id").val()) {
        $(".section-error").text("Section is required");
        $("#section_id").addClass("is-invalid");
        hasError = true;
      }
      // DOB
      if (!$("#dob").val()) {
        $(".dob-error").text("Date of Birth is required");
        $("#dob").addClass("is-invalid");
        hasError = true;
      } else {
        let dob = new Date($("#dob").val());
        let today = new Date();
        if (dob > today) {
          $(".dob-error").text("DOB cannot be in the future");
          $("#dob").addClass("is-invalid");
          hasError = true;
        }
        let age = today.getFullYear() - dob.getFullYear();
        if (age < 3 || age > 25) {
          $(".dob-error").text("Age must be between 3 and 25 years");
          $("#dob").addClass("is-invalid");
          hasError = true;
        }
      }
      // Gender
      if (!$("#gender").val()) {
        $(".gender-error").text("Gender is required");
        $("#gender").addClass("is-invalid");
        hasError = true;
      }
      // Admission date
      if (!$("#admission_date").val()) {
        $(".admission_date-error").text("Admission Date is required");
        $("#admission_date").addClass("is-invalid");
        hasError = true;
      } else {
        let adDate = new Date($("#admission_date").val());
        let dob = new Date($("#dob").val());
        let today = new Date();
        if (adDate > today) {
          $(".admission_date-error").text("Admission Date cannot be in future");
          $("#admission_date").addClass("is-invalid");
          hasError = true;
        }
        if ($("#dob").val() && adDate < dob) {
          $(".admission_date-error").text(
            "Admission Date cannot be before DOB"
          );
          $("#admission_date").addClass("is-invalid");
          hasError = true;
        }
      }
      // Aadhar No
      if (!$("#aadhar_no").val()) {
        $(".aadhar_no-error").text("Aadhar is required");
        $("#aadhar_no").addClass("is-invalid");
        hasError = true;
      }
      if ($("#aadhar_no").val() && !/^\d{12}$/.test($("#aadhar_no").val())) {
        $(".aadhar_no-error").text("Aadhar must be 12 digits");
        $("#aadhar_no").addClass("is-invalid");
        hasError = true;
      }
      // Blood Group
      if (
        $("#blood_group").val() &&
        !/^(A|B|AB|O)[+-]$/.test($("#blood_group").val())
      ) {
        $(".blood_group-error").text("Invalid blood group (e.g., O+, AB-)");
        $("#blood_group").addClass("is-invalid");
        hasError = true;
      }
      // Photo
      if ($("#photo")[0].files.length > 0) {
        let file = $("#photo")[0].files[0];
        let ext = file.name.split(".").pop().toLowerCase();
        if (!["jpg", "jpeg", "png"].includes(ext)) {
          $(".photo-error").text("Only JPG, JPEG, PNG allowed");
          $("#photo").addClass("is-invalid");
          hasError = true;
        }
        if (file.size > 2 * 1024 * 1024) {
          $(".photo-error").text("Photo must be less than 2MB");
          $("#photo").addClass("is-invalid");
          hasError = true;
        }
      }
    } else if (role == "2") {
      let valid = true;
      $(".teacher-mapping-row").each(function () {
        if (!$(this).find(".teacher-class").val()) valid = false;
        if (!$(this).find(".teacher-section").val()) valid = false;
        if (!$(this).find(".teacher-subject").val()) valid = false;
      });
      if (!valid) {
        $(".teacher-error").text(
          "All Class/Section/Subject mappings must be selected"
        );
        hasError = true;
      }
    } else if (role == "5") {
      if (!$("#salary").val() || parseFloat($("#salary").val()) < 0) {
        $(".salary-error").text("Salary is required and must be valid");
        $("#salary").addClass("is-invalid");
        hasError = true;
      }

      let bankVal = $("#bank_account").val().replace(/\s+/g, ""); // remove spaces
      if (!bankVal.match(/^\d{12}$/)) {
        $(".bank_account-error").text("Bank account must be exactly 12 digits");
        $("#bank_account").addClass("is-invalid");
        hasError = true;
      }
    } else if (role == "4") {
      // Parent
      if (
        $("#father_name").val().trim() &&
        !/^[A-Za-z\s]+$/.test($("#father_name").val().trim())
      ) {
        $(".father_name-error").text("Father name must contain only letters");
        $("#father_name").addClass("is-invalid");
        hasError = true;
      }
      if (
        $("#mother_name").val().trim() &&
        !/^[A-Za-z\s]+$/.test($("#mother_name").val().trim())
      ) {
        $(".mother_name-error").text("Mother name must contain only letters");
        $("#mother_name").addClass("is-invalid");
        hasError = true;
      }
      if ($("#parent_salary").val() && isNaN($("#parent_salary").val())) {
        $(".parent_salary-error").text("Salary must be a number");
        $("#parent_salary").addClass("is-invalid");
        hasError = true;
      }
      if (
        $("#alt_phone").val().trim() &&
        !/^\d{10}$/.test($("#alt_phone").val().trim())
      ) {
        $(".alt_phone-error").text("Phone must be 10 digits");
        $("#alt_phone").addClass("is-invalid");
        hasError = true;
      }
      if (
        $("#alt_email").val().trim() &&
        !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test($("#alt_email").val().trim())
      ) {
        $(".alt_email-error").text("Invalid email format");
        $("#alt_email").addClass("is-invalid");
        hasError = true;
      }
    }

    if (hasError) return;

    // 🔹 Submit
    let formData = new FormData(this);
    formData.append("action", "add_user");

    $.ajax({
      type: "POST",
      url: "common/functions.php", // Admin-specific functions.php
      data: formData,
      processData: false,
      contentType: false,
      dataType: "json",
      beforeSend: function () {
        // optional: disable button, show spinner
      },
      success: function (res) {
        if (res.status === "success") {
          Swal.fire({
            icon: "success",
            title: "Success",
            text: res.message,
            confirmButtonColor: getComputedStyle(
              document.documentElement
            ).getPropertyValue("--color-success"),
          }).then(() => {
            $("#addUserForm")[0].reset();
            $(".role-fields").addClass("d-none");
          });
        } else {
          Swal.fire({
            icon: "error",
            title: "Error",
            text: res.message,
            confirmButtonColor: getComputedStyle(
              document.documentElement
            ).getPropertyValue("--color-danger"),
          });
        }
      },
      error: function () {
        Swal.fire({
          icon: "warning",
          title: "Oops!",
          text: "Something went wrong. Please try again.",
          confirmButtonColor: getComputedStyle(
            document.documentElement
          ).getPropertyValue("--color-warning"),
        });
      },
    });
  });

  // Live remove error on input/change
  $(document).on(
    "input change",
    "#addUserForm .form-control, #addUserForm .form-control-file",
    function () {
      $(this).removeClass("is-invalid");
      $(this).siblings(".invalid-feedback").text("");
    }
  );

  // Handle Edit User form submit
  $(document).on("submit", "#editUserForm", function (e) {
    e.preventDefault();

    $(".invalid-feedback").text("");
    $(".form-control").removeClass("is-invalid");

    let role = $("#roleSelect").val();
    let hasError = false;

    // 🔹 Common validations
    if (!/^[A-Za-z\s]+$/.test($("#fullname").val().trim())) {
      $(".fullname-error").text("Full name must contain only letters");
      $("#fullname").addClass("is-invalid");
      hasError = true;
    }
    if (!/^[A-Za-z0-9_]+$/.test($("#username").val().trim())) {
      $(".username-error").text(
        "Username must contain only letters, numbers, or _"
      );
      $("#username").addClass("is-invalid");
      hasError = true;
    }
    if (
      !$("#email").val().trim() ||
      !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test($("#email").val().trim())
    ) {
      $(".email-error").text("Enter a valid email address");
      $("#email").addClass("is-invalid");
      hasError = true;
    }
    if ($("#phone").val() && !/^\d{10}$/.test($("#phone").val())) {
      $(".phone-error").text("Phone must be 10 digits");
      $("#phone").addClass("is-invalid");
      hasError = true;
    }
    if (!$("#branch_id").val()) {
      $(".branch-error").text("Branch is required");
      $("#branch_id").addClass("is-invalid");
      hasError = true;
    }
    if (!$("#roleSelect").val()) {
      $(".role-error").text("Role is required");
      $("#roleSelect").addClass("is-invalid");
      hasError = true;
    }

    // 🔹 Role-specific validations
    if (role == "3") {
      if (!$("#student_class_id").val()) {
        $(".class-error").text("Class is required");
        $("#student_class_id").addClass("is-invalid");
        hasError = true;
      }
      if (!$("#student_section_id").val()) {
        $(".section-error").text("Section is required");
        $("#student_section_id").addClass("is-invalid");
        hasError = true;
      }
      // DOB
      if (!$("#dob").val()) {
        $(".dob-error").text("Date of Birth is required");
        $("#dob").addClass("is-invalid");
        hasError = true;
      }
      // Admission Date
      if (!$("#admission_date").val()) {
        $(".admission_date-error").text("Admission Date is required");
        $("#admission_date").addClass("is-invalid");
        hasError = true;
      }
      // Aadhar No
      if ($("#aadhar_no").val() && !/^\d{12}$/.test($("#aadhar_no").val())) {
        $(".aadhar_no-error").text("Aadhar must be 12 digits");
        $("#aadhar_no").addClass("is-invalid");
        hasError = true;
      }
    } else if (role == "2") {
      let valid = true;
      $(".teacher-mapping-row-edit").each(function () {
        if (!$(this).find(".teacher-class-edit").val()) valid = false;
        if (!$(this).find(".teacher-section-edit").val()) valid = false;
        if (!$(this).find(".teacher-subject-edit").val()) valid = false;
      });
      if (!valid) {
        $(".teacher-error").text(
          "All Class/Section/Subject mappings must be selected"
        );
        hasError = true;
      }
    } else if (role == "5") {
      if (!$("#salary").val() || parseFloat($("#salary").val()) < 0) {
        $(".salary-error").text("Salary is required and must be valid");
        $("#salary").addClass("is-invalid");
        hasError = true;
      }
      let bankVal = $("#bank_account").val().replace(/\s+/g, "");
      if (!bankVal.match(/^\d{12}$/)) {
        $(".bank_account-error").text("Bank account must be exactly 12 digits");
        $("#bank_account").addClass("is-invalid");
        hasError = true;
      }
    } else if (role == "4") {
      if (
        $("#father_name").val().trim() &&
        !/^[A-Za-z\s]+$/.test($("#father_name").val().trim())
      ) {
        $(".father_name-error").text("Father name must contain only letters");
        $("#father_name").addClass("is-invalid");
        hasError = true;
      }
      if (
        $("#mother_name").val().trim() &&
        !/^[A-Za-z\s]+$/.test($("#mother_name").val().trim())
      ) {
        $(".mother_name-error").text("Mother name must contain only letters");
        $("#mother_name").addClass("is-invalid");
        hasError = true;
      }
      if ($("#parent_salary").val() && isNaN($("#parent_salary").val())) {
        $(".parent_salary-error").text("Salary must be a number");
        $("#parent_salary").addClass("is-invalid");
        hasError = true;
      }
      if (
        $("#alt_phone").val().trim() &&
        !/^\d{10}$/.test($("#alt_phone").val().trim())
      ) {
        $(".alt_phone-error").text("Phone must be 10 digits");
        $("#alt_phone").addClass("is-invalid");
        hasError = true;
      }
      if (
        $("#alt_email").val().trim() &&
        !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test($("#alt_email").val().trim())
      ) {
        $(".alt_email-error").text("Invalid email format");
        $("#alt_email").addClass("is-invalid");
        hasError = true;
      }
    }

    if (hasError) return;

    // 🔹 Submit
    let formData = new FormData(this);
    formData.append("action", "update_user");

    $.ajax({
      type: "POST",
      url: "common/functions.php",
      data: formData,
      processData: false,
      contentType: false,
      dataType: "json",
      beforeSend: function () {
        // disable button, maybe spinner
      },
      success: function (res) {
        if (res.status === "success") {
          Swal.fire({
            icon: "success",
            title: "Updated",
            text: res.message,
            confirmButtonColor: getComputedStyle(
              document.documentElement
            ).getPropertyValue("--color-success"),
          }).then(() => {
            // reload or go back to manage page
            loadPage("users/manage.php");
          });
        } else {
          Swal.fire({
            icon: "error",
            title: "Error",
            text: res.message,
            confirmButtonColor: getComputedStyle(
              document.documentElement
            ).getPropertyValue("--color-danger"),
          });
        }
      },
      error: function () {
        Swal.fire({
          icon: "warning",
          title: "Oops!",
          text: "Something went wrong. Please try again.",
          confirmButtonColor: getComputedStyle(
            document.documentElement
          ).getPropertyValue("--color-warning"),
        });
      },
    });
  });

  // Live remove error on input/change
  $(document).on(
    "input change",
    "#editUserForm .form-control, #editUserForm .form-control-file",
    function () {
      $(this).removeClass("is-invalid");
      $(this).siblings(".invalid-feedback").text("");
    }
  );

  // Auto-format bank account (spaces after every 4 digits)
  $(document).on("input", "#bank_account", function () {
    let value = $(this).val().replace(/\D/g, ""); // keep digits only
    value = value.substring(0, 12); // limit to 14 digits
    let formatted = value.replace(/(.{4})(?=.)/g, "$1 ");
    $(this).val(formatted);
  });

  // Student photo preview + cancel
  $(document).on("change", "#photo", function () {
    if (this.files && this.files[0]) {
      let reader = new FileReader();
      reader.onload = function (e) {
        $("#photoPreview").attr("src", e.target.result).removeClass("d-none");
        $("#removePhoto").removeClass("d-none");
      };
      reader.readAsDataURL(this.files[0]);
    }
  });

  // Remove photo
  $(document).on("click", "#removePhoto", function () {
    $("#photo").val(""); // clear file input
    $("#photoPreview").attr("src", "assets/images/avatar.png"); // default avatar
    $(this).addClass("d-none");
  });

  // Clone mapping row
  $(document).on("click", "#addTeacherMapping", function () {
    let row = $(".teacher-mapping-row:first").clone();
    row.find("select").val(""); // reset selects
    $("#teacherAssignmentsContainer").append(row);
  });
  // Remove mapping row
  $(document).on("click", ".removeTeacherMapping", function () {
    if ($(".teacher-mapping-row").length > 1) {
      // keep at least one row
      $(this).closest(".teacher-mapping-row").remove();
    } else {
      alert("At least one mapping is required.");
    }
  });

  // Branch → Classes (existing code, just assign to teacher-class)
  $(document).on("change", "#branch_id", function () {
    let branch_id = $(this).val();
    if (!branch_id) return;
    $.post(
      "common/functions.php",
      { action: "get_classes", branch_id },
      function (res) {
        if (res.status === "success") {
          let options = '<option value="">Select Class</option>';
          res.data.forEach(
            (c) =>
              (options += `<option value="${c.class_id}">${c.class_name}</option>`)
          );
          $(".teacher-class").html(options);
        }
      },
      "json"
    );
  });

  // Branch → Subjects (existing code)
  $(document).on("change", "#teacher_class", function () {
    let class_id = $(this).val();
    if (!class_id) return;
    $.post(
      "common/functions.php",
      { action: "get_subjects", class_id },
      function (res) {
        if (res.status === "success") {
          let options = '<option value="">Select Subject</option>';
          res.data.forEach(
            (s) =>
              (options += `<option value="${s.subject_id}">${s.subject_name}</option>`)
          );
          $(".teacher-subject").html(options);
        }
      },
      "json"
    );
  });
  // Class → Section
  $(document).on("change", ".teacher-class", function () {
    let class_id = $(this).val();
    let $section = $(this)
      .closest(".teacher-mapping-row")
      .find(".teacher-section");

    $section.html("<option>Loading...</option>");

    if (!class_id) {
      $section.html('<option value="">Select Section</option>');
      return;
    }

    $.post(
      "common/functions.php",
      { action: "get_sections", class_id },
      function (res) {
        if (res.status === "success") {
          let options = '<option value="">Select Section</option>';
          res.data.forEach(
            (s) =>
              (options += `<option value="${s.section_id}">${s.section_name}</option>`)
          );
          $section.html(options);
        } else {
          console.error("Error loading sections:", res.message);
        }
      },
      "json"
    );
  });

  // Edit user
  $(document).on("click", ".edit-user", function () {
    const userId = $(this).data("id");
    const fullPage = "users/edit?user_id=" + userId;
    history.pushState(
      { page: fullPage },
      "",
      "?page=" + encodeURIComponent(fullPage)
    );
    routePage(fullPage);
  });
  // Delete user
  $(document).on("click", ".delete-user", function () {
    const userId = $(this).data("id");
    const fullPage = "users/delete?user_id=" + userId;
    history.pushState(
      { page: fullPage },
      "",
      "?page=" + encodeURIComponent(fullPage)
    );
    routePage(fullPage);
  });
});
