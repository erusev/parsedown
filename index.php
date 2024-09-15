<?php 

include 'Parsedown.php';

$Parsedown = new Parsedown();

echo $Parsedown->text('Horizontal rule made up of three stars

* * *

An unordered list

* List item
* Another list item
* There now follows two newlines and then another horizontal rule

* * *');

?>