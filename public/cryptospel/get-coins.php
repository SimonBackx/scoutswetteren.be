<?php
    if (isset($_ENV["DEBUG"]) && $_ENV["DEBUG"] == 1) {
        $db= new mysqli('db', 'root', 'root', 'cryptospel');
    } else {
        $db = new mysqli('127.0.0.1', 'root', 'root', 'cryptospel');
    }

    // coins ophalen
    $query = 'SELECT * FROM coins';
    $result = $db->query($query);

    $coinData = [];
    while ($row = $result->fetch_assoc()) {
        $name = $row['name'];
        $value = $row['value'];
        $id = $row['id'];

        $coinData[$id] = (object) [
            'up' => false,
            'name' => $name,
            'id' => $id,
            'down' => false,
            'change' => '=',
            'value' => $value
        ];
    }

    $news = [];

    // coin voorlaatste prijs ophalen
    $query = 'select a.*
        from coin_price_history a
        left join coin_price_history b on b.id > a.id and b.coin_id = a.coin_id
        where b.id is null';
    $result = $db->query($query);

    while ($row = $result->fetch_assoc()) {
        $myNews = null;
        if (isset($row['news']) && strlen($row['news']) > 0) {
            $myNews = $row['news'];
        }

        $id = $row['coin_id'];
        $coin = $coinData[$id];

        if ($row['price'] == 0) {
            // News toeveogen
            continue;
        }

        $coin->change = round((($coin->value - $row['price']) / abs($row['price'])) * 100, 2);

        if ($coin->change > 0) {
            $coin->up = true;
        } elseif ($coin->change < 0) {
            $coin->change = -$coin->change;
            $coin->down = true;
        }
        if ($coin->change == 0) {
            $coin->change = '=';
        } else {
            $coin->change = $coin->change . '%';
        }

        if (isset($myNews)) {
            $news[] = (object) [
                'text' => $myNews,
                'coin' => $coin,
            ];
        }
    }

    
    header('Content-Type: application/json');
    echo json_encode(array('coins' => array_values($coinData), 'news' => $news));
?>
