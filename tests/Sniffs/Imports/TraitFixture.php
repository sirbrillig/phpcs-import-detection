<?php

// Declare the interface 'GoodTrait'
trait GoodTrait
{
    private $traitVar = "trait";

    public function getTraitVar()
    {
        return $this->traitVar;
    };
}

// Use the trait
// This will work
class UseTrait
{
    use GoodTrait;
    private $vars = array();
  
    public function setVariable($name, $var)
    {
        $this->vars[$name] = $var;
    }
  
    public function getHtml($template)
    {
        foreach($this->vars as $name => $value) {
            $template = str_replace('{' . $name . '}', $value, $template);
        }
 
        return $template;
    }
}

class BadClass
    use BadTrait;
}
