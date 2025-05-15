document.addEventListener('DOMContentLoaded', () => {
    // Tabs
    const tabs = document.querySelectorAll('.usci-tabs li');
    const contents = document.querySelectorAll('.usci-tab-content');
    tabs.forEach(tab => {
        tab.addEventListener('click', () => {
            const target = tab.getAttribute('data-tab');
            tabs.forEach(t => t.classList.remove('active'));
            tab.classList.add('active');
            contents.forEach(c => {
                c.classList.remove('active');
                if (c.getAttribute('data-tab-content') === target) {
                    c.classList.add('active');
                }
            });
        });
    });

    // Rules
    const positionSelect = document.querySelector('select[name="injection_position"]');
    const contentPositionSelect = document.querySelector('select[name="injection_content_position"]');
    const rowContentPosition = document.getElementById('row-injection-content-position');
    const rowTag = document.getElementById('row-injection-tag');

    function updateVisibility() {
        const position = positionSelect.value;
        const contentPosition = contentPositionSelect.value;

        // Show content position row only for the_content
        rowContentPosition.style.display = (position === 'the_content') ? '' : 'none';

        // Show tag rule input if specific_tag or before_specific_tag is selected
        const showTag = position === 'the_content' && (
            contentPosition === 'specific_tag' ||
            contentPosition === 'before_specific_tag'
        );
        rowTag.style.display = showTag ? '' : 'none';
    }

    positionSelect.addEventListener('change', updateVisibility);
    contentPositionSelect.addEventListener('change', updateVisibility);
    updateVisibility(); // initial state
});
