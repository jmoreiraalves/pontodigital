// Funções gerais do sistema
document.addEventListener('DOMContentLoaded', function() {
    
    // Inicializar tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
    
    // Inicializar popovers
    var popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
    var popoverList = popoverTriggerList.map(function (popoverTriggerEl) {
        return new bootstrap.Popover(popoverTriggerEl);
    });
    
    // Confirmar exclusões
    document.querySelectorAll('.btn-delete').forEach(button => {
        button.addEventListener('click', function(e) {
            if (!confirm('Tem certeza que deseja excluir este registro? Esta ação não pode ser desfeita.')) {
                e.preventDefault();
            }
        });
    });
    
    // Máscaras de input
    // CPF
    var cpfInputs = document.querySelectorAll('.cpf-mask');
    cpfInputs.forEach(input => {
        input.addEventListener('input', function(e) {
            var value = e.target.value.replace(/\D/g, '');
            if (value.length > 3) {
                value = value.replace(/^(\d{3})(\d)/, '$1.$2');
            }
            if (value.length > 6) {
                value = value.replace(/^(\d{3})\.(\d{3})(\d)/, '$1.$2.$3');
            }
            if (value.length > 9) {
                value = value.replace(/^(\d{3})\.(\d{3})\.(\d{3})(\d)/, '$1.$2.$3-$4');
            }
            if (value.length > 11) {
                value = value.substring(0, 14);
            }
            e.target.value = value;
        });
    });
    
    // CNPJ
    var cnpjInputs = document.querySelectorAll('.cnpj-mask');
    cnpjInputs.forEach(input => {
        input.addEventListener('input', function(e) {
            var value = e.target.value.replace(/\D/g, '');
            if (value.length > 2) {
                value = value.replace(/^(\d{2})(\d)/, '$1.$2');
            }
            if (value.length > 5) {
                value = value.replace(/^(\d{2})\.(\d{3})(\d)/, '$1.$2.$3');
            }
            if (value.length > 8) {
                value = value.replace(/^(\d{2})\.(\d{3})\.(\d{3})(\d)/, '$1.$2.$3/$4');
            }
            if (value.length > 12) {
                value = value.replace(/^(\d{2})\.(\d{3})\.(\d{3})\/(\d{4})(\d)/, '$1.$2.$3/$4-$5');
            }
            if (value.length > 14) {
                value = value.substring(0, 18);
            }
            e.target.value = value;
        });
    });
    
    // Telefone
    var telInputs = document.querySelectorAll('.tel-mask');
    telInputs.forEach(input => {
        input.addEventListener('input', function(e) {
            var value = e.target.value.replace(/\D/g, '');
            if (value.length > 0) {
                value = '(' + value;
            }
            if (value.length > 3) {
                value = value.replace(/^(\d{2})(\d)/, '$1) $2');
            }
            if (value.length > 9) {
                value = value.replace(/(\d{5})(\d)/, '$1-$2');
            }
            if (value.length > 14) {
                value = value.substring(0, 15);
            }
            e.target.value = value;
        });
    });
    
    // Auto-uppercase para campos de login
    var uppercaseInputs = document.querySelectorAll('.uppercase');
    uppercaseInputs.forEach(input => {
        input.addEventListener('input', function(e) {
            e.target.value = e.target.value.toUpperCase();
        });
        
        // Aplicar uppercase no valor atual
        input.value = input.value.toUpperCase();
    });
    
    // Validação de formulários
    var forms = document.querySelectorAll('.needs-validation');
    Array.prototype.slice.call(forms).forEach(function(form) {
        form.addEventListener('submit', function(event) {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            form.classList.add('was-validated');
        }, false);
    });
    
    // Atualizar horário em tempo real
    function updateClock() {
        var now = new Date();
        var timeString = now.toLocaleTimeString('pt-BR');
        var dateString = now.toLocaleDateString('pt-BR', {
            weekday: 'long',
            year: 'numeric',
            month: 'long',
            day: 'numeric'
        });
        
        var clockElement = document.getElementById('live-clock');
        var dateElement = document.getElementById('live-date');
        
        if (clockElement) {
            clockElement.textContent = timeString;
        }
        
        if (dateElement) {
            dateElement.textContent = dateString.charAt(0).toUpperCase() + dateString.slice(1);
        }
    }
    
    // Atualizar relógio a cada segundo
    setInterval(updateClock, 1000);
    updateClock();
    
    // Modal de reconhecimento facial
    var facialModal = document.getElementById('facialModal');
    if (facialModal) {
        facialModal.addEventListener('show.bs.modal', function() {
            iniciarReconhecimentoFacial();
        });
        
        facialModal.addEventListener('hidden.bs.modal', function() {
            pararReconhecimentoFacial();
        });
    }
    
    // Busca em tempo real para colaboradores
    var searchInput = document.getElementById('searchColaborador');
    if (searchInput) {
        searchInput.addEventListener('keyup', function() {
            var filter = this.value.toLowerCase();
            var rows = document.querySelectorAll('#colaboradoresTable tbody tr');
            
            rows.forEach(function(row) {
                var text = row.textContent.toLowerCase();
                row.style.display = text.indexOf(filter) > -1 ? '' : 'none';
            });
        });
    }
});

// Função para iniciar reconhecimento facial
function iniciarReconhecimentoFacial() {
    console.log('Iniciando reconhecimento facial...');
    
    // Em um sistema real, aqui seria implementada a captura via Webcam
    // e integração com API de reconhecimento facial
    
    // Simulação
    document.getElementById('facialStatus').innerHTML = 
        '<div class="alert alert-info">Aguardando reconhecimento facial...</div>';
    
    // Simular processo de reconhecimento
    setTimeout(function() {
        var statusElement = document.getElementById('facialStatus');
        var random = Math.random();
        
        if (random > 0.3) {
            statusElement.innerHTML = 
                '<div class="alert alert-success">' +
                '<i class="fas fa-check-circle"></i> Reconhecimento bem-sucedido!<br>' +
                'Ponto registrado com sucesso.' +
                '</div>';
            
            // Atualizar status do ponto
            setTimeout(function() {
                if (document.getElementById('ultimoPonto')) {
                    var now = new Date();
                    var hora = now.getHours().toString().padStart(2, '0') + ':' + 
                              now.getMinutes().toString().padStart(2, '0');
                    document.getElementById('ultimoPonto').innerText = 
                        'Último registro: ' + now.toLocaleDateString('pt-BR') + ' ' + hora;
                }
            }, 1000);
        } else {
            statusElement.innerHTML = 
                '<div class="alert alert-danger">' +
                '<i class="fas fa-times-circle"></i> Reconhecimento falhou.<br>' +
                'Por favor, tente novamente ou use o CPF e senha.' +
                '</div>';
        }
    }, 3000);
}

// Função para parar reconhecimento facial
function pararReconhecimentoFacial() {
    console.log('Parando reconhecimento facial...');
    document.getElementById('facialStatus').innerHTML = '';
}

// Função para exportar para PDF
function exportToPDF(tableId, filename) {
    console.log('Exportando tabela ' + tableId + ' para PDF...');
    
    // Em um sistema real, aqui seria implementada a geração de PDF
    // usando bibliotecas como jsPDF ou fazendo requisição para o servidor
    
    alert('Funcionalidade de exportação para PDF será implementada em breve.');
}

// Função para imprimir
function printTable(tableId) {
    console.log('Imprimindo tabela ' + tableId + '...');
    
    var printWindow = window.open('', '_blank');
    var table = document.getElementById(tableId);
    
    if (table) {
        printWindow.document.write('<html><head><title>Imprimir</title>');
        printWindow.document.write('<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet">');
        printWindow.document.write('<style>body { padding: 20px; }</style>');
        printWindow.document.write('</head><body>');
        printWindow.document.write('<h3>Relatório do Sistema</h3>');
        printWindow.document.write(table.outerHTML);
        printWindow.document.write('</body></html>');
        printWindow.document.close();
        printWindow.print();
    }
}

// Função para fazer backup
function fazerBackup() {
    if (confirm('Deseja realizar o backup do sistema agora?')) {
        fetch('ti.php?action=backup')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Backup realizado com sucesso!');
                } else {
                    alert('Erro ao realizar backup: ' + data.message);
                }
            })
            .catch(error => {
                alert('Erro na requisição: ' + error);
            });
    }
}

// Função para alternar visibilidade de senha
function togglePassword(inputId) {
    var input = document.getElementById(inputId);
    var icon = document.querySelector('#toggle' + inputId.charAt(0).toUpperCase() + inputId.slice(1));
    
    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
    } else {
        input.type = 'password';
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
    }
}
