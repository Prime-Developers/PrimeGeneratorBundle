<?php
/**
 * Created by PhpStorm.
 * User: leviothan
 * Date: 26/05/14
 * Time: 17:13
 */

namespace Prime\GeneratorBundle\Generator;


use Prime\GeneratorBundle\Code\YamlGenerator;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Yaml\Parser;
use Zend\Code\Generator\FileGenerator;

class RouteGenerator
{

    protected $kernel;
    protected $filesystem;

    protected $routePrefix;
    protected $targetBundleName;
    protected $entityName;

    public function __construct(KernelInterface $kernel, Filesystem $filesystem)
    {
        $this->filesystem = $filesystem;
        $this->kernel = $kernel;
    }


    public function generate($routePrefix, $targetBundleName, $entityName, $actions)
    {
        $targetBundle = $this->kernel->getBundle($targetBundleName);

        $this->routePrefix = $routePrefix;
        $this->targetBundleName = $targetBundleName;
        $this->entityName = $entityName;

        $routingFile = $targetBundle->getPath().'/Resources/config/routing.yml';
        $oldBody = file_get_contents($routingFile);
        $newBody = "\n";

        foreach ($actions as $action) {
            $newBody .= $this->generateAction($action)."\n";
        }

        $this->filesystem->dumpFile($routingFile, $oldBody.$newBody, 0644);
    }

    protected function generateAction($action)
    {
        $routeName = $this->getBaseRouteName().'_'.strtolower($this->entityName);
        $pattern = $this->routePrefix;
        $defaults = array(
            '_controller' => $this->targetBundleName.':'.$this->entityName.':'.$action['route_suffix']
        );

        if (!empty($action['route_suffix'])) {
            $routeName .= '_'.$action['route_suffix'];
            $pattern .= '/'.$action['route_suffix'];
        }

        $params = array();
        if (!empty($action['route_params']) and is_array($action['route_params'])) {
            foreach ($action['route_params'] as $routeParam) {
                $params[] = '{'.$routeParam['name'].'}';
                if (!empty($routeParam['required']) and !$routeParam['required']) {
                    $defaults[$routeParam['name']] = null;
                }
            }

            $pattern .= '/'.implode('/', $params);
        }

        $route = array(
            $routeName => array(
                'pattern' => $pattern,
                'defaults' => $defaults
            )
        );

        return $this->generateYaml($route);
    }

    protected function generateYaml(array $array)
    {
        $generator = new YamlGenerator();
        $generator->fromArray($array);

        $body = $generator->generate();

        return $body;
    }

    protected function getBaseRouteName()
    {
        $bundleNamespace = $this->kernel->getBundle($this->targetBundleName)->getNamespace();

        $routeName = strtolower(str_replace('\\', '_', preg_replace('/Bundle$/', '', $bundleNamespace)));

        return $routeName;
    }
}
