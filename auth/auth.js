document.getElementById('loginForm').addEventListener('submit', function(event) {
            const input = document.querySelector('input[name="identifier"]');
            const errorMsg = document.getElementById('errorMsg');
            const value = input.value.trim();

            // Simple validation: check if it's not empty and resembles email or phone
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            const phoneRegex = /^\d{10,15}$/; // Basic phone check (10-15 digits)

            if (!value || (!emailRegex.test(value) && !phoneRegex.test(value))) {
                event.preventDefault(); // Prevent form submission
                errorMsg.style.display = 'block';
            } else {
                errorMsg.style.display = 'none';
            }
        });