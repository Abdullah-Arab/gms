$(document).ready(function() {
    // Authentication handling
    $('#loginBtn').click(function() {
        $('#registerForm').hide();
        $('#loginForm').show();
    });

    $('#registerBtn').click(function() {
        $('#loginForm').hide();
        $('#registerForm').show();
    });

    $('#loginFormSubmit').submit(function(e) {
        e.preventDefault();
        $.ajax({
            url: 'api/auth.php',
            method: 'POST',
            data: {
                action: 'login',
                email: $('#loginEmail').val(),
                password: $('#loginPassword').val()
            },
            success: function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    alert(response.message);
                }
            }
        });
    });

    $('#registerFormSubmit').submit(function(e) {
        e.preventDefault();
        if ($('#registerPassword').val() !== $('#confirmPassword').val()) {
            alert('Passwords do not match');
            return;
        }

        $.ajax({
            url: 'api/auth.php',
            method: 'POST',
            data: {
                action: 'register',
                email: $('#registerEmail').val(),
                password: $('#registerPassword').val()
            },
            success: function(response) {
                if (response.success) {
                    $('#loginBtn').click();
                    alert('Registration successful. Please login.');
                } else {
                    alert(response.message);
                }
            }
        });
    });

    $('#logoutBtn').click(function() {
        $.ajax({
            url: 'api/auth.php',
            method: 'POST',
            data: { action: 'logout' },
            success: function() {
                location.reload();
            }
        });
    });

    // Goals handling
    function loadGoals(filter = 'all') {
        $.ajax({
            url: 'api/goals.php',
            method: 'GET',
            data: { action: 'list', filter: filter },
            success: function(response) {
                if (response.success) {
                    displayGoals(response.goals);
                }
            }
        });
    }

    function displayGoals(goals) {
        const goalsList = $('#goalsList');
        goalsList.empty();

        goals.forEach(goal => {
            const deadline = new Date(goal.deadline);
            const progress = goal.progress || 0;
            const card = $(`
                <div class="col-md-4 mb-4">
                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start">
                                <h5 class="card-title">${goal.title}</h5>
                                <span class="badge bg-${getPriorityColor(goal.priority)}">${goal.priority}</span>
                            </div>
                            <p class="card-text">${goal.description}</p>
                            <div class="progress mb-3">
                                <div class="progress-bar" role="progressbar" style="width: ${progress}%">
                                    ${Math.round(progress)}%
                                </div>
                            </div>
                            <p class="text-muted">
                                <i class="bi bi-calendar"></i> 
                                ${deadline.toLocaleDateString()}
                            </p>
                            <div class="btn-group">
                                <button class="btn btn-sm btn-outline-primary view-milestones" data-goal-id="${goal.id}">
                                    Milestones
                                </button>
                                <button class="btn btn-sm btn-outline-success edit-goal" data-goal-id="${goal.id}">
                                    Edit
                                </button>
                                <button class="btn btn-sm btn-outline-danger delete-goal" data-goal-id="${goal.id}">
                                    Delete
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            `);
            goalsList.append(card);
        });
    }

    function getPriorityColor(priority) {
        switch(priority) {
            case 'high': return 'danger';
            case 'medium': return 'warning';
            case 'low': return 'info';
            default: return 'secondary';
        }
    }

    // Goal Modal handling
    let editingGoalId = null;

    $('#addGoalBtn').click(function() {
        editingGoalId = null;
        $('#goalModal .modal-title').text('Add New Goal');
        $('#goalForm')[0].reset();
        $('#goalModal').modal('show');
    });

    $('#saveGoal').click(function() {
        const goalData = {
            title: $('#goalTitle').val(),
            description: $('#goalDescription').val(),
            deadline: $('#goalDeadline').val(),
            priority: $('#goalPriority').val()
        };

        $.ajax({
            url: 'api/goals.php',
            method: 'POST',
            data: {
                action: editingGoalId ? 'update' : 'create',
                goal_id: editingGoalId,
                ...goalData
            },
            success: function(response) {
                if (response.success) {
                    $('#goalModal').modal('hide');
                    loadGoals();
                } else {
                    alert(response.message);
                }
            }
        });
    });

    // Milestone handling
    $(document).on('click', '.view-milestones', function() {
        const goalId = $(this).data('goal-id');
        loadMilestones(goalId);
    });

    function loadMilestones(goalId) {
        $.ajax({
            url: 'api/milestones.php',
            method: 'GET',
            data: { action: 'list', goal_id: goalId },
            success: function(response) {
                if (response.success) {
                    displayMilestones(response.milestones, goalId);
                }
            }
        });
    }

    function displayMilestones(milestones, goalId) {
        $('#goalId').val(goalId);
        const milestonesList = $('<div>');
        
        milestones.forEach(milestone => {
            const item = $(`
                <div class="milestone-item d-flex justify-content-between align-items-center mb-2">
                    <div>
                        <input type="checkbox" class="milestone-status" 
                               data-milestone-id="${milestone.id}"
                               ${milestone.status === 'completed' ? 'checked' : ''}>
                        <span class="${milestone.status === 'completed' ? 'text-decoration-line-through' : ''}">
                            ${milestone.title}
                        </span>
                    </div>
                    <button class="btn btn-sm btn-danger delete-milestone" data-milestone-id="${milestone.id}">
                        <i class="bi bi-trash"></i>
                    </button>
                </div>
            `);
            milestonesList.append(item);
        });

        // Show milestones in modal
        $('#milestoneModal .modal-body').prepend(milestonesList);
        $('#milestoneModal').modal('show');
    }

    $('#saveMilestone').click(function() {
        const milestoneData = {
            goal_id: $('#goalId').val(),
            title: $('#milestoneTitle').val(),
            completion_date: $('#milestoneDate').val()
        };

        $.ajax({
            url: 'api/milestones.php',
            method: 'POST',
            data: {
                action: 'create',
                ...milestoneData
            },
            success: function(response) {
                if (response.success) {
                    loadMilestones(milestoneData.goal_id);
                    $('#milestoneForm')[0].reset();
                } else {
                    alert(response.message);
                }
            }
        });
    });

    // Event handlers for milestone status toggle and deletion
    $(document).on('change', '.milestone-status', function() {
        const milestoneId = $(this).data('milestone-id');
        const goalId = $('#goalId').val();

        $.ajax({
            url: 'api/milestones.php',
            method: 'POST',
            data: {
                action: 'toggle_status',
                milestone_id: milestoneId,
                goal_id: goalId
            },
            success: function(response) {
                if (response.success) {
                    loadMilestones(goalId);
                }
            }
        });
    });

    $(document).on('click', '.delete-milestone', function() {
        if (!confirm('Are you sure you want to delete this milestone?')) {
            return;
        }

        const milestoneId = $(this).data('milestone-id');
        const goalId = $('#goalId').val();

        $.ajax({
            url: 'api/milestones.php',
            method: 'POST',
            data: {
                action: 'delete',
                milestone_id: milestoneId,
                goal_id: goalId
            },
            success: function(response) {
                if (response.success) {
                    loadMilestones(goalId);
                }
            }
        });
    });

    // Goal deletion
    $(document).on('click', '.delete-goal', function() {
        if (!confirm('Are you sure you want to delete this goal?')) {
            return;
        }

        const goalId = $(this).data('goal-id');
        $.ajax({
            url: 'api/goals.php',
            method: 'POST',
            data: {
                action: 'delete',
                goal_id: goalId
            },
            success: function(response) {
                if (response.success) {
                    loadGoals();
                } else {
                    alert(response.message);
                }
            }
        });
    });

    // Goal editing
    $(document).on('click', '.edit-goal', function() {
        const goalId = $(this).data('goal-id');
        editingGoalId = goalId;

        $.ajax({
            url: 'api/goals.php',
            method: 'GET',
            data: {
                action: 'get',
                goal_id: goalId
            },
            success: function(response) {
                if (response.success) {
                    const goal = response.goal;
                    $('#goalTitle').val(goal.title);
                    $('#goalDescription').val(goal.description);
                    $('#goalDeadline').val(goal.deadline.slice(0, 16));
                    $('#goalPriority').val(goal.priority);
                    
                    $('#goalModal .modal-title').text('Edit Goal');
                    $('#goalModal').modal('show');
                }
            }
        });
    });

    // Filter handling
    $('.btn-group [data-filter]').click(function() {
        $(this).siblings().removeClass('active');
        $(this).addClass('active');
        loadGoals($(this).data('filter'));
    });

    // Initial load
    if ($('#goalsDashboard').length) {
        loadGoals();
    }
});
