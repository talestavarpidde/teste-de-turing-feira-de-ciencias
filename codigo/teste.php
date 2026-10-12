<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="stile.css">
    <title>pergunta</title>
</head>
<body>
    <?php
     require_once 'conexao.php';
     $sql = "SELECT * FROM pergunta ORDER BY RAND() LIMIT 10";
     $perguntas = mysqli_query($conexao, $sql);
      while ($pergunta = mysqli_fetch_array($perguntas)) {
            $idpergunta = $pergunta['idpergunta'];
            $enunciado = $pergunta['enunciado'];
        $sql2 = "SELECT * FROM resposta WHERE tipo_resposta = 2 AND idpergunta = $idpergunta ORDER BY RAND() LIMIT 3";
        $respostas = mysqli_query($conexao, $sql2);
        while ($resposta = mysqli_fetch_array($respostas)) {
        $idresposta = $resposta['idresposta'];
        $alternativa = $resposta['resposta'];
        
            echo"<div class='questão'>";
                echo"<div class='pergunta'>";
                echo"<p>$enunciado</p>";
                echo"</div>";
                echo"<div class='alternativas'>";
                echo"$alternativa";
                echo"</div>";
            echo"</div>";
        }
      }
    ?>
</body>
</html>