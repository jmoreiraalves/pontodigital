function mostrarSwetAlert(tipo, mensagem, usarTimer) {
    const options = {
        icon: tipo,
        title: tipo === 'success' ? 'Sucesso!' :
               tipo === 'error'   ? 'Erro!'    :
               tipo === 'warning' ? 'Atenção!' :
               tipo === 'info'    ? 'Informação' : 'Confirmação',
        text: mensagem || '',
        allowOutsideClick: false,
        allowEscapeKey: true
    };

    if (usarTimer) {
        options.showConfirmButton = false;
        options.timer = 5000;
        options.timerProgressBar = true;
    } else {
        options.showConfirmButton = true;
        options.confirmButtonText = 'OK';
    }

    Swal.fire(options);
}

/**
 * Função aprimorada de alerta com SweetAlert2
 * @param {string} tipo - Tipo do alerta
 * @param {string} mensagem - Mensagem principal
 * @param {object} config - Configurações adicionais
 */
function showSwetAlert(tipo, mensagem, config = {}) {
    const defaults = {
        titulo: null,
        usarTimer: false,
        tempo: 3000,
        acao: null,
        textoBotao: 'OK',
        mostrarBotao: true,
        html: false,
        customClass: {},
        backdrop: true,
        toast: false,
        position: 'center'
    };

    const settings = { ...defaults, ...config };

    // Títulos padrão baseados no tipo
    if (!settings.titulo) {
        const titulosPadrao = {
            'success': 'Sucesso!',
            'error': 'Erro!',
            'warning': 'Atenção!',
            'info': 'Informação',
            'question': 'Confirmação'
        };
        settings.titulo = titulosPadrao[tipo] || 'Alerta';
    }

    const options = {
        icon: tipo,
        title: settings.titulo,
        allowOutsideClick: !settings.usarTimer,
        allowEscapeKey: true,
        backdrop: settings.backdrop,
        position: settings.position,
        customClass: settings.customClass,
        toast: settings.toast
    };

    // Adicionar conteúdo (text ou html)
    if (settings.html) {
        options.html = mensagem;
    } else {
        options.text = mensagem;
    }

    // Configurar botão
    if (settings.mostrarBotao && !settings.usarTimer) {
        options.showConfirmButton = true;
        options.confirmButtonText = settings.textoBotao;
        options.confirmButtonColor= '#3085d6';
    } else {
        options.showConfirmButton = false;
    }

    // Configurar timer
    if (settings.usarTimer) {
        options.timer = settings.tempo;
        options.timerProgressBar = true;
        options.showConfirmButton = false;
    }

    // Executar o alerta
    Swal.fire(options).then((result) => {
        if (settings.acao && typeof settings.acao === 'function') {
            // Verificar como foi fechado
            const fechadoPor = result.dismiss;
            
            if (fechadoPor === Swal.DismissReason.timer ||
                fechadoPor === Swal.DismissReason.close ||
                result.isConfirmed) {
                settings.acao(result);
            }
        }
    });
}

// // Exemplos de uso avançado:

// // 1. Redirect após sucesso
// showAlert('success', 'Cadastro realizado com sucesso!', {
//     usarTimer: true,
//     tempo: 1500,
//     acao: () => location.reload()
// });

// // 2. Confirmação com ação
// showAlert('question', 'Tem certeza que deseja excluir este item?', {
//     textoBotao: 'Sim, excluir',
//     mostrarCancelButton: true,
//     cancelButtonText: 'Cancelar',
//     acao: (result) => {
//         if (result.isConfirmed) {
//             // Executar exclusão
//             excluirItem(id);
//         }
//     }
// });

// // 3. Toast notification
// showAlert('success', 'Configurações salvas!', {
//     toast: true,
//     position: 'top-end',
//     tempo: 3000,
//     usarTimer: true
// });

// // 4. Alert com HTML personalizado
// showAlert('info', `
//     <div class="text-left">
//         <h4>Detalhes do Erro</h4>
//         <p>Código: <strong>ERR-001</strong></p>
//         <p>Descrição: Falha na conexão com o banco de dados</p>
//     </div>
// `, {
//     html: true,
//     acao: () => abrirLogErros()
// });