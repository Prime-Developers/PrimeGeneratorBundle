<?php
/**
 * Created by PhpStorm.
 * User: leviothan
 * Date: 27/05/14
 * Time: 23:04
 */

namespace Prime\GeneratorBundle\Generator;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpKernel\KernelInterface;

class ViewGenerator
{
    protected $kernel;
    protected $twig;
    protected $filesystem;

    public function __construct(KernelInterface $kernel, \Twig_Environment $twig, Filesystem $filesystem)
    {
        $this->kernel = $kernel;
        $this->twig = $twig;
        $this->filesystem = $filesystem;
    }

    public function generate($actions, $bundle, $entity)
    {
        $bundlePath = $this->kernel->getBundle($bundle)->getPath();
        foreach ($actions as $action) {
            $viewPath = $bundlePath.'/Resources/views/'.$entity.'/'.$action['name'].'.html.twig';
            $content = $this->generateView($action);

            if (empty($content)) {
                continue;
            }
            $this->filesystem->dumpFile($viewPath, $content, 0644);
        }
    }

    public function generateView($action)
    {
        $bundle = $this->kernel->getBundle('PrimeGeneratorBundle');
        $filePath = $bundle->getPath().'/Resources/views/Simple/view/'.$action['name'].'.html.twig';

        if (!$this->filesystem->exists($filePath)) {
            return '';
        }

        $template = $this->twig->loadTemplate('PrimeGeneratorBundle:Simple:view/'.$action['name'].'.html.twig');
        $content = $template->render($action);

        return $content;
    }
}
