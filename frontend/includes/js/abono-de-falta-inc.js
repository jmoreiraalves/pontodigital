// Script para melhorar a usabilidade
document.addEventListener('DOMContentLoaded', function () {
    // Formatação da data atual no campo
    const dataAbono = document.getElementById('data_abono');
    if (dataAbono) {
        const hoje = new Date().toISOString().split('T')[0];
        dataAbono.value = hoje;
    }

    // Validação do formulário
    const form = document.querySelector('#modalRegistrarAbono form');
    if (form) {
        form.addEventListener('submit', function (e) {
            const colaborador = document.getElementById('colaborador_id');
            const data = document.getElementById('data_abono');

            if (!colaborador.value) {
                e.preventDefault();
                alert('Por favor, selecione um colaborador.');
                colaborador.focus();
                return false;
            }

            if (!data.value) {
                e.preventDefault();
                alert('Por favor, selecione uma data.');
                data.focus();
                return false;
            }

            // Confirmar ação
            if (!confirm('Tem certeza que deseja registrar este abono? Esta ação marcará todos os registros do dia como abonados.')) {
                e.preventDefault();
                return false;
            }
        });
    }

    // Auto-fechar alertas após 5 segundos
    setTimeout(function () {
        const alerts = document.querySelectorAll('.alert');
        alerts.forEach(function (alert) {
            const bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        });
    }, 5000);
});