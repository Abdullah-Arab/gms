<?php
// Check if this file is being accessed directly
if (!defined('INCLUDED')) {
    header('Location: index.php');
    exit;
}
?>
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
<div class="row mb-4">
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

<!-- Goals List -->
<div class="row">
    <div class="col">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">My Goals</h5>
                <button class="btn btn-primary add-goal-btn">
                    <i class="bi bi-plus-circle"></i> Add Goal
                </button>
            </div>
            <div class="card-body">
                <div id="goalsList"></div>
            </div>
        </div>
    </div>
</div>
