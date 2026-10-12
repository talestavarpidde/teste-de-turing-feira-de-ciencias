<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
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
        }
    ?>
            <div>
            <?php echo $enunciado; ?>
        </div>
</body>
</html>