<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Astiri</title>
</head>
<body>
    <h1>Astiri</h1>
    <p>Hello, Welcom to Astiri, please enter the URI you want to request.</p>
    <form action="index.php" method="post">
        <input type="text" name="uri"/>
        <input type="submit" name="request" value="Request"/> 
    </form>
    <p>
        <?php
        include 'ChildRequest.php';
        $astiri = new ChildRequest(5);
        if (array_key_exists('request', $_POST)){
            if ($_POST["uri"] == ""){
                echo "Cannot request empty URI.";
            } else{
                $res = $astiri->childProcess($_POST["uri"]);
                echo "Response: ". $res;
                $astiri->showTimeArray();
            }
        } 
        ?>
    </p>
    <form action="index.php" method="post">
        <input type="submit" name="uri_lst" value="URI you have requested"/>
    </form>
    <form action="index.php" method="post">
        <input type="submit" name="uri_lst" value="URI you have requested"/>
    </form>

</body>
</html>