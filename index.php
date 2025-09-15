<?php
// Caminho do arquivo JSON
$file = __DIR__ . '/tarefas.json';

// Se o arquivo não existe, cria um array vazio e salva
if (!file_exists($file)) {
    file_put_contents($file, json_encode([], JSON_PRETTY_PRINT), LOCK_EX);
}

// Lê o JSON e transforma em array PHP
$tarefas = json_decode(file_get_contents($file), true);

// Tratar requisições POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $titulo = trim($_POST['titulo'] ?? '');
        if ($titulo !== '') {
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
        $id = intval($_POST['id'] ?? 0);
        foreach ($tarefas as $k => $t) {
            if ($t['id'] == $id) {
                unset($tarefas[$k]);
                break;
            }
        }
        $tarefas = array_values($tarefas);
        file_put_contents($file, json_encode($tarefas, JSON_PRETTY_PRINT), LOCK_EX);
    }

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
html, body {
    height: 100%;
    margin: 0;
    padding: 0;
}

body {
    font-family: Arial, sans-serif;
    background-image: url('https://images.wallpapers.com/wallpapers/lo-fi-background-digital-art-1eal6irnz09bvq45.jpg'); 
    background-size: cover;
    background-repeat: no-repeat;
    background-position: center center;
    display: flex;
    justify-content: center;
    padding: 40px 0;
}

.container {
    background: rgba(245, 222, 179, 0.85); /* leve tom marrom claro, sem perder visibilidade */
    padding: 20px;
    border-radius: 12px;
    width: 90%;
    max-width: 700px;
    box-shadow: 0 0 10px rgba(0,0,0,0.3);
}

h1 {
    text-align: center;
    margin-bottom: 20px;
    color: #4b2e1e; /* tom marrom mais escuro */
}

input[type="text"] {
    padding: 10px;
    width: 70%;
    border-radius: 12px; /* bordas arredondadas */
    border: 1px solid #ccc;
    outline: none;
}

button {
    padding: 10px 15px;
    border-radius: 12px;
    border: none;
    cursor: pointer;
}

button.delete {
    background:#e74c3c;
    color:white;
}

.done { 
    text-decoration: line-through; 
    color: gray;
}

ul { 
    list-style: none; 
    padding: 0; 
    margin-top: 20px;
}

li { 
    margin: 8px 0; 
    display:flex; 
    align-items:center; 
    gap:8px; 
}

.task-title { flex:1; }
form.inline { display:inline; margin:0; }
</style>
</head>
<body>
<div class="container">
    <h1>MEU CHECKLIST</h1>

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
        <li>Nenhuma tarefa ainda...</li>
    <?php else: ?>
        <?php foreach ($tarefas as $t): ?>
            <li>
                <form class="inline" method="post">
                    <input type="hidden" name="action" value="toggle">
                    <input type="hidden" name="id" value="<?= intval($t['id']) ?>">
                    <input type="checkbox" name="done" onchange="this.form.submit()" <?= ($t['status'] === 'concluída') ? 'checked' : '' ?>>
                </form>

                <span class="task-title <?= ($t['status'] === 'concluída') ? 'done' : '' ?>">
                    <?= htmlspecialchars($t['titulo'], ENT_QUOTES, 'UTF-8') ?>
                </span>

                <form class="inline" method="post" onsubmit="return confirm('Remover essa tarefa?');">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" value="<?= intval($t['id']) ?>">
                    <button class="delete" type="submit">Remover</button>
                </form>
            </li>
        <?php endforeach; ?>
    <?php endif; ?>
    </ul>
</div>
</body>
</html>
