<?php
require_once 'includes/config.php';
require_once 'includes/database.php';
require_once 'includes/functions.php';

// Verificar autenticação
if (!isAuth()) {
    header('Location: login.php');
    exit;
}

$user = $_SESSION['user'];

// Ações CRUD
$action = $_GET['action'] ?? '';
$id = $_GET['id'] ?? 0;

// Listar colaboradores da empresa
try {
    $stmt = $pdo->prepare("SELECT c.*, e.nome as empresa_nome 
                          FROM colaboradores c 
                          JOIN empresas e ON c.empresa_id = e.id 
                          WHERE c.empresa_id = ? 
                          ORDER BY c.nome");
    $stmt->execute([$user['empresa_id']]);
    $colaboradores = $stmt->fetchAll();
} catch (PDOException $e) {
    $errors[] = 'Erro ao carregar colaboradores: ' . $e->getMessage();
}

// Listar empresas para seleção secundária
try {
    $stmt = $pdo->prepare("SELECT id, nome, prefixo FROM empresas WHERE id != ? AND ativa = 1 ORDER BY nome");
    $stmt->execute([$user['empresa_id']]);
    $empresas = $stmt->fetchAll();
} catch (PDOException $e) {
    $errors[] = 'Erro ao carregar empresas: ' . $e->getMessage();
}

// Adicionar colaborador
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_colaborador'])) {
    $nome = toUpperCase($_POST['nome'] ?? '');
    $cpf = preg_replace('/[^0-9]/', '', $_POST['cpf'] ?? '');
    $senha = $_POST['senha'] ?? '';
    $confirmar_senha = $_POST['confirmar_senha'] ?? '';
    $turno = $_POST['turno'] ?? 'matutino';
    $permite_duas_empresas = isset($_POST['permite_duas_empresas']) ? 1 : 0;
    $empresa_secundaria_id = $_POST['empresa_secundaria_id'] ?? null;
    $ativo = isset($_POST['ativo']) ? 1 : 0;
    
    // Validações
    if (empty($nome) || empty($cpf) || empty($senha)) {
        $errors[] = 'Preencha todos os campos obrigatórios';
    } elseif (!validar_cpf($cpf)) {
        $errors[] = 'CPF inválido';
    } elseif ($senha !== $confirmar_senha) {
        $errors[] = 'As senhas não coincidem';
    } elseif (strlen($senha) < 6) {
        $errors[] = 'Senha deve ter pelo menos 6 caracteres';
    } else {
        try {
            // Verificar se CPF já existe na empresa
            $stmt = $pdo->prepare("SELECT id FROM colaboradores WHERE cpf = ? AND empresa_id = ?");
            $stmt->execute([$cpf, $user['empresa_id']]);
            if ($stmt->fetch()) {
                $errors[] = 'CPF já cadastrado nesta empresa';
            } else {
                // Gerar código único
                $codigo = gerar_codigo($user['empresa_prefixo'], 'colaboradores');
                
                // Inserir colaborador
                $stmt = $pdo->prepare("INSERT INTO colaboradores 
                                      (empresa_id, codigo, nome, cpf, senha, turno, 
                                       permite_duas_empresas, empresa_secundaria_id, ativo) 
                                      VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([
                    $user['empresa_id'],
                    $codigo,
                    $nome,
                    formatar_cpf($cpf),
                    password_hash($senha, PASSWORD_DEFAULT),
                    $turno,
                    $permite_duas_empresas,
                    $empresa_secundaria_id ?: null,
                    $ativo
                ]);
                
                $success = 'Colaborador cadastrado com sucesso! Código: ' . $codigo;
                registrar_log('CADASTRO_COLABORADOR', "Novo colaborador: $nome", $user['id']);
                
                // Recarregar lista
                $stmt = $pdo->prepare("SELECT c.*, e.nome as empresa_nome 
                                      FROM colaboradores c 
                                      JOIN empresas e ON c.empresa_id = e.id 
                                      WHERE c.empresa_id = ? 
                                      ORDER BY c.nome");
                $stmt->execute([$user['empresa_id']]);
                $colaboradores = $stmt->fetchAll();
            }
        } catch (PDOException $e) {
            $errors[] = 'Erro ao cadastrar colaborador: ' . $e->getMessage();
        }
    }
}

// Editar colaborador
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['edit_colaborador'])) {
    $id = $_POST['id'] ?? 0;
    $nome = toUpperCase($_POST['nome'] ?? '');
    $cpf = preg_replace('/[^0-9]/', '', $_POST['cpf'] ?? '');
    $turno = $_POST['turno'] ?? 'matutino';
    $permite_duas_empresas = isset($_POST['permite_duas_empresas']) ? 1 : 0;
    $empresa_secundaria_id = $_POST['empresa_secundaria_id'] ?? null;
    $ativo = isset($_POST['ativo']) ? 1 : 0;
    
    if (empty($nome) || empty($cpf)) {
        $errors[] = 'Preencha todos os campos obrigatórios';
    } else {
        try {
            // Verificar se CPF já existe (excluindo o próprio colaborador)
            $stmt = $pdo->prepare("SELECT id FROM colaboradores WHERE cpf = ? AND empresa_id = ? AND id != ?");
            $stmt->execute([$cpf, $user['empresa_id'], $id]);
            if ($stmt->fetch()) {
                $errors[] = 'CPF já cadastrado nesta empresa';
            } else {
                // Atualizar colaborador
                $stmt = $pdo->prepare("UPDATE colaboradores 
                                      SET nome = ?, cpf = ?, turno = ?, 
                                          permite_duas_empresas = ?, empresa_secundaria_id = ?, 
                                          ativo = ?, updated_at = NOW() 
                                      WHERE id = ? AND empresa_id = ?");
                $stmt->execute([
                    $nome,
                    formatar_cpf($cpf),
                    $turno,
                    $permite_duas_empresas,
                    $empresa_secundaria_id ?: null,
                    $ativo,
                    $id,
                    $user['empresa_id']
                ]);
                
                // Atualizar senha se fornecida
                if (!empty($_POST['senha'])) {
                    $senha = $_POST['senha'];
                    if (strlen($senha) >= 6) {
                        $stmt = $pdo->prepare("UPDATE colaboradores 
                                              SET senha = ?, updated_at = NOW() 
                                              WHERE id = ? AND empresa_id = ?");
                        $stmt->execute([
                            password_hash($senha, PASSWORD_DEFAULT),
                            $id,
                            $user['empresa_id']
                        ]);
                    }
                }
                
                $success = 'Colaborador atualizado com sucesso!';
                registrar_log('EDITAR_COLABORADOR', "Colaborador ID $id atualizado", $user['id']);
                
                // Recarregar lista
                $stmt = $pdo->prepare("SELECT c.*, e.nome as empresa_nome 
                                      FROM colaboradores c 
                                      JOIN empresas e ON c.empresa_id = e.id 
                                      WHERE c.empresa_id = ? 
                                      ORDER BY c.nome");
                $stmt->execute([$user['empresa_id']]);
                $colaboradores = $stmt->fetchAll();
            }
        } catch (PDOException $e) {
            $errors[] = 'Erro ao atualizar colaborador: ' . $e->getMessage();
        }
    }
}

// Excluir colaborador
if ($action == 'delete' && $id > 0) {
    try {
        // Verificar se colaborador tem registros de ponto
        $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM registros_ponto WHERE colaborador_id = ?");
        $stmt->execute([$id]);
        $total_registros = $stmt->fetch()['total'];
        
        if ($total_registros > 0) {
            $errors[] = 'Não é possível excluir colaborador com registros de ponto';
        } else {
            $stmt = $pdo->prepare("DELETE FROM colaboradores WHERE id = ? AND empresa_id = ?");
            $stmt->execute([$id, $user['empresa_id']]);
            
            if ($stmt->rowCount() > 0) {
                $success = 'Colaborador excluído com sucesso!';
                registrar_log('EXCLUIR_COLABORADOR', "Colaborador ID $id excluído", $user['id']);
                
                // Recarregar lista
                $stmt = $pdo->prepare("SELECT c.*, e.nome as empresa_nome 
                                      FROM colaboradores c 
                                      JOIN empresas e ON c.empresa_id = e.id 
                                      WHERE c.empresa_id = ? 
                                      ORDER BY c.nome");
                $stmt->execute([$user['empresa_id']]);
                $colaboradores = $stmt->fetchAll();
            } else {
                $errors[] = 'Colaborador não encontrado ou não pertence à sua empresa';
            }
        }
    } catch (PDOException $e) {
        $errors[] = 'Erro ao excluir colaborador: ' . $e->getMessage();
    }
}

// Obter colaborador para edição
$colaborador_edit = null;
if ($action == 'edit' && $id > 0) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM colaboradores WHERE id = ? AND empresa_id = ?");
        $stmt->execute([$id, $user['empresa_id']]);
        $colaborador_edit = $stmt->fetch();
        
        if (!$colaborador_edit) {
            $errors[] = 'Colaborador não encontrado';
            $action = '';
        }
    } catch (PDOException $e) {
        $errors[] = 'Erro ao carregar colaborador: ' . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Colaboradores - <?php echo SITE_NAME; ?></title>
    
    <!-- Bootstrap 5.0 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- CSS Personalizado -->
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="container-fluid p-0">
        <!-- Navbar -->
        <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
            <div class="container">
                <a class="navbar-brand" href="dashboard.php">
                    <i class="fas fa-clock"></i> <?php echo SITE_NAME; ?>
                </a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="navbarNav">
                    <ul class="navbar-nav ms-auto">
                        <li class="nav-item">
                            <a class="nav-link" href="dashboard.php">
                                <i class="fas fa-tachometer-alt"></i> Dashboard
                            </a>
                        </li>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle active" href="#" id="userDropdown" role="button" 
                               data-bs-toggle="dropdown">
                                <i class="fas fa-user-circle"></i> <?php echo $user['nome']; ?>
                            </a>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="#">
                                    <i class="fas fa-building"></i> <?php echo $user['empresa_nome']; ?>
                                </a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="logout.php">
                                    <i class="fas fa-sign-out-alt"></i> Sair
                                </a></li>
                            </ul>
                        </li>
                    </ul>
                </div>
            </div>
        </nav>

        <!-- Sidebar e Conteúdo -->
        <div class="container-fluid">
            <div class="row">
                <!-- Sidebar -->
                <div class="col-md-3 col-lg-2 sidebar d-md-block">
                    <div class="position-sticky pt-3">
                        <ul class="nav flex-column">
                            <li class="nav-item">
                                <a class="nav-link" href="dashboard.php">
                                    <i class="fas fa-tachometer-alt"></i> Dashboard
                                </a>
                            </li>
                            
                            <?php if (hasPermission($user['tipo'], 'super')): ?>
                            <li class="nav-item">
                                <a class="nav-link" href="empresas.php">
                                    <i class="fas fa-building"></i> Empresas
                                </a>
                            </li>
                            <?php endif; ?>
                            
                            <li class="nav-item">
                                <a class="nav-link" href="empresa.php">
                                    <i class="fas fa-edit"></i> Minha Empresa
                                </a>
                            </li>
                            
                            <li class="nav-item">
                                <a class="nav-link active" href="colaboradores.php">
                                    <i class="fas fa-users"></i> Colaboradores
                                </a>
                            </li>
                            
                            <li class="nav-item">
                                <a class="nav-link" href="turnos.php">
                                    <i class="fas fa-exchange-alt"></i> Troca de Turnos
                                </a>
                            </li>
                            
                            <?php if (hasPermission($user['tipo'], 'ti')): ?>
                            <li class="nav-item">
                                <a class="nav-link" href="ti.php">
                                    <i class="fas fa-laptop-code"></i> Profissional T.I.
                                </a>
                            </li>
                            <?php endif; ?>
                            
                            <li class="nav-item mt-3">
                                <a class="nav-link btn btn-success" href="registrar_ponto.php">
                                    <i class="fas fa-fingerprint"></i> Registrar Ponto
                                </a>
                            </li>
                            
                            <li class="nav-item">
                                <button class="nav-link btn btn-warning mt-2" data-bs-toggle="modal" 
                                        data-bs-target="#facialModal">
                                    <i class="fas fa-camera"></i> Reconhecimento Facial
                                </button>
                            </li>
                        </ul>
                    </div>
                </div>

                <!-- Conteúdo Principal -->
                <main class="col-md-9 col-lg-10 main-content">
                    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                        <h1 class="h2">
                            <i class="fas fa-users"></i> Gerenciar Colaboradores
                        </h1>
                        <div class="btn-toolbar mb-2 mb-md-0">
                            <button type="button" class="btn btn-primary" data-bs-toggle="modal" 
                                    data-bs-target="#addColaboradorModal">
                                <i class="fas fa-user-plus"></i> Novo Colaborador
                            </button>
                        </div>
                    </div>

                    <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <?php foreach ($errors as $error): ?>
                        <p class="mb-0"><i class="fas fa-exclamation-circle"></i> <?php echo $error; ?></p>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($success)): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i> <?php echo $success; ?>
                    </div>
                    <?php endif; ?>

                    <!-- Busca -->
                    <div class="card mb-3">
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-search"></i></span>
                                        <input type="text" class="form-control" id="searchColaborador" 
                                               placeholder="Buscar colaborador...">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="text-end">
                                        <span class="badge bg-primary">
                                            Total: <?php echo count($colaboradores); ?> colaboradores
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Lista de Colaboradores -->
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="fas fa-list"></i> Colaboradores da Empresa
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover" id="colaboradoresTable">
                                    <thead>
                                        <tr>
                                            <th>Código</th>
                                            <th>Nome</th>
                                            <th>CPF</th>
                                            <th>Turno</th>
                                            <th>Dual Empresa</th>
                                            <th>Status</th>
                                            <th>Ações</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($colaboradores as $colaborador): ?>
                                        <tr>
                                            <td><span class="badge bg-primary"><?php echo $colaborador['codigo']; ?></span></td>
                                            <td><?php echo htmlspecialchars($colaborador['nome']); ?></td>
                                            <td><?php echo $colaborador['cpf']; ?></td>
                                            <td>
                                                <span class="badge bg-info">
                                                    <?php 
                                                        $turnos = getTurnos();
                                                        echo $turnos[$colaborador['turno']] ?? $colaborador['turno'];
                                                    ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php if ($colaborador['permite_duas_empresas']): ?>
                                                    <span class="badge bg-success">Sim</span>
                                                    <?php if ($colaborador['empresa_secundaria_id']): ?>
                                                        <small class="text-muted">(Secundária)</small>
                                                    <?php endif; ?>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary">Não</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <span class="badge <?php echo $colaborador['ativo'] ? 'bg-success' : 'bg-danger'; ?>">
                                                    <?php echo $colaborador['ativo'] ? 'Ativo' : 'Inativo'; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <a href="?action=edit&id=<?php echo $colaborador['id']; ?>" 
                                                   class="btn btn-sm btn-warning" title="Editar">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <a href="?action=delete&id=<?php echo $colaborador['id']; ?>" 
                                                   class="btn btn-sm btn-danger btn-delete" title="Excluir">
                                                    <i class="fas fa-trash"></i>
                                                </a>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </main>
            </div>
        </div>
    </div>

    <!-- Modal Adicionar Colaborador -->
    <div class="modal fade" id="addColaboradorModal" tabindex="-1" aria-labelledby="addColaboradorModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="addColaboradorModalLabel">
                        <i class="fas fa-user-plus"></i> Novo Colaborador
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" class="needs-validation" novalidate>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="nome" class="form-label">Nome Completo *</label>
                                <input type="text" class="form-control uppercase" id="nome" name="nome" required>
                                <div class="invalid-feedback">Campo obrigatório</div>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="cpf" class="form-label">CPF *</label>
                                <input type="text" class="form-control cpf-mask" id="cpf" name="cpf" required>
                                <div class="invalid-feedback">CPF inválido</div>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="senha" class="form-label">Senha *</label>
                                <div class="input-group">
                                    <input type="password" class="form-control" id="senha" name="senha" required minlength="6">
                                    <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('senha')">
                                        <i class="fas fa-eye" id="toggleSenha"></i>
                                    </button>
                                </div>
                                <div class="invalid-feedback">Mínimo 6 caracteres</div>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="confirmar_senha" class="form-label">Confirmar Senha *</label>
                                <div class="input-group">
                                    <input type="password" class="form-control" id="confirmar_senha" name="confirmar_senha" required minlength="6">
                                    <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('confirmar_senha')">
                                        <i class="fas fa-eye" id="toggleConfirmarSenha"></i>
                                    </button>
                                </div>
                                <div class="invalid-feedback">As senhas devem coincidir</div>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="turno" class="form-label">Turno *</label>
                                <select class="form-select" id="turno" name="turno" required>
                                    <?php foreach (getTurnos() as $valor => $descricao): ?>
                                    <option value="<?php echo $valor; ?>"><?php echo $descricao; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Permissões</label>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="permite_duas_empresas" 
                                           name="permite_duas_empresas" value="1">
                                    <label class="form-check-label" for="permite_duas_empresas">
                                        Permitir duas empresas simultaneamente
                                    </label>
                                </div>
                                <div class="form-check mt-2">
                                    <input class="form-check-input" type="checkbox" id="ativo" name="ativo" value="1" checked>
                                    <label class="form-check-label" for="ativo">Ativo</label>
                                </div>
                            </div>
                            
                            <div class="col-12 mb-3">
                                <label for="empresa_secundaria_id" class="form-label">Empresa Secundária (opcional)</label>
                                <select class="form-select" id="empresa_secundaria_id" name="empresa_secundaria_id">
                                    <option value="">Selecione uma empresa...</option>
                                    <?php foreach ($empresas as $emp): ?>
                                    <option value="<?php echo $emp['id']; ?>">
                                        <?php echo htmlspecialchars($emp['nome']); ?> (<?php echo $emp['prefixo']; ?>)
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                                <small class="text-muted">Apenas para colaboradores que trabalham em duas empresas</small>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="fas fa-times"></i> Cancelar
                        </button>
                        <button type="submit" name="add_colaborador" class="btn btn-primary">
                            <i class="fas fa-save"></i> Salvar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Editar Colaborador -->
    <?php if ($action == 'edit' && $colaborador_edit): ?>
    <div class="modal fade show" id="editColaboradorModal" tabindex="-1" aria-labelledby="editColaboradorModalLabel" 
         style="display: block; background-color: rgba(0,0,0,0.5);" aria-modal="true" role="dialog">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-warning text-dark">
                    <h5 class="modal-title" id="editColaboradorModalLabel">
                        <i class="fas fa-edit"></i> Editar Colaborador
                    </h5>
                    <a href="colaboradores.php" class="btn-close"></a>
                </div>
                <form method="POST" class="needs-validation" novalidate>
                    <input type="hidden" name="id" value="<?php echo $colaborador_edit['id']; ?>">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="edit_nome" class="form-label">Nome Completo *</label>
                                <input type="text" class="form-control uppercase" id="edit_nome" name="nome" 
                                       value="<?php echo htmlspecialchars($colaborador_edit['nome']); ?>" required>
                                <div class="invalid-feedback">Campo obrigatório</div>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="edit_cpf" class="form-label">CPF *</label>
                                <input type="text" class="form-control cpf-mask" id="edit_cpf" name="cpf" 
                                       value="<?php echo $colaborador_edit['cpf']; ?>" required>
                                <div class="invalid-feedback">CPF inválido</div>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="edit_senha" class="form-label">Nova Senha (deixe em branco para não alterar)</label>
                                <div class="input-group">
                                    <input type="password" class="form-control" id="edit_senha" name="senha" minlength="6">
                                    <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('edit_senha')">
                                        <i class="fas fa-eye" id="toggleEditSenha"></i>
                                    </button>
                                </div>
                                <small class="text-muted">Mínimo 6 caracteres</small>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="edit_turno" class="form-label">Turno *</label>
                                <select class="form-select" id="edit_turno" name="turno" required>
                                    <?php foreach (getTurnos() as $valor => $descricao): ?>
                                    <option value="<?php echo $valor; ?>" 
                                        <?php echo $colaborador_edit['turno'] == $valor ? 'selected' : ''; ?>>
                                        <?php echo $descricao; ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Permissões</label>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="edit_permite_duas_empresas" 
                                           name="permite_duas_empresas" value="1"
                                           <?php echo $colaborador_edit['permite_duas_empresas'] ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="edit_permite_duas_empresas">
                                        Permitir duas empresas simultaneamente
                                    </label>
                                </div>
                                <div class="form-check mt-2">
                                    <input class="form-check-input" type="checkbox" id="edit_ativo" name="ativo" value="1"
                                           <?php echo $colaborador_edit['ativo'] ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="edit_ativo">Ativo</label>
                                </div>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="edit_empresa_secundaria_id" class="form-label">Empresa Secundária (opcional)</label>
                                <select class="form-select" id="edit_empresa_secundaria_id" name="empresa_secundaria_id">
                                    <option value="">Selecione uma empresa...</option>
                                    <?php foreach ($empresas as $emp): ?>
                                    <option value="<?php echo $emp['id']; ?>"
                                        <?php echo $colaborador_edit['empresa_secundaria_id'] == $emp['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($emp['nome']); ?> (<?php echo $emp['prefixo']; ?>)
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="col-12 mb-3">
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle"></i>
                                    <strong>Código do Colaborador:</strong> <?php echo $colaborador_edit['codigo']; ?><br>
                                    <strong>Empresa:</strong> <?php echo $user['empresa_nome']; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <a href="colaboradores.php" class="btn btn-secondary">
                            <i class="fas fa-times"></i> Cancelar
                        </a>
                        <button type="submit" name="edit_colaborador" class="btn btn-warning">
                            <i class="fas fa-save"></i> Atualizar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"></script>
    <!-- JavaScript Personalizado -->
    <script src="assets/js/script.js"></script>
    
    <script>
    <?php if ($action == 'edit' && $colaborador_edit): ?>
    // Mostrar modal de edição automaticamente
    document.addEventListener('DOMContentLoaded', function() {
        var modal = new bootstrap.Modal(document.getElementById('editColaboradorModal'));
        modal.show();
    });
    <?php endif; ?>
    </script>
</body>
</html>
