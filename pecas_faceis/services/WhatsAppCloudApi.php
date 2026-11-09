<?php
require_once __DIR__ . '/../config/db.php';

class WhatsAppCloudApi
{
    public static function notificarNovoPedido(int $pedidoId): array
    {
        $pdo = Database::getConnection();
        $config = $pdo->query("SELECT numero, cloud_phone_number_id, cloud_access_token,
                                      cloud_api_version, cloud_template_name, cloud_template_language,
                                      cloud_ativo
                               FROM whatsapp_config WHERE id = 1")->fetch(PDO::FETCH_ASSOC);

        if (!$config || (int)$config['cloud_ativo'] !== 1 ||
            empty($config['cloud_phone_number_id']) || empty($config['cloud_access_token']) ||
            empty($config['cloud_template_name']) || empty($config['numero'])) {
            return ['ok' => false, 'erro' => 'Integração do WhatsApp Cloud API não configurada ou desativada.'];
        }

        $stmt = $pdo->prepare("SELECT id, cliente_nome, cliente_telefone, cliente_email,
                                      forma_pagamento, total, criado_em
                               FROM pedidos WHERE id = :id");
        $stmt->execute([':id' => $pedidoId]);
        $pedido = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$pedido) return ['ok' => false, 'erro' => 'Pedido não encontrado.'];

        $stmt = $pdo->prepare("SELECT produto_nome, quantidade, preco_unitario, subtotal
                               FROM pedido_itens WHERE pedido_id = :id ORDER BY id");
        $stmt->execute([':id' => $pedidoId]);
        $itens = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $linhas = [];
        foreach ($itens as $item) {
            $linhas[] = $item['quantidade'] . 'x ' . $item['produto_nome'] . ' (R$ ' . number_format((float)$item['subtotal'], 2, ',', '.') . ')';
        }
        $resumoItens = implode('; ', $linhas);
        if (mb_strlen($resumoItens, 'UTF-8') > 900) $resumoItens = mb_substr($resumoItens, 0, 897, 'UTF-8') . '...';

        // O modelo aprovado na Meta deve ter 6 variáveis {{1}} ... {{6}} no corpo.
        $parametros = [
            (string)$pedido['id'],
            (string)$pedido['cliente_nome'],
            (string)$pedido['cliente_telefone'],
            (string)$pedido['forma_pagamento'],
            $resumoItens,
            'R$ ' . number_format((float)$pedido['total'], 2, ',', '.')
        ];

        $versao = trim($config['cloud_api_version'] ?: 'v23.0');
        if ($versao[0] !== 'v') $versao = 'v' . $versao;
        $url = 'https://graph.facebook.com/' . rawurlencode($versao) . '/' .
               rawurlencode($config['cloud_phone_number_id']) . '/messages';

        $payload = [
            'messaging_product' => 'whatsapp',
            'recipient_type' => 'individual',
            'to' => preg_replace('/\D+/', '', $config['numero']),
            'type' => 'template',
            'template' => [
                'name' => $config['cloud_template_name'],
                'language' => ['code' => $config['cloud_template_language'] ?: 'pt_BR'],
                'components' => [[
                    'type' => 'body',
                    'parameters' => array_map(static function ($valor) {
                        return ['type' => 'text', 'text' => (string)$valor];
                    }, $parametros)
                ]]
            ]
        ];

        if (!function_exists('curl_init')) return ['ok' => false, 'erro' => 'A extensão cURL do PHP não está habilitada.'];
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $config['cloud_access_token'],
                'Content-Type: application/json'
            ],
            CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT => 25
        ]);
        $resposta = curl_exec($ch);
        $http = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlErro = curl_error($ch);
        curl_close($ch);

        if ($resposta === false) return ['ok' => false, 'erro' => 'Falha de conexão: ' . $curlErro];
        $json = json_decode($resposta, true);
        if ($http < 200 || $http >= 300 || empty($json['messages'][0]['id'])) {
            $erro = $json['error']['message'] ?? ('Erro HTTP ' . $http);
            return ['ok' => false, 'erro' => $erro];
        }
        return ['ok' => true, 'message_id' => $json['messages'][0]['id']];
    }
}
