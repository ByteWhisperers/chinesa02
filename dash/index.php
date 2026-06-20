<?php include 'partials/html.php' ?>

<?php
#======================================#
ini_set('display_errors', 0);
error_reporting(E_ALL);
#======================================#
session_start();
include_once "services/database.php";
include_once 'logs/registrar_logs.php';
include_once "services/funcao.php";
include_once "services/crud.php";
include_once "services/crud-adm.php";
include_once 'services/checa_login_adm.php';
include_once "services/CSRF_Protect.php";
$csrf = new CSRF_Protect();
#======================================#
#expulsa user
checa_login_adm();
#======================================#
//inicio do scriot expulsa usuario bloqueado
if ($_SESSION['data_adm']['status'] != '1') {
    echo "<script>setTimeout(function() { window.location.href = 'bloqueado.php'; }, 0);</script>";
    exit();
}

// Pegar os dados para os gráficos de PHP
$depositos_dias = depositos_por_dia();
$saques_dias = saques_por_dia();

// Formatar os dados para uso em JavaScript
$labels_depositos = json_encode(array_column($depositos_dias, 'dia'));
$dados_depositos = json_encode(array_column($depositos_dias, 'total'));

$labels_saques = json_encode(array_column($saques_dias, 'dia'));
$dados_saques = json_encode(array_column($saques_dias, 'total'));
/// final do script --#
?>

<head>
    <?php $title = "ADMIN 7K";
    include 'partials/title-meta.php' ?>

    <link rel="stylesheet" href="assets/libs/jsvectormap/jsvectormap.min.css">

    <?php include 'partials/head-css.php' ?>
    <style>
        .dtx-community {
            width: 100%;
            margin: 0 0 24px;
        }

        .dtx-community__inner {
            max-width: 960px;
            margin: 0 auto;
            padding: 10px 16px 4px;
            text-align: center;
        }

        .dtx-community__subtitle {
            margin: 0 0 20px;
            font-family: "Arial Black", Arial, sans-serif;
            font-size: clamp(1.1rem, 1.6vw + 0.9rem, 1.8rem);
            line-height: 1.35;
            color: #1b1b1b;
            background: linear-gradient(90deg, #1b1b1b 0%, #2a1b6d 45%, #1b1b1b 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .dtx-community__btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 12px 28px;
            border-radius: 999px;
            background: linear-gradient(90deg, #5b2cff 0%, #287bff 100%);
            color: #ffffff;
            font-weight: 700;
            text-decoration: none;
            box-shadow: 0 8px 18px rgba(43, 37, 122, 0.18);
            transition: filter 0.2s ease, transform 0.2s ease;
        }

        .dtx-community__btn:hover {
            filter: brightness(0.92);
            transform: translateY(-1px);
            color: #ffffff;
        }

        .dtx-community__btn:focus-visible {
            outline: 3px solid rgba(40, 123, 255, 0.35);
            outline-offset: 3px;
        }
    </style>
</head>

<body>

    <!-- Top Bar Start -->
    <?php include 'partials/topbar.php' ?>
    <!-- Top Bar End -->
    <!-- leftbar-tab-menu -->
    <?php include 'partials/startbar.php' ?>
    <!-- end leftbar-tab-menu-->

    <div class="page-wrapper">

        <!-- Page Content-->
        <div class="page-content">
            <div class="container-xxl">
                <div class="row justify-content-center">
                    <div style="padding-left: 20px;padding-top: 10px;padding-bottom: 20px;">
                        <h1>DTXSISTEMAS INFORMA A HORA</h1>
                        <p>Hora atual: <span id="hora">Carregando...</span></p>
                    </div>
                    <section class="dtx-community" aria-label="Comunidade DTX Sistemas">
                        <div class="dtx-community__inner">
                            <p class="dtx-community__subtitle">
                                A DTX Sistemas criou este sistema para você. Para ter acesso a mais sistemas, estratégias
                                validadas, criativos prontos e network com pessoas que já faturam nesse mercado, entre agora
                                na nossa comunidade.
                            </p>
                            <a class="dtx-community__btn" href="https://links.dtxsistemas.com" target="_blank"
                                rel="noopener noreferrer">
                                Entrar na comunidade
                            </a>
                        </div>
                    </section>
                    <!-- Seção de Cadastros -->
                    <div class="row">
                        <h5 class="col-12 mb-3 d-flex align-items-center">
                            <div class="tag"></div>
                            Cadastros
                        </h5>

                        <div class="col-md-6 col-lg-4">
                            <div class="card">
                                <div class="card-body">
                                    <div class="row d-flex justify-content-center border-dashed-bottom pb-3">
                                        <div class="col-9">
                                            <p class="text-dark mb-0 fw-semibold fs-14">Total cadastros</p>
                                            <h3 class="mt-2 mb-0 fw-bold"><?= qtd_usuarios(); ?></h3>
                                        </div>
                                        <div class="col-3 align-self-center">
                                            <div
                                                class="d-flex justify-content-center align-items-center thumb-xl bg-light rounded-circle mx-auto">
                                                <i class="iconoir-group h1 align-self-center mb-0 text-success"></i>
                                            </div>
                                        </div>
                                    </div>
                                    <p class="mb-0 text-truncate text-muted mt-3"><span class="text-warning">Total de
                                            cadastros da operação.</span></p>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6 col-lg-4">
                            <div class="card">
                                <div class="card-body">
                                    <div class="row d-flex justify-content-center border-dashed-bottom pb-3">
                                        <div class="col-9">
                                            <p class="text-dark mb-0 fw-semibold fs-14">Cadastros hoje</p>
                                            <h3 class="mt-2 mb-0 fw-bold"><?= qtd_usuarios_diarios(); ?></h3>
                                        </div>
                                        <div class="col-3 align-self-center">
                                            <div
                                                class="d-flex justify-content-center align-items-center thumb-xl bg-light rounded-circle mx-auto">
                                                <i class="iconoir-user h1 align-self-center mb-0 text-success"></i>
                                            </div>
                                        </div>
                                    </div>
                                    <p class="mb-0 text-truncate text-muted mt-3"><span class="text-warning">Novos
                                            cadastros realizados hoje.</span></p>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6 col-lg-4">
                            <div class="card">
                                <div class="card-body">
                                    <div class="row d-flex justify-content-center border-dashed-bottom pb-3">
                                        <div class="col-9">
                                            <p class="text-dark mb-0 fw-semibold fs-14">Cadastros 90D</p>
                                            <h3 class="mt-2 mb-0 fw-bold"><?= qtd_usuarios_ultimos_90_dias(); ?></h3>
                                        </div>
                                        <div class="col-3 align-self-center">
                                            <div
                                                class="d-flex justify-content-center align-items-center thumb-xl bg-light rounded-circle mx-auto">
                                                <i class="iconoir-user h1 align-self-center mb-0 text-success"></i>
                                            </div>
                                        </div>
                                    </div>
                                    <p class="mb-0 text-truncate text-muted mt-3"><span class="text-warning">Novos
                                            cadastros nos últimos 90 dias.</span></p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Seção Financeiro -->
                    <div class="row mt-4">
                        <h5 class="col-12 mb-3 d-flex align-items-center">
                            <div class="tag"></div>
                            Financeiro
                        </h5>

                        <div class="col-md-6 col-lg-4">
                            <div class="card">
                                <div class="card-body">
                                    <div class="row d-flex justify-content-center border-dashed-bottom pb-3">
                                        <div class="col-9">
                                            <p class="text-dark mb-0 fw-semibold fs-14">Depositos Total</p>
                                            <h3 class="mt-2 mb-0 fw-bold">R$<?= Reais2(depositos_total()); ?></h3>
                                        </div>
                                        <div class="col-3 align-self-center">
                                            <div
                                                class="d-flex justify-content-center align-items-center thumb-xl bg-light rounded-circle mx-auto">
                                                <i
                                                    class="iconoir-check-circle h1 align-self-center mb-0 text-success"></i>
                                            </div>
                                        </div>
                                    </div>
                                    <p class="mb-0 text-truncate text-muted mt-3"><span class="text-warning">Depositos Total da PLataforma</span></p>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6 col-lg-4">
                            <div class="card">
                                <div class="card-body">
                                    <div class="row d-flex justify-content-center border-dashed-bottom pb-3">
                                        <div class="col-9">
                                            <p class="text-dark mb-0 fw-semibold fs-14">Lucro cassino</p>
                                            <h3 class="mt-2 mb-0 fw-bold">R$<?= Reais2(saldo_cassino()); ?></h3>
                                        </div>
                                        <div class="col-3 align-self-center">
                                            <div
                                                class="d-flex justify-content-center align-items-center thumb-xl bg-light rounded-circle mx-auto">
                                                <i
                                                    class="iconoir-check-circle h1 align-self-center mb-0 text-danger"></i>
                                            </div>
                                        </div>
                                    </div>
                                    <p class="mb-0 text-truncate text-muted mt-3"><span class="text-warning">Lucro da
                                            operação (Dep - Saques).</span></p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 col-lg-4">
                            <div class="card">
                                <div class="card-body">
                                    <div class="row d-flex justify-content-center border-dashed-bottom pb-3">
                                        <div class="col-9">
                                            <p class="text-dark mb-0 fw-semibold fs-14">Depósitos pagos hoje</p>
                                            <h3 class="mt-2 mb-0 fw-bold"><?= Reais2(depositos_diarios_pagos()); ?></h3>
                                        </div>
                                        <div class="col-3 align-self-center">
                                            <div
                                                class="d-flex justify-content-center align-items-center thumb-xl bg-light rounded-circle mx-auto">
                                                <!-- Ícone de sucesso (check-circle) -->
                                                <i
                                                    class="iconoir-check-circle h1 align-self-center mb-0 text-success"></i>
                                            </div>
                                        </div>
                                    </div>
                                    <p class="mb-0 text-truncate text-muted mt-3"><span class="text-warning">Total de
                                            depósitos pagos hoje</span></p>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6 col-lg-4">
                            <div class="card">
                                <div class="card-body">
                                    <div class="row d-flex justify-content-center border-dashed-bottom pb-3">
                                        <div class="col-9">
                                            <p class="text-dark mb-0 fw-semibold fs-14">Depósitos Gerados</p>
                                            <h3 class="mt-2 mb-0 fw-bold">R$<?= Reais2(depositos_diarios()); ?></h3>
                                        </div>
                                        <div class="col-3 align-self-center">
                                            <div
                                                class="d-flex justify-content-center align-items-center thumb-xl bg-light rounded-circle mx-auto">
                                                <i class="iconoir-hourglass h1 align-self-center mb-0 text-warning"></i>
                                            </div>
                                        </div>
                                    </div>
                                    <p class="mb-0 text-truncate text-muted mt-3"><span class="text-warning">Total de
                                            depósitos processando hoje</span></p>
                                </div>
                            </div>
                        </div>

                        <!--end col-->
                        <div class="col-md-6 col-lg-4">
                            <div class="card">
                                <div class="card-body">
                                    <div class="row d-flex justify-content-center border-dashed-bottom pb-3">
                                        <div class="col-9">
                                            <p class="text-dark mb-0 fw-semibold fs-14">Saques total</p>
                                            <h3 class="mt-2 mb-0 fw-bold">R$<?= Reais2(saques_total()); ?></h3>
                                        </div>
                                        <!--end col-->
                                        <div class="col-3 align-self-center">
                                            <div
                                                class="d-flex justify-content-center align-items-center thumb-xl bg-light rounded-circle mx-auto">
                                                <i
                                                    class="iconoir-check-circle h1 align-self-center mb-0 text-success"></i>
                                            </div>
                                        </div>
                                        <!--end col-->
                                    </div>
                                    <!--end row-->
                                    <p class="mb-0 text-truncate text-muted mt-3"><span class="text-warning">Total de
                                            saques da operação.</span></p>
                                </div>
                                <!--end card-body-->
                            </div>
                            <!--end card-->
                        </div>
                        <!--end col-->
                        <div class="col-md-6 col-lg-4">
                            <div class="card">
                                <div class="card-body">
                                    <div class="row d-flex justify-content-center border-dashed-bottom pb-3">
                                        <div class="col-9">
                                            <p class="text-dark mb-0 fw-semibold fs-14">Saques pagos hoje</p>
                                            <h3 class="mt-2 mb-0 fw-bold">R$<?= Reais2(saques_diarios_pagos()); ?></h3>
                                        </div>
                                        <div class="col-3 align-self-center">
                                            <div
                                                class="d-flex justify-content-center align-items-center thumb-xl bg-light rounded-circle mx-auto">
                                                <i
                                                    class="iconoir-check-circle h1 align-self-center mb-0 text-success"></i>
                                            </div>
                                        </div>
                                    </div>
                                    <p class="mb-0 text-truncate text-muted mt-3"><span class="text-warning">Total de
                                            saques pagos hoje.</span></p>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6 col-lg-4">
                            <div class="card">
                                <div class="card-body">
                                    <div class="row d-flex justify-content-center border-dashed-bottom pb-3">
                                        <div class="col-9">
                                            <p class="text-dark mb-0 fw-semibold fs-14">Saques pendentes hoje</p>
                                            <h3 class="mt-2 mb-0 fw-bold">R$<?= Reais2(saques_diarios_pagos()); ?></h3>
                                        </div>
                                        <div class="col-3 align-self-center">
                                            <div
                                                class="d-flex justify-content-center align-items-center thumb-xl bg-light rounded-circle mx-auto">
                                                <i class="iconoir-hourglass h1 align-self-center mb-0 text-warning"></i>
                                            </div>
                                        </div>
                                    </div>
                                    <p class="mb-0 text-truncate text-muted mt-3"><span class="text-warning">Valor de
                                            saques pendentes hoje.</span></p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <h5 class="col-12 mb-3 d-flex align-items-center">
                            <div class="tag"></div>
                            Visitas
                        </h5>
                        <div class="col-md-6 col-lg-4">
                            <div class="card">
                                <div class="card-body">
                                    <div class="row d-flex justify-content-center border-dashed-bottom pb-3">
                                        <div class="col-9">
                                            <p class="text-dark mb-0 fw-semibold fs-14">Acessos total</p>
                                            <h3 class="mt-2 mb-0 fw-bold"><?= visitas_count('total'); ?></h3>
                                        </div>
                                        <!--end col-->
                                        <div class="col-3 align-self-center">
                                            <div
                                                class="d-flex justify-content-center align-items-center thumb-xl bg-light rounded-circle mx-auto">
                                                <i class="iconoir-user h1 align-self-center mb-0 text-secondary"></i>
                                            </div>
                                        </div>
                                        <!--end col-->
                                    </div>
                                    <!--end row-->
                                    <p class="mb-0 text-truncate text-muted mt-3"><span class="text-danger">Acessos
                                            total:
                                            <?= visitas_count('total'); ?> </span></p>
                                </div>
                                <!--end card-body-->
                            </div>
                            <!--end card-->
                        </div>
                        <div class="col-md-6 col-lg-4">
                            <div class="card">
                                <div class="card-body">
                                    <div class="row d-flex justify-content-center border-dashed-bottom pb-3">
                                        <div class="col-9">
                                            <p class="text-dark mb-0 fw-semibold fs-14">Acessos Hoje</p>
                                            <h3 class="mt-2 mb-0 fw-bold"><?= visitas_count('diario'); ?></h3>
                                        </div>
                                        <!--end col-->
                                        <div class="col-3 align-self-center">
                                            <div
                                                class="d-flex justify-content-center align-items-center thumb-xl bg-light rounded-circle mx-auto">
                                                <i class="iconoir-user h1 align-self-center mb-0 text-secondary"></i>
                                            </div>
                                        </div>
                                        <!--end col-->
                                    </div>
                                    <!--end row-->
                                    <p class="mb-0 text-truncate text-muted mt-3"><span class="text-danger">Acessos
                                            hoje:
                                            <?= visitas_count('diario'); ?> </span></p>
                                </div>
                                <!--end card-body-->
                            </div>
                            <!--end card-->
                        </div>
                        <div class="col-md-6 col-lg-4">
                            <div class="card">
                                <div class="card-body">
                                    <div class="row d-flex justify-content-center border-dashed-bottom pb-3">
                                        <div class="col-9">
                                            <p class="text-dark mb-0 fw-semibold fs-14">Lugar Mais Acessado</p>
                                            <?php
                                            // Chamada da função
                                            $lugar_mais_acessado = visitas_count2('total');
                                            ?>
                                            <h3 class="mt-2 mb-0 fw-bold">
                                                <?php
                                                // Verifica se há dados
                                                if ($lugar_mais_acessado['cidade'] && $lugar_mais_acessado['estado']) {
                                                    echo $lugar_mais_acessado['cidade'] . ', ' . $lugar_mais_acessado['estado'];
                                                } else {
                                                    echo "Não há dados";
                                                }
                                                ?>
                                            </h3>
                                        </div>
                                        <!--end col-->
                                        <div class="col-3 align-self-center">
                                            <div
                                                class="d-flex justify-content-center align-items-center thumb-xl bg-light rounded-circle mx-auto">
                                                <i class="iconoir-user h1 align-self-center mb-0 text-secondary"></i>
                                            </div>
                                        </div>
                                        <!--end col-->
                                    </div>
                                    <!--end row-->
                                    <p class="mb-0 text-truncate text-muted mt-3">
                                        <span class="text-danger">Acessos do lugar mais acessado:
                                            <?= $lugar_mais_acessado['total']; ?></span>
                                    </p>
                                </div>
                                <!--end card-body-->
                            </div>
                            <!--end card-->
                        </div>
                    </div>
                    <!--end col-->
                    <div class="row mt-4">
                        <h5 class="col-12 mb-3 d-flex align-items-center">
                            <div class="tag"></div>
                            Gráficos
                        </h5>
                        <div class="col-lg-6 col-md-6 col-sm-12">
                            <div class="card">
                                <div class="card-body">
                                    <h5 class="card-title">Gráfico de Depósitos por Dia</h5>
                                    <div id="chart-depositos"></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-6 col-md-6 col-sm-12">
                            <div class="card">
                                <div class="card-body">
                                    <h5 class="card-title">Gráfico de Saques por Dia</h5>
                                    <div id="chart-saques"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!--end row-->
                    <div class="row">
                        <h5 class="col-12 mb-3 d-flex align-items-center">
                            <div class="tag"></div>
                            Histórico financeiro
                        </h5>
                        <div class="col-lg-6">
                            <div class="card card-h-100">
                                <div class="card-header">
                                    <div class="row align-items-center">
                                        <div class="col">
                                            <h4 class="card-title">Saques Aprovados</h4>
                                            <p class="fs-11 fst-bold text-muted">Últimos 5 Saques Aprovados.<a href="#!"
                                                    class="link-danger ms-1"><i
                                                        class="align-middle iconoir-refresh"></i></a></p>
                                        </div>
                                    </div>
                                </div>
                                <div class="card-body pt-0">
                                    <div class="table-responsive browser_users">
                                        <table class="table mb-0">
                                            <thead class="table-light">
                                                <tr>
                                                    <th class="border-top-0">Id</th>
                                                    <th class="border-top-0">Usuário</th>
                                                    <th class="border-top-0">Data/Hora</th>
                                                    <th class="border-top-0">Valor</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php
                                                session_start();
                                                include_once '../services/database.php';
                                                include_once '../services/funcao.php';
                                                include_once '../services/crud.php';
                                                include_once '../services/crud-adm.php';
                                                include_once '../services/checa_login_adm.php';
                                                checa_login_adm();

                                                global $mysqli;
                                                $pagina = 1; // Página atual
                                                $qnt_result_pg = 5; // Quantidade de resultados por página
                                                $inicio = ($pagina * $qnt_result_pg) - $qnt_result_pg;

                                                // Consultar saques aprovados no banco de dados
                                                $result_usuario = "SELECT * FROM solicitacao_saques WHERE status=1 ORDER BY id DESC LIMIT $inicio, $qnt_result_pg";
                                                $resultado_usuario = mysqli_query($mysqli, $result_usuario);

                                                if ($resultado_usuario && mysqli_num_rows($resultado_usuario) > 0) {
                                                    while ($data = mysqli_fetch_assoc($resultado_usuario)) {
                                                        $data_return = data_user_id($data['id_user']);
                                                        ?>
                                                        <tr>
                                                            <td><?= $data['id']; ?></td>
                                                            <td><?= $data_return['mobile']; ?></td>
                                                            <td><?= ver_data($data['data_hora']); ?></td>
                                                            <td>R$ <?= Reais2($data['valor']); ?></td>
                                                        </tr>
                                                        <?php
                                                    }
                                                } else {
                                                    echo "<tr><td colspan='4' class='text-center'>Sem dados disponíveis!</td></tr>";
                                                }
                                                ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-6">
                            <div class="card card-h-100">
                                <div class="card-header">
                                    <div class="row align-items-center">
                                        <div class="col">
                                            <h4 class="card-title">Depósitos Pagos</h4>
                                            <p class="fs-11 fst-bold text-muted">Últimos 5 Depósitos Pagos.<a href="#!"
                                                    class="link-danger ms-1"><i
                                                        class="align-middle iconoir-refresh"></i></a></p>
                                        </div>
                                    </div>
                                </div>
                                <div class="card-body pt-0">
                                    <div class="table-responsive">
                                        <table class="table mb-0">
                                            <thead class="table-light">
                                                <tr>
                                                    <th class="border-top-0">Id</th>
                                                    <th class="border-top-0">Usuário</th>
                                                    <th class="border-top-0">Data/Hora</th>
                                                    <th class="border-top-0">Valor</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php
                                                // Consultar total de visitas por canal no banco de dados
                                                $result_visitas = "SELECT * FROM transacoes ORDER BY id DESC LIMIT $inicio, $qnt_result_pg";
                                                $resultado_visitas = mysqli_query($mysqli, $result_visitas);

                                                if ($resultado_visitas && mysqli_num_rows($resultado_visitas) > 0) {
                                                    while ($visit = mysqli_fetch_assoc($resultado_visitas)) {
                                                        $change_icon = ($visit['valor'] >= 20) ? 'fa-caret-up text-success' : 'fa-caret-down text-warning';
                                                        ?>
                                                        <tr>
                                                            <td><?= $visit['id']; ?></td>
                                                            <td><?= $visit['usuario']; ?></td>
                                                            <td><?= $visit['data_hora']; ?></td>
                                                            <td><?= $visit['valor']; ?> <i
                                                                    class="fas <?= $change_icon; ?> font-16"></i>
                                                            </td>
                                                        </tr>
                                                        <?php
                                                    }
                                                } else {
                                                    echo "<tr><td colspan='4' class='text-center'>Sem dados disponíveis!</td></tr>";
                                                }
                                                ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!--end row-->
            </div><!-- container -->

            <!--Start Rightbar-->
            <?php include 'partials/endbar.php' ?>
            <!--end Rightbar-->
            <!--Start Footer-->
            <?php include 'partials/footer.php' ?>
            <!--end footer-->
        </div>
        <!-- end page content -->
    </div>
    <!-- end page-wrapper -->

    <!-- Javascript  -->
    <!-- vendor js -->
    <?php include 'partials/vendorjs.php' ?>

    <script src="assets/libs/apexcharts/apexcharts.min.js"></script>
    <script src="assets/data/stock-prices.js"></script>
    <script src="assets/libs/jsvectormap/jsvectormap.min.js"></script>
    <script src="assets/libs/jsvectormap/maps/world.js"></script>
    <script src="assets/js/pages/index.init.js"></script>
    <script src="assets/js/app.js"></script>


    <script>
        // Usar as variáveis PHP para passar os dados diretamente para o JavaScript
        var labelsDepositos = <?= $labels_depositos; ?>;
        var depositosData = <?= $dados_depositos; ?>;

        var labelsSaques = <?= $labels_saques; ?>;
        var saquesData = <?= $dados_saques; ?>;

        // Gráfico de Depósitos
        var optionsDepositos = {
            series: [{
                name: 'Depósitos',
                data: depositosData
            }],
            chart: {
                type: 'line',
                height: 350
            },
            xaxis: {
                categories: labelsDepositos
            },
            stroke: {
                curve: 'smooth'
            },
            title: {
                text: 'Depósitos Diários',
                align: 'left'
            }
        };

        var chartDepositos = new ApexCharts(document.querySelector("#chart-depositos"), optionsDepositos);
        chartDepositos.render();

        // Gráfico de Saques
        var optionsSaques = {
            series: [{
                name: 'Saques',
                data: saquesData
            }],
            chart: {
                type: 'line',
                height: 350
            },
            xaxis: {
                categories: labelsSaques
            },
            stroke: {
                curve: 'smooth'
            },
            title: {
                text: 'Saques Diários',
                align: 'left'
            }
        };

        var chartSaques = new ApexCharts(document.querySelector("#chart-saques"), optionsSaques);
        chartSaques.render();
    </script>

    <script type="text/javascript">
        // Função para fazer a requisição AJAX e atualizar a hora
        function atualizarHora() {
            var xhr = new XMLHttpRequest(); // Cria um objeto XMLHttpRequest
            xhr.open('GET', 'hora.php', true); // Configura o método GET para o arquivo hora.php

            // Define o que acontece quando a resposta chegar
            xhr.onload = function () {
                if (xhr.status === 200) {
                    // Atualiza o conteúdo da página com a hora recebida do PHP
                    document.getElementById('hora').innerText = xhr.responseText;
                } else {
                    console.error('Erro ao carregar a hora.');
                }
            };

            // Envia a requisição para o servidor
            xhr.send();
        }

        // Chama a função para atualizar a hora a cada 15 segundos (15000 ms)
        setInterval(atualizarHora, 1000);

        // Atualiza a hora imediatamente quando a página for carregada
        window.onload = function () {
            atualizarHora(); // Atualiza a hora assim que a página for carregada
        };
    </script>


</body>
<!--end body-->

</html>
