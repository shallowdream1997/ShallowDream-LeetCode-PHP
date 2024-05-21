<?php


class Annotate
{
    public $name;

    public function __construct($options = []){
        if (isset($options['value'])) {
            $this->name = $options['value'];
        }
    }
}



class MyClass
{
    // 类的其他代码
}

$reflectionClass = new ReflectionClass('MyClass');
$classAnnotations = $reflectionClass->getDocComment();
preg_match('/@Annotate\((.*?)\)/', $classAnnotations, $matches);

if ($matches) {
    $annotationOptions = eval('return ' . $matches[1] . ';');
    $myAnnotation = new Annotate($annotationOptions);
    echo $myAnnotation->name; // 输出: Hello, World!
}