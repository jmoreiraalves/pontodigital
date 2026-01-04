document.addEventListener('DOMContentLoaded', function() {
    // Form validation
    const form = document.getElementById('formAlterarEmpresa');
    
    form.addEventListener('submit', function(event) {
        if (!form.checkValidity()) {
            event.preventDefault();
            event.stopPropagation();
        }
        form.classList.add('was-validated');
    });
});v