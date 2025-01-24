<?php
require_once 'includes/auth.php';
session_start();

// Check if user is logged in
if (!isLoggedIn()) {
    header('Location: index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Goal Management System - Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .sortable {
            cursor: pointer;
            user-select: none;
        }
        .sortable:hover {
            background-color: rgba(0,0,0,0.05);
        }
        .sortable i {
            margin-left: 5px;
            opacity: 0.5;
        }
        .sortable i.bi-arrow-up,
        .sortable i.bi-arrow-down {
            opacity: 1;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="#">Goal Management System</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link active" href="home.php">Dashboard</a>
                    </li>
                </ul>
                <button class="btn btn-light" id="logoutBtn">Logout</button>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <!-- Reports Section -->
        <div class="row mb-4">
            <div class="col">
                <h2>Dashboard</h2>
            </div>
            <div class="col-auto">
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#goalModal">
                    <i class="bi bi-plus-circle"></i> Add Goal
                </button>
            </div>
        </div>

        <!-- Summary Cards -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card bg-primary text-white">
                    <div class="card-body">
                        <h5 class="card-title">Total Goals</h5>
                        <h2 class="card-text" id="totalGoals">0</h2>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-success text-white">
                    <div class="card-body">
                        <h5 class="card-title">Completed Goals</h5>
                        <h2 class="card-text" id="completedGoals">0</h2>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-info text-white">
                    <div class="card-body">
                        <h5 class="card-title">Total Milestones</h5>
                        <h2 class="card-text" id="totalMilestones">0</h2>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-warning text-white">
                    <div class="card-body">
                        <h5 class="card-title">Todo Completion</h5>
                        <h2 class="card-text" id="todoCompletion">0%</h2>
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts Row -->
        <div class="row mb-4">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Goals by Status</h5>
                    </div>
                    <div class="card-body">
                        <canvas id="goalStatusChart"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Goals by Priority</h5>
                    </div>
                    <div class="card-body">
                        <canvas id="goalPriorityChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Monthly Trend and Upcoming Milestones -->
        <div class="row">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Monthly Goal Completion Trend</h5>
                    </div>
                    <div class="card-body">
                        <canvas id="monthlyTrendChart"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Upcoming Milestones</h5>
                    </div>
                    <div class="card-body">
                        <div id="upcomingMilestones" class="list-group list-group-flush">
                            <!-- Upcoming milestones will be loaded here -->
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Goals List Section -->
        <div class="row mt-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">My Goals</h5>
                    </div>
                    <div class="card-body">
                        <div id="goalsList"></div>
                    </div>
                </div>
            </div>
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
                        <select class="form-select" id="goalPriority">
                            <option value="low">Low</option>
                            <option value="medium" selected>Medium</option>
                            <option value="high">High</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="saveGoal">Save</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="assets/js/home.js"></script>
</body>
</html>
