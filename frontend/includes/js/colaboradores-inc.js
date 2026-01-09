$(document).ready(function () {

    ////criar novo colaborador
    $('#formNovaColaborador').on('submit', function (e) {
        e.preventDefault();

        $.ajax({
            url: 'ajax/colaboradores-ajax.php?action=create',
            type: 'POST',
            dataType: 'json', // Adicione esta linha para esperar JSON
            data: $(this).serialize(),
            success: function (response) { // Adicione o parâmetro 'response'
                if (response.success) {
                    $('#formNovaColaborador').modal('hide');
                    showSwetAlert('success', response.message, {
                        usarTimer: true,
                        tempo: 1500,
                        acao: () => location.reload()
                    });
                } else {
                    // Se success for false, mostrar mensagem de erro
                    mostrarSwetAlert('error', response.message || 'Erro ao adicionar Colaborador.', false);
                }
            },
            error: function (xhr, status, error) {
                console.error('Erro na requisição:', error);
                console.error('Resposta:', xhr.responseText);

                try {
                    // Tentar parsear o erro como JSON
                    var errorResponse = JSON.parse(xhr.responseText);
                    mostrarSwetAlert('error', errorResponse.message || 'Erro ao adicionar Colaborador.', false);
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

    ////alterar dados de uma colaboradores
    $('#formeditColaborador').on('submit', function (e) {
        e.preventDefault();

        $.ajax({
            url: 'ajax/colaboradores-ajax.php?action=update',
            type: 'POST',
            dataType: 'json', // Adicione esta linha para esperar JSON
            data: $(this).serialize(),
            success: function (response) { // Adicione o parâmetro 'response'
                if (response.success) {
                    $('#formeditColaborador').modal('hide');
                    showSwetAlert('success', response.message, {
                        usarTimer: true,
                        tempo: 1500,
                        acao: () => location.reload()
                    });
                } else {
                    // Se success for false, mostrar mensagem de erro
                    mostrarSwetAlert('error', response.message || 'Erro ao editar Colaborador.', false);
                }
            },
            error: function (xhr, status, error) {
                console.error('Erro na requisição:', error);
                console.error('Resposta:', xhr.responseText);

                try {
                    // Tentar parsear o erro como JSON
                    var errorResponse = JSON.parse(xhr.responseText);
                    mostrarSwetAlert('error', errorResponse.message || 'Erro ao editar Colaborador.', false);
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

    //// deletar uma colaboradores não permitindo mais o login
     $('#formdeleteColaborador').on('submit', function (e) {
        e.preventDefault();

        $.ajax({
            url: 'ajax/colaboradores-ajax.php?action=delete',
            type: 'POST',
            dataType: 'json', // Adicione esta linha para esperar JSON
            data: $(this).serialize(),
            success: function (response) { // Adicione o parâmetro 'response'
                if (response.success) {
                    $('#formdeleteColaborador').modal('hide');
                    showSwetAlert('success', response.message, {
                        usarTimer: true,
                        tempo: 1500,
                        acao: () => location.reload()
                    });
                } else {
                    // Se success for false, mostrar mensagem de erro
                    mostrarSwetAlert('error', response.message || 'Erro ao deletar Colaborador.', false);
                }
            },
            error: function (xhr, status, error) {
                console.error('Erro na requisição:', error);
                console.error('Resposta:', xhr.responseText);

                try {
                    // Tentar parsear o erro como JSON
                    var errorResponse = JSON.parse(xhr.responseText);
                    mostrarSwetAlert('error', errorResponse.message || 'Erro ao deletar Colaborador.', false);
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

    // 
    $('.btneditColaboradorModals').on('click', function () {
        const id = $(this).data('id');

        alert("buscar colaborador");

        $.ajax({
            url: 'ajax/colaboradores-ajax.php',
            type: 'GET',
            data: {  // <-- Passa como objeto data
                action: 'get',
                id: id
            },
            success: function (response) {
                if (response.success) {
                    const nome = response.colaborador.nome;
                    const cnpj = response.colaborador.cnpj;
                    const prefixo = response.colaborador.prefixo;
                    const endereco = response.colaborador.endereco;
                    const telefone = response.colaborador.telefone;
                    const email = response.colaborador.email;
                    const ativa = response.colaborador.ativa;
                    // Preenche os campos do modal
                    $('#update_id').val(id);
                    $('#update_nome').val(nome);
                    $('#update_cnpj').val(cnpj);
                    $("#update_prefixo").val(prefixo);
                    $('#update_endereco').val(endereco);
                    $('#update_telefone').val(telefone);
                    $('#update_email').val(email);
                    $('#update_ativa').val(ativa);
                    $('#editColaboradorModals').modal('show');
                } else {
                    // Se success for false, mostrar mensagem de erro
                    mostrarSwetAlert('error', response.message || 'Erro ao solicitar Colaborador.', false);
                }
            },
            error: function (xhr, status, error) {
                console.error('Erro na requisição:', error);
                console.error('Resposta:', xhr.responseText);

                try {
                    // Tentar parsear o erro como JSON
                    var errorResponse = JSON.parse(xhr.responseText);
                    mostrarSwetAlert('error', errorResponse.message || 'Erro ao solicitar Colaborador.', false);
                } catch (e) {
                    // Se não for JSON, mostrar erro genérico
                    mostrarSwetAlert('error', 'Erro de conexão ou servidor.', false);
                }
            }
        });
    });

    // btndeleteColaboradorModals
    $('.btndeleteColaboradorModals').on('click', function () {
        const id = $(this).data('id');

        $.ajax({
            url: 'ajax/colaboradores-ajax.php',
            type: 'GET',
            data: {  // <-- Passa como objeto data
                action: 'get',
                id: id
            },
            success: function (response) {
                if (response.success) {
                    // Preenche os campos do modal
                    
                    const nome = response.colaborador.nome;
                    const cnpj = response.colaborador.cnpj;
                    const prefixo = response.colaborador.prefixo;
                    const endereco = response.colaborador.endereco;
                    // Preenche os campos do modal
                    $('#update_id').val(id);
                    $('#update_nome').val(nome);
                    $('#update_cnpj').val(cnpj);
                    $("#update_prefixo").val(prefixo);
                    $('#update_endereco').val(endereco);
                    $('#deleteColaboradorModals').modal('show');
                } else {
                    // Se success for false, mostrar mensagem de erro
                    mostrarSwetAlert('error', response.message || 'Erro ao solicitar Colaborador.', false);
                }
            },
            error: function (xhr, status, error) {
                console.error('Erro na requisição:', error);
                console.error('Resposta:', xhr.responseText);

                try {
                    // Tentar parsear o erro como JSON
                    var errorResponse = JSON.parse(xhr.responseText);
                    mostrarSwetAlert('error', errorResponse.message || 'Erro ao solicitar Colaborador.', false);
                } catch (e) {
                    // Se não for JSON, mostrar erro genérico
                    mostrarSwetAlert('error', 'Erro de conexão ou servidor.', false);
                }
            }
        });
    });


    //// ./document.read
});