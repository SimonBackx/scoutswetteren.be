<?php
    if (!isset($_GET['id'])) {
        die('no coin');
    }

    if (isset($_ENV["DEBUG"]) && $_ENV["DEBUG"] == 1) {
        $db= new mysqli('db', 'root', 'root', 'cryptospel');
    } else {
        $db = new mysqli('127.0.0.1', 'root', 'root', 'cryptospel');
    }

    $coin_id = $_GET['id'];

    // coins ophalen
    $query = 'SELECT * FROM coin_price_history where coin_id=\''.$coin_id.'\' order by id desc';
    $result = $db->query($query);

    $price_data = [];
    while ($row = $result->fetch_assoc()) {
        $price_data[] = (int) $row['price'];
    }

     $query = "SELECT * FROM coins WHERE id = '".$coin_id."'";
    $result = $db->query($query);

    if (!$result) {
        die('coin not found');
    }
    $coin = $result->fetch_assoc();
    array_unshift($price_data, (int) $coin['value']);


    header('Content-Type: application/json');
    echo json_encode($price_data);
?>
