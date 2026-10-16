<?php
session_start();

require_once 'conexao.php';

/*
 * O parâmetro "novo=1" inicia explicitamente uma nova sequência.
 * Ele é usado somente na tela final; assim, voltar para teste.php depois
 * da décima resposta não sorteia perguntas novas automaticamente.
 */
if (isset($_GET['novo']) && $_GET['novo'] === '1') {
    unset(
        $_SESSION['perguntas_teste'],
        $_SESSION['indice_pergunta'],
        $_SESSION['teste_finalizado'],
        $_SESSION['resultados_teste']
    );
}

/*
 * Depois da décima resposta, mantemos a marcação de finalização na sessão.
 * Isso impede que o link das telas de acerto/erro inicie outro teste sozinho.
 */
/*
 * A sessão guarda a sequência sorteada para que ela não mude a cada
 * atualização da página. O índice controla qual pergunta será exibida.
 */
if (!isset($_SESSION['perguntas_teste'])) {
    $sql_perguntas = "SELECT idpergunta
                      FROM pergunta
                      ORDER BY RAND()
                      LIMIT 1";
    $resultado_perguntas = mysqli_query($conexao, $sql_perguntas);

    if ($resultado_perguntas === false) {
        die('Não foi possível carregar as perguntas do teste.');
    }

    $_SESSION['perguntas_teste'] = [];

    while ($linha = mysqli_fetch_assoc($resultado_perguntas)) {
        $_SESSION['perguntas_teste'][] = (int) $linha['idpergunta'];
    }

    if (count($_SESSION['perguntas_teste']) < 1) {
        die('Não há pelo menos 4 perguntas cadastradas para iniciar o teste.');
    }

    $_SESSION['indice_pergunta'] = 0;
    $_SESSION['resultados_teste'] = [];
}

/*
 * Compatibilidade com sessões antigas: partidas iniciadas antes da criação
 * de resultados_teste não possuem essa chave. Inicializamos um array vazio
 * para evitar warnings e erros ao finalizar a partida.
 */
if (
    !isset($_SESSION['resultados_teste']) ||
    !is_array($_SESSION['resultados_teste'])
) {
    $_SESSION['resultados_teste'] = [];
}

/*
 * Quando uma alternativa é escolhida, o teste.php registra o resultado na
 * sessão e avança para a próxima pergunta. O formulário usa POST para que
 * uma atualização da página não repita a resposta automaticamente.
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['idresposta'])) {
    $idresposta = (int) $_POST['idresposta'];
    $indice_atual = (int) $_SESSION['indice_pergunta'];
    $idpergunta_atual = $_SESSION['perguntas_teste'][$indice_atual];

    $sql_resposta = "SELECT resposta, tipo_resposta
                     FROM resposta
                     WHERE idresposta = $idresposta
                     AND idpergunta = $idpergunta_atual
                     LIMIT 1";
    $resultado_resposta = mysqli_query($conexao, $sql_resposta);

    if ($resultado_resposta === false) {
        die('Não foi possível validar a resposta escolhida.');
    }

    $resposta_escolhida = mysqli_fetch_assoc($resultado_resposta);

    if ($resposta_escolhida === null) {
        die('A resposta escolhida não pertence à pergunta atual.');
    }

    $sql_enunciado = "SELECT enunciado
                      FROM pergunta
                      WHERE idpergunta = $idpergunta_atual
                      LIMIT 1";
    $resultado_enunciado = mysqli_query($conexao, $sql_enunciado);

    if ($resultado_enunciado === false) {
        die('Não foi possível carregar o enunciado da pergunta.');
    }

    $pergunta_respondida = mysqli_fetch_assoc($resultado_enunciado);

    $sql_ia = "SELECT resposta
               FROM resposta
               WHERE idpergunta = $idpergunta_atual
               AND tipo_resposta = 2
               LIMIT 1";
    $resultado_ia = mysqli_query($conexao, $sql_ia);

    if ($resultado_ia === false) {
        die('Não foi possível carregar a resposta da IA.');
    }

    $resposta_ia = mysqli_fetch_assoc($resultado_ia);

    if ($pergunta_respondida === null || $resposta_ia === null) {
        die('Não foi possível montar o resultado da pergunta.');
    }

    $_SESSION['resultados_teste'][] = [
        'pergunta' => $pergunta_respondida['enunciado'],
        'resposta_escolhida' => $resposta_escolhida['resposta'],
        'resposta_ia' => $resposta_ia['resposta'],
        'acertou' => (int) $resposta_escolhida['tipo_resposta'] === 2
    ];

    /*
     * O tipo 2 representa a IA e o tipo 1 representa uma resposta humana.
     * O avanço ocorre uma única vez, no momento em que a alternativa é aceita.
     */
    $_SESSION['indice_pergunta']++;

    if ($_SESSION['indice_pergunta'] >= count($_SESSION['perguntas_teste'])) {
        $_SESSION['teste_finalizado'] = true;
    }

    // Evita que o navegador reenvie a mesma resposta ao atualizar a página.
    header('Location: teste.php');
    exit;
}

/*
 * Depois da décima pergunta, exibimos o resumo da partida. A resposta da IA
 * é revelada somente aqui, nunca durante as perguntas.
 */
if (
    isset($_SESSION['teste_finalizado']) &&
    $_SESSION['teste_finalizado'] === true
) {
    $resultados = is_array($_SESSION['resultados_teste'])
        ? $_SESSION['resultados_teste']
        : [];
    $acertos = count(array_filter($resultados, function ($resultado) {
        return $resultado['acertou'];
    }));
    ?>
    <!DOCTYPE html>
    <html lang="pt-br">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <link rel="stylesheet" href="style.css">
        <title>Resultado do teste</title>
    </head>
    <body class="pagina-resultados">
        <main class="resultados-container">
            <header class="resultados-cabecalho">
                <p class="inicio-etiqueta">Fim do desafio</p>
                <h1>Resultado do teste</h1>
                <p class="resumo-resultado">
                    Você acertou <strong><?php echo $acertos; ?> de <?php echo count($resultados); ?></strong> perguntas.
                </p>
            </header>

            <section class="lista-resultados" aria-label="Respostas do teste">
                <?php foreach ($resultados as $numero => $resultado): ?>
                    <article class="cartao-resultado">
                        <div class="titulo-resultado">
                            <h2>Pergunta <?php echo $numero + 1; ?></h2>
                            <span class="selo-resultado <?php echo $resultado['acertou'] ? 'selo-acerto' : 'selo-erro'; ?>">
                                <?php echo $resultado['acertou'] ? 'Acertou' : 'Errou'; ?>
                            </span>
                        </div>
                        <p class="enunciado-resultado"><strong><?php echo htmlspecialchars($resultado['pergunta'], ENT_QUOTES, 'UTF-8'); ?></strong></p>
                        <div class="respostas-resultado">
                            <p><span>Sua resposta</span><?php echo htmlspecialchars($resultado['resposta_escolhida'], ENT_QUOTES, 'UTF-8'); ?></p>
                            <p><span>Resposta da IA</span><?php echo htmlspecialchars($resultado['resposta_ia'], ENT_QUOTES, 'UTF-8'); ?></p>
                        </div>
                    </article>
                <?php endforeach; ?>
            </section>

            <nav class="acoes-resultados" aria-label="Ações">
                <a class="botao-principal" href="teste.php?novo=1">Fazer outro teste <span aria-hidden="true">&rarr;</span></a>
                <a class="link-secundario" href="index.php">Voltar para a página inicial</a>
            </nav>
        </main>
    </body>
    </html>
    <?php
    exit;
}

$indice_atual = (int) $_SESSION['indice_pergunta'];
$idpergunta_atual = $_SESSION['perguntas_teste'][$indice_atual];

$sql_pergunta = "SELECT enunciado
                 FROM pergunta
                 WHERE idpergunta = $idpergunta_atual
                 LIMIT 1";
$resultado_pergunta = mysqli_query($conexao, $sql_pergunta);

if ($resultado_pergunta === false) {
    die('Não foi possível carregar a pergunta atual.');
}

$pergunta_atual = mysqli_fetch_assoc($resultado_pergunta);

if ($pergunta_atual === null) {
    die('A pergunta atual não foi encontrada.');
}

$enunciado = htmlspecialchars($pergunta_atual['enunciado'], ENT_QUOTES, 'UTF-8');

// Carrega as três respostas humanas da pergunta atual.
$sql_humanas = "SELECT idresposta, resposta
                FROM resposta
                WHERE idpergunta = $idpergunta_atual
                AND tipo_resposta = 1
                ORDER BY RAND()
                LIMIT 3";
$respostas_humanas = mysqli_query($conexao, $sql_humanas);

if ($respostas_humanas === false) {
    die('Não foi possível carregar as respostas humanas.');
}

// Carrega a única resposta da IA da pergunta atual.
$sql_ia = "SELECT idresposta, resposta
           FROM resposta
           WHERE idpergunta = $idpergunta_atual
           AND tipo_resposta = 2
           LIMIT 1";
$resposta_ia = mysqli_query($conexao, $sql_ia);

if ($resposta_ia === false) {
    die('Não foi possível carregar a resposta da IA.');
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style.css">
    <title>Pergunta</title>
</head>
<body>
    <div class="questao">
        <div class="pergunta">
            <p><strong><?php echo $enunciado; ?></strong></p>
        </div>

        <div class="alternativas">
            <?php while ($resposta = mysqli_fetch_assoc($respostas_humanas)): ?>
                <?php
                $idresposta = (int) $resposta['idresposta'];
                $alternativa = htmlspecialchars($resposta['resposta'], ENT_QUOTES, 'UTF-8');
                ?>
                <form method="post" action="teste.php">
                    <input type="hidden" name="idresposta" value="<?php echo $idresposta; ?>">
                    <button type="submit"><?php echo $alternativa; ?></button>
                </form>
            <?php endwhile; ?>
        </div>

        <div class="resposta-certa">
            <?php if ($resposta = mysqli_fetch_assoc($resposta_ia)): ?>
                <?php
                $idresposta = (int) $resposta['idresposta'];
                $alternativa = htmlspecialchars($resposta['resposta'], ENT_QUOTES, 'UTF-8');
                ?>
                <form method="post" action="teste.php">
                    <input type="hidden" name="idresposta" value="<?php echo $idresposta; ?>">
                    <button type="submit"><?php echo $alternativa; ?></button>
                </form>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>