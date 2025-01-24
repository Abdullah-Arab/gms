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
            success: function(response) {
                if (response && response.success && response.data) {
                    console.log('Reports data:', response.data);
                    updateDashboard(response.data);
                } else {
                    console.error('Error loading reports:', response ? response.message : 'Unknown error');
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX error loading reports:', error);
                console.error('Response:', xhr.responseText);
            }
        });
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
        
        if (monthlyTrendChart) {
            monthlyTrendChart.destroy();
        }

        const months = data.map(item => item.month);
        const totalGoals = data.map(item => item.total_goals || 0);
        const completedGoals = data.map(item => item.completed_goals || 0);

        monthlyTrendChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: months,
                datasets: [
                    {
                        label: 'Total Goals',
                        data: totalGoals,
                        borderColor: '#0d6efd',
                        backgroundColor: 'rgba(13, 110, 253, 0.1)',
                        fill: true
                    },
                    {
                        label: 'Completed Goals',
                        data: completedGoals,
                        borderColor: '#198754',
                        backgroundColor: 'rgba(25, 135, 84, 0.1)',
                        fill: true
                    }
                ]
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
    function displayGoals(goals) {
        const container = $('#goalsList');
        container.empty();

        if (goals.length === 0) {
            container.html('<p class="text-muted">No goals yet. Click "Add Goal" to create one.</p>');
            return;
        }

        const table = $(`
            <table class="table">
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Priority</th>
                        <th>Status</th>
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
