document.addEventListener('DOMContentLoaded', function () {
    (function () {
        const dashboard = document.getElementById('dashboard'); // Parent dashboard container
        const verticalExample = document.getElementById('vertical-example');
        const taskStatistics = document.getElementById('task-statistics');
        const projectStatistics = document.getElementById('project-statistics');
        const todoStatistics = document.getElementById('todos-statistics');
        const languageDropdown = document.getElementById('languageDropdown');
        const unreadNotificationsContainer = document.getElementById('unreadNotificationsContainer');
        const horizontalExample = document.getElementById('horizontal-example');
        const recentActivity = document.getElementById('recent-activity');
        const horizVertExample = document.getElementById('both-scrollbars-example');

        // Initialize Perfect Scrollbar for all scrollable elements
        const scrollableElements = [
            { element: verticalExample, options: { wheelPropagation: true } },
            { element: taskStatistics, options: { wheelPropagation: true } },
            { element: projectStatistics, options: { wheelPropagation: true } },
            { element: todoStatistics, options: { wheelPropagation: true } },
            { element: recentActivity, options: { wheelPropagation: true } },
            { element: languageDropdown, options: { wheelPropagation: true } },
            { element: unreadNotificationsContainer, options: { wheelPropagation: true } },
            { element: horizontalExample, options: { wheelPropagation: true, suppressScrollY: true } },
            { element: horizVertExample, options: { wheelPropagation: true } }
        ].filter(item => item.element); // Filter out null elements

        const psInstances = scrollableElements.map(item => {
            return new PerfectScrollbar(item.element, item.options);
        });

        // Handle dashboard and card scrolling
        const cardElements = [taskStatistics, projectStatistics, todoStatistics, recentActivity].filter(Boolean);
        const cardPsInstances = psInstances.filter((_, index) =>
            scrollableElements[index].element === taskStatistics ||
            scrollableElements[index].element === projectStatistics ||
            scrollableElements[index].element === todoStatistics ||
            scrollableElements[index].element === recentActivity
        );

        if (dashboard && cardElements.length > 0) {
            // Initially allow card scrolling
            cardElements.forEach(card => {
                card.style.overflowY = 'auto';
            });

            dashboard.addEventListener('wheel', (e) => {
                const atTop = dashboard.scrollTop <= 0 && e.deltaY < 0;
                const atBottom = dashboard.scrollHeight - dashboard.scrollTop <= dashboard.clientHeight + 1 && e.deltaY > 0;

                if (!atTop && !atBottom) {
                    // Dashboard can scroll, disable card scrolling
                    cardPsInstances.forEach(ps => {
                        ps.element.style.overflowY = 'hidden';
                        ps.update();
                    });
                } else {
                    // Dashboard can't scroll, enable card scrolling
                    cardPsInstances.forEach(ps => {
                        ps.element.style.overflowY = 'auto';
                        ps.update();
                    });
                }
            });

            // Re-enable card scrolling on mouse enter
            cardElements.forEach(card => {
                card.addEventListener('mouseenter', () => {
                    cardPsInstances.forEach(ps => {
                        ps.element.style.overflowY = 'auto';
                        ps.update();
                    });
                });
                card.addEventListener('mouseleave', () => {
                    const atTop = dashboard.scrollTop <= 0;
                    const atBottom = dashboard.scrollHeight - dashboard.scrollTop <= dashboard.clientHeight + 1;
                    if (!atTop && !atBottom) {
                        cardPsInstances.forEach(ps => {
                            ps.element.style.overflowY = 'hidden';
                            ps.update();
                        });
                    }
                });
            });
        }

        // Handle .statisticsDiv elements
        const statisticsDivs = document.querySelectorAll('.statisticsDiv');
        statisticsDivs.forEach(div => {
            new PerfectScrollbar(div, {
                wheelPropagation: true
            });
        });
    })();
});
