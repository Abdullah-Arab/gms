<?php
session_start();
require_once __DIR__ . '/includes/User.php';
require_once __DIR__ . '/includes/Goal.php';
require_once __DIR__ . '/includes/Milestone.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

// Check if goal_id is provided
if (!isset($_GET['id'])) {
    header('Location: index.php');
    exit;
}

$goalId = $_GET['id'];
$goal = new Goal();
$goalData = $goal->getGoal($goalId, $_SESSION['user_id']);

// Verify goal ownership
if (!$goalData) {
    header('Location: index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Goal Details - <?php echo htmlspecialchars($goalData['title']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="index.php">GMS</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">
                            <i class="bi bi-arrow-left"></i> Back to Goals
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#" id="logoutBtn">
                            <i class="bi bi-box-arrow-right"></i> Logout
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <div class="row mb-4">
            <div class="col">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <h2><?php echo htmlspecialchars($goalData['title']); ?></h2>
                        <p class="text-muted"><?php echo htmlspecialchars($goalData['description']); ?></p>
                    </div>
                    <div>
                        <span class="badge bg-<?php echo getPriorityColor($goalData['priority']); ?> mb-2 d-block">
                            <?php echo ucfirst($goalData['priority']); ?> Priority
                        </span>
                        <span class="badge bg-<?php echo getStatusColor($goalData['status']); ?>">
                            <?php echo formatStatus($goalData['status']); ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mb-4">
            <div class="col">
                <div class="card">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-3 text-center">
                                <h5>Deadline</h5>
                                <p class="text-muted"><?php echo date('M j, Y', strtotime($goalData['deadline'])); ?></p>
                            </div>
                            <div class="col-md-3 text-center">
                                <h5>Total Milestones</h5>
                                <p class="text-muted"><?php echo $goalData['total_milestones']; ?></p>
                            </div>
                            <div class="col-md-3 text-center">
                                <h5>Completed</h5>
                                <p class="text-muted"><?php echo $goalData['completed_milestones']; ?></p>
                            </div>
                            <div class="col-md-3 text-center">
                                <h5>Progress</h5>
                                <div class="progress">
                                    <div class="progress-bar" role="progressbar" 
                                         style="width: <?php echo $goalData['progress']; ?>%"
                                         aria-valuenow="<?php echo $goalData['progress']; ?>" 
                                         aria-valuemin="0" 
                                         aria-valuemax="100">
                                        <?php echo round($goalData['progress']); ?>%
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mb-4">
            <div class="col-auto ms-auto">
                <button class="btn btn-primary" id="addMilestoneBtn">
                    <i class="bi bi-plus-circle"></i> Add Milestone
                </button>
            </div>
        </div>

        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Milestones</h5>
                    </div>
                    <div class="card-body">
                        <div id="milestonesList"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Add/Edit Milestone Modal -->
    <div class="modal fade" id="milestoneModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add Milestone</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="goalId" value="<?php echo htmlspecialchars($goalId); ?>">
                    <div class="mb-3">
                        <label for="milestoneTitle" class="form-label">Title</label>
                        <input type="text" class="form-control" id="milestoneTitle" required>
                    </div>
                    <div class="mb-3">
                        <label for="milestoneDate" class="form-label">Completion Date</label>
                        <input type="datetime-local" class="form-control" id="milestoneDate" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="saveMilestone">Save</button>
                </div>
            </div>
        </div>
    </div>

    <style>
        /* Custom styles for the accordion */
        .accordion-button::after {
            margin-left: 1rem;
        }
        
        .accordion-button:not(.collapsed) {
            background-color: #f8f9fa;
            color: inherit;
        }
        
        .todo-section {
            padding: 1rem 0;
        }
        
        .todo-list .list-group-item {
            border-left: none;
            border-right: none;
            border-radius: 0;
        }
        
        .todo-list .list-group-item:first-child {
            border-top: none;
        }
        
        .todo-list .list-group-item:last-child {
            border-bottom: none;
        }
        
        .form-check-input:checked + .form-check-label {
            text-decoration: line-through;
            color: #6c757d;
        }
        
        .milestone-status {
            z-index: 3;
        }
    </style>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="assets/js/goal-details.js"></script>

    <?php
    function getPriorityColor($priority) {
        switch ($priority) {
            case 'low': return 'info';
            case 'medium': return 'warning';
            case 'high': return 'danger';
            default: return 'secondary';
        }
    }

    function getStatusColor($status) {
        switch ($status) {
            case 'not_started': return 'secondary';
            case 'in_progress': return 'primary';
            case 'completed': return 'success';
            default: return 'secondary';
        }
    }

    function formatStatus($status) {
        return ucwords(str_replace('_', ' ', $status));
    }
    ?>
</body>
</html>
