document.addEventListener('DOMContentLoaded', () => {
    // tabs
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

    // rules
    const positionSelect = document.querySelector('select[name="injection_position"]');
    const contentPositionSelect = document.querySelector('select[name="injection_content_position"]');

    console.log(positionSelect)

    const rowContentPosition = document.getElementById('row-injection-content-position');
    const rowTag = document.getElementById('row-injection-tag');
    const rowTagPosition = document.getElementById('row-injection-tag-position');

    function updateVisibility() {
        const position = positionSelect.value;
        const contentPosition = contentPositionSelect.value;

        // Show content position only if "the_content" is selected
        if (position === 'the_content') {
            rowContentPosition.style.display = '';
        } else {
            rowContentPosition.style.display = 'none';
        }

        // Show tag and tag position only if "specific_tag" is selected
        if (position == 'the_content' && contentPosition === 'specific_tag') {
            rowTag.style.display = '';
            rowTagPosition.style.display = '';
        } else {
            rowTag.style.display = 'none';
            rowTagPosition.style.display = 'none';
        }
    }

    positionSelect.addEventListener('change', updateVisibility);
    contentPositionSelect.addEventListener('change', updateVisibility);

    updateVisibility(); // initial run
});
