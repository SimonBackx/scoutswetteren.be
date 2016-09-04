<?php
  $protocol = "HTTP/1.0";
  if ( "HTTP/1.1" == $_SERVER["SERVER_PROTOCOL"] )
    $protocol = "HTTP/1.1";
  header( "$protocol 503 Service Unavailable", true, 503 );
  header( "Retry-After: 86400" );
?>
<!DOCTYPE html>
<html>
<head>
    <title>Scouts Prins Boudewijn Wetteren</title>
    <meta charset="UTF-8">
    <link href='https://fonts.googleapis.com/css?family=Montserrat:400,700' rel='stylesheet' type='text/css'>
    <link href='style.css' rel='stylesheet' type='text/css'>

    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">

</head>
<body>
   <div id="container">
       <img src="images/groot-gekleurd-logo.png" alt="Scouts Prins Boudewijn Wetteren">
        <h1>Onze website is even offline.</h1>
       <h2>Streekbieravond: Vrijdag 9 september 2016 om 19u</h2>
       <h2>Startdag: Zondag 11 september 2016 om 14u</h2>
       <p>E-mail: groepsleiding@scoutswetteren.be</p>
       <p><a href="https://www.facebook.com/scoutsprinsboudewijn">Facebook pagina</a></p>
   </div>
</body>
</html>