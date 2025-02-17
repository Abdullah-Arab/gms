$(document).ready(function() {
    // Chart instances
    let goalStatusChart = null;
    let goalPriorityChart = null;
    let monthlyTrendChart = null;

    // Load all data on page load
    loadReports();
    loadGoals();

    function loadReports() {
        $.ajax({
            url: 'api/reports.php',
            method: 'GET',
            dataType: 'json',
            success: function(response) {
                console.log('Raw response:', response);
                if (response && response.success === true && response.data) {
                    console.log('Reports data:', response.data);
                    updateDashboard(response.data);
                } else {
                    console.error('Error loading reports:', {
                        message: response?.message || 'Unknown error',
                        debug: response?.debug || [],
                        response: response
                    });
                    // Show error in the UI
                    showError('Error loading reports. Please try refreshing the page.');
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX error loading reports:', {
                    error: error,
                    status: status,
                    response: xhr.responseText
                });
                try {
                    const response = JSON.parse(xhr.responseText);
                    console.error('Parsed error response:', {
                        message: response?.message,
                        debug: response?.debug
                    });
                } catch (e) {
                    console.error('Could not parse error response');
                }
                // Show error in the UI
                showError('Could not load reports. Please try again later.');
            }
        });
    }

    function showError(message) {
        // Create error alert if it doesn't exist
        if ($('#errorAlert').length === 0) {
            const alert = $(`
                <div id="errorAlert" class="alert alert-danger alert-dismissible fade show" role="alert">
                    <span class="message"></span>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            `);
            $('.container').first().prepend(alert);
        }
        
        // Update error message
        $('#errorAlert .message').text(message);
    }

    function updateDashboard(data) {
        if (!data) return;
        
        // Update summary cards
        $('#totalGoals').text(data.goals ? data.goals.total_goals || 0 : 0);
        $('#completedGoals').text(data.goals ? data.goals.completed || 0 : 0);
        $('#totalMilestones').text(data.milestones ? data.milestones.total_milestones || 0 : 0);
        
        const todoCompletion = data.milestones && data.milestones.total_todos > 0
            ? Math.round((data.milestones.completed_todos / data.milestones.total_todos) * 100)
            : 0;
        $('#todoCompletion').text(todoCompletion + '%');

        // Update Goal Status Chart
        if (data.goals) {
            updateGoalStatusChart(data.goals);
        }

        // Update Goal Priority Chart
        if (data.goals_by_priority) {
            updateGoalPriorityChart(data.goals_by_priority);
        }

        // Update Monthly Trend Chart
        if (data.monthly_trend) {
            updateMonthlyTrendChart(data.monthly_trend);
        }

        // Update Upcoming Milestones
        if (data.upcoming_milestones) {
            updateUpcomingMilestones(data.upcoming_milestones);
        }
    }

    function updateGoalStatusChart(data) {
        const ctx = document.getElementById('goalStatusChart').getContext('2d');
        
        if (goalStatusChart) {
            goalStatusChart.destroy();
        }

        goalStatusChart = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: ['Not Started', 'In Progress', 'Completed'],
                datasets: [{
                    data: [
                        data.not_started || 0,
                        data.in_progress || 0,
                        data.completed || 0
                    ],
                    backgroundColor: ['#dc3545', '#ffc107', '#28a745']
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
    }

    function updateGoalPriorityChart(data) {
        const ctx = document.getElementById('goalPriorityChart').getContext('2d');
        
        if (goalPriorityChart) {
            goalPriorityChart.destroy();
        }

        const priorities = data.map(item => item.priority.charAt(0).toUpperCase() + item.priority.slice(1));
        const counts = data.map(item => item.count || 0);

        goalPriorityChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: priorities,
                datasets: [{
                    label: 'Goals by Priority',
                    data: counts,
                    backgroundColor: ['#28a745', '#ffc107', '#dc3545']
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: false
                    }
                }
            }
        });
    }

    function updateMonthlyTrendChart(data) {
        const ctx = document.getElementById('monthlyTrendChart').getContext('2d');
        
        // Format dates for display
        const labels = data.map(item => {
            const [year, month] = item.month.split('-');
            return new Date(year, month - 1).toLocaleDateString('default', { month: 'short', year: 'numeric' });
        });

        const totalGoals = data.map(item => item.total_goals);
        const completedGoals = data.map(item => item.completed_goals);

        if (monthlyTrendChart) {
            monthlyTrendChart.destroy();
        }

        monthlyTrendChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [
                    {
                        label: 'Total Goals',
                        data: totalGoals,
                        borderColor: 'rgb(54, 162, 235)',
                        backgroundColor: 'rgba(54, 162, 235, 0.1)',
                        fill: true,
                        tension: 0.4
                    },
                    {
                        label: 'Completed Goals',
                        data: completedGoals,
                        borderColor: 'rgb(75, 192, 192)',
                        backgroundColor: 'rgba(75, 192, 192, 0.1)',
                        fill: true,
                        tension: 0.4
                    }
                ]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'top',
                    },
                    title: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    }
                }
            }
        });
    }

    function updateUpcomingMilestones(milestones) {
        const container = $('#upcomingMilestones');
        container.empty();

        if (milestones.length === 0) {
            container.append('<div class="text-muted">No upcoming milestones</div>');
            return;
        }

        milestones.forEach(milestone => {
            const todoProgress = milestone.total_todos > 0
                ? Math.round((milestone.completed_todos / milestone.total_todos) * 100)
                : 0;

            const milestoneHtml = `
                <div class="list-group-item">
                    <div class="d-flex w-100 justify-content-between">
                        <h6 class="mb-1">${milestone.title}</h6>
                        <small>${formatDate(milestone.completion_date)}</small>
                    </div>
                    <p class="mb-1 text-muted small">${milestone.goal_title}</p>
                    <div class="progress mt-2" style="height: 5px;">
                        <div class="progress-bar" role="progressbar" style="width: ${todoProgress}%"></div>
                    </div>
                </div>
            `;
            container.append(milestoneHtml);
        });
    }

    function formatDate(dateString) {
        const date = new Date(dateString);
        return date.toLocaleDateString('en-US', { 
            month: 'short', 
            day: 'numeric', 
            year: 'numeric' 
        });
    }

    // Load Goals
    function loadGoals() {
        $.ajax({
            url: 'api/goals.php?action=list',
            method: 'GET',
            success: function(response) {
                if (response.success) {
                    displayGoals(response.goals);
                }
            }
        });
    }

    // Display Goals
    let currentSort = { field: 'deadline', direction: 'asc' };
    
    function displayGoals(goals) {
        const container = $('#goalsList');
        container.empty();

        if (goals.length === 0) {
            container.html('<p class="text-muted">No goals yet. Click "Add Goal" to create one.</p>');
            return;
        }

        // Sort goals based on current sort settings
        goals.sort((a, b) => {
            let aVal = a[currentSort.field];
            let bVal = b[currentSort.field];
            
            // Handle special cases
            if (currentSort.field === 'deadline') {
                aVal = new Date(aVal);
                bVal = new Date(bVal);
            }
            
            if (aVal < bVal) return currentSort.direction === 'asc' ? -1 : 1;
            if (aVal > bVal) return currentSort.direction === 'asc' ? 1 : -1;
            return 0;
        });

        const table = $(`
            <table class="table">
                <thead>
                    <tr>
                        <th class="sortable" data-sort="title">
                            Title <i class="bi bi-arrow-down-up"></i>
                        </th>
                        <th class="sortable" data-sort="deadline">
                            Deadline <i class="bi bi-arrow-down-up"></i>
                        </th>
                        <th class="sortable" data-sort="priority">
                            Priority <i class="bi bi-arrow-down-up"></i>
                        </th>
                        <th class="sortable" data-sort="status">
                            Status <i class="bi bi-arrow-down-up"></i>
                        </th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        `);

        goals.forEach(goal => {
            const row = $(`
                <tr>
                    <td>${goal.title}</td>
                    <td>${formatDate(goal.deadline)}</td>
                    <td>
                        <span class="badge bg-${getPriorityColor(goal.priority)}">
                            ${goal.priority.charAt(0).toUpperCase() + goal.priority.slice(1)}
                        </span>
                    </td>
                    <td>
                        <span class="badge bg-${getStatusColor(goal.status)}">
                            ${formatStatus(goal.status)}
                        </span>
                    </td>
                    <td>
                        <div class="btn-group">
                            <a href="goal-details.php?id=${goal.id}" class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-list-check"></i> Details
                            </a>
                            <button class="btn btn-sm btn-outline-danger delete-goal" data-goal-id="${goal.id}">
                                <i class="bi bi-trash"></i>
                            </button>
                        </div>
                    </td>
                </tr>
            `);
            table.find('tbody').append(row);
        });

        container.append(table);

        // Add click handlers for sorting
        $('.sortable').click(function() {
            const field = $(this).data('sort');
            if (currentSort.field === field) {
                currentSort.direction = currentSort.direction === 'asc' ? 'desc' : 'asc';
            } else {
                currentSort.field = field;
                currentSort.direction = 'asc';
            }
            
            // Update sort icons
            $('.sortable i').removeClass('bi-arrow-up bi-arrow-down').addClass('bi-arrow-down-up');
            const icon = $(this).find('i');
            icon.removeClass('bi-arrow-down-up')
                .addClass(currentSort.direction === 'asc' ? 'bi-arrow-up' : 'bi-arrow-down');
            
            displayGoals(goals);
        });

        // Show current sort
        const currentHeader = $(`.sortable[data-sort="${currentSort.field}"]`);
        currentHeader.find('i')
            .removeClass('bi-arrow-down-up')
            .addClass(currentSort.direction === 'asc' ? 'bi-arrow-up' : 'bi-arrow-down');
    }

    function getPriorityColor(priority) {
        switch (priority) {
            case 'high': return 'danger';
            case 'medium': return 'warning';
            case 'low': return 'success';
            default: return 'secondary';
        }
    }

    function getStatusColor(status) {
        switch (status) {
            case 'completed': return 'success';
            case 'in_progress': return 'warning';
            case 'not_started': return 'danger';
            default: return 'secondary';
        }
    }

    function formatStatus(status) {
        return status.split('_')
            .map(word => word.charAt(0).toUpperCase() + word.slice(1))
            .join(' ');
    }

    // Save Goal
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
                action: 'create',
                ...goalData
            },
            success: function(response) {
                if (response.success) {
                    $('#goalModal').modal('hide');
                    $('#goalTitle').val('');
                    $('#goalDescription').val('');
                    $('#goalDeadline').val('');
                    $('#goalPriority').val('medium');
                    loadGoals();
                    loadReports();
                } else {
                    alert(response.message);
                }
            }
        });
    });

    // Delete Goal
    $(document).on('click', '.delete-goal', function() {
        if (!confirm('Are you sure you want to delete this goal and all its milestones?')) return;

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
                    loadReports();
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
