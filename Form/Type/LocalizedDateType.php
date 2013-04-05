<?php
namespace Oro\Bundle\UserBundle\Form\Type;

use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Form\AbstractType;

use Symfony\Component\Form\FormView;
use Symfony\Component\Form\FormInterface;

class LocalizedDateType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function finishView(FormView $view, FormInterface $form, array $options)
    {
        $dateFormat = is_int($options['format']) ? $options['format'] : \IntlDateFormatter::SHORT;
        $timeFormat = \IntlDateFormatter::NONE;
        $calendar = \IntlDateFormatter::GREGORIAN;
        $pattern = is_string($options['format']) ? $options['format'] : null;

        $formatter = new \IntlDateFormatter(
            \Locale::getDefault(),
            $dateFormat,
            $timeFormat,
            'UTC',
            $calendar,
            $pattern
        );

        $view->vars['attr']['-data-format'] = str_replace(
            array('M', 'yy'),
            array('m', 'y'),
            $formatter->getPattern()
        );
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'years' => range(date('Y') - 120, date('Y')),
            'format' => \IntlDateFormatter::SHORT
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return 'date';
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'oro_localized_date';
    }
}