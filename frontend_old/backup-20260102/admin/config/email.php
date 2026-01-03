<?php
return [
    'host' => 'smtp.gmail.com',
    'port' => 587,
    'encryption' => 'tls',
    'username' => 'sistema@empresa.com',
    'password' => 'senha_segura',
    'from_email' => 'sistema@empresa.com',
    'from_name' => 'Sistema de Contratos',
    'reply_to' => 'contato@empresa.com',
    'bcc' => ['gerencia@empresa.com'],
    
    // Templates disponíveis
    'templates' => [
        'expiration_alert',
        'workflow_approval',
        'signature_request',
        'document_uploaded',
        'procuracao_expired'
    ],
    
    // Configurações de agendamento
    'schedule' => [
        'expiration_check' => '08:00', // Verificar vencimentos às 8h
        'daily_report' => '17:00',     // Relatório diário às 17h
        'weekly_report' => '09:00',    // Relatório semanal segunda-feira
    ]
];
