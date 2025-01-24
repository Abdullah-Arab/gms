$(document).ready(function() {
    // Load milestones on page load
    loadMilestones();

    // Add Milestone button
    $('#addMilestoneBtn').click(function() {
        $('#milestoneModal').modal('show');
    });

    // Save Milestone
    $('#saveMilestone').click(function() {
        const dateInput = $('#milestoneDate').val();
        // Ensure the date is in MySQL datetime format (YYYY-MM-DD HH:mm:ss)
        const date = new Date(dateInput);
        const formattedDate = date.getFullYear() + '-' + 
            String(date.getMonth() + 1).padStart(2, '0') + '-' + 
            String(date.getDate()).padStart(2, '0') + ' ' + 
            String(date.getHours()).padStart(2, '0') + ':' + 
            String(date.getMinutes()).padStart(2, '0') + ':00';
        
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
                    $('#milestoneModal').modal('hide');
                    $('#milestoneTitle').val('');
                    $('#milestoneDate').val('');
                    loadMilestones();
                } else {
                    alert(response.message);
                }
            }
        });
    });

    // Load Milestones
    function loadMilestones() {
        const goalId = $('#goalId').val();
        $.ajax({
            url: `api/milestones.php?action=list&goal_id=${goalId}`,
            method: 'GET',
            success: function(response) {
                if (response.success) {
                    displayMilestones(response.milestones);
                }
            }
        });
    }

    // Display Milestones
    function displayMilestones(milestones) {
        const container = $('#milestonesList');
        container.empty();

        if (milestones.length === 0) {
            container.html('<p class="text-muted">No milestones yet. Click "Add Milestone" to create one.</p>');
            return;
        }

        const list = $('<div class="list-group"></div>');
        milestones.forEach(milestone => {
            const completionDate = new Date(milestone.completion_date);
            const formattedDate = completionDate.toLocaleDateString();
            
            const todoProgress = milestone.total_todos > 0 
                ? Math.round((milestone.completed_todos / milestone.total_todos) * 100) 
                : 0;

            const item = $(`
                <div class="list-group-item">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="flex-grow-1">
                            <div class="form-check">
                                <input class="form-check-input milestone-status" type="checkbox" 
                                    value="${milestone.id}" ${milestone.status === 'completed' ? 'checked' : ''}>
                                <label class="form-check-label">
                                    ${milestone.title}
                                </label>
                            </div>
                            <small class="text-muted">Due: ${formattedDate}</small>
                            ${milestone.total_todos > 0 ? 
                                `<div class="progress mt-2" style="height: 5px;">
                                    <div class="progress-bar" role="progressbar" 
                                        style="width: ${todoProgress}%" 
                                        aria-valuenow="${todoProgress}" 
                                        aria-valuemin="0" 
                                        aria-valuemax="100">
                                    </div>
                                </div>
                                <small class="text-muted">
                                    ${milestone.completed_todos}/${milestone.total_todos} todos completed
                                </small>` 
                                : ''}
                        </div>
                        <div class="btn-group">
                            <button class="btn btn-sm btn-outline-primary view-todos" 
                                    data-milestone-id="${milestone.id}">
                                <i class="bi bi-list-check"></i> Todos
                            </button>
                            <button class="btn btn-sm btn-outline-danger delete-milestone" 
                                    data-milestone-id="${milestone.id}">
                                <i class="bi bi-trash"></i>
                            </button>
                        </div>
                    </div>
                </div>
            `);
            list.append(item);
        });

        container.append(list);
    }

    // Handle Todo List View
    $(document).on('click', '.view-todos', function() {
        const milestoneId = $(this).data('milestone-id');
        $('#currentMilestoneId').val(milestoneId);
        loadTodos(milestoneId);
        $('#todoModal').modal('show');
    });

    // Load Todos
    function loadTodos(milestoneId) {
        $.ajax({
            url: `api/todos.php?action=list&milestone_id=${milestoneId}`,
            method: 'GET',
            success: function(response) {
                if (response.success) {
                    displayTodos(response.todos);
                }
            }
        });
    }

    // Display Todos
    function displayTodos(todos) {
        const container = $('#todoList');
        container.empty();

        if (todos.length === 0) {
            container.html('<p class="text-muted">No todos yet. Add one above.</p>');
            return;
        }

        todos.forEach(todo => {
            const item = $(`
                <div class="list-group-item">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="form-check">
                            <input class="form-check-input todo-status" type="checkbox" 
                                value="${todo.id}" ${todo.status === 'completed' ? 'checked' : ''}>
                            <label class="form-check-label">
                                ${todo.title}
                            </label>
                        </div>
                        <button class="btn btn-sm btn-outline-danger delete-todo" data-todo-id="${todo.id}">
                            <i class="bi bi-trash"></i>
                        </button>
                    </div>
                </div>
            `);
            container.append(item);
        });
    }

    // Add Todo
    $('#addTodo').click(function() {
        const title = $('#todoTitle').val().trim();
        if (!title) return;

        const milestoneId = $('#currentMilestoneId').val();
        
        $.ajax({
            url: 'api/todos.php',
            method: 'POST',
            data: {
                action: 'create',
                milestone_id: milestoneId,
                title: title
            },
            success: function(response) {
                if (response.success) {
                    $('#todoTitle').val('');
                    loadTodos(milestoneId);
                    loadMilestones(); // Refresh milestone progress
                } else {
                    alert(response.message);
                }
            }
        });
    });

    // Toggle Todo Status
    $(document).on('change', '.todo-status', function() {
        const todoId = $(this).val();
        const milestoneId = $('#currentMilestoneId').val();

        $.ajax({
            url: 'api/todos.php',
            method: 'POST',
            data: {
                action: 'toggle_status',
                todo_id: todoId,
                milestone_id: milestoneId
            },
            success: function(response) {
                if (response.success) {
                    loadTodos(milestoneId);
                    loadMilestones(); // Refresh milestone progress
                }
            }
        });
    });

    // Delete Todo
    $(document).on('click', '.delete-todo', function() {
        if (!confirm('Are you sure you want to delete this todo?')) return;

        const todoId = $(this).data('todo-id');
        const milestoneId = $('#currentMilestoneId').val();

        $.ajax({
            url: 'api/todos.php',
            method: 'POST',
            data: {
                action: 'delete',
                todo_id: todoId,
                milestone_id: milestoneId
            },
            success: function(response) {
                if (response.success) {
                    loadTodos(milestoneId);
                    loadMilestones(); // Refresh milestone progress
                }
            }
        });
    });

    // Toggle Milestone Status
    $(document).on('change', '.milestone-status', function() {
        const milestoneId = $(this).val();
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
                    loadMilestones();
                }
            }
        });
    });

    // Delete Milestone
    $(document).on('click', '.delete-milestone', function() {
        if (!confirm('Are you sure you want to delete this milestone and all its todos?')) return;

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
                    loadMilestones();
                }
            }
        });
    });

    // Logout
    $('#logoutBtn').click(function() {
        $.ajax({
            url: 'api/auth.php',
            method: 'POST',
            data: { action: 'logout' },
            success: function() {
                window.location.href = 'index.php';
            }
        });
    });
});
