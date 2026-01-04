        // Inicializar DataTable
        $(document).ready(function() {
            $('#tabelaAtestados').DataTable({
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/pt-BR.json'
                },
                pageLength: 25,
                order: [[0, 'desc']]
            });

            // Configurar datas mínimas
            const hoje = new Date().toISOString().split('T')[0];
            $('#data_emissao').val(hoje);
            $('#data_inicio').val(hoje);
            $('#data_fim').val(hoje);

            // Calcular dias automaticamente
            $('#data_inicio, #data_fim').change(function() {
                const inicio = new Date($('#data_inicio').val());
                const fim = new Date($('#data_fim').val());
                
                if (inicio && fim && inicio <= fim) {
                    const diffTime = Math.abs(fim - inicio);
                    const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24)) + 1;
                    $('#dias_afastamento').val(diffDays);
                }
            });
        });

        // Função para visualizar atestado
        function visualizarAtestado(id) {
            fetch(`ajax/atestado-medico-ajax.php?id=${id}`)
                .then(response => response.text())
                .then(data => {
                    $('#detalhesAtestado').html(data);
                    const modal = new bootstrap.Modal(document.getElementById('modalVisualizarAtestado'));
                    modal.show();
                })
                .catch(error => {
                    console.error('Erro:', error);
                    Swal.fire('Erro', 'Não foi possível carregar os detalhes do atestado.', 'error');
                });
        }

        // Função para confirmar exclusão
        function confirmarExclusao(id) {
            Swal.fire({
                title: 'Tem certeza?',
                text: "Esta ação não poderá ser desfeita!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Sim, excluir!',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    fetch(`ajax/atestado-medico-ajax.php`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: `acao=excluir&id=${id}`
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            Swal.fire(
                                'Excluído!',
                                'Atestado excluído com sucesso.',
                                'success'
                            ).then(() => {
                                location.reload();
                            });
                        } else {
                            Swal.fire(
                                'Erro!',
                                data.message || 'Erro ao excluir atestado.',
                                'error'
                            );
                        }
                    });
                }
            });
        }

        // Validação do formulário
        document.getElementById('formCadastroAtestado').addEventListener('submit', function(e) {
            const dataInicio = new Date(document.getElementById('data_inicio').value);
            const dataFim = new Date(document.getElementById('data_fim').value);
            
            if (dataInicio > dataFim) {
                e.preventDefault();
                Swal.fire('Erro', 'A data de início não pode ser maior que a data de fim.', 'error');
                return false;
            }
        });

        ////criar novo 
    $('#formCadastroAtestado').on('submit', function (e) {
        e.preventDefault();

        $.ajax({
            url: 'ajax/atestado-medico-ajax.php?action=create',
            type: 'POST',
            dataType: 'json', // Adicione esta linha para esperar JSON
            data: $(this).serialize(),
            success: function (response) { // Adicione o parâmetro 'response'
                if (response.success) {
                    $('#formCadastroAtestado').modal('hide');
                    showSwetAlert('success', response.message, {
                        usarTimer: true,
                        tempo: 1500,
                        acao: () => location.reload()
                    });
                } else {
                    // Se success for false, mostrar mensagem de erro
                    mostrarSwetAlert('error', response.message || 'Erro ao adicionar Atestado.', false);
                }
            },
            error: function (xhr, status, error) {
                console.error('Erro na requisição:', error);
                console.error('Resposta:', xhr.responseText);

                try {
                    // Tentar parsear o erro como JSON
                    var errorResponse = JSON.parse(xhr.responseText);
                    mostrarSwetAlert('error', errorResponse.message || 'Erro ao adicionar Atestado.', false);
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
