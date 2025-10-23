<?php

return [
    'failed' => 'Estas credenciais não correspondem aos nossos registros.',
    'password' => 'A senha fornecida está incorreta.',
    'throttle' => 'Muitas tentativas de login. Por favor, tente novamente em :seconds segundos.',
    'verification' => [
        'title' => 'Verificação de Email',
        'description' => 'Antes de continuar, você poderia verificar seu endereço de email clicando no link que acabamos de enviar? Se você não recebeu o email, teremos prazer em enviar outro.',
        'link_sent' => 'Um novo link de verificação foi enviado para o endereço de email fornecido nas configurações do seu perfil.',
        'resend' => 'Reenviar Email de Verificação',
        'edit_profile' => 'Editar Perfil',
        'logout' => 'Sair',
        'email' => [
            'subject' => 'Verifique seu endereço de email',
            'greeting' => 'Olá!',
            'line1' => 'Por favor, clique no botão abaixo para verificar seu endereço de email.',
            'action' => 'Verificar Email',
            'line2' => 'Se você não criou uma conta, nenhuma ação adicional é necessária.',
            'salutation' => 'Atenciosamente,',
            'team' => 'Equipe :app',
            'subcopy' => 'Se você está tendo problemas para clicar no botão ":actionText", copie e cole o URL abaixo no seu navegador:',
            'copyright' => '© :year :app. Todos os direitos reservados.',
        ],
    ],
    'two_factor' => [
        'title' => 'Autenticação de Dois Fatores',
        'description' => [
            'code' => 'Por favor, confirme o acesso à sua conta inserindo o código de autenticação fornecido pelo seu aplicativo autenticador.',
            'recovery' => 'Por favor, confirme o acesso à sua conta inserindo um dos seus códigos de recuperação de emergência.',
        ],
        'code' => 'Código',
        'recovery_code' => 'Código de Recuperação',
        'use_recovery' => 'Usar um código de recuperação',
        'use_code' => 'Usar um código de autenticação',
        'login' => 'Entrar',
    ],
    'confirm_password' => [
        'title' => 'Confirmar Senha',
        'description' => 'Esta é uma área segura da aplicação. Por favor, confirme sua senha antes de continuar.',
        'password' => 'Senha',
        'confirm' => 'Confirmar',
    ],
    'forgot_password' => [
        'title' => 'Esqueceu a Senha',
        'description' => 'Esqueceu sua senha? Sem problemas. Apenas informe seu endereço de e-mail e enviaremos um link para redefinir sua senha.',
        'email' => 'E-mail',
        'button' => 'Enviar Link de Redefinição de Senha',
        'status' => 'Enviamos um link de redefinição de senha para o seu e-mail.',
    ],
];
