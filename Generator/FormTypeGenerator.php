<?php
/**
 * Created by PhpStorm.
 * User: leviothan
 * Date: 28/05/14
 * Time: 21:37
 */

namespace Prime\GeneratorBundle\Generator;

use Doctrine\ORM\Mapping\ClassMetadata;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpKernel\KernelInterface;
use Zend\Code\Generator\ClassGenerator;
use Doctrine\Bundle\DoctrineBundle\Registry;
use Zend\Code\Generator\DocBlock\Tag\ParamTag;
use Zend\Code\Generator\DocBlock\Tag\ReturnTag;
use Zend\Code\Generator\DocBlockGenerator;
use Zend\Code\Generator\FileGenerator;
use Zend\Code\Generator\MethodGenerator;
use Zend\Code\Generator\ParameterGenerator;

class FormTypeGenerator
{

    protected $kernel;
    protected $filesystem;
    protected $doctrine;

    public function __construct(KernelInterface $kernel, Filesystem $filesystem, Registry $doctrine)
    {
        $this->kernel = $kernel;
        $this->filesystem = $filesystem;
        $this->doctrine = $doctrine;
    }

    public function generate($entity, $entityBundleName, $targetBundleName)
    {
        $targetBundle = $this->kernel->getBundle($targetBundleName);
        $entityBundle = $this->kernel->getBundle($entityBundleName);

        $formPath = $targetBundle->getPath().'/Form/Type';
        $formNamespace = $targetBundle->getNamespace().'\\Form\\Type';
        $formClassName = $entity.'Type';

        /** @var ClassMetadata $metaData */
        $metaData = $this->doctrine->getManager()->getClassMetadata($entityBundle->getNamespace().'\\Entity\\'.$entity);

//        var_dump($metaData);die();

        $classGenerator = new ClassGenerator();
        $classGenerator
            ->setName($formClassName)
            ->setExtendedClass('AbstractType')
            ->setNamespaceName($formNamespace)
            ->addUse('Symfony\Component\Form\AbstractType')
            ->addUse('Symfony\Component\Form\FormBuilderInterface')
            ->addUse('Symfony\Component\OptionsResolver\OptionsResolverInterface')
            ->addMethodFromGenerator($this->getBuildFormMethodGenerator($entity, $metaData))
            ->addMethodFromGenerator($this->getSetDefaultOptionsMethodGenerator($entity, $entityBundleName))
            ->addMethodFromGenerator($this->getGetNameMethodGenerator($entity))
        ;

        $fileGenerator = new FileGenerator();
        $fileGenerator
            ->setClass($classGenerator)
        ;

        $this->filesystem->dumpFile($formPath.'/'.$formClassName.'.php', $fileGenerator->generate());

        return $formClassName;
    }

    protected function getBuildFormMethodGenerator($entity, ClassMetadata $metadata)
    {
        $methodGenerator = new MethodGenerator();
        $methodGenerator
            ->setParameter(
                new ParameterGenerator('builder', 'FormBuilderInterface')
            )
            ->setParameter(
                new ParameterGenerator('options', 'array')
            )
            ->setDocBlock(
                new DocBlockGenerator(
                    null,
                    null,
                    array(
                        new ParamTag('builder', array('FormBuilderInterface')),
                        new ParamTag('options', array('array')),
                    )
                )
            )
            ->setName('buildForm')
        ;

        $fields = $metadata->getFieldNames();

        $body = '$builder'."\n";
        $indentation = $methodGenerator->getIndentation();

        foreach ($fields as $field) {
            if ($field == 'id') {
                continue;
            }

            $mapping = $metadata->getFieldMapping($field);
            $fieldLabel = $entity.'.'.ucfirst($mapping['fieldName']);

            $body .= $indentation.'->add("'.$mapping['fieldName'].'", null, array('."\n".
                $indentation.$indentation.'"label" => "'.$fieldLabel.'"'."\n".
                $indentation.'))'."\n";
        }

        $body .= ";\n";

        $methodGenerator->setBody($body);

        return $methodGenerator;
    }

    protected function getSetDefaultOptionsMethodGenerator($entity, $entityBundle)
    {
        $methodGenerator = new MethodGenerator();

        $bundleNamespace = $this->kernel->getBundle($entityBundle)->getNamespace();

        $methodBody = '$resolver->setDefaults(array('."\n".
            $methodGenerator->getIndentation()."'data_class' => '".$bundleNamespace."\\Entity\\".$entity."',\n".
            $methodGenerator->getIndentation().'"translation_domain" => "form"'."\n".
        '));';


        $methodGenerator
            ->setParameter(
                new ParameterGenerator('resolver', 'OptionsResolverInterface')
            )
            ->setBody($methodBody)
            ->setDocBlock(
                new DocBlockGenerator(
                    null,
                    null,
                    array(
                        new ParamTag('resolver', array('OptionsResolverInterface'))
                    )
                )
            )
            ->setName('setDefaultOptions')
        ;

        return $methodGenerator;
    }

    protected function getGetNameMethodGenerator($entity)
    {
        $methodGenerator = new MethodGenerator();

        $methodGenerator
            ->setBody('return "'.$entity.'Type";')
            ->setDocBlock(
                new DocBlockGenerator(
                    null,
                    null,
                    array(
                        new ReturnTag(array('string'))
                    )
                )
            )
            ->setName('getName')
        ;

        return $methodGenerator;
    }
}
