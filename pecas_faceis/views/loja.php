<?php
$carros = isset($peças) && is_array($peças) ? $peças : [];
$peças = $carros;
$perfil = $_SESSION['perfil'] ?? null;
$usuario = $_SESSION['nome'] ?? null;

function imagemCarroUrl(int $carroId): string
{
    $baseFs = __DIR__ . '/../public/uploads/carros/';
    $baseUrl = 'public/uploads/carros/';
    foreach (['jpg', 'png', 'webp'] as $ext) {
        if (file_exists($baseFs . $carroId . '.' . $ext)) {
            return $baseUrl . $carroId . '.' . $ext;
        }
    }
    return 'img/auto.png';
}

function bannerImageUrl(): string
{
    $bannerFiles = [
        __DIR__ . '/../img/entrada.png',
        __DIR__ . '/../img/banner.png',
        __DIR__ . '/../img/banner.2.jpg',
    ];

    foreach ($bannerFiles as $filePath) {
        if (file_exists($filePath)) {
            return str_replace(__DIR__ . '/../', '', $filePath);
        }
    }
    return 'https://via.placeholder.com/1920x720?text=Pecas+Faceis';
}

$whatsappNumero = '';
try {
    $pdo = Database::getConnection();
    $stmt = $pdo->query("SELECT numero FROM whatsapp_config WHERE id = 1");
    $config = $stmt->fetch(PDO::FETCH_ASSOC);
    $whatsappNumero = $config ? $config['numero'] : '';
} catch (Exception $e) {
    $whatsappNumero = '';
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Peças Fáceis | Sua loja de autopeças online</title>
<link rel="icon" type="image/png" href="img/auto.png">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Barlow+Condensed:wght@600;700;800;900&family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<style>
:root{--red:#ed1018;--red2:#a90008;--black:#070707;--panel:#111;--silver:#c7cbd0;--muted:#a5a5a5;--line:rgba(255,255,255,.13);--green:#13c653}
*{box-sizing:border-box}
html{scroll-behavior:smooth}
body{margin:0;background:#080808;color:#f6f6f6;font-family:Inter,Arial,sans-serif;background-image:radial-gradient(ellipse at 50% -20%,rgba(180,0,0,.22),transparent 55%),linear-gradient(135deg,#080808,#101010 50%,#080808)}
a{color:inherit}
.site-shell{max-width:1680px;margin:auto;overflow:hidden}
.top-header{background:linear-gradient(180deg,#050505,#101010);border-bottom:1px solid rgba(255,30,35,.7);position:relative;z-index:20;box-shadow:0 8px 28px rgba(0,0,0,.5)}
.top-header:after{content:"";position:absolute;left:0;right:0;bottom:-2px;height:2px;background:linear-gradient(90deg,transparent,var(--red),#f3f3f3,var(--red),transparent);opacity:.8}
.header-main{max-width:1500px;margin:auto;min-height:118px;padding:14px 28px;display:grid;grid-template-columns:360px minmax(260px,1fr) auto;gap:28px;align-items:center}
.brand img{display:block;width:min(100%,350px);max-height:100px;object-fit:contain;filter:drop-shadow(0 0 12px rgba(255,0,0,.2))}
.search-form{display:flex;max-width:620px;width:100%;margin:auto;border:1px solid #666;border-radius:30px;background:linear-gradient(180deg,#292929,#111);overflow:hidden;box-shadow:inset 0 2px 8px #000}
.search-form input{min-width:0;flex:1;border:0!important;outline:0;background:transparent!important;color:#fff!important;padding:15px 20px;font-size:15px}
.search-form button{width:62px;border:0;background:linear-gradient(135deg,#ff272d,#b90008);color:#fff;font-size:21px;cursor:pointer}
.header-tools{display:flex;align-items:center;gap:20px;white-space:nowrap}
.header-tools a{text-decoration:none;display:flex;align-items:center;gap:9px;font-size:13px;font-weight:700}
.header-tools .tool-icon{font-size:27px}
.header-tools .divider{height:42px;width:1px;background:#555}
.cart-count{background:var(--red);border-radius:50%;padding:3px 7px;font-size:11px;margin-left:-8px;margin-top:-17px}
.nav-bar{border-top:1px solid #292929;background:linear-gradient(180deg,#171717,#080808)}
.nav-inner{max-width:1500px;margin:auto;padding:9px 28px;display:flex;align-items:center;gap:8px}
.nav-inner a{padding:13px 22px;text-decoration:none;font-size:14px;font-weight:700;border-right:1px solid #333;transition:.2s}
.nav-inner a:hover,.nav-inner a.active{background:linear-gradient(135deg,#ff272d,#b90008);border-radius:9px;color:white}
.nav-inner .wa-link{margin-left:auto;border:0;border-radius:28px;background:#0bbd4b;padding:12px 23px;font-size:15px}
.hero{position:relative;min-height:370px;margin:0 auto;background:#080808 url('img/hero_racing_referencia.jpg') center 48%/cover no-repeat;border-bottom:2px solid #bd090f;box-shadow:0 14px 38px rgba(0,0,0,.5)}
.hero:before{content:"";position:absolute;inset:0;background:linear-gradient(90deg,rgba(0,0,0,.18),rgba(0,0,0,.03) 60%,rgba(0,0,0,.2));pointer-events:none}
.hero-caption{position:absolute;left:5%;bottom:24px;max-width:420px;padding:12px 18px;border-left:4px solid var(--red);background:linear-gradient(90deg,rgba(0,0,0,.86),transparent)}
.hero-caption strong{font:800 24px 'Barlow Condensed',sans-serif;text-transform:uppercase}
.hero-caption span{display:block;color:#ddd;font-size:13px;margin-top:4px}
.trust-row{display:flex;justify-content:center;gap:38px;flex-wrap:wrap;padding:16px 18px;background:linear-gradient(90deg,#151515,#080808,#151515);border-bottom:1px solid #2c2c2c}
.trust-item{display:flex;align-items:center;gap:10px;color:#eee;font-size:13px;font-weight:700}
.trust-item b{font-size:23px;color:#f1f1f1}
.section-wrap{max-width:1500px;margin:auto;padding:24px 28px}
.section-title{display:flex;align-items:center;justify-content:space-between;margin:0 0 16px}
.section-title h2{font:800 27px 'Barlow Condensed',sans-serif;text-transform:uppercase;margin:0;letter-spacing:.4px}
.section-title h2:before{content:"";display:inline-block;width:5px;height:26px;background:var(--red);margin-right:11px;vertical-align:-5px;box-shadow:0 0 12px rgba(255,0,0,.5)}
.section-title a{color:#fff;text-decoration:none;background:linear-gradient(135deg,#f21b22,#a90008);padding:10px 16px;border-radius:22px;font-size:12px;font-weight:800}
.category-grid{display:grid;grid-template-columns:repeat(9,minmax(0,1fr));gap:14px}
.category-tile{min-height:92px;border:1px solid #555;border-radius:15px;background:linear-gradient(145deg,#252525,#080808 65%);box-shadow:inset 0 1px rgba(255,255,255,.12),0 6px 15px #000;display:flex;flex-direction:column;justify-content:center;align-items:center;gap:8px;text-decoration:none;font-weight:700;font-size:12px;transition:.2s}
.category-tile .cat-icon{font-size:27px;color:#e8e8e8;text-shadow:0 2px 5px #000}
.category-tile:hover{border-color:#ff252b;transform:translateY(-4px);box-shadow:0 8px 22px rgba(220,0,0,.2)}
.category-tile.all{background:linear-gradient(135deg,#fa252c,#a50008);border-color:#ff6a6a}
.promo-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:18px;padding-top:20px}
.promo{min-height:112px;position:relative;overflow:hidden;border:1px solid #8c2428;border-radius:16px;padding:18px 20px;background:linear-gradient(115deg,#272727,#090909 65%);box-shadow:inset 0 1px rgba(255,255,255,.12),0 8px 18px #000}
.promo:after{content:"";position:absolute;width:170px;height:170px;border:1px solid rgba(255,255,255,.07);border-radius:50%;right:-35px;top:-60px;box-shadow:0 0 0 12px rgba(255,255,255,.025),0 0 0 25px rgba(255,255,255,.02)}
.promo .promo-icon{float:left;font-size:38px;margin-right:16px;color:#f21b22}
.promo strong{font:800 20px 'Barlow Condensed',sans-serif;text-transform:uppercase}
.promo strong em{font-style:normal;color:#ff252b}
.promo small{display:block;color:#bbb;margin-top:5px}
.about-section{padding-top:30px;padding-bottom:34px}
.about-panel{position:relative;overflow:hidden;display:grid;grid-template-columns:1.2fr .8fr;gap:28px;align-items:center;padding:30px;border:1px solid rgba(220,35,42,.65);border-radius:20px;background:linear-gradient(120deg,rgba(35,35,35,.98),rgba(8,8,8,.98) 65%),linear-gradient(135deg,#555,#111);box-shadow:inset 0 1px rgba(255,255,255,.12),0 12px 30px rgba(0,0,0,.45)}
.about-panel:before{content:"";position:absolute;inset:0 auto 0 0;width:5px;background:linear-gradient(#f33,#8d0007);box-shadow:0 0 22px rgba(255,0,0,.55)}
.about-copy,.about-highlights{position:relative;z-index:1}
.about-eyebrow{display:inline-flex;align-items:center;gap:8px;color:#ff555b;font-size:11px;font-weight:900;letter-spacing:2px;text-transform:uppercase;margin-bottom:10px}
.about-copy h2{font:900 clamp(28px,4vw,42px)/1.02 'Barlow Condensed',sans-serif;text-transform:uppercase;margin:0 0 14px;color:#fff}
.about-copy h2 span{color:#f32a31;text-shadow:0 0 18px rgba(255,0,0,.2)}
.about-copy p{max-width:760px;color:#c7c7c7;font-size:14px;line-height:1.85;margin:0 0 10px}
.about-highlights{display:grid;grid-template-columns:1fr 1fr;gap:12px}
.about-highlight{min-height:94px;padding:17px;border:1px solid #454545;border-radius:13px;background:linear-gradient(145deg,rgba(65,65,65,.42),rgba(12,12,12,.92));box-shadow:inset 0 1px rgba(255,255,255,.08)}
.about-highlight b{display:block;color:#f33;font:800 18px 'Barlow Condensed',sans-serif;text-transform:uppercase;margin-bottom:6px}
.about-highlight span{color:#bbb;font-size:11px;line-height:1.5}
.products-section{padding-top:20px}
.cars-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:18px}
.car-card{position:relative;display:flex;flex-direction:column;overflow:hidden;background:linear-gradient(180deg,#202020,#0d0d0d);border:1px solid #3d3d3d;border-radius:15px;box-shadow:0 8px 22px rgba(0,0,0,.4);transition:.22s}
.car-card:hover{transform:translateY(-5px);border-color:#e51a20;box-shadow:0 12px 28px rgba(220,0,0,.18)}
.car-card>img{width:100%;height:205px;object-fit:contain;padding:12px;background:linear-gradient(135deg,#f1f1f1,#c9c9c9);border-bottom:3px solid #b90008}
.car-card-body{padding:16px;display:flex;flex-direction:column;flex:1}
.car-card-body h2{font-size:15px;margin:0 0 7px;color:#fff}
.car-sub{font-size:12px;color:#aaa;margin-bottom:9px}
.car-price{font-size:21px;font-weight:900;color:#ff343a;margin:4px 0 9px}
.car-desc{font-size:12px;line-height:1.5;color:#aaa;min-height:36px;margin-bottom:12px;flex:1}
.car-card-actions{display:flex;align-items:center;gap:8px;flex-wrap:wrap;border-top:1px solid #333;padding-top:12px}
.car-card-actions form{margin:0}
.car-card-actions button,.car-card-actions a{border:0;border-radius:22px;padding:10px 13px;font-size:12px;font-weight:800;cursor:pointer;text-decoration:none}
.car-card-actions .buy-button{background:linear-gradient(135deg,#f3242b,#a80008);color:#fff}
.car-card-actions .btn-contact{background:#13bd53;color:#06190d}
.car-card-actions .btn-outline{background:transparent;border:1px solid #666;color:#eee}
.empty-state{margin:20px 28px 50px;text-align:center;padding:55px 20px;border:1px dashed #555;border-radius:15px;background:#111}
.footer{border-top:2px solid #b90008;background:#050505;padding:28px;text-align:center;color:#888;font-size:12px}
.footer strong{color:#eee}
.contato-overlay{position:fixed;inset:0;background:rgba(0,0,0,.78);z-index:2000;display:none;align-items:center;justify-content:center;backdrop-filter:blur(5px)}
.contato-overlay.open{display:flex}
.contato-modal{background:#151515;border:1px solid #555;border-top:3px solid #ed1018;border-radius:18px;width:460px;max-width:calc(100vw - 32px);max-height:calc(100vh - 40px);overflow-y:auto;padding:26px;box-shadow:0 20px 60px #000}
.contato-modal-header{display:flex;align-items:center;justify-content:space-between;margin-bottom:20px}
.contato-modal-header h2{margin:0;font-size:20px}
.contato-modal .close-btn{background:none;border:0;color:#aaa;font-size:25px;cursor:pointer}
.contato-modal .car-info{background:#222;border-radius:10px;padding:14px;margin-bottom:18px;border:1px solid #444}
.contato-modal .car-info strong{color:#ff343a}
.contato-modal .form-group{margin-bottom:14px}
.contato-modal .form-group label{display:block;margin-bottom:6px;font-size:13px;font-weight:700}
.contato-modal .form-group input,.contato-modal .form-group textarea{width:100%;padding:12px;border-radius:9px;background:#090909;border:1px solid #444;color:#fff}
.contato-modal .form-group textarea{resize:vertical;min-height:80px}
.contato-modal .btn-enviar{width:100%;padding:14px;border:0;border-radius:10px;background:#13bd53;color:#06190d;font-weight:900;cursor:pointer}
.unavailable-msg{text-align:center;padding:22px;color:#bbb}
@media(max-width:1100px){.header-main{grid-template-columns:240px 1fr;}.header-tools{grid-column:1/-1;justify-content:flex-end}.category-grid{grid-template-columns:repeat(5,1fr)}.cars-grid{grid-template-columns:repeat(3,1fr)}}
@media(max-width:700px){.header-main{display:flex;flex-wrap:wrap;padding:12px 15px;gap:12px}.brand{width:100%;text-align:center}.brand img{max-width:270px;margin:auto}.search-form{order:3;max-width:none}.header-tools{width:100%;justify-content:center;gap:12px}.nav-inner{padding:8px 12px;flex-wrap:wrap;justify-content:center}.nav-inner a{padding:10px 12px;font-size:12px}.nav-inner .wa-link{margin:4px auto}.hero{min-height:230px;background-position:center}.hero-caption{left:12px;bottom:10px;padding:8px 10px}.hero-caption strong{font-size:17px}.hero-caption span{font-size:10px}.trust-row{gap:14px;padding:13px}.trust-item{font-size:10px}.trust-item b{font-size:18px}.section-wrap{padding:20px 14px}.category-grid{grid-template-columns:repeat(3,1fr);gap:9px}.category-tile{min-height:78px}.about-panel{grid-template-columns:1fr;padding:22px}.about-highlights{grid-template-columns:1fr 1fr}.promo-grid{grid-template-columns:1fr;gap:10px}.promo{min-height:95px}.cars-grid{grid-template-columns:repeat(2,minmax(0,1fr));gap:10px}.car-card>img{height:145px}.car-card-body{padding:11px}.car-card-body h2{font-size:13px}.car-price{font-size:17px}.car-card-actions button,.car-card-actions a{padding:9px 10px;font-size:10px}}
@media(max-width:420px){.about-highlights{grid-template-columns:1fr}.cars-grid{grid-template-columns:1fr}.header-tools a{font-size:11px}.section-title h2{font-size:22px}}
</style>
</head>
<body>
<div class="site-shell">
<header class="top-header">
  <div class="header-main">
    <a class="brand" href="lojaview.php"><img src="img/dentr.png" alt="Peças Fáceis"></a>
    <form class="search-form" action="lojaview.php" method="GET">
      <input type="search" name="search" placeholder="O que você está procurando?" value="<?= htmlspecialchars($_GET['search'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
      <button type="submit" aria-label="Buscar">⌕</button>
    </form>
    <div class="header-tools">
      <a href="<?= $usuario ? 'index.php?controller=auth&action=logout' : 'index.php?controller=auth&action=form' ?>"><span class="tool-icon">♙</span><span><?= $usuario ? 'Olá, '.htmlspecialchars($usuario, ENT_QUOTES, 'UTF-8').'<br>Sair' : 'Minha Conta<br>Entrar / Cadastrar' ?></span></a>
      <span class="divider"></span>
      <a href="carrinho.php"><span class="tool-icon">🛒</span><span class="cart-count"><?= array_sum($_SESSION['carrinho'] ?? []) ?></span><span>Meu Carrinho<br>Confira seus itens</span></a>
      <?php if ($perfil === 'admin'): ?><a href="index.php?controller=auth&action=dashboard">⚙ Admin</a><?php endif; ?>
    </div>
  </div>
  <nav class="nav-bar"><div class="nav-inner">
    <a class="active" href="lojaview.php">⌂ Início</a>
    <a href="#categorias">▦ Categorias⌄</a>
    <a href="lojaview.php?search=oferta">◆ Ofertas</a>
    <a href="#sobre">ⓘ Sobre Nós</a>
    <a href="#contato">☏ Contato</a>
    <?php if ($whatsappNumero !== ''): ?><a class="wa-link" target="_blank" rel="noopener" href="https://wa.me/55<?= preg_replace('/\D+/', '', $whatsappNumero) ?>">☏ Fale Conosco</a><?php endif; ?>
  </div></nav>
</header>

<section class="hero" aria-label="Destaques Peças Fáceis">
  <div class="hero-caption"><strong>Peças de qualidade para seu carro</strong><span>Ofertas especiais, compra segura e praticidade para você.</span></div>
</section>

<div class="trust-row">
  <div class="trust-item"><b>✓</b> Compra segura</div>
  <div class="trust-item"><b>🚚</b> Entrega rápida</div>
  <div class="trust-item"><b>▣</b> Pagamento facilitado</div>
  <div class="trust-item"><b>⚙</b> Peças selecionadas</div>
</div>

<section class="section-wrap" id="categorias">
  <div class="section-title"><h2>Compre por categoria</h2><a href="#produtos">Ver produtos →</a></div>
  <div class="category-grid">
    <a class="category-tile" href="lojaview.php?search=motor"><span class="cat-icon">⚙</span>Motor</a>
    <a class="category-tile" href="lojaview.php?search=freio"><span class="cat-icon">◉</span>Freios</a>
    <a class="category-tile" href="lojaview.php?search=suspensão"><span class="cat-icon">⌁</span>Suspensão</a>
    <a class="category-tile" href="lojaview.php?search=bateria"><span class="cat-icon">▣</span>Baterias</a>
    <a class="category-tile" href="lojaview.php?search=filtro"><span class="cat-icon">▤</span>Filtros</a>
    <a class="category-tile" href="lojaview.php?search=farol"><span class="cat-icon">✧</span>Iluminação</a>
    <a class="category-tile" href="lojaview.php?search=acessório"><span class="cat-icon">⚙</span>Acessórios</a>
    <a class="category-tile" href="lojaview.php?search=pneu"><span class="cat-icon">◉</span>Pneus</a>
    <a class="category-tile all" href="#produtos"><span class="cat-icon">▦</span>Ver Todas →</a>
  </div>

  <div class="promo-grid">
    <div class="promo"><span class="promo-icon">％</span><strong>Ofertas <em>imperdíveis</em></strong><small>Economize nas peças para seu veículo.</small></div>
    <div class="promo"><span class="promo-icon">🚚</span><strong>Entrega para todo o Brasil</strong><small>Praticidade e segurança na sua compra.</small></div>
    <div class="promo"><span class="promo-icon">🛡</span><strong>Compra <em>100% segura</em></strong><small>Atendimento para ajudar você.</small></div>
  </div>
</section>

<section class="section-wrap about-section" id="sobre" aria-labelledby="sobreTitulo">
  <div class="about-panel">
    <div class="about-copy">
      <div class="about-eyebrow">✦ Nossa marca · Peças Fáceis</div>
      <h2 id="sobreTitulo">Paixão por carros. <span>Compromisso com você.</span></h2>
      <p>A Peças Fáceis nasceu com uma ideia simples: tornar a busca por peças automotivas mais prática, clara e acessível. Queremos ajudar cada cliente a encontrar o que precisa para cuidar do seu veículo, com uma experiência de compra fácil do começo ao fim.</p>
      <p>Trabalhamos para oferecer produtos, informações e atendimento com atenção e responsabilidade. Mais do que vender peças, queremos construir uma relação de confiança com quem escolhe a nossa marca.</p>
    </div>
    <div class="about-highlights" aria-label="Compromissos da Peças Fáceis">
      <div class="about-highlight"><b>Compra prática</b><span>Uma experiência simples para você encontrar o que procura.</span></div>
      <div class="about-highlight"><b>Atendimento próximo</b><span>Estamos aqui para ajudar com dúvidas e informações.</span></div>
      <div class="about-highlight"><b>Foco em qualidade</b><span>Atenção aos produtos e aos detalhes de cada pedido.</span></div>
      <div class="about-highlight"><b>Confiança sempre</b><span>Respeito, transparência e cuidado em cada etapa.</span></div>
    </div>
  </div>
</section>

<section class="section-wrap products-section" id="produtos">
  <div class="section-title"><h2><?= !empty($_GET['search']) ? 'Resultado da busca' : 'Produtos em destaque' ?></h2><a href="lojaview.php">Ver todos os produtos →</a></div>
  <?php if (count($carros) === 0): ?>
    <div class="empty-state"><h2>Nenhuma peça encontrada</h2><p style="color:#aaa;margin-top:8px;">Tente buscar por outro nome, marca ou modelo.</p></div>
  <?php else: ?>
    <div class="cars-grid">
      <?php foreach ($carros as $car): ?>
        <?php
          $carId=(int)($car['id']??0); $imgUrl=imagemCarroUrl($carId);
          $preco=isset($car['preco_atual']) && $car['preco_atual']!=='' ? (float)$car['preco_atual'] : (float)($car['preco_inicial']??$car['preco']??0);
          $nomeCarro=$car['nome']??''; $marcaCarro=$car['marca']??''; $modeloCarro=$car['modelo']??''; $anoCarro=$car['ano']??''; $corCarro=$car['cor']??'';
        ?>
        <article class="car-card">
          <img src="<?= htmlspecialchars($imgUrl, ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars($nomeCarro, ENT_QUOTES, 'UTF-8') ?>" loading="lazy">
          <div class="car-card-body">
            <h2><?= htmlspecialchars($nomeCarro, ENT_QUOTES, 'UTF-8') ?></h2>
            <div class="car-sub"><?= htmlspecialchars(trim($marcaCarro.' '.$modeloCarro), ENT_QUOTES, 'UTF-8') ?><?= $anoCarro ? ' • '.htmlspecialchars((string)$anoCarro, ENT_QUOTES, 'UTF-8') : '' ?></div>
            <div class="car-price">R$ <?= number_format($preco,2,',','.') ?></div>
            <div class="car-desc"><?= htmlspecialchars(mb_strimwidth((string)($car['descricao']??''),0,100,'...'), ENT_QUOTES, 'UTF-8') ?></div>
            <div class="car-card-actions">
              <form method="post" action="carrinho.php"><input type="hidden" name="acao" value="adicionar"><input type="hidden" name="produto_id" value="<?= $carId ?>"><input type="hidden" name="quantidade" value="1"><button class="buy-button" type="submit">🛒 Comprar</button></form>
              <button class="btn-contact" onclick="openContato(<?= $carId ?>, '<?= htmlspecialchars($nomeCarro, ENT_QUOTES) ?>', '<?= htmlspecialchars($marcaCarro, ENT_QUOTES) ?>', '<?= htmlspecialchars($modeloCarro, ENT_QUOTES) ?>', '<?= htmlspecialchars($anoCarro, ENT_QUOTES) ?>', '<?= htmlspecialchars($corCarro, ENT_QUOTES) ?>', '<?= htmlspecialchars(number_format($preco,2,',','.'), ENT_QUOTES) ?>')">WhatsApp</button>
              <?php if ($perfil === 'admin'): ?><a class="btn-outline" href="index.php?controller=produto&action=index">Editar</a><?php endif; ?>
            </div>
          </div>
        </article>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</section>

<footer class="footer" id="contato"><strong>PEÇAS FÁCEIS</strong> · Sua loja de autopeças online · Qualidade, praticidade e confiança.</footer>

    <div class="contato-overlay" id="contatoOverlay">
        <div class="contato-modal">
            <div class="contato-modal-header">
                <h2>📱 Contato</h2>
                <button class="close-btn" onclick="closeContato()">✕</button>
            </div>

            <div id="contatoCarInfo" class="car-info" style="display:none;">
                <strong id="contatoCarName"></strong><br>
                <span id="contatoCarDetails" style="color:var(--muted); font-size:0.9rem;"></span>
            </div>

            <?php if ($whatsappNumero === ''): ?>
                <div class="unavailable-msg">
                    <span>😔</span>
                    <p>O contato via WhatsApp está indisponível no momento.</p>
                    <p style="font-size:0.85rem;">Tente novamente mais tarde.</p>
                </div>
            <?php else: ?>
                <form id="contatoForm" onsubmit="return sendWhatsApp(event)">
                    <input type="hidden" id="fieldCarroId" value="">
                    <input type="hidden" id="fieldMarca" value="">
                    <input type="hidden" id="fieldModelo" value="">
                    <input type="hidden" id="fieldAno" value="">
                    <input type="hidden" id="fieldCor" value="">
                    <input type="hidden" id="fieldPreco" value="">

                    <div class="form-group">
                        <label>Nome <span class="required">*</span></label>
                        <input type="text" id="fieldNome" required placeholder="Seu nome">
                    </div>
                    <div class="form-group">
                        <label>Telefone <span class="required">*</span></label>
                        <input type="text" id="fieldTelefone" required placeholder="(11) 99999-9999" oninput="maskTelefone(this)">
                    </div>
                    <div class="form-group">
                        <label>Mensagem <span style="color:var(--muted); font-size:0.8rem;">(opcional)</span></label>
                        <textarea id="fieldMensagem" placeholder="Digite sua mensagem..."></textarea>
                    </div>

                    <button type="submit" class="btn-enviar" id="btnEnviar">
                        Enviar para WhatsApp
                    </button>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <script>
    function openContato(id, nome, marca, modelo, ano, cor, preco) {
        document.getElementById('contatoCarInfo').style.display = 'block';
        document.getElementById('contatoCarName').textContent = nome;
        document.getElementById('contatoCarDetails').textContent = marca + ' ' + modelo + ' • ' + ano + ' • ' + cor + ' • R$ ' + preco;

        document.getElementById('fieldCarroId').value = id;
        document.getElementById('fieldMarca').value = marca;
        document.getElementById('fieldModelo').value = modelo;
        document.getElementById('fieldAno').value = ano;
        document.getElementById('fieldCor').value = cor;
        document.getElementById('fieldPreco').value = preco;

        document.getElementById('contatoOverlay').classList.add('open');
        document.body.style.overflow = 'hidden';
    }

    function closeContato() {
        document.getElementById('contatoOverlay').classList.remove('open');
        document.body.style.overflow = '';
    }

    document.getElementById('contatoOverlay').addEventListener('click', function(e) {
        if (e.target === this) closeContato();
    });

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') closeContato();
    });

    function maskTelefone(input) {
        let value = input.value.replace(/[^0-9]/g, '');
        if (value.length > 11) value = value.slice(0, 11);

        if (value.length > 7) {
            value = '(' + value.slice(0, 2) + ') ' + value.slice(2, 7) + '-' + value.slice(7);
        } else if (value.length > 2) {
            value = '(' + value.slice(0, 2) + ') ' + value.slice(2);
        } else if (value.length > 0) {
            value = '(' + value;
        }
        input.value = value;
    }

    function sendWhatsApp(event) {
        event.preventDefault();

        const nome = document.getElementById('fieldNome').value.trim();
        const telefone = document.getElementById('fieldTelefone').value.trim();
        const mensagem = document.getElementById('fieldMensagem').value.trim();

        if (!nome) { alert('Informe seu nome.'); return false; }
        if (!telefone) { alert('Informe seu telefone.'); return false; }

        const marca = document.getElementById('fieldMarca').value;
        const modelo = document.getElementById('fieldModelo').value;
        const ano = document.getElementById('fieldAno').value;
        const cor = document.getElementById('fieldCor').value;
        const preco = document.getElementById('fieldPreco').value;

        const texto = 'Olá! Tenho interesse no produto abaixo.\n\n' +
            '🚗 Veículo: ' + marca + ' ' + modelo + '\n' +
            '📅 Ano: ' + ano + '\n' +
            '🎨 Cor: ' + cor + '\n' +
            '💰 Preço: R$ ' + preco + '\n\n' +
            '👤 Nome: ' + nome + '\n' +
            '📞 Telefone: ' + telefone + '\n\n' +
            '💬 Mensagem:\n' + (mensagem || '(Não informada)');

        const numero = <?= json_encode($whatsappNumero) ?>;
        window.open('https://wa.me/55' + numero + '?text=' + encodeURIComponent(texto), '_blank');

        closeContato();
        return false;
    }
    </script>

</div>
</body>
</html>
