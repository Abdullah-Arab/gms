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
        const container = $('#goalsDashboard');
        container.find('.goals-list').remove();

        if (goals.length === 0) {
            container.append('<p class="text-muted">No goals found. Click "New Goal" to create one.</p>');
            return;
        }

        const list = $('<div class="goals-list row"></div>');
        goals.forEach(goal => {
            const card = $(`
                <div class="col-md-4 mb-4">
                    <div class="card h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start">
                                <h5 class="card-title">${goal.title}</h5>
                                <span class="badge bg-${getPriorityColor(goal.priority)}">${goal.priority}</span>
                            </div>
                            <p class="card-text">${goal.description}</p>
                            <div class="progress mb-3" style="height: 5px;">
                                <div class="progress-bar" role="progressbar" 
                                    style="width: ${goal.progress}%" 
                                    aria-valuenow="${goal.progress}" 
                                    aria-valuemin="0" 
                                    aria-valuemax="100">
                                </div>
                            </div>
                            <div class="d-flex justify-content-between align-items-center">
                                <small class="text-muted">
                                    ${goal.completed_milestones}/${goal.total_milestones} milestones
                                </small>
                                <div class="btn-group">
                                    <a href="goal-details.php?id=${goal.id}" class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-eye"></i> Details
                                    </a>
                                    <button class="btn btn-sm btn-outline-secondary edit-goal" data-goal-id="${goal.id}">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <button class="btn btn-sm btn-outline-danger delete-goal" data-goal-id="${goal.id}">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            `);
            list.append(card);
        });

        container.append(list);
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
        const dateInput = $('#milestoneDate').val();
        // Format the date to match the expected format (YYYY-MM-DD HH:mm)
        const formattedDate = dateInput.replace('T', ' ');
        
        const milestoneData = {
            goal_id: $('#goalId').val(),
            title: $('#milestoneTitle').val(),
            completion_date: formattedDate
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
