<?php
/**
 * Created by PhpStorm.
 * User: leviothan
 * Date: 25/05/14
 * Time: 21:54
 */

namespace Prime\GeneratorBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Helper\DialogHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class CrudCommand extends ContainerAwareCommand
{
    protected $actions = array(
        array(
            'name' => 'index',
            'view' => 'index',
            'route_suffix' => 'list'
        ),
        array(
            'name' => 'edit',
            'view' => 'edit',
            'route_suffix' => 'edit',
            'route_params' => array(
                array(
                    'name' => 'id',
                    'required' => false
                )
            )
        ),
        array(
            'name' => 'remove',
            'view' => 'remove',
            'route_suffix' => 'remove',
            'route_params' => array(
                array(
                    'name' => 'id',
                )
            )
        ),
    );

    protected function configure()
    {
        $this
            ->setName('crud:simple')
            ->setDescription('Generate simple crud')
            ->addOption('entity', null, InputOption::VALUE_NONE, '')
            ->addOption('target-bundle', null, InputOption::VALUE_NONE, '')
            ->addOption('route-prefix', null, InputOption::VALUE_NONE, '')
        ;
    }

    protected function interact(InputInterface $input, OutputInterface $output)
    {
        /** @var DialogHelper $dialog */
        $dialog = $this->getHelperSet()->get('dialog');

        $bundleNames = array_keys($this->getContainer()->get('kernel')->getBundles());

        $output->writeln('You must use the shortcut notation like <comment>AcmeBlogBundle:Post</comment>.');

        $entity = $dialog->askAndValidate(
            $output,
            '<info>Enter the Entity</info>: ',
            array($this->getContainer()->get('prime.crud_command_validator'), 'validateEntity'),
            false,
            null,
            $bundleNames
        );
        $input->setOption('entity', $entity);

        list($entityBundle, $entityName) = $this
                                            ->getContainer()
                                            ->get('prime.crud_command_validator')
                                            ->parseShortcutNotation($entity);

        $targetBundle = $dialog->askAndValidate(
            $output,
            sprintf('<info>Enter the CRUD target bundle</info> [<comment>%s</comment>]: ', $entityBundle),
            array($this->getContainer()->get('prime.crud_command_validator'), 'validateBundle'),
            false,
            $entityBundle,
            $bundleNames
        );
        $input->setOption('target-bundle', $targetBundle);

        $routePrefix = $dialog->askAndValidate(
            $output,
            sprintf('<info>Enter the route prefix</info> [<comment>%s</comment>]: ', '/'.strtolower($entityName)),
            array($this->getContainer()->get('prime.crud_command_validator'), 'validateRoute'),
            false,
            '/'.strtolower($entityName)
        );
        $input->setOption('route-prefix', $routePrefix);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        list($entityBundle, $entity) = $this
                                            ->getContainer()
                                            ->get('prime.crud_command_validator')
                                            ->parseShortcutNotation($input->getOption('entity'));

        $targetBundle = $input->getOption('target-bundle');

        $routeGenerator = $this->getContainer()->get('prime.crud_route_generator');
        $routeGenerator->generate($input->getOption('route-prefix'), $targetBundle, $entity, $this->actions);

        $formGenerator = $this->getContainer()->get('prime.crud_form_generator');
        $formClass = $formGenerator->generate($entity, $entityBundle, $targetBundle);

        $controllerGenerator = $this->getContainer()->get('prime.crud_controller_generator');
        $controllerGenerator->generate($entity, $entityBundle, $targetBundle, $this->actions, $formClass);

        $viewGenerator = $this->getContainer()->get('prime.crud_view_generator');
        $viewGenerator->generate($this->actions, $targetBundle, $entity);

        $output->writeln('Nothing generated. Ha-ha');
    }
}
