<template>
    <section class="admin-list-shell">
        <!-- =========================================================
             HERO
        ========================================================== -->
        <section class="student-hero-card">
            <div class="hero-copy">
                <div class="hero-kicker">
                    <span class="hero-kicker-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24">
                            <path
                                d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2M9 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8zM22 21v-2a4 4 0 0 0-3-3.87" />
                        </svg>
                    </span>

                    <span>Users & Records</span>
                </div>

                <h1>Student Directory</h1>

                <p>
                    Maintain student accounts, academic information, account
                    status, and account details in one secure administrative
                    workspace.
                </p>

                <div class="hero-meta">
                    <span class="hero-meta-chip">
                        <span class="live-dot"></span>
                        {{ students.length }} total records
                    </span>

                    <span class="hero-meta-chip">
                        {{ activeCount }} active accounts
                    </span>
                </div>
            </div>

            <div class="hero-actions">
                <button type="button" class="outline-action" :disabled="listLoading" @click="loadListData">
                    <svg :class="{ spinning: listLoading }" viewBox="0 0 24 24" aria-hidden="true">
                        <path d="M19 7v4h-4M5 17v-4h4M6.3 9a6.5 6.5 0 0 1 10.7-2L19 9M5 15l2 2a6.5 6.5 0 0 0 10.7-2" />
                    </svg>

                    <span>
                        {{ listLoading ? 'Refreshing…' : 'Refresh' }}
                    </span>
                </button>

                <button type="button" class="gold-action" @click="openStudentModal('create')">
                    <svg viewBox="0 0 24 24" aria-hidden="true">
                        <path d="M12 5v14M5 12h14" />
                    </svg>

                    <span>Add Student</span>
                </button>
            </div>
        </section>

        <!-- =========================================================
             STATISTICS
        ========================================================== -->
        <section class="student-stat-grid" aria-label="Student summary">
            <article class="student-stat-card">
                <div class="stat-icon green" aria-hidden="true">
                    <svg viewBox="0 0 24 24">
                        <path
                            d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2M9 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8zM22 21v-2a4 4 0 0 0-3-3.87" />
                    </svg>
                </div>

                <div>
                    <span>Total Students</span>
                    <strong>{{ students.length }}</strong>
                    <small>Directory accounts</small>
                </div>
            </article>

            <article class="student-stat-card">
                <div class="stat-icon success" aria-hidden="true">
                    <svg viewBox="0 0 24 24">
                        <path d="m5 12 4 4L19 6" />
                    </svg>
                </div>

                <div>
                    <span>Active Accounts</span>
                    <strong>{{ activeCount }}</strong>
                    <small>Currently enabled</small>
                </div>
            </article>

            <article class="student-stat-card">
                <div class="stat-icon gold" aria-hidden="true">
                    <svg viewBox="0 0 24 24">
                        <path d="M12 5v14M5 12h14" />
                    </svg>
                </div>

                <div>
                    <span>New This Week</span>
                    <strong>{{ newStudentsCount }}</strong>
                    <small>Recently added</small>
                </div>
            </article>

            <article class="student-stat-card">
                <div class="stat-icon danger" aria-hidden="true">
                    <svg viewBox="0 0 24 24">
                        <path
                            d="M12 9v4M12 17h.01M10.3 3.8 2.8 17a2 2 0 0 0 1.8 3h14.8a2 2 0 0 0 1.8-3L13.7 3.8a2 2 0 0 0-3.4 0z" />
                    </svg>
                </div>

                <div>
                    <span>Restricted</span>
                    <strong>{{ suspendedCount + inactiveCount }}</strong>
                    <small>Suspended or inactive</small>
                </div>
            </article>
        </section>

        <!-- =========================================================
             DIRECTORY PANEL
        ========================================================== -->
        <section class="student-directory-panel">
            <div class="directory-panel-head">
                <div>
                    <p class="section-kicker">Student Management</p>
                    <h2>Directory Records</h2>
                    <p>
                        Search, filter, review, and manage student accounts.
                    </p>
                </div>

                <div class="records-pill">
                    <span>Results</span>
                    <strong>{{ filteredStudents.length }}</strong>
                </div>
            </div>

            <!-- FILTERS -->
            <div class="student-filter-bar">
                <label class="student-search-field">
                    <span class="field-label">Search students</span>

                    <div class="student-search-shell">
                        <span class="student-search-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24">
                                <circle cx="11" cy="11" r="7" />
                                <path d="m20 20-3.5-3.5" />
                            </svg>
                        </span>

                        <input v-model.trim="studentSearch" type="search"
                            placeholder="Search name, ID, email, course, or department…" />

                        <button v-if="studentSearch" type="button" class="clear-search-btn" aria-label="Clear search"
                            @click="studentSearch = ''">
                            ×
                        </button>
                    </div>
                </label>

                <div class="student-filters">
                    <label>
                        <span class="field-label">Course</span>

                        <select v-model="courseFilter">
                            <option value="">All courses</option>

                            <option v-for="course in COURSES" :key="course" :value="course">
                                {{ course }}
                            </option>
                        </select>
                    </label>

                    <label>
                        <span class="field-label">Year</span>

                        <select v-model="yearLevelFilter">
                            <option value="">All years</option>

                            <option v-for="year in YEAR_LEVELS" :key="year" :value="year">
                                {{ year }}
                            </option>
                        </select>
                    </label>

                    <label>
                        <span class="field-label">Status</span>

                        <select v-model="statusFilter">
                            <option value="">All statuses</option>

                            <option v-for="status in statusOptions" :key="status" :value="status">
                                {{ status === 'unknown' ? 'Unknown' : capitalize(status) }}
                            </option>
                        </select>
                    </label>

                    <label>
                        <span class="field-label">Sort</span>

                        <select v-model="sortOption">
                            <option value="newest">Newest first</option>
                            <option value="oldest">Oldest first</option>
                        </select>
                    </label>
                </div>
            </div>

            <div class="filter-summary-row">
                <div class="matching-count">
                    <span class="matching-dot"></span>

                    <strong>{{ filteredStudents.length }}</strong>
                    {{
                        filteredStudents.length === 1
                            ? 'matching student'
                            : 'matching students'
                    }}
                </div>

                <div class="filter-summary-actions">
                    <button v-if="hasFilters" type="button" class="soft-action" @click="clearFilters">
                        Clear filters
                    </button>

                    <button type="button" class="directory-print-button"
                        :disabled="listLoading || !sortedStudents.length"
                        title="Print all students matching the selected filters" @click="printStudentDirectory">
                        <svg viewBox="0 0 24 24" aria-hidden="true">
                            <path
                                d="M6 9V3h12v6M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2" />
                            <path d="M6 15h12v6H6zM17 12h.01" />
                        </svg>
                        Print / Save PDF
                    </button>
                </div>
            </div>

            <!-- MESSAGES -->
            <div v-if="errorMessage" class="student-alert error" role="alert">
                <span class="student-alert-icon">!</span>

                <div>
                    <strong>Unable to complete the request</strong>
                    <p>{{ errorMessage }}</p>
                </div>
            </div>

            <div v-if="statusMessage" class="student-alert success" role="status">
                <span class="student-alert-icon">✓</span>

                <div>
                    <strong>Student directory updated</strong>
                    <p>{{ statusMessage }}</p>
                </div>
            </div>

            <!-- TABLE -->
            <div class="student-table-shell">
                <div v-if="listLoading" class="student-loading-state">
                    <span class="student-loader"></span>

                    <div>
                        <strong>Loading student directory</strong>
                        <p>Retrieving the latest student records.</p>
                    </div>
                </div>

                <div v-else-if="!students.length" class="student-empty-state">
                    <div class="student-empty-icon">
                        <svg viewBox="0 0 24 24" aria-hidden="true">
                            <path
                                d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2M9 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8zM19 8v6M16 11h6" />
                        </svg>
                    </div>

                    <h3>No students yet</h3>

                    <p>
                        Add a student account to begin building the directory.
                    </p>

                    <button type="button" class="gold-action" @click="openStudentModal('create')">
                        Add First Student
                    </button>
                </div>

                <div v-else-if="!filteredStudents.length" class="student-empty-state">
                    <div class="student-empty-icon">
                        <svg viewBox="0 0 24 24" aria-hidden="true">
                            <circle cx="11" cy="11" r="7" />
                            <path d="m20 20-3.5-3.5" />
                        </svg>
                    </div>

                    <h3>No matching students</h3>

                    <p>
                        Try another search term or clear your current filters.
                    </p>

                    <button type="button" class="soft-action" @click="clearFilters">
                        Clear Filters
                    </button>
                </div>

                <table v-else class="student-records-table">
                    <thead>
                        <tr>
                            <th>Student</th>
                            <th>Student ID</th>
                            <th>Email</th>
                            <th>Program</th>
                            <th>Status</th>
                            <th class="actions-heading">Actions</th>
                        </tr>
                    </thead>

                    <tbody>
                        <tr v-for="student in paginatedStudents" :key="student.id">
                            <td>
                                <div class="student-person">
                                    <img :src="studentImage(student)"
                                        :alt="`${student.name || 'Student'} profile picture`" />

                                    <div>
                                        <strong>
                                            {{ student.name || 'Unnamed Student' }}
                                        </strong>

                                        <span>
                                            {{
                                                student.department ||
                                                'Department not set'
                                            }}
                                        </span>
                                    </div>
                                </div>
                            </td>

                            <td>
                                <span class="student-id-badge">
                                    {{ studentId(student) }}
                                </span>
                            </td>

                            <td>
                                <span class="student-email">
                                    {{ student.email || '—' }}
                                </span>
                            </td>

                            <td>
                                <div class="student-program">
                                    <strong>
                                        {{ student.course || 'No program' }}
                                    </strong>

                                    <span>
                                        {{ student.year_level || 'Year not set' }}
                                    </span>
                                </div>
                            </td>

                            <td>
                                <span :class="[
                                    'student-status-badge',
                                    statusClass(studentAccountStatus(student)),
                                ]">
                                    {{ studentStatus(student) }}
                                </span>
                            </td>

                            <td>
                                <div class="student-actions">
                                    <button type="button" class="table-action view" title="View student details"
                                        @click="openDetailsModal(student)">
                                        <svg viewBox="0 0 24 24" aria-hidden="true">
                                            <path
                                                d="M12 5c-7 0-10 7-10 7s3 7 10 7 10-7 10-7-3-7-10-7zm0 12a5 5 0 1 1 0-10 5 5 0 0 1 0 10z" />
                                            <circle cx="12" cy="12" r="2.5" />
                                        </svg>
                                    </button>

                                    <button type="button" class="table-action edit" title="Edit student"
                                        @click="openStudentModal('edit', student)">
                                        <svg viewBox="0 0 24 24" aria-hidden="true">
                                            <path
                                                d="M4 16.5V20h3.5L18.2 9.3l-3.5-3.5L4 16.5zm16.7-9.8a1 1 0 0 0 0-1.4l-2-2a1 1 0 0 0-1.4 0l-1.6 1.6 3.5 3.5 1.5-1.7z" />
                                        </svg>
                                    </button>

                                    <button type="button" class="table-action password" title="Change student password"
                                        @click="openPasswordModal(student)">
                                        <svg viewBox="0 0 24 24" aria-hidden="true">
                                            <path
                                                d="M7 10V8a5 5 0 0 1 10 0v2h1a2 2 0 0 1 2 2v7a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2v-7a2 2 0 0 1 2-2h1zm2 0h6V8a3 3 0 0 0-6 0v2zm3 4a2 2 0 0 0-1 3.7V19h2v-1.3A2 2 0 0 0 12 14z" />
                                        </svg>
                                    </button>

                                    <button type="button" class="table-action delete" title="Delete student"
                                        :disabled="deletingStudentId === student.id"
                                        @click="confirmDeleteStudent(student)">
                                        <svg viewBox="0 0 24 24" aria-hidden="true">
                                            <path d="M6 7h12v12a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2V7zm3-4h6v2H9V3z" />
                                        </svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- PAGINATION -->
            <div v-if="pageCount > 1" class="student-pagination">
                <div class="pagination-summary">
                    {{ pageRangeText }}
                </div>

                <div class="pagination-controls">
                    <button type="button" class="pagination-btn" :disabled="currentPage === 1"
                        @click="currentPage -= 1">
                        Previous
                    </button>

                    <button v-for="page in pageNumbers" :key="page" type="button" :class="[
                        'pagination-btn',
                        { active: page === currentPage },
                    ]" @click="currentPage = page">
                        {{ page }}
                    </button>

                    <button type="button" class="pagination-btn" :disabled="currentPage === pageCount"
                        @click="currentPage += 1">
                        Next
                    </button>

                    <label class="rows-control">
                        <span>Rows</span>

                        <select v-model.number="rowsPerPage">
                            <option v-for="option in rowsPerPageOptions" :key="option" :value="option">
                                {{ option }}
                            </option>
                        </select>
                    </label>
                </div>
            </div>
        </section>

        <!-- =========================================================
             CREATE / EDIT STUDENT MODAL
        ========================================================== -->
        <div v-if="showStudentModal" class="student-modal-overlay" @click.self="closeStudentModal">
            <div class="student-modal student-modal-large">
                <div class="student-modal-header">
                    <div class="student-modal-title">
                        <div class="modal-icon gold">
                            <svg viewBox="0 0 24 24" aria-hidden="true">
                                <path d="M12 5v14M5 12h14" />
                            </svg>
                        </div>

                        <div>
                            <p v-if="studentModalMode === 'edit'" class="modal-kicker">
                                Edit Student Account
                            </p>
                            <p v-else class="modal-kicker">
                                Student Account
                            </p>

                            <h2 v-if="studentModalMode === 'edit'">
                                Edit Student
                            </h2>
                            <h2 v-else>
                                Add Student
                            </h2>

                            <p v-if="studentModalMode === 'edit'">
                                Update the student profile and academic information. Password changes are handled
                                separately.
                            </p>

                            <p v-else>
                                Create a new student account and complete the required academic information.
                            </p>
                        </div>
                    </div>

                    <button type="button" class="modal-close-btn" @click="closeStudentModal" aria-label="Close">
                        ×
                    </button>
                </div>

                <form class="student-form" @submit.prevent="submitStudent">
                    <section class="student-form-section">
                        <div class="form-section-title">
                            <span>01</span>

                            <div>
                                <h3>Personal Information</h3>
                                <p>Student identity and official school ID.</p>
                            </div>
                        </div>

                        <div class="three-column-grid">
                            <label>
                                <span>First name *</span>

                                <input v-model.trim="studentForm.first_name" type="text" placeholder="First name" />

                                <small v-if="studentErrors.first_name" class="field-error">
                                    {{ studentErrors.first_name }}
                                </small>
                            </label>

                            <label>
                                <span>Middle name</span>

                                <input v-model.trim="studentForm.middle_name" type="text" placeholder="Middle name" />
                            </label>

                            <label>
                                <span>Last name *</span>

                                <input v-model.trim="studentForm.last_name" type="text" placeholder="Last name" />

                                <small v-if="studentErrors.last_name" class="field-error">
                                    {{ studentErrors.last_name }}
                                </small>
                            </label>
                        </div>

                        <label>
                            <span>Student ID *</span>

                            <input v-model.trim="studentForm.student_id" type="text" placeholder="e.g. 2026-00123" />

                            <small v-if="studentErrors.student_id" class="field-error">
                                {{ studentErrors.student_id }}
                            </small>
                        </label>
                    </section>

                    <section class="student-form-section">
                        <div class="form-section-title">
                            <span>02</span>

                            <div>
                                <h3>Academic Information</h3>
                                <p>Program, year level, and department assignment.</p>
                            </div>
                        </div>

                        <label>
                            <span>Course or program *</span>

                            <select v-model="studentForm.course_base">
                                <option value="" disabled>
                                    Select course or program
                                </option>

                                <option v-for="course in COURSES" :key="course" :value="course">
                                    {{ course }}
                                </option>
                            </select>

                            <small v-if="studentErrors.course_base" class="field-error">
                                {{ studentErrors.course_base }}
                            </small>
                        </label>

                        <label v-if="needsMajorSelection">
                            <span>Major *</span>

                            <select v-model="studentForm.major">
                                <option value="" disabled>
                                    Select major
                                </option>

                                <option v-for="major in AVAILABLE_MAJORS" :key="major" :value="major">
                                    {{ major }}
                                </option>
                            </select>

                            <small v-if="studentErrors.major" class="field-error">
                                {{ studentErrors.major }}
                            </small>
                        </label>

                        <label v-if="needsConcentrationSelection">
                            <span>Concentration *</span>

                            <select v-model="studentForm.concentration">
                                <option value="" disabled>
                                    Select concentration
                                </option>

                                <option v-for="conc in AVAILABLE_CONCENTRATIONS" :key="conc" :value="conc">
                                    {{ conc }}
                                </option>
                            </select>

                            <small v-if="studentErrors.concentration" class="field-error">
                                {{ studentErrors.concentration }}
                            </small>
                        </label>

                        <div class="two-column-grid">
                            <label>
                                <span>Year level *</span>

                                <select v-model="studentForm.year_level">
                                    <option value="" disabled>
                                        Select year level
                                    </option>

                                    <option v-for="year in YEAR_LEVELS" :key="year" :value="year">
                                        {{ year }}
                                    </option>
                                </select>

                                <small v-if="studentErrors.year_level" class="field-error">
                                    {{ studentErrors.year_level }}
                                </small>
                            </label>

                            <label>
                                <span>Department *</span>

                                <select v-model="studentForm.department">
                                    <option value="" disabled>
                                        Select department
                                    </option>

                                    <option v-for="dept in DEPARTMENTS" :key="dept" :value="dept">
                                        {{ dept }}
                                    </option>
                                </select>

                                <small v-if="studentErrors.department" class="field-error">
                                    {{ studentErrors.department }}
                                </small>
                            </label>
                        </div>
                    </section>

                    <section class="student-form-section">
                        <div class="form-section-title">
                            <span>03</span>

                            <div>
                                <h3>Account Information</h3>
                                <p v-if="studentModalMode === 'edit'">
                                    Update the official SSCT email. Use the password action in the table to change the
                                    password.
                                </p>
                                <p v-else>
                                    Configure the official SSCT email and temporary password.
                                </p>
                            </div>
                        </div>

                        <label>
                            <span>Email</span>

                            <div class="email-row">
                                <input v-model.trim="studentForm.emailPrefix" type="text" placeholder="username"
                                    @input="studentEmailEdited = true" />

                                <span class="email-domain">
                                    @ssct.edu.ph
                                </span>
                            </div>

                            <small v-if="emailDuplicateWarning" class="email-warning">
                                {{ emailDuplicateWarning }}
                            </small>

                            <small v-if="studentErrors.email" class="field-error">
                                {{ studentErrors.email }}
                            </small>
                        </label>

                        <template v-if="studentModalMode === 'create'">
                            <label>
                                <span>Temporary password *</span>

                                <div class="password-shell">
                                    <input v-model="studentForm.password" :type="showPassword ? 'text' : 'password'"
                                        placeholder="Set password" />

                                    <button type="button" class="generate-btn" @click="generateTemporaryPassword">
                                        Generate
                                    </button>

                                    <button type="button" class="password-eye" :aria-label="showPassword
                                        ? 'Hide password'
                                        : 'Show password'
                                        " @click="showPassword = !showPassword">
                                        <svg v-if="!showPassword" viewBox="0 0 24 24" aria-hidden="true">
                                            <path
                                                d="M12 5c5.4 0 9.8 3.5 11 7-1.2 3.5-5.6 7-11 7S2.2 15.5 1 12c1.2-3.5 5.6-7 11-7zm0 2.2A4.8 4.8 0 0 0 7.2 12 4.8 4.8 0 0 0 12 16.8 4.8 4.8 0 0 0 16.8 12 4.8 4.8 0 0 0 12 7.2zm0 2.1a2.7 2.7 0 1 1 0 5.4 2.7 2.7 0 0 1 0-5.4z" />
                                        </svg>

                                        <svg v-else viewBox="0 0 24 24" aria-hidden="true">
                                            <path
                                                d="M3.5 3.5 20.5 20.5l-1.4 1.4-3.2-3.1A11.5 11.5 0 0 1 12 18.8C6.6 18.8 2.2 15.3 1 12c.6-1.5 1.7-2.9 3.1-4.1L2.1 5l1.4-1.5zm5.4 5.4 2.1 2.1A2.7 2.7 0 0 1 14.3 12l2.1 2.1A4.8 4.8 0 0 0 12 7.2a4.8 4.8 0 0 0-3.1 1.7zm8.6-4.1-3 3A7.4 7.4 0 0 1 18.4 12a7.4 7.4 0 0 1-7.7 6.2L8.6 20c1.3.5 2.6.8 3.4.8 5.4 0 9.8-3.5 11-7-1.2-3.5-5.6-7-11-7-.9 0-1.8.1-2.7.4z" />
                                        </svg>
                                    </button>
                                </div>

                                <small class="password-note">
                                    At least 6 characters, 1 uppercase letter,
                                    1 number, and one of !, _, -, .
                                </small>

                                <small v-if="studentErrors.password" class="field-error">
                                    {{ studentErrors.password }}
                                </small>
                            </label>

                            <label>
                                <span>Confirm password *</span>

                                <div class="password-shell">
                                    <input v-model="studentForm.confirm_password" :type="showConfirmPassword
                                        ? 'text'
                                        : 'password'
                                        " placeholder="Confirm password" />

                                    <button type="button" class="password-eye" :aria-label="showConfirmPassword
                                        ? 'Hide confirm password'
                                        : 'Show confirm password'
                                        " @click="
                                            showConfirmPassword =
                                            !showConfirmPassword
                                            ">
                                        <svg v-if="!showConfirmPassword" viewBox="0 0 24 24" aria-hidden="true">
                                            <path
                                                d="M12 5c5.4 0 9.8 3.5 11 7-1.2 3.5-5.6 7-11 7S2.2 15.5 1 12c1.2-3.5 5.6-7 11-7zm0 2.2A4.8 4.8 0 0 0 7.2 12 4.8 4.8 0 0 0 12 16.8 4.8 4.8 0 0 0 16.8 12 4.8 4.8 0 0 0 12 7.2zm0 2.1a2.7 2.7 0 1 1 0 5.4 2.7 2.7 0 0 1 0-5.4z" />
                                        </svg>

                                        <svg v-else viewBox="0 0 24 24" aria-hidden="true">
                                            <path
                                                d="M3.5 3.5 20.5 20.5l-1.4 1.4-3.2-3.1A11.5 11.5 0 0 1 12 18.8C6.6 18.8 2.2 15.3 1 12c.6-1.5 1.7-2.9 3.1-4.1L2.1 5l1.4-1.5zm5.4 5.4 2.1 2.1A2.7 2.7 0 0 1 14.3 12l2.1 2.1A4.8 4.8 0 0 0 12 7.2a4.8 4.8 0 0 0-3.1 1.7zm8.6-4.1-3 3A7.4 7.4 0 0 1 18.4 12a7.4 7.4 0 0 1-7.7 6.2L8.6 20c1.3.5 2.6.8 3.4.8 5.4 0 9.8-3.5 11-7-1.2-3.5-5.6-7-11-7-.9 0-1.8.1-2.7.4z" />
                                        </svg>
                                    </button>
                                </div>

                                <small v-if="studentErrors.confirm_password" class="field-error">
                                    {{ studentErrors.confirm_password }}
                                </small>
                            </label>
                        </template>
                    </section>

                    <div class="student-modal-actions">
                        <button type="button" class="soft-action" @click="closeStudentModal">
                            Cancel
                        </button>

                        <button type="submit" class="gold-action" :disabled="studentModalLoading">
                            <span v-if="studentModalLoading">Saving...</span>
                            <span v-else-if="studentModalMode === 'edit'">Save Changes</span>
                            <span v-else>Create Student</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- =========================================================
             DETAILS MODAL
        ========================================================== -->
        <div v-if="showDetailsModal && selectedStudent" class="student-modal-overlay" @click.self="closeDetailsModal">
            <div class="student-modal student-modal-large">
                <div class="student-modal-header profile-header">
                    <div class="profile-heading">
                        <img :src="studentImage(selectedStudent)"
                            :alt="`${selectedStudent.name || 'Student'} profile picture`" />

                        <div>
                            <p class="modal-kicker">Student Profile</p>

                            <h2>
                                {{
                                    selectedStudent.name ||
                                    'Student details'
                                }}
                            </h2>

                            <p>
                                {{
                                    selectedStudent.email ||
                                    'No email available'
                                }}
                            </p>

                            <span :class="[
                                'student-status-badge',
                                statusClass(studentAccountStatus(selectedStudent)),
                            ]">
                                {{ studentStatus(selectedStudent) }}
                            </span>
                        </div>
                    </div>

                    <button type="button" class="modal-close-btn" @click="closeDetailsModal" aria-label="Close">
                        ×
                    </button>
                </div>

                <div class="profile-detail-grid">
                    <div class="profile-detail-card">
                        <span>School ID</span>
                        <strong>{{ studentId(selectedStudent) }}</strong>
                    </div>

                    <div class="profile-detail-card">
                        <span>Status</span>
                        <strong>{{ studentStatus(selectedStudent) }}</strong>
                    </div>

                    <div class="profile-detail-card wide">
                        <span>Email</span>
                        <strong>{{ selectedStudent.email || '—' }}</strong>
                    </div>

                    <div class="profile-detail-card wide">
                        <span>Course</span>
                        <strong>{{ selectedStudent.course || '—' }}</strong>
                    </div>

                    <div class="profile-detail-card">
                        <span>Department</span>
                        <strong>{{ selectedStudent.department || '—' }}</strong>
                    </div>

                    <div class="profile-detail-card">
                        <span>Year level</span>
                        <strong>{{ selectedStudent.year_level || '—' }}</strong>
                    </div>

                    <div class="profile-detail-card">
                        <span>Created date</span>
                        <strong>
                            {{ formatDate(selectedStudent.created_at) }}
                        </strong>
                    </div>

                    <div class="profile-detail-card">
                        <span>Updated date</span>
                        <strong>
                            {{ formatDate(selectedStudent.updated_at) }}
                        </strong>
                    </div>
                </div>

                <div class="student-modal-actions">
                    <button type="button" class="soft-action" @click="closeDetailsModal">
                        Close Details
                    </button>
                </div>
            </div>
        </div>

        <!-- =========================================================
             CHANGE PASSWORD MODAL
        ========================================================== -->
        <div v-if="showPasswordModal && passwordStudent" class="student-modal-overlay" @click.self="closePasswordModal">
            <div class="student-modal password-modal">
                <div class="student-modal-header">
                    <div class="student-modal-title">
                        <div class="modal-icon gold">
                            <svg viewBox="0 0 24 24" aria-hidden="true">
                                <path
                                    d="M7 10V8a5 5 0 0 1 10 0v2h1a2 2 0 0 1 2 2v7a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2v-7a2 2 0 0 1 2-2h1zm2 0h6V8a3 3 0 0 0-6 0v2z" />
                            </svg>
                        </div>

                        <div>
                            <p class="modal-kicker">Account Security</p>
                            <h2>Change Password</h2>
                            <p>
                                Set a new password for
                                <strong>{{ passwordStudent.name || passwordStudent.email || 'this student' }}</strong>.
                            </p>
                        </div>
                    </div>

                    <button type="button" class="modal-close-btn" aria-label="Close" :disabled="passwordModalLoading"
                        @click="closePasswordModal">
                        ×
                    </button>
                </div>

                <form class="student-form" @submit.prevent="submitPasswordChange">
                    <section class="student-form-section">
                        <div class="password-student-summary">
                            <span>Student</span>
                            <strong>{{ passwordStudent.name || 'Student' }}</strong>
                            <small>{{ passwordStudent.email || 'No email available' }}</small>
                        </div>

                        <label>
                            <span>New password *</span>

                            <div class="password-shell">
                                <input v-model="passwordForm.password" :type="showNewPassword ? 'text' : 'password'"
                                    placeholder="Enter new password" autocomplete="new-password" />

                                <button type="button" class="generate-btn" @click="generatePasswordReset">
                                    Generate
                                </button>

                                <button type="button" class="password-eye"
                                    :aria-label="showNewPassword ? 'Hide password' : 'Show password'"
                                    @click="showNewPassword = !showNewPassword">
                                    {{ showNewPassword ? '×' : '•' }}
                                </button>
                            </div>

                            <small class="password-note">
                                At least 6 characters, 1 uppercase letter, 1 number,
                                and one of !, _, -, .
                            </small>

                            <small v-if="passwordErrors.password" class="field-error">
                                {{ passwordErrors.password }}
                            </small>
                        </label>

                        <label>
                            <span>Confirm new password *</span>

                            <div class="password-shell">
                                <input v-model="passwordForm.password_confirmation"
                                    :type="showNewPasswordConfirmation ? 'text' : 'password'"
                                    placeholder="Confirm new password" autocomplete="new-password" />

                                <button type="button" class="password-eye"
                                    :aria-label="showNewPasswordConfirmation ? 'Hide password' : 'Show password'"
                                    @click="showNewPasswordConfirmation = !showNewPasswordConfirmation">
                                    {{ showNewPasswordConfirmation ? '×' : '•' }}
                                </button>
                            </div>

                            <small v-if="passwordErrors.password_confirmation" class="field-error">
                                {{ passwordErrors.password_confirmation }}
                            </small>
                        </label>
                    </section>

                    <div class="student-modal-actions">
                        <button type="button" class="soft-action" :disabled="passwordModalLoading"
                            @click="closePasswordModal">
                            Cancel
                        </button>

                        <button type="submit" class="gold-action" :disabled="passwordModalLoading">
                            {{ passwordModalLoading ? 'Updating...' : 'Change Password' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- =========================================================
             DELETE MODAL
        ========================================================== -->
        <div v-if="showDeleteDialog && studentToDelete" class="student-modal-overlay" @click.self="cancelDelete">
            <div class="student-modal delete-modal">
                <div class="delete-warning-icon">
                    <svg viewBox="0 0 24 24" aria-hidden="true">
                        <path
                            d="M12 8v5M12 17h.01M10.3 3.8 2.8 17a2 2 0 0 0 1.8 3h14.8a2 2 0 0 0 1.8-3L13.7 3.8a2 2 0 0 0-3.4 0z" />
                    </svg>
                </div>

                <p class="delete-kicker">Permanent Action</p>

                <h3>Delete Student?</h3>

                <p>
                    Remove
                    <strong>{{ studentToDelete.name }}</strong>
                    from the student directory? This action cannot be undone.
                </p>

                <div class="delete-actions">
                    <button type="button" class="soft-action" @click="cancelDelete">
                        Keep Student
                    </button>

                    <button type="button" class="danger-action" :disabled="deletingStudentId === studentToDelete.id"
                        @click="executeDeleteStudent">
                        <span v-if="deletingStudentId === studentToDelete.id">Deleting...</span>
                        <span v-else>Confirm Delete</span>
                    </button>
                </div>
            </div>
        </div>
    </section>
</template>

<script setup>
import { computed, onMounted, ref, watch } from 'vue'
import api from '@/bootstrap'

const YEAR_LEVELS = ['1st Year', '2nd Year', '3rd Year', '4th Year']

const DEPARTMENTS = [
    'College of Computing and Information Sciences (CCIS)',
    'College of Arts & Sciences (CAS)',
    'College of Engineering (COE)',
    'College of Teacher Education (CTE)',
    'College of Business and Technology (CBT)',
]

const COURSE_DEPARTMENT_MAP = [
    { keywords: ['computer science', 'bscs'], department: 'College of Computing and Information Sciences (CCIS)' },
    { keywords: ['information technology', 'bsit', 'b.s. i.t.', 'bs i.t.'], department: 'College of Computing and Information Sciences (CCIS)' },
    { keywords: ['information systems', 'bsis'], department: 'College of Computing and Information Sciences (CCIS)' },
    { keywords: ['information system', 'bsis'], department: 'College of Computing and Information Sciences (CCIS)' },
    { keywords: ['mathematics', 'bsmath', 'bs math'], department: 'College of Arts & Sciences (CAS)' },
    { keywords: ['environmental science', 'bses'], department: 'College of Arts & Sciences (CAS)' },
    { keywords: ['english language', 'ba english'], department: 'College of Arts & Sciences (CAS)' },
    { keywords: ['civil engineering', 'bsce'], department: 'College of Engineering (COE)' },
    { keywords: ['electrical engineering', 'bsee'], department: 'College of Engineering (COE)' },
    { keywords: ['electronics engineering', 'bsece'], department: 'College of Engineering (COE)' },
    { keywords: ['computer engineering', 'bscpe', 'bscope', 'computer engineering'], department: 'College of Engineering (COE)' },
    { keywords: ['elementary education', 'beed'], department: 'College of Teacher Education (CTE)' },
    { keywords: ['secondary education', 'bsed', 'bse d'], department: 'College of Teacher Education (CTE)' },
    { keywords: ['technical teacher education', 'btte'], department: 'College of Teacher Education (CTE)' },
    { keywords: ['physical education', 'bped'], department: 'College of Teacher Education (CTE)' },
    { keywords: ['electrical engineering technology', 'beet'], department: 'College of Business and Technology (CBT)' },
    { keywords: ['electronics engineering technology', 'bexet'], department: 'College of Business and Technology (CBT)' },
    { keywords: ['mechanical engineering technology', 'bmet'], department: 'College of Business and Technology (CBT)' },
    { keywords: ['automotive engineering technology', 'baet'], department: 'College of Business and Technology (CBT)' },
    { keywords: ['industrial technology', 'bindtech', 'industrial technology', 'b ind tech'], department: 'College of Business and Technology (CBT)' },
    { keywords: ['hospitality management', 'bshm'], department: 'College of Business and Technology (CBT)' },
    { keywords: ['tourism management', 'bsmt'], department: 'College of Business and Technology (CBT)' },
]

const COURSES = [
    'Bachelor of Science in Civil Engineering (BSCE)',
    'Bachelor of Science in Electronics Engineering (BSECE)',
    'Bachelor of Science in Electrical Engineering (BSEE)',
    'Bachelor of Science in Computer Engineering (BSCpE)',
    'Bachelor of Automotive Engineering Technology (BAET)',
    'Bachelor of Electrical Engineering Technology (BEET)',
    'Bachelor of Electronics Engineering Technology (BEXET)',
    'Bachelor of Mechanical Engineering Technology (BMET)',
    'Bachelor of Industrial Technology (BIndTech)',
    'Bachelor of Science in Hospitality Management (BSHM)',
    'Bachelor of Science in Information Systems (BSIS)',
    'Bachelor of Science in Information Technology (BSIT)',
    'Bachelor of Science in Computer Science (BSCS)',
    'Bachelor of Technical-Vocational Teacher Education (BTVTEd)',
    'Bachelor of Secondary Education (BSEd)',
    'Bachelor of Elementary Education (BEEd)',
    'Bachelor of Science in Environmental Science (BSES)',
    'Bachelor of Science in Mathematics (BS Math)',
    'Bachelor of Science in Tourism Management (BSMT)',
    'Bachelor of Physical Education (BPEd)',
    'Bachelor of Arts in English Language (ABEL)',
]

const MAJORS_BTTE = ['Automotive Technology', 'Electrical Technology', 'Foods and Services Management']
const MAJORS_BSED = ['English', 'Filipino', 'Science', 'Mathematics']
const CONCENTRATIONS_BMET = ['Mechanical Technology', 'Refrigeration and Air-Conditioning Technology', 'Welding and Fabrication Technology']
const STATUS_OPTIONS = ['active', 'inactive', 'suspended', 'pending']

const students = ref([])
const listLoading = ref(false)
const deletingStudentId = ref(null)
const errorMessage = ref('')
const statusMessage = ref('')

const studentSearch = ref('')
const courseFilter = ref('')
const yearLevelFilter = ref('')
const statusFilter = ref('')
const sortOption = ref('newest')

const showStudentModal = ref(false)
const showDetailsModal = ref(false)
const showDeleteDialog = ref(false)
const showPasswordModal = ref(false)
const selectedStudent = ref(null)
const studentToDelete = ref(null)
const passwordStudent = ref(null)
const studentModalLoading = ref(false)
const passwordModalLoading = ref(false)
const studentModalMode = ref('create')
const editingStudentId = ref(null)

const studentEmailEdited = ref(false)
const showPassword = ref(false)
const showConfirmPassword = ref(false)
const showNewPassword = ref(false)
const showNewPasswordConfirmation = ref(false)
const studentForm = ref({
    first_name: '',
    middle_name: '',
    last_name: '',
    student_id: '',
    course_base: '',
    major: '',
    concentration: '',
    year_level: '',
    department: '',
    emailPrefix: '',
    password: '',
    confirm_password: '',
})
const initialStudentForm = ref({})
const studentErrors = ref({})
const passwordForm = ref({
    password: '',
    password_confirmation: '',
})
const passwordErrors = ref({})

function capitalize(value) {
    if (!value) return ''
    return String(value)
        .toLowerCase()
        .replace(/(^|\s)\S/g, (letter) => letter.toUpperCase())
}

// Use the real account status from Laravel; never assume missing data is Active.
// The /api/admin/list endpoint currently returns both `status` and `account_status`.
function studentAccountStatus(student) {
    const raw = student?.account_status ?? student?.status

    if (typeof raw === 'string' && raw.trim()) {
        const value = raw.trim().toLowerCase().replace(/[\s-]+/g, '_')
        if (['disabled', 'deactivated'].includes(value)) return 'inactive'
        if (['blocked', 'banned'].includes(value)) return 'suspended'
        if (['enabled'].includes(value)) return 'active'
        if (['pending_approval', 'pending_verification'].includes(value)) return 'pending'
        return value
    }

    // Older records may expose is_active instead of status.
    const isActive = student?.is_active
    if (isActive === true || isActive === 1 || isActive === '1') return 'active'
    if (isActive === false || isActive === 0 || isActive === '0') return 'inactive'

    return 'unknown'
}

function studentStatus(student) {
    const status = studentAccountStatus(student)
    return status === 'unknown' ? 'Unknown' : capitalize(status.replace(/_/g, ' '))
}

function studentInitials(name) {
    if (!name) return 'S'
    const parts = name.trim().split(/\s+/)
    if (parts.length === 1) return parts[0].slice(0, 2).toUpperCase()
    return (parts[0].charAt(0) + parts[parts.length - 1].charAt(0)).toUpperCase()
}

function studentImage(student) {
    const path = student?.profile_photo || student?.profile_picture || student?.profilePhoto
    if (!path) return '/assets/logo.png'
    if (/^https?:\/\//i.test(path) || path.startsWith('/')) return path
    return `/${String(path).replace(/^\/+/, '')}`
}

function studentId(student) {
    return student?.student_id || student?.student_id_number || '—'
}


// Printable student-directory report. Data comes from the current directory
// response; never export passwords, reset tokens, or internal database IDs.
function escapePrintHtml(value) {
    const printable = value === null || value === undefined || String(value).trim() === ''
        ? '—'
        : String(value)

    return printable.replace(/[&<>"']/g, (character) => ({
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#39;',
    })[character])
}

function printStudentDirectory() {
    if (listLoading.value || errorMessage.value || !sortedStudents.value.length) return

    // sortedStudents already applies search, course, year, status, and order;
    // using it here includes every matching record across all pages.
    const records = sortedStudents.value
    const printedActive = records.filter((student) => studentAccountStatus(student) === 'active').length
    const printedRestricted = records.filter((student) =>
        ['inactive', 'suspended'].includes(studentAccountStatus(student))
    ).length
    const generatedAt = new Date().toLocaleString('en-PH', {
        year: 'numeric', month: 'long', day: 'numeric',
        hour: 'numeric', minute: '2-digit',
    })

    const printFilters = [
        ['Search', studentSearch.value.trim() || 'All'],
        ['Course', courseFilter.value || 'All'],
        ['Year level', yearLevelFilter.value || 'All'],
        ['Status', statusFilter.value ? capitalize(statusFilter.value) : 'All'],
        ['Order', sortOption.value === 'oldest' ? 'Oldest first' : 'Newest first'],
    ].map(([label, value]) =>
        `<span><b>${escapePrintHtml(label)}:</b> ${escapePrintHtml(value)}</span>`
    ).join('')

    const printRows = records.map((student, index) => `
        <tr>
            <td class="number">${index + 1}</td>
            <td class="name">${escapePrintHtml(student.name || 'Unnamed Student')}</td>
            <td>${escapePrintHtml(studentId(student))}</td>
            <td>${escapePrintHtml(student.email)}</td>
            <td>${escapePrintHtml(student.course)}</td>
            <td>${escapePrintHtml(student.department)}</td>
            <td>${escapePrintHtml(student.year_level)}</td>
            <td>${escapePrintHtml(studentStatus(student))}</td>
            <td>${escapePrintHtml(formatDate(student.created_at))}</td>
        </tr>
    `).join('')

    const printHtml = `<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FindIt - Student Directory Report</title>
    <style>
        @page { size: A4 landscape; margin: 12mm; }
        * { box-sizing: border-box; }
        body { margin: 0; color: #183b2d; font-family: Arial, Helvetica, sans-serif; background: #fff; font-size: 10px; }
        .report-heading { display: flex; justify-content: space-between; align-items: flex-start; gap: 24px; padding-bottom: 14px; border-bottom: 3px solid #1a6545; }
        .eyebrow { margin: 0 0 5px; color: #4f7661; font-weight: bold; font-size: 9px; letter-spacing: 1.1px; text-transform: uppercase; }
        h1 { margin: 0; color: #12432f; font-size: 23px; line-height: 1.15; }
        .subtitle { margin: 6px 0 0; color: #537061; font-size: 10px; }
        .generated { min-width: 180px; text-align: right; color: #537061; line-height: 1.55; }
        .generated strong { display: block; color: #12432f; font-size: 11px; }
        .summary { display: flex; gap: 12px; margin: 16px 0 12px; }
        .summary-card { flex: 1; padding: 11px 13px; border: 1px solid #d6e6db; border-radius: 7px; background: #f2f8f3; }
        .summary-card strong { display: block; color: #12432f; font-size: 19px; }
        .summary-card span { display: block; margin-top: 4px; color: #567263; font-size: 9px; text-transform: uppercase; letter-spacing: .45px; }
        .filters { display: flex; flex-wrap: wrap; column-gap: 16px; row-gap: 6px; margin-bottom: 15px; padding: 10px 12px; border: 1px solid #e0ebe3; background: #f9fcfa; font-size: 9px; }
        .filters span { overflow-wrap: anywhere; }
        h2 { margin: 0 0 9px; color: #174b35; font-size: 13px; }
        table { width: 100%; border-collapse: collapse; table-layout: fixed; font-size: 8px; }
        thead { display: table-header-group; }
        th { padding: 9px 5px; background: #19573b; color: #fff; text-align: left; font-weight: bold; }
        td { padding: 8px 5px; border-bottom: 1px solid #e0e9e2; vertical-align: top; overflow-wrap: anywhere; }
        tbody tr:nth-child(even) { background: #f4f9f5; }
        tr { break-inside: avoid; page-break-inside: avoid; }
        th:nth-child(1) { width: 3%; }
        th:nth-child(2) { width: 14%; }
        th:nth-child(3) { width: 11%; }
        th:nth-child(4) { width: 18%; }
        th:nth-child(5) { width: 16%; }
        th:nth-child(6) { width: 16%; }
        th:nth-child(7) { width: 7%; }
        th:nth-child(8) { width: 8%; }
        th:nth-child(9) { width: 7%; }
        .name { font-weight: 700; }
        .number { color: #799082; }
        .report-footer { display: flex; justify-content: space-between; gap: 16px; margin-top: 16px; padding-top: 8px; border-top: 1px solid #d5e5d9; color: #687d6f; font-size: 9px; }
        @media screen { body { max-width: 1120px; margin: 24px auto; padding: 22px; box-shadow: 0 5px 30px #0000001c; } }
        @media print { body { margin: 0; padding: 0; box-shadow: none; } }
    </style>
</head>
<body>
    <header class="report-heading">
        <div>
            <p class="eyebrow">Surigao del Norte State University · FindIt Lost &amp; Found</p>
            <h1>Student Directory Report</h1>
            <p class="subtitle">Administrative student account and academic records</p>
        </div>
        <div class="generated"><strong>Generated on</strong>${escapePrintHtml(generatedAt)}<br>For authorized administrative use</div>
    </header>
    <section class="summary" aria-label="Report summary">
        <div class="summary-card"><strong>${records.length}</strong><span>Matching students</span></div>
        <div class="summary-card"><strong>${printedActive}</strong><span>Active accounts</span></div>
        <div class="summary-card"><strong>${printedRestricted}</strong><span>Restricted accounts</span></div>
    </section>
    <div class="filters">${printFilters}</div>
    <h2>Student Records</h2>
    <table>
        <thead><tr><th>#</th><th>Student</th><th>School ID</th><th>Email</th><th>Course / Program</th><th>Department</th><th>Year</th><th>Status</th><th>Added</th></tr></thead>
        <tbody>${printRows}</tbody>
    </table>
    <footer class="report-footer"><span>FindIt · Student Directory</span><span>Confidential — do not distribute outside authorized USC personnel</span></footer>
</body>
</html>`

    // Open a blank same-origin tab in direct response to the click to avoid popup blockers.
    const printWindow = window.open('', '_blank')
    if (!printWindow) {
        window.alert('Please allow pop-ups for this website to use Print / Save PDF.')
        return
    }

    try {
        printWindow.document.open()
        printWindow.document.write(printHtml)
        printWindow.document.close()
        printWindow.focus()
        // Give Chrome a moment to lay out the printable document.
        printWindow.setTimeout(() => {
            if (!printWindow.closed) {
                printWindow.focus()
                printWindow.print()
            }
        }, 350)
    } catch (error) {
        printWindow.close()
        window.alert('Could not prepare the student directory report. Please try again.')
    }
}

function formatDate(value) {
    if (!value) return '—'
    const date = new Date(value)
    if (Number.isNaN(date.getTime())) return '—'
    return new Intl.DateTimeFormat('en-US', {
        month: 'short',
        day: 'numeric',
        year: 'numeric',
    }).format(date)
}

function normalizeCourseKey(course) {
    return String(course || '')
        .toLowerCase()
        .replace(/[()]/g, '')
        .replace(/\s+/g, ' ')
        .trim()
}

function getDepartmentForCourse(course) {
    const normalized = normalizeCourseKey(course)
    if (!normalized) return ''

    const match = COURSE_DEPARTMENT_MAP.find(({ keywords }) =>
        keywords.some((keyword) => normalized.includes(keyword))
    )

    return match ? match.department : ''
}

watch(
    () => studentForm.value.course_base,
    (course) => {
        if (!course) {
            studentForm.value.department = ''
            return
        }

        const department = getDepartmentForCourse(course)
        if (department) {
            studentForm.value.department = department
        }
    },
    { immediate: true }
)

function resetMessages() {
    errorMessage.value = ''
    statusMessage.value = ''
}

function normalizeEmailPrefix(value) {
    return String(value || '')
        .trim()
        .toLowerCase()
        .replace(/[^a-z0-9._-]/g, '')
}

function getExistingStudentEmails(excludeStudentId = null) {
    return students.value
        .filter((student) => excludeStudentId === null || student.id !== excludeStudentId)
        .map((student) => String(student.email || '').trim().toLowerCase())
        .filter(Boolean)
}

function generateUniqueEmailPrefix(firstName, lastName) {
    const first = (firstName || '').trim()
    const last = (lastName || '').trim()
    if (!first && !last) return ''

    const baseLast = normalizeEmailPrefix(last)
    const firstNameChars = normalizeEmailPrefix(first)

    if (!firstNameChars && baseLast) return baseLast
    if (!baseLast) return firstNameChars

    let candidateBase = firstNameChars.charAt(0) || ''
    const candidates = []

    for (let i = 1; i <= firstNameChars.length; i++) {
        const prefix = firstNameChars.slice(0, i)
        candidates.push(`${prefix}${baseLast}`)
    }

    const existing = getExistingStudentEmails()
    const finalChoice = candidates.find((candidate) => !existing.includes(`${candidate}@ssct.edu.ph`))

    return finalChoice || `${firstNameChars.charAt(0) || 'student'}${baseLast}`
}

function generateEmailPrefix(firstName, lastName) {
    const first = (firstName || '').trim()
    const last = (lastName || '').trim()
    if (!first && !last) return ''
    if (!first) return normalizeEmailPrefix(last)
    if (!last) return normalizeEmailPrefix(first)

    return generateUniqueEmailPrefix(first, last)
}

const emailDuplicateWarning = computed(() => {
    const email = normalizeEmailPrefix(studentForm.value.emailPrefix)
    if (!email) return ''

    const fullEmail = `${email}@ssct.edu.ph`
    const exists = students.value.some((student) => {
        if (studentModalMode.value === 'edit' && student.id === editingStudentId.value) {
            return false
        }

        return String(student.email || '').trim().toLowerCase() === fullEmail
    })

    if (exists) {
        return 'This email already exists. A unique email was generated automatically.'
    }

    return ''
})

watch(
    [() => studentForm.value.first_name, () => studentForm.value.last_name],
    ([firstName, lastName]) => {
        if (!studentEmailEdited.value) {
            studentForm.value.emailPrefix = generateEmailPrefix(firstName, lastName)
        }
    },
)

function createStrongPassword() {
    const specials = '!_.-'
    const uppercase = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ'
    const lowercase = 'abcdefghijklmnopqrstuvwxyz'
    const digits = '0123456789'

    const randomChar = (set) => set[Math.floor(Math.random() * set.length)]

    return [
        randomChar(uppercase),
        randomChar(lowercase),
        randomChar(digits),
        randomChar(specials),
        randomChar(lowercase),
        randomChar(digits),
        randomChar(uppercase),
        randomChar(specials),
        randomChar(lowercase),
        randomChar(digits),
    ].join('')
}

function generateTemporaryPassword() {
    const password = createStrongPassword()
    studentForm.value.password = password
    studentForm.value.confirm_password = password
}

function generatePasswordReset() {
    const password = createStrongPassword()
    passwordForm.value.password = password
    passwordForm.value.password_confirmation = password
}

const needsMajorSelection = computed(() => {
    const course = String(studentForm.value.course_base || '').toLowerCase()
    return course.includes('technical') && course.includes('teacher education') || course.includes('secondary education')
})

const needsConcentrationSelection = computed(() => {
    const course = String(studentForm.value.course_base || '').toLowerCase()
    return course.includes('mechanical engineering technology') || course.includes('bmet')
})

const AVAILABLE_MAJORS = computed(() => {
    const course = String(studentForm.value.course_base || '').toLowerCase()
    if ((course.includes('technical') && course.includes('teacher education')) || course.includes('btte')) return MAJORS_BTTE
    if (course.includes('secondary education') || course.includes('bsed')) return MAJORS_BSED
    return []
})

const AVAILABLE_CONCENTRATIONS = computed(() => {
    const course = String(studentForm.value.course_base || '').toLowerCase()
    if (course.includes('mechanical engineering technology') || course.includes('bmet')) return CONCENTRATIONS_BMET
    return []
})

// All status badges, counts, and filters now use the same backend-derived value.
const statusOptions = computed(() => {
    const fromRecords = students.value.map(studentAccountStatus)
    return [...new Set([...STATUS_OPTIONS, ...fromRecords])]
})
const activeCount = computed(() => students.value.filter((student) => studentAccountStatus(student) === 'active').length)
const suspendedCount = computed(() => students.value.filter((student) => studentAccountStatus(student) === 'suspended').length)
const inactiveCount = computed(() => students.value.filter((student) => studentAccountStatus(student) === 'inactive').length)
const newStudentsCount = computed(() => {
    const sevenDays = 1000 * 60 * 60 * 24 * 7
    return students.value.filter((student) => {
        const date = student.created_at ? new Date(student.created_at) : null
        return date instanceof Date && !Number.isNaN(date.getTime()) && Date.now() - date.getTime() <= sevenDays
    }).length
})

const filteredStudents = computed(() => {
    const query = studentSearch.value.trim().toLowerCase()

    return students.value.filter((student) => {
        if (courseFilter.value && !String(student.course || '').toLowerCase().startsWith(courseFilter.value.toLowerCase())) {
            return false
        }

        if (yearLevelFilter.value && String(student.year_level || '').toLowerCase() !== yearLevelFilter.value.toLowerCase()) {
            return false
        }

        if (statusFilter.value && studentAccountStatus(student) !== statusFilter.value) {
            return false
        }

        if (!query) return true

        const haystack = [
            student.name,
            studentId(student),
            student.email,
            student.course,
            student.year_level,
            student.department,
        ]
            .filter(Boolean)
            .join(' ')
            .toLowerCase()

        return haystack.includes(query)
    })
})

const sortedStudents = computed(() => {
    const list = [...filteredStudents.value]
    return list.sort((a, b) => {
        const aDate = new Date(a.created_at || 0).getTime()
        const bDate = new Date(b.created_at || 0).getTime()
        return sortOption.value === 'oldest' ? aDate - bDate : bDate - aDate
    })
})

const currentPage = ref(1)
const rowsPerPage = ref(10)
const rowsPerPageOptions = [10, 20, 50]

watch([studentSearch, courseFilter, yearLevelFilter, statusFilter, sortOption], () => {
    currentPage.value = 1
})

const pageCount = computed(() => Math.max(1, Math.ceil(sortedStudents.value.length / rowsPerPage.value)))

watch(sortedStudents, (value) => {
    if (currentPage.value > pageCount.value) {
        currentPage.value = pageCount.value
    }
})

const paginatedStudents = computed(() => {
    const start = (currentPage.value - 1) * rowsPerPage.value
    return sortedStudents.value.slice(start, start + rowsPerPage.value)
})

const pageNumbers = computed(() => {
    return Array.from({ length: pageCount.value }, (_, index) => index + 1)
})

const pageRangeText = computed(() => {
    const start = Math.min(sortedStudents.value.length, (currentPage.value - 1) * rowsPerPage.value + 1)
    const end = Math.min(sortedStudents.value.length, currentPage.value * rowsPerPage.value)
    return `${start}-${end} of ${sortedStudents.value.length} students`
})

const hasFilters = computed(() => {
    return Boolean(studentSearch.value.trim() || courseFilter.value || yearLevelFilter.value || statusFilter.value)
})

function sortStatus(status) {
    return STATUS_OPTIONS.includes(status ? status.toLowerCase() : '')
}

function statusClass(status) {
    const key = String(status || 'unknown').toLowerCase()
    return {
        'status-active': key === 'active',
        'status-inactive': key === 'inactive',
        'status-suspended': key === 'suspended',
        'status-pending': key === 'pending',
        'status-unknown': !['active', 'inactive', 'suspended', 'pending'].includes(key),
    }
}

function generateStudentID() {
    const year = new Date().getFullYear()
    const randomNum = Math.floor(10000 + Math.random() * 90000)
    return `${year}-${randomNum}`
}

function resetStudentForm(isCreate = true) {
    studentForm.value = {
        first_name: '',
        middle_name: '',
        last_name: '',
        student_id: isCreate ? generateStudentID() : '',
        course_base: '',
        major: '',
        concentration: '',
        year_level: '',
        department: '',
        emailPrefix: '',
        password: '',
        confirm_password: '',
    }
    showPassword.value = false
    showConfirmPassword.value = false
    studentEmailEdited.value = false
    studentErrors.value = {}
    initialStudentForm.value = JSON.parse(JSON.stringify(studentForm.value))
}

function validateEmail(value) {
    return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value)
}

function validateStudentForm() {
    const errors = {}

    if (!studentForm.value.first_name) errors.first_name = 'First name is required.'
    if (!studentForm.value.last_name) errors.last_name = 'Last name is required.'
    if (!studentForm.value.student_id) errors.student_id = 'Student ID is required.'
    if (!studentForm.value.course_base) errors.course_base = 'Course is required.'
    if (needsMajorSelection.value && !studentForm.value.major) {
        errors.major = 'Please select a major.'
    }
    if (needsConcentrationSelection.value && !studentForm.value.concentration) {
        errors.concentration = 'Please select a concentration.'
    }
    if (!studentForm.value.year_level) errors.year_level = 'Year level is required.'
    if (!studentForm.value.department) errors.department = 'Department is required.'

    const currentEmail = normalizeEmailPrefix(studentForm.value.emailPrefix)
    const fullEmail = `${currentEmail}@ssct.edu.ph`
    const existingEmails = getExistingStudentEmails(
        studentModalMode.value === 'edit' ? editingStudentId.value : null
    )

    if (!currentEmail) {
        errors.email = 'Email username is required.'
    } else if (!validateEmail(fullEmail)) {
        errors.email = 'Enter a valid SSCT email username.'
    } else if (existingEmails.includes(fullEmail)) {
        errors.email = 'This email already exists. Please use a unique username.'
    }

    if (studentModalMode.value === 'create') {
        if (!studentForm.value.password) {
            errors.password = 'Password is required.'
        } else if (!/^(?=.*[A-Z])(?=.*\d)(?=.*[!_.\-]).{6,}$/.test(studentForm.value.password)) {
            errors.password = 'Password must be at least 6 characters and include at least one uppercase letter, one number, and one of !, _, -, .'
        }

        if (!studentForm.value.confirm_password) {
            errors.confirm_password = 'Please confirm your password.'
        } else if (studentForm.value.password !== studentForm.value.confirm_password) {
            errors.confirm_password = 'Passwords do not match.'
        }
    }

    studentErrors.value = errors
    return Object.keys(errors).length === 0
}

const isStudentFormDirty = computed(() => {
    return JSON.stringify(studentForm.value) !== JSON.stringify(initialStudentForm.value)
})

function parseStudentName(student) {
    const fullName = String(student?.name || '').trim()
    const parts = fullName ? fullName.split(/\s+/) : []

    return {
        firstName: student?.first_name || parts[0] || '',
        middleName: student?.middle_name || (parts.length > 2 ? parts.slice(1, -1).join(' ') : ''),
        lastName: student?.last_name || (parts.length > 1 ? parts[parts.length - 1] : ''),
    }
}

function parseCourseForEdit(courseValue) {
    const course = String(courseValue || '').trim()
    const baseCourse = COURSES.find((option) => course === option || course.startsWith(`${option} `)) || course
    const majorMatch = course.match(/\s+Major in\s+(.+)$/i)
    const concentrationMatch = course.match(/\s+Concentration in\s+(.+)$/i)

    return {
        course_base: baseCourse,
        major: majorMatch?.[1]?.trim() || '',
        concentration: concentrationMatch?.[1]?.trim() || '',
    }
}

function openStudentModal(mode = 'create', student = null) {
    resetMessages()
    studentErrors.value = {}
    studentModalMode.value = mode === 'edit' ? 'edit' : 'create'
    editingStudentId.value = studentModalMode.value === 'edit' ? student?.id ?? null : null

    if (studentModalMode.value === 'edit' && student) {
        const name = parseStudentName(student)
        const course = parseCourseForEdit(student.course)
        const email = String(student.email || '').trim().toLowerCase()

        studentForm.value = {
            first_name: name.firstName,
            middle_name: name.middleName,
            last_name: name.lastName,
            student_id: studentId(student) === '—' ? '' : studentId(student),
            course_base: course.course_base,
            major: course.major,
            concentration: course.concentration,
            year_level: student.year_level || '',
            department: student.department || getDepartmentForCourse(course.course_base),
            emailPrefix: email.endsWith('@ssct.edu.ph')
                ? email.slice(0, -'@ssct.edu.ph'.length)
                : email.split('@')[0] || '',
            password: '',
            confirm_password: '',
        }

        studentEmailEdited.value = true
        showPassword.value = false
        showConfirmPassword.value = false
        initialStudentForm.value = JSON.parse(JSON.stringify(studentForm.value))
    } else {
        resetStudentForm(true)
    }

    showStudentModal.value = true
}

function closeStudentModal() {
    if (studentModalLoading.value) return

    if (isStudentFormDirty.value && !window.confirm('Discard unsaved changes?')) {
        return
    }

    showStudentModal.value = false
    studentModalLoading.value = false
    studentModalMode.value = 'create'
    editingStudentId.value = null
    resetStudentForm(false)
}

async function submitStudent() {
    if (!validateStudentForm()) return

    studentModalLoading.value = true
    resetMessages()

    let fullCourseName = studentForm.value.course_base
    if (needsMajorSelection.value && studentForm.value.major) {
        fullCourseName += ` Major in ${studentForm.value.major}`
    } else if (needsConcentrationSelection.value && studentForm.value.concentration) {
        fullCourseName += ` Concentration in ${studentForm.value.concentration}`
    }

    const payload = {
        first_name: studentForm.value.first_name.trim(),
        middle_name: studentForm.value.middle_name?.trim() ? studentForm.value.middle_name.trim() : null,
        last_name: studentForm.value.last_name.trim(),
        student_id: studentForm.value.student_id,
        course: fullCourseName,
        year_level: studentForm.value.year_level,
        department: studentForm.value.department,
        email: `${normalizeEmailPrefix(studentForm.value.emailPrefix)}@ssct.edu.ph`,
    }

    try {
        if (studentModalMode.value === 'edit') {
            const response = await api.put(`/api/admin/students/${editingStudentId.value}`, payload)
            const updatedStudent = response.data?.data

            if (updatedStudent) {
                students.value = students.value.map((student) =>
                    student.id === editingStudentId.value ? updatedStudent : student
                )
            }

            // Refresh from the authoritative backend after updating profile information.
            await loadListData({ preserveMessages: true })
            statusMessage.value = response.data?.message || 'Student record updated successfully.'
        } else {
            const response = await api.post('/api/admin/students', {
                ...payload,
                password: studentForm.value.password,
            })

            if (response.data?.data) {
                students.value = [response.data.data, ...students.value.filter(Boolean)]
            }

            // A newly created student's status is determined by the server.
            await loadListData({ preserveMessages: true })
            statusMessage.value = response.data?.message || 'Student account created successfully.'
        }

        showStudentModal.value = false
        studentModalMode.value = 'create'
        editingStudentId.value = null
        resetStudentForm(false)
    } catch (error) {
        const validationErrors = error.response?.data?.errors || {}
        studentErrors.value = Object.fromEntries(
            Object.entries(validationErrors).map(([key, value]) => [
                key,
                Array.isArray(value) ? value[0] : value,
            ])
        )
        errorMessage.value = error.response?.data?.message || 'Failed to save student record.'
    } finally {
        studentModalLoading.value = false
    }
}

function resetPasswordForm() {
    passwordForm.value = {
        password: '',
        password_confirmation: '',
    }
    passwordErrors.value = {}
    showNewPassword.value = false
    showNewPasswordConfirmation.value = false
}

function openPasswordModal(student) {
    resetMessages()
    passwordStudent.value = student
    resetPasswordForm()
    showPasswordModal.value = true
}

function closePasswordModal() {
    if (passwordModalLoading.value) return
    showPasswordModal.value = false
    passwordStudent.value = null
    resetPasswordForm()
}

function validatePasswordForm() {
    const errors = {}

    if (!passwordForm.value.password) {
        errors.password = 'New password is required.'
    } else if (!/^(?=.*[A-Z])(?=.*\d)(?=.*[!_.\-]).{6,}$/.test(passwordForm.value.password)) {
        errors.password = 'Password must be at least 6 characters and include at least one uppercase letter, one number, and one of !, _, -, .'
    }

    if (!passwordForm.value.password_confirmation) {
        errors.password_confirmation = 'Please confirm the new password.'
    } else if (passwordForm.value.password !== passwordForm.value.password_confirmation) {
        errors.password_confirmation = 'Passwords do not match.'
    }

    passwordErrors.value = errors
    return Object.keys(errors).length === 0
}

async function submitPasswordChange() {
    if (!passwordStudent.value || !validatePasswordForm()) return

    passwordModalLoading.value = true
    resetMessages()

    try {
        const response = await api.patch(
            `/api/admin/students/${passwordStudent.value.id}/password`,
            {
                password: passwordForm.value.password,
                password_confirmation: passwordForm.value.password_confirmation,
            }
        )

        statusMessage.value = response.data?.message || 'Student password changed successfully.'
        showPasswordModal.value = false
        passwordStudent.value = null
        resetPasswordForm()
    } catch (error) {
        const validationErrors = error.response?.data?.errors || {}
        passwordErrors.value = Object.fromEntries(
            Object.entries(validationErrors).map(([key, value]) => [
                key,
                Array.isArray(value) ? value[0] : value,
            ])
        )
        errorMessage.value = error.response?.data?.message || 'Failed to change student password.'
    } finally {
        passwordModalLoading.value = false
    }
}

function openDetailsModal(student) {
    selectedStudent.value = student
    showDetailsModal.value = true
}

function closeDetailsModal() {
    showDetailsModal.value = false
    selectedStudent.value = null
}

function clearFilters() {
    studentSearch.value = ''
    courseFilter.value = ''
    yearLevelFilter.value = ''
    statusFilter.value = ''
}

function confirmDeleteStudent(student) {
    studentToDelete.value = student
    showDeleteDialog.value = true
}

function cancelDelete() {
    showDeleteDialog.value = false
    studentToDelete.value = null
}

async function executeDeleteStudent() {
    if (!studentToDelete.value) return

    deletingStudentId.value = studentToDelete.value.id
    resetMessages()

    try {
        const response = await api.delete(`/api/admin/students/${studentToDelete.value.id}`)
        students.value = students.value.filter((item) => item.id !== studentToDelete.value.id)
        statusMessage.value = response.data?.message || 'Student record deleted successfully.'
        cancelDelete()
    } catch (error) {
        errorMessage.value = error.response?.data?.message || 'Failed to delete student record.'
    } finally {
        deletingStudentId.value = null
    }
}

async function loadListData({ preserveMessages = false } = {}) {
    if (!preserveMessages) resetMessages()
    listLoading.value = true

    try {
        const response = await api.get('/api/admin/list')
        students.value = Array.isArray(response.data?.data?.students) ? response.data.data.students : []
    } catch (error) {
        // Keep the last known records if a manual refresh fails.
        errorMessage.value = error.response?.data?.message || 'Failed to load student data.'
    } finally {
        listLoading.value = false
    }
}

onMounted(loadListData)
</script>

<style scoped>
/* =========================================================
   PAGE
========================================================= */

.admin-list-shell {
    --bg: #071f16;
    --bg-2: #09271b;
    --panel: #0d3827;
    --panel-2: #123f2e;
    --panel-3: #174b35;
    --green: #67aa7e;
    --green-soft: #9ed1ad;
    --green-bright: #b8e1c3;
    --gold: #e1c875;
    --gold-soft: #f0dda0;
    --text: #f4f8f5;
    --text-soft: #d8e7db;
    --muted: #a8beae;
    --border: rgba(158, 209, 173, 0.15);
    --border-strong: rgba(158, 209, 173, 0.26);

    min-height: calc(100vh - 60px);
    padding: 26px 28px 34px !important;
    color: var(--text) !important;
    background:
        radial-gradient(circle at 8% 0%, rgba(47, 125, 89, 0.22), transparent 34%),
        radial-gradient(circle at 96% 8%, rgba(225, 200, 117, 0.055), transparent 20%),
        linear-gradient(150deg, var(--bg-2), var(--bg)) !important;
    font-family:
        Inter,
        ui-sans-serif,
        system-ui,
        -apple-system,
        BlinkMacSystemFont,
        "Segoe UI",
        sans-serif;
    font-size: 16px;
}

.admin-list-shell *,
.admin-list-shell *::before,
.admin-list-shell *::after {
    box-sizing: border-box;
}


/* =========================================================
   HERO
========================================================= */

.student-hero-card {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 28px;
    min-height: 190px;
    margin-bottom: 18px;
    padding: 30px 32px;
    border: 1px solid rgba(158, 209, 173, 0.17);
    border-radius: 22px;
    background:
        radial-gradient(circle at 92% 20%, rgba(225, 200, 117, 0.08), transparent 25%),
        linear-gradient(135deg, #174b35 0%, #0d3827 58%, #0a2c1f 100%) !important;
    box-shadow:
        0 22px 52px rgba(0, 0, 0, 0.22),
        inset 0 1px 0 rgba(255, 255, 255, 0.035);
}

.hero-copy {
    min-width: 0;
}

.hero-kicker {
    display: flex;
    align-items: center;
    gap: 9px;
    margin-bottom: 10px;
    color: var(--gold);
    font-size: 0.78rem;
    font-weight: 820;
    letter-spacing: 0.13em;
    text-transform: uppercase;
}

.hero-kicker-icon {
    display: grid;
    place-items: center;
    width: 31px;
    height: 31px;
    border: 1px solid rgba(225, 200, 117, 0.24);
    border-radius: 9px;
    background: rgba(225, 200, 117, 0.08);
}

.hero-kicker-icon svg {
    width: 16px;
    height: 16px;
    fill: none;
    stroke: currentColor;
    stroke-width: 1.8;
    stroke-linecap: round;
    stroke-linejoin: round;
}

.student-hero-card h1 {
    margin: 0 !important;
    color: #f8fbf9 !important;
    font-size: clamp(2.15rem, 3vw, 3rem) !important;
    font-weight: 830 !important;
    line-height: 1.06 !important;
    letter-spacing: -0.04em !important;
}

.student-hero-card .hero-copy>p {
    max-width: 760px;
    margin: 13px 0 0 !important;
    color: #d7e6da !important;
    font-size: 1rem !important;
    line-height: 1.7 !important;
}

.hero-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    margin-top: 18px;
}

.hero-meta-chip {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    min-height: 30px;
    padding: 6px 10px;
    border: 1px solid rgba(158, 209, 173, 0.16);
    border-radius: 999px;
    background: rgba(255, 255, 255, 0.035);
    color: #cfe0d3;
    font-size: 0.78rem;
    font-weight: 700;
}

.live-dot,
.matching-dot {
    width: 7px;
    height: 7px;
    border-radius: 999px;
    background: #7fd397;
    box-shadow: 0 0 0 4px rgba(127, 211, 151, 0.1);
}

.hero-actions {
    display: flex;
    align-items: center;
    gap: 10px;
    flex: 0 0 auto;
}

.gold-action,
.outline-action,
.soft-action,
.danger-action,
.pagination-btn {
    font-family: inherit;
    font-weight: 780;
    cursor: pointer;
    transition:
        transform 150ms ease,
        border-color 150ms ease,
        background 150ms ease,
        filter 150ms ease;
}

.gold-action,
.outline-action,
.soft-action,
.danger-action {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    min-height: 43px;
    padding: 0.72rem 1rem;
    border-radius: 11px;
    font-size: 0.88rem;
}

.gold-action {
    border: 1px solid rgba(225, 200, 117, 0.5);
    background: linear-gradient(135deg, #e1c875, #c9ad57) !important;
    color: #10271a !important;
    box-shadow: 0 9px 22px rgba(0, 0, 0, 0.17);
}

.gold-action:hover:not(:disabled) {
    transform: translateY(-1px);
    filter: brightness(1.04);
}

.outline-action,
.soft-action {
    border: 1px solid rgba(158, 209, 173, 0.22);
    background: rgba(103, 170, 126, 0.07) !important;
    color: #deece1 !important;
}

.outline-action:hover:not(:disabled),
.soft-action:hover:not(:disabled) {
    transform: translateY(-1px);
    border-color: rgba(158, 209, 173, 0.38);
    background: rgba(103, 170, 126, 0.13) !important;
}

.danger-action {
    border: 1px solid rgba(255, 180, 180, 0.28);
    background: rgba(160, 55, 55, 0.2) !important;
    color: #ffd1d1 !important;
}

.gold-action svg,
.outline-action svg {
    width: 18px;
    height: 18px;
    fill: none;
    stroke: currentColor;
    stroke-width: 2;
    stroke-linecap: round;
    stroke-linejoin: round;
}

.spinning {
    animation: spin 0.85s linear infinite;
}


/* =========================================================
   STAT CARDS
========================================================= */

.student-stat-grid {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 14px;
    margin-bottom: 18px;
}

.student-stat-card {
    display: flex;
    align-items: center;
    gap: 14px;
    min-width: 0;
    padding: 18px 19px;
    border: 1px solid var(--border);
    border-radius: 16px;
    background:
        linear-gradient(145deg, rgba(18, 63, 46, 0.98), rgba(13, 56, 39, 0.98)) !important;
    box-shadow: 0 12px 28px rgba(0, 0, 0, 0.14);
}

.stat-icon {
    display: grid;
    place-items: center;
    flex: 0 0 auto;
    width: 44px;
    height: 44px;
    border-radius: 12px;
    border: 1px solid rgba(158, 209, 173, 0.19);
    background: rgba(103, 170, 126, 0.08);
    color: var(--green-bright);
}

.stat-icon.gold {
    border-color: rgba(225, 200, 117, 0.22);
    background: rgba(225, 200, 117, 0.08);
    color: var(--gold);
}

.stat-icon.danger {
    border-color: rgba(240, 167, 167, 0.18);
    background: rgba(160, 55, 55, 0.09);
    color: #efb1b1;
}

.stat-icon svg {
    width: 21px;
    height: 21px;
    fill: none;
    stroke: currentColor;
    stroke-width: 1.8;
    stroke-linecap: round;
    stroke-linejoin: round;
}

.student-stat-card span {
    display: block;
    margin-bottom: 2px;
    color: #acc1b1;
    font-size: 0.74rem;
    font-weight: 780;
    letter-spacing: 0.07em;
    text-transform: uppercase;
}

.student-stat-card strong {
    display: block;
    color: #f7fbf8;
    font-size: 1.7rem;
    font-weight: 830;
    line-height: 1.08;
}

.student-stat-card small {
    display: block;
    margin-top: 4px;
    color: #8fa696;
    font-size: 0.78rem;
}


/* =========================================================
   DIRECTORY PANEL
========================================================= */

.student-directory-panel {
    overflow: hidden;
    border: 1px solid var(--border);
    border-radius: 20px;
    background: #0d3827 !important;
    box-shadow: 0 22px 50px rgba(0, 0, 0, 0.18);
}

.directory-panel-head {
    display: flex;
    align-items: flex-end;
    justify-content: space-between;
    gap: 22px;
    padding: 22px 24px 18px;
    border-bottom: 1px solid var(--border);
    background: linear-gradient(135deg, #174b35, #0d3827) !important;
}

.section-kicker,
.modal-kicker,
.delete-kicker {
    margin: 0 0 5px;
    color: var(--green-soft);
    font-size: 0.75rem;
    font-weight: 820;
    letter-spacing: 0.12em;
    text-transform: uppercase;
}

.directory-panel-head h2 {
    margin: 0 !important;
    color: #f4faf5 !important;
    font-size: 1.35rem !important;
    font-weight: 810 !important;
    letter-spacing: -0.015em !important;
}

.directory-panel-head>div>p:not(.section-kicker) {
    margin: 6px 0 0;
    color: #a9c0af;
    font-size: 0.88rem;
    line-height: 1.5;
}

.records-pill {
    flex: 0 0 auto;
    min-width: 80px;
    padding: 9px 12px;
    border: 1px solid rgba(225, 200, 117, 0.16);
    border-radius: 11px;
    background: rgba(225, 200, 117, 0.055);
    text-align: center;
}

.records-pill span {
    display: block;
    color: #9db2a2;
    font-size: 0.65rem;
    font-weight: 760;
    letter-spacing: 0.08em;
    text-transform: uppercase;
}

.records-pill strong {
    display: block;
    margin-top: 2px;
    color: var(--gold-soft);
    font-size: 1rem;
}


/* =========================================================
   FILTERS
========================================================= */

.student-filter-bar {
    display: grid;
    grid-template-columns: minmax(360px, 1.2fr) minmax(620px, 1.8fr);
    gap: 14px;
    padding: 17px 24px 13px;
    background: rgba(7, 31, 22, 0.28);
}

.student-search-field,
.student-filters label {
    display: grid;
    gap: 7px;
}

.field-label {
    color: #bad0c0;
    font-size: 0.72rem;
    font-weight: 800;
    letter-spacing: 0.075em;
    text-transform: uppercase;
}

.student-search-shell {
    position: relative;
}

.student-search-shell input,
.student-filters select,
.student-form input,
.student-form select,
.rows-control select {
    width: 100%;
    min-height: 44px;
    border: 1px solid var(--border-strong);
    border-radius: 11px;
    outline: none;
    background: rgba(7, 31, 22, 0.78) !important;
    color: #f0f8f2 !important;
    font-family: inherit;
    font-size: 0.9rem;
    font-weight: 560;
}

.student-search-shell input {
    padding: 0.74rem 3rem 0.74rem 3.5rem !important;
}

.student-filters select,
.student-form input,
.student-form select,
.rows-control select {
    padding: 0.74rem 0.82rem;
}

.student-search-shell input::placeholder,
.student-form input::placeholder {
    color: #9db2a3 !important;
    opacity: 1 !important;
}

.student-search-shell input:focus,
.student-filters select:focus,
.student-form input:focus,
.student-form select:focus,
.rows-control select:focus {
    border-color: rgba(158, 209, 173, 0.52);
    box-shadow: 0 0 0 4px rgba(103, 170, 126, 0.1);
}

.student-filters select option,
.student-form select option,
.rows-control select option {
    background: #0d3827;
    color: #f0f8f2;
}

.student-search-icon {
    position: absolute;
    top: 50%;
    left: 9px;
    z-index: 2;
    display: grid;
    place-items: center;
    width: 33px;
    height: 33px;
    transform: translateY(-50%);
    border: 1px solid rgba(158, 209, 173, 0.16);
    border-radius: 8px;
    background: rgba(103, 170, 126, 0.075);
    color: var(--green-soft);
    pointer-events: none;
}

.student-search-icon svg {
    width: 16px;
    height: 16px;
    fill: none;
    stroke: currentColor;
    stroke-width: 2;
    stroke-linecap: round;
}

.clear-search-btn {
    position: absolute;
    top: 50%;
    right: 9px;
    z-index: 2;
    display: grid;
    place-items: center;
    width: 29px;
    height: 29px;
    transform: translateY(-50%);
    border: 0;
    border-radius: 7px;
    background: rgba(255, 255, 255, 0.05);
    color: #a8beae;
    font-size: 1.1rem;
    cursor: pointer;
}

.student-filters {
    display: grid;
    grid-template-columns: 1.55fr 0.8fr 0.9fr 0.95fr;
    gap: 9px;
}

.filter-summary-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 14px;
    padding: 0 24px 16px;
    background: rgba(7, 31, 22, 0.28);
}

/* Student-directory PDF action in the visible filter toolbar. */
.filter-summary-actions {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    flex-wrap: wrap;
    gap: 9px;
}

.directory-print-button {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    min-height: 43px;
    padding: 0.68rem 1rem;
    border: 1px solid rgba(225, 200, 117, 0.46);
    border-radius: 11px;
    background: linear-gradient(135deg, #e1c875, #c9ad57) !important;
    color: #10271a !important;
    font: inherit;
    font-size: 0.85rem;
    font-weight: 800;
    cursor: pointer;
    transition: filter 150ms ease, transform 150ms ease;
}

.directory-print-button:hover:not(:disabled) {
    transform: translateY(-1px);
    filter: brightness(1.05);
}

.directory-print-button:disabled {
    opacity: 0.55;
    cursor: not-allowed;
}

.directory-print-button svg {
    width: 17px;
    height: 17px;
    fill: none;
    stroke: currentColor;
    stroke-width: 1.8;
    stroke-linecap: round;
    stroke-linejoin: round;
}

.matching-count {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    color: #d1e2d5;
    font-size: 0.82rem;
}

.matching-count strong {
    color: #f0f7f2;
}


/* =========================================================
   ALERTS / STATES
========================================================= */

.student-alert {
    display: flex;
    align-items: flex-start;
    gap: 11px;
    margin: 16px 24px 0;
    padding: 13px 14px;
    border-radius: 11px;
}

.student-alert.error {
    border: 1px solid rgba(255, 180, 180, 0.2);
    background: rgba(160, 55, 55, 0.13);
    color: #f3c2c2;
}

.student-alert.success {
    border: 1px solid rgba(158, 209, 173, 0.2);
    background: rgba(72, 137, 91, 0.13);
    color: #d6ebda;
}

.student-alert-icon {
    display: grid;
    place-items: center;
    flex: 0 0 auto;
    width: 25px;
    height: 25px;
    border-radius: 7px;
    background: rgba(255, 255, 255, 0.05);
    font-weight: 900;
}

.student-alert strong {
    display: block;
    color: #f3f8f4;
    font-size: 0.88rem;
}

.student-alert p {
    margin: 2px 0 0;
    font-size: 0.82rem;
    line-height: 1.45;
}

.student-table-shell {
    min-height: 260px;
    overflow-x: auto;
}

.student-loading-state,
.student-empty-state {
    min-height: 280px;
}

.student-loading-state {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 14px;
    padding: 30px;
}

.student-loading-state strong {
    color: #f0f7f2;
    font-size: 0.94rem;
}

.student-loading-state p {
    margin: 3px 0 0;
    color: #92a999;
    font-size: 0.8rem;
}

.student-loader {
    width: 32px;
    height: 32px;
    border: 3px solid rgba(158, 209, 173, 0.12);
    border-top-color: var(--gold);
    border-radius: 999px;
    animation: spin 0.85s linear infinite;
}

.student-empty-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 42px 24px;
    text-align: center;
}

.student-empty-icon {
    display: grid;
    place-items: center;
    width: 58px;
    height: 58px;
    margin-bottom: 14px;
    border: 1px solid var(--border-strong);
    border-radius: 16px;
    background: rgba(103, 170, 126, 0.07);
    color: var(--green-soft);
}

.student-empty-icon svg {
    width: 27px;
    height: 27px;
    fill: none;
    stroke: currentColor;
    stroke-width: 1.8;
    stroke-linecap: round;
    stroke-linejoin: round;
}

.student-empty-state h3 {
    margin: 0;
    color: #f2f8f4;
    font-size: 1.14rem;
    font-weight: 810;
}

.student-empty-state p {
    max-width: 500px;
    margin: 7px 0 17px;
    color: #99ad9f;
    font-size: 0.87rem;
    line-height: 1.55;
}


/* =========================================================
   TABLE
========================================================= */

.student-records-table {
    width: 100%;
    min-width: 1120px;
    border-collapse: collapse;
}

.student-records-table thead {
    background: #0a2c1f;
}

.student-records-table th {
    padding: 14px 16px;
    border-top: 1px solid rgba(158, 209, 173, 0.07);
    border-bottom: 1px solid rgba(158, 209, 173, 0.13);
    color: #b7cdbd;
    font-size: 0.72rem;
    font-weight: 820;
    letter-spacing: 0.075em;
    text-align: left;
    text-transform: uppercase;
    white-space: nowrap;
}

.student-records-table td {
    padding: 15px 16px;
    border-bottom: 1px solid rgba(158, 209, 173, 0.075);
    color: #d3e2d6;
    font-size: 0.88rem;
    vertical-align: middle;
}

.student-records-table tbody tr {
    background: rgba(255, 255, 255, 0.004);
    transition: background 140ms ease;
}

.student-records-table tbody tr:hover {
    background: rgba(103, 170, 126, 0.045);
}

.student-person {
    display: flex;
    align-items: center;
    gap: 11px;
    min-width: 240px;
}

.student-person img {
    flex: 0 0 auto;
    width: 43px;
    height: 43px;
    border: 1px solid rgba(158, 209, 173, 0.2);
    border-radius: 12px;
    object-fit: cover;
    background: #174b35;
}

.student-person>div {
    min-width: 0;
}

.student-person strong {
    display: block;
    overflow: hidden;
    max-width: 260px;
    color: #f2f8f4;
    font-size: 0.92rem;
    font-weight: 760;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.student-person span {
    display: block;
    overflow: hidden;
    max-width: 280px;
    margin-top: 2px;
    color: #8fa495;
    font-size: 0.75rem;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.student-id-badge {
    display: inline-flex;
    align-items: center;
    min-height: 29px;
    padding: 5px 8px;
    border: 1px solid rgba(158, 209, 173, 0.15);
    border-radius: 7px;
    background: rgba(103, 170, 126, 0.055);
    color: #d7e7da;
    font-size: 0.8rem;
    font-weight: 720;
}

.student-email {
    color: #c8d9cc;
    font-size: 0.84rem;
}

.student-program {
    display: grid;
    max-width: 300px;
    gap: 2px;
}

.student-program strong {
    overflow: hidden;
    color: #e9f2eb;
    font-size: 0.84rem;
    font-weight: 700;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.student-program span {
    color: #8da393;
    font-size: 0.74rem;
}

.student-status-badge {
    display: inline-flex;
    align-items: center;
    min-height: 27px;
    padding: 5px 9px;
    border-radius: 999px;
    font-size: 0.7rem;
    font-weight: 820;
    white-space: nowrap;
}

.status-active {
    border: 1px solid rgba(158, 209, 173, 0.2);
    background: rgba(103, 170, 126, 0.1);
    color: #c7e7cf;
}

.status-inactive {
    border: 1px solid rgba(158, 209, 173, 0.13);
    background: rgba(120, 145, 127, 0.09);
    color: #bcc9bf;
}

.status-suspended {
    border: 1px solid rgba(255, 180, 180, 0.2);
    background: rgba(160, 55, 55, 0.13);
    color: #f1baba;
}

.status-pending {
    border: 1px solid rgba(225, 200, 117, 0.2);
    background: rgba(225, 200, 117, 0.075);
    color: var(--gold-soft);
}

.status-unknown {
    border: 1px solid rgba(180, 194, 184, 0.2);
    background: rgba(180, 194, 184, 0.07);
    color: #cad5cd;
}

.actions-heading {
    text-align: right !important;
}

.student-actions {
    display: flex;
    justify-content: flex-end;
    gap: 7px;
}

.table-action {
    display: grid;
    place-items: center;
    width: 36px;
    height: 36px;
    border-radius: 9px;
    cursor: pointer;
    transition:
        transform 140ms ease,
        background 140ms ease,
        border-color 140ms ease;
}

.table-action.view {
    border: 1px solid rgba(158, 209, 173, 0.19);
    background: rgba(103, 170, 126, 0.07);
    color: #cce5d2;
}

.table-action.edit {
    border: 1px solid rgba(225, 200, 117, 0.22);
    background: rgba(225, 200, 117, 0.075);
    color: var(--gold-soft);
}

.table-action.password {
    border: 1px solid rgba(145, 190, 255, 0.2);
    background: rgba(70, 120, 185, 0.1);
    color: #bed7ff;
}

.table-action.delete {
    border: 1px solid rgba(255, 180, 180, 0.17);
    background: rgba(160, 55, 55, 0.1);
    color: #efb1b1;
}

.table-action:hover:not(:disabled) {
    transform: translateY(-1px);
}

.table-action.view:hover {
    border-color: rgba(158, 209, 173, 0.34);
    background: rgba(103, 170, 126, 0.13);
}

.table-action.edit:hover {
    border-color: rgba(225, 200, 117, 0.4);
    background: rgba(225, 200, 117, 0.13);
}

.table-action.password:hover {
    border-color: rgba(145, 190, 255, 0.36);
    background: rgba(70, 120, 185, 0.18);
}

.table-action.delete:hover {
    border-color: rgba(255, 180, 180, 0.3);
    background: rgba(160, 55, 55, 0.18);
}

.table-action svg {
    width: 16px;
    height: 16px;
    fill: currentColor;
}


/* =========================================================
   PAGINATION
========================================================= */

.student-pagination {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 14px;
    padding: 15px 24px;
    border-top: 1px solid var(--border);
    background: #0a2c1f;
}

.pagination-summary {
    color: #9bb0a1;
    font-size: 0.8rem;
}

.pagination-controls {
    display: flex;
    align-items: center;
    gap: 6px;
    flex-wrap: wrap;
}

.pagination-btn {
    min-height: 35px;
    min-width: 35px;
    padding: 0 9px;
    border: 1px solid rgba(158, 209, 173, 0.17);
    border-radius: 8px;
    background: rgba(255, 255, 255, 0.025);
    color: #cfe0d3;
    font-size: 0.78rem;
}

.pagination-btn.active {
    border-color: rgba(225, 200, 117, 0.45);
    background: var(--gold);
    color: #10271a;
}

.rows-control {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    margin-left: 5px;
    color: #8fa495;
    font-size: 0.74rem;
    font-weight: 700;
}

.rows-control select {
    width: 70px;
    min-height: 34px;
    padding: 0.4rem 0.55rem;
}


/* =========================================================
   MODALS
========================================================= */

.student-modal-overlay {
    position: fixed;
    inset: 0;
    z-index: 1200;
    display: grid;
    place-items: center;
    padding: 22px;
    overflow-y: auto;
    background: rgba(2, 12, 8, 0.84);
    backdrop-filter: blur(7px);
}

.student-modal {
    position: relative;
    width: min(900px, calc(100vw - 36px));
    max-height: calc(100vh - 44px);
    overflow: auto;
    border: 1px solid rgba(158, 209, 173, 0.2);
    border-radius: 22px;
    background: #0d3827 !important;
    color: var(--text);
    box-shadow: 0 30px 88px rgba(0, 0, 0, 0.48);
}

.student-modal-large {
    width: min(920px, calc(100vw - 36px));
}

.student-modal-header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 16px;
    padding: 24px 26px 20px;
    border-bottom: 1px solid var(--border);
    background: linear-gradient(135deg, #174b35, #0d3827) !important;
}

.student-modal-title {
    display: flex;
    align-items: flex-start;
    gap: 12px;
}

.modal-icon {
    display: grid;
    place-items: center;
    flex: 0 0 auto;
    width: 39px;
    height: 39px;
    border-radius: 11px;
}

.modal-icon.gold {
    border: 1px solid rgba(225, 200, 117, 0.2);
    background: rgba(225, 200, 117, 0.07);
    color: var(--gold);
}

.modal-icon svg {
    width: 18px;
    height: 18px;
    fill: none;
    stroke: currentColor;
    stroke-width: 2;
    stroke-linecap: round;
}

.modal-kicker {
    color: var(--gold);
}

.student-modal-header h2 {
    margin: 0;
    color: #f6fbf7;
    font-size: 1.5rem;
    font-weight: 820;
    letter-spacing: -0.02em;
}

.student-modal-header p:not(.modal-kicker) {
    margin: 5px 0 0;
    color: #a8beae;
    font-size: 0.84rem;
    line-height: 1.45;
}

.modal-close-btn {
    display: grid;
    place-items: center;
    flex: 0 0 auto;
    width: 36px;
    height: 36px;
    border: 1px solid rgba(158, 209, 173, 0.15);
    border-radius: 10px;
    background: rgba(7, 31, 22, 0.5);
    color: #d9e7dc;
    font-size: 1.28rem;
    cursor: pointer;
}


/* =========================================================
   FORM
========================================================= */

.student-form {
    display: grid;
    gap: 16px;
    padding: 20px 26px 0;
}

.student-form-section {
    display: grid;
    gap: 15px;
    padding: 17px;
    border: 1px solid rgba(158, 209, 173, 0.13);
    border-radius: 14px;
    background: rgba(7, 31, 22, 0.25);
}

.form-section-title {
    display: flex;
    align-items: flex-start;
    gap: 10px;
    padding-bottom: 12px;
    border-bottom: 1px solid rgba(158, 209, 173, 0.08);
}

.form-section-title>span {
    display: grid;
    place-items: center;
    flex: 0 0 auto;
    width: 30px;
    height: 30px;
    border: 1px solid rgba(225, 200, 117, 0.17);
    border-radius: 8px;
    background: rgba(225, 200, 117, 0.055);
    color: var(--gold);
    font-size: 0.7rem;
    font-weight: 850;
}

.form-section-title h3 {
    margin: 0;
    color: #eef6f0;
    font-size: 0.96rem;
    font-weight: 800;
}

.form-section-title p {
    margin: 2px 0 0;
    color: #8fa495;
    font-size: 0.76rem;
    line-height: 1.4;
}

.student-form label {
    display: grid;
    gap: 6px;
    color: #d6e5d9;
    font-size: 0.82rem;
    font-weight: 720;
}

.three-column-grid {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 11px;
}

.two-column-grid {
    display: grid;
    grid-template-columns: 0.75fr 1.25fr;
    gap: 11px;
}

.email-row {
    display: flex;
    align-items: stretch;
    gap: 8px;
}

.email-row input {
    flex: 1;
}

.email-domain {
    display: inline-flex;
    align-items: center;
    padding: 0 12px;
    border: 1px solid rgba(158, 209, 173, 0.16);
    border-radius: 10px;
    background: rgba(103, 170, 126, 0.06);
    color: #cfe1d3;
    font-size: 0.81rem;
    font-weight: 720;
}

.password-shell {
    position: relative;
    display: flex;
    align-items: center;
}

.password-shell input {
    padding-right: 126px;
}

.generate-btn {
    position: absolute;
    top: 50%;
    right: 40px;
    transform: translateY(-50%);
    min-height: 27px;
    padding: 0 8px;
    border: 1px solid rgba(225, 200, 117, 0.18);
    border-radius: 7px;
    background: rgba(225, 200, 117, 0.07);
    color: var(--gold-soft);
    font-size: 0.67rem;
    font-weight: 800;
    cursor: pointer;
}

.password-eye {
    position: absolute;
    top: 50%;
    right: 7px;
    display: grid;
    place-items: center;
    width: 28px;
    height: 28px;
    transform: translateY(-50%);
    border: 0;
    border-radius: 7px;
    background: rgba(255, 255, 255, 0.035);
    color: #a8beae;
    cursor: pointer;
}

.password-eye svg {
    width: 16px;
    height: 16px;
    fill: currentColor;
}

.password-note,
.email-warning,
.field-error {
    display: block;
    font-size: 0.73rem;
    line-height: 1.42;
}

.password-note {
    color: #8fa495;
}

.email-warning {
    color: var(--gold-soft);
}

.field-error {
    color: #f0b3b3;
    font-weight: 650;
}

.student-modal-actions {
    display: flex;
    justify-content: flex-end;
    gap: 9px;
    margin: 0 -26px;
    padding: 15px 26px;
    border-top: 1px solid var(--border);
    background: #0a2c1f;
}


/* =========================================================
   PROFILE
========================================================= */

.profile-header {
    align-items: center;
}

.profile-heading {
    display: flex;
    align-items: center;
    gap: 14px;
}

.profile-heading>img {
    flex: 0 0 auto;
    width: 72px;
    height: 72px;
    border: 2px solid rgba(225, 200, 117, 0.24);
    border-radius: 18px;
    object-fit: cover;
    background: #174b35;
}

.profile-heading .student-status-badge {
    margin-top: 8px;
}

.profile-detail-grid {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 10px;
    padding: 20px 26px 23px;
}

.profile-detail-card {
    display: grid;
    gap: 4px;
    min-width: 0;
    padding: 12px 13px;
    border: 1px solid rgba(158, 209, 173, 0.09);
    border-radius: 11px;
    background: rgba(7, 31, 22, 0.24);
}

.profile-detail-card.wide {
    grid-column: span 2;
}

.profile-detail-card.accent {
    border-color: rgba(225, 200, 117, 0.13);
    background: rgba(225, 200, 117, 0.04);
}

.profile-detail-card span {
    color: #819789;
    font-size: 0.67rem;
    font-weight: 770;
    letter-spacing: 0.07em;
    text-transform: uppercase;
}

.profile-detail-card strong {
    overflow-wrap: anywhere;
    color: #dce9df;
    font-size: 0.86rem;
    font-weight: 700;
    line-height: 1.4;
}

.profile-detail-card.accent strong {
    color: var(--gold-soft);
}


/* =========================================================
   PASSWORD MODAL
========================================================= */

.password-modal {
    width: min(580px, calc(100vw - 36px));
}

.password-student-summary {
    display: grid;
    gap: 3px;
    padding: 13px 14px;
    border: 1px solid rgba(225, 200, 117, 0.13);
    border-radius: 11px;
    background: rgba(225, 200, 117, 0.04);
}

.password-student-summary span {
    color: #8fa495;
    font-size: 0.67rem;
    font-weight: 800;
    letter-spacing: 0.08em;
    text-transform: uppercase;
}

.password-student-summary strong {
    color: #f2f8f4;
    font-size: 0.92rem;
}

.password-student-summary small {
    color: #a8beae;
    font-size: 0.78rem;
}

/* =========================================================
   DELETE MODAL
========================================================= */

.delete-modal {
    width: min(490px, calc(100vw - 36px));
    padding: 28px;
    text-align: center;
}

.delete-warning-icon {
    display: grid;
    place-items: center;
    width: 56px;
    height: 56px;
    margin: 0 auto 14px;
    border: 1px solid rgba(255, 180, 180, 0.2);
    border-radius: 16px;
    background: rgba(160, 55, 55, 0.11);
    color: #efb2b2;
}

.delete-warning-icon svg {
    width: 27px;
    height: 27px;
    fill: none;
    stroke: currentColor;
    stroke-width: 1.8;
    stroke-linecap: round;
    stroke-linejoin: round;
}

.delete-kicker {
    color: #efb2b2;
}

.delete-modal h3 {
    margin: 0;
    color: #f5faf6;
    font-size: 1.4rem;
    font-weight: 820;
}

.delete-modal>p:not(.delete-kicker) {
    margin: 10px 0 0;
    color: #a8beae;
    font-size: 0.87rem;
    line-height: 1.55;
}

.delete-modal p strong {
    color: #f2f8f4;
}

.delete-actions {
    display: flex;
    justify-content: flex-end;
    gap: 9px;
    margin-top: 22px;
}


/* =========================================================
   FOCUS / RESPONSIVE
========================================================= */

button:focus-visible,
input:focus-visible,
select:focus-visible {
    outline: none;
    box-shadow: 0 0 0 4px rgba(225, 200, 117, 0.14);
}

@keyframes spin {
    to {
        transform: rotate(360deg);
    }
}

@media (max-width: 1220px) {
    .student-stat-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .student-filter-bar {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 900px) {
    .student-filters {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .directory-panel-head {
        align-items: flex-start;
        flex-direction: column;
    }

    .profile-detail-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}

@media (max-width: 760px) {
    .admin-list-shell {
        padding: 18px !important;
    }

    .student-hero-card {
        align-items: stretch;
        flex-direction: column;
        min-height: auto;
        padding: 23px;
    }

    .hero-actions {
        width: 100%;
    }

    .hero-actions button {
        flex: 1;
    }

    .student-filter-bar,
    .filter-summary-row,
    .directory-panel-head {
        padding-left: 18px;
        padding-right: 18px;
    }

    .filter-summary-row,
    .student-pagination {
        align-items: stretch;
        flex-direction: column;
    }

    .filter-summary-actions {
        justify-content: flex-start;
    }

    .directory-print-button {
        flex: 1;
    }

    .pagination-controls {
        justify-content: flex-start;
    }

    .student-modal-overlay {
        padding: 13px;
    }

    .student-modal {
        max-height: calc(100vh - 26px);
    }

    .student-modal-header {
        padding: 21px 18px;
    }

    .student-form {
        padding: 18px 18px 0;
    }

    .student-modal-actions {
        margin-left: -18px;
        margin-right: -18px;
        padding-left: 18px;
        padding-right: 18px;
    }

    .three-column-grid,
    .two-column-grid,
    .profile-detail-grid {
        grid-template-columns: 1fr;
    }

    .profile-detail-card.wide {
        grid-column: auto;
    }

    .email-row {
        flex-direction: column;
    }

    .email-domain {
        min-height: 42px;
        justify-content: center;
    }
}

@media (max-width: 560px) {
    .admin-list-shell {
        padding: 14px !important;
    }

    .student-stat-grid,
    .student-filters {
        grid-template-columns: 1fr;
    }

    .student-hero-card h1 {
        font-size: 1.9rem !important;
    }

    .hero-actions {
        flex-direction: column;
    }

    .hero-actions button {
        width: 100%;
    }

    .profile-heading {
        align-items: flex-start;
        flex-direction: column;
    }

    .student-modal-actions,
    .delete-actions {
        align-items: stretch;
        flex-direction: column-reverse;
    }

    .student-modal-actions button,
    .delete-actions button {
        width: 100%;
    }
}
</style>
