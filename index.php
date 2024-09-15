<?php 

include 'Parsedown.php';

$Parsedown = new Parsedown();

echo $Parsedown->text('Horizontal rule made up of three stars

Garcia Isaias Manuel

* * *

An unordered list

* List item
* Another list item
* There now follows two newlines and then another horizontal rule

* * *

Garcia Isaias Manuel
');

?>