<?php

// Conteúdo e marca desta cópia. Seções com listas vazias (ex.: testimonials)
// simplesmente não são renderizadas pela landing.
return [
    'brand' => [
        'mentor_name' => 'Marina Costa',
        'program_name' => 'Rota Clara',
        'logo' => 'assets/img/logo.svg',
        'tagline' => 'Mentoria para profissionais autônomas estruturarem e precificarem seus serviços',
    ],

    'colors' => [
        'primary' => '#1F4D3A',
        'accent' => '#C8553D',
        'background' => '#F6F3EE',
        'text' => '#1B1F23',
    ],

    'fonts' => [
        'display' => 'Archivo',
        'body' => 'Inter Tight',
    ],

    'seo' => [
        'title' => 'Rota Clara — Mentoria com Marina Costa',
        'description' => 'Estruture sua rotina, organize seus preços e ganhe clareza para crescer como profissional autônoma.',
        'og_image' => 'assets/img/mentor.svg',
    ],

    'hero' => [
        'eyebrow' => 'Mentoria Rota Clara',
        'headline' => 'Clareza para organizar seu negócio e cobrar o que você vale',
        'subheadline' => 'Acompanhamento prático para profissionais autônomas que querem sair do improviso e ter uma rotina, um preço e um plano.',
        'cta_label' => 'Quero conversar com a Marina',
    ],

    'about' => [
        'title' => 'Sobre a Marina',
        'text' => [
            'Depois de 12 anos atuando como consultora de negócios, Marina Costa criou a Rota Clara para ajudar profissionais autônomas a saírem da correria sem direção.',
            'Já acompanhou mais de 200 profissionais na estruturação de rotina, precificação e posicionamento — sempre com método próprio e sem fórmulas prontas.',
        ],
        'photo' => 'assets/img/mentor.svg',
        'highlights' => [
            '+200 profissionais mentoradas',
            '12 anos de experiência em consultoria',
            'Metodologia própria de precificação',
        ],
    ],

    'products' => [
        [
            'id' => 'mentoria-individual',
            'title' => 'Mentoria Individual',
            'description' => 'Acompanhamento 1:1 por 3 meses para organizar rotina, preços e posicionamento.',
            'price' => 'R$ 1.497',
            'link' => null,
        ],
        [
            'id' => 'grupo-aceleracao',
            'title' => 'Grupo de Aceleração',
            'description' => 'Turma fechada com encontros quinzenais e comunidade para trocar experiências.',
            'price' => 'R$ 697',
            'link' => null,
        ],
        [
            'id' => 'diagnostico-express',
            'title' => 'Diagnóstico Express',
            'description' => 'Sessão única para identificar os principais gargalos do seu negócio hoje.',
            'price' => 'R$ 297',
            'link' => null,
        ],
    ],

    'testimonials' => [
        [
            'name' => 'Camila Andrade',
            'role' => 'Consultora financeira',
            'text' => 'Em três meses organizei minha agenda e finalmente cobro um preço justo pelo meu trabalho.',
            'photo' => null,
        ],
        [
            'name' => 'Juliana Prado',
            'role' => 'Designer de interiores',
            'text' => 'A mentoria me deu um passo a passo real, nada de fórmula genérica.',
            'photo' => null,
        ],
        [
            'name' => 'Renata Souza',
            'role' => 'Nutricionista',
            'text' => 'Sair do diagnóstico já com um plano de ação foi o que me fez decidir continuar na mentoria.',
            'photo' => null,
        ],
    ],

    'faq' => [
        [
            'question' => 'Para quem é a mentoria?',
            'answer' => 'Para profissionais autônomas que já atendem clientes, mas sentem que faltam rotina e clareza de preços.',
        ],
        [
            'question' => 'Quanto tempo dura o acompanhamento?',
            'answer' => 'A mentoria individual dura 3 meses, com encontros quinzenais e suporte entre as sessões.',
        ],
        [
            'question' => 'É online ou presencial?',
            'answer' => 'Todo o acompanhamento é online, por videochamada.',
        ],
        [
            'question' => 'Como funciona o pagamento?',
            'answer' => 'Você recebe os detalhes de pagamento após a primeira conversa, sem compromisso.',
        ],
        [
            'question' => 'E se eu não souber qual produto escolher?',
            'answer' => 'Marque uma conversa pelo formulário: a Marina te ajuda a decidir o melhor caminho.',
        ],
    ],

    'whatsapp' => [
        'number' => '5511999999999',
        'default_message' => 'Olá, Marina! Vim pelo site e quero saber mais sobre a mentoria.',
        'after_lead_message' => 'Olá, Marina! Sou {nome}, acabei de enviar meu contato pelo site.',
    ],

    'lgpd' => [
        'consent_text' => 'Autorizo o uso dos meus dados para contato sobre a mentoria, conforme a Política de Privacidade.',
        'policy_url' => '#',
        'footer_notice' => 'Seus dados são usados apenas para contato sobre a mentoria.',
    ],

    'social' => [
        ['label' => 'Instagram', 'url' => 'https://instagram.com/'],
        ['label' => 'LinkedIn', 'url' => 'https://linkedin.com/'],
    ],
];
