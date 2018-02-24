<?php
    if (isset($_ENV["DEBUG"]) && $_ENV["DEBUG"] == 1) {
        $db= new mysqli('db', 'root', 'root', 'cryptospel');
    } else {
        $db = new mysqli('127.0.0.1', 'root', 'root', 'cryptospel');
    }

    $text = '';
    if (isset($_POST['plus'], $_POST['coin'])) {
        $query = "SELECT * FROM coins WHERE id = '".$_POST['coin']."'";
        $result = $db->query($query);

        if (!$result) {
            die('coin not found');
        }
        $coin = $result->fetch_assoc();

        $query = "INSERT INTO 
                coin_price_history (`coin_id`, `price`, `news`)
                VALUES ('".$coin['id']."', '".$coin['value']."', '".$_POST['news']."')";

        if ($db->query($query)){
            $text = 'Waarde aangepast.';
        }

        // coin update
        $query = "UPDATE coins
                SET 
                 value = GREATEST(value ".$_POST['plus'].", 5)
                 where id = '".$_POST['coin']."'";

        if ($db->query($query)){
            $text = 'Waarde aangepast.';
        }

    }

    // coins ophalen
    $query = 'SELECT * FROM coins';
    $result = $db->query($query);

    $coinData = [];
    while ($row = $result->fetch_assoc()) {
        $name = $row['name'];
        $value = $row['value'];
        $id = $row['id'];

        $coinData[] = $row;
    }
?>

<!DOCTYPE html>
<html>
<head>
    <title></title>
    <meta charset="UTF-8">
    <link href='https://fonts.googleapis.com/css?family=Montserrat:400,700' rel='stylesheet' type='text/css'>
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">

    <style type="text/css">
        html {
            font-family: Montserrat, sans-serif;
        }

        select {
            padding: 12px 10px;
        }

        textarea {
            width: 100%;
            box-sizing: border-box;
            appearance: none;
            height: 150px;

        }

        label {
            display: block;
            text-transform: uppercase;
            font-weight: bold;
            margin-top: 20px;
        }

        label.option {
            display: block;
            margin: 5px 0;
            text-transform: none;
            font-weight: normal;
        }

        input[type=submit] {
            appearance: none;
            border-radius: 5px;
            background: #7D29F4;
            color: white;
            padding: 12px 10px;
            outline: none;
            width: 25%;
            margin: 5px;
            box-sizing: border-box;
            display: inline-block;
            font-size: 18px;
            font-weight: bold;
            border: 0;
        }

         input[type=text] {
            width: 100%;
            padding: 12px 10px;
            border: 2px solid gray;
            box-sizing: border-box;
            appearance: none;
         }

        input[type=radio] {
            display: inline-block;
            padding: 10px;
            margin-right: 15px;
        }

        table {
            border-collapse: collapse;
            margin-bottom: 30px;
            width: 100%;
        }

        table td {
            padding: 10px 0;
        }
        table td:first-child {
            padding-right: 20px;
        }
        table thead tr {
            font-weight: bold;
            text-transform: uppercase;
            padding: 10px;
            border-bottom: 2px solid gray;
        }
    </style>
</head>
<body>

    <h1>Coin overzicht</h1>

    <form action="" method="POST">
        <table>
            <thead>
                <tr>
                    <td>Coin</td>
                    <td>Value</td>
                </tr>
            </thead>
            <tbody>
                <?php
                    foreach ($coinData as $coin) {
                        echo '<tr><td><label class="option"><input type="radio" name="coin" value="'.$coin['id'].'">'.$coin['name'].'</label></td><td>€ '.$coin['value'].'</td></tr>';
                    }
                ?>
            </tbody>
        </table>

        <h1><?php echo $text; ?></h1>

        <label>Add newfact to this change</label>
        <input type="text" name="news" placeholder="Optional">

        <label>Stijgen</label>
        <input type="submit" name="plus" value="+ 5">
        <input type="submit" name="plus" value="+ 10">
        <input type="submit" name="plus" value="+ 15">
        <input type="submit" name="plus" value="+ 20">
        <input type="submit" name="plus" value="+ 25">
        <input type="submit" name="plus" value="+ 30">
        <input type="submit" name="plus" value="+ 35">
        <input type="submit" name="plus" value="+ 40">
        <input type="submit" name="plus" value="+ 45">
        <input type="submit" name="plus" value="+ 50">
        <input type="submit" name="plus" value="+ 60">
        <input type="submit" name="plus" value="+ 70">
        <input type="submit" name="plus" value="+ 80">
        <input type="submit" name="plus" value="+ 90">
        <input type="submit" name="plus" value="+ 100">

        <label>Dalen</label>
        <input type="submit" name="plus" value="- 5">
        <input type="submit" name="plus" value="- 10">
        <input type="submit" name="plus" value="- 15">
        <input type="submit" name="plus" value="- 20">
        <input type="submit" name="plus" value="- 25">
        <input type="submit" name="plus" value="- 30">
        <input type="submit" name="plus" value="- 35">
        <input type="submit" name="plus" value="- 40">
        <input type="submit" name="plus" value="- 45">
        <input type="submit" name="plus" value="- 50">
        <input type="submit" name="plus" value="- 60">
        <input type="submit" name="plus" value="- 70">
        <input type="submit" name="plus" value="- 80">
        <input type="submit" name="plus" value="- 90">
        <input type="submit" name="plus" value="- 100">

        <label>Gelijk</label>
        <input type="submit" name="plus" value="+ 0">
        

    </form>
</body>
</html>
