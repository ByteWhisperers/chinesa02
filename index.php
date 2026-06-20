<?php
session_start();
include_once("dash/services/database.php");
include_once("dash/services/funcao.php");
include_once("dash/services/crud.php");
include_once("dash/services/CSRF_Protect.php");
include_once("dash/services/pega-ip.php");
include_once("dash/services/ip-crawler.php");
$csrf = new CSRF_Protect();
#==================================================================#
if (isset($_GET['utm_ads']) && !empty($_GET['utm_ads'])) {
  $ads_tipo = PHP_SEGURO($_GET['utm_ads']);
} else {
  $ads_tipo = NULL;
}
#==================================================================#
$url_atual = (isset($_SERVER['HTTPS']) ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
#==================================================================#
// INSERT DE VISITAS NAS LPS
$data_hoje = date("Y-m-d");
$hora_hoje = date("H:i:s");
$ref = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : $url_atual;
#==================================================================#
$data_us = ip_F($ip);
#==================================================================#
if ($browser != "Unknown Browser" && $os != "Unknown OS Platform" && $data_us['pais'] == "Brazil") {
  $id_user_ret = "1";
  $sql0 = $mysqli->prepare("SELECT ip_visita FROM visita_site WHERE data_cad=? AND ip_visita=?");
  $sql0->bind_param("ss", $data_hoje, $ip);
  $sql0->execute();
  $sql0->store_result();
  if (!$sql0->num_rows) {
    $sql = $mysqli->prepare("INSERT INTO visita_site (nav_os,mac_os,ip_visita,refer_visita,data_cad,hora_cad,id_user,pais,cidade,estado,ads_tipo) VALUES (?,?,?,?,?,?,?,?,?,?,?)");
    $sql->bind_param("sssssssssss", $browser, $os, $ip, $ref, $data_hoje, $hora_hoje, $id_user_ret, $data_us['pais'], $data_us['cidade'], $data_us['regiao'], $ads_tipo);
    $sql->execute();
  }
}
#===============================================================================#
?>
<!DOCTYPE html>
<html lang="pt">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1,maximum-scale=1,user-scalable=0,viewport-fit=cover" />
  <title><?= $dataconfig['nome']; ?></title>
  <meta name="theme-color" content="#0ea5e9" />

  <!-- Open Graph / Twitter -->
  <meta property="og:type" content="website" />
  <meta property="og:title" content="<?= htmlspecialchars($dataconfig['nome'] ?? '', ENT_QUOTES) ?>" />
  <meta property="og:description" content="" />
  <meta property="og:image" content="/xxxx/h5/share_image.jpg" />
  <meta property="og:url" content="<?= htmlspecialchars($url_atual, ENT_QUOTES) ?>" />
  <meta name="twitter:card" content="summary_large_image" />

  <!-- Favicons -->
  <link rel="apple-touch-icon" href="/xxxx/h5/favicon.png" />
  <link rel="icon" href="/xxxx/h5/favicon.png" />

  <!-- DNS Prefetch para melhorar conexões -->
  <link rel="dns-prefetch" href="https://accounts.google.com" />
  <link rel="dns-prefetch" href="https://connect.facebook.net" />
  <link rel="preconnect" href="https://accounts.google.com" crossorigin />
  <link rel="preconnect" href="https://connect.facebook.net" crossorigin />

  <!-- CSS principal com prioridade -->
  <link rel="preload" as="style" href="/yq-br-prod/web1/css/index.css?v=2024_12_14_18_4" />
  <link rel="stylesheet" href="/yq-br-prod/web1/css/index.css?v=2024_12_14_18_4" />

  <!-- Preload de assets críticos -->
  <link rel="preload" as="image" href="/ssss/start_page_img.webp" />
  <link rel="modulepreload" href="/yq-br-prod/web1/assets/icons-Cdaou_E3-2024_12_14_18_4.js" />
  <link rel="modulepreload" href="/yq-br-prod/web1/assets/comps-B8ShbmG--2024_12_14_18_4.js" />
  <link rel="modulepreload" href="/yq-br-prod/web1/assets/index-CXpt7Cav-2024_12_14_18_4.js" />

  <!-- CSS secundário -->
  <link rel="stylesheet" href="/yq-br-prod/web1/assets/comps-DLgBMKMA-2024_12_14_18_4.css" />
  <link rel="stylesheet" href="/yq-br-prod/web1/assets/icons-CxwRDrrn-2024_12_14_18_4.css" />
  <link rel="stylesheet" href="/yq-br-prod/web1/assets/index-D1UCb5so-2024_12_14_18_4.css" />

  <!-- Estilos básicos -->
  <style>
    * {
      -webkit-tap-highlight-color: transparent;
    }

    body {
      margin: 0;
      padding: 0;
      overflow-x: hidden;
      background: #ffffff;
    }

    /* Fade in suave do conteúdo */
    #root {
      opacity: 0;
      animation: contentFadeIn 0.4s ease-out forwards;
    }

    @keyframes contentFadeIn {
      to {
        opacity: 1;
      }
    }

    /* Reduzir animações para usuários que preferem */
    @media (prefers-reduced-motion: reduce) {
      * {
        animation-duration: 0.01ms !important;
        transition-duration: 0.01ms !important;
      }
    }
  </style>
</head>
<body>
  <!-- Conteúdo principal -->
  <div id="root"></div>
  <div id="logRegBlock"></div>

  <!-- Scripts de configuração -->
  <script>
    // Performance marks
    if (window.performance && window.performance.mark) {
      performance.mark('app-start');
    }

    // Preload de recursos críticos de forma assíncrona
    (function() {
      const criticalResources = [
        '/yq-br-prod/web1/assets/index-CXpt7Cav-2024_12_14_18_4.js',
        '/yq-br-prod/web1/assets/comps-B8ShbmG--2024_12_14_18_4.js'
      ];

      criticalResources.forEach(url => {
        const link = document.createElement('link');
        link.rel = 'modulepreload';
        link.href = url;
        document.head.appendChild(link);
      });
    })();
  </script>

  <!-- Config/tema -->
  <script src="/xxxx/prod/config.js?v=2024_12_14_18_4" defer></script>
  <script src="/ssss/theme.php?v=2024_12_14_18_4" defer></script>

  <!-- App principal -->
  <script type="module" src="/yq-br-prod/web1/assets/index-CXpt7Cav-2024_12_14_18_4.js"></script>

  <!-- Fallback para navegadores legados -->
  <script nomodule crossorigin id="vite-legacy-polyfill"
    src="/yq-br-prod/web1/assets/polyfills-legacy-CaA53fb3-2024_12_14_18_4.js"></script>
  <script nomodule crossorigin id="vite-legacy-entry"
    data-src="/yq-br-prod/web1/assets/index-legacy-A9U20HDf-2024_12_14_18_4.js">
    System.import(document.getElementById("vite-legacy-entry").getAttribute("data-src"));
  </script>

  <!-- SDKs externos (carregamento diferido e inteligente) -->
  <script>
    // Carrega Google e Facebook apenas quando necessário
    (function() {
      // Detecta se o usuário interage com a página
      let loaded = false;
      
      function loadExternalSDKs() {
        if (loaded) return;
        loaded = true;

        // Google Sign-In
        const googleScript = document.createElement('script');
        googleScript.src = 'https://accounts.google.com/gsi/client';
        googleScript.async = true;
        googleScript.defer = true;
        document.body.appendChild(googleScript);

        // Facebook SDK
        const fbScript = document.createElement('script');
        fbScript.src = 'https://connect.facebook.net/en_US/sdk.js#xfbml=1&version=v20.0';
        fbScript.async = true;
        fbScript.defer = true;
        fbScript.crossOrigin = 'anonymous';
        document.body.appendChild(fbScript);
      }

      // Carrega após interação do usuário ou após 3 segundos
      ['scroll', 'mousemove', 'touchstart', 'click'].forEach(event => {
        window.addEventListener(event, loadExternalSDKs, { once: true, passive: true });
      });

      setTimeout(loadExternalSDKs, 3000);
    })();
  </script>

  <!-- Service Worker (opcional - para PWA) -->
  <script>
    if ('serviceWorker' in navigator) {
      window.addEventListener('load', () => {
        navigator.serviceWorker.register('/sw.js').catch(() => {});
      });
    }
  </script>
</body>
</html>