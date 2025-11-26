document.addEventListener('DOMContentLoaded', function() {
    // Logic for tab switching in transactions card
    const tabs = document.querySelectorAll('.tab');
    const tabContents = document.querySelectorAll('.tab-content');

    tabs.forEach(tab => {
        tab.addEventListener('click', () => {
            // Deactivate all tabs and content
            tabs.forEach(t => t.classList.remove('active'));
            tabContents.forEach(c => c.style.display = 'none');

            // Activate the clicked tab and its content
            tab.classList.add('active');
            const targetContentId = tab.dataset.target;
            document.getElementById(targetContentId).style.display = 'block';
        });
    });

    // You can add more interactive elements here as needed,
    // for example, handling the currency selection, form validation, etc.
});
