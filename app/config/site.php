<?php

// Conteúdo e marca desta cópia. Seções com listas vazias (ex.: products)
// simplesmente não são renderizadas pela landing.
return [
    'brand' => [
        'mentor_name' => 'Conceição Melo',
        'program_name' => 'Advocacia Previdenciária',
        'logo' => 'assets/img/logo-conceicao-melo.png',
    ],

    'colors' => [
        'primary' => '#C8A24C',
        'accent' => '#9A7428',
        'background' => '#2F3E46',
        'text' => '#F3EFE6',
    ],

    'fonts' => [
        'display' => 'Cinzel',
        'body' => 'Inter',
    ],

    'seo' => [
        'title' => 'Conceição Melo — Advocacia Previdenciária',
        'description' => 'Fale com a equipe de Conceição Melo, advocacia previdenciária, e receba o retorno com o próximo passo do seu caso.',
        'og_image' => 'assets/img/logo-conceicao-melo.png',
    ],

    'products' => [
        [
            'id' => 'aposentadoria',
            'title' => 'Aposentadoria',
            'description' => 'Análise do tempo de contribuição e das regras aplicáveis ao seu caso.',
        ],
        [
            'id' => 'revisao-beneficio',
            'title' => 'Revisão de benefício',
            'description' => 'Revisão de cálculo de benefícios já concedidos pelo INSS.',
        ],
        [
            'id' => 'bpc-loas',
            'title' => 'BPC/LOAS',
            'description' => 'Benefício assistencial para idosos e pessoas com deficiência de baixa renda.',
        ],
        [
            'id' => 'auxilio-doenca',
            'title' => 'Auxílio-doença / incapacidade',
            'description' => 'Orientação sobre pedidos de auxílio por incapacidade temporária ou permanente.',
        ],
        [
            'id' => 'pensao-morte',
            'title' => 'Pensão por morte',
            'description' => 'Orientação para dependentes solicitarem a pensão junto ao INSS.',
        ],
    ],

    'whatsapp' => [
        'number' => '5511999999999',
        'default_message' => 'Olá! Vim pelo site e gostaria de falar sobre um caso previdenciário.',
        'after_lead_message' => 'Olá! Sou {nome}, acabei de enviar meu contato pelo site.',
    ],

    'commercial' => [
        'label' => 'Falar com o comercial',
        'url' => 'https://wa.me/5511999999999',
    ],

    'admin_url' => '/admin/',

    'lgpd' => [
        'consent_text' => 'Autorizo o uso dos meus dados para contato sobre o meu caso, conforme a Política de Privacidade.',
        'policy_url' => '#',
        'footer_notice' => 'Seus dados são usados apenas para contato sobre o seu caso.',
    ],
];
