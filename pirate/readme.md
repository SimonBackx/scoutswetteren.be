The wheel folder contains every core element of the pirate CMS. It loads the sails (± modules) and ships (± themes) and provides some own services modules can plug into.

Main flow: (zeer snel geschreven)

Een sail bepaalt enkele routes waarvoor hij een pagina wil tonen. Hij kan hiervoor alle mogelijke criteria gebruiken.

Eens een sail een url opeist, zal de sail die pagina moeten opladen (instnatie aanmaken en doorgeven).

De page instantie bepaalt de inhoud van de pagina, waarbij het gebruik maakt van templates in de eigen templates folder - die overschreven kunnen worden in de globale templates folder -  en geeft deze door aan een layout instantie.

Een sail kan ook blocks bevatten: dit zijn stukken slimme HTML code die op verschillende pagina's kunnen worden toegevoegd. Ook hierbij kan gebruik worden gemaakt van templates.

Deze layout instantie voegt bepaalde dingen toe (zoals header, footer) die vaak herhaalt moeten worden en geeft de uiteindelijke HTML code terug die zal worden getoond op het scherm.