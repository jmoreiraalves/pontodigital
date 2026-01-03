$(document).ready(function () {

    ////criar nova empresa
    $('#formNovaEmpresa').on('submit', function (e) {
        e.preventDefault();

        $.ajax({
            url: 'ajax/empresas_actions.php?action=create',
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
                    // mostrarSwetAlert('success', response.message, true);

                    // // Opção 1: Recarregar a página após sucesso
                    // location.reload();

                    // // Opção 2: Atualizar a tabela sem recarregar (recomendado)
                    // //carregarCategorias(); // Implemente esta função

                    // // Opção 3: Adicionar a nova categoria na tabela diretamente
                    // // adicionarNovaCategoriaNaTabela(response.id);

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
            success: function(response) {
                console.log(response.empresas);
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

                //console.log("depois de recuperado do botão: ", nome);

                $('#editEmpresaModals').modal('show');
            }
        });


        

    });

    // btndeleteEmpresaModals
    $('.btndeleteEmpresaModals').on('click', function () {
        // $('#deleteEmpresaModals').modal('hide');

        const id = $(this).data('id');
        const nome = $(this).data('nome');
        const cnpj = $(this).data('cnpj');
        const prefixo = $(this).data('prefixo');
        const endereco = $(this).data('endereco');
        // Preenche os campos do modal
        $('#delete_id').val(id);
        $('#delete_nome').val(nome);
        $('#delete_cnpj').val(cnpj);
        $("#delete_prefixo").val(prefixo);
        $('#delete_endereco').val(endereco);

        $('#deleteEmpresaModals').modal('show');

    });

    //// ./document.read
});