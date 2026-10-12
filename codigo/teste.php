<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="stile.css">
    <title>Pergunta</title>
</head>
<body>
    <?php
    require_once 'conexao.php';

    $sql = "SELECT * FROM pergunta ORDER BY RAND() LIMIT 10";
    $perguntas = mysqli_query($conexao, $sql);

    while ($pergunta = mysqli_fetch_array($perguntas)) {
        $idpergunta = $pergunta['idpergunta'];
        $enunciado = $pergunta['enunciado'];
        echo "<div class='questao'>";
            echo "<div class='pergunta'>";
                echo "<p><strong>$enunciado</strong></p>";
            echo "</div>";
            echo "<div class='alternativas'>";
            $sql2 = "SELECT * FROM resposta WHERE idpergunta = $idpergunta AND tipo_resposta = 2 ORDER BY RAND() LIMIT 3";
            $respostas = mysqli_query($conexao, $sql2);
            while ($resposta = mysqli_fetch_array($respostas)) {
                $idresposta = $resposta['idresposta'];
                $alternativa = $resposta['resposta'];
                $tipo = $resposta['tipo_resposta'];
                    echo "<a href='errou.php'><p>$alternativa</p></a>";
            }
            echo "</div>";
        echo "</div>";
    }
    ?>
</body>
</html>