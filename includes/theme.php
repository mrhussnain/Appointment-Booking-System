<?php
// Create this new file to hold common styles
$themeStyles = <<<EOT
<style>
:root {
    --gradient-primary: linear-gradient(135deg, #667db6 0%, #0082c8 50%, #0082c8 75%, #667db6 100%);
    --gradient-secondary: linear-gradient(135deg, #0082c8 0%, #667db6 100%);
    --gradient-light: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
}

body { 
    background: var(--gradient-light);
    font-family: 'Poppins', sans-serif;
    min-height: 100vh;
    display: flex;
    flex-direction: column;
}

.navbar { 
    background: var(--gradient-primary) !important;
    box-shadow: 0 2px 4px rgba(0,0,0,.1); 
}

.navbar-brand {
    font-weight: 600;
    font-size: 1.5rem;
}

.card {
    border: none;
    box-shadow: 0 0 20px rgba(0,0,0,.08);
    border-radius: 15px;
    background: #ffffff;
}

.card-header {
    background: var(--gradient-light);
    border-bottom: 2px solid #f8f9fa;
    border-radius: 15px 15px 0 0 !important;
}

.btn-primary {
    background: var(--gradient-primary);
    border: none;
    transition: all 0.3s ease;
}

.btn-primary:hover {
    background: var(--gradient-secondary);
    transform: translateY(-1px);
    box-shadow: 0 5px 15px rgba(102, 125, 182, 0.3);
}

.btn-secondary {
    background: var(--gradient-secondary);
    border: none;
}

.badge.bg-primary {
    background: var(--gradient-primary) !important;
}

.badge.bg-success {
    background: linear-gradient(135deg, #28a745 0%, #20c997 100%) !important;
}

.badge.bg-warning {
    background: linear-gradient(135deg, #ffc107 0%, #ffdb4d 100%) !important;
}

.badge.bg-danger {
    background: linear-gradient(135deg, #dc3545 0%, #ff4d4d 100%) !important;
}

.form-control:focus {
    border-color: #0082c8;
    box-shadow: 0 0 0 0.25rem rgba(102, 125, 182, 0.25);
}

.modal-content {
    border: none;
    border-radius: 15px;
}

.modal-header {
    background: var(--gradient-primary);
    color: white;
    border-radius: 15px 15px 0 0;
}

.modal-header .btn-close {
    filter: brightness(0) invert(1);
}

.alert {
    border: none;
    border-radius: 10px;
}

.alert-success {
    background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%);
}

.alert-danger {
    background: linear-gradient(135deg, #f8d7da 0%, #f5c6cb 100%);
}

.alert-warning {
    background: linear-gradient(135deg, #fff3cd 0%, #ffeeba 100%);
}

.nav-link {
    color: white !important;
    opacity: 0.9;
    transition: all 0.3s ease;
}

.nav-link:hover {
    opacity: 1;
    transform: translateY(-1px);
}

.nav-link.active {
    opacity: 1;
    background: rgba(255, 255, 255, 0.1);
    border-radius: 8px;
}

.dropdown-menu {
    border: none;
    box-shadow: 0 0 20px rgba(0,0,0,.1);
    border-radius: 10px;
}

.table th {
    color: #667db6;
    font-weight: 600;
}

.status-pending { 
    color: #ffc107;
    font-weight: 500;
}

.status-confirmed { 
    color: #28a745;
    font-weight: 500;
}

.status-cancelled { 
    color: #dc3545;
    font-weight: 500;
}

/* Login/Signup specific styles */
.login-form, .signup-form {
    max-width: 400px;
    margin: 50px auto;
    padding: 2rem;
    background: white;
    border-radius: 20px;
    box-shadow: 0 0 30px rgba(0,0,0,0.1);
}

.time-slot label {
    margin: 5px;
    transition: all 0.3s ease;
}

.time-slot label:hover {
    transform: translateY(-2px);
}

.btn-check:checked + .btn-outline-primary {
    background: var(--gradient-primary);
    border: none;
}

.footer {
    background: var(--gradient-primary);
    color: white;
    padding: 1rem 0;
    margin-top: auto;
}

.footer p {
    margin-bottom: 0;
    opacity: 0.9;
}

.footer a {
    color: white;
    text-decoration: none;
    font-weight: 500;
    transition: all 0.3s ease;
}

.footer a:hover {
    opacity: 1;
    transform: translateY(-1px);
}
</style>
EOT;
?> 