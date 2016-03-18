<?php

namespace Oro\Bundle\ActionBundle\Tests\Unit\Model\Assembler;

use Oro\Bundle\ActionBundle\Form\Type\OperationType;
use Oro\Bundle\ActionBundle\Model\Assembler\AttributeAssembler;
use Oro\Bundle\ActionBundle\Model\Assembler\FormOptionsAssembler;
use Oro\Bundle\ActionBundle\Model\Assembler\OperationActionGroupAssembler;
use Oro\Bundle\ActionBundle\Model\Assembler\OperationAssembler;
use Oro\Bundle\ActionBundle\Model\Operation;
use Oro\Bundle\ActionBundle\Model\OperationDefinition;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;

use Oro\Component\Action\Action\ActionFactory;
use Oro\Component\ConfigExpression\ExpressionFactory as ConditionFactory;

class OperationAssemblerTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject|DoctrineHelper */
    protected $doctrineHelper;

    /** @var OperationAssembler */
    protected $assembler;

    protected function setUp()
    {
        $this->doctrineHelper = $this->getMockBuilder('Oro\Bundle\EntityBundle\ORM\DoctrineHelper')
            ->disableOriginalConstructor()
            ->getMock();
        $this->doctrineHelper->expects($this->any())
            ->method('getEntityClass')
            ->willReturnCallback(function ($class) {
                return $class;
            });

        $this->assembler = new OperationAssembler(
            $this->getActionFactory(),
            $this->getConditionFactory(),
            $this->getAttributeAssembler(),
            $this->getFormOptionsAssembler(),
            $this->getOperationActionGroupAssembler(),
            $this->doctrineHelper
        );
    }

    protected function tearDown()
    {
        unset($this->assembler, $this->functionFactory, $this->conditionFactory, $this->attributeAssembler);
    }

    /**
     * @param array $configuration
     * @param array $expected
     *
     * @dataProvider assembleProvider
     */
    public function testAssemble(array $configuration, array $expected)
    {
        $definitions = $this->assembler->assemble($configuration);

        $this->assertEquals($expected, $definitions);
    }

    /**
     * @expectedException \Oro\Bundle\ActionBundle\Exception\MissedRequiredOptionException
     * @expectedExceptionMessage Option "label" is required
     */
    public function testAssembleWithMissedRequiredOptions()
    {
        $configuration = [
            'test_config' => [],
        ];

        $this->assembler->assemble($configuration);
    }

    /**
     * @return array
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function assembleProvider()
    {
        $definition1 = new OperationDefinition();
        $definition1
            ->setName('minimum_name')
            ->setLabel('My Label')
            ->setEntities(['Oro\Bundle\ActionBundle\Tests\Unit\Stub\TestEntity1'])
            ->setPreconditions([])
            ->setActions('preactions', [])
            ->setActions('form_init', [])
            ->setFormType(OperationType::NAME);

        $definition2 = clone $definition1;
        $definition2
            ->setName('maximum_name')
            ->setSubstituteOperation('test_operation_to_substitute')
            ->setRoutes(['my_route'])
            ->setGroups(['my_group'])
            ->setEnabled(false)
            ->setApplications(['application1'])
            ->setAttributes(['config_attr'])
            ->setPreconditions(['config_pre_cond'])
            ->setActions('preactions', ['config_pre_func'])
            ->setActions('form_init', ['config_form_init_func'])
            ->setFormOptions(['config_form_options'])
            ->setFrontendOptions(['config_frontend_options'])
            ->setOrder(77)
            ->setFormType(OperationType::NAME);

        $definition3 = clone $definition2;
        $definition3
            ->setName('maximum_name_and_acl')
            ->setEnabled(false)
            ->setApplications(['application1'])
            ->setAttributes(['config_attr'])
            ->setForAllEntities(true)
            ->setExcludeEntities(['Oro\Bundle\ActionBundle\Tests\Unit\Stub\TestEntity2'])
            ->setPreconditions([
                '@and' => [
                    ['@acl_granted' => 'test_acl'],
                    ['config_pre_cond']
                ]
            ])
            ->setActions('preactions', ['config_pre_func'])
            ->setActions('form_init', ['config_form_init_func'])
            ->setFormOptions(['config_form_options'])
            ->setFrontendOptions(['config_frontend_options'])
            ->setOrder(77)
            ->setFormType(OperationType::NAME);

        return [
            'no data' => [
                [],
                'expected' => [],
            ],
            'minimum data' => [
                [
                    'minimum_name' => [
                        'label' => 'My Label',
                        'entities' => [
                            '\Oro\Bundle\ActionBundle\Tests\Unit\Stub\TestEntity1'
                        ],
                    ]
                ]
                ,
                'expected' => [
                    'minimum_name' => new Operation(
                        $this->getActionFactory(),
                        $this->getConditionFactory(),
                        $this->getAttributeAssembler(),
                        $this->getFormOptionsAssembler(),
                        $this->getOperationActionGroupAssembler(),
                        $definition1
                    )
                ],
            ],
            'maximum data' => [
                [
                    'maximum_name' => [
                        'label' => 'My Label',
                        'substitute_operation' => 'test_operation_to_substitute',
                        'entities' => ['\Oro\Bundle\ActionBundle\Tests\Unit\Stub\TestEntity1'],
                        'routes' => ['my_route'],
                        'groups' => ['my_group'],
                        'enabled' => false,
                        'applications' => ['application1'],
                        'attributes' => ['config_attr'],
                        'preactions' => ['config_pre_func'],
                        'preconditions' => ['config_pre_cond'],
                        'form_init' => ['config_form_init_func'],
                        'form_options' => ['config_form_options'],
                        'frontend_options' => ['config_frontend_options'],
                        'order' => 77,
                    ]
                ],
                'expected' => [
                    'maximum_name' => new Operation(
                        $this->getActionFactory(),
                        $this->getConditionFactory(),
                        $this->getAttributeAssembler(),
                        $this->getFormOptionsAssembler(),
                        $this->getOperationActionGroupAssembler(),
                        $definition2
                    )
                ],
            ],
            'maximum data and acl_resource' => [
                [
                    'maximum_name_and_acl' => [
                        'label' => 'My Label',
                        'substitute_operation' => 'test_operation_to_substitute',
                        'entities' => ['\Oro\Bundle\ActionBundle\Tests\Unit\Stub\TestEntity1'],
                        'routes' => ['my_route'],
                        'groups' => ['my_group'],
                        'enabled' => false,
                        'for_all_entities' => true,
                        'exclude_entities' => ['\Oro\Bundle\ActionBundle\Tests\Unit\Stub\TestEntity2'],
                        'applications' => ['application1'],
                        'attributes' => ['config_attr'],
                        'preactions' => ['config_pre_func'],
                        'preconditions' => ['config_pre_cond'],
                        'form_init' => ['config_form_init_func'],
                        'form_options' => ['config_form_options'],
                        'frontend_options' => ['config_frontend_options'],
                        'order' => 77,
                        'acl_resource' => 'test_acl',
                    ]
                ],
                'expected' => [
                    'maximum_name_and_acl' => new Operation(
                        $this->getActionFactory(),
                        $this->getConditionFactory(),
                        $this->getAttributeAssembler(),
                        $this->getFormOptionsAssembler(),
                        $this->getOperationActionGroupAssembler(),
                        $definition3
                    )
                ],
            ],
        ];
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|ActionFactory
     */
    protected function getActionFactory()
    {
        return $this->getMockBuilder('Oro\Component\Action\Action\ActionFactory')
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|ConditionFactory
     */
    protected function getConditionFactory()
    {
        return $this->getMockBuilder('Oro\Component\ConfigExpression\ExpressionFactory')
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|AttributeAssembler
     */
    protected function getAttributeAssembler()
    {
        return $this->getMockBuilder('Oro\Bundle\ActionBundle\Model\Assembler\AttributeAssembler')
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|FormOptionsAssembler
     */
    protected function getFormOptionsAssembler()
    {
        return $this->getMockBuilder('Oro\Bundle\ActionBundle\Model\Assembler\FormOptionsAssembler')
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|OperationActionGroupAssembler
     */
    protected function getOperationActionGroupAssembler()
    {
        $assembler = new OperationActionGroupAssembler();
        $assembler->addConfigurationPass(
            $this->getMockBuilder('Oro\Bundle\ActionBundle\Model\ConfigurationPass\ReplacePropertyPath')
                ->getMock()
        );

        return $assembler;
    }
}
