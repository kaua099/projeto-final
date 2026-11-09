<?php
$nome = $_SESSION['nome'] ?? 'Administrador';
$perfil = $_SESSION['perfil'] ?? 'vendedor';
require_once __DIR__ . '/../config/db.php';

$pdo = Database::getConnection();
$fmt = static function ($valor) {
    return 'R$ ' . number_format((float)$valor, 2, ',', '.');
};
$kpis = ['vendas' => 0, 'pedidos' => 0, 'clientes' => 0, 'produtos' => 0];
$ultimosPedidos = [];
$notificacoes = [];
$vendasSemana = array_fill(0, 7, 0.0);
$labelsSemana = [];

try {
    $kpis['clientes'] = (int)$pdo->query("SELECT COUNT(*) FROM usuarios WHERE tipo = 'cliente'")->fetchColumn();
    $kpis['produtos'] = (int)$pdo->query("SELECT COUNT(*) FROM carros WHERE ativo = 1")->fetchColumn();

    // Pedidos da loja virtual (checkout).
    try {
        $kpis['vendas'] = (float)$pdo->query("SELECT COALESCE(SUM(total),0) FROM pedidos WHERE YEAR(criado_em)=YEAR(CURDATE()) AND MONTH(criado_em)=MONTH(CURDATE())")->fetchColumn();
        $kpis['pedidos'] = (int)$pdo->query("SELECT COUNT(*) FROM pedidos WHERE YEAR(criado_em)=YEAR(CURDATE()) AND MONTH(criado_em)=MONTH(CURDATE())")->fetchColumn();
        $ultimosPedidos = $pdo->query("SELECT id, cliente_nome, cliente_telefone, forma_pagamento, total, status, whatsapp_status, criado_em FROM pedidos ORDER BY criado_em DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
        $notificacoes = $pdo->query("SELECT id, cliente_nome, total, whatsapp_status, whatsapp_erro, criado_em FROM pedidos ORDER BY criado_em DESC LIMIT 4")->fetchAll(PDO::FETCH_ASSOC);
        $stmtSemana = $pdo->query("SELECT DATE(criado_em) dia, SUM(total) total FROM pedidos WHERE criado_em >= CURDATE() - INTERVAL 6 DAY GROUP BY DATE(criado_em)");
    } catch (PDOException $e) {
        // Compatibilidade com instalações que ainda não importaram a tabela de pedidos.
        $kpis['vendas'] = (float)$pdo->query("SELECT COALESCE(SUM(valor),0) FROM vendas WHERE YEAR(data_venda)=YEAR(CURDATE()) AND MONTH(data_venda)=MONTH(CURDATE())")->fetchColumn();
        $kpis['pedidos'] = (int)$pdo->query("SELECT COUNT(*) FROM vendas WHERE YEAR(data_venda)=YEAR(CURDATE()) AND MONTH(data_venda)=MONTH(CURDATE())")->fetchColumn();
        $ultimosPedidos = $pdo->query("SELECT v.id, v.cliente_nome, v.cliente_telefone, v.valor AS total, v.data_venda AS criado_em, c.nome AS produto_nome FROM vendas v LEFT JOIN carros c ON c.id=v.carro_id ORDER BY v.data_venda DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
        $stmtSemana = $pdo->query("SELECT DATE(data_venda) dia, SUM(valor) total FROM vendas WHERE data_venda >= CURDATE() - INTERVAL 6 DAY GROUP BY DATE(data_venda)");
    }
    $porDia = [];
    foreach ($stmtSemana->fetchAll(PDO::FETCH_ASSOC) as $linha) $porDia[$linha['dia']] = (float)$linha['total'];
    for ($i = 6; $i >= 0; $i--) {
        $dia = date('Y-m-d', strtotime("-$i days"));
        $labelsSemana[] = ['label' => date('D', strtotime($dia)), 'valor' => $porDia[$dia] ?? 0];
        $vendasSemana[$i] = $porDia[$dia] ?? 0;
    }
} catch (PDOException $e) {
    // O painel continua carregando mesmo se alguma consulta de indicador falhar.
}
$maxSemana = max(array_merge([1], array_column($labelsSemana, 'valor')));
$dataHoje = date('d/m/Y');
?>
<!doctype html>
<html lang="pt-br">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Painel Administrativo | Peças Fáceis</title>
<link rel="icon" type="image/png" href="img/logo.png">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
<style>
:root{--red:#ed101b;--red2:#a90009;--bg:#090909;--panel:#151515;--line:rgba(255,255,255,.09);--muted:#aaa;--white:#fff}
*{box-sizing:border-box}body{margin:0;background:radial-gradient(ellipse at 80% 0%,#390609 0,#100707 34%,#080808 72%);color:var(--white);font-family:Inter,Arial,sans-serif;min-height:100vh}
a{color:inherit;text-decoration:none}.layout{display:grid;grid-template-columns:250px minmax(0,1fr);min-height:100vh}
.sidebar{background:linear-gradient(180deg,#170303,#090909 72%);border-right:1px solid rgba(255,30,40,.35);padding:24px 16px;display:flex;flex-direction:column;position:sticky;top:0;height:100vh}
.brand{text-align:center;padding:0 8px 25px;border-bottom:1px solid var(--line)}.brand img{max-width:100%;width:205px;height:115px;object-fit:contain}.brand small{display:block;color:#ddd;font-size:11px;font-weight:700;font-style:italic;margin-top:-7px}
.menu{display:grid;gap:7px;margin-top:25px}.menu a{display:flex;align-items:center;gap:13px;padding:13px 14px;border:1px solid transparent;border-radius:13px;color:#d6d6d6;font-size:13px;font-weight:650;transition:.2s}.menu a span{font-size:19px;width:24px;text-align:center}.menu a:hover,.menu a.active{background:linear-gradient(100deg,#ed101b,#a40008);color:white;box-shadow:0 8px 24px #ed101b30}.support{margin-top:auto;padding:16px 13px;border:1px solid #ed101b;border-radius:17px;background:linear-gradient(135deg,#240609,#090909);box-shadow:0 0 22px #ed101b22}.support b{display:block;font-size:14px}.support small{color:#bbb;line-height:1.5}
.main{min-width:0}.topbar{height:96px;padding:18px 30px;display:flex;align-items:center;justify-content:space-between;background:linear-gradient(105deg,#b9000b,#f3131e 52%,#780008);border-bottom:1px solid #ff4b53;box-shadow:0 8px 35px #ed101b24}.welcome{display:flex;align-items:center;gap:14px}.welcome .avatar{font-size:34px}.welcome h1{font-size:21px;margin:0 0 5px;font-weight:800}.welcome p{margin:0;font-size:12px;color:#ffe4e4}.top-actions{display:flex;align-items:center;gap:12px}.today,.top-icon{background:#4d0005a8;border:1px solid #ffffff30;border-radius:30px;padding:11px 15px;font-size:12px;font-weight:700}.top-icon{border-radius:50%;font-size:18px;padding:10px}.content{padding:25px 28px 35px;max-width:1750px;margin:auto}
.kpis{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:15px}.kpi{min-height:125px;padding:20px 18px;border:1px solid #ff353d8a;border-radius:17px;background:linear-gradient(145deg,#e20c17,#650006 75%,#270306);box-shadow:0 12px 30px #0008;display:flex;align-items:center;gap:15px;position:relative;overflow:hidden}.kpi:after{content:"";position:absolute;width:130px;height:130px;border:1px solid #ffffff12;border-radius:50%;right:-45px;top:-50px}.kpi-icon{font-size:34px;filter:drop-shadow(0 3px 8px #0007)}.kpi-label{font-size:12px;color:#ffe1e1;font-weight:600}.kpi-value{font-size:24px;font-weight:850;margin-top:8px;letter-spacing:-.5px}.kpi-note{font-size:10px;color:#ffd0d0;margin-top:5px}
.middle{display:grid;grid-template-columns:1.35fr .95fr;gap:17px;margin-top:19px}.panel{background:linear-gradient(145deg,#171313,#0c0b0b);border:1px solid #ffffff18;border-radius:18px;padding:19px;box-shadow:0 14px 32px #0008;min-width:0}.panel-head{display:flex;align-items:center;justify-content:space-between;gap:10px;margin-bottom:18px}.panel-head h2{font-size:16px;margin:0;font-weight:800}.panel-head h2 span{color:var(--red);margin-right:7px}.period{background:#bd0711;border:0;border-radius:30px;padding:9px 14px;color:white;font-weight:700;font-size:11px}
.chart{height:220px;display:flex;align-items:flex-end;gap:clamp(8px,2vw,22px);padding:18px 8px 0;border-bottom:1px solid #ffffff32;background:repeating-linear-gradient(to bottom,transparent 0,transparent 52px,#ffffff0d 53px)}.bar-wrap{flex:1;min-width:0;height:100%;display:flex;flex-direction:column;justify-content:flex-end;align-items:center;gap:8px}.bar{width:min(48px,80%);min-height:3px;background:linear-gradient(180deg,#ff555b,#c9000b);border-radius:8px 8px 0 0;box-shadow:0 0 15px #e8001640}.bar-value{font-size:9px;color:#ddd;white-space:nowrap}.bar-label{font-size:10px;color:#bbb;padding-top:8px}
.promo{min-height:270px;display:flex;align-items:center;position:relative;overflow:hidden;background:radial-gradient(circle at 85% 50%,#650b10,#180607 50%,#0b0b0b 80%);border-color:#e21a26}.promo-copy{position:relative;z-index:2;max-width:75%}.promo h2{font-size:clamp(24px,3vw,38px);font-style:italic;line-height:1;margin:0 0 12px;font-weight:900}.promo h2 em{color:#ff252e}.promo p{font-size:13px;color:#ddd;line-height:1.5}.promo .cta{display:inline-block;margin-top:8px;padding:10px 15px;border-radius:30px;background:#e90d18;font-size:11px;font-weight:800}.promo-art{position:absolute;right:-10px;bottom:0;font-size:100px;opacity:.85;filter:drop-shadow(0 0 15px #e11)}
.bottom{display:grid;grid-template-columns:1.45fr .8fr;gap:17px;margin-top:19px}.table-scroll{overflow-x:auto}table{width:100%;border-collapse:collapse;min-width:560px}th{text-align:left;background:linear-gradient(90deg,#d70b15,#9d0008);font-size:11px;padding:12px 10px}th:first-child{border-radius:9px 0 0 0}th:last-child{border-radius:0 9px 0 0}td{font-size:11px;padding:12px 10px;border-bottom:1px solid #ffffff12;color:#e9e9e9}tbody tr:nth-child(even){background:#ffffff06}.status{display:inline-block;border-radius:30px;padding:5px 9px;font-size:10px;font-weight:800}.ok{background:#087d38;color:#fff}.pending{background:#805700;color:#ffe29a}.failed{background:#8d1010;color:#ffd0d0}.empty{padding:24px;text-align:center;color:#aaa;font-size:12px}
.notice{display:flex;gap:11px;padding:13px 0;border-bottom:1px solid #ffffff12}.notice:last-child{border:0}.notice-icon{font-size:22px}.notice b{display:block;font-size:11px;margin-bottom:5px}.notice small{font-size:10px;color:#aaa;line-height:1.5}.notice time{margin-left:auto;color:#aaa;font-size:9px;white-space:nowrap}
@media(max-width:1150px){.layout{grid-template-columns:210px minmax(0,1fr)}.kpis{grid-template-columns:repeat(2,1fr)}.middle,.bottom{grid-template-columns:1fr}.sidebar{padding:20px 12px}}
@media(max-width:700px){.layout{display:block}.sidebar{position:relative;height:auto;padding:12px}.brand{padding:0 0 10px}.brand img{height:65px;width:150px}.brand small,.support{display:none}.menu{display:flex;overflow-x:auto;margin:10px 0 0;padding-bottom:4px}.menu a{white-space:nowrap;padding:9px 11px;font-size:11px}.menu a span{font-size:15px;width:auto}.topbar{height:auto;padding:16px;gap:10px}.welcome h1{font-size:16px}.welcome p{font-size:10px}.today{display:none}.content{padding:16px}.kpis{gap:9px}.kpi{padding:13px 10px;min-height:100px;gap:8px}.kpi-icon{font-size:24px}.kpi-value{font-size:17px}.kpi-label{font-size:10px}.panel{padding:14px}.chart{height:180px}.promo{min-height:210px}.promo-art{font-size:70px}}
</style>
</head>
<body>
<div class="layout">
<aside class="sidebar">
    <div class="brand"><img src="img/logo.png" alt="Peças Fáceis"><small>Sua loja de autopeças online!</small></div>
    <nav class="menu">
        <a class="active" href="index.php?controller=auth&action=dashboard"><span>⌂</span>Dashboard</a>
        <a href="index.php?controller=venda&action=index"><span>🛒</span>Vendas</a>
        <a href="index.php?controller=produto&action=index"><span>📦</span>Produtos</a>
        <a href="index.php?controller=usuario&action=index"><span>👥</span>Clientes / Usuários</a>
        <a href="index.php?controller=entrada&action=index"><span>🚚</span>Entradas</a>
        <a href="index.php?controller=relatorio&action=index"><span>📊</span>Relatórios</a>
        <a href="index.php?controller=contato&action=index"><span>✉</span>Contatos</a>
        <a href="index.php?controller=whatsapp&action=config"><span>◉</span>WhatsApp</a>
        <a href="index.php?controller=auth&action=logout"><span>⇥</span>Sair</a>
    </nav>
    <div class="support"><b>🎧 Suporte sempre com você!</b><small>Gerencie sua loja com praticidade e acompanhe seus pedidos em um só lugar.</small></div>
</aside>
<main class="main">
<header class="topbar">
    <div class="welcome"><div class="avatar">👤</div><div><h1>Olá, <?= htmlspecialchars($nome, ENT_QUOTES, 'UTF-8') ?>!</h1><p>Bem-vindo(a) de volta ao painel da Peças Fáceis</p></div></div>
    <div class="top-actions"><div class="today">▦ &nbsp; Hoje: <?= $dataHoje ?></div><a class="top-icon" href="index.php?controller=whatsapp&action=config" title="Configurações do WhatsApp">⚙</a><a class="top-icon" href="index.php?controller=contato&action=index" title="Notificações">♧</a></div>
</header>
<div class="content">
    <section class="kpis">
        <article class="kpi"><div class="kpi-icon">🛒</div><div><div class="kpi-label">Vendas neste mês</div><div class="kpi-value"><?= $fmt($kpis['vendas']) ?></div><div class="kpi-note">Total registrado no sistema</div></div></article>
        <article class="kpi"><div class="kpi-icon">📦</div><div><div class="kpi-label">Pedidos neste mês</div><div class="kpi-value"><?= number_format($kpis['pedidos'],0,',','.') ?></div><div class="kpi-note">Pedidos registrados</div></div></article>
        <article class="kpi"><div class="kpi-icon">👥</div><div><div class="kpi-label">Clientes cadastrados</div><div class="kpi-value"><?= number_format($kpis['clientes'],0,',','.') ?></div><div class="kpi-note">Contas de clientes</div></div></article>
        <article class="kpi"><div class="kpi-icon">🔧</div><div><div class="kpi-label">Produtos ativos</div><div class="kpi-value"><?= number_format($kpis['produtos'],0,',','.') ?></div><div class="kpi-note">Itens disponíveis na loja</div></div></article>
    </section>
    <section class="middle">
        <article class="panel"><div class="panel-head"><h2><span>▥</span>Vendas dos últimos 7 dias</h2><span class="period">Últimos 7 dias</span></div>
            <div class="chart">
            <?php foreach ($labelsSemana as $dia): $altura = $maxSemana > 0 ? max(2, ($dia['valor'] / $maxSemana) * 155) : 2; ?>
                <div class="bar-wrap"><div class="bar-value"><?= $dia['valor'] > 0 ? $fmt($dia['valor']) : '—' ?></div><div class="bar" style="height:<?= (float)$altura ?>px"></div><div class="bar-label"><?= htmlspecialchars($dia['label']) ?></div></div>
            <?php endforeach; ?>
            </div>
        </article>
        <article class="panel promo"><div class="promo-copy"><h2>PEÇAS<br><em>FÁCEIS</em></h2><p>Qualidade, praticidade<br>e o melhor preço!</p><a class="cta" href="lojaview.php">Acessar loja ↗</a></div><div class="promo-art">⚙️ 🚘</div></article>
    </section>
    <section class="bottom">
        <article class="panel"><div class="panel-head"><h2><span>⚡</span>Pedidos recentes</h2><a class="period" href="index.php?controller=venda&action=index">Ver vendas →</a></div>
        <?php if ($ultimosPedidos): ?><div class="table-scroll"><table><thead><tr><th>Pedido</th><th>Cliente</th><th>Total</th><th>Pagamento</th><th>Status WhatsApp</th><th>Data</th></tr></thead><tbody>
        <?php foreach ($ultimosPedidos as $pedido): $wstatus=$pedido['whatsapp_status']??''; ?>
        <tr><td><strong>#<?= (int)$pedido['id'] ?></strong></td><td><?= htmlspecialchars($pedido['cliente_nome']??'Cliente',ENT_QUOTES,'UTF-8') ?></td><td><?= $fmt($pedido['total']??0) ?></td><td><?= htmlspecialchars($pedido['forma_pagamento']??'—',ENT_QUOTES,'UTF-8') ?></td><td><span class="status <?= $wstatus==='enviado'?'ok':($wstatus==='falhou'?'failed':'pending') ?>"><?= $wstatus==='enviado'?'Enviado':($wstatus==='falhou'?'Falhou':($wstatus!==''?'Pendente':'Registrado')) ?></span></td><td><?= !empty($pedido['criado_em'])?date('d/m H:i',strtotime($pedido['criado_em'])):'—' ?></td></tr>
        <?php endforeach; ?></tbody></table></div><?php else: ?><div class="empty">Ainda não há pedidos registrados.</div><?php endif; ?>
        </article>
        <article class="panel"><div class="panel-head"><h2><span>◉</span>Notificações</h2><a class="period" href="index.php?controller=whatsapp&action=config">Configurar →</a></div>
        <?php if ($notificacoes): foreach ($notificacoes as $n): ?>
            <div class="notice"><div class="notice-icon">🟢</div><div><b><?= ($n['whatsapp_status']??'')==='enviado'?'Aviso de pedido enviado':((($n['whatsapp_status']??'')==='falhou')?'Falha no aviso WhatsApp':'Novo pedido registrado') ?></b><small>Pedido #<?= (int)$n['id'] ?> · <?= htmlspecialchars($n['cliente_nome'],ENT_QUOTES,'UTF-8') ?><br><?= $fmt($n['total']) ?><?= !empty($n['whatsapp_erro'])?' · '.htmlspecialchars($n['whatsapp_erro'],ENT_QUOTES,'UTF-8'):'' ?></small></div><time><?= date('H:i',strtotime($n['criado_em'])) ?></time></div>
        <?php endforeach; else: ?><div class="empty">As notificações de novos pedidos aparecerão aqui.</div><?php endif; ?>
        </article>
    </section>
</div>
</main>
</div>
</body>
</html>
