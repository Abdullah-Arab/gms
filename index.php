<?php
session_start();
require_once __DIR__ . '/includes/User.php';
require_once __DIR__ . '/includes/Goal.php';
require_once __DIR__ . '/includes/Milestone.php';

// Check if user is logged in
$isLoggedIn = isset($_SESSION['user_id']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Goal Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="#">GMS</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <?php if ($isLoggedIn): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="#" id="addGoalBtn">
                                <i class="bi bi-plus-circle"></i> New Goal
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#" id="logoutBtn">
                                <i class="bi bi-box-arrow-right"></i> Logout
                            </a>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link" href="#" id="loginBtn">Login</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#" id="registerBtn">Register</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <?php if (!$isLoggedIn): ?>
            <div id="authForms">
                <!-- Login Form -->
                <div id="loginForm" class="auth-form">
                    <h2>Login</h2>
                    <form id="loginFormSubmit">
                        <div class="mb-3">
                            <label for="loginEmail" class="form-label">Email</label>
                            <input type="email" class="form-control" id="loginEmail" required>
                        </div>
                        <div class="mb-3">
                            <label for="loginPassword" class="form-label">Password</label>
                            <input type="password" class="form-control" id="loginPassword" required>
                        </div>
                        <button type="submit" class="btn btn-primary">Login</button>
                    </form>
                </div>

                <!-- Register Form -->
                <div id="registerForm" class="auth-form" style="display: none;">
                    <h2>Register</h2>
                    <form id="registerFormSubmit">
                        <div class="mb-3">
                            <label for="registerEmail" class="form-label">Email</label>
                            <input type="email" class="form-control" id="registerEmail" required>
                        </div>
                        <div class="mb-3">
                            <label for="registerPassword" class="form-label">Password</label>
                            <input type="password" class="form-control" id="registerPassword" required>
                        </div>
                        <div class="mb-3">
                            <label for="confirmPassword" class="form-label">Confirm Password</label>
                            <input type="password" class="form-control" id="confirmPassword" required>
                        </div>
                        <button type="submit" class="btn btn-primary">Register</button>
                    </form>
                </div>
            </div>
        <?php else: ?>
            <!-- Goals Dashboard -->
            <div id="goalsDashboard">
                <div class="row mb-4">
                    <div class="col">
                        <h2>My Goals</h2>
                    </div>
                    <div class="col-auto">
                        <div class="btn-group">
                            <button class="btn btn-outline-primary" data-filter="all">All</button>
                            <button class="btn btn-outline-primary" data-filter="pending">Pending</button>
                            <button class="btn btn-outline-primary" data-filter="in_progress">In Progress</button>
                            <button class="btn btn-outline-primary" data-filter="completed">Completed</button>
                        </div>
                    </div>
                </div>

                <div id="goalsList" class="row">
                    <!-- Goals will be dynamically loaded here -->
                </div>
            </div>

            <!-- Goal Modal -->
            <div class="modal fade" id="goalModal" tabindex="-1">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Add New Goal</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <form id="goalForm">
                                <div class="mb-3">
                                    <label for="goalTitle" class="form-label">Title</label>
                                    <input type="text" class="form-control" id="goalTitle" required>
                                </div>
                                <div class="mb-3">
                                    <label for="goalDescription" class="form-label">Description</label>
                                    <textarea class="form-control" id="goalDescription" rows="3"></textarea>
                                </div>
                                <div class="mb-3">
                                    <label for="goalDeadline" class="form-label">Deadline</label>
                                    <input type="datetime-local" class="form-control" id="goalDeadline" required>
                                </div>
                                <div class="mb-3">
                                    <label for="goalPriority" class="form-label">Priority</label>
                                    <select class="form-control" id="goalPriority">
                                        <option value="low">Low</option>
                                        <option value="medium" selected>Medium</option>
                                        <option value="high">High</option>
                                    </select>
                                </div>
                            </form>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                            <button type="button" class="btn btn-primary" id="saveGoal">Save Goal</button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Milestone Modal -->
            <div class="modal fade" id="milestoneModal" tabindex="-1">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Add Milestone</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <form id="milestoneForm">
                                <input type="hidden" id="goalId">
                                <div class="mb-3">
                                    <label for="milestoneTitle" class="form-label">Title</label>
                                    <input type="text" class="form-control" id="milestoneTitle" required>
                                </div>
                                <div class="mb-3">
                                    <label for="milestoneDate" class="form-label">Completion Date</label>
                                    <input type="datetime-local" class="form-control" id="milestoneDate" required>
                                </div>
                            </form>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                            <button type="button" class="btn btn-primary" id="saveMilestone">Save Milestone</button>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="assets/js/main.js"></script>
</body>
</html>
