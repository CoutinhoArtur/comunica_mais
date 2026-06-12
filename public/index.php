<?php

// =================================================================================
// ARQUIVO: public/index.php
//
// Página inicial pública da clínica Comunica+.
//
// Objetivos:
// - Conectar ao banco de dados
// - Buscar os profissionais cadastrados
// - Exibir informações institucionais
// - Mostrar especialistas registrados
// - Permitir acesso à área de login
// =================================================================================


// -----------------------------------------------------------------------------
// CONFIGURAÇÃO DE ERROS
// -----------------------------------------------------------------------------

// Exibe erros durante a execução do PHP
ini_set('display_errors', 1);

// Exibe erros que acontecem durante a inicialização do PHP
ini_set('display_startup_errors', 1);

// Exibe TODOS os tipos de erros
error_reporting(E_ALL);


// -----------------------------------------------------------------------------
// CONEXÃO COM BANCO DE DADOS
// -----------------------------------------------------------------------------

// Importa o arquivo responsável pela conexão com o banco.
//
// require_once:
// - Carrega apenas uma vez
// - Se não encontrar o arquivo, interrompe o sistema
require_once '../config/db.php';


// -----------------------------------------------------------------------------
// VARIÁVEL QUE ARMAZENARÁ OS PROFISSIONAIS
// -----------------------------------------------------------------------------

$profissionais = [];


// -----------------------------------------------------------------------------
// BUSCA DOS PROFISSIONAIS CADASTRADOS
// -----------------------------------------------------------------------------

try {

    // Consulta SQL
    //
    // Busca:
    // - nome
    // - email
    // - especialidade
    // - registro profissional
    // - mini currículo
    //
    // Apenas usuários do tipo "medico"
    //
    // ORDER BY nome ASC
    // Ordena alfabeticamente (A → Z)

    $sql = "
        SELECT
            nome,
            email,
            especialidade,
            registro_profissional,
            mini_curriculo
        FROM usuarios
        WHERE tipo = 'medico'
        ORDER BY nome ASC
    ";

    // Executa a consulta SQL
    $stmt = $pdo->query($sql);

    // Busca todos os resultados encontrados
    //
    // PDO::FETCH_OBJ transforma cada linha em objeto
    //
    // Exemplo:
    // $prof->nome
    // $prof->email
    // $prof->especialidade

    $profissionais = $stmt->fetchAll(PDO::FETCH_OBJ);

}

// Caso ocorra algum erro no banco
catch (PDOException $e) {

    // Guarda a mensagem de erro
    $erro_banco = $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="pt-pt">

<head>

    <!-- Permite caracteres especiais -->
    <meta charset="UTF-8">

    <!-- Responsividade para celulares e tablets -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- Nome exibido na aba do navegador -->
    <title>Comunica+ | Clínica de Fonoaudiologia</title>


    <!-- ============================================================= -->
    <!-- BOOTSTRAP -->
    <!-- ============================================================= -->

    <!-- Biblioteca CSS do Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">


    <!-- ============================================================= -->
    <!-- FONT AWESOME -->
    <!-- ============================================================= -->

    <!-- Biblioteca de ícones -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">


    <!-- ============================================================= -->
    <!-- CSS INTERNO -->
    <!-- ============================================================= -->

    <style>

        /* ========================================================= */
        /* VARIÁVEIS GLOBAIS DE CORES */
        /* ========================================================= */

        :root {

            /* Verde principal */
            --verde-escuro: #1b4d3e;

            /* Verde intermediário */
            --verde-medio: #2c7a5f;

            /* Verde muito claro */
            --verde-claro: #f4f9f4;

            /* Cor padrão dos textos */
            --texto-escuro: #212529;
        }


        /* ========================================================= */
        /* CONFIGURAÇÕES GERAIS DA PÁGINA */
        /* ========================================================= */

        body {

            /* Fonte principal */
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;

            /* Cor padrão do texto */
            color: var(--texto-escuro);

            /* Fundo branco */
            background-color: #ffffff;
        }


        /* ========================================================= */
        /* NAVBAR */
        /* ========================================================= */

        .navbar {

            /* Cor de fundo da navbar */
            background-color: var(--verde-escuro) !important;
        }

        .navbar-brand,
        .nav-link {

            /* Texto branco */
            color: #ffffff !important;
        }

        .nav-link:hover {

            /* Cor ao passar o mouse */
            color: #b5e2b5 !important;
        }


        /* ========================================================= */
        /* BANNER PRINCIPAL */
        /* ========================================================= */

        .carousel-item-hero {

            /* Cor reserva caso a imagem não carregue */
            background-color: var(--verde-escuro);

            /* Imagem de fundo */
            background-image: url('../assets/img/hero-bg.jpg');

            /* Faz a imagem preencher todo o espaço */
            background-size: cover;

            /* Centraliza a imagem */
            background-position: center;

            /* Altura mínima */
            min-height: 500px;
        }


        /* Camada escura sobre a imagem */
        .hero-overlay {

            background:
                linear-gradient(
                    135deg,
                    rgba(27, 77, 62, 0.95),
                    rgba(44, 122, 95, 0.9)
                );

            min-height: 500px;

            display: flex;

            align-items: center;

            padding: 60px 0;

            width: 100%;
        }


        /* ========================================================= */
        /* BOTÃO PERSONALIZADO */
        /* ========================================================= */

        .btn-custom {

            background-color: var(--verde-medio);

            color: #ffffff;

            border: none;

            padding: 12px 35px;

            font-weight: 600;

            border-radius: 5px;

            transition: 0.3s;
        }

        .btn-custom:hover {

            background-color: #12352b;

            color: #ffffff;

            transform: translateY(-2px);
        }

                /* ========================================================= */
        /* CARDS DAS ÁREAS DE ATUAÇÃO */
        /* ========================================================= */

        .card-atuacao {

            /* Remove borda padrão */
            border: none;

            /* Sombra leve */
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);

            /* Animação suave */
            transition: 0.3s;

            /* Fundo branco */
            background: #ffffff;

            /* Bordas arredondadas */
            border-radius: 8px;
        }

        .card-atuacao:hover {

            /* Efeito ao passar o mouse */
            transform: translateY(-5px);

            /* Sombra mais forte */
            box-shadow: 0 8px 25px rgba(44,122,95,0.15);
        }

        .icon-box {

            /* Cor dos ícones */
            color: var(--verde-medio);

            /* Tamanho do ícone */
            font-size: 2.5rem;

            margin-bottom: 15px;
        }


        /* ========================================================= */
        /* CONFIGURAÇÃO DO CARROSSEL */
        /* ========================================================= */

        .carousel-item-hero {

            /* Altura proporcional à tela */
            height: 75vh;

            /* Altura mínima */
            min-height: 500px;

            background-size: cover;

            background-position: center;

            background-repeat: no-repeat;

            position: relative;
        }


        /* ========================================================= */
        /* IMAGEM DO PRIMEIRO SLIDE */
        /* ========================================================= */

        .slide-vocal {

            background-image:
                url('../assets/img/slide1.jpg');
        }


        /* ========================================================= */
        /* IMAGEM DO SEGUNDO SLIDE */
        /* ========================================================= */

        .slide-homecare {

            background-image:
                url('../assets/img/slide2.jpg');
        }


        /* ========================================================= */
        /* CAMADA ESCURA SOBRE O BANNER */
        /* ========================================================= */

        .hero-overlay {

            position: absolute;

            top: 0;

            left: 0;

            width: 100%;

            height: 100%;

            background:
                linear-gradient(
                    to right,
                    rgba(0,0,0,0.85) 30%,
                    rgba(0,0,0,0.4) 100%
                );
        }


        /* ========================================================= */
        /* SOMBRA NOS TÍTULOS DO BANNER */
        /* ========================================================= */

        .carousel-item-hero h1 {

            text-shadow:
                2px 2px 4px rgba(0,0,0,0.6);
        }


        .carousel-item-hero p {

            color:
                rgba(255,255,255,0.85) !important;
        }


        /* ========================================================= */
        /* CARD DOS PROFISSIONAIS */
        /* ========================================================= */

        .medico-card {

            border:
                1px solid rgba(0,0,0,0.08);

            border-radius: 12px;

            background: #ffffff;

            box-shadow:
                0 6px 18px rgba(0,0,0,0.03);

            transition: 0.3s;

            overflow: hidden;
        }

        .medico-card:hover {

            box-shadow:
                0 10px 30px rgba(44,122,95,0.12);
        }


        /* Cabeçalho do card */
        .medico-header-info {

            background-color:
                var(--verde-claro);

            padding: 25px;

            border-bottom:
                1px solid rgba(0,0,0,0.04);
        }


        /* Badge CRFa */
        .medico-badge-crfa {

            font-size: 11px;

            background-color: #e3ece7;

            color: var(--verde-escuro);

            padding: 4px 10px;

            border-radius: 4px;

            font-weight: 700;

            display: inline-block;
        }


        /* Área do mini currículo */
        .medico-body-curriculo {

            padding: 25px;

            font-size: 14px;

            line-height: 1.6;

            color: #4a5568;

            text-align: justify;
        }


        /* ========================================================= */
        /* BOTÃO FLUTUANTE WHATSAPP */
        /* ========================================================= */

        .whatsapp-float {

            position: fixed;

            bottom: 40px;

            right: 40px;

            background-color: #25d366;

            color: white;

            border-radius: 50px;

            text-align: center;

            font-size: 30px;

            box-shadow:
                2px 2px 10px rgba(0,0,0,0.2);

            z-index: 1000;

            width: 60px;

            height: 60px;

            line-height: 60px;

            transition: 0.3s;
        }

        .whatsapp-float:hover {

            transform: scale(1.1);

            color: white;
        }


        /* ========================================================= */
        /* RODAPÉ */
        /* ========================================================= */

        footer {

            background-color:
                var(--verde-escuro);

            color: #ffffff;

            padding: 40px 0 20px 0;
        }

    </style>
</head>

<body>

    <!-- ========================================================== -->
<!-- MENU DE NAVEGAÇÃO -->
<!-- ========================================================== -->

<nav class="navbar navbar-expand-lg navbar-dark sticky-top">

    <div class="container">

        <!-- Logo da clínica -->
        <a class="navbar-brand fw-bold" href="#">

            <!-- Ícone -->
            <i class="fa-solid fa-waveform-lines me-2"></i>

            Comunica+

        </a>

        <!-- Botão menu hamburguer para celular -->
        <button
            class="navbar-toggler"
            type="button"
            data-bs-toggle="collapse"
            data-bs-target="#navbarNav">

            <span class="navbar-toggler-icon"></span>

        </button>


        <!-- Área que será recolhida em telas menores -->
        <div class="collapse navbar-collapse" id="navbarNav">

            <ul class="navbar-nav ms-auto align-items-center">

                <!-- Link para seção início -->
                <li class="nav-item">
                    <a class="nav-link active" href="#inicio">
                        Início
                    </a>
                </li>

                <!-- Link para áreas de atuação -->
                <li class="nav-item">
                    <a class="nav-link" href="#atuacao">
                        Áreas de Atuação
                    </a>
                </li>

                <!-- Link para profissionais -->
                <li class="nav-item">
                    <a class="nav-link" href="#equipa">
                        Corpo Clínico
                    </a>
                </li>

                <!-- Link para avaliações -->
                <li class="nav-item">
                    <a class="nav-link" href="#depoimentos">
                        Avaliações
                    </a>
                </li>

                <!-- Botão login -->
                <li class="nav-item ms-2">

                    <a
                        class="btn btn-outline-light px-4"
                        href="login.php">

                        Entrar

                    </a>

                </li>

            </ul>

        </div>

    </div>

</nav>

<!-- ========================================================== -->
<!-- SEÇÃO INICIAL DA PÁGINA -->
<!-- ========================================================== -->

<section id="inicio">

    <!--
        Carrossel Bootstrap

        data-bs-ride="carousel"
        => inicia automaticamente

        data-bs-interval="5000"
        => troca slide a cada 5 segundos
    -->
    <div
        id="heroCarousel"
        class="carousel slide"
        data-bs-ride="carousel"
        data-bs-interval="5000">

        <!-- Área interna do carrossel -->
        <div class="carousel-inner">

            <!-- ================================================== -->
            <!-- SLIDE 1 -->
            <!-- ================================================== -->

            <div class="carousel-item active">

                <!--
                    Classe personalizada do slide

                    slide-vocal
                    => usa a imagem slide1.jpg
                -->
                <div class="carousel-item-hero slide-vocal">

                    <!-- Camada escura sobre a imagem -->
                    <div class="hero-overlay d-flex align-items-center">

                        <div class="container">

                            <div class="row">

                                <!-- Área do conteúdo -->
                                <div class="col-lg-7 col-md-9">

                                    <!-- Selo -->
                                    <span
                                        class="badge bg-success bg-opacity-25 text-white px-3 py-2 rounded mb-3 text-uppercase fw-bold"
                                        style="letter-spacing: 1px;">

                                        Alta Performance Vocal

                                    </span>

                                    <!-- Título principal -->
                                    <h1 class="display-4 fw-bold mb-3 text-white">

                                        Sua voz e comunicação
                                        em perfeita harmonia

                                    </h1>

                                    <!-- Texto descritivo -->
                                    <p class="lead mb-4 text-white-50">

                                        Especialistas em
                                        Fonoaudiologia Clínica
                                        e Saúde Vocal para Músicos.

                                        Desenvolvemos treinos
                                        sob medida para que
                                        sua arte e voz nunca parem.

                                    </p>

                                    <!-- Botão -->
                                    <a
                                        href="login.php"
                                        class="btn btn-success btn-lg px-4 shadow-sm fw-bold">

                                        Agendar Consulta

                                    </a>

                                </div>

                            </div>

                        </div>

                    </div>

                </div>

            </div>


            <!-- ================================================== -->
            <!-- SLIDE 2 -->
            <!-- ================================================== -->

            <div class="carousel-item">

                <!--
                    slide-homecare
                    => usa slide2.jpg
                -->
                <div class="carousel-item-hero slide-homecare">

                    <div class="hero-overlay d-flex align-items-center">

                        <div class="container">

                            <div class="row">

                                <div class="col-lg-7 col-md-9">

                                    <!-- Badge -->
                                    <span
                                        class="badge bg-success bg-opacity-25 text-white px-3 py-2 rounded mb-3 text-uppercase fw-bold"
                                        style="letter-spacing: 1px;">

                                        Tecnologia & Saúde

                                    </span>

                                    <!-- Título -->
                                    <h1 class="display-4 fw-bold mb-3 text-white">

                                        Acompanhamento Clínico
                                        e Exercícios Home Care

                                    </h1>

                                    <!-- Descrição -->
                                    <p class="lead mb-4 text-white-50">

                                        Monitore sua evolução
                                        clínica a cada consulta
                                        e receba treinos
                                        fonocomunicativos personalizados.

                                    </p>

                                    <!-- Botão -->
                                    <a
                                        href="login.php"
                                        class="btn btn-light text-success fw-bold btn-lg px-4 shadow-sm">

                                        Acessar Meu Painel

                                    </a>

                                </div>

                            </div>

                        </div>

                    </div>

                </div>

            </div>

        </div>


        <!-- ================================================== -->
        <!-- BOTÃO ANTERIOR -->
        <!-- ================================================== -->

        <button
            class="carousel-control-prev"
            type="button"
            data-bs-target="#heroCarousel"
            data-bs-slide="prev">

            <span
                class="carousel-control-prev-icon"
                aria-hidden="true">
            </span>

        </button>


        <!-- ================================================== -->
        <!-- BOTÃO PRÓXIMO -->
        <!-- ================================================== -->

        <button
            class="carousel-control-next"
            type="button"
            data-bs-target="#heroCarousel"
            data-bs-slide="next">

            <span
                class="carousel-control-next-icon"
                aria-hidden="true">
            </span>

        </button>

    </div>

</section>

    <section id="atuacao" class="py-5">
        <div class="container py-3">
            <div class="text-center mb-5">
                <h2 class="fw-bold" style="color: var(--verde-escuro)">Como Podemos Ajudar?</h2>
                <p class="text-muted">Soluções fonoaudiológicas personalizadas para cada perfil de paciente.</p>
            </div>
            <div class="row g-4">
                <div class="col-md-4">
                    <div class="card card-atuacao h-100 p-4 text-center">
                        <div class="icon-box"><i class="fa-solid fa-music"></i></div>
                        <h4 class="fw-bold mb-3">Saúde Vocal para Cantores</h4>
                        <p class="text-muted mb-0">Aquecimento, desaquecimento, reabilitação e aperfeiçoamento da voz cantada para profissionais da música.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card card-atuacao h-100 p-4 text-center">
                        <div class="icon-box"><i class="fa-solid fa-comments"></i></div>
                        <h4 class="fw-bold mb-3">Dificuldades na Fala</h4>
                        <p class="text-muted mb-0">Tratamento de trocas de sons, gaguez, atrasos de linguagem e reabilitação pós-traumas.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card card-atuacao h-100 p-4 text-center">
                        <div class="icon-box"><i class="fa-solid fa-wheelchair-move"></i></div>
                        <h4 class="fw-bold mb-3">Atendimento PCD</h4>
                        <p class="text-muted mb-0">Acompanhamento fonoaudiológico especializado e inclusivo para Pessoas com Deficiência e Espectro Autista.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>


        <!-- SEÇÃO PROFISSIONAIS -->
    <section id="equipa" class="py-5 bg-light-custom border-top">
    <div class="container py-3">
        
        <!-- Cabeçalho da Seção -->
        <div class="text-center mb-5">
            <span class="text-success small fw-bold text-uppercase" style="letter-spacing: 1px;">Especialistas Registrados</span>
            <h2 class="fw-bold mt-1" style="color: var(--verde-escuro)">Corpo Clínico e Corpo de Especialistas</h2>
            <p class="text-muted">Conheça as qualificações acadêmicas e os registros dos fonoaudiólogos responsáveis pelo seu tratamento.</p>
        </div>
        
        <div class="row g-4 justify-content-center">
            <?php if (empty($profissionais)): ?>
                <!-- Estado Vazio (Caso não haja médicos) -->
                <div class="col-12 text-center text-muted">
                    <p class="small py-4 bg-white rounded border shadow-sm">
                        <i class="fa-solid fa-user-doctor me-2 text-warning"></i>Nenhum terapeuta listado no sistema no momento.
                    </p>
                </div>
            <?php else: ?>
                <?php foreach ($profissionais as $prof): 
    // 1. Pegamos o nome limpo do médico (Remove Dr. ou Dra. e espaços extras)
    $nome_limpo = trim(str_replace(['Dr.', 'Dra.', 'Dr', 'Dra'], '', $prof->nome));
    
    // 2. Caminho baseado no NOME do arquivo (Ex: "../assets/img/medicos/Roberto Almeida.jpg")
    $caminho_foto = "../assets/img/medicos/" . $nome_limpo . ".jpg";
    $foto_perfil = false;

    // 3. O PHP valida se o arquivo com esse nome existe na pasta
    if (file_exists($caminho_foto)) {
        $foto_perfil = $caminho_foto;
    }
?>
                    <div class="col-lg-6 col-md-12">
                        <!-- Card do Médico -->
                        <div class="medico-card h-100 d-flex flex-column justify-content-between bg-white rounded-4 shadow-sm overflow-hidden border-0" style="transition: transform 0.3s ease, box-shadow 0.3s ease;">
                            
                            <div class="p-4">
                                <div class="d-flex align-items-center gap-3 flex-wrap flex-sm-nowrap">
                                    
                                    <!-- ÁREA DA FOTO / AVATAR -->
                                    <div class="medico-foto-container flex-shrink-0">
                                        <?php if ($foto_perfil): ?>
                                            <!-- Mostra a foto real (ex: 7.jpg) -->
                                            <img src="<?= $foto_perfil ?>" 
                                                 alt="<?= htmlspecialchars($prof->nome) ?>" 
                                                 class="rounded-circle object-fit-cover shadow-sm border border-2 border-white" 
                                                 style="width: 80px; height: 80px;">
                                        <?php else: ?>
                                            <!-- Mostra o círculo com as iniciais se não tiver foto -->
                                            <div class="rounded-circle d-flex align-items-center justify-content-center text-white fw-bold shadow-sm" 
                                                 style="width: 80px; height: 80px; background: linear-gradient(135deg, #1b4d3e, #2c7a5f); font-size: 1.5rem;">
                                                 <?php 
                                                    $nomes = explode(' ', trim(str_replace(['Dr.', 'Dra.', 'Dr', 'Dra'], '', $prof->nome)));
                                                    echo strtoupper(substr($nomes[0], 0, 1) . (isset($nomes[1]) ? substr($nomes[1], 0, 1) : ''));
                                                 ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>

                                    <!-- INFORMAÇÕES DO MÉDICO -->
                                    <div class="flex-grow-1">
                                        <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
                                            <div>
                                                <h4 class="fw-bold text-dark mb-1 fs-5">
                                                    <?= htmlspecialchars($prof->nome) ?>
                                                </h4>
                                                <span class="text-success fw-semibold small d-block mb-2">
                                                    <i class="fa-solid fa-graduation-cap me-1"></i> 
                                                    <?= htmlspecialchars($prof->especialidade ?? 'Fonoaudiologia Geral') ?>
                                                </span>
                                            </div>
                                            <!-- Badge do CRFa -->
                                            <div class="badge rounded-pill bg-light text-dark border px-3 py-2 small shadow-none fw-semibold">
                                                <i class="fa-solid fa-id-card text-secondary me-1"></i> 
                                                <?= htmlspecialchars($prof->registro_professional ?? 'CRFa Região') ?>
                                            </div>
                                        </div>
                                    </div>
                                    
                                </div>

                                <!-- MINI CURRÍCULO -->
                                <div class="medico-body-curriculo mt-3 pt-3 border-top border-light">
                                    <strong class="text-dark d-block mb-1 small text-uppercase fw-bold" style="letter-spacing: 0.5px; font-size: 0.75rem;">Resumo Profissional & Formação:</strong>
                                    <p class="m-0 text-secondary small lh-base">
                                        <?php 
                                            // Correção cirúrgica: trocado de mini_curriculo para mini_curric
                                            echo htmlspecialchars($prof->mini_curriculo ?? 'Profissional de fonoaudiologia qualificado.'); 
                                        ?>
                                    </p>
                                </div>
                            </div>

                            <!-- RODAPÉ DO CARD -->
                            <div class="p-3 bg-light border-top d-flex align-items-center justify-content-between flex-wrap gap-2">
                                <span class="text-muted small" style="font-size: 12px;">
                                    <i class="fa-solid fa-envelope me-1"></i> <?= htmlspecialchars($prof->email) ?>
                                </span>
                                <a href="login.php" class="btn btn-sm px-4 fw-semibold py-2 shadow-sm text-white" style="font-size: 13px; border-radius: 8px; background-color: #1b4d3e;">
                                    <i class="fa-solid fa-calendar-check me-1"></i> Solicitar Agendamento
                                </a>
                            </div>

                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</section>

    <section id="depoimentos" class="py-5 bg-white">
        <div class="container py-3">
            <div class="text-center mb-5">
                <h2 class="fw-bold" style="color: var(--verde-escuro)">O que dizem os nossos clientes</h2>
            </div>
            <div id="carouselDepoimentos" class="carousel slide" data-bs-ride="carousel">
                <div class="carousel-inner text-center pb-4">
                    <div class="carousel-item active">
                        <div class="row justify-content-center">
                            <div class="col-md-8">
                                <i class="fa-solid fa-quote-left text-success mb-3 fs-3"></i>
                                <p class="fs-5 italic">"O atendimento especializado em voz cantada salvou a minha tour. Consegui ajustar a minha técnica vocal e evitar a fadiga que sentia nos concertos."</p>
                                <h5 class="fw-bold mt-3 text-success">Lucas Silva - Cantor Profissional</h5>
                            </div>
                        </div>
                    </div>
                    <div class="carousel-item">
                        <div class="row justify-content-center">
                            <div class="col-md-8">
                                <i class="fa-solid fa-quote-left text-success mb-3 fs-3"></i>
                                <p class="fs-5">"O carinho e a paciência no atendimento ao meu filho autista foram incríveis. A evolução dele na comunicação em poucos meses é visível."</p>
                                <h5 class="fw-bold mt-3 text-success">Mariana Costa - Mãe do Pedro</h5>
                            </div>
                        </div>
                    </div>
                </div>
                <button class="carousel-control-prev" type="button" data-bs-target="#carouselDepoimentos" data-bs-slide="prev">
                    <span class="carousel-control-prev-icon bg-secondary rounded-circle" aria-hidden="true"></span>
                </button>
                <button class="carousel-control-next" type="button" data-bs-target="#carouselDepoimentos" data-bs-slide="next">
                    <span class="carousel-control-next-icon bg-secondary rounded-circle" aria-hidden="true"></span>
                </button>
            </div>
        </div>
    </section>

    <a href="https://wa.me/99999999999?text=Olá! Gostaria de mais informações sobre os agendamentos na Comunica+" class="whatsapp-float" target="_blank" aria-label="Contacto via WhatsApp">
        <i class="fa-brands fa-whatsapp"></i>
    </a>

    <footer class="text-center text-md-start">
        <div class="container">
            <div class="row g-4">
                <div class="col-md-6">
                    <h5 class="fw-bold mb-3"><i class="fa-solid fa-waveform-lines me-2"></i>Clínica Comunica+</h5>
                    <p><i class="fa-solid fa-location-dot me-2"></i> Av. Paulista, 1000 - Bela Vista, São Paulo - SP</p>
                    <p><i class="fa-solid fa-phone me-2"></i> (11) 4002-8922</p>
                    <p><i class="fa-solid fa-envelope me-2"></i> contacto@comunicamais.com.br</p>
                </div>
                <div class="col-md-6">
                    <h5 class="fw-bold mb-3">Onde Estamos</h5>
                    <div class="ratio ratio-21x9 rounded overflow-hidden" style="max-height: 150px;">
                        <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3657.1975424784946!2d-46.654668223772274!3d-23.561349661184988!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x94ce59c8da0aa315%3A0xd59f9431f2c9776a!2sAv.%20Paulista%2C%201000%20-%20Bela%20Vista%2C%20S%C3%A3o%20Paulo%20-%20SP!5e0!3m2!1spt-PT!2sbr!4v1700000000000!5m2!1spt-PT!2sbr" allowfullscreen="" loading="lazy"></iframe>
                    </div>
                </div>
            </div>
            <hr class="mt-4 bg-light">
            <div class="text-center text-muted fs-7">
                <p class="mb-0">© 2026 Comunica+. Projeto Académico SENAI. Todos os direitos reservados.</p>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>