<?php
/**
 * Created by PhpStorm.
 * User: leviothan
 * Date: 25/05/14
 * Time: 22:45
 */

namespace Prime\GeneratorBundle\Generator;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpKernel\Bundle\BundleInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Zend\Code\Generator\ClassGenerator;
use Zend\Code\Generator\FileGenerator;
use Zend\Code\Generator\MethodGenerator;
use Zend\Code\Generator\ParameterGenerator;

class ControllerGenerator
{
    protected $kernel;
    protected $filesystem;
    protected $twig;

    protected $entity;
    protected $entityBundle;

    /** @var  BundleInterface */
    protected $targetBundle;

    public function __construct(KernelInterface $kernel, Filesystem $filesystem, \Twig_Environment $twig)
    {
        $this->kernel = $kernel;
        $this->filesystem = $filesystem;
        $this->twig = $twig;
    }

    public function generate($entity, $entityBundle, $targetBundleName, $actions, $formClass)
    {
        $targetBundle = $this->kernel->getBundle($targetBundleName);
        $bundlePath = $targetBundle->getPath();
        $controllerName = $entity.'Controller';
        $controllerNamespace = $targetBundle->getNamespace().'\\Controller';
        $controllerPath = $bundlePath.'/Controller';

        $this->entity = $entity;
        $this->targetBundle = $targetBundle;
        $this->entityBundle = $entityBundle;

        $controller = new ClassGenerator();
        $controller
            ->setName($controllerName)
            ->setNamespaceName($controllerNamespace)
            ->addUse('Symfony\Bundle\FrameworkBundle\Controller\Controller')
            ->addUse('Symfony\Component\HttpFoundation\Request')
            ->addUse($targetBundle->getNamespace().'\\Entity\\'.$entity)
            ->addUse($targetBundle->getNamespace().'\\Form\\Type\\'.$formClass)
            ->setExtendedClass('Controller')
        ;

        foreach ($actions as $action) {
            $methodName = $action['name'].'ActionGenerator';
            $controller->addMethodFromGenerator($this->$methodName(array(
                'formClass' => $formClass
            )));
        }

        $controller->addMethodFromGenerator($this->getRepositoryGenerator());

        $fileGenerator = new FileGenerator();
        $fileGenerator
            ->setClass($controller)
        ;

        $generatedCode = $fileGenerator->generate();

        $this->filesystem->dumpFile($controllerPath.'/'.$controllerName.'.php', $generatedCode, 0644);
    }

    /**
     * @return MethodGenerator
     */
    public function indexActionGenerator()
    {
        $template = $this->twig->loadTemplate('PrimeGeneratorBundle:Simple:action/index.php.twig');
        $methodGenerator = $this->getActionGenerator()
            ->setName('indexAction')
            ->setBody($template->render(array(
                'entity' => $this->entity,
                'targetBundleName' => $this->targetBundle->getName()
            )))
        ;

        return $methodGenerator;
    }

    /**
     * @param array $params
     * @return MethodGenerator
     */
    public function editActionGenerator(array $params)
    {
        $template = $this->twig->loadTemplate('PrimeGeneratorBundle:Simple:action/edit.php.twig');
        $methodGenerator = $this->getActionGenerator()
            ->setParameter(
                new ParameterGenerator('request', 'Request')
            )
            ->setParameter('id')
            ->setName('editAction')
            ->setBody($template->render(array(
                'entity' => $this->entity,
                'targetBundleName' => $this->targetBundle->getName(),
                'indexRoute' => $this->getIndexRoute(),
                'formClass' => $params['formClass']
            )))
        ;

        return $methodGenerator;
    }

    /**
     * @return MethodGenerator
     */
    public function removeActionGenerator()
    {
        $template = $this->twig->loadTemplate('PrimeGeneratorBundle:Simple:action/remove.php.twig');
        $methodGenerator = $this->getActionGenerator()
            ->setParameter('id')
            ->setName('removeAction')
            ->setBody($template->render(array(
                'entity' => $this->entity,
                'entityBundleName' => $this->entityBundle,
                'indexRoute' => $this->getIndexRoute()
            )))
        ;

        return $methodGenerator;
    }

    /**
     * @return MethodGenerator
     */
    public function getRepositoryGenerator()
    {
        $methodGenerator = new MethodGenerator();
        $methodGenerator
            ->addFlag(MethodGenerator::FLAG_PRIVATE)
            ->setName('getRepository')
            ->setBody(
                sprintf('return $this->getDoctrine()->getRepository("%s:%s");', $this->entityBundle, $this->entity)
            )
        ;

        return $methodGenerator;
    }


    /**
     * @return MethodGenerator
     */
    public function getActionGenerator()
    {
        $methodGenerator = new MethodGenerator();
        $methodGenerator
            ->addFlag(MethodGenerator::FLAG_PUBLIC)
        ;

        return $methodGenerator;
    }

    private function getIndexRoute()
    {
        $routeName =
            strtolower(str_replace('\\', '_', preg_replace('/Bundle$/', '', $this->targetBundle->getNamespace())))
            .'_'.strtolower($this->entity).'_list'
        ;

        return $routeName;
    }
}
