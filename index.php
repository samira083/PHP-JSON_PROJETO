<?php
// Caminho do arquivo JSON
$file = __DIR__ . '/tarefas.json';

// Se o arquivo não existe, cria um array vazio e salva
if (!file_exists($file)) {
    file_put_contents($file, json_encode([], JSON_PRETTY_PRINT), LOCK_EX);
}

// 1) Lê o JSON e transforma em array PHP
$tarefas = json_decode(file_get_contents($file), true);

// 2) Tratar requisições POST (adicionar, alternar status, deletar)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        // Adiciona nova tarefa
        $titulo = trim($_POST['titulo'] ?? '');
        if ($titulo !== '') {
            // Gera id seguro (max id + 1) para não duplicar quando remover itens
            $maxId = 0;
            foreach ($tarefas as $t) {
                if (isset($t['id']) && $t['id'] > $maxId) $maxId = $t['id'];
            }
            $nova = [
                'id' => $maxId + 1,
                'titulo' => $titulo,
                'status' => 'pendente',
                'data' => date('Y-m-d')
            ];
            $tarefas[] = $nova;
            file_put_contents($file, json_encode($tarefas, JSON_PRETTY_PRINT), LOCK_EX);
        }
    } elseif ($action === 'toggle') {
        // Alterna status com base na presença do campo 'done' (checkbox envia só quando checado)
        $id = intval($_POST['id'] ?? 0);
        $novoStatus = isset($_POST['done']) ? 'concluída' : 'pendente';
        foreach ($tarefas as &$t) {
            if ($t['id'] == $id) {
                $t['status'] = $novoStatus;
                break;
            }
        }
        unset($t);
        file_put_contents($file, json_encode($tarefas, JSON_PRETTY_PRINT), LOCK_EX);
    } elseif ($action === 'delete') {
        // Remove pelo id
        $id = intval($_POST['id'] ?? 0);
        foreach ($tarefas as $k => $t) {
            if ($t['id'] == $id) {
                unset($tarefas[$k]);
                break;
            }
        }
        // reindexa o array pra ficar bonito
        $tarefas = array_values($tarefas);
        file_put_contents($file, json_encode($tarefas, JSON_PRETTY_PRINT), LOCK_EX);
    }

    // Redireciona para evitar reenvio do formulário (boa prática)
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}
?>
<!doctype html>
<html lang="pt-br">
<head>
<meta charset="utf-8">
<title>Checklist simples</title>
<style>
    body { font-family: Arial, sans-serif; max-width:700px; margin:20px auto; }
    .done { text-decoration: line-through; color: gray; }
    ul { list-style: none; padding: 0; }
    li { margin: 8px 0; display:flex; align-items:center; gap:8px; }
    .task-title { flex:1; }
    form.inline { display:inline; margin:0; }
    button.delete { background:#e74c3c; color:white; border:0; padding:4px 8px; cursor:pointer; border-radius:4px; }
    input[type="text"]{ padding:6px; width:70%; }
</style>
</head>
<body>
    <h1>Meu Checklist</h1>

    <!-- Formulário para adicionar tarefa -->
    <form method="post">
        <input type="hidden" name="action" value="add">
        <input type="text" name="titulo" placeholder="Digite uma tarefa" required>
        <button type="submit">Adicionar</button>
    </form>

    <hr>

    <!-- Lista de tarefas -->
    <ul>
    <?php if (empty($tarefas)): ?>
        <li>Nenhuma tarefa ainda.</li>
    <?php else: ?>
        <?php foreach ($tarefas as $t): ?>
            <li>
                <!-- Form para alternar status (checkbox) -->
                <form class="inline" method="post">
                    <input type="hidden" name="action" value="toggle">
                    <input type="hidden" name="id" value="<?= intval($t['id']) ?>">
                    <!-- o checkbox só envia 'done' se estiver marcado -->
                    <input type="checkbox" name="done" onchange="this.form.submit()" <?= ($t['status'] === 'concluída') ? 'checked' : '' ?>>
                </form>

                <!-- Título (escapado para segurança) -->
                <span class="task-title <?= ($t['status'] === 'concluída') ? 'done' : '' ?>">
                    <?= htmlspecialchars($t['titulo'], ENT_QUOTES, 'UTF-8') ?>
                </span>

                <!-- Botão de deletar -->
                <form class="inline" method="post" onsubmit="return confirm('Remover essa tarefa?');">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" value="<?= intval($t['id']) ?>">
                    <button class="delete" type="submit">Remover</button>
                </form>
            </li>
        <?php endforeach; ?>
    <?php endif; ?>
    </ul>
</body>
</html>
