<?php include 'partials/html.php' ?>

<head>
    <?php $title = "Phillyps";
    include 'partials/title-meta.php' ?>

    <?php include 'partials/head-css.php' ?>
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
                    <div class="col-md-12 col-lg-12">
                        <div class="card">
                            <div class="card-header">
                                <div class="row align-items-center">

                                    <div class="col" style="display: flex;align-content: center;align-items: center;">
                                        <div class="tag"></div>
                                        <h4 class="card-title">Saques Aprovados</h4>
                                    </div><!--end col-->
                                </div> <!--end row-->
                            </div><!--end card-header-->
                            <div class="card-body pt-0">
                                <div class="table-responsive">
                                    <table class="table  mb-0 table-centered">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Id</th>
                                                <th>Nome do usuário</th>
                                                <th>Transação ID</th>
                                                <th>Valor</th>
                                                <th>Data/Hora</th>
                                                <th>CHAVE PIX</th>
                                                <th>CPF CHAVE</th>
                                                <th>TIPO CHAVE PIX</th>
                                                <th>Status</th>

                                                <!-- <th>Comprovante</th> -->
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            global $mysqli;
                                            function identificarTipoChavePix($flag)
                                            {
                                                $tiposChavePix = [
                                                    [
                                                        "displayName" => "CPF",
                                                        "ty" => 3,
                                                        "enable" => true,
                                                        "num" => 1,
                                                    ],
                                                    [
                                                        "displayName" => "PHONE",
                                                        "ty" => 2,
                                                        "enable" => true,
                                                        "num" => 1,
                                                    ],
                                                    [
                                                        "displayName" => "EMAIL",
                                                        "ty" => 1,
                                                        "enable" => true,
                                                        "num" => 1,
                                                    ],
                                                    [
                                                        "displayName" => "CNPJ",
                                                        "ty" => 4,
                                                        "enable" => false,
                                                        "num" => 1,
                                                    ],
                                                ];

                                                foreach ($tiposChavePix as $tipo) {
                                                    if ($tipo['ty'] == $flag) {
                                                        return $tipo['displayName'];
                                                    }
                                                }

                                                return 'invalid';
                                            }
                                            $query_saques = "
                                            SELECT 
                                                ss.*, 
                                                u.mobile 
                                            FROM 
                                                solicitacao_saques ss
                                            JOIN 
                                                usuarios u ON ss.id_user = u.id
                                            WHERE 
                                                ss.status = '1' 
                                            ORDER BY 
                                                ss.id DESC
                                        ";
                                            $result_saques = mysqli_query($mysqli, $query_saques);

                                            if ($result_saques && mysqli_num_rows($result_saques) > 0) {
                                                while ($saque = mysqli_fetch_assoc($result_saques)) {
                                                    // Definir o cargo com base nos dados da tabela (exemplo para afiliado)
                                                    $cargo_badge = ($saque['status'] == '1') ? "<span class='badge bg-success'>Aprovado</span>" : "<span class='badge bg-secondary'>Usuário</span>";
                                                    $chavepixuser = localizarchavepixall($saque['pix']);
                                                    $tipoChavePix = identificarTipoChavePix($chavepixuser['flag']);

                                                    ?>
                                                    <tr>
                                                        <td><?= $saque['id']; ?></td>
                                                        <td><?= $saque['mobile']; ?></td>

                                                        <td><?= $saque['transacao_id']; ?></td>
                                                        <td>R$ <?= number_format($saque['valor'], 2, ',', '.'); ?></td>
                                                        <td><?= $saque['data_cad']; ?> : <?= $saque['data_hora']; ?></td>



                                                        <td><?= $chavepixuser['pix_account']; ?></td>
                                                        <td><?= $chavepixuser['pix_id']; ?></td>

                                                        <td><?= $tipoChavePix; ?></td>

                                                        <td><?= $cargo_badge; ?></td>

                                                        <!-- <td>
                                                            <?php if (!empty($saque['comprovante'])) { ?>
                                                                <a href="download.php?file=<?= urlencode($saque['comprovante']); ?>" class="btn btn-sm btn-success">
                                                                    Baixar
                                                                </a>
                                                            <?php } ?>
                                                        </td> -->

                                                    </tr>
                                                    <?php
                                                }
                                            } else {
                                                echo "<tr><td colspan='7' class='text-center'>Sem dados disponíveis!</td></tr>";
                                            }
                                            ?>
                                        </tbody>
                                    </table><!--end /table-->
                                </div><!--end /tableresponsive-->
                            </div><!--end card-body-->

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

                <script src="assets/js/app.js"></script>
                <script src="assets/libs/clipboard/clipboard.min.js"></script>
                <script src="assets/js/pages/clipboard.init.js"></script>
                <script src="assets/js/app.js"></script>
</body>
<!--end body-->

</html>