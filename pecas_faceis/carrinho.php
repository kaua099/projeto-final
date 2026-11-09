<?php
session_start();
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/services/WhatsAppCloudApi.php';

if (!isset($_SESSION['carrinho']) || !is_array($_SESSION['carrinho'])) $_SESSION['carrinho'] = [];
$pdo = Database::getConnection();
$erroCheckout = '';
$pedidoFinalizado = null;
$whatsappEnviado = false;

function voltarCarrinho(): void { header('Location: carrinho.php'); exit; }
function dinheiro(float $valor): string { return 'R$ ' . number_format($valor, 2, ',', '.'); }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = $_POST['acao'] ?? '';
    $id = (int)($_POST['produto_id'] ?? 0);
    if ($acao === 'adicionar' && $id > 0) {
        $stmt = $pdo->prepare('SELECT id FROM carros WHERE id = :id AND ativo = 1');
        $stmt->execute([':id' => $id]);
        if ($stmt->fetch()) {
            $qtd = max(1, min(20, (int)($_POST['quantidade'] ?? 1)));
            $_SESSION['carrinho'][$id] = min(20, (int)($_SESSION['carrinho'][$id] ?? 0) + $qtd);
        }
        voltarCarrinho();
    } elseif ($acao === 'atualizar' && $id > 0) {
        $qtd = (int)($_POST['quantidade'] ?? 1);
        if ($qtd <= 0) unset($_SESSION['carrinho'][$id]); else $_SESSION['carrinho'][$id] = min(20, $qtd);
        voltarCarrinho();
    } elseif ($acao === 'remover' && $id > 0) {
        unset($_SESSION['carrinho'][$id]); voltarCarrinho();
    } elseif ($acao === 'limpar') {
        $_SESSION['carrinho'] = []; voltarCarrinho();
    } elseif ($acao === 'finalizar') {
        $nome = trim($_POST['cliente_nome'] ?? '');
        $telefone = trim($_POST['cliente_telefone'] ?? '');
        $email = trim($_POST['cliente_email'] ?? '');
        $pagamento = trim($_POST['forma_pagamento'] ?? '');
        $formas = ['Pix', 'Dinheiro', 'Cartão de crédito', 'Cartão de débito'];

        if (!$nome || !$telefone || !in_array($pagamento, $formas, true) || empty($_SESSION['carrinho'])) {
            $erroCheckout = 'Confira seus dados, a forma de pagamento e os produtos do carrinho.';
        } else {
            $ids = array_map('intval', array_keys($_SESSION['carrinho']));
            $marks = implode(',', array_fill(0, count($ids), '?'));
            $stmt = $pdo->prepare("SELECT id, nome, marca, modelo, ano, preco, preco_atual FROM carros WHERE ativo = 1 AND id IN ($marks)");
            $stmt->execute($ids);
            $produtos = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $itensPedido = [];
            $totalPedido = 0.0;
            foreach ($produtos as $produto) {
                $qtd = max(1, min(20, (int)($_SESSION['carrinho'][$produto['id']] ?? 1)));
                $preco = (float)($produto['preco_atual'] ?: $produto['preco']);
                $subtotal = $preco * $qtd;
                $itensPedido[] = ['produto' => $produto, 'quantidade' => $qtd, 'preco' => $preco, 'subtotal' => $subtotal];
                $totalPedido += $subtotal;
            }
            if (!$itensPedido || count($itensPedido) !== count($ids)) {
                $erroCheckout = 'Um ou mais produtos não estão mais disponíveis. Atualize seu carrinho.';
            } else {
                try {
                    $pdo->beginTransaction();
                    $stmt = $pdo->prepare("INSERT INTO pedidos (cliente_nome, cliente_telefone, cliente_email, forma_pagamento, total, status)
                                           VALUES (:nome, :telefone, :email, :pagamento, :total, 'novo')");
                    $stmt->execute([':nome'=>$nome, ':telefone'=>$telefone, ':email'=>($email ?: null), ':pagamento'=>$pagamento, ':total'=>$totalPedido]);
                    $pedidoId = (int)$pdo->lastInsertId();
                    $insertItem = $pdo->prepare("INSERT INTO pedido_itens (pedido_id, carro_id, produto_nome, quantidade, preco_unitario, subtotal)
                                                  VALUES (:pedido, :carro, :nome, :qtd, :preco, :subtotal)");
                    foreach ($itensPedido as $linha) {
                        $p = $linha['produto'];
                        $insertItem->execute([':pedido'=>$pedidoId, ':carro'=>(int)$p['id'], ':nome'=>$p['nome'], ':qtd'=>$linha['quantidade'], ':preco'=>$linha['preco'], ':subtotal'=>$linha['subtotal']]);
                    }
                    $pdo->commit();
                    $_SESSION['carrinho'] = [];
                    $resultado = WhatsAppCloudApi::notificarNovoPedido($pedidoId);
                    $whatsappEnviado = $resultado['ok'];
                    $stmt = $pdo->prepare("UPDATE pedidos SET whatsapp_status = :status, whatsapp_erro = :erro, whatsapp_message_id = :msg WHERE id = :id");
                    $stmt->execute([':status'=>$whatsappEnviado ? 'enviado' : 'falhou', ':erro'=>$whatsappEnviado ? null : ($resultado['erro'] ?? 'Falha não identificada'), ':msg'=>$resultado['message_id'] ?? null, ':id'=>$pedidoId]);
                    $pedidoFinalizado = ['id'=>$pedidoId, 'total'=>$totalPedido];
                } catch (Throwable $e) {
                    if ($pdo->inTransaction()) $pdo->rollBack();
                    $erroCheckout = 'Não foi possível registrar o pedido. Tente novamente. Detalhe: ' . $e->getMessage();
                }
            }
        }
    }
}

$itens = [];
$total = 0.0;
if (!empty($_SESSION['carrinho'])) {
    $ids = array_map('intval', array_keys($_SESSION['carrinho']));
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $stmt = $pdo->prepare("SELECT id, nome, marca, modelo, ano, preco, preco_atual FROM carros WHERE ativo = 1 AND id IN ($placeholders)");
    $stmt->execute($ids);
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $item) {
        $preco = (float)($item['preco_atual'] ?: $item['preco']);
        $qtd = (int)$_SESSION['carrinho'][$item['id']];
        $item['quantidade'] = $qtd; $item['preco_calculado'] = $preco; $item['subtotal'] = $preco * $qtd;
        $total += $item['subtotal']; $itens[] = $item;
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1"><link rel="icon" type="image/png" href="img/auto.png"><title>Carrinho - Peças Fáceis</title>
<style>
*{box-sizing:border-box}body{margin:0;background:#0a0a0a;color:#eee;font-family:Arial,sans-serif}.wrap{max-width:1000px;margin:35px auto;padding:20px}.top{display:flex;justify-content:space-between;align-items:center;gap:15px;flex-wrap:wrap}h1{color:#fff}.link,.btn{display:inline-block;padding:10px 16px;border-radius:9px;text-decoration:none;border:0;cursor:pointer;font-weight:bold}.link{color:#fff;border:1px solid #555}.btn{background:#c20f0f;color:#fff}.item{display:flex;justify-content:space-between;align-items:center;gap:18px;padding:18px;margin:14px 0;background:#161616;border:1px solid #333;border-radius:12px;flex-wrap:wrap}.item h3{margin:0 0 6px}.muted{color:#aaa}.actions{display:flex;gap:8px;align-items:center}.actions input{width:65px;padding:9px;border-radius:7px;border:1px solid #555;background:#222;color:#fff}.total{text-align:right;font-size:1.35rem;font-weight:bold;margin:25px 0;color:#fff}.checkout{background:#161616;padding:22px;border-radius:12px;border:1px solid #333}.checkout input:not([type=radio]){width:100%;padding:12px;margin:7px 0 14px;background:#222;border:1px solid #444;color:#fff;border-radius:8px}.pagamentos{display:grid;grid-template-columns:repeat(auto-fit,minmax(190px,1fr));gap:10px;margin:10px 0 18px}.pagamento-opcao{display:flex;align-items:center;gap:9px;padding:12px;background:#222;border:1px solid #444;border-radius:8px;cursor:pointer}.pagamento-opcao input{width:auto;margin:0;accent-color:#c20f0f}.checkout label{display:block;margin-top:8px}.empty{text-align:center;padding:60px 15px;background:#161616;border-radius:12px}.danger{background:#444}.hint{color:#aaa;font-size:.9rem;line-height:1.5}.alert{padding:16px;border-radius:10px;margin:18px 0;line-height:1.5}.success{background:#12351f;border:1px solid #238447}.warning{background:#392c12;border:1px solid #9c7626}.error{background:#421818;border:1px solid #a33}
</style></head><body><main class="wrap">
<div class="top"><h1>🛒 Meu carrinho</h1><a class="link" href="lojaview.php">← Continuar comprando</a></div>
<?php if ($pedidoFinalizado): ?>
<div class="alert success"><h2>Pedido #<?= (int)$pedidoFinalizado['id'] ?> registrado com sucesso!</h2><p>Total: <strong><?= dinheiro((float)$pedidoFinalizado['total']) ?></strong></p><p>Seu pedido foi salvo no sistema.</p></div>
<?php if ($whatsappEnviado): ?><div class="alert success">A notificação do novo pedido foi enviada ao WhatsApp da administração.</div><?php else: ?><div class="alert warning">O pedido foi salvo, mas a notificação do WhatsApp não foi enviada. A administração pode consultar o pedido no sistema e revisar a configuração da API.</div><?php endif; ?>
<a class="btn" href="lojaview.php">Voltar para a loja</a>
<?php elseif (!$itens): ?><div class="empty"><h2>Seu carrinho está vazio</h2><p class="muted">Adicione um produto para começar sua compra.</p><a class="btn" href="lojaview.php">Ver produtos</a></div>
<?php else: ?>
<?php if ($erroCheckout): ?><div class="alert error"><?= htmlspecialchars($erroCheckout) ?></div><?php endif; ?>
<?php foreach ($itens as $item): ?><section class="item"><div><h3><?= htmlspecialchars($item['nome']) ?></h3><div class="muted"><?= htmlspecialchars(trim(($item['marca'] ?? '').' '.($item['modelo'] ?? '').' '.($item['ano'] ?? ''))) ?></div><strong><?= dinheiro($item['preco_calculado']) ?></strong><div class="muted">Subtotal: <?= dinheiro($item['subtotal']) ?></div></div>
<div class="actions"><form method="post"><input type="hidden" name="acao" value="atualizar"><input type="hidden" name="produto_id" value="<?= (int)$item['id'] ?>"><input aria-label="Quantidade" type="number" min="0" max="20" name="quantidade" value="<?= (int)$item['quantidade'] ?>"><button class="btn" type="submit">Atualizar</button></form><form method="post"><input type="hidden" name="acao" value="remover"><input type="hidden" name="produto_id" value="<?= (int)$item['id'] ?>"><button class="btn danger" type="submit">Remover</button></form></div></section><?php endforeach; ?>
<div class="total">Total: <?= dinheiro($total) ?></div>
<section class="checkout"><h2>Finalizar pedido</h2><p class="hint">Preencha seus dados. O pedido será registrado no sistema e uma notificação será enviada ao WhatsApp da administração quando a integração estiver configurada. O site ainda não processa pagamentos online.</p>
<form method="post"><input type="hidden" name="acao" value="finalizar"><label>Seu nome</label><input name="cliente_nome" required maxlength="120" placeholder="Nome completo"><label>Telefone</label><input name="cliente_telefone" required maxlength="20" placeholder="(21) 99999-9999"><label>E-mail (opcional)</label><input name="cliente_email" type="email" maxlength="150" placeholder="seuemail@exemplo.com">
<label>Forma de pagamento</label><div class="pagamentos"><label class="pagamento-opcao"><input type="radio" name="forma_pagamento" value="Pix" required> Pix</label><label class="pagamento-opcao"><input type="radio" name="forma_pagamento" value="Dinheiro"> Dinheiro</label><label class="pagamento-opcao"><input type="radio" name="forma_pagamento" value="Cartão de crédito"> Cartão de crédito</label><label class="pagamento-opcao"><input type="radio" name="forma_pagamento" value="Cartão de débito"> Cartão de débito</label></div>
<button class="btn" type="submit">Confirmar pedido</button></form></section>
<form method="post" style="margin-top:15px"><input type="hidden" name="acao" value="limpar"><button class="btn danger" type="submit">Esvaziar carrinho</button></form>
<?php endif; ?></main></body></html>
