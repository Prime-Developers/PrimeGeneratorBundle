<?php
/**
 * Created by PhpStorm.
 * User: leviothan
 * Date: 26/05/14
 * Time: 21:16
 */

namespace Prime\GeneratorBundle\Code;


use Symfony\Component\Yaml\Dumper;
use Zend\Code\Generator\AbstractGenerator;

class YamlGenerator extends AbstractGenerator
{

    protected $tree;

    public function fromArray(array $array)
    {
        $this->tree = $array;
    }

    public function generate()
    {
        $dumper = new Dumper();

        return $dumper->dump($this->tree, 2);
    }
}
