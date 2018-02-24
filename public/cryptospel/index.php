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
    <script type="text/javascript" src="/js/jquery-3.1.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/vue@2.5.13/dist/vue.js"></script>

    <style type="text/css">
        html {
            font-family: Montserrat, sans-serif;
            -webkit-font-smoothing: antialiased;
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
        }

        .price-change {
            font-size: 12px;
            font-weight: bold;
            display: block;
        }

        .news .price-change {
            display: inline-block;
            margin: 0 10px;
        }

        .price-change.down {
            color: #FF0000;
            background: url("down.svg") 10px center no-repeat;
            padding-left: 20px;
        }

        .price-change.up {
            color: #00CA01;
            background: url("up.svg") 10px center no-repeat;
            padding-left: 20px;
        }

        input[type=submit] {
            appearance: none;
            border-radius: 5px;
            background: black;
            color: white;
            padding: 12px 10px;
            outline: none;
            width: 100%;
            box-sizing: border-box;
            display: inline-block;
            font-size: 18px;
            font-weight: bold;
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

        .chart-container {
            display: block;
            padding: 10px 5px;
            padding-bottom: 0;
            height: 400px;
            overflow-x: scroll;
            overflow-y: hidden;
            direction:rtl;
            -webkit-overflow-scrolling: touch;
            background: black;
            border-radius: 4px;
            border: 2px solid #DADADA;

        }

        .chart {
            text-align: right;
            height: 400px;
            white-space: nowrap;
        }

        .chart > div {
            height: 400px;
            width: 20px;
            position: relative;
            display: inline-block;
            margin: 0 8px;

        }

        .chart > div div:first-child {
            width: 20px;
            position: absolute;
            left: 0;
            right: 0;
            bottom: 0;
            background: #7D29F4;
        }

        .chart > div div:last-child {
            position: absolute;
            left: 0;
            right: 0;
            bottom: 30px;
            font-size: 14px;
            color: white;
            transform: rotate(-90deg);
        }

        button {
            border: 0;
            outline: none;
            appearance: none;
            background: url('chart.svg') center center no-repeat;
            width: 30px;
            height: 30px;
        }

        .news {
            position: fixed;
            left: 0;
            right: 0;
            bottom: 0;
            height: 40px;
            background: black;
            width: 100%;
            overflow: hidden;
            padding-left: 100%;
        }


        @keyframes ticker {
          0% {
            -webkit-transform: translate(0, 0);
            transform: translate(0, 0);
          }

          100% {
            -webkit-transform: translate(-100%, 0);
            transform: translate(-100%, 0);
          }

        }

        .news > div {
         display: inline-block;
          height: 40px;
          line-height: 40px;  
          white-space: nowrap;
          padding-right: 150%;
          -webkit-animation-delay: 1s;
          animation-delay: 1s;
          animation-iteration-count: infinite;
          animation-timing-function: linear;
          animation-name: ticker;
          animation-duration: 30s;
        } 

        .news > div > div{
            display: inline-block;
            white-space: nowrap;
            height: 40px;
            line-height: 40px;  
            font-size: 12px;
            text-transform: uppercase;
            padding-right: 100px;
            color: white;   


        }

        main {
            padding-bottom: 80px;
        }
    </style>
</head>
<body>
    <main id="app">
        <h1>Coin overview</h1>

        <table>
            <thead>
                <tr>
                    <td>Coin</td>
                    <td>Value</td>
                    <td></td>
                </tr>
            </thead>
            <tbody>
                <tr v-for="coin in coins">
                    <td>{{ coin.name }}</td>
                    <td>
                        € {{ coin.value }}
                        <div class="price-change" :class="{up: coin.up, down: coin.down}">{{ coin.change }}</div>
                    </td>
                    <td>
                        <button v-on:click="selectCoin(coin.id, coin.name)"></button>
                    <td>
                </tr>

            </tbody>
        </table>

        <div v-if="selected_coin">
            <h1>{{ selected_coin.name }}</h1>
            <div v-if="!selected_coin_data">Laden...</div>
            <div class="chart-container" v-else>
                <div class="chart">
                    <div v-for="price in selected_coin_data" >
                        <div :style="{height: price/maxPrice*100+'%'}"></div>
                        <div>{{ price }} €</div>
                    </div>

                </div>
            </div>
        </div>

        <div class="news" v-if="hasNews">
            <div>
                <div v-for="n in currentNews">
                    <strong>{{n.coin.name}}</strong>: {{ n.text }}
                    <div class="price-change" :class="{up: n.coin.up, down: n.coin.down}">{{ n.coin.change }}</div>
                </div>
            </div>
        </div>
    </main>


    <script type="text/javascript">
        var app = new Vue({
          el: '#app',
          data: {
            coins: [],
            selected_coin: null,
            selected_coin_data: null,
            maxPrice: 1,
            currentNews: [],
            news: [],
            hasNews: false,
          },

          mounted: function() {
            this.getCoins();

            window.setInterval(function() {
                this.getCoins();

                if (this.selected_coin) {
                    this.selectCoin(this.selected_coin.id, this.selected_coin.name);
                }
             }.bind(this), 10000);

            window.setTimeout(function() {
                window.setInterval(function() {
                    this.currentNews = this.news;

                    this.hasNews = this.currentNews.length > 0;

                    this.$forceUpdate();
                 }.bind(this), 30000);
            }.bind(this), 1000);
          },

          methods: {
            getCoins: function() {
                $.getJSON( "get-coins.php", function( data ) {
                    console.log(data);
                    this.coins = data.coins;
                    this.news = data.news;
                    if (this.currentNews.length == 0) {
                        this.currentNews = this.news;
                        this.hasNews = this.currentNews.length > 0;
                    }
                }.bind(this));
            },
            selectCoin: function(id, name) {
                this.selected_coin = {id: id, name: name};
                // api request
                $.getJSON( "get-graph.php?id="+id, function( data ) {
                    this.selected_coin_data = data;
                    this.maxPrice = this.getMaxPrice();
                }.bind(this));
            },

            getMaxPrice: function() {
                var max = 0;
                for (var i = 0; i < this.selected_coin_data.length; i++) {
                    if (this.selected_coin_data[i] > max) {
                        max = this.selected_coin_data[i];
                    }
                }
                console.log(max);

                return max;
            }
          }
        })
    </script>
</body>
</html>
