```
<?php

$message = 'fenced code block';
echo $message;
```

~~~
tilde
~~~

```php
echo 'language identifier';
```

```php
<?php
echo 'Hello World',"\n";

function fibonacci($n,$first = 0,$second = 1)
{
    $fib = [$first,$second];
    for($i=1;$i<$n;$i++)
    {
        $fib[] = $fib[$i]+$fib[$i-1];
    }
    return $fib;
}
```

```c
#include <stdio.h>
int main() {
    printf("Hello World\n");

    return 0;
}
```
