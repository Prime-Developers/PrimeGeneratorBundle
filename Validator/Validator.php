<?php
/**
 * Created by PhpStorm.
 * User: leviothan
 * Date: 25/05/14
 * Time: 22:46
 */

namespace Prime\GeneratorBundle\Validator;


use Symfony\Component\HttpKernel\KernelInterface;

class Validator
{
    private $kernel;

    public function __construct(KernelInterface $kernel)
    {
        $this->kernel = $kernel;
    }

    public function validateRoute($route)
    {
        return $route;
    }

    public function validateBundle($answer)
    {
        return $answer;
    }

    public function validateEntity($answer)
    {
        list($bundle, $entity) = $this->parseShortcutNotation($answer);
        $bundle = $this->kernel->getBundle($bundle);

        $fullClassName = $bundle->getNamespace().'\\Entity\\'.$entity;

        if (!class_exists($fullClassName)) {
            throw new \Exception('Entity not found');
        }

        return $answer;
    }

    public function parseShortcutNotation($shortcut)
    {

        $entity = str_replace('/', '\\', $shortcut);

        if (false === $pos = strpos($entity, ':')) {
            $errorMessage = sprintf('The entity name must contain a : ("%s" given, expecting something like AcmeBlogBundle:Blog/Post)', $entity);
            throw new \InvalidArgumentException($errorMessage);
        }

        return array(substr($entity, 0, $pos), substr($entity, $pos + 1));
    }
}
