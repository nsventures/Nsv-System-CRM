$(document).ready(function () {
    // Get all todo list containers as DOM elements
    let todoListContainers = document.querySelectorAll(".todo-list-container");

    // Initialize Sortable on each container with shared group
    todoListContainers.forEach(function (container) {
        new Sortable(container, {
            handle: ".todo-drag-handle", // Dragging allowed only on the move icon
            animation: 150,
            group: "todos", // Shared group name allows dragging between containers
            onEnd: function (evt) {
                // This function runs when an item is dropped
                const item = evt.item; // The dragged item
                const from = evt.from; // Source list
                const to = evt.to; // Destination list

                // Check if the todo was moved between different lists
                if (from !== to) {
                    // Get the todo ID from the item
                    const todoId = $(item).data('todo-id');

                    // If item was moved to completed list
                    if ($(to).closest('.todo-card').hasClass('todo-card-complete')) {
                        // Add completed class to the item
                        $(item).addClass('todo-completed');

                        // Update the checkbox
                        $(item).find('.todo-check-input').prop('checked', true);

                        // Replace priority badge with completed tag
                        const metaContainer = $(item).find('.todo-meta');
                        metaContainer.find('.badge').replaceWith(
                            '<span class="badge badge-ok"><i class="bx bx-check-double me-1"></i>' +
                            'Completed</span>'
                        );

                        // Update database
                        updateTodoStatus(todoId, true);
                    }
                    // If item was moved to incomplete list
                    else {
                        // Remove completed class
                        $(item).removeClass('todo-completed');

                        // Uncheck the checkbox with slight delay
                        setTimeout(() => {
                            $(item).find('.todo-check-input').prop('checked', false);
                        }, 10);
                        // Get priority from data attribute
                        const priority = $(item).hasClass('todo-priority-high') ? 'high' :
                            ($(item).hasClass('todo-priority-medium') ? 'medium' : 'low');

                        // Get proper color class based on priority
                        const colorClass = priority === 'high' ? 'badge-err' :
                            (priority === 'medium' ? 'badge-warn' : 'badge-info');

                        // Replace completed tag with priority badge
                        const metaContainer = $(item).find('.todo-meta');
                        metaContainer.find('.badge').replaceWith(
                            '<span class="badge ' + colorClass + '">' +
                            priority.charAt(0).toUpperCase() + priority.slice(1) +
                            '</span>'
                        );

                        // Update database
                        updateTodoStatus(todoId, false);
                    }

                    // Update the counter on both containers
                    updateCounters();
                }
            }
        });
    });

    // Function to update the counters after drag and drop
    function updateCounters() {
        const incompleteContainer = document.querySelector('.todo-card-incomplete .todo-list-container');
        const completeContainer = document.querySelector('.todo-card-complete .todo-list-container');

        if (!incompleteContainer || !completeContainer) return;

        // Count todos (excluding add-item divs)
        const incompleteCount = incompleteContainer.querySelectorAll('.todo-item').length;
        const completeCount = completeContainer.querySelectorAll('.todo-item').length;
        const totalCount = incompleteCount + completeCount;

        // Update counters
        const incompleteHeader = document.querySelector('.todo-card-incomplete .todo-card-header');
        const completeHeader = document.querySelector('.todo-card-complete .todo-card-header');
        
        if (incompleteHeader) {
            incompleteHeader.querySelector('.todo-counter').textContent = incompleteCount;
        }
        if (completeHeader) {
            completeHeader.querySelector('.todo-counter').textContent = completeCount;
        }

        // Calculate progress
        let progress = totalCount > 0 ? (completeCount / totalCount) * 100 : 0;
        progress = progress.toFixed(2); // Same formatting as PHP

        // Update progress text and bar
        $('.todo-progress-value').text(`${completeCount} / ${totalCount} (${progress}%)`);
        $('.progress-bar').css('width', `${progress}%`);
        $('.progress-bar').attr('aria-valuenow', progress);
    }

    // Function to send AJAX request to update todo status
    function updateTodoStatus(todoId, isCompleted) {
        $.ajax({
            url: baseUrl + '/todos/update_status',
            type: 'POST',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            data: {
                _method: 'PUT',
                id: todoId,
                status: isCompleted ? 1 : 0
            },
            success: function (response) {
                if (response.error == false) {
                    toastr.success(response.message);
                } else {
                    toastr.error(response.message);
                }
            },
            error: function (xhr) {
                console.error('Error updating todo status:', xhr.responseText);
            }
        });
    }

    // Inline todo add for both lists
    $('.new-todo-title').on('keyup', function (e) {
        if (e.key === 'Enter') {
            addNewTodo($(this));
        }
    });

    function addNewTodo(input) {
        const title = input.val().trim();
        const listType = input.data('list'); // 'incomplete' or 'completed'
        if (!title) return;

        $.ajax({
            url: baseUrl + '/todos/store', // Adjust to your create route
            type: 'POST',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            data: {
                title: title,
                priority: 'low', // Default; add UI select if needed
                is_completed: listType === 'completed' ? 1 : 0
            },
            success: function (response) {
                if (response.error === false) {
                    const todo = response.data;
                    const priorityColors = {
                        low: 'badge-info',
                        medium: 'badge-warn',
                        high: 'badge-err'
                    };
                    const colorClass = priorityColors[todo.priority] || 'badge-info';
                    const formattedDate = new Date(todo.created_at).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
                    const ucfirstPriority = todo.priority.charAt(0).toUpperCase() + todo.priority.slice(1);

                    const html = `
                        <div class="todo-item ${todo.is_completed ? 'todo-completed' : ''} todo-priority-${todo.priority} d-flex align-items-center" data-todo-id="${todo.id}">
                            <div class="todo-drag-handle me-2">
                                <i class="bx bx-grid-vertical fs-4"></i>
                            </div>
                            <div class="todo-check me-3">
                                <input type="checkbox" class="todo-check-input border-2" id="${todo.id}" onclick="update_status(this)" name="${todo.id}" ${todo.is_completed ? 'checked' : ''}>
                            </div>
                            <div class="flex-grow-1">
                                <h6 class="todo-title">${todo.title}</h6>
                                <div class="todo-meta">
                                    <span class="todo-meta-item"><i class="bx bx-calendar-alt"></i> ${formattedDate}</span>
                                    ${todo.is_completed ?
                            '<span class="badge badge-ok"><i class="bx bx-check-double me-1"></i>Completed</span>' :
                            `<span class="badge ${colorClass}">${ucfirstPriority}</span>`
                        }
                                </div>
                            </div>
                            <div class="todo-actions-container">
                                <div class="d-flex">
                                    <a href="javascript:void(0);" class="edit-todo me-2" data-bs-toggle="modal" data-bs-target="#edit_todo_modal" data-id="${todo.id}" title="Update"><i class='bx bx-edit fs-5'></i></a>
                                    <a href="javascript:void(0);" type="button" data-id="${todo.id}" data-type="todos" data-reload="true" title="Delete" class="delete text-danger"><i class='bx bx-trash fs-5'></i></a>
                                </div>
                            </div>
                        </div>
                    `;

                    // Remove empty message if present
                    const targetList = $(`.todo-add-item[data-list="${listType}"]`).closest('.todo-list-container');
                    targetList.find('.text-center.text-muted').remove();

                    // Append to the correct list (insert before add item)
                    $(html).insertBefore(targetList.find('.todo-add-item'));

                    input.val('');
                    updateCounters();
                    toastr.success(response.message);
                } else {
                    toastr.error(response.message);
                }
            },
            error: function (xhr) {
                console.error('Error adding todo:', xhr.responseText);
                toastr.error('Failed to add todo.');
            }
        });
    }
});
