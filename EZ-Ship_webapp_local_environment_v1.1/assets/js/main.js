// An Event Listener for the 'sandwich' to toggle nav selection in mobile display
document.addEventListener('DOMContentLoaded', () => {
    const toggle = document.querySelector('.nav-toggle');
    const nav = document.querySelector('.nav');
    toggle.addEventListener('click', () => {
        nav.classList.toggle('show');
    });
});

// An Event Listener for loading the proper Sign-Up form based on role.
document.addEventListener('DOMContentLoaded', () => {
    const select = document.getElementById('signup-role-select');
    const forms = document.querySelectorAll('.role-form');
    select.addEventListener('change', () => {
        const role = select.value;
        // Hide all forms first
        forms.forEach(f => f.style.display = 'none');
        // Show the selected role form
        if (role) {
            const formToShow = document.getElementById(`form-${role}`);
            if (formToShow) formToShow.style.display = 'block';
        }
    });
});

// Sorts the warehouses in client.php based on parcel size selection.
document.addEventListener('DOMContentLoaded', function () {
    const psSelect = document.getElementById('ps');
    const warehouseSelect = document.getElementById('wid');
    if (!psSelect || !warehouseSelect) return;
    psSelect.addEventListener('change', function () {
        const psValue = parseInt(this.value);
        Array.from(warehouseSelect.options).forEach(option => {
            if (!option.dataset.maxSize) return;
            const maxSize = parseInt(option.dataset.maxSize);
            if (!psValue || maxSize >= psValue) {
                option.hidden = false;
            } else {
                option.hidden = true;
            }
        });
        warehouseSelect.value = "";
    });
});

// An even listener for showing new recipient entry for client entry.
document.addEventListener('DOMContentLoaded', function() {
    var recipientSelect = document.getElementById('recipient');
    var newFields = document.getElementById('new-recipient-fields');

    recipientSelect.addEventListener('change', function() {
        if (this.value === 'new') {
            newFields.style.display = 'block';
        } else {
            newFields.style.display = 'none';
        }
    });
});