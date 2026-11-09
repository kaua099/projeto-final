<?php
require_once __DIR__ . '/../config/db.php';

class WhatsAppController
{
    private PDO $pdo;
    public function __construct() { $this->pdo = Database::getConnection(); }

    public function config(): void
    {
        $this->checkAdmin();
        $saved = false; $erro = '';
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $numero = preg_replace('/\D+/', '', trim($_POST['numero'] ?? ''));
            $phoneId = trim($_POST['cloud_phone_number_id'] ?? '');
            $token = trim($_POST['cloud_access_token'] ?? '');
            $versao = trim($_POST['cloud_api_version'] ?? 'v23.0');
            $template = trim($_POST['cloud_template_name'] ?? '');
            $idioma = trim($_POST['cloud_template_language'] ?? 'pt_BR');
            $ativo = isset($_POST['cloud_ativo']) ? 1 : 0;
            if ($numero !== '' && strlen($numero) < 10) $erro = 'Informe o WhatsApp do administrador com DDD e código do país.';
            elseif ($phoneId !== '' && !ctype_digit($phoneId)) $erro = 'O Phone Number ID deve conter apenas números.';
            else {
                // Token em branco mantém o token já salvo.
                $sql = "UPDATE whatsapp_config SET numero=:numero, cloud_phone_number_id=:phone_id,
                        cloud_api_version=:versao, cloud_template_name=:template,
                        cloud_template_language=:idioma, cloud_ativo=:ativo";
                $params = [':numero'=>$numero, ':phone_id'=>$phoneId, ':versao'=>$versao, ':template'=>$template, ':idioma'=>$idioma, ':ativo'=>$ativo];
                if ($token !== '') { $sql .= ', cloud_access_token=:token'; $params[':token'] = $token; }
                $sql .= ' WHERE id=1';
                $stmt = $this->pdo->prepare($sql); $stmt->execute($params); $saved = true;
            }
        }
        $config = $this->pdo->query("SELECT numero, cloud_phone_number_id, cloud_api_version,
                                            cloud_template_name, cloud_template_language,
                                            cloud_ativo, (cloud_access_token <> '') AS token_configurado
                                     FROM whatsapp_config WHERE id=1")->fetch(PDO::FETCH_ASSOC) ?: [];
        $numero = $config['numero'] ?? '';
        require_once __DIR__ . '/../views/whatsapp_config.php';
    }

    private function checkAdmin(): void
    {
        if (!isset($_SESSION['usuario_id'])) { header('Location: index.php?controller=auth&action=form'); exit; }
        if (($_SESSION['perfil'] ?? '') !== 'admin') { header('Location: lojaview.php'); exit; }
    }
}
