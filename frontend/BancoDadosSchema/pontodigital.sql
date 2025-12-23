-- Banco de dados: ponto_eletronico
CREATE DATABASE IF NOT EXISTS ponto_eletronico CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE ponto_eletronico;

-- Tabela de empresas
CREATE TABLE empresas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    cnpj VARCHAR(18) UNIQUE NOT NULL,
    prefixo VARCHAR(10) UNIQUE NOT NULL,
    endereco TEXT,
    telefone VARCHAR(20),
    email VARCHAR(100),
    ativa BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Tabela de usuários (administrativos)
CREATE TABLE usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    empresa_id INT NOT NULL,
    codigo VARCHAR(20) UNIQUE NOT NULL,
    nome VARCHAR(100) NOT NULL,
    cpf VARCHAR(14) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    senha VARCHAR(255) NOT NULL,
    tipo ENUM('super', 'admin', 'ti', 'gestor') DEFAULT 'admin',
    ativo BOOLEAN DEFAULT TRUE,
    ultimo_login TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (empresa_id) REFERENCES empresas(id) ON DELETE CASCADE,
    INDEX idx_empresa_ativo (empresa_id, ativo)
);

-- Tabela de colaboradores (quem bate ponto)
CREATE TABLE colaboradores (
    id INT AUTO_INCREMENT PRIMARY KEY,
    empresa_id INT NOT NULL,
    codigo VARCHAR(20) UNIQUE NOT NULL,
    nome VARCHAR(100) NOT NULL,
    cpf VARCHAR(14) NOT NULL,
    senha VARCHAR(255) NOT NULL,
    foto TEXT,
    turno ENUM('matutino', 'vespertino', 'noturno', 'flexivel') DEFAULT 'matutino',
    ativo BOOLEAN DEFAULT TRUE,
    permite_duas_empresas BOOLEAN DEFAULT FALSE,
    empresa_secundaria_id INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (empresa_id) REFERENCES empresas(id) ON DELETE CASCADE,
    FOREIGN KEY (empresa_secundaria_id) REFERENCES empresas(id) ON DELETE SET NULL,
    INDEX idx_cpf (cpf),
    INDEX idx_empresa_ativo (empresa_id, ativo)
);

-- Tabela de registros de ponto
CREATE TABLE registros_ponto (
    id INT AUTO_INCREMENT PRIMARY KEY,
    colaborador_id INT NOT NULL,
    empresa_id INT NOT NULL,
    tipo ENUM('entrada', 'saida', 'entrada_intervalo', 'retorno_intervalo') NOT NULL,
    data_registro DATE NOT NULL,
    hora_registro TIME NOT NULL,
    latitude DECIMAL(10, 8),
    longitude DECIMAL(11, 8),
    ip_address VARCHAR(45),
    user_agent TEXT,
    metodo ENUM('web', 'facial', 'mobile') DEFAULT 'web',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (colaborador_id) REFERENCES colaboradores(id) ON DELETE CASCADE,
    FOREIGN KEY (empresa_id) REFERENCES empresas(id) ON DELETE CASCADE,
    INDEX idx_colaborador_data (colaborador_id, data_registro),
    INDEX idx_empresa_data (empresa_id, data_registro),
    UNIQUE KEY uk_colaborador_tipo_hora (colaborador_id, tipo, data_registro, hora_registro)
);

-- Tabela de trocas de turno
CREATE TABLE trocas_turno (
    id INT AUTO_INCREMENT PRIMARY KEY,
    empresa_id INT NOT NULL,
    colaborador_substituido_id INT NOT NULL,
    colaborador_substituto_id INT NOT NULL,
    data_troca DATE NOT NULL,
    periodo ENUM('manha', 'tarde', 'noite', 'dia_inteiro') NOT NULL,
    motivo TEXT,
    aprovado_por INT,
    status ENUM('pendente', 'aprovado', 'recusado') DEFAULT 'pendente',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (empresa_id) REFERENCES empresas(id) ON DELETE CASCADE,
    FOREIGN KEY (colaborador_substituido_id) REFERENCES colaboradores(id) ON DELETE CASCADE,
    FOREIGN KEY (colaborador_substituto_id) REFERENCES colaboradores(id) ON DELETE CASCADE,
    FOREIGN KEY (aprovado_por) REFERENCES usuarios(id) ON DELETE SET NULL
);

-- Tabela de backups
CREATE TABLE backups (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    arquivo VARCHAR(255) NOT NULL,
    tamanho VARCHAR(20),
    tipo ENUM('completo', 'parcial') DEFAULT 'completo',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
);

-- Tabela de logs do sistema
CREATE TABLE logs_sistema (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NULL,
    colaborador_id INT NULL,
    acao VARCHAR(100) NOT NULL,
    descricao TEXT,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL,
    FOREIGN KEY (colaborador_id) REFERENCES colaboradores(id) ON DELETE SET NULL
);

-- Inserir super usuário padrão
INSERT INTO empresas (nome, cnpj, prefixo, email) VALUES 
('Empresa Principal', '00.000.000/0001-00', 'EMP', 'contato@empresa.com');

INSERT INTO usuarios (empresa_id, codigo, nome, cpf, email, senha, tipo) VALUES 
(1, 'EMP001', 'João Carlos Moreira Alves Junior', '000.000.000-00', 'jmoreiraalves@admin.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'super');

-- A senha acima é 'admin123' criptografada
