function carregarPagina(pagina) {
    $('#titulo-pagina').text(capitalizeFirstLetter(pagina));
    
    switch(pagina) {
        case 'dashboard':
            $('#conteudo').load('includes/dashboard-inc.php');
            break;
        case 'usuarios':
            carregarUsuarios();
            break;
        case 'produtos':
            $('#conteudo').html('<div class="alert alert-info">Módulo de Produtos em desenvolvimento</div>');
            break;
        case 'vendas':
            $('#conteudo').html('<div class="alert alert-info">Módulo de Vendas em desenvolvimento</div>');
            break;
        case 'relatorios':
            $('#conteudo').html('<div class="alert alert-info">Módulo de Relatórios em desenvolvimento</div>');
            break;
    }
}

function capitalizeFirstLetter(string) {
    return string.charAt(0).toUpperCase() + string.slice(1);
}

// Gerenciamento de Usuários
function carregarUsuarios() {
    $.ajax({
        url: 'api/usuarios.php',
        method: 'GET',
        dataType: 'json',
        success: function(data) {
            let html = `
                <div class="d-flex justify-content-between mb-3">
                    <h4>Gerenciar Usuários</h4>
                    <button class="btn btn-primary" onclick="abrirModalUsuario()">
                        <i class="bi bi-plus"></i> Novo Usuário
                    </button>
                </div>
                
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nome</th>
                                <th>Email</th>
                                <th>Perfil</th>
                                <th>Status</th>
                                <th>Data Criação</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>`;
            
            data.forEach(usuario => {
                html += `
                    <tr>
                        <td>${usuario.id}</td>
                        <td>${usuario.nome}</td>
                        <td>${usuario.email}</td>
                        <td>
                            <span class="badge bg-${usuario.perfil === 'admin' ? 'danger' : 'primary'}">
                                ${usuario.perfil}
                            </span>
                        </td>
                        <td>
                            <span class="badge bg-${usuario.status === 'ativo' ? 'success' : 'secondary'}">
                                ${usuario.status}
                            </span>
                        </td>
                        <td>${usuario.data_criacao}</td>
                        <td>
                            <button class="btn btn-sm btn-warning" onclick="editarUsuario(${usuario.id})">
                                <i class="bi bi-pencil"></i>
                            </button>
                            <button class="btn btn-sm btn-danger" onclick="excluirUsuario(${usuario.id})">
                                <i class="bi bi-trash"></i>
                            </button>
                        </td>
                    </tr>`;
            });
            
            html += `</tbody></table></div>`;
            $('#conteudo').html(html);
        },
        error: function() {
            $('#conteudo').html('<div class="alert alert-danger">Erro ao carregar usuários</div>');
        }
    });
}

function abrirModalUsuario(usuarioId = null) {
    let titulo = usuarioId ? 'Editar Usuário' : 'Novo Usuário';
    let url = 'api/usuarios.php';
    
    if (usuarioId) {
        url += '?id=' + usuarioId;
    }
    
    $.ajax({
        url: url,
        method: 'GET',
        dataType: 'json',
        success: function(data) {
            let usuario = usuarioId ? data : {};
            
            let html = `
                <div class="modal-header">
                    <h5 class="modal-title">${titulo}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="formUsuario">
                        ${usuarioId ? '<input type="hidden" name="id" value="' + usuario.id + '">' : ''}
                        
                        <div class="mb-3">
                            <label class="form-label">Nome</label>
                            <input type="text" class="form-control" name="nome" value="${usuario.nome || ''}" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" name="email" value="${usuario.email || ''}" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Senha</label>
                            <input type="password" class="form-control" name="senha" ${usuarioId ? '' : 'required'}>
                            <small class="text-muted">${usuarioId ? 'Deixe em branco para manter a senha atual' : 'Mínimo 6 caracteres'}</small>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Perfil</label>
                                <select class="form-select" name="perfil" required>
                                    <option value="vendedor" ${usuario.perfil === 'vendedor' ? 'selected' : ''}>Vendedor</option>
                                    <option value="gerente" ${usuario.perfil === 'gerente' ? 'selected' : ''}>Gerente</option>
                                    <option value="admin" ${usuario.perfil === 'admin' ? 'selected' : ''}>Administrador</option>
                                </select>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Status</label>
                                <select class="form-select" name="status" required>
                                    <option value="ativo" ${usuario.status === 'ativo' ? 'selected' : ''}>Ativo</option>
                                    <option value="inativo" ${usuario.status === 'inativo' ? 'selected' : ''}>Inativo</option>
                                </select>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary" onclick="salvarUsuario()">Salvar</button>
                </div>`;
            
            $('#modal-conteudo').html(html);
            $('#modalAcao').modal('show');
        }
    });
}

function editarUsuario(id) {
    abrirModalUsuario(id);
}

function salvarUsuario() {
    let formData = $('#formUsuario').serializeArray();
    let data = {};
    
    formData.forEach(item => {
        data[item.name] = item.value;
    });
    
    $.ajax({
        url: 'api/usuarios.php',
        method: 'POST',
        contentType: 'application/json',
        data: JSON.stringify(data),
        success: function(response) {
            if (response.success) {
                $('#modalAcao').modal('hide');
                carregarUsuarios();
                mostrarAlerta('success', 'Usuário salvo com sucesso!');
            } else {
                mostrarAlerta('danger', response.error || 'Erro ao salvar usuário');
            }
        },
        error: function() {
            mostrarAlerta('danger', 'Erro na comunicação com o servidor');
        }
    });
}

function excluirUsuario(id) {
    if (confirm('Tem certeza que deseja excluir este usuário?')) {
        $.ajax({
            url: 'api/usuarios.php',
            method: 'DELETE',
            contentType: 'application/json',
            data: JSON.stringify({id: id}),
            success: function(response) {
                if (response.success) {
                    carregarUsuarios();
                    mostrarAlerta('success', 'Usuário excluído com sucesso!');
                } else {
                    mostrarAlerta('danger', response.error || 'Erro ao excluir usuário');
                }
            }
        });
    }
}

function mostrarAlerta(tipo, mensagem) {
    let alerta = `<div class="alert alert-${tipo} alert-dismissible fade show position-fixed top-0 end-0 m-3" style="z-index: 9999">
                    ${mensagem}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                  </div>`;
    
    $('body').append(alerta);
    
    setTimeout(() => {
        $('.alert').alert('close');
    }, 5000);
}

// Carregar página inicial
$(document).ready(function() {
    carregarPagina('dashboard');
});
