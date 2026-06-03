// assets/js/validators.js
// Validadores reutilizables en frontend, espejo de includes/functions.php
const Validators = {
    sanitize: (v) => {
        if (v === null || v === undefined) return '';
        return String(v).trim();
    },
    required: (v) => {
        if (v === null || v === undefined) return false;
        if (Array.isArray(v)) return v.length > 0;
        return String(v).trim() !== '';
    },
    email: (v) => {
        if (!v) return false;
        // simple regex
        return /^[^@\s]+@[^@\s]+\.[^@\s]+$/.test(v);
    },
    phone: (v) => {
        if (!v) return false;
        return /^[0-9+\-()\s]{6,20}$/.test(v);
    },
    integer: (v) => {
        return /^-?\d+$/.test(String(v));
    },
    date: (v) => {
        if (!v) return false;
        // yyyy-mm-dd or yyyy-mm-ddThh:mm
        return /^\d{4}-\d{2}-\d{2}(T\d{2}:\d{2})?$/.test(v);
    },
    plate: (v) => {
        if (!v) return false;
        return /^[A-Za-z0-9\-]{3,12}$/.test(String(v).toUpperCase());
    },
    showError: (inputEl, msg) => {
        if (!inputEl) return;
        // simple inline error message
        let next = inputEl.nextElementSibling;
        if (!next || !next.classList.contains('field-error')) {
            next = document.createElement('div');
            next.className = 'field-error';
            next.style.color = '#c0392b';
            next.style.fontSize = '0.9em';
            inputEl.parentNode.insertBefore(next, inputEl.nextSibling);
        }
        next.innerText = msg;
    },
    clearError: (inputEl) => {
        if (!inputEl) return;
        const next = inputEl.nextElementSibling;
        if (next && next.classList.contains('field-error')) next.remove();
    }
};

if (typeof window !== 'undefined') window.Validators = Validators;
