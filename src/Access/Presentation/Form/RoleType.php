<?php declare(strict_types=1);

namespace App\Access\Presentation\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Regex;

final class RoleType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('name', TextType::class, [
            'label' => 'Role name',
            'help' => '2-30 chars: lowercase letters, digits, underscores; starts with a letter.',
            'constraints' => [
                new NotBlank(),
                new Regex(pattern: '/^[a-z][a-z0-9_]{1,29}$/', message: 'Use 2-30 chars: lowercase letters, digits, underscores; start with a letter.'),
            ],
        ]);
    }
}
