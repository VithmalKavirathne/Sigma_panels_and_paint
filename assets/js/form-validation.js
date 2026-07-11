/*
 * Sigma Panels & Paint - Form Validation
 * Phase 9 - progressive client-side validation for quote & contact forms.
 * Server-side validation in PHP remains the source of truth; this only
 * improves UX and never replaces the backend checks.
 */
(function () {
    'use strict';

    function isEmail(v) {
        return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(v);
    }

    function setError(field, message) {
        field.classList.add('has-error');
        var msg = field.querySelector('.err-msg');
        if (msg) { msg.textContent = message; }
    }

    function clearError(field) {
        field.classList.remove('has-error');
    }

    function validateField(input) {
        var field = input.closest('.field');
        if (!field) { return true; }
        var value = (input.value || '').trim();

        if (input.hasAttribute('required') && value === '') {
            setError(field, 'This field is required.');
            return false;
        }
        if (input.type === 'email' && value !== '' && !isEmail(value)) {
            setError(field, 'Please enter a valid email address.');
            return false;
        }
        clearError(field);
        return true;
    }

    document.addEventListener('DOMContentLoaded', function () {
        var forms = document.querySelectorAll('form[data-validate]');
        forms.forEach(function (form) {
            var inputs = form.querySelectorAll('input, select, textarea');

            inputs.forEach(function (input) {
                input.addEventListener('blur', function () { validateField(input); });
                input.addEventListener('input', function () {
                    var field = input.closest('.field');
                    if (field && field.classList.contains('has-error')) { validateField(input); }
                });
            });

            form.addEventListener('submit', function (e) {
                var valid = true;
                inputs.forEach(function (input) {
                    if (!validateField(input)) { valid = false; }
                });
                if (!valid) {
                    e.preventDefault();
                    var firstError = form.querySelector('.field.has-error input, .field.has-error select, .field.has-error textarea');
                    if (firstError) { firstError.focus(); }
                }
            });
        });
    });
})();
