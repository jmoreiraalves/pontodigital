$(document).ready(function () {

    ////criar nova empresa
    $('#formNovaEmpresa').on('submit', function (e) {
        e.preventDefault();

        $.ajax({
            url: 'ajax/empresas-ajax.php?action=create',
            type: 'POST',
            dataType: 'json', // Adicione esta linha para esperar JSON
            data: $(this).serialize(),
            success: function (response) { // Adicione o parâmetro 'response'
                if (response.success) {
                    $('#formNovaEmpresa').modal('hide');
                    showSwetAlert('success', response.message, {
                        usarTimer: true,
                        tempo: 1500,
                        acao: () => location.reload()
                    });
                } else {
                    // Se success for false, mostrar mensagem de erro
                    mostrarSwetAlert('error', response.message || 'Erro ao adicionar Empresa.', false);
                }
            },
            error: function (xhr, status, error) {
                console.error('Erro na requisição:', error);
                console.error('Resposta:', xhr.responseText);

                try {
                    // Tentar parsear o erro como JSON
                    var errorResponse = JSON.parse(xhr.responseText);
                    mostrarSwetAlert('error', errorResponse.message || 'Erro ao adicionar Empresa.', false);
                } catch (e) {
                    // Se não for JSON, mostrar erro genérico
                    mostrarSwetAlert('error', 'Erro de conexão ou servidor.', false);
                }
            },
            complete: function () {
                // Opcional: executar algo sempre ao final
                // console.log('Requisição completa');
            }
        });
    });

    ////alterar dados de uma empresa
    $('#formeditEmpresa').on('submit', function (e) {
        e.preventDefault();

        $.ajax({
            url: 'ajax/empresas-ajax.php?action=update',
            type: 'POST',
            dataType: 'json', // Adicione esta linha para esperar JSON
            data: $(this).serialize(),
            success: function (response) { // Adicione o parâmetro 'response'
                if (response.success) {
                    $('#formeditEmpresa').modal('hide');
                    showSwetAlert('success', response.message, {
                        usarTimer: true,
                        tempo: 1500,
                        acao: () => location.reload()
                    });
                } else {
                    // Se success for false, mostrar mensagem de erro
                    mostrarSwetAlert('error', response.message || 'Erro ao editar Empresa.', false);
                }
            },
            error: function (xhr, status, error) {
                console.error('Erro na requisição:', error);
                console.error('Resposta:', xhr.responseText);

                try {
                    // Tentar parsear o erro como JSON
                    var errorResponse = JSON.parse(xhr.responseText);
                    mostrarSwetAlert('error', errorResponse.message || 'Erro ao editar Empresa.', false);
                } catch (e) {
                    // Se não for JSON, mostrar erro genérico
                    mostrarSwetAlert('error', 'Erro de conexão ou servidor.', false);
                }
            },
            complete: function () {
                // Opcional: executar algo sempre ao final
                // console.log('Requisição completa');
            }
        });
    });

    //// deletar uma empresa não permitindo mais o login
     $('#formdeleteEmpresa').on('submit', function (e) {
        e.preventDefault();

        $.ajax({
            url: 'ajax/empresas-ajax.php?action=delete',
            type: 'POST',
            dataType: 'json', // Adicione esta linha para esperar JSON
            data: $(this).serialize(),
            success: function (response) { // Adicione o parâmetro 'response'
                if (response.success) {
                    $('#formdeleteEmpresa').modal('hide');
                    showSwetAlert('success', response.message, {
                        usarTimer: true,
                        tempo: 1500,
                        acao: () => location.reload()
                    });
                } else {
                    // Se success for false, mostrar mensagem de erro
                    mostrarSwetAlert('error', response.message || 'Erro ao deletar Empresa.', false);
                }
            },
            error: function (xhr, status, error) {
                console.error('Erro na requisição:', error);
                console.error('Resposta:', xhr.responseText);

                try {
                    // Tentar parsear o erro como JSON
                    var errorResponse = JSON.parse(xhr.responseText);
                    mostrarSwetAlert('error', errorResponse.message || 'Erro ao deletar Empresa.', false);
                } catch (e) {
                    // Se não for JSON, mostrar erro genérico
                    mostrarSwetAlert('error', 'Erro de conexão ou servidor.', false);
                }
            },
            complete: function () {
                // Opcional: executar algo sempre ao final
                // console.log('Requisição completa');
            }
        });
    });

    // btneditEmpresaModal
    $('.btneditEmpresaModal').on('click', function () {
        const id = $(this).data('id');

        $.ajax({
            url: 'ajax/empresas-ajax.php',
            type: 'GET',
            data: {  // <-- Passa como objeto data
                action: 'get',
                id: id
            },
            success: function (response) {
                if (response.success) {
                    const nome = response.empresa.nome;
                    const cnpj = response.empresa.cnpj;
                    const prefixo = response.empresa.prefixo;
                    const endereco = response.empresa.endereco;
                    const telefone = response.empresa.telefone;
                    const email = response.empresa.email;
                    const ativa = response.empresa.ativa;
                    // Preenche os campos do modal
                    $('#update_id').val(id);
                    $('#update_nome').val(nome);
                    $('#update_cnpj').val(cnpj);
                    $("#update_prefixo").val(prefixo);
                    $('#update_endereco').val(endereco);
                    $('#update_telefone').val(telefone);
                    $('#update_email').val(email);
                    $('#update_ativa').val(ativa);
                    $('#editEmpresaModals').modal('show');
                } else {
                    // Se success for false, mostrar mensagem de erro
                    mostrarSwetAlert('error', response.message || 'Erro ao solicitar Empresa.', false);
                }
            },
            error: function (xhr, status, error) {
                console.error('Erro na requisição:', error);
                console.error('Resposta:', xhr.responseText);

                try {
                    // Tentar parsear o erro como JSON
                    var errorResponse = JSON.parse(xhr.responseText);
                    mostrarSwetAlert('error', errorResponse.message || 'Erro ao solicitar Empresa.', false);
                } catch (e) {
                    // Se não for JSON, mostrar erro genérico
                    mostrarSwetAlert('error', 'Erro de conexão ou servidor.', false);
                }
            }
        });
    });

    // btndeleteEmpresaModals
    $('.btndeleteEmpresaModals').on('click', function () {
        const id = $(this).data('id');

        $.ajax({
            url: 'ajax/empresas-ajax.php',
            type: 'GET',
            data: {  // <-- Passa como objeto data
                action: 'get',
                id: id
            },
            success: function (response) {
                if (response.success) {
                    // Preenche os campos do modal
                    
                    const nome = response.empresa.nome;
                    const cnpj = response.empresa.cnpj;
                    const prefixo = response.empresa.prefixo;
                    const endereco = response.empresa.endereco;
                    // Preenche os campos do modal
                    $('#update_id').val(id);
                    $('#update_nome').val(nome);
                    $('#update_cnpj').val(cnpj);
                    $("#update_prefixo").val(prefixo);
                    $('#update_endereco').val(endereco);
                    $('#deleteEmpresaModals').modal('show');
                } else {
                    // Se success for false, mostrar mensagem de erro
                    mostrarSwetAlert('error', response.message || 'Erro ao solicitar Empresa.', false);
                }
            },
            error: function (xhr, status, error) {
                console.error('Erro na requisição:', error);
                console.error('Resposta:', xhr.responseText);

                try {
                    // Tentar parsear o erro como JSON
                    var errorResponse = JSON.parse(xhr.responseText);
                    mostrarSwetAlert('error', errorResponse.message || 'Erro ao solicitar Empresa.', false);
                } catch (e) {
                    // Se não for JSON, mostrar erro genérico
                    mostrarSwetAlert('error', 'Erro de conexão ou servidor.', false);
                }
            }
        });
    });


    //// ./document.read
});