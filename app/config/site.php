<?php

// Conteúdo e marca desta cópia. Seções com listas vazias (ex.: products)
// simplesmente não são renderizadas pela landing.
return [
    'brand' => [
        'mentor_name' => 'Conceição Melo',
        'program_name' => 'Advocacia Previdenciária',
        'logo' => 'assets/img/logo-conceicao-melo.svg',
    ],

    'colors' => [
        'primary' => '#263039',
        'accent' => '#C8A24C',
        'background' => '#263039',
        'text' => '#F3EFE6',
    ],

    'fonts' => [
        'display' => 'Archivo',
        'body' => 'Inter',
    ],

    'seo' => [
        'title' => 'Conceição Melo — Advocacia Previdenciária',
        'description' => 'Entre na lista de espera dos próximos lançamentos de Conceição Melo, advocacia previdenciária.',
        'og_image' => 'assets/img/logo-conceicao-melo.png',
    ],

    'waitlist' => [
        'headline' => 'Entre na lista de espera dos próximos lançamentos',
        'paragraph' => 'Estamos preparando novos produtos e serviços. Quem está na lista recebe o aviso primeiro.',
        'bullets' => [
            'Sem spam: contato apenas quando houver novidade relevante',
        ],
        'card_title' => 'Garanta sua vaga na lista',
        'card_subtitle' => 'Leva menos de um minuto. Conte também o que você procura.',
        'submit_label' => 'Entrar na lista de espera',
    ],

    'form' => [
        'name_label' => 'Nome',
        'name_placeholder' => 'Seu nome completo',
        'email_label' => 'Email',
        'email_placeholder' => 'voce@email.com',
        'phone_label' => 'Telefone / WhatsApp',
        'phone_placeholder' => '(00) 90000-0000',
        'message_label' => 'O que você procura?',
        'message_placeholder' => 'Descreva o produto, serviço ou resultado que você gostaria de encontrar aqui.',
    ],

    'products' => [],

    'whatsapp' => [
        'number' => '5586988036660',
        'default_message' => 'Olá! Vim pelo site e gostaria de entrar na lista de espera.',
        'after_lead_message' => 'Olá! Sou {nome}, acabei de entrar na lista de espera pelo site.',
    ],

    'commercial' => [
        'label' => 'Falar com o comercial',
        'url' => 'https://wa.me/5586988036660?text=Ol%C3%A1%21%20Vim%20pelo%20site%20e%20gostaria%20de%20falar%20com%20o%20comercial.',
    ],

    'admin_url' => '/admin/',

    'lgpd' => [
        'consent_text' => 'Concordo em receber contato e com a Política de Privacidade.',
        'policy_url' => '/privacidade.php',
        'footer_notice' => 'Seus dados são usados apenas para contato sobre o seu caso.',
        'contact_email' => 'conceicao@conceicaomeloadvprev.com.br',
    ],
];
